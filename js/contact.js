javascript
/* =========================================================
   CONTACT PAGE
   EVENT SOLUTIONS BY S.H.E.
========================================================= */

document.addEventListener("DOMContentLoaded", function () {

    /* =====================================================
       HELPER FUNCTIONS
    ====================================================== */

    const $ = function (selector, parent = document) {
        return parent.querySelector(selector);
    };

    const $$ = function (selector, parent = document) {
        return Array.from(parent.querySelectorAll(selector));
    };


    /* =====================================================
       CONTACT FORM
    ====================================================== */

    const contactForm = $("#contactForm");
    const fullnameInput = $("#fullname");
    const addressInput = $("#address");
    const phoneInput = $("#phone");
    const messageInput = $("#message");
    const messageCount = $("#messageCount");
    const contactSubmitButton = $("#contactSubmitButton");


    /* =====================================================
       CONTACT MESSAGE POPUP
    ====================================================== */

    const contactMessageOverlay =
        $("#contactMessageOverlay");

    const contactMessagePopup =
        contactMessageOverlay
            ? $(".contact-message-popup", contactMessageOverlay)
            : null;

    const contactMessageClose =
        $("#contactMessageClose");

    const contactMessageButton =
        $("#contactMessageButton");


    /* =====================================================
       POPUP FUNCTIONS
    ====================================================== */

    function showContactMessage() {

        if (!contactMessageOverlay) {
            return;
        }

        contactMessageOverlay.classList.add("show");

        document.body.classList.add(
            "contact-popup-open"
        );

        contactMessageOverlay.setAttribute(
            "aria-hidden",
            "false"
        );

        if (contactMessageClose) {

            setTimeout(function () {

                contactMessageClose.focus();

            }, 50);
        }
    }


    function closeContactMessage() {

        if (!contactMessageOverlay) {
            return;
        }

        contactMessageOverlay.classList.remove("show");

        document.body.classList.remove(
            "contact-popup-open"
        );

        contactMessageOverlay.setAttribute(
            "aria-hidden",
            "true"
        );
    }


    /* =====================================================
       SHOW SERVER MESSAGE
    ====================================================== */

    if (
        contactMessageOverlay &&
        contactMessageOverlay.dataset.show === "true"
    ) {

        setTimeout(function () {

            showContactMessage();

        }, 100);
    }


    /* =====================================================
       CLOSE POPUP - X BUTTON
    ====================================================== */

    if (contactMessageClose) {

        contactMessageClose.addEventListener(
            "click",
            function (event) {

                event.preventDefault();

                event.stopPropagation();

                closeContactMessage();

            }
        );
    }


    /* =====================================================
       CONTINUE / TRY AGAIN BUTTON
    ====================================================== */

    if (contactMessageButton) {

        contactMessageButton.addEventListener(
            "click",
            function (event) {

                /*
                 * If the button is an anchor
                 * such as "Login to Continue",
                 * allow normal navigation.
                 */

                if (
                    contactMessageButton.tagName.toLowerCase() === "a"
                ) {
                    return;
                }

                event.preventDefault();

                event.stopPropagation();

                closeContactMessage();

            }
        );
    }


    /* =====================================================
       PREVENT POPUP CONTENT FROM CLOSING POPUP
    ====================================================== */

    if (contactMessagePopup) {

        contactMessagePopup.addEventListener(
            "click",
            function (event) {

                event.stopPropagation();

            }
        );
    }


    /* =====================================================
       CLOSE WHEN CLICKING OVERLAY
    ====================================================== */

    if (contactMessageOverlay) {

        contactMessageOverlay.addEventListener(
            "click",
            function (event) {

                if (
                    event.target === contactMessageOverlay
                ) {

                    closeContactMessage();

                }

            }
        );
    }


    /* =====================================================
       ESCAPE KEY
    ====================================================== */

    document.addEventListener(
        "keydown",
        function (event) {

            if (
                event.key === "Escape" &&
                contactMessageOverlay &&
                contactMessageOverlay.classList.contains("show")
            ) {

                closeContactMessage();

            }

        }
    );


    /* =====================================================
       MESSAGE COUNTER
    ====================================================== */

    function updateMessageCounter() {

        if (!messageInput || !messageCount) {
            return;
        }

        const currentLength =
            messageInput.value.length;

        messageCount.textContent =
            currentLength;
    }


    if (messageInput) {

        messageInput.addEventListener(
            "input",
            updateMessageCounter
        );

        updateMessageCounter();
    }


    /* =====================================================
       PHONE INPUT
    ====================================================== */

    if (phoneInput) {

        phoneInput.addEventListener(
            "input",
            function () {

                let value =
                    phoneInput.value;

                value =
                    value.replace(
                        /[^0-9+\-()\s]/g,
                        ""
                    );

                phoneInput.value =
                    value;
            }
        );
    }


    /* =====================================================
       INPUT FOCUS
    ====================================================== */

    const formInputs = $$(
        ".form-group input, .form-group textarea"
    );

    formInputs.forEach(function (input) {

        input.addEventListener(
            "focus",
            function () {

                const wrapper =
                    input.closest(
                        ".form-input-wrapper, .form-textarea-wrapper"
                    );

                if (wrapper) {

                    wrapper.classList.add(
                        "is-focused"
                    );
                }

            }
        );


        input.addEventListener(
            "blur",
            function () {

                const wrapper =
                    input.closest(
                        ".form-input-wrapper, .form-textarea-wrapper"
                    );

                if (wrapper) {

                    wrapper.classList.remove(
                        "is-focused"
                    );
                }

            }
        );

    });


    /* =====================================================
       REMOVE ERROR STATE WHEN USER TYPES
    ====================================================== */

    formInputs.forEach(function (input) {

        input.addEventListener(
            "input",
            function () {

                input.classList.remove(
                    "input-error"
                );

            }
        );

    });


    /* =====================================================
       FORM VALIDATION
    ====================================================== */

    function validateContactForm() {

        if (!contactForm) {
            return true;
        }

        let isValid = true;

        const requiredInputs = [
            fullnameInput,
            addressInput,
            phoneInput,
            messageInput
        ];


        requiredInputs.forEach(
            function (input) {

                if (!input) {
                    return;
                }

                input.classList.remove(
                    "input-error"
                );

                if (
                    input.value.trim() === ""
                ) {

                    input.classList.add(
                        "input-error"
                    );

                    isValid = false;
                }

            }
        );


        /* =================================================
           MESSAGE LENGTH
        ================================================== */

        if (
            messageInput &&
            messageInput.value.trim() !== ""
        ) {

            const messageLength =
                messageInput.value.trim().length;

            if (
                messageLength < 10 ||
                messageLength > 1000
            ) {

                messageInput.classList.add(
                    "input-error"
                );

                isValid = false;
            }
        }


        /* =================================================
           PHONE VALIDATION
        ================================================== */

        if (
            phoneInput &&
            phoneInput.value.trim() !== ""
        ) {

            const phoneValue =
                phoneInput.value.trim();

            const phonePattern =
                /^[0-9+\-()\s]{7,30}$/;

            if (
                !phonePattern.test(
                    phoneValue
                )
            ) {

                phoneInput.classList.add(
                    "input-error"
                );

                isValid = false;
            }
        }


        /* =================================================
           FOCUS FIRST ERROR
        ================================================== */

        if (!isValid) {

            const firstError =
                $(".input-error");

            if (firstError) {

                firstError.focus();

                firstError.scrollIntoView({
                    behavior: "smooth",
                    block: "center"
                });

            }
        }

        return isValid;
    }


    /* =====================================================
       FORM SUBMISSION
    ====================================================== */

    if (contactForm) {

        contactForm.addEventListener(
            "submit",
            function (event) {

                if (
                    !validateContactForm()
                ) {

                    event.preventDefault();

                    return;
                }


                if (contactSubmitButton) {

                    contactSubmitButton.classList.add(
                        "loading"
                    );

                    contactSubmitButton.disabled =
                        true;


                    const buttonText =
                        $("span", contactSubmitButton);

                    const buttonIcon =
                        $("i", contactSubmitButton);


                    if (buttonText) {

                        buttonText.textContent =
                            "Sending...";
                    }

                    if (buttonIcon) {

                        buttonIcon.className =
                            "fa-solid fa-spinner fa-spin";
                    }
                }

            }
        );
    }


    /* =====================================================
       INTERSECTION OBSERVER
    ====================================================== */

    if ("IntersectionObserver" in window) {

        const contactObserver =
            new IntersectionObserver(
                function (entries, observer) {

                    entries.forEach(
                        function (entry) {

                            if (
                                entry.isIntersecting
                            ) {

                                entry.target.classList.add(
                                    "visible"
                                );

                                entry.target.classList.add(
                                    "section-visible"
                                );

                                observer.unobserve(
                                    entry.target
                                );
                            }

                        }
                    );

                },
                {
                    threshold: 0.12
                }
            );


        $$(".contact-detail").forEach(
            function (element) {

                contactObserver.observe(
                    element
                );

            }
        );


        $$(".contact-form-wrapper").forEach(
            function (element) {

                contactObserver.observe(
                    element
                );

            }
        );


        $$(".contact-hours-container").forEach(
            function (element) {

                contactObserver.observe(
                    element
                );

            }
        );


        $$(".contact-cta-content").forEach(
            function (element) {

                contactObserver.observe(
                    element
                );

            }
        );

    } else {

        $$(".contact-detail").forEach(
            function (element) {

                element.classList.add(
                    "visible"
                );

            }
        );


        $$(".contact-form-wrapper").forEach(
            function (element) {

                element.classList.add(
                    "section-visible"
                );

            }
        );


        $$(".contact-hours-container").forEach(
            function (element) {

                element.classList.add(
                    "section-visible"
                );

            }
        );


        $$(".contact-cta-content").forEach(
            function (element) {

                element.classList.add(
                    "section-visible"
                );

            }
        );
    }


    /* =====================================================
       CTA BUTTON HOVER
    ====================================================== */

    const ctaButton =
        $(".contact-cta-button");

    if (ctaButton) {

        ctaButton.addEventListener(
            "mouseenter",
            function () {

                ctaButton.classList.add(
                    "is-hovered"
                );

            }
        );


        ctaButton.addEventListener(
            "mouseleave",
            function () {

                ctaButton.classList.remove(
                    "is-hovered"
                );

            }
        );
    }


    /* =====================================================
       INITIAL FORM STATE
    ====================================================== */

    if (contactForm) {

        /*
         * Make sure the actual form is visible.
         * This prevents an animation CSS rule such as
         * opacity: 0 from permanently hiding the form.
         */

        contactForm.style.opacity = "1";
        contactForm.style.visibility = "visible";
        contactForm.style.display = "block";

        contactForm.classList.add(
            "contact-form-ready"
        );
    }


    /* =====================================================
       MAKE FORM WRAPPER VISIBLE
    ====================================================== */

    const contactFormWrappers =
        $$(".contact-form-wrapper");

    contactFormWrappers.forEach(
        function (wrapper) {

            /*
             * The wrapper should never remain hidden
             * if IntersectionObserver has not triggered.
             */

            wrapper.classList.add(
                "section-visible"
            );

            wrapper.classList.add(
                "visible"
            );

        }
    );


    /* =====================================================
       KEYBOARD ACCESSIBILITY
    ====================================================== */

    document.addEventListener(
        "keydown",
        function (event) {

            if (
                event.key === "Enter" &&
                document.activeElement === contactMessageClose
            ) {

                event.preventDefault();

                closeContactMessage();

            }

        }
    );

});

