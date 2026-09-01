/* ========================================
   TIER 4 OPTIMIZATION: Extracted JavaScript
   File: footer.php inline scripts
   Date: August 2026
   Purpose: Extract inline JS to external file for better caching and page performance
   ======================================== */

var baseurl = "";  // Set via PHP in footer before script load
var vajax = null;
var searchDebounceTimer = null;

/**
 * ✅ TIER 4.5: Debounced firmware model data search via AJAX
 * Prevents excessive AJAX calls while user is still typing
 * @param {string} data - Search query
 * @param {number} delay - Debounce delay in milliseconds (default: 300ms)
 */
function loadAjaxData(data, delay){
    delay = delay || 300;  // Default 300ms debounce
    
    // Clear previous debounce timer
    if(searchDebounceTimer){
        clearTimeout(searchDebounceTimer);
    }
    
    // Set new debounce timer
    searchDebounceTimer = setTimeout(function(){
        if(data.length > 2){
            // Abort previous AJAX request if still pending
            if(vajax != null){ 
                $('.load-ajax-data').hide(); 
                vajax.abort(); 
            }
            $('.search-icon').addClass('animate-spin');
            vajax = $.ajax({
                data: {query: data},
                url: baseurl + 'load/model',
                timeout: 5000,  // 5 second timeout
                success: function(response){
                    $('.search-icon').removeClass('animate-spin');
                    $('.load-ajax-data').show();
                    // Show max 3 results visible with scrollbar - height calculated as: 3 items × ~60px per item = ~180px
                    $('.load-ajax-data').html('<div class="bg-white rounded-xl shadow-2xl border border-gray-200" style="max-height: 240px; overflow-y: auto;">'+response+'</div>');
                },
                error: function(err){
                    $('.search-icon').removeClass('animate-spin');
                    if(err.statusText !== 'abort'){  // Don't show error if request was intentionally aborted
                        $('.load-ajax-data').hide();
                    }
                }
            });
        } else {
            $('.search-icon').removeClass('animate-spin');
            $('.load-ajax-data').hide();
            $('.load-ajax-data').html('');
        }
    }, delay);
}

/**
 * Hide dropdown when clicking outside
 */
$(document).on('click', function(e){
    if($(e.target).closest(".load-ajax-data").length === 0 && !$(e.target).hasClass('ajax-model-load')){
        $(".load-ajax-data").hide();
    }
});

/**
 * Send ping to keep session alive
 */
function setPing(){
    $.ajax({
        url: baseurl + 'home/ping',
        success: function(response){}
    });
}

/**
 * Initialize ping on document ready and every 60 seconds
 */
$(document).ready(function(){
    setPing();
    setInterval("setPing()", 60000);
});

/**
 * Handle comment form submission
 */
$('.comment-form').submit(function(e){
    var btnObj = $(this).find('button[type="submit"]');
    btnObj.prop('disabled', true).text('Please wait....');
    $.ajax({
        type: 'post',
        data: $(this).serialize(),
        url: window.location.href,
        success: function(response){
            btnObj.prop('disabled', false).text('Submit');
            if($.trim(response) != 'success'){
                alert(response);
            } else {
                $('.comment-fields').val('').text('');
                $('.success-comment').show();
                setTimeout(function(){
                    $('.success-comment').hide();
                }, 5000);
            }
        },
        error: function(err){
            btnObj.prop('disabled', false).text('Submit');
            alert('Unable to process your request');
        }
    });
    e.preventDefault();
});

/**
 * Handle contact form submission
 */
$('.contact-us-form').submit(function(e){
    e.preventDefault();
    var btnObj = $(this).find('button[type="submit"]');
    btnObj.prop('disabled', true);
    $.ajax({
        type: 'post',
        data: $(this).serialize(),
        url: baseurl + 'home/contactUs',
        success: function(response){
            btnObj.prop('disabled', false);
            if($.trim(response) != 'success'){
                alert(response);
            } else {
                alert("thank you for reaching us, we'll contact your asap.");
                setTimeout(function(){
                    window.location.reload();
                }, 1500);
            }
        },
        error: function(err){
            btnObj.prop('disabled', false);
        }
    });
});

/**
 * Handle post action (like, share, etc)
 */
function postAction(obj, area, type, postId){
    if($(obj).hasClass('active')){
        return false;
    }
    $('.btn-post-action').removeClass('active');
    $(obj).addClass('active');
    var params = 'postId=' + postId + '&type=' + type + '&area=' + area;
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.open("POST", baseurl + 'landingPages/postAction', true);
    xmlhttp.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
    xmlhttp.send(params);
}

/**
 * Handle search field Enter key
 */
function isFieldSearch(e, obj){
    if(e.which == 13){
        searchInField($(obj).val());
    }
}

/**
 * Search in site using Google search operator
 */
function searchInField(search){
    if(search != ''){
        var siteSearchDomain = window.location.hostname;
        var url = 'https://www.google.com/search?q=' + search + '&sitesearch=' + siteSearchDomain;
        window.open(url, '_blank').focus();
    }
}
