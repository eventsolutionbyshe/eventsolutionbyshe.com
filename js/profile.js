/* =========================================================
   PROFILE PAGE JAVASCRIPT
   EVENT SOLUTIONS BY S.H.E.
========================================================= */

document.addEventListener(
    "DOMContentLoaded",
    function () {


        /* =====================================================
           PASSWORD SHOW / HIDE
        ====================================================== */

        const passwordToggles =
            document.querySelectorAll(
                ".password-toggle"
            );


        passwordToggles.forEach(
            function (button) {

                button.addEventListener(
                    "click",
                    function () {

                        const targetId =
                            this.getAttribute(
                                "data-target"
                            );

                        const input =
                            document.getElementById(
                                targetId
                            );

                        if (!input) {
                            return;
                        }


                        if (
                            input.type === "password"
                        ) {

                            input.type = "text";

                            this.textContent =
                                "Hide";

                            this.setAttribute(
                                "aria-label",
                                "Hide password"
                            );

                        } else {

                            input.type = "password";

                            this.textContent =
                                "Show";

                            this.setAttribute(
                                "aria-label",
                                "Show password"
                            );
                        }

                    }
                );

            }
        );


        /* =====================================================
           PROFILE IMAGE INPUT
        ====================================================== */

        const imageInput =
            document.getElementById(
                "profile_image"
            );

        const imageLabel =
            document.querySelector(
                ".photo-upload-label"
            );


        if (
            imageInput &&
            imageLabel
        ) {

            imageInput.addEventListener(
                "change",
                function () {

                    if (
                        this.files &&
                        this.files.length > 0
                    ) {

                        const file =
                            this.files[0];

                        imageLabel.textContent =
                            file.name;

                    } else {

                        imageLabel.textContent =
                            "Change Profile Photo";
                    }

                }
            );

        }


        /* =====================================================
           ALERT CLOSE BUTTON
        ====================================================== */

        const closeButtons =
            document.querySelectorAll(
                ".alert-close"
            );


        closeButtons.forEach(
            function (button) {

                button.addEventListener(
                    "click",
                    function () {

                        const alert =
                            this.closest(
                                ".profile-alert"
                            );

                        if (!alert) {
                            return;
                        }

                        alert.classList.add(
                            "alert-hide"
                        );


                        setTimeout(
                            function () {

                                alert.remove();

                            },
                            300
                        );

                    }
                );

            }
        );


        /* =====================================================
           AUTO HIDE ALERTS
        ====================================================== */

        const alerts =
            document.querySelectorAll(
                ".profile-alert"
            );


        alerts.forEach(
            function (alert) {

                setTimeout(
                    function () {

                        if (
                            document.body.contains(
                                alert
                            )
                        ) {

                            alert.classList.add(
                                "alert-hide"
                            );


                            setTimeout(
                                function () {

                                    if (
                                        document.body.contains(
                                            alert
                                        )
                                    ) {

                                        alert.remove();

                                    }

                                },
                                300
                            );
                        }

                    },
                    5000
                );

            }
        );


        /* =====================================================
           PASSWORD MATCH CHECK
        ====================================================== */

        const newPassword =
            document.getElementById(
                "new_password"
            );

        const confirmPassword =
            document.getElementById(
                "confirm_password"
            );


        if (
            newPassword &&
            confirmPassword
        ) {

            confirmPassword.addEventListener(
                "input",
                function () {

                    if (
                        this.value !== "" &&
                        this.value !==
                        newPassword.value
                    ) {

                        this.classList.add(
                            "input-error"
                        );

                    } else {

                        this.classList.remove(
                            "input-error"
                        );
                    }

                }
            );


            newPassword.addEventListener(
                "input",
                function () {

                    if (
                        confirmPassword.value !== "" &&
                        confirmPassword.value !==
                        this.value
                    ) {

                        confirmPassword.classList.add(
                            "input-error"
                        );

                    } else {

                        confirmPassword.classList.remove(
                            "input-error"
                        );
                    }

                }
            );

        }


        /* =====================================================
           PROFILE FORM VALIDATION
        ====================================================== */

        const passwordForm =
            document.querySelector(
                ".password-form"
            );


        if (passwordForm) {

            passwordForm.addEventListener(
                "submit",
                function (event) {

                    if (
                        newPassword &&
                        confirmPassword &&
                        newPassword.value !==
                        confirmPassword.value
                    ) {

                        event.preventDefault();

                        confirmPassword.classList.add(
                            "input-error"
                        );

                        confirmPassword.focus();

                    }

                }
            );

        }

    }
);