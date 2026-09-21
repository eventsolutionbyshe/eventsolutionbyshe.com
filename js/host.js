/* =========================================================
   HOST PAGE JAVASCRIPT
   Event Solutions by S.H.E.
========================================================= */

document.addEventListener("DOMContentLoaded", function () {

    console.log("hosts.js loaded");

    /*
    |--------------------------------------------------------------------------
    | Package Booking Buttons
    |--------------------------------------------------------------------------
    */

    const bookingButtons =
        document.querySelectorAll(".package-book-button");


    bookingButtons.forEach(function (button) {

        button.addEventListener("click", function () {

            const originalHTML =
                button.innerHTML;

            /*
            |--------------------------------------------------------------------------
            | Prevent Double Click
            |--------------------------------------------------------------------------
            */

            if (button.dataset.loading === "true") {
                return;
            }

            button.dataset.loading = "true";

            button.innerHTML = `
                <span>Loading...</span>
                <span class="material-symbols-outlined">
                    progress_activity
                </span>
            `;


            /*
            |--------------------------------------------------------------------------
            | Restore Button If Navigation Is Cancelled
            |--------------------------------------------------------------------------
            */

            setTimeout(function () {

                button.dataset.loading = "false";

                button.innerHTML =
                    originalHTML;

            }, 3000);

        });

    });


    /*
    |--------------------------------------------------------------------------
    | Host Image Fallback
    |--------------------------------------------------------------------------
    */

    const hostImages =
        document.querySelectorAll(".host-main-image");


    hostImages.forEach(function (image) {

        image.addEventListener("error", function () {

            if (
                image.dataset.fallbackApplied === "true"
            ) {
                return;
            }

            image.dataset.fallbackApplied = "true";

            image.src =
                "images/host-placeholder.jpg";

        });

    });


    /*
    |--------------------------------------------------------------------------
    | Host Type Filter
    |--------------------------------------------------------------------------
    |
    | The select uses normal PHP GET filtering.
    | No live search is used.
    |--------------------------------------------------------------------------
    */

    const hostTypeFilter =
        document.getElementById("hostTypeFilter");


    if (hostTypeFilter) {

        hostTypeFilter.addEventListener(
            "change",
            function () {

                const form =
                    hostTypeFilter.closest("form");

                if (form) {
                    form.submit();
                }

            }
        );

    }


    /*
    |--------------------------------------------------------------------------
    | Smooth Card Entrance
    |--------------------------------------------------------------------------
    */

    const hostBlocks =
        document.querySelectorAll(".host-block");


    hostBlocks.forEach(function (host, index) {

        host.style.opacity = "0";

        host.style.transform =
            "translateY(12px)";


        setTimeout(function () {

            host.style.transition =
                "opacity 0.35s ease, transform 0.35s ease";

            host.style.opacity = "1";

            host.style.transform =
                "translateY(0)";

        }, 60 + (index * 60));

    });


    console.log(
        "Host page initialized. Search has been removed."
    );

});