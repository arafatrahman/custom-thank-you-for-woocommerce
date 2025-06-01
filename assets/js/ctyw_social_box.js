jQuery.noConflict();
jQuery(document).ready(function ($) {

    // set tabs options and init them!
    var myTabs = tabs({
        el: '#ctyw-social-box',
        tabNavigationLinks: '.ctyw-social-tabs-nav__link',
        tabContentContainers: '.ctyw-social-tab'
    });

    myTabs.init();

    // show the styled tabs only after css and the page is fully loaded.
    $('#ctyw-social-box').show();

    // activate the first tab available.
    $($('.ctyw-social-tabs-nav .ctyw-social-tabs-nav__link')[0]).addClass('is-active');
    $($('.ctyw-social-tab')[0]).addClass('is-active');

//        $('ctyw-social-tabs-nav__link').()

    // SLIDER.
    var currentIndex = 0,
            items = $('.is-active .ctyw-social-slider_container #ctyw-tab_sharing_product'),
            itemAmt = items.length;

    // init the correct slider on changing the tab.
    $('.ctyw-social-tabs-nav__link').on("click", function () {
        // start the slider.
        currentIndex = 0;
        items = $('.is-active .ctyw-social-slider_container #ctyw-tab_sharing_product');
        itemAmt = items.length;
    });

    function cycleItems() {
        var item = $('.is-active .ctyw-social-slider_container #ctyw-tab_sharing_product').eq(currentIndex);
        items.css('display', 'none');
        items.removeClass('active');
        item.css('display', 'block');
        item.addClass('active');
    }

    // handle next slider arrow.
    $('.ctyw-slider_next').click(function () {
        // clearInterval(autoSlide);
        currentIndex += 1;
        if (currentIndex > itemAmt - 1) {
            currentIndex = 0;
        }
        cycleItems();
    });

    // handle prev slider arrow.
    $('.ctyw-slider_prev').click(function () {
        currentIndex -= 1;
        if (currentIndex < 0) {
            currentIndex = itemAmt - 1;
        }
        cycleItems();
    });

    // make all first products show for every tab.
    function first_cycle() {
        $('#ctyw-social-box .ctyw-social-tab').each(function () {
            $($(this).find('#ctyw-tab_sharing_product')[0]).each(function () {
                $(this).css('display', 'block');
                $(this).addClass('active');
            });
        });
    }

    // start the first cycle.
    first_cycle();

    /* social sharer title and description checker
     * this fields cannot be empty
     * if so we take the default value
     */

    $('input.ctyw_title').change(function () {
        // sharer title field cannot be empty.
        // if so we put the default value.
        if ($(this).val().trim() == '') {
            $(this).val($(this).attr('ctyw_default_title'));
        }
    });

    $('textarea.ctyw_excerpt').change(function () {
        // sharer title field cannot be empty.
        // if so we put the default value.
        if ($(this).val().trim() == '') {
            $(this).val($(this).attr('ctyw_default_description'));
        }
    });

    // manage the twitter chars counter.
    $('.ctyw_twitter textarea.ctyw_excerpt').each(function () {
        $(this).keyup(function () {
            var counter = $(this).next();
            var url = $(this).parent().find('.ctyw_url_field').val();
            $(this).attr('maxlength', 138 - url.length);
            var chars = 139 - $(this).val().length - url.length - 1; // it seems Twitter eat 1 chars.
            counter.find('span').text(chars);
            counter.show();
            if (($(this).val().length + url.length + 1) > 138) {
                $(this).val($(this).val().substring(0, 138));
                counter.find('span').text(0);
            }
        });

        $(this).focusout(function () {
            var counter = $(this).next().hide();
        });
    });
});

/**
 * ctpw_socialize
 *
 * @description open the share link by social box share buttons.
 * @param {string} id of the selcted social.
 */
function ctyw_socialize(selector) {
    /* get all the infos */
    /* actually usde by facebook and pinterest*/

    var infos = document.getElementById(selector).children;
    var img = infos[0].value;
    var url = infos[1].value;
    var sharer = infos[2].value;
    var title = infos[3].value;
    var msg = infos[4].value;

    /* replace all the infos values needed in the sharer link */
    sharer = sharer.replace('ctyw_title', title);
    sharer = sharer.replace('ctyw_url', url);
    sharer = sharer.replace('ctyw_img', img);
    sharer = sharer.replace('ctyw_description', msg);

    /* open the sharer link in new tab */
    var win = window.open(sharer, '_blank');
    win.focus();

    /* avoid the href */
    return false;

}