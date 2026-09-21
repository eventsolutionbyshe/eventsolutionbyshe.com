/* =========================================================
SERVICES PAGE JAVASCRIPT
EVENT SOLUTIONS BY S.H.E.
========================================================= */

document.addEventListener("DOMContentLoaded", function () {


"use strict";


/* =====================================================
   SERVICE CARD SCROLL REVEAL
====================================================== */

const serviceCards =
    document.querySelectorAll(".service-card");


if ("IntersectionObserver" in window) {

    const cardObserver = new IntersectionObserver(
        function (entries, observer) {

            entries.forEach(function (entry) {

                if (entry.isIntersecting) {

                    const card = entry.target;

                    const index =
                        Array.from(serviceCards)
                        .indexOf(card);

                    setTimeout(function () {

                        card.classList.add("is-visible");

                    }, index * 100);

                    observer.unobserve(card);

                }

            });

        },
        {
            threshold: 0.12
        }
    );


    serviceCards.forEach(function (card) {

        cardObserver.observe(card);

    });

} else {

    serviceCards.forEach(function (card) {

        card.classList.add("is-visible");

    });

}


/* =====================================================
   SMOOTH HERO SCROLL
====================================================== */

const heroScroll =
    document.querySelector(".hero-scroll");


const servicesSection =
    document.querySelector(".services-section");


if (heroScroll && servicesSection) {

    heroScroll.addEventListener("click", function () {

        servicesSection.scrollIntoView({
            behavior: "smooth"
        });

    });

    heroScroll.style.cursor = "pointer";

}


/* =====================================================
   IMAGE HOVER PARALLAX
====================================================== */

const serviceImages =
    document.querySelectorAll(".service-image");


serviceImages.forEach(function (imageContainer) {

    const image =
        imageContainer.querySelector("img");


    if (!image) {
        return;
    }


    imageContainer.addEventListener(
        "mousemove",
        function (event) {

            const rect =
                imageContainer.getBoundingClientRect();


            const x =
                (event.clientX - rect.left) /
                rect.width;


            const y =
                (event.clientY - rect.top) /
                rect.height;


            const moveX =
                (x - 0.5) * 8;


            const moveY =
                (y - 0.5) * 8;


            image.style.transform =
                "scale(1.07) translate(" +
                moveX +
                "px, " +
                moveY +
                "px)";

        }
    );


    imageContainer.addEventListener(
        "mouseleave",
        function () {

            image.style.transform =
                "scale(1) translate(0, 0)";

        }
    );

});


/* =====================================================
   BENEFIT REVEAL
====================================================== */

const benefits =
    document.querySelectorAll(".service-benefit");


if ("IntersectionObserver" in window) {

    const benefitObserver =
        new IntersectionObserver(
            function (entries, observer) {

                entries.forEach(function (entry) {

                    if (entry.isIntersecting) {

                        entry.target.style.opacity = "1";

                        entry.target.style.transform =
                            "translateY(0)";

                        observer.unobserve(
                            entry.target
                        );

                    }

                });

            },
            {
                threshold: 0.15
            }
        );


    benefits.forEach(function (benefit) {

        benefit.style.opacity = "0";

        benefit.style.transform =
            "translateY(25px)";

        benefit.style.transition =
            "opacity .7s ease, transform .7s ease";


        benefitObserver.observe(benefit);

    });

}


/* =====================================================
   CTA BUTTON MICRO INTERACTION
====================================================== */

const ctaButton =
    document.querySelector(".cta-button");


if (ctaButton) {

    ctaButton.addEventListener(
        "mouseenter",
        function () {

            ctaButton.classList.add(
                "cta-hover"
            );

        }
    );


    ctaButton.addEventListener(
        "mouseleave",
        function () {

            ctaButton.classList.remove(
                "cta-hover"
            );

        }
    );

}


/* =====================================================
   REDUCE MOTION ACCESSIBILITY
====================================================== */

const reducedMotion =
    window.matchMedia(
        "(prefers-reduced-motion: reduce)"
    );


if (reducedMotion.matches) {

    document
        .querySelectorAll(".service-card")
        .forEach(function (card) {

            card.style.transition = "none";

            card.classList.add(
                "is-visible"
            );

        });


    document
        .querySelectorAll(".service-benefit")
        .forEach(function (benefit) {

            benefit.style.opacity = "1";

            benefit.style.transform =
                "none";

            benefit.style.transition =
                "none";

        });

}


});
