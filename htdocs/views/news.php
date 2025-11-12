<?php
// htdocs/views/news.php
//views and styles are handled by @katkarpranav2004
require_once __DIR__ . '/../../vendor/autoload.php';

use jcobhams\NewsApi\NewsApi;

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!defined('BASE_URL_PATH')) {
    define('BASE_URL_PATH', '/tracker');
}

$username = $_SESSION['username'] ?? 'User';
$isLoggedIn = isset($_SESSION["logged_in"]) && $_SESSION["logged_in"] === true;
$currentPage = 'news';

// --- NewsAPI Integration ---
$apiKey = 'aea9a3d0212c48debe441af511f40527'; 

// --- UPDATED Category Initialization & Order ---
$categorizedNews = [
    'electricity' => [],
    'water' => [],
    'usage' => [],
    'tech' => [],
    'policy' => [],      // Added back
    'impact' => [],
    'environment' => [],
    'general' => []
];
// --- END UPDATED Categories ---

$allArticles = [];
$apiError = null;

if (empty($apiKey) || $apiKey === 'YOUR_NEWS_API_KEY') {
    $apiError = "NewsAPI key is missing or invalid. Please check the \$apiKey variable in news.php.";
} else {
    try {
        $newsapi = new NewsApi($apiKey);

        // --- Refined Query & Parameters ---
        // More focused keywords, using phrases where appropriate
        $query = '"electricity prices" OR "water conservation" OR "energy saving" OR "power grid" OR "renewable energy" OR "climate policy" OR "environmental impact" OR utilities OR hydro OR "water usage" OR "energy efficiency"';

        // Specify relevant domains (reduces noise significantly) - ADD MORE INDIAN SOURCES
        $domains = 'timesofindia.indiatimes.com,thehindu.com,economictimes.indiatimes.com,indianexpress.com,livemint.com,hindustantimes.com,moneycontrol.com,business-standard.com'; // Example list

        $fromDate = date('Y-m-d', strtotime('-14 days')); // Look back 2 weeks

        $apiResponse = $newsapi->getEverything(
            $q = $query,
            $sources = null,
            $domains = $domains, // Filter by specific domains
            $excludeDomains = null,
            $from = $fromDate,
            $to = null,
            $language = 'en',
            $sortBy = 'relevancy', // Sort by relevance instead of date
            $pageSize = 60,
            $page = 1
        );
         // --- End Refined Query ---

        if (isset($apiResponse->status) && $apiResponse->status === 'ok' && isset($apiResponse->articles)) {
            $allArticles = $apiResponse->articles;

            // --- UPDATED Categorization Logic & Order ---
            foreach ($allArticles as $article) {
                $titleLower = strtolower($article->title ?? '');
                $descLower = strtolower($article->description ?? '');
                $contentLower = $titleLower . ' ' . $descLower;
                $assigned = false;

                // Check most specific first
                if (preg_match('/\b(electric|electricity|power|grid|solar|wind|outage|blackout|energy)\b/', $contentLower)) {
                    $categorizedNews['electricity'][] = $article;
                    $assigned = true;
                } elseif (preg_match('/\b(water|hydro|conservation|dam|river|irrigation|drought|flood|reservoir)\b/', $contentLower)) {
                    $categorizedNews['water'][] = $article;
                    $assigned = true;
                } elseif (preg_match('/\b(usage|statistic|consumption|demand|supply|meter|price|cost|tariff|bill|report|data)\b/', $contentLower)) {
                    $categorizedNews['usage'][] = $article;
                    $assigned = true;
                } elseif (preg_match('/\b(technology|innovation|smart|efficiency|battery|storage|research|develop|achieve|advancement)\b/', $contentLower)) {
                    $categorizedNews['tech'][] = $article;
                    $assigned = true;
                } elseif (preg_match('/\b(law|policy|government|regulation|rebate|rule|act|scheme|plan|initiative)\b/', $contentLower)) {
                    $categorizedNews['policy'][] = $article;
                    $assigned = true;
                } elseif (preg_match('/\b(impact|social|economic|community|effect|consequence|people|society)\b/', $contentLower)) {
                    $categorizedNews['impact'][] = $article;
                    $assigned = true;
                } elseif (preg_match('/\b(environment|climate|pollution|sustainable|sustainability|eco|green|emissions)\b/', $contentLower)) {
                    $categorizedNews['environment'][] = $article;
                    $assigned = true;
                }

                // Fallback to General for anything remaining from the API call
                if (!$assigned) {
                     $categorizedNews['general'][] = $article;
                }
            }
             // --- End UPDATED Categorization ---

        } elseif (isset($apiResponse->status) && $apiResponse->status === 'error') {
            $apiError = "NewsAPI Error: " . ($apiResponse->message ?? 'Unknown error');
        } else {
             $apiError = "Could not fetch news articles or no articles found for the query.";
             if (isset($apiResponse->articles) && count($apiResponse->articles) === 0) {
                 $apiError .= " (The API call succeeded but returned 0 articles matching the criteria.)";
             }
        }

    } catch (\Exception $e) {
        $apiError = "Error fetching news: " . $e->getMessage();
    }
}
// --- End NewsAPI Integration ---

// Helper function to display a news section
function display_news_section($title, $articles, $sectionId) {
    if (empty($articles)) {
        return;
    }
    ?>
    <section id="<?php echo $sectionId; ?>" class="mb-12 scroll-animate scroll-animate-init">
        <h2 class="text-2xl font-bold mb-6 text-light-text-primary dark:text-white border-b-2 border-light-accent dark:border-gold-accent pb-2">
            <?php echo htmlspecialchars($title); ?>
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 md:gap-8">
            <?php foreach ($articles as $index => $article): ?>
                <?php
                    $headline = $article->title ?? 'No Title Available';
                    $excerpt = $article->description ?? 'No description available.';
                    $link = $article->url ?? '#';
                    $imageUrl = $article->urlToImage;
                    $source = $article->source->name ?? 'Unknown Source';
                    $publishedDate = 'Unknown Date';
                    if (isset($article->publishedAt)) {
                        try {
                            $date = new DateTime($article->publishedAt);
                            $publishedDate = $date->format('F j, Y');
                        } catch (Exception $e) { $publishedDate = 'Invalid Date'; }
                    }
                    $imageFallback = 'https://placehold.co/600x400/cccccc/ffffff?text=News';
                    $imageSrc = $imageUrl ?: $imageFallback;
                ?>
                <div class="news-card content-box-alert scroll-animate scroll-animate-init scroll-animate-stagger bg-light-card dark:bg-dark-card rounded-lg shadow-md border border-light-border dark:border-dark-border overflow-hidden flex flex-col"
                     style="transition-delay: <?php echo $index * 0.08; ?>s;">
                    <a href="<?php echo htmlspecialchars($link); ?>" target="_blank" rel="noopener noreferrer" class="block hover:opacity-90 transition-opacity">
                        <img src="<?php echo htmlspecialchars($imageSrc); ?>" alt="<?php echo htmlspecialchars($headline); ?>" class="w-full h-48 object-cover"
                             onerror="this.src='<?php echo $imageFallback; ?>'; this.onerror=null;">
                    </a>
                    <div class="p-4 md:p-5 flex flex-col flex-grow">
                        <h2 class="text-lg font-semibold mb-2 text-light-text-primary dark:text-white">
                            <a href="<?php echo htmlspecialchars($link); ?>" target="_blank" rel="noopener noreferrer" class="hover:underline">
                                <?php echo htmlspecialchars($headline); ?>
                            </a>
                        </h2>
                        <p class="text-sm text-light-text-secondary dark:text-dark-text-secondary mb-4 flex-grow">
                            <?php echo htmlspecialchars($excerpt); ?>
                        </p>
                        <div class="mt-auto pt-3 border-t border-light-border dark:border-dark-border/50 text-xs text-light-text-secondary dark:text-dark-text-secondary flex justify-between items-center">
                            <span><?php echo htmlspecialchars($publishedDate); ?> - <?php echo htmlspecialchars($source); ?></span>
                            <a href="<?php echo htmlspecialchars($link); ?>" target="_blank" rel="noopener noreferrer" class="font-medium text-light-accent dark:text-gold-accent hover:underline">
                                Read More &rarr;
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php
}
?>
<!DOCTYPE html>
<html lang="en" class="<?php echo $_COOKIE['theme'] ?? 'light'; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>News & Updates - GridSync</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                     colors: {
                        'light-bg': '#F3F4F6',
                        'light-card': 'rgba(255, 255, 255, 0.7)',
                        'light-profile': '#FFFFFF',
                        'light-text-primary': '#1F2937',
                        'light-text-secondary': '#4B5567',
                        'light-border': '#D1D5DB',
                        'light-accent': '#2563EB',
                        'light-accent-hover': '#1D4ED8',
                        'gold-accent': '#ecc931',
                        'dark-card': 'rgba(31, 41, 55, 0.7)',
                        'dark-bg': '#111827',
                        'dark-text-primary': '#F9FAFB',
                        'dark-text-secondary': '#9CA3AF',
                        'dark-border': '#4B5563',
                        'dark-profile': 'rgba(31, 41, 55)',
                        'dark-input-bg': '#374151',
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="<?php echo BASE_URL_PATH; ?>/output.css">
    <link rel="stylesheet" href="<?php echo BASE_URL_PATH; ?>/Styles/homepage_styling.css">
    <link rel="stylesheet" href="<?php echo BASE_URL_PATH; ?>/Styles/partials_styling.css">
    <style>
        .news-card img {
            aspect-ratio: 16 / 9;
            object-fit: cover;
        }
        .content-box-alert:hover {
             /* Prevent scaling if defined in homepage_styling.css */
             /* transform: scale(1.0); */
        }
    </style>
</head>

<body class="bg-light-bg text-light-text-primary dark:bg-dark-bg dark:text-dark-text-primary min-h-screen flex flex-col font-sans transition-colors duration-300">

    <?php include(__DIR__ . '/partials/header.php'); ?>

    <main class="p-6 md:p-8 flex-grow max-w-7xl mx-auto w-full">
        <h1 class="text-3xl font-bold mb-4 text-center text-light-text-primary dark:text-white">News & Updates</h1>
        <p class="text-center text-md text-light-text-secondary dark:text-dark-text-secondary max-w-2xl mx-auto mb-10 md:mb-12">
            Latest news on energy, water, conservation, policy, and technology relevant to India.
        </p>

        <?php if ($apiError): ?>
            <div class="p-4 mb-6 text-center text-red-700 bg-red-100 border border-red-400 rounded-md dark:bg-red-900/30 dark:text-red-300 dark:border-red-600">
                <?php echo htmlspecialchars($apiError); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($allArticles) && !$apiError): ?>
             <p class="text-center text-light-text-secondary dark:text-dark-text-secondary">No relevant news articles found at this time.</p>
        <?php else: ?>
            <?php
            // --- UPDATED Display Order ---
            display_news_section('Electricity & Power', $categorizedNews['electricity'], 'news-electricity');
            display_news_section('Water & Conservation', $categorizedNews['water'], 'news-water');
            display_news_section('Usage & Statistics', $categorizedNews['usage'], 'news-usage');
            display_news_section('Technology & Achievements', $categorizedNews['tech'], 'news-tech');
            display_news_section('Policy & Law', $categorizedNews['policy'], 'news-policy');
            display_news_section('Socio-Economic Impact', $categorizedNews['impact'], 'news-impact');
            display_news_section('Environment', $categorizedNews['environment'], 'news-environment');
            display_news_section('General News', $categorizedNews['general'], 'news-general');
            // --- END UPDATED Display Order ---
            ?>
        <?php endif; ?>

    </main>

    <?php include(__DIR__ . '/partials/footer.php'); ?>

    <script src="<?php echo BASE_URL_PATH; ?>/JavaScripts/dynamic.js" defer></script>
    <script src="<?php echo BASE_URL_PATH; ?>/JavaScripts/partials_script.js" defer></script>

</body>
</html>
