<?php

namespace App\Controllers;

use App\Models\HomeModel;

class LandingPages extends BaseController
{
    protected $app;
    protected $web;
    protected $ads;
    protected $countries;
    protected $page_record;
    protected $db;
    protected HomeModel $home_model;

    public function __construct()
    {
        helper(['url', 'cookie', 'text', 'global_function', 'query_cache']);

        $this->db = \Config\Database::connect();

        date_default_timezone_set(defaultTimeZone());

        $this->app = session()->get('fw');

        $this->web = @json_decode(@file_get_contents(RESOURCE_PATH . 'web-setting.info'));

        $this->home_model = new HomeModel();

        $this->page_record = isset($_GET['record']) && $_GET['record'] ? $_GET['record'] : '0';

        $this->ads = getSiteMeta('ads');

        $this->countries = worldCountries();

        // ✅ VISITOR TRACKING: Enabled for statistics
        setVisitor(service('request')->getIPAddress());
    }

    // ----------------------------------------------------------------
    // Index
    // ----------------------------------------------------------------

    public function index(): string
    {
        return $this->indexOld();
    }

    public function indexOld(): string
    {
        // Get homepage data
        $homePage = getPageByArea('home');

        // ✅ REAL STATS: Calculate database-driven statistics with caching
        $data['siteStats'] = $this->getSiteStats();

        // ✅ NO PAGINATION ON HOMEPAGE: Show only latest 10 firmware
        // Benefits: Faster load, better SEO, reduced scraping surface, cleaner UX
        $data['record'] = $this->home_model->getRecentPosts(10, 1);
        
        $data['blog_record'] = $this->home_model->viewBlogPostsLanding(3, 0);

        // ✅ FIX: Get recently added models - Show newly PUBLISHED posts, not just newly scraped
        // Changed from createdTime to publishedAt (correct column name) so newly published posts appear immediately
        // Performance: Optimized via database indexes on model, device, publishedAt
        $data['recentModels'] = $this->db->table('fw_posts')
                                        ->select('model, device, MAX(publishedAt) as latest_time')
                                        ->where('postStatus', 'Active')
                                        ->where('publishedAt >=', date('Y-m-d H:i:s', strtotime('-7 days')))
                                        ->where('publishedAt IS NOT NULL')
                                        ->groupBy('model')
                                        ->orderBy('latest_time', 'DESC')
                                        ->limit(6)
                                        ->get()
                                        ->getResult();

        // Get OS list
        // ✅ SECURITY FIX: Only show OS from Active posts
        // Performance: Optimized via database index on postStatus, os
        $data['osList'] = $this->db->table('fw_posts')
                                   ->select('os')
                                   ->where('postStatus', 'Active')
                                   ->where('os <> ', '')
                                   ->groupBy('os')
                                   ->get()
                                   ->getResult();

        $data['header_ads']       = $this->ads['header_ads']       ?? '';
        $data['sidebar_ads']      = $this->ads['sidebar_ads']      ?? '';
        $data['footer_ads']       = $this->ads['footer_ads']       ?? '';
        $data['latest_model_ads'] = $this->ads['latest_model_ads'] ?? '';

        $data['meta_tags']        = $this->web->metaTags       ?? '';
        $data['page_title']       = $homePage->metaTitle ?? 'Samsung Firmware Download'; // ✅ Clean for H1
        // ✅ PRIORITY FIX: Use Web Portal settings first, then fall back to CMS home page
        $data['meta_title']       = $this->web->metaTitle      ?: ($homePage->metaTitle ?? '');
        $data['meta_description'] = $this->web->metaDesription ?: ($homePage->metaDesription ?? '');
        $data['web_title']        = ($data['meta_title'] ?: $homePage->metaTitle ?? '') . ' | ' . ($this->web->webTitle ?? 'SamFware');
        $data['homePage']         = $homePage;
        $data['web']              = $this->web; // ✅ Pass web object to view for logo display
        $data['request']          = 'home';

        return view(LANDING_PATH . '/include/content', $data);
    }

    // ----------------------------------------------------------------
    // Post Search (AJAX)
    // ----------------------------------------------------------------

    public function postSearch(): void
    {
        $query = trim($this->request->getGet('query'));

        // ✅ SECURITY FIX: Only search Active posts, exclude Draft/Inactive
        // Performance: Optimized via database index on postStatus, model, device
        $record = $this->db->table('fw_posts')
                           ->select('*')
                           ->where('postStatus', 'Active')
                           ->groupStart()
                               ->like('model', $query)
                               ->orLike('device', $query)
                           ->groupEnd()
                           ->groupBy('model')
                           ->limit(20)
                           ->get();

        $vhtml = '';
        foreach ($record->getResult() as $rec) {
            $ptitle  = $rec->model != '' ? $rec->model . ' / ' . $rec->device : $rec->postTitle;
            $slugUrl = 'firmware/' . $rec->model;
            $vhtml  .= '<a href="' . base_url($slugUrl) . '" class="flex items-center space-x-3 px-4 py-3 hover:bg-accent-soft transition-colors duration-200 border-b border-gray-100 last:border-0 group">
                <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-accent/10 flex items-center justify-center group-hover:bg-accent group-hover:scale-110 transition-all duration-200">
                    <svg class="w-5 h-5 text-accent group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-bold text-ink group-hover:text-accent truncate">' . $rec->model . '</p>
                    <p class="text-xs text-ink-muted truncate">' . $rec->device . '</p>
                </div>
                <svg class="w-5 h-5 text-gray-400 group-hover:text-accent group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </a>';
        }

        echo $vhtml != '' ? '<div class="divide-y divide-gray-100">' . $vhtml . '</div>' : '<div class="px-4 py-8 text-center"><svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg><p class="text-sm text-ink-muted">No results found. Try searching with a different model or device name.</p></div>';
    }

    // ----------------------------------------------------------------
    // View Post
    // ----------------------------------------------------------------

    public function viewPost(): string
    {
        $uri   = $this->request->getUri();
        $total = $uri->getTotalSegments();

        $seg1 = ($total >= 1) ? trim($uri->getSegment(1)) : '';
        $seg2 = ($total >= 2) ? trim($uri->getSegment(2)) : '';
        $seg3 = ($total >= 3) ? trim($uri->getSegment(3)) : '';
        $seg4 = ($total >= 4) ? trim($uri->getSegment(4)) : '';

        if ($seg1 == 'firmware') {
            $model   = $seg2;
            $csc     = $seg3;
            $version = $seg4;
        } else {
            $model   = $seg1;
            $csc     = $seg2;
            $version = $seg3;
        }

        // ✅ SECURITY FIX: Only show Active posts, prevent direct URL access to Draft posts
        // Performance: Optimized via database indexes on postStatus, model, version, csc, country
        $record = $this->db->table('fw_posts')
                           ->select('*')
                           ->where('postStatus', 'Active')
                           ->where('model', $model)
                           ->where('version', $version)
                           ->groupStart()
                               ->where('csc', strtoupper($csc))
                               ->orWhere('country', $csc)
                           ->groupEnd()
                           ->get();

        $postCount = $record->getNumRows();

        if ($postCount == 0) {
            // ✅ UX: Check if post exists but is scheduled/draft (better error handling)
            $scheduledPost = $this->db->table('fw_posts')
                                      ->where('model', $model)
                                      ->where('version', $version)
                                      ->groupStart()
                                          ->where('csc', strtoupper($csc))
                                          ->orWhere('country', $csc)
                                      ->groupEnd()
                                      ->get()->getRow();
            
            if ($scheduledPost && $scheduledPost->postStatus != 'Active') {
                // Post exists but not published yet - redirect to model page with message
                return redirect()->to(base_url('firmware/' . $model))
                                ->with('info', 'This firmware version will be available soon.');
            }
            
            // Post doesn't exist at all - show 404
            return $this->indexOld();
        } else {
            $post = $record->getRow();
            return $this->showPost($post, $postCount);
        }
    }

    // ----------------------------------------------------------------
    // Show Post
    // ----------------------------------------------------------------

    public function showPost($post, $postCount): string
    {
        if ($this->request->isAJAX()) {
            $postId    = trim($this->request->getPost('postId'));
            $fromName  = trim($this->request->getPost('fromName'));
            $fromEmail = trim($this->request->getPost('fromEmail'));
            $comment   = trim($this->request->getPost('comment'));
            $sanswer   = trim($this->request->getPost('sanswer'));

            if ($fromName == '')  exit('Please enter your name');
            if ($fromEmail == '') exit('Please enter your email');
            if ($comment == '')   exit('Please enter your comment');

            $dataArr = [
                'postId'      => $postId,
                'ipAddress'   => $this->request->getIPAddress(),
                'fromName'    => $fromName,
                'fromEmail'   => $fromEmail,
                'comment'     => $comment,
                'commentTime' => date('Y-m-d H:i:s'),
            ];
            $this->db->table('fw_post_comments')->insert($dataArr);
            exit('success');
        }

        $data['header_ads']  = $this->ads['header_ads']  ?? '';
        $data['sidebar_ads'] = $this->ads['sidebar_ads'] ?? '';
        $data['footer_ads']  = $this->ads['footer_ads']  ?? '';
        $data['post_ads']    = $this->ads['post_ads']    ?? '';

        // ✅ REAL STATS: Pass site statistics to all pages
        $data['siteStats'] = $this->getSiteStats();

        $data['meta_tags']        = replacePostToken($post->metaTags, $post);
        $data['meta_title']       = replacePostToken($post->metaTitle, $post);
        $data['page_title']       = replacePostToken($post->metaTitle, $post); // ✅ Clean title for H1 (no brand)
        $data['meta_description'] = replacePostToken($post->metaDesription, $post);
        
        // ✅ BRANDING: Smart truncation for web_title to fit brand within 55 chars
        $brandSuffix = ' | ' . ($this->web->webTitle ?? 'SamFware');
        $maxLength = 55 - strlen($brandSuffix); // 43 chars for content
        
        if (strlen($post->postTitle) > $maxLength) {
            // Truncate intelligently - keep model, CSC, version priority
            $truncated = substr($post->postTitle, 0, $maxLength);
            // Remove incomplete word at end
            $truncated = preg_replace('/\s+\S*$/', '', $truncated);
            $data['web_title'] = $truncated . $brandSuffix;
        } else {
            $data['web_title'] = $post->postTitle . $brandSuffix;
        }

        // ✅ SEO FIX: Provide fallback meta tags if database fields are empty
        if (empty(trim($data['meta_title']))) {
            // ✅ PROGRESSIVE FUNNEL: Add "Download" for action intent
            $displayDevice = formatDeviceDisplay($post->device);
            $baseTitle = "Download {$post->model} {$post->csc} {$post->version} Firmware";
            $data['page_title'] = $baseTitle; // ✅ Clean for H1
            $data['meta_title'] = $baseTitle . " | " . ($this->web->webTitle ?? 'SamFware'); // ✅ Branded for <title>
        } else {
            // Admin filled meta title - ensure "Download" prefix and brand suffix
            $cleanTitle = trim($data['meta_title']);
            
            // Add "Download" prefix if not present
            if (stripos($cleanTitle, 'Download') !== 0) {
                $cleanTitle = 'Download ' . $cleanTitle;
            }
            
            // Store clean title for H1 (remove brand if present)
            $data['page_title'] = preg_replace('/\s*\|\s*SamFware\s*$/i', '', $cleanTitle);
            
            // Add brand suffix if not present
            if (strpos($cleanTitle, 'SamFware') === false && strpos($cleanTitle, '|') === false) {
                $brandSuffix = ' | ' . ($this->web->webTitle ?? 'SamFware');
                
                // Smart truncation if too long (keep under 60 chars)
                $maxMetaLength = 58 - strlen($brandSuffix); // 58 to be safe
                if (strlen($cleanTitle) > $maxMetaLength) {
                    $cleanTitle = substr($cleanTitle, 0, $maxMetaLength);
                    $cleanTitle = preg_replace('/\s+\S*$/', '', $cleanTitle); // Remove incomplete word
                }
                $data['meta_title'] = $cleanTitle . $brandSuffix;
            } else {
                $data['meta_title'] = $cleanTitle;
            }
        }
        
        if (empty(trim($data['meta_description']))) {
            $worldCountries = worldCountries();
            $countryName = $worldCountries[$post->country ?? 'US']['name'] ?? '';
            $countryText = $countryName ? " ({$countryName})" : "";
            $osText = !empty($post->os) ? "Android {$post->os}, " : "";
            $sizeText = !empty($post->fileSize) ? "{$post->fileSize}. " : "";
            
            // ✅ GALAXY KEYWORD: Add "Galaxy" prefix for better SEO
            $displayDevice = formatDeviceDisplay($post->device);
            
            $data['meta_description'] = "Download {$post->model} firmware version {$post->version} for {$post->csc}{$countryText}. " .
                                       "Official Samsung {$displayDevice} stock ROM. {$osText}{$sizeText}Free & fast download with installation guide.";
        }

        // ✅ SEO FIX: Always set canonical URL to prevent duplicate content issues
        $data['canonicalTags'] = postUrl($post);

        $data['post']    = $post;
        $data['request'] = 'view-post';

        return view(LANDING_PATH . '/include/content', $data);
    }

    // ----------------------------------------------------------------
    // Contact Us
    // ----------------------------------------------------------------

    public function contactUs(): string
    {
        $homePage = getPageByArea('home');

        $data['header_ads']       = $this->ads['header_ads']  ?? '';
        $data['sidebar_ads']      = $this->ads['sidebar_ads'] ?? '';
        $data['footer_ads']       = $this->ads['footer_ads']  ?? '';
        
        // ✅ REAL STATS: Pass site statistics to all pages
        $data['siteStats'] = $this->getSiteStats();
        
        // ⚠️ REMOVED: Meta keywords (deprecated since 2009)
        $data['page_title']       = 'Contact Us'; // ✅ Clean title for H1
        $data['meta_title']       = 'Contact Us | ' . ($this->web->webTitle ?? 'SamFware');
        $data['meta_description'] = 'Contact US ' . ($homePage->metaDesription ?? '');
        $data['web_title']        = 'Contact Us | ' . ($this->web->webTitle ?? 'SamFware');
        $data['request']          = 'contact-us';

        return view(LANDING_PATH . '/include/content', $data);
    }

    // ----------------------------------------------------------------
    // View Page (CMS)
    // ----------------------------------------------------------------

    public function viewPage(): string|\CodeIgniter\HTTP\RedirectResponse
    {
        // ✅ FIX: safe segment access
        $uri     = $this->request->getUri();
        $slugUrl = ($uri->getTotalSegments() >= 1) ? trim($uri->getSegment(1)) : '';

        $post = getPostBySlug($slugUrl);
        if ($post == '0') {
            $pagee = getPageBySlug($slugUrl);
            if ($pagee == '0') {
                return redirect()->to(base_url());
            }

            $data['header_ads']       = $this->ads['header_ads']  ?? '';
            $data['sidebar_ads']      = $this->ads['sidebar_ads'] ?? '';
            $data['footer_ads']       = $this->ads['footer_ads']  ?? '';
            
            // ✅ REAL STATS: Pass site statistics to all pages
            $data['siteStats'] = $this->getSiteStats();
            
            // ⚠️ REMOVED: Meta keywords (deprecated since 2009)
            $data['page_title']       = $pagee->pageTitle ?? 'Page'; // ✅ Clean for H1
            $data['meta_title']       = $pagee->metaTitle         ?? '';
            $data['meta_description'] = $pagee->metaDesription    ?? '';
            $data['web_title']        = ($pagee->pageTitle ?? '') . ' | ' . ($this->web->webTitle ?? 'SamFware');
            $data['pagee']            = $pagee;
            $data['web']              = $this->web; // ✅ Pass web object to view for logo display
            $data['request']          = 'view-cms-page';

            return view(LANDING_PATH . '/include/content', $data);
        } else {
            return $this->showPost($post, 1);
        }
    }

    // ----------------------------------------------------------------
    // Category Posts
    // ----------------------------------------------------------------

    public function categoryPosts(): string
    {
        $homePage = getPageByArea('home');

        // ✅ FIX: safe segment access
        $uri     = $this->request->getUri();
        $catSlug = ($uri->getTotalSegments() >= 2) ? trim($uri->getSegment(2)) : '';
        $cat     = getCategory($catSlug);

        if ($cat == '0') {
            return view('error-404');
        }

        $per_page   = 50;
        $total_rows = $this->home_model->getCategoryPosts($cat->category);

        $data['total_rows']  = $total_rows;
        $data['per_page']    = $per_page;
        $data['page_record'] = $this->page_record;
        $data['base_url']    = base_url('category/' . $catSlug);
        $data['record']      = $this->home_model->getCategoryPosts($cat->category, $per_page, $this->page_record);

        $data['header_ads']       = $this->ads['header_ads']  ?? '';
        $data['sidebar_ads']      = $this->ads['sidebar_ads'] ?? '';
        $data['footer_ads']       = $this->ads['footer_ads']  ?? '';
        
        // ✅ REAL STATS: Pass site statistics to all pages
        $data['siteStats'] = $this->getSiteStats();
        
        // ⚠️ REMOVED: Meta keywords (deprecated since 2009)
        $data['page_title']       = $cat->category ?? 'Category'; // ✅ Clean for H1
        $data['meta_title']       = $homePage->metaTitle      ?? '';
        $data['meta_description'] = $homePage->metaDesription ?? '';
        $data['web_title']        = ($homePage->metaTitle ?? '') . ' | ' . ($this->web->webTitle ?? 'SamFware');
        $data['request']          = 'category-posts';

        return view(LANDING_PATH . '/include/content', $data);
    }

    // ----------------------------------------------------------------
    // Firmware Posts
    // ----------------------------------------------------------------

    public function firmwarePosts(): string
    {
        $homePage = getPageByArea('home');

        // ✅ FIX: safe segment access
        $uri   = $this->request->getUri();
        $model = ($uri->getTotalSegments() >= 2) ? trim($uri->getSegment(2)) : '';
        $csc   = ($uri->getTotalSegments() >= 3) ? trim($uri->getSegment(3)) : '';

        $per_page   = 10;
        $total_rows = $this->home_model->getModelPosts($model, $csc);
        $record     = $this->home_model->getModelPosts($model, $csc, $per_page, $this->page_record);

        $data['total_rows']  = $total_rows;
        $data['per_page']    = $per_page;
        $data['page_record'] = $this->page_record;
        $data['base_url']    = base_url('firmware/' . $model);
        $data['record']      = $record;

        $data['header_ads']  = $this->ads['header_ads']  ?? '';
        $data['sidebar_ads'] = $this->ads['sidebar_ads'] ?? '';
        $data['footer_ads']  = $this->ads['footer_ads']  ?? '';
        
        // ✅ REAL STATS: Pass site statistics to all pages
        $data['siteStats'] = $this->getSiteStats();
        
        // ⚠️ REMOVED: Meta keywords (deprecated since 2009)

        // ✅ SEO OPTIMIZATION: Enhanced meta tags with better descriptions
        $firstRec = $record[0] ?? null;
        $deviceName = $firstRec->device ?? 'Samsung Device';
        
        // ✅ GALAXY KEYWORD: Add "Galaxy" prefix for better SEO
        $displayDeviceName = formatDeviceDisplay($deviceName);
        
        $versionCount = is_array($record) ? count($record) : 0;
        
        if ($csc != '' && $csc != null) {
            // Step 2: Model + CSC page
            $countryName = '';
            if ($firstRec && isset($firstRec->country)) {
                $worldCountries = worldCountries();
                $countryName = $worldCountries[$firstRec->country]['name'] ?? '';
            }
            
            // ✅ SEO: Create separate page title (clean) and meta title (branded + optimized)
            // H1/H2 will show: "SM-F761U1 GALAXY Z FLIP7 FE XAA\nUnited States"
            $pageTitle = $model . ' ' . $displayDeviceName . ' ' . $csc . ($countryName ? "\n" . $countryName : '');
            
            // Meta title optimized: Add "Firmware" for clear intent, under 60 chars
            $mTitle = $model . ' ' . $displayDeviceName . ' ' . $csc . ' Firmware | ' . ($this->web->webTitle ?? 'SamFware');
            
            $data['meta_description'] = "Download official Samsung {$displayDeviceName} ({$model}) firmware for {$csc}" . 
                                       ($countryName ? " ({$countryName})" : "") . 
                                       ". {$versionCount}+ stock ROM versions available. Latest Android firmware updates, fast & free download, complete guides.";
        } else {
            // Step 1: Model-only page
            // ✅ SEO: Create separate page title (clean) and meta title (branded + optimized)
            // H1/H2 will show: "SM-F761U1 GALAXY Z FLIP7 FE Firmware - All versions"
            $pageTitle = $model . ' ' . $displayDeviceName . ' Firmware - All versions';
            
            // Meta title optimized: Add "Firmware" for clear intent, under 60 chars
            $mTitle = $model . ' ' . $displayDeviceName . ' Firmware | ' . ($this->web->webTitle ?? 'SamFware');
            
            $data['meta_description'] = "Download official Samsung {$displayDeviceName} ({$model}) firmware. {$versionCount}+ stock ROM versions for all regions. Latest Android updates, fast & free download, complete installation guides.";
        }
        
        // Add pagination indicator to titles
        if ($this->page_record != '0') {
            $pageNum = ((int)$this->page_record / 10) + 1;
            $pageTitle .= ' - Page ' . $pageNum;
            $mTitle .= ' - Page ' . $pageNum;
        }

        $data['page_title']       = $pageTitle; // ✅ Clean title for H1/H2 (no brand)
        $data['meta_title']       = $mTitle;     // ✅ Branded title for <title> tag
        $data['web_title']        = $mTitle;     // ✅ Use same branded title for <title> tag

        // ✅ SEO PHASE 1: Set canonical URL (clean URL without query parameters)
        // This helps consolidate all filtered/paginated variations to the main page
        $canonicalPath = 'firmware/' . $model;
        if ($csc != '' && $csc != null) {
            $canonicalPath .= '/' . $csc;
        }
        $data['canonicalTags'] = base_url($canonicalPath);

        // ✅ SECURITY FIX: Only show OS from Active posts
        $data['osList'] = $this->db->table('fw_posts')
                                   ->select('os')
                                   ->where('postStatus', 'Active')
                                   ->where('model', $model)
                                   ->where('os <> ', '')
                                   ->groupBy('os')
                                   ->get()
                                   ->getResult();

        $data['homePage'] = getPageByArea('home');
        $data['request']  = 'model-posts';

        return view(LANDING_PATH . '/include/content', $data);
    }

    // ----------------------------------------------------------------
    // ✅ SEO PHASE 2: AJAX Filter - Returns filtered firmware rows
    // ----------------------------------------------------------------

    public function ajaxFilter(): string
    {
        // Get filter parameters
        $model = $this->request->getGet('model');
        $csc = $this->request->getGet('csc');
        $bit = $this->request->getGet('bit');
        $os = $this->request->getGet('os');
        $filterCsc = $this->request->getGet('filter_csc');
        
        // Build query
        $builder = $this->db->table('fw_posts')
                            ->where('postStatus', 'Active')
                            ->where('model', $model);
        
        // Apply CSC filter from URL segment (Step 2: /firmware/MODEL/CSC)
        if (!empty($csc)) {
            $builder->groupStart()
                   ->where('csc', strtoupper($csc))
                   ->orWhere('country', strtoupper($csc))
                   ->groupEnd();
        }
        
        // Apply additional filters from user input
        if (!empty($bit)) {
            $builder->where('bit', $bit);
        }
        
        if (!empty($os)) {
            $builder->where('os', $os);
        }
        
        if (!empty($filterCsc)) {
            $builder->groupStart()
                   ->like('csc', strtoupper($filterCsc))
                   ->orLike('country', strtoupper($filterCsc))
                   ->groupEnd();
        }
        
        // Get filtered results
        $record = $builder->orderBy('modifiedTime', 'desc')
                         ->limit(100)
                         ->get()
                         ->getResult();
        
        // Generate table rows HTML
        $html = '';
        foreach ($record as $rec) {
            $post_link = postUrl($rec);
            $html .= '<tr class="hover:bg-accent-soft cursor-pointer transition-all duration-200 link-click" data-link="'.$post_link.'">';
            $html .= '<td class="px-6 py-4"><a href="'.base_url('firmware/'.$rec->model).'" class="text-accent hover:text-accent-hover font-bold text-sm">'.$rec->model.'</a></td>';
            $html .= '<td class="px-6 py-4 text-ink font-medium text-sm">'.($rec->device != '' ? $rec->device : $rec->postTitle).'</td>';
            $html .= '<td class="px-6 py-4 text-center"><a href="'.base_url('firmware/'.$rec->model.'/'.$rec->csc).'" class="inline-flex flex-col items-center space-y-1 hover:scale-110 transition-transform"><img class="rounded shadow-sm border border-gray-200" loading="lazy" alt="'.ucfirst(worldCountries()[$rec->country]['name'] ?? 'us').' flag" src="'.base_url('assets/img/flags/4x3/'.(strtolower(worldCountries()[$rec->country]['code'] ?? 'us')).'.svg').'" width="28" height="21"><span class="text-xs font-bold text-ink mt-1">'.$rec->csc.'</span></a></td>';
            $html .= '<td class="px-6 py-4 text-ink-muted font-mono text-sm">'.$rec->version.'</td>';
            $html .= '<td class="px-6 py-4"><span class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-bold bg-accent-soft text-accent border border-accent/20">'.$rec->os.'</span></td>';
            $html .= '<td class="px-6 py-4 text-ink-muted text-sm font-medium">'.($rec->fileSize != '' ? $rec->fileSize : '<span class="text-highlight font-semibold">Uploading...</span>').'</td>';
            $html .= '<td class="px-6 py-4 text-ink-muted text-sm">'.date('Y-m-d', strtotime($rec->modifiedTime)).'</td>';
            $html .= '</tr>';
        }
        
        return $html;
    }

    // ----------------------------------------------------------------
    // ✅ SEO PHASE 2: AJAX Filter for Home Page
    // ----------------------------------------------------------------

    public function ajaxFilterHome(): string
    {
        // Get filter parameters
        $bit = $this->request->getGet('bit');
        $os = $this->request->getGet('os');
        $filterCsc = $this->request->getGet('filter_csc');
        
        // Build query - get latest firmware across all models
        $builder = $this->db->table('fw_posts')
                            ->where('postStatus', 'Active');
        
        // Apply filters
        if (!empty($bit)) {
            $builder->where('bit', $bit);
        }
        
        if (!empty($os)) {
            $builder->where('os', $os);
        }
        
        if (!empty($filterCsc)) {
            $builder->groupStart()
                   ->like('csc', strtoupper($filterCsc))
                   ->orLike('country', strtoupper($filterCsc))
                   ->groupEnd();
        }
        
        // Get filtered results
        $record = $builder->orderBy('modifiedTime', 'desc')
                         ->limit(10)
                         ->get()
                         ->getResult();
        
        // Generate table rows HTML
        $html = '';
        foreach ($record as $rec) {
            $post_link = postUrl($rec);
            $html .= '<tr class="hover:bg-accent-soft cursor-pointer transition-all duration-200 link-click" data-link="'.$post_link.'">';
            $html .= '<td class="px-6 py-4"><a href="'.base_url('firmware/'.$rec->model).'" class="text-accent hover:text-accent-hover font-bold text-sm">'.$rec->model.'</a></td>';
            $html .= '<td class="px-6 py-4 text-ink font-medium text-sm">'.($rec->device != '' ? $rec->device : $rec->postTitle).'</td>';
            $html .= '<td class="px-6 py-4 text-center"><a href="'.base_url('firmware/'.$rec->model.'/'.$rec->csc).'" class="inline-flex flex-col items-center space-y-1 hover:scale-110 transition-transform"><img class="rounded shadow-sm border border-gray-200" loading="lazy" alt="'.ucfirst(worldCountries()[$rec->country]['name'] ?? 'us').' flag" src="'.base_url('assets/img/flags/4x3/'.(strtolower(worldCountries()[$rec->country]['code'] ?? 'us')).'.svg').'" width="28" height="21"><span class="text-xs font-bold text-ink mt-1">'.$rec->csc.'</span></a></td>';
            $html .= '<td class="px-6 py-4 text-ink-muted font-mono text-sm">'.$rec->version.'</td>';
            $html .= '<td class="px-6 py-4"><span class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-bold bg-accent-soft text-accent border border-accent/20">'.$rec->os.'</span></td>';
            $html .= '<td class="px-6 py-4 text-ink-muted text-sm font-medium">'.($rec->fileSize != '' ? $rec->fileSize : '<span class="text-highlight font-semibold">Uploading...</span>').'</td>';
            $html .= '<td class="px-6 py-4 text-ink-muted text-sm">'.date('Y-m-d', strtotime($rec->modifiedTime)).'</td>';
            $html .= '</tr>';
        }
        
        return $html;
    }

    // ----------------------------------------------------------------
    // Download Now
    // ----------------------------------------------------------------

    public function downloadNow(): string
    {
        $postId = trim($this->request->getPost('postId'));
        $post   = getPost($postId);

        if ($post == '0') {
            return view('error-404');
        }

        // ✅ FIX: Update download count with error handling
        try {
            $this->db->table('fw_posts')
                     ->where('postId', $postId)
                     ->set('downloadCount', 'downloadCount + 1', false)
                     ->update();
        } catch (\Exception $e) {
            // Database is read-only, log error but continue
            log_message('error', 'Cannot update downloadCount (DB read-only): ' . $e->getMessage());
        }

        $data['header_ads']       = $this->ads['header_ads']  ?? '';
        $data['sidebar_ads']      = $this->ads['sidebar_ads'] ?? '';
        $data['footer_ads']       = $this->ads['footer_ads']  ?? '';
        $data['post_ads']         = $this->ads['post_ads']    ?? '';
        
        // ✅ REAL STATS: Pass site statistics to all pages
        $data['siteStats'] = $this->getSiteStats();
        
        // ⚠️ REMOVED: Meta keywords (deprecated since 2009)
        $data['page_title']       = 'Download Samsung Firmware'; // ✅ Clean title for H1
        $data['meta_title']       = 'Download Samsung Firmware | ' . ($this->web->webTitle ?? 'SamFware');
        $data['meta_description'] = $this->web->metaDesription ?? '';
        $data['web_title']        = 'Download Samsung Firmware | ' . ($this->web->webTitle ?? 'SamFware');
        $data['web_title']        = 'Download Now';
        $data['post']             = $post;
        $data['downrec']          = getPageByArea('download');
        $data['request']          = 'download-now';

        return view(LANDING_PATH . '/include/content', $data);
    }

    // ----------------------------------------------------------------
    // Download Link
    // ----------------------------------------------------------------

    public function downloadLink(): void
    {
        // ✅ FIX: safe segment access
        $uri    = $this->request->getUri();
        $seg2   = ($uri->getTotalSegments() >= 2) ? trim($uri->getSegment(2)) : '';
        $uhash  = decrypt($seg2);
        $postId = @current(explode('::', $uhash));
        $post   = getPost($postId);

        if ($post == '0') {
            redirect()->to(base_url());
            return;
        }

        // ✅ FIX: Update download count with error handling
        try {
            $this->db->table('fw_posts')
                     ->where('postId', $postId)
                     ->set('downloadCount', 'downloadCount + 1', false)
                     ->update();
        } catch (\Exception $e) {
            // Database is read-only, log error but continue
            log_message('error', 'Cannot update downloadCount (DB read-only): ' . $e->getMessage());
        }

        $downloadButton = @json_decode($post->downloadButton, true);

        if (
            $post->downloadButton != '' &&
            count($downloadButton) > 0 &&
            $downloadButton['buttonUrl'] != ''
        ) {
            header('Location: ' . $downloadButton['buttonUrl']);
        } elseif ($post->externalFileLink != '' && $post->externalFileLink != null) {
            header('Location: ' . $post->externalFileLink);
        } else {
            echo '<h3 style="text-align:center;color:red;">Firmware Uploading.... Please visit 2 hour later</h3>';
        }
    }

    // ----------------------------------------------------------------
    // Post Action (Like/Dislike)
    // ----------------------------------------------------------------

    public function postAction(): void
    {
        $postId    = trim($this->request->getPost('postId'));
        $type      = trim($this->request->getPost('type'));
        $area      = trim($this->request->getPost('area'));
        $ipAddress = $this->request->getIPAddress();

        // ✅ FIX: table() se shuru karo
        $record = $this->db->table('fw_post_likes')
                           ->select('likeId')
                           ->where('postId', $postId)
                           ->where('ipAddress', $ipAddress)
                           ->where('area', $area)
                           ->get();

        $dataArr = [
            'postId'    => $postId,
            'type'      => $type,
            'area'      => $area,
            'ipAddress' => $ipAddress,
            'likeTime'  => date('Y-m-d H:i:s'),
        ];

        if ($record->getNumRows() == 0) {
            $this->db->table('fw_post_likes')->insert($dataArr);
        } else {
            $likeId = $record->getRow()->likeId;
            $this->db->table('fw_post_likes')->where('likeId', $likeId)->update($dataArr);
        }

        // ✅ FIX: table() se shuru karo
        $likesCount = $this->db->table('fw_post_likes')
                               ->select('likeId')
                               ->where('postId', $postId)
                               ->where('type', 'like')
                               ->where('area', $area)
                               ->where('ipAddress', $ipAddress)
                               ->get()->getNumRows();

        $dbTable = ($area == 'blog') ? 'fw_blogs' : 'fw_posts';
        $this->db->table($dbTable)->where('postId', $postId)->update(['likesCount' => $likesCount]);
    }

    // ----------------------------------------------------------------
    // View Blogs
    // ----------------------------------------------------------------

    public function viewBlogs(): string
    {
        $per_page   = 10;
        $total_rows = $this->home_model->viewBlogPostsLanding();
        $record     = $this->home_model->viewBlogPostsLanding($per_page, $this->page_record);

        $data['total_rows']  = $total_rows;
        $data['per_page']    = $per_page;
        $data['page_record'] = $this->page_record;
        $data['base_url']    = base_url('blog');
        $data['record']      = $record;

        $data['header_ads']  = $this->ads['header_ads']  ?? '';
        $data['sidebar_ads'] = $this->ads['sidebar_ads'] ?? '';
        $data['footer_ads']  = $this->ads['footer_ads']  ?? '';
        
        // ✅ REAL STATS: Pass site statistics to all pages
        $data['siteStats'] = $this->getSiteStats();
        
        // ⚠️ NOTE: Hardcoded meta keywords below for blog listing (consider removing in future)
        $data['meta_tags']   = 'samsung firmware, samsung firmware download, samfirmware, firmware samsung, firmware download, samsung update, samsung firmware update, galaxy firmware, one ui';

        $mTitle = 'Blog';
        $pageTitle = 'Blog'; // ✅ Clean title for H1
        if ($this->page_record != '0') {
            $pageNum = ((int)$this->page_record + 1);
            $mTitle .= ' - Page ' . $pageNum;
            $pageTitle .= ' - Page ' . $pageNum;
        }
        $mTitle .= ' | ' . ($this->web->webTitle ?? 'SamFware');
        
        $mDescription = 'News in SamFware.com - Samsung News';
        if ($this->page_record != '0') {
            $mDescription .= ' - Page ' . ((int)$this->page_record + 1);
        }

        $data['page_title']       = $pageTitle; // ✅ Clean title for H1
        $data['meta_title']       = $mTitle;
        $data['meta_description'] = $mDescription;
        $data['web_title']        = $mTitle;
        $data['request']          = 'blog-posts';

        return view(LANDING_PATH . '/include/content', $data);
    }

    // ----------------------------------------------------------------
    // View Single Blog
    // ----------------------------------------------------------------

    public function viewSingleBlogs(): string
    {
        // ✅ FIX: safe segment access
        $uri     = $this->request->getUri();
        $slugUrl = ($uri->getTotalSegments() >= 2) ? trim($uri->getSegment(2)) : '';
        $pagee   = getBlogBySlug($slugUrl);

        if ($pagee == '0') {
            return redirect()->to(base_url('blog'));
        }

        $data['header_ads']       = $this->ads['header_ads']  ?? '';
        $data['sidebar_ads']      = $this->ads['sidebar_ads'] ?? '';
        $data['footer_ads']       = $this->ads['footer_ads']  ?? '';
        
        // ✅ REAL STATS: Pass site statistics to all pages
        $data['siteStats'] = $this->getSiteStats();
        
        // ⚠️ REMOVED: Meta keywords (deprecated since 2009)
        $data['page_title']       = $pagee->pageTitle ?? 'Blog Post'; // ✅ Clean for H1
        $data['meta_title']       = $pagee->metaTitle         ?? '';
        $data['meta_description'] = $pagee->metaDesription    ?? '';
        $data['web_title']        = ($pagee->pageTitle ?? '') . ' | ' . ($this->web->webTitle ?? 'SamFware');
        $data['pagee']            = $pagee;
        $data['request']          = 'view-blog-page';

        return view(LANDING_PATH . '/include/content', $data);
    }

    // ----------------------------------------------------------------
    // Speed Test
    // ----------------------------------------------------------------

    public function speedtest(): string
    {
        $homePage = getPageByArea('home');

        $data['header_ads']       = $this->ads['header_ads']  ?? '';
        $data['sidebar_ads']      = $this->ads['sidebar_ads'] ?? '';
        $data['footer_ads']       = $this->ads['footer_ads']  ?? '';
        
        // ✅ REAL STATS: Pass site statistics to all pages
        $data['siteStats'] = $this->getSiteStats();
        
        // ⚠️ REMOVED: Meta keywords (deprecated since 2009)
        $data['page_title']       = 'Internet Speed Test'; // ✅ Clean title for H1
        $data['meta_title']       = 'Internet Speed Test | ' . ($this->web->webTitle ?? 'SamFware');
        $data['meta_description'] = 'Internet Speed Test ' . ($homePage->metaDesription ?? '');
        $data['web_title']        = 'Internet Speed Test | ' . ($this->web->webTitle ?? 'SamFware');
        $data['request']          = 'internet-speed-test';

        return view(LANDING_PATH . '/include/content', $data);
    }

    // ----------------------------------------------------------------
    // Get Site Statistics (Cached)
    // ----------------------------------------------------------------
    
    private function getSiteStats(): array
    {
        $cache = \Config\Services::cache();
        $cacheKey = 'site_stats_v1';
        
        // Try to get from cache (cached for 1 hour)
        $stats = $cache->get($cacheKey);
        
        if ($stats === null) {
            // Calculate fresh stats from database
            
            // Total firmware files (Active posts only)
            $totalFirmware = $this->db->table('fw_posts')
                                     ->where('postStatus', 'Active')
                                     ->countAllResults();
            
            // Total unique countries (from CSC codes)
            $totalCountries = $this->db->table('fw_posts')
                                      ->select('country')
                                      ->where('postStatus', 'Active')
                                      ->where('country IS NOT NULL')
                                      ->where('country !=', '')
                                      ->groupBy('country')
                                      ->countAllResults();
            
            // Total unique device models
            $totalModels = $this->db->table('fw_posts')
                                   ->select('model')
                                   ->where('postStatus', 'Active')
                                   ->where('model IS NOT NULL')
                                   ->where('model !=', '')
                                   ->groupBy('model')
                                   ->countAllResults();
            
            // Check if updated today (last 24 hours)
            $recentUpdates = $this->db->table('fw_posts')
                                     ->where('postStatus', 'Active')
                                     ->where('publishedAt >=', date('Y-m-d H:i:s', strtotime('-24 hours')))
                                     ->countAllResults();
            
            $stats = [
                'totalFirmware' => $totalFirmware,
                'totalCountries' => $totalCountries,
                'totalModels' => $totalModels,
                'updatedToday' => $recentUpdates > 0,
                'recentCount' => $recentUpdates
            ];
            
            // Cache for 5 minutes (300 seconds) - reduced from 3600 for more frequent updates
            // ✅ IMPROVEMENT: Faster stat updates + paired with manual cache clear on publish
            $cache->save($cacheKey, $stats, 300);
        }
        
        return $stats;
    }
}