<?php
$downloadButton = @json_decode($post->downloadButton, true);
$goToPostLink   = postUrl($post);

$downloadUrl = '';
if ($post->downloadButton != '' && count($downloadButton) > 0 && $downloadButton['buttonUrl'] != '') {
    $downloadUrl = $downloadButton['buttonUrl'];
} elseif ($post->externalFileLink != '' && $post->externalFileLink != null) {
    $downloadUrl = $post->externalFileLink;
}
?>
<!-- Download Page -->
<div class="lg:col-span-9 lg:order-1">
    <div class="space-y-6 move-to-area">
        <!-- Download Preparation Card -->
        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h2 class="text-lg font-bold text-ink flex items-center space-x-2 main-head">
                    <svg class="w-6 h-6 animate-spin text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    <span>Preparing your download link</span>
                </h2>
            </div>

            <div class="p-6 lg:p-8">
                <?php if ($post->separateAds == 'Yes' && $post->adsContent != '') { ?>
                <div class="mb-6 p-4 bg-gray-50 rounded-xl border border-gray-200">
                    <?= $post->adsContent ?>
                </div>
                <?php } ?>

                <?php if ($downloadUrl != '') { ?>
                <div class="text-center py-8">
                    <div class="inline-flex items-center space-x-3 px-6 py-4 bg-accent-soft text-accent rounded-xl border-l-4 border-accent shadow-sm setup-link">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span class="text-lg font-bold"><?= $web->webTitle ?? '' ?> is super fast, free and easy to use</span>
                    </div>
                </div>
                <?php } ?>
            </div>
        </div>

        <!-- Download Instructions -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6 lg:p-8">
                <div class="prose prose-lg max-w-none f-desc">
                    <?php
                    $pageContent   = $downrec->pageContent ?? '';
                    $ext_countries = extractCountry($pageContent);
                    if (count($ext_countries[1]) > 0) {
                        foreach ($ext_countries[1] as $cnt) {
                            $countryName = ucfirst($cnt);
                            $pageContent = str_replace('{'.strtolower($cnt).'}', '<img class="inline-block rounded shadow-sm" loading="lazy" alt="'.$countryName.' flag" src="'.base_url('assets/img/flags/4x3/'.($cnt).'.svg').'" width="24" height="18">', $pageContent);
                        }
                    }
                    echo $pageContent;
                    ?>
                </div>

                <?php if (!empty($post_ads)) { ?>
                <div class="mt-6 p-4 bg-gray-50 rounded-xl border border-gray-200">
                    <?= $post_ads ?>
                </div>
                <?php } ?>

                <!-- Download Button Area -->
                <div class="text-center mt-8 setup-link2"></div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
var startfrom=4;var timer=null;
setTimeout(function(){startTimer();},2000);
function startTimer(){
    timer=setInterval(function(){
        if(startfrom==0){
            clearInterval(timer);
            $('.main-head').html('<div class="inline-flex items-center space-x-2 bg-accent-soft text-accent px-4 py-2 rounded-lg border-l-4 border-accent"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg><span class="font-bold">Link is ready</span></div>');
            var downloadLinkButton='<a href="<?= $downloadUrl ?>" class="inline-flex items-center justify-center space-x-3 px-10 py-5 bg-accent text-white rounded-2xl font-bold text-xl shadow-2xl hover:bg-accent-hover hover:scale-105 transition-all duration-300 border-2 border-white/20" onclick="proceedDownload()" target="_blank"><svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 11l3 3m0 0l3-3m-3 3V8"></path></svg><span>Download Now</span></a>';
            $('.setup-link2').html(downloadLinkButton);
            $('html, body').animate({scrollTop:$(".setup-link2").offset().top - 100},700);
        } else {
            startfrom=startfrom-1;
        }
    },1200);
}
function proceedDownload(){setTimeout(function(){window.location.href='<?= $goToPostLink ?>';},1000);}
</script>
