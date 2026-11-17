(function ($) {
    "use strict";

    function hideLoadSkeleton() {
        $('#page-skeleton, #spinner').removeClass('show');
    }
    $(window).on('load', hideLoadSkeleton);
    setTimeout(hideLoadSkeleton, 150);
    
    // Sticky Navbar
    $(window).scroll(function () {
        var threshold = $('.home-hero-shell').length ? 120 : 45;
        if ($(this).scrollTop() > threshold) {
            $('.nav-bar').addClass('sticky-top shadow-sm');
        } else {
            $('.nav-bar').removeClass('sticky-top shadow-sm');
        }
    });

    if (typeof $.fn.counterUp === 'function') {
        $('[data-toggle="counter-up"]').counterUp({
            delay: 5,
            time: 2000
        });
    }


    // Modal Video
    $(document).ready(function () {
        var $videoSrc;
        $('.btn-play').click(function () {
            $videoSrc = $(this).data("src");
        });
        $('#videoModal').on('shown.bs.modal', function (e) {
            $("#video").attr('src', $videoSrc + "?autoplay=1&amp;modestbranding=1&amp;showinfo=0");
        })

        $('#videoModal').on('hide.bs.modal', function (e) {
            $("#video").attr('src', $videoSrc);
        })
    });


    if ($(".testimonial-carousel").length && typeof $.fn.owlCarousel === 'function') {
    $(".testimonial-carousel").owlCarousel({
        autoplay: true,
        smartSpeed: 2000,
        center: false,
        dots: false,
        loop: true,
        margin: 25,
        nav : true,
        navText : [
            '<i class="fas fa-arrow-left"></i>',
            '<i class="fas fa-arrow-right"></i>'
        ],
        responsiveClass: true,
        responsive: {
            0:{
                items:1
            },
            576:{
                items:1
            },
            768:{
                items:2
            },
            992:{
                items:2
            },
            1200:{
                items:2
            }
        }
    });
    }

})(jQuery);

