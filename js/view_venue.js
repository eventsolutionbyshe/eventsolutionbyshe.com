document.addEventListener("DOMContentLoaded", () => {

    const venueImage = document.querySelector(".venue-image");
    const venueInformation = document.querySelector(".venue-information");

    /* =========================================
       IMAGE LOAD EFFECT
    ========================================= */

    if (venueImage) {

        if (venueImage.complete) {
            venueImage.classList.add("loaded");
        } else {
            venueImage.addEventListener("load", () => {
                venueImage.classList.add("loaded");
            });
        }

        venueImage.addEventListener("error", () => {
            const wrapper = venueImage.parentElement;

            venueImage.style.display = "none";

            wrapper.innerHTML += `
                <div class="venue-image-placeholder">
                    <span>Image unavailable</span>
                </div>
            `;
        });
    }


    /* =========================================
       SIMPLE SCROLL REVEAL
    ========================================= */

    if (venueInformation) {

        const observer = new IntersectionObserver(
            (entries, observer) => {

                entries.forEach(entry => {

                    if (entry.isIntersecting) {

                        entry.target.classList.add("venue-visible");

                        observer.unobserve(entry.target);
                    }

                });

            },
            {
                threshold: 0.15
            }
        );

        observer.observe(venueInformation);
    }


    /* =========================================
       BACK BUTTON
    ========================================= */

    const backButton = document.querySelector(".back-button");

    if (backButton) {

        backButton.addEventListener("click", event => {

            // If browser history has a previous page,
            // use it for a smoother return.

            if (document.referrer &&
                document.referrer.includes("venues.php")) {

                event.preventDefault();
                window.history.back();

            }

        });

    }

});