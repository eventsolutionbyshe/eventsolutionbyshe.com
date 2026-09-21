/* =========================================
   CREATE INVITATION JAVASCRIPT
   Event Solutions by S.H.E.
========================================= */

document.addEventListener(
    "DOMContentLoaded",
    function () {

        /* =====================================
           COPY INVITATION LINK
        ===================================== */

        const copyButton =
            document.getElementById(
                "copyInvitation"
            );

        const invitationLink =
            document.getElementById(
                "invitationLink"
            );


        if (
            copyButton &&
            invitationLink
        ) {

            copyButton.addEventListener(
                "click",
                async function () {

                    const text =
                        invitationLink.value.trim();


                    if (!text) {
                        return;
                    }


                    const originalHTML =
                        copyButton.innerHTML;


                    try {

                        if (
                            navigator.clipboard &&
                            window.isSecureContext
                        ) {

                            await navigator.clipboard.writeText(
                                text
                            );

                        } else {

                            invitationLink.focus();

                            invitationLink.select();

                            invitationLink.setSelectionRange(
                                0,
                                invitationLink.value.length
                            );

                            document.execCommand(
                                "copy"
                            );

                        }


                        copyButton.classList.add(
                            "copied"
                        );


                        copyButton.innerHTML = `

                            <span
                                class="material-symbols-outlined"
                                aria-hidden="true"
                            >
                                check
                            </span>

                            <span>
                                Copied
                            </span>

                        `;


                        setTimeout(
                            function () {

                                copyButton.classList.remove(
                                    "copied"
                                );

                                copyButton.innerHTML =
                                    originalHTML;

                            },
                            1800
                        );


                    } catch (error) {

                        invitationLink.focus();

                        invitationLink.select();

                        copyButton.innerHTML = `

                            <span
                                class="material-symbols-outlined"
                                aria-hidden="true"
                            >
                                error
                            </span>

                            <span>
                                Select & Copy
                            </span>

                        `;


                        setTimeout(
                            function () {

                                copyButton.innerHTML =
                                    originalHTML;

                            },
                            1800
                        );

                    }

                }
            );

        }


        /* =====================================
           GUEST FORM
        ===================================== */

        const guestForm =
            document.querySelector(
                ".guest-form"
            );

        const submitButton =
            guestForm
                ? guestForm.querySelector(
                    ".submit-button"
                )
                : null;


        if (
            guestForm &&
            submitButton
        ) {

            guestForm.addEventListener(
                "submit",
                function (event) {

                    if (
                        submitButton.disabled
                    ) {

                        event.preventDefault();

                        return;

                    }


                    submitButton.disabled =
                        true;

                    submitButton.classList.add(
                        "is-loading"
                    );

                    submitButton.innerHTML = `

                        <span
                            class="material-symbols-outlined loading-icon"
                            aria-hidden="true"
                        >
                            progress_activity
                        </span>

                        <span>
                            Creating Invitation...
                        </span>

                    `;

                }
            );

        }


        /* =====================================
           INPUT INTERACTION
        ===================================== */

        const inputs =
            document.querySelectorAll(
                ".form-group input"
            );


        inputs.forEach(
            function (input) {

                input.addEventListener(
                    "focus",
                    function () {

                        const group =
                            input.closest(
                                ".form-group"
                            );

                        if (group) {

                            group.classList.add(
                                "is-focused"
                            );

                        }

                    }
                );


                input.addEventListener(
                    "blur",
                    function () {

                        const group =
                            input.closest(
                                ".form-group"
                            );

                        if (group) {

                            group.classList.remove(
                                "is-focused"
                            );

                        }

                    }
                );

            }
        );


        /* =====================================
           AUTO SELECT INVITATION LINK
        ===================================== */

        if (invitationLink) {

            invitationLink.addEventListener(
                "click",
                function () {

                    invitationLink.select();

                }
            );

        }


        /* =====================================
           PREVENT DOUBLE CLICK
        ===================================== */

        const shareButtons =
            document.querySelectorAll(
                ".share-button"
            );


        shareButtons.forEach(
            function (button) {

                button.addEventListener(
                    "click",
                    function () {

                        button.classList.add(
                            "is-loading"
                        );

                    }
                );

            }
        );


        /* =====================================
           PAGE SHOW
        ===================================== */

        window.addEventListener(
            "pageshow",
            function () {

                if (submitButton) {

                    submitButton.disabled =
                        false;

                    submitButton.classList.remove(
                        "is-loading"
                    );

                }


                shareButtons.forEach(
                    function (button) {

                        button.classList.remove(
                            "is-loading"
                        );

                    }
                );

            }
        );


        /* =====================================
           ESCAPE KEY
        ===================================== */

        document.addEventListener(
            "keydown",
            function (event) {

                if (
                    event.key === "Escape" &&
                    invitationLink &&
                    document.activeElement ===
                        invitationLink
                ) {

                    invitationLink.blur();

                }

            }
        );


        /* =====================================
           REDUCED MOTION
        ===================================== */

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

    }
);