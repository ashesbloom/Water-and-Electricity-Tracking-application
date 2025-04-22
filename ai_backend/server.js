// ai-backend/server.js
import express from 'express';
import dotenv from 'dotenv';
import cors from 'cors';
import { GoogleGenerativeAI, HarmCategory, HarmBlockThreshold } from '@google/generative-ai';

// --- Configuration ---
dotenv.config(); // Load .env file from the 'ai-backend' directory
const app = express();
const port = process.env.PORT || 3000; // Use port 3000 unless specified in .env
const GOOGLE_API_KEY = process.env.GEMINI_API_KEY; // Load key from .env

// --- API Key Check ---
if (!GOOGLE_API_KEY) {
    console.error("\nFATAL ERROR: GEMINI_API_KEY is not defined in the .env file.");
    console.error("Please create a '.env' file in the 'ai-backend' directory with the line:");
    console.error("GEMINI_API_KEY=YOUR_API_KEY_HERE\n");
    process.exit(1); // Exit if key is missing
}

// --- Initialize Gemini ---
let genAI;
let model;
try {
    genAI = new GoogleGenerativeAI(GOOGLE_API_KEY);
    model = genAI.getGenerativeModel({
        model: "gemini-1.5-flash-latest", // Or "gemini-pro" if flash fails
    });
    console.log("Gemini AI SDK Initialized. Model:", model.model);
} catch (error) {
     console.error("FATAL ERROR: Failed to initialize GoogleGenerativeAI.");
     console.error("Ensure your API key is valid and has permissions.");
     console.error("Error details:", error.message);
     process.exit(1);
}


// --- Gemini Configuration (Optional but Recommended) ---
const generationConfig = {
    temperature: 0.7, // Controls randomness
    topP: 0.95,
    topK: 64,
    maxOutputTokens: 4096,
    responseMimeType: "text/plain",
};

const safetySettings = [
    { category: HarmCategory.HARM_CATEGORY_HARASSMENT, threshold: HarmBlockThreshold.BLOCK_MEDIUM_AND_ABOVE },
    { category: HarmCategory.HARM_CATEGORY_HATE_SPEECH, threshold: HarmBlockThreshold.BLOCK_MEDIUM_AND_ABOVE },
    { category: HarmCategory.HARM_CATEGORY_SEXUALLY_EXPLICIT, threshold: HarmBlockThreshold.BLOCK_MEDIUM_AND_ABOVE },
    { category: HarmCategory.HARM_CATEGORY_DANGEROUS_CONTENT, threshold: HarmBlockThreshold.BLOCK_MEDIUM_AND_ABOVE },
];

// --- Middleware ---
app.use(cors({ origin: 'http://localhost' })); // Allow requests from localhost
app.use(express.json()); // Parse JSON request bodies

// --- API Route for Chat ---
app.post('/api/chat', async (req, res) => {
    // Expect 'message' key from frontend
    const { message } = req.body;

    // Validate using 'message' variable
    if (!message || typeof message !== 'string' || message.trim() === '') {
        return res.status(400).json({ error: "Missing or invalid 'userMessage'." });
    }

    // --- Simulate Fetching/Receiving User Data ---
    // TODO: Replace with actual data source later
    const simulatedUsageData = [
        // Week 1
        { date: '2025-04-14', type: 'Electricity', value: 11.5, notes: 'Normal Monday' }, { date: '2025-04-14', type: 'Water', value: 175, notes: '' },
        { date: '2025-04-15', type: 'Electricity', value: 12.8, notes: 'Used oven' }, { date: '2025-04-15', type: 'Water', value: 180, notes: 'Laundry day' },
        { date: '2025-04-16', type: 'Electricity', value: 10.5, notes: 'Out most of the day' }, { date: '2025-04-16', type: 'Water', value: 150, notes: '' },
        { date: '2025-04-17', type: 'Electricity', value: 13.0, notes: '' }, { date: '2025-04-17', type: 'Water', value: 185, notes: '' },
        { date: '2025-04-18', type: 'Electricity', value: 11.8, notes: '' }, { date: '2025-04-18', type: 'Water', value: 180, notes: '' },
        { date: '2025-04-19', type: 'Electricity', value: 14.1, notes: 'Weekend - more TV/Computer use' }, { date: '2025-04-19', type: 'Water', value: 195, notes: 'Washing machine run twice.' },
        { date: '2025-04-20', type: 'Electricity', value: 15.5, notes: 'High usage day, guests over.' }, { date: '2025-04-20', type: 'Water', value: 210, notes: 'Watered the garden. Guests used water.' },
        // Week 2
        { date: '2025-04-21', type: 'Electricity', value: 12.0, notes: 'Back to normal' }, { date: '2025-04-21', type: 'Water', value: 170, notes: '' },
        { date: '2025-04-22', type: 'Electricity', value: 12.5, notes: '' }, { date: '2025-04-22', type: 'Water', value: 178, notes: 'Laundry' },
        { date: '2025-04-23', type: 'Electricity', value: 16.0, notes: 'Air conditioner used heavily - hot day' }, { date: '2025-04-23', type: 'Water', value: 190, notes: 'Extra shower due to heat' },
        { date: '2025-04-24', type: 'Electricity', value: 13.5, notes: 'AC used moderately' }, { date: '2025-04-24', type: 'Water', value: 180, notes: '' },
        { date: '2025-04-25', type: 'Electricity', value: 11.9, notes: '' }, { date: '2025-04-25', type: 'Water', value: 175, notes: '' },
        { date: '2025-04-26', type: 'Electricity', value: 13.8, notes: 'Weekend usage' }, { date: '2025-04-26', type: 'Water', value: 192, notes: 'Cleaned the car' },
        { date: '2025-04-27', type: 'Electricity', value: 13.2, notes: '' }, { date: '2025-04-27', type: 'Water', value: 188, notes: '' },
    ];
    // --- End Data Simulation ---

    // --- Construct the **IMPROVED** Prompt for Gemini ---
    let prompt = `You are GridSync AI, a friendly and helpful AI assistant specialized in analyzing electricity (kWh) and water (Litres) usage data for a user in India. Your primary goal is to help the user understand their usage patterns based on the data provided.

Instructions:
1.  **Analyze Data:** When the user asks a question about their usage (e.g., "highest usage?", "usage last Tuesday?", "compare weeks?", "why was usage high on...?"), answer primarily using the provided 'User's Recent Usage Data'. Be factual and concise. If the data doesn't contain the answer, state that clearly.
2.  **Handle Greetings:** If the user simply greets you (e.g., "hi", "hello", "good morning"), respond with a friendly greeting back.
3.  **General Knowledge/Tips:** If the user asks for general energy/water saving tips or related quick facts (e.g., "how to save water?", "average electricity use?"), provide a brief, helpful, general answer. Do not provide financial advice.
4.  **Clarification:** If the user's request is unclear or very broad, ask politely for more specific details.
5.  **Tone:** Maintain a helpful, encouraging, and slightly informal tone.
6.  **Context:** Assume today's date is around April 28, 2025 for relative questions like 'last week'.

User's Recent Usage Data:
`;
    if (!simulatedUsageData || simulatedUsageData.length === 0) {
        prompt += "No usage data available.\n";
    } else {
        // Send all simulated data for now
        simulatedUsageData.forEach(reading => {
            prompt += `- Date: ${reading.date}, Type: ${reading.type}, Value: ${reading.value}` + (reading.notes ? `, Notes: "${reading.notes}"` : "") + "\n";
        });
    }

    prompt += `\nUser Question: "${message}"

GridSync AI Response:
`; // Added a label for the AI's turn

    console.log("--- Sending Prompt to Gemini ---");
    // console.log(prompt); // Keep commented unless debugging full prompt
    console.log("User Question:", message);
    console.log("-----------------------------");

    try {
        // --- Call Gemini API ---
        const result = await model.generateContent({
            contents: [{ role: "user", parts: [{ text: prompt }] }],
            generationConfig,
            safetySettings,
        });

        // --- Process Response ---
        if (!result.response) {
             console.error("Gemini API Error: No response received.");
             const feedback = result.promptFeedback;
             const blockReason = feedback?.blockReason;
             let errorMessage = "AI failed to generate a response.";
             if (blockReason) {
                 errorMessage = `Content blocked by API safety settings. Reason: ${blockReason}`;
                 console.warn("Safety Block Reason:", blockReason);
                 return res.status(503).json({ error: errorMessage });
             } else {
                 return res.status(500).json({ error: errorMessage });
             }
        }

        const responseText = result.response.text();

        console.log("--- Received Response from Gemini ---");
        // console.log(responseText); // Keep commented unless debugging full response
        console.log("------------------------------------");

        // Send response back to frontend
        res.json({ reply: responseText }); // Use 'reply' key as expected by frontend

    } catch (error) {
        console.error("Error calling Gemini API or processing response:", error);
        res.status(500).json({ error: "Failed to get response from AI assistant. Please check server logs." });
    }
});

// --- Start Server ---
app.listen(port, () => {
    console.log(`Node.js AI backend server running at http://localhost:${port}`);
    console.log("Waiting for requests to /api/chat ...");
});
