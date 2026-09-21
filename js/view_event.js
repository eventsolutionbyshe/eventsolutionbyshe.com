/* =========================================
   VIEW EVENT JAVASCRIPT
   Event Solutions by S.H.E.
   Modern / Light / Professional
========================================= */

document.addEventListener("DOMContentLoaded", function () {

    /* =========================================
       ACTION BUTTONS
    ========================================= */

    const actionButtons =
        document.querySelectorAll(
            ".primary-action, .secondary-action"
        );


    actionButtons.forEach(function (button) {

        button.addEventListener(
            "click",
            function () {

                /*
                 * Do not interfere with
                 * external or special links.
                 */

                if (
                    button.target === "_blank" ||
                    button.hasAttribute("download") ||
                    button.getAttribute("href") === "#" ||
                    button.getAttribute("href") === ""
                ) {
                    return;
                }


                /*
                 * Prevent accidental
                 * double-clicking.
                 */

                if (
                    button.classList.contains(
                        "is-loading"
                    )
                ) {
                    return;
                }


                const originalHTML =
                    button.innerHTML;


                button.dataset.originalHTML =
                    originalHTML;


                button.classList.add(
                    "is-loading"
                );


                button.setAttribute(
                    "aria-busy",
                    "true"
                );


                button.innerHTML = `
                    <span
                        class="material-symbols-outlined"
                        aria-hidden="true"
                    >
                        progress_activity
                    </span>

                    <span>
                        Loading...
                    </span>
                `;


                /*
                 * Allow the browser to navigate normally.
                 * Restore only if navigation does not happen.
                 */

                setTimeout(function () {

                    button.classList.remove(
                        "is-loading"
                    );

                    button.removeAttribute(
                        "aria-busy"
                    );


                    if (
                        button.dataset.originalHTML
                    ) {

                        button.innerHTML =
                            button.dataset.originalHTML;

                    }

                }, 5000);

            }
        );

    });


    /* =========================================
       KEYBOARD ACCESSIBILITY
    ========================================= */

    document.addEventListener(
        "keydown",
        function (event) {

            if (event.key === "Escape") {

                const activeElement =
                    document.activeElement;

                if (
                    activeElement &&
                    activeElement.classList.contains(
                        "action-button"
                    )
                ) {

                    activeElement.blur();

                }

            }

        }
    );


    /* =========================================
       REDUCED MOTION
    ========================================= */

    const prefersReducedMotion =
        window.matchMedia(
            "(prefers-reduced-motion: reduce)"
        );


    if (
        prefersReducedMotion.matches
    ) {

        document.documentElement.classList.add(
            "reduce-motion"
        );

    }


    /* =========================================
       PAGE READY
    ========================================= */

    document.body.classList.add(
        "view-event-ready"
    );

});