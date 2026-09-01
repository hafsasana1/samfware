<?php
$record = $record ?? null;
$webLogo = base_url().'resource/default/logo.png';
$favicon = base_url().'resource/default/favicon.ico';
if ($record && !empty($record->webLogo) && file_exists(FCPATH.'resource/'.$record->webLogo)) {
    $webLogo = base_url().'resource/'.$record->webLogo;
}
if ($record && !empty($record->favicon) && file_exists(FCPATH.'resource/'.$record->favicon)) {
    $favicon = base_url().'resource/'.$record->favicon;
}
$webLogo = $webLogo.'?v='.time();
$favicon = $favicon.'?v='.time();
?>

<!-- Page Header -->
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Website Settings</h1>
    <p class="text-sm text-gray-600 mt-1">Configure your website branding and meta information</p>
</div>

<!-- Settings Card -->
<div class="bg-white rounded-lg shadow-card overflow-hidden">
        <form method="post" class="web-form" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="save" value="yes">

            <!-- Branding Section -->
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-lg font-semibold text-gray-900">Branding</h2>
            </div>
            <div class="p-6 space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Website Title <span class="text-red-600">*</span></label>
                    <input type="text" name="webTitle" value="<?= $record->webTitle ?? '' ?>" required
                           class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-accent focus:ring-2 focus:ring-accent focus:ring-opacity-20 transition-colors"
                           placeholder="Enter website title">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Logo Upload -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Website Logo</label>
                        <div class="space-y-3">
                            <label class="flex items-center justify-center w-full px-4 py-3 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:border-accent transition-colors">
                                <div class="flex items-center gap-2 text-gray-600">
                                    <i class="fas fa-upload"></i>
                                    <span class="text-sm font-medium w-image-text">Choose Logo</span>
                                </div>
                                <input type="file" name="webLogo" class="hidden w-image-input" accept="image/png,image/jpg,image/jpeg" onchange="previewImage(this,'w-image')">
                            </label>
                            <p class="text-xs text-gray-500">PNG, JPG, JPEG formats allowed</p>
                            <div class="p-4 bg-gray-50 rounded-lg text-center">
                                <img src="<?= $webLogo ?>" alt="Logo" class="max-w-[120px] mx-auto w-image-preview">
                            </div>
                        </div>
                    </div>

                    <!-- Favicon Upload -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Favicon</label>
                        <div class="space-y-3">
                            <label class="flex items-center justify-center w-full px-4 py-3 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:border-accent transition-colors">
                                <div class="flex items-center gap-2 text-gray-600">
                                    <i class="fas fa-upload"></i>
                                    <span class="text-sm font-medium f-image-text">Choose Favicon</span>
                                </div>
                                <input type="file" name="favicon" class="hidden f-image-input" accept=".ico" onchange="previewImage(this,'f-image')">
                            </label>
                            <p class="text-xs text-gray-500">ICO format only</p>
                            <div class="p-4 bg-gray-50 rounded-lg text-center">
                                <img src="<?= $favicon ?>" alt="Favicon" class="max-w-[120px] mx-auto f-image-preview">
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Open Graph Image
                            <span class="text-xs text-gray-500">(Social Media Preview)</span>
                        </label>
                        <div class="space-y-3">
                            <label class="flex items-center justify-center w-full px-4 py-3 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:border-accent transition-colors">
                                <div class="flex items-center gap-2 text-gray-600">
                                    <i class="fas fa-image"></i>
                                    <span class="text-sm font-medium og-image-text">Choose OG Image</span>
                                </div>
                                <input type="file" name="og_image" class="hidden og-image-input" accept="image/png,image/jpg,image/jpeg" onchange="previewImage(this,'og-image')">
                            </label>
                            <p class="text-xs text-gray-500">
                                📐 Recommended: 1200x630px (JPG/PNG) | Used for Facebook, Twitter, LinkedIn preview
                            </p>
                            <?php 
                            $ogImagePath = base_url().'resource/og-image-default.jpg';
                            if ($record && !empty($record->og_image) && file_exists(FCPATH.'resource/'.$record->og_image)) {
                                $ogImagePath = base_url().'resource/'.$record->og_image;
                            }
                            ?>
                            <div class="p-4 bg-gray-50 rounded-lg text-center">
                                <img src="<?= $ogImagePath ?>?v=<?= time() ?>" alt="OG Image" class="max-w-full h-auto mx-auto og-image-preview" style="max-height: 200px;">
                                <p class="text-xs text-gray-500 mt-2">Preview: How your page appears on social media</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SEO Section -->
            <div class="px-6 py-4 border-t border-b border-gray-200 bg-gray-50">
                <h2 class="text-lg font-semibold text-gray-900">SEO Settings</h2>
            </div>
            <div class="p-6 space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Meta Title</label>
                    <input type="text" name="metaTitle" value="<?= $record->metaTitle ?? '' ?>"
                           class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-accent focus:ring-2 focus:ring-accent focus:ring-opacity-20 transition-colors">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Meta Description</label>
                    <textarea name="metaDesription" rows="3"
                              class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-accent focus:ring-2 focus:ring-accent focus:ring-opacity-20 transition-colors resize-y"><?= $record->metaDesription ?? '' ?></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Header Search Title</label>
                    <input type="text" name="headerSearchTitle" value="<?= $record->headerSearchTitle ?? '' ?>"
                           class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-accent focus:ring-2 focus:ring-accent focus:ring-opacity-20 transition-colors">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Header Search Description</label>
                    <textarea name="headerSearchDescription" rows="3"
                              class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-accent focus:ring-2 focus:ring-accent focus:ring-opacity-20 transition-colors resize-y"><?= $record->headerSearchDescription ?? '' ?></textarea>
                </div>
            </div>

            <!-- Social Links Section -->
            <div class="px-6 py-4 border-t border-b border-gray-200 bg-gray-50">
                <h2 class="text-lg font-semibold text-gray-900">Social Media Links</h2>
            </div>
            <div class="p-6 space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fab fa-twitter text-blue-400 mr-2"></i>Twitter Link
                    </label>
                    <input type="url" name="twitterLink" value="<?= $record->twitterLink ?? '' ?>"
                           class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-accent focus:ring-2 focus:ring-accent focus:ring-opacity-20 transition-colors"
                           placeholder="https://twitter.com/yourprofile">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fab fa-facebook text-blue-600 mr-2"></i>Facebook Link
                    </label>
                    <input type="url" name="facebookLink" value="<?= $record->facebookLink ?? '' ?>"
                           class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-accent focus:ring-2 focus:ring-accent focus:ring-opacity-20 transition-colors"
                           placeholder="https://facebook.com/yourpage">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fab fa-youtube text-red-600 mr-2"></i>Youtube Link
                    </label>
                    <input type="url" name="youtubeLink" value="<?= $record->youtubeLink ?? '' ?>"
                           class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-accent focus:ring-2 focus:ring-accent focus:ring-opacity-20 transition-colors"
                           placeholder="https://youtube.com/yourchannel">
                </div>
            </div>

            <!-- Form Footer -->
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                <button type="submit" 
                        class="w-full md:w-auto px-6 py-3 bg-accent hover:bg-accent-hover text-white font-semibold rounded-lg transition-colors shadow-sm hover:shadow-md flex items-center justify-center gap-2 do-save">
                    <i class="fas fa-save"></i>
                    <span>Save Settings</span>
                </button>
            </div>
        </form>
    </div>

<script type="text/javascript">
// Initialize Select2 for tags
$(document).ready(function(){
    // Destroy any existing Select2 instance
    if ($('.select-tags').data('select2')) {
        $('.select-tags').select2('destroy');
    }
    
    // Initialize with proper configuration
    $('.select-tags').select2({
        tags: true,
        tokenSeparators: [",", ";"],
        placeholder: "Type and press Enter to add tags...",
        width: '100%',
        minimumResultsForSearch: -1,
        allowClear: false,
        closeOnSelect: false
    });
});

function previewImage(input, prefix){
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            $('.'+prefix+'-preview').attr('src', e.target.result);
        }
        reader.readAsDataURL(input.files[0]);
        $('.'+prefix+'-text').text(input.files[0].name);
    }
}

$("form.web-form").submit(function(e){
    e.preventDefault();
    
    var formData = new FormData($(this)[0]);
    var btnObj = $('.web-form .do-save');
    var originalHtml = btnObj.html();
    
    btnObj.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Saving...');
    
    $.ajax({
        url: window.location.href,
        type: 'POST',
        data: formData,
        success: function(response) {
            if(response != '1'){
                toastr.error(response, '', {timeOut: 5000, positionClass: 'toast-top-center'});
                btnObj.prop('disabled', false).html(originalHtml);
            } else {
                toastr.success('Settings saved successfully!', '', {timeOut: 3000, positionClass: 'toast-top-center'});
                setTimeout(function(){ window.location.reload(); }, 1500);
            }
        },
        error: function(err){
            toastr.error('Unable to process your request', '', {timeOut: 5000, positionClass: 'toast-top-center'});
            btnObj.prop('disabled', false).html(originalHtml);
        },
        cache: false,
        contentType: false,
        processData: false
    });
});
</script>
