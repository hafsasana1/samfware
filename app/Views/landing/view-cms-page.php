<!-- CMS Page -->
<div class="lg:col-span-9 lg:order-1">
    <div class="space-y-6 move-to-area">
        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h1 class="text-2xl font-bold text-ink"><?= $pagee->pageTitle ?></h1>
            </div>

            <div class="p-6 lg:p-8">
                <div class="prose prose-lg max-w-none page-content">
                    <?php
                    $pageContent = $pagee->pageContent; 
                    $ext_countries = extractCountry($pageContent);
                    if(count($ext_countries[1]) > 0){
                        foreach($ext_countries[1] as $cnt){
                            $countryName = ucfirst($cnt);
                            $pageContent = str_replace('{'.strtolower($cnt).'}', '<img class="inline-block rounded shadow-sm" loading="lazy" alt="'.$countryName.' flag" src="'.base_url('assets/img/flags/4x3/'.($cnt).'.svg').'" width="24" height="18">', $pageContent);
                        }
                    }
                    echo $pageContent;
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.page-content img {
    @apply max-w-full h-auto rounded-lg shadow-lg mx-auto my-6;
    max-height: 600px;
}
.page-content blockquote {
    @apply italic border-l-4 border-accent pl-4 py-2 my-4 text-ink-muted bg-canvas rounded-r;
}
.page-content ol {
    @apply list-decimal pl-8 my-4 space-y-2;
}
.page-content ul {
    @apply list-disc pl-8 my-4 space-y-2;
}
.page-content a {
    @apply text-accent hover:text-accent-hover underline;
}
.page-content h1, .page-content h2, .page-content h3 {
    @apply font-bold mt-6 mb-4;
}
.page-content p {
    @apply mb-4 leading-relaxed;
}
</style>
