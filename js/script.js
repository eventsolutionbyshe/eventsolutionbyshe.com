/* =====================================================
   EVENT SOLUTIONS BY S.H.E.
   MAIN JAVASCRIPT
===================================================== */

"use strict";


/* =====================================================
   DOM READY
===================================================== */

document.addEventListener("DOMContentLoaded", function () {


    /* =================================================
       HERO CARD CAROUSEL
    ================================================= */

    const hero =
        document.querySelector(".hero");

    const cards =
        document.querySelectorAll(".hero-card");

    const dots =
        document.querySelectorAll(".hero-card-dot");


    if (cards.length > 0) {

        let currentSlide = 0;

        let slideInterval = null;

        let isCardHovered = false;

        let touchStartX = 0;

        let touchEndX = 0;


        /* =============================================
           SHOW SLIDE
        ============================================= */

        function showSlide(index) {

            if (cards.length === 0) {
                return;
            }


            if (index < 0) {

                index =
                    cards.length - 1;

            }


            if (index >= cards.length) {

                index = 0;

            }


            cards.forEach(function (card) {

                card.classList.remove("active");

            });


            dots.forEach(function (dot) {

                dot.classList.remove("active");

                dot.setAttribute(
                    "aria-selected",
                    "false"
                );

            });


            currentSlide = index;


            if (cards[currentSlide]) {

                cards[currentSlide]
                    .classList
                    .add("active");

            }


            if (dots[currentSlide]) {

                dots[currentSlide]
                    .classList
                    .add("active");

                dots[currentSlide]
                    .setAttribute(
                        "aria-selected",
                        "true"
                    );

            }

        }


        /* =============================================
           NEXT SLIDE
        ============================================= */

        function nextSlide() {

            let next =
                currentSlide + 1;


            if (next >= cards.length) {

                next = 0;

            }


            showSlide(next);

        }


        /* =============================================
           PREVIOUS SLIDE
        ============================================= */

        function previousSlide() {

            let previous =
                currentSlide - 1;


            if (previous < 0) {

                previous =
                    cards.length - 1;

            }


            showSlide(previous);

        }


        /* =============================================
           START AUTO SLIDER
        ============================================= */

        function startSlider() {

            clearInterval(
                slideInterval
            );


            slideInterval =
                setInterval(
                    function () {

                        if (!isCardHovered) {

                            nextSlide();

                        }

                    },
                    5000
                );

        }


        /* =============================================
           RESET AUTO SLIDER
        ============================================= */

        function resetSlider() {

            clearInterval(
                slideInterval
            );

            startSlider();

        }


        /* =============================================
           CARD HOVER
        ============================================= */

        cards.forEach(
            function (card, index) {


                card.addEventListener(
                    "mouseenter",
                    function () {

                        isCardHovered =
                            true;

                        showSlide(index);

                    }
                );


                card.addEventListener(
                    "mouseleave",
                    function () {

                        isCardHovered =
                            false;

                        resetSlider();

                    }
                );


                /* =====================================
                   CARD CLICK
                ===================================== */

                card.addEventListener(
                    "click",
                    function () {

                        showSlide(index);

                        resetSlider();

                    }
                );


                /* =====================================
                   KEYBOARD ACCESS
                ===================================== */

                card.addEventListener(
                    "keydown",
                    function (event) {

                        if (
                            event.key ===
                                "Enter" ||
                            event.key ===
                                " "
                        ) {

                            event.preventDefault();

                            showSlide(index);

                            resetSlider();

                        }


                        if (
                            event.key ===
                            "ArrowRight"
                        ) {

                            event.preventDefault();

                            nextSlide();

                            resetSlider();

                        }


                        if (
                            event.key ===
                            "ArrowLeft"
                        ) {

                            event.preventDefault();

                            previousSlide();

                            resetSlider();

                        }

                    }
                );

            }
        );


        /* =============================================
           DOT NAVIGATION
        ============================================= */

        dots.forEach(
            function (dot, index) {

                dot.addEventListener(
                    "click",
                    function () {

                        showSlide(index);

                        resetSlider();

                    }
                );

            }
        );


        /* =============================================
           TOUCH START
        ============================================= */

        if (hero) {

            hero.addEventListener(
                "touchstart",
                function (event) {

                    if (
                        event.changedTouches &&
                        event.changedTouches.length
                    ) {

                        touchStartX =
                            event.changedTouches[0]
                                .clientX;

                    }

                },
                {
                    passive: true
                }
            );


            /* =========================================
               TOUCH END
            ========================================= */

            hero.addEventListener(
                "touchend",
                function (event) {

                    if (
                        !event.changedTouches ||
                        !event.changedTouches.length
                    ) {

                        return;

                    }


                    touchEndX =
                        event.changedTouches[0]
                            .clientX;


                    const swipeDistance =
                        touchStartX -
                        touchEndX;


                    if (
                        Math.abs(
                            swipeDistance
                        ) > 50
                    ) {

                        if (
                            swipeDistance > 0
                        ) {

                            nextSlide();

                        } else {

                            previousSlide();

                        }


                        resetSlider();

                    }

                },
                {
                    passive: true
                }
            );


            /* =========================================
               MOUSE ENTER HERO
            ========================================= */

            hero.addEventListener(
                "mouseenter",
                function () {

                    /*
                       Do not completely stop the
                       timer here.

                       The timer itself checks
                       isCardHovered so only the
                       actual card hover pauses
                       the carousel.
                    */

                }
            );


            /* =========================================
               VISIBILITY CHANGE
            ========================================= */

            document.addEventListener(
                "visibilitychange",
                function () {

                    if (
                        document.hidden
                    ) {

                        clearInterval(
                            slideInterval
                        );

                    } else {

                        resetSlider();

                    }

                }
            );

        }


        /* =============================================
           INITIAL SLIDE
        ============================================= */

        showSlide(0);


        /* =============================================
           START CAROUSEL
        ============================================= */

        startSlider();

    }


    /* =================================================
       SCROLL REVEAL ANIMATION
       PRESERVED FROM YOUR EXISTING SCRIPT
    ================================================= */

    const scrollElements =
        document.querySelectorAll(
            ".section-label, " +
            ".section-header h2, " +
            ".section-header p, " +
            ".about-content, " +
            ".about-image, " +
            ".highlight-card, " +
            ".company-content, " +
            ".company-point, " +
            ".talent-card, " +
            ".process-item, " +
            ".testimonial-card, " +
            ".cta-container"
        );


    if (
        scrollElements.length === 0
    ) {

        return;

    }


    /* =================================================
       FALLBACK
    ================================================= */

    if (
        !(
            "IntersectionObserver"
            in window
        )
    ) {

        scrollElements.forEach(
            function (element) {

                element.classList.add(
                    "scroll-visible"
                );

            }
        );

        return;

    }


    /* =================================================
       INTERSECTION OBSERVER
    ================================================= */

    const scrollObserver =
        new IntersectionObserver(
            function (
                entries,
                observer
            ) {

                entries.forEach(
                    function (entry) {

                        if (
                            entry.isIntersecting
                        ) {

                            entry.target
                                .classList
                                .add(
                                    "scroll-visible"
                                );


                            observer.unobserve(
                                entry.target
                            );

                        }

                    }
                );

            },
            {
                threshold: 0.12,

                rootMargin:
                    "0px 0px -50px 0px"
            }
        );


    /* =================================================
       OBSERVE ELEMENTS
    ================================================= */

    scrollElements.forEach(
        function (element) {

            scrollObserver.observe(
                element
            );

        }
    );

});