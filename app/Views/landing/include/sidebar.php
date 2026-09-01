<!-- Modern Sidebar -->
<div class="lg:col-span-3 lg:order-2 space-y-6">
    <!-- Search Widget -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden sticky top-24">
        <div class="px-5 py-3 border-b border-gray-100">
            <h3 class="text-ink font-semibold flex items-center space-x-2">
                <span class="w-2 h-2 bg-accent rounded-full"></span>
                <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <span>Quick Search</span>
            </h3>
        </div>
        <div class="p-5">
            <div class="relative">
                <input type="text" 
                       class="w-full px-4 py-3 pr-12 rounded-xl border-2 border-gray-200 focus:border-accent focus:ring-4 focus:ring-accent-soft outline-none transition-all search-in-field" 
                       placeholder="Search..." 
                       onkeyup="return isFieldSearch(event,this)">
                <button class="absolute right-2 top-1/2 -translate-y-1/2 p-2 bg-accent text-white rounded-lg hover:bg-accent-hover transition-all" 
                        onclick="searchInField($('.search-in-field').val())">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Recently Added Widget - Improved Version -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-3 bg-gradient-to-r from-highlight-soft to-white border-b border-highlight/20">
            <h3 class="text-ink font-semibold flex items-center space-x-2">
                <div class="relative">
                    <span class="w-2 h-2 bg-highlight rounded-full animate-pulse"></span>
                </div>
                <svg class="w-5 h-5 text-highlight" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>Recently Added</span>
                <span class="ml-auto text-xs text-highlight font-bold">LIVE</span>
            </h3>
        </div>
        <div class="p-4">
            <ul class="space-y-3">
                <?php
                $currentPostId = isset($post) && is_object($post) && isset($post->postId) ? $post->postId : '';
                foreach (recentPosts(5, $currentPostId) as $pst) {
                    $post_url  = postUrl($pst);
                    $deviceName = !empty($pst->device) ? $pst->device : $pst->model;
                    
                    // Calculate time ago
                    $timestamp = strtotime($pst->publishedAt ?? $pst->createdTime);
                    $now = time();
                    $diff = $now - $timestamp;
                    
                    if ($diff < 3600) {
                        $timeAgo = floor($diff / 60) . ' min ago';
                        $timeColor = 'text-green-600';
                    } elseif ($diff < 86400) {
                        $timeAgo = floor($diff / 3600) . ' hrs ago';
                        $timeColor = 'text-blue-600';
                    } elseif ($diff < 604800) {
                        $timeAgo = floor($diff / 86400) . ' days ago';
                        $timeColor = 'text-gray-600';
                    } else {
                        $timeAgo = date('M j', $timestamp);
                        $timeColor = 'text-gray-500';
                    }
                    
                    echo '<li class="group">';
                    echo '<a href="' . $post_url . '" class="block p-2.5 rounded-lg hover:bg-highlight-soft transition-all">';
                    echo '<div class="flex items-start justify-between gap-2 mb-1">';
                    echo '<h4 class="text-sm font-semibold text-ink group-hover:text-highlight transition-colors line-clamp-1">' . esc(formatDeviceDisplay($deviceName)) . '</h4>';
                    echo '<span class="text-xs font-medium ' . $timeColor . ' whitespace-nowrap flex-shrink-0">' . $timeAgo . '</span>';
                    echo '</div>';
                    echo '<div class="flex items-center gap-2 text-xs text-ink-muted">';
                    echo '<span class="font-mono text-accent font-semibold">' . esc($pst->model) . '</span>';
                    echo '<span>•</span>';
                    echo '<span class="font-medium">' . esc($pst->csc) . '</span>';
                    if (!empty($pst->os)) {
                        echo '<span>•</span>';
                        echo '<span class="text-highlight font-semibold">Android ' . esc($pst->os) . '</span>';
                    }
                    echo '</div>';
                    echo '</a>';
                    echo '</li>';
                }
                ?>
            </ul>
        </div>
    </div>

    <!-- Sidebar Ads -->
    <?php if (!empty($sidebar_ads)) { ?>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-5">
            <?= $sidebar_ads ?>
        </div>
    </div>
    <?php } ?>
</div>