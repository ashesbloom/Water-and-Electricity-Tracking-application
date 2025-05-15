// --- ai_backend/server.js (Using ES Module Syntax) ---

import express from 'express';
import fetch from 'node-fetch';
import cors from 'cors';
import dotenv from 'dotenv';

// Load environment variables from .env file
dotenv.config();

const app = express();
app.use(express.json()); // Middleware to parse JSON bodies

// Configure CORS
const allowedOrigin = process.env.PHP_FRONTEND_ORIGIN || 'http://localhost';
app.use(cors({ origin: allowedOrigin }));
console.log(`[CORS] Allowing requests from origin: ${allowedOrigin}`);


// Gemini API Key and URL
const GEMINI_API_KEY = process.env.GEMINI_API_KEY;
if (!GEMINI_API_KEY) {
    console.error("FATAL ERROR: GEMINI_API_KEY is not defined in environment variables.");
    process.exit(1);
}
const GEMINI_API_URL = `https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent?key=${GEMINI_API_KEY}`;

// PHP Data API URL
const PHP_DATA_API_URL = process.env.PHP_DATA_API_URL || 'http://localhost/tracker/api/chatbotData';

// --- Define Cost Rates ---
const ELECTRICITY_RATE_PER_KWH = 7.00; // ₹ per kWh
const WATER_RATE_PER_KL = 10.00;      // ₹ per 1000 Litres (kL)

// --- Helper function to call Gemini API ---
async function callGemini(prompt, history = []) {
    // ... (callGemini function remains the same) ...
    const contents = history.map(entry => ({
        role: entry.role,
        parts: [{ text: entry.text }]
    }));
    contents.push({ role: 'user', parts: [{ text: prompt }] });
    const data = { contents };
    try {
        const response = await fetch(GEMINI_API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        if (!response.ok) {
            const errorText = await response.text();
            console.error(`[callGemini] Gemini API Error (${response.status}): ${errorText}`);
            try {
                const errorJson = JSON.parse(errorText);
                if (errorJson?.error?.message) return `Sorry, I encountered an API error (${response.status}): ${errorJson.error.message}`;
            } catch (e) { /* Ignore */ }
            return `Sorry, I encountered an API error (${response.status}).`;
        }
        const responseData = await response.json();
        if (responseData?.promptFeedback?.blockReason) {
            console.warn(`[callGemini] Response blocked: ${responseData.promptFeedback.blockReason}`);
            return `My response was blocked: ${responseData.promptFeedback.blockReason}.`;
        }
        if (responseData?.candidates?.[0]?.finishReason && responseData.candidates[0].finishReason !== 'STOP') {
             console.warn(`[callGemini] Response finished unexpectedly: ${responseData.candidates[0].finishReason}`);
             return `My response generation was interrupted (${responseData.candidates[0].finishReason}).`;
        }
        const text = responseData?.candidates?.[0]?.content?.parts?.[0]?.text;
        if (text) return text;
        else {
            console.error("[callGemini] Could not extract text:", JSON.stringify(responseData));
            return "Sorry, I received an unexpected response format from the AI.";
        }
    } catch (error) {
        console.error("[callGemini] Error calling Gemini API:", error);
        return "Sorry, there was an error connecting to the AI service.";
    }
}

// --- Main Chat Endpoint ---
app.post('/api/chat', async (req, res) => {
    const { message, userId } = req.body;

    if (!message || !userId) {
        console.warn("[/api/chat] Bad Request: Missing message or userId");
        return res.status(400).json({ error: 'Missing message or userId' });
    }

    try {
        // --- Step 1: Intent Recognition & Parameter Extraction ---
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const todayDate = `${year}-${month}-${day}`;
        const veryStartDate = '2000-01-01'; // Define a very early start date for "all time" queries

        // *** REFINED Intent Prompt ***
        const intentPrompt = `
            Analyze the user query about their utility usage. Identify the key intent and extract parameters precisely.
            Intents: 'get_peak_usage', 'search_notes', 'get_total_usage', 'calculate_cost', 'search_and_sum_usage', 'general_query'.
            Parameters:
            - usageType: 'electricity', 'water', or null if not specified or ambiguous.
            - dateRange: Interpret relative ranges ('today', 'yesterday', 'this week', 'last week', 'this month', 'last month', 'this year', 'all time'/'till now'). Convert them to specific startDate and endDate (YYYY-MM-DD). The current date is ${todayDate}. For 'all time' or 'till now', use startDate '${veryStartDate}' and endDate '${todayDate}'. Also handle specific dates like 'YYYY-MM', 'YYYY-MM-DD', or ranges like 'YYYY-MM-DD to YYYY-MM-DD'. If only a single date is mentioned (e.g., "on 2025-04-16"), set both startDate and endDate to that date. Default to 'today' (startDate=${todayDate}, endDate=${todayDate}) if no date/range is specified.
            - keywords: For 'search_notes' or 'search_and_sum_usage'. Extract the core keywords or item names (e.g., 'AC', 'leak', 'important', 'guest visit').
            - explicitAmount: A specific usage amount mentioned by the user (e.g., "5 kwh", "1500 litres"). Extract the number only.

            Respond ONLY with a valid JSON object: {"intent": "...", "parameters": {"usageType": ..., "startDate": ..., "endDate": ..., "keywords": ..., "explicitAmount": ...}}.
            - If the query asks about cost, use intent 'calculate_cost'.
            - If the query asks for total usage related to specific keywords/items (e.g., "usage for AC", "water used for garden"), use intent 'search_and_sum_usage'.
            - If the query asks only to find notes with keywords, use 'search_notes'.
            - If the query asks for overall total usage for a period, use 'get_total_usage'.
            - If the query asks for the highest usage point, use 'get_peak_usage'.
            - If no specific data-related intent is detected, use 'general_query'.

            Examples:
            "how much did my electricity cost today?" -> {"intent": "calculate_cost", "parameters": {"usageType": "electricity", "startDate": "${todayDate}", "endDate": "${todayDate}"}}
            "cost of 1500 litres water" -> {"intent": "calculate_cost", "parameters": {"usageType": "water", "explicitAmount": 1500}}
            "peak usage last month" -> {"intent": "get_peak_usage", "parameters": {"usageType": null, "startDate": "YYYY-MM-01", "endDate": "YYYY-MM-DD"}}
            "notes about leak" -> {"intent": "search_notes", "parameters": {"keywords": "leak"}}
            "total electricity usage for AC this month" -> {"intent": "search_and_sum_usage", "parameters": {"usageType": "electricity", "keywords": "AC", "startDate": "${year}-${month}-01", "endDate": "${todayDate}"}}
            "total water usage till now" -> {"intent": "get_total_usage", "parameters": {"usageType": "water", "startDate": "${veryStartDate}", "endDate": "${todayDate}"}}
            "electricity usage on 2025-04-16" -> {"intent": "get_total_usage", "parameters": {"usageType": "electricity", "startDate": "2025-04-16", "endDate": "2025-04-16"}}

            User Query: "${message}"
        `;
        // *** END REFINED Intent Prompt ***

        const intentResponseText = await callGemini(intentPrompt);
        let intentData;
        try {
            const jsonMatch = intentResponseText.match(/```json\s*([\s\S]*?)\s*```/);
            const jsonStringToParse = jsonMatch ? jsonMatch[1].trim() : intentResponseText.trim();
            intentData = JSON.parse(jsonStringToParse);
            if (!intentData.intent || typeof intentData.parameters !== 'object') {
                 throw new Error("Invalid JSON structure");
            }
        } catch (e) {
            console.error("[/api/chat] Failed to parse intent JSON:", intentResponseText, e);
            intentData = { intent: 'general_query', parameters: {} };
        }

        console.log("[/api/chat] Detected Intent:", intentData.intent);
        console.log("[/api/chat] Extracted Parameters:", intentData.parameters);


        let contextData = null;
        let finalPrompt = "";
        let calculatedCost = null;
        let calculatedSum = null; // For search_and_sum_usage

        // Step 2: Fetch Data or Calculate Cost/Sum
        // *** MODIFIED: Handle new/refined intents ***
        if (intentData.intent === 'calculate_cost') {
            const params = intentData.parameters;
            const usageType = params.usageType;
            const explicitAmount = params.explicitAmount;

            if (!usageType) {
                contextData = { error: "Please specify whether you want the cost for electricity or water." };
            } else if (explicitAmount !== undefined && explicitAmount !== null) {
                // Calculate cost for explicit amount
                if (usageType === 'electricity') {
                    calculatedCost = explicitAmount * ELECTRICITY_RATE_PER_KWH;
                    contextData = { usage_amount: explicitAmount, usage_type: 'electricity', calculated_cost: calculatedCost.toFixed(2), rate: ELECTRICITY_RATE_PER_KWH, unit: 'kWh' };
                } else if (usageType === 'water') {
                    calculatedCost = (explicitAmount / 1000) * WATER_RATE_PER_KL;
                    contextData = { usage_amount: explicitAmount, usage_type: 'water', calculated_cost: calculatedCost.toFixed(2), rate: WATER_RATE_PER_KL, unit: 'Litres', rate_unit: 'kL' };
                }
            } else {
                // Calculate cost for total usage over a period - Fetch total usage data
                try {
                    const apiResponse = await fetch(PHP_DATA_API_URL, {
                        method: 'POST', headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ userId: userId, intent: 'get_total_usage', parameters: { usageType: usageType, startDate: params.startDate || todayDate, endDate: params.endDate || todayDate } })
                    });
                    if (!apiResponse.ok) { contextData = { error: `Failed to fetch usage data (Status: ${apiResponse.status}).` }; }
                    else {
                        const usageData = await apiResponse.json();
                        if (usageData && usageData.total_usage !== undefined) {
                             if (usageType === 'electricity') {
                                calculatedCost = usageData.total_usage * ELECTRICITY_RATE_PER_KWH;
                                contextData = { total_usage: usageData.total_usage.toFixed(2), usage_type: 'electricity', period_start: usageData.period_start, period_end: usageData.period_end, calculated_cost: calculatedCost.toFixed(2), rate: ELECTRICITY_RATE_PER_KWH, unit: 'kWh' };
                            } else if (usageType === 'water') {
                                calculatedCost = (usageData.total_usage / 1000) * WATER_RATE_PER_KL;
                                contextData = { total_usage: usageData.total_usage.toFixed(0), usage_type: 'water', period_start: usageData.period_start, period_end: usageData.period_end, calculated_cost: calculatedCost.toFixed(2), rate: WATER_RATE_PER_KL, unit: 'Litres', rate_unit: 'kL' };
                            }
                        } else { contextData = usageData; } // Pass 'no data' message
                    }
                } catch (error) { contextData = { error: "Could not connect to data service for cost calc." }; }
            }
            intentData.intent = 'display_calculated_cost'; // Set intent for final prompt

        } else if (intentData.intent === 'search_and_sum_usage') {
            // Fetch notes matching keywords, then sum the usage amounts
             const params = intentData.parameters;
             const keywords = params.keywords;
             const usageType = params.usageType; // Might be null

             if (!keywords) {
                 contextData = { error: "Please specify keywords (like 'AC' or 'garden') to search for and sum usage." };
                 intentData.intent = 'error_display'; // Use a generic error display intent
             } else {
                try {
                    console.log(`[/api/chat] Calling PHP Data API for intent: search_notes (for sum calc)`);
                    const apiResponse = await fetch(PHP_DATA_API_URL, {
                        method: 'POST', headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ userId: userId, intent: 'search_notes', parameters: { keywords: keywords, usageType: usageType, startDate: params.startDate || veryStartDate, endDate: params.endDate || todayDate } }) // Search broadly if no date
                    });
                    if (!apiResponse.ok) { contextData = { error: `Failed to fetch notes (Status: ${apiResponse.status}).` }; }
                    else {
                        const notesData = await apiResponse.json();
                        if (notesData && Array.isArray(notesData) && notesData.length > 0) {
                            // Sum the usage_amount from the results
                            calculatedSum = notesData.reduce((sum, record) => sum + (record.usage_amount || 0), 0);
                            contextData = { // Provide context for the final prompt
                                search_keywords: keywords,
                                usage_type: usageType || 'combined', // Report type searched
                                period_start: params.startDate || 'all time',
                                period_end: params.endDate || 'now',
                                calculated_sum: calculatedSum,
                                record_count: notesData.length,
                                // Optionally include first few notes for context?
                                // sample_notes: notesData.slice(0, 2)
                            };
                        } else if (notesData && Array.isArray(notesData) && notesData.length === 0) {
                             contextData = { message: `No usage records found with notes containing '${keywords}'` + (usageType ? ` for ${usageType}` : '') + ` in the specified period.` };
                        } else {
                            contextData = notesData; // Pass potential error/message from API
                        }
                    }
                } catch (error) { contextData = { error: "Could not connect to data service for note search." }; }
                // Keep intent as 'search_and_sum_usage' for final prompt handling
             }

        } else if (intentData.intent !== 'general_query') {
            // Fetch data for other specific intents (peak, notes, total)
            try {
                console.log(`[/api/chat] Calling PHP Data API for intent: ${intentData.intent}`);
                const apiResponse = await fetch(PHP_DATA_API_URL, {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ userId: userId, intent: intentData.intent, parameters: intentData.parameters })
                });
                if (!apiResponse.ok) { contextData = { error: `Failed to fetch data (Status: ${apiResponse.status}).` }; }
                else { contextData = await apiResponse.json(); }
            } catch (error) { contextData = { error: "Could not connect to data service." }; }
        }
        // *** END MODIFICATION ***


        // Step 3: Construct Final Prompt
        // *** MODIFIED: Handle new/refined intents in final prompt ***
        if (intentData.intent === 'display_calculated_cost') {
            if (contextData?.error) {
                 finalPrompt = `The user asked about cost: "${message}". Inform the user about the following error: ${contextData.error}`;
            } else if (contextData?.calculated_cost !== undefined) {
                 const cost = contextData.calculated_cost; const type = contextData.usage_type; const rate = contextData.rate;
                 let responsePrefix = "";
                 if (contextData.total_usage !== undefined) { responsePrefix = `The total ${type} usage from ${contextData.period_start} to ${contextData.period_end} was ${contextData.total_usage} ${contextData.unit}.`; }
                 else { responsePrefix = `For ${contextData.usage_amount} ${contextData.unit} of ${type},`; }
                 const rateUnit = type === 'water' ? '/kL' : '/kWh';
                 finalPrompt = `${responsePrefix} Based on the rate of ₹${rate}${rateUnit}, the estimated cost is **₹${cost}**.`;
            } else { finalPrompt = `I could not calculate the cost for "${message}". Please try rephrasing.`; }

        } else if (intentData.intent === 'search_and_sum_usage') {
             if (contextData?.error) {
                 finalPrompt = `The user asked about usage for items matching keywords: "${message}". Inform the user about the following error: ${contextData.error}`;
             } else if (contextData?.calculated_sum !== undefined) {
                 const sum = contextData.calculated_sum;
                 const type = contextData.usage_type === 'electricity' ? 'kWh' : (contextData.usage_type === 'water' ? 'Litres' : 'units');
                 const keywords = contextData.search_keywords;
                 const count = contextData.record_count;
                 const period = (contextData.period_start === 'all time') ? 'across all recorded entries' : `from ${contextData.period_start} to ${contextData.period_end}`;

                 finalPrompt = `Based on ${count} record(s) with notes containing "${keywords}" ${period}, the total calculated usage is **${sum.toFixed(type === 'kWh' ? 2 : 0)} ${type}**.`;
             } else if (contextData?.message) {
                 // Handle "no data found" message from PHP API or this script
                 finalPrompt = contextData.message;
             }
              else {
                 finalPrompt = `I could not find or sum the usage for notes containing "${intentData.parameters.keywords}". Please try different keywords or check your records.`;
             }

        } else if (intentData.intent !== 'general_query') {
             // Construct prompt for other data-based intents (peak, notes, total)
             // Added more specific instructions for different data types
             finalPrompt = `
                You are GridSync's helpful assistant. User asked: "${message}"
                Based ONLY on the following data, answer directly and factually.
                Instructions:
                1. State the specific answer (peak usage, total usage, or list notes).
                2. For peak: "The peak [type] usage was [amount] [unit] on [date] around hour [hour]."
                3. For total: "The total [type] usage from [start] to [end] was [amount] [unit]."
                4. For notes: List each note clearly: "On [date], [amount] [unit] ([type]): [note text]". If many notes, summarize briefly and state the count.
                5. If data has a 'message' field (e.g., "No data found"), state that message.
                6. If data has an 'error' field, state that error.
                7. Do NOT suggest checking graphs/app. Do NOT hallucinate. Format nicely.
                Provided Data:
                \`\`\`json
                ${JSON.stringify(contextData, null, 2)}
                \`\`\`
            `;
        } else {
             // Construct prompt for general queries
             finalPrompt = `
                You are GridSync's helpful assistant. User asked: "${message}"
                Answer generally. Do NOT suggest checking graphs/app. State if you cannot access specific data. Keep concise.
             `;
        }
        // *** END MODIFICATION ***


        // Step 4: Call Gemini with Final Prompt
        const finalReply = await callGemini(finalPrompt);

        // Step 5: Send Response to Frontend
        res.json({ reply: finalReply });

    } catch (error) {
        console.error("[/api/chat] Unexpected error in route handler:", error);
        res.status(500).json({ error: 'An internal server error occurred.' });
    }
});

// --- Server Start ---
const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
    console.log(`AI backend server running on http://localhost:${PORT}`);
    console.log(`Expecting PHP data API at: ${PHP_DATA_API_URL}`);
    if (!process.env.GEMINI_API_KEY) {
         console.warn("Warning: GEMINI_API_KEY environment variable is not set.");
    }
});
