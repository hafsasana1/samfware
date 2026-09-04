<?php
$request_obj = service('request');

$profileImage = base_url().'resource/avatar.png';
$webLogo = base_url().'resource/logo.png';
$favicon = base_url().'resource/favicon.ico';

// ✅ FIX: Load web settings directly if not passed from controller
if (!isset($web)) {
    $web = @json_decode(@file_get_contents(RESOURCE_PATH . 'web-setting.info'));
}

// Load from database settings
if (isset($web) && !empty($web->webLogo) && file_exists(RESOURCE_PATH . $web->webLogo)) {
    $webLogo = base_url().'resource/'.$web->webLogo;
}
if (isset($web) && !empty($web->favicon) && file_exists(RESOURCE_PATH . $web->favicon)) {
    $favicon = base_url().'resource/'.$web->favicon;
}
$webLogo = $webLogo.'?v='.time();
$favicon = $favicon.'?v='.time();

$webTitle       = $web->webTitle       ?? '';
if (!empty($web_title))        { $webTitle = $web_title; }

$metaTitle      = $web->metaTitle      ?? '';
$metaDesription = $web->metaDesription ?? '';
// ⚠️ REMOVED: Meta keywords (deprecated since 2009)
// $metaTags = $web->metaTags ?? '';

// ⚠️ REMOVED: Meta keywords override (deprecated since 2009)
// if (!empty($meta_tags)) { $metaTags = $meta_tags; }

if (!empty($meta_title))       { $metaTitle       = $meta_title; $webTitle = $meta_title; }
if (!empty($meta_description)) { $metaDesription  = $meta_description; }

// ✅ FIX: $request_obj->uri protected hai — getUri() + getTotalSegments() use karo
$_uri      = $request_obj->getUri();
$_seg1     = ($_uri->getTotalSegments() >= 1) ? $_uri->getSegment(1) : '';

$gSitekey = '6LfFIOggAAAAAG2Rse1QdKSAWH8ibnW2kPEe9x0x';
?>
<!DOCTYPE html>
<html lang="en-us" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <title><?= $webTitle ?></title>
        <meta name="description" content="<?= $metaDesription ?>">
        <!-- ============================================ -->
        <!-- OPENGRAPH META TAGS (Facebook, LinkedIn)     -->
        <!-- ============================================ -->
        <meta property="og:type" content="website" />
        <meta property="og:title" content="<?= $metaTitle ?>" />
        <meta property="og:site_name" content="<?= $web->webTitle ?? '' ?>" />
        <meta property="og:description" content="<?= $metaDesription ?>" />
        <meta property="og:url" content="<?= !empty($canonicalTags) ? $canonicalTags : current_url() ?>" />
        <meta property="og:locale" content="en_US" />
        
        <?php 
        // ✅ OPENGRAPH OPTIMIZATION: Smart image selection with fallbacks
        $ogImage = base_url('resource/og-image-default.jpg');
        $ogImageType = 'image/jpeg';
        
        // Try to load from database settings first
        try {
            $siteSettings = @json_decode(@file_get_contents(RESOURCE_PATH . 'web-setting.info'));
            if ($siteSettings && !empty($siteSettings->og_image) && file_exists(RESOURCE_PATH . $siteSettings->og_image)) {
                $ogImage = base_url('resource/'.$siteSettings->og_image);
                // Detect image type
                $ext = strtolower(pathinfo($siteSettings->og_image, PATHINFO_EXTENSION));
                $ogImageType = $ext === 'png' ? 'image/png' : ($ext === 'webp' ? 'image/webp' : 'image/jpeg');
            }
        } catch (\Exception $e) {
            // Fall back to default
        }
        
        // Override with post-specific image if available
        if (!empty($post->image ?? '')) {
            $ogImage = base_url('uploads/' . $post->image);
            $ext = strtolower(pathinfo($post->image, PATHINFO_EXTENSION));
            $ogImageType = $ext === 'png' ? 'image/png' : ($ext === 'webp' ? 'image/webp' : 'image/jpeg');
        }
        ?>
        
        <!-- Primary OG Image -->
        <meta property="og:image" content="<?= $ogImage ?>" />
        <meta property="og:image:secure_url" content="<?= $ogImage ?>" />
        <meta property="og:image:width" content="1200" />
        <meta property="og:image:height" content="630" />
        <meta property="og:image:alt" content="<?= htmlspecialchars($metaTitle) ?>" />
        <meta property="og:image:type" content="<?= $ogImageType ?>" />
        
        <!-- Additional metadata for better social sharing -->
        <meta property="og:updated_time" content="<?= date('c') ?>" />
        
        <!-- ============================================ -->
        <!-- TWITTER CARD META TAGS                       -->
        <!-- ============================================ -->
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:site" content="@samfware" />
        <meta name="twitter:creator" content="@samfware" />
        <meta name="twitter:title" content="<?= $metaTitle ?>" />
        <meta name="twitter:description" content="<?= $metaDesription ?>" />
        <meta name="twitter:image" content="<?= $ogImage ?>" />
        <meta name="twitter:image:alt" content="<?= htmlspecialchars($metaTitle) ?>" />
        <meta name="twitter:domain" content="<?= parse_url(base_url(), PHP_URL_HOST) ?>" />
        
        <!-- ============================================ -->
        <!-- ADDITIONAL SOCIAL PLATFORMS                  -->
        <!-- ============================================ -->
        <!-- Pinterest -->
        <meta name="pinterest-rich-pin" content="true" />
        
        <!-- LinkedIn -->
        <meta property="og:see_also" content="<?= base_url() ?>" />
        
        <!-- Additional Meta Tags -->
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="theme-color" content="#1FBF8F" />
        
        <?php
        // ✅ SEO PHASE 1: Prevent "Crawled - Currently Not Indexed" issues
        // Detect filtered or paginated URLs and add robots meta + canonical
        $hasFilters = $request_obj->getGet('bit') || $request_obj->getGet('os') || $request_obj->getGet('csc');
        
        // Check BOTH pagination systems: ?page= AND ?record=
        $recordParam = $request_obj->getGet('record');
        $pageParam = (int)($request_obj->getGet('page') ?? 1);
        $hasPagination = ($recordParam && $recordParam !== '0') || ($pageParam > 1);
        
        // For filtered or paginated pages: noindex to prevent duplicate content
        if ($hasFilters || $hasPagination):
        ?>
        <meta name="robots" content="noindex, follow">
        <?php endif; ?>
        
        <link href="<?= $favicon ?>" rel="icon" type="image/png">
        
        <?php 
        // Output canonical tag - either from controller or auto-generate clean URL
        if (!empty($canonicalTags)) { 
            echo '<link rel="canonical" href="'.$canonicalTags.'" />'; 
        } elseif ($hasFilters || $hasPagination) {
            // Auto-generate clean canonical URL (remove all query parameters)
            $cleanUrl = base_url($request_obj->getUri()->getPath());
            echo '<link rel="canonical" href="'.$cleanUrl.'" />'; 
        }
        ?>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
        <link href="<?= base_url() ?>assets/css/tailwind-output.css?v=1.0.0" rel="stylesheet">
        <link href="<?= base_url() ?>assets/frontsite/plugins/font-awesome/css/font-awesome.min.css" rel="stylesheet">
        <link href="<?= base_url() ?>assets/css/home.css?v=1.0.0" rel="stylesheet">
        <script defer src="<?= base_url() ?>assets/js/alpine.min.js"></script>
        <style>
            /* Custom scrollbar for search results */
            .load-ajax-data div::-webkit-scrollbar {
                width: 8px;
            }
            .load-ajax-data div::-webkit-scrollbar-track {
                background: #f1f1f1;
                border-radius: 10px;
            }
            .load-ajax-data div::-webkit-scrollbar-thumb {
                background: #888;
                border-radius: 10px;
            }
            .load-ajax-data div::-webkit-scrollbar-thumb:hover {
                background: #555;
            }
        </style>
        <?= getSiteMeta('analytics')['analytics'] ?? '' ?>
    </head>
    <body class="bg-canvas min-h-screen" x-data="{ mobileMenuOpen: false, searchOpen: false }">
        <!-- Modern Navigation -->
        <nav class="fixed w-full top-0 z-50 bg-navy-900 border-b border-navy-800 shadow-lg transition-all duration-300">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-20">
                    <!-- Logo -->
                    <div class="flex-shrink-0">
                        <a href="<?= base_url() ?>" class="flex items-center space-x-3 group">
                            <?php 
                            // Extract filename from URL (remove query string and base_url)
                            $logoFile = '';
                            if (!empty($webLogo)) {
                                $logoUrl = explode('?', $webLogo)[0]; // Remove ?v=timestamp
                                $logoFile = str_replace(base_url().'resource/', '', $logoUrl);
                            }
                            
                            // Check if logo file exists
                            $logoExists = !empty($logoFile) && file_exists(RESOURCE_PATH . $logoFile);
                            
                            if ($logoExists): 
                            ?>
                                <img src="<?= $webLogo ?>" alt="<?= $web->webTitle ?? 'SamFware' ?>" loading="lazy" class="h-10 w-auto transition-transform group-hover:scale-105">
                            <?php else: ?>
                                <span class="text-2xl font-bold text-accent transition-transform group-hover:scale-105" style="font-family: 'Inter', sans-serif;">
                                    <?= $web->webTitle ?? 'SamFware' ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    </div>

                    <!-- Desktop Navigation -->
                    <div class="hidden lg:flex items-center space-x-1">
                        <a href="<?= base_url() ?>" class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 <?= $_seg1 == '' ? 'bg-accent text-white' : 'text-white hover:bg-navy-800' ?>">
                            Home
                        </a>
                        <?php /* Blog/News - Commented out for later use
                        <a href="<?= base_url() ?>blog" class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 <?= $_seg1 == 'blog' ? 'bg-accent text-white' : 'text-white hover:bg-navy-800' ?>">
                            Blog/News
                        </a>
                        */ ?>
                        <?php
                        foreach (getActivePages('header') as $pagee) {
                            if ($pagee->isButton == 'Yes') {
                                echo '<a href="'.base_url($pagee->slugUrl).'" class="ml-2 px-5 py-2.5 rounded-lg text-sm font-semibold bg-accent text-white hover:bg-accent-hover transition-all duration-200">'.$pagee->navTitle.'</a>';
                            } else {
                                echo '<a href="'.base_url($pagee->slugUrl).'" class="px-4 py-2 rounded-lg text-sm font-medium text-white hover:bg-navy-800 transition-all duration-200">'.$pagee->navTitle.'</a>';
                            }
                        }
                        ?>
                    </div>

                    <!-- Mobile menu button -->
                    <div class="lg:hidden">
                        <button @click="mobileMenuOpen = !mobileMenuOpen" class="p-2 rounded-lg text-white hover:bg-navy-800 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path x-show="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                                <path x-show="mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile Menu -->
            <div x-show="mobileMenuOpen" 
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 transform scale-95"
                 x-transition:enter-end="opacity-100 transform scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 transform scale-100"
                 x-transition:leave-end="opacity-0 transform scale-95"
                 class="lg:hidden border-t border-navy-800 bg-navy-900">
                <div class="px-4 py-3 space-y-2">
                    <a href="<?= base_url() ?>" class="block px-4 py-3 rounded-lg text-sm font-medium <?= $_seg1 == '' ? 'bg-accent text-white' : 'text-white hover:bg-navy-800' ?>">Home</a>
                    <?php /* Blog/News - Commented out for later use
                    <a href="<?= base_url() ?>blog" class="block px-4 py-3 rounded-lg text-sm font-medium <?= $_seg1 == 'blog' ? 'bg-accent text-white' : 'text-white hover:bg-navy-800' ?>">Blog/News</a>
                    */ ?>
                    <?php
                    foreach (getActivePages('header') as $pagee) {
                        if ($pagee->isButton == 'Yes') {
                            echo '<a href="'.base_url($pagee->slugUrl).'" class="block px-4 py-3 rounded-lg text-sm font-semibold bg-accent text-white text-center">'.$pagee->navTitle.'</a>';
                        } else {
                            echo '<a href="'.base_url($pagee->slugUrl).'" class="block px-4 py-3 rounded-lg text-sm font-medium text-white hover:bg-navy-800">'.$pagee->navTitle.'</a>';
                        }
                    }
                    ?>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <div class="relative pt-20 bg-navy-900 pb-12">
            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16 pb-12">
                <div class="text-center max-w-4xl mx-auto">
                    <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-white mb-4 animate-fade-in">
                        <?= $web->headerSearchTitle ?? $web->webTitle ?? 'SamFware' ?>
                    </h1>
                    <p class="text-base sm:text-lg text-gray-300 mb-8 max-w-2xl mx-auto">
                        <?= $web->headerSearchDescription ?? 'Download Samsung Firmware at maximum speed, completely free. Official updates for all devices.' ?>
                    </p>
                    
                    <!-- Modern Search Box -->
                    <div class="relative max-w-2xl mx-auto search-container" x-data="{ searching: false }">
                        <div class="relative group">
                            <div class="relative flex items-center">
                                <input type="text" 
                                       class="w-full px-6 py-5 rounded-2xl border-2 border-navy-700 focus:border-accent focus:ring-4 focus:ring-accent-soft outline-none transition-all duration-200 text-lg bg-navy-800 text-white placeholder-gray-400 shadow-xl ajax-model-load" 
                                       placeholder="Search device name or model code..." 
                                       onkeyup="loadAjaxData(this.value)"
                                       @input="searching = $event.target.value.length > 0">
                                <div class="absolute right-3 flex items-center space-x-2">
                                    <svg class="w-6 h-6 text-gray-400 search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        <!-- Search Results Dropdown - Centered with margin auto -->
                        <div class="load-ajax-data w-full mt-2 z-50" style="position: absolute; top: 100%; left: 50%; transform: translateX(-50%);"></div>
                    </div>

                    <!-- Real Stats - Single Line (Compact) -->
                    <div class="mt-8 flex flex-wrap justify-center items-center gap-8 text-sm">
                        <!-- Total Firmware Files -->
                        <div class="flex items-center space-x-2 group">
                            <svg class="w-5 h-5 text-accent group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                            </svg>
                            <span class="font-semibold text-white"><?= number_format($siteStats['totalFirmware'] ?? 0) ?>+</span>
                            <span class="text-gray-300">Firmware Files</span>
                        </div>

                        <!-- Separator -->
                        <div class="hidden sm:block w-px h-6 bg-gray-600"></div>

                        <!-- Total Countries -->
                        <div class="flex items-center space-x-2 group">
                            <svg class="w-5 h-5 text-accent group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span class="font-semibold text-white"><?= number_format($siteStats['totalCountries'] ?? 0) ?>+</span>
                            <span class="text-gray-300">Countries</span>
                        </div>

                        <!-- Separator -->
                        <div class="hidden sm:block w-px h-6 bg-gray-600"></div>

                        <!-- Total Device Models -->
                        <div class="flex items-center space-x-2 group">
                            <svg class="w-5 h-5 text-accent group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                            </svg>
                            <span class="font-semibold text-white"><?= number_format($siteStats['totalModels'] ?? 0) ?>+</span>
                            <span class="text-gray-300">Device Models</span>
                        </div>

                        <!-- Separator -->
                        <div class="hidden sm:block w-px h-6 bg-gray-600"></div>

                        <!-- Daily Updates -->
                        <div class="flex items-center space-x-2 group">
                            <svg class="w-5 h-5 text-accent group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            <span class="font-semibold text-white"><?php echo ($siteStats['updatedToday'] ?? false) ? 'Daily' : 'Regular'; ?></span>
                            <span class="text-gray-300">Updates</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Header Ads Section -->
        <?php if (!empty($header_ads)) { ?>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
                <?= $header_ads ?>
            </div>
        </div>
        <?php } ?>

        <!-- Main Content -->
        <section class="py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">