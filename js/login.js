/* =========================================
   LOGIN PAGE JAVASCRIPT
   EVENT SOLUTIONS BY S.H.E
========================================= */

document.addEventListener("DOMContentLoaded", function () {


    /* =========================================
       PASSWORD TOGGLE
    ========================================== */

    const passwordToggles = document.querySelectorAll(
        ".login-password-toggle"
    );


    passwordToggles.forEach(function (toggle) {

        toggle.addEventListener("click", function () {

            const targetId = toggle.getAttribute("data-target");

            const passwordInput =
                document.getElementById(targetId);


            if (!passwordInput) {
                return;
            }


            if (passwordInput.type === "password") {

                passwordInput.type = "text";

                toggle.classList.add("showing");

                toggle.setAttribute(
                    "aria-label",
                    "Hide password"
                );

                toggle.setAttribute(
                    "title",
                    "Hide password"
                );

            } else {

                passwordInput.type = "password";

                toggle.classList.remove("showing");

                toggle.setAttribute(
                    "aria-label",
                    "Show password"
                );

                toggle.setAttribute(
                    "title",
                    "Show password"
                );
            }

        });

    });


    /* =========================================
       FORM ELEMENTS
    ========================================== */

    const loginForm =
        document.getElementById("loginForm");

    const emailInput =
        document.getElementById("email");

    const passwordInput =
        document.getElementById("password");

    const emailError =
        document.getElementById("emailError");

    const passwordError =
        document.getElementById("passwordError");


    /* =========================================
       HELPER FUNCTIONS
    ========================================== */

    function showError(input, errorElement, message) {

        const group =
            input.closest(".login-form-group");


        if (group) {

            group.classList.add("has-error");

            group.classList.remove("has-success");
        }


        if (errorElement) {

            errorElement.textContent = message;
        }
    }


    function clearError(input, errorElement) {

        const group =
            input.closest(".login-form-group");


        if (group) {

            group.classList.remove("has-error");
        }


        if (errorElement) {

            errorElement.textContent = "";
        }
    }


    /* =========================================
       EMAIL VALIDATION
    ========================================== */

    function validateEmail() {

        const email =
            emailInput.value.trim();


        if (email === "") {

            showError(
                emailInput,
                emailError,
                "Email address is required."
            );

            return false;
        }


        const emailPattern =
            /^[^\s@]+@[^\s@]+\.[^\s@]+$/;


        if (!emailPattern.test(email)) {

            showError(
                emailInput,
                emailError,
                "Please enter a valid email address."
            );

            return false;
        }


        clearError(
            emailInput,
            emailError
        );

        return true;
    }


    /* =========================================
       PASSWORD VALIDATION
    ========================================== */

    function validatePassword() {

        const password =
            passwordInput.value;


        if (password === "") {

            showError(
                passwordInput,
                passwordError,
                "Password is required."
            );

            return false;
        }


        clearError(
            passwordInput,
            passwordError
        );

        return true;
    }


    /* =========================================
       LIVE EMAIL VALIDATION
    ========================================== */

    if (emailInput) {

        emailInput.addEventListener(
            "blur",
            validateEmail
        );


        emailInput.addEventListener(
            "input",
            function () {

                if (
                    emailInput.value.trim() !== ""
                ) {

                    clearError(
                        emailInput,
                        emailError
                    );
                }

            }
        );
    }


    /* =========================================
       LIVE PASSWORD VALIDATION
    ========================================== */

    if (passwordInput) {

        passwordInput.addEventListener(
            "blur",
            validatePassword
        );


        passwordInput.addEventListener(
            "input",
            function () {

                if (
                    passwordInput.value !== ""
                ) {

                    clearError(
                        passwordInput,
                        passwordError
                    );
                }

            }
        );
    }


    /* =========================================
       FORM SUBMISSION
    ========================================== */

    if (loginForm) {

        loginForm.addEventListener(
            "submit",
            function (event) {

                const emailValid =
                    validateEmail();

                const passwordValid =
                    validatePassword();


                if (
                    !emailValid ||
                    !passwordValid
                ) {

                    event.preventDefault();

                    if (!emailValid) {

                        emailInput.focus();

                    } else {

                        passwordInput.focus();
                    }

                    return;
                }


                const submitButton =
                    loginForm.querySelector(
                        ".login-submit"
                    );


                if (submitButton) {

                    submitButton.disabled = true;

                    submitButton.style.opacity = "0.75";

                    submitButton.style.cursor =
                        "wait";
                }

            }
        );
    }


    /* =========================================
       FORGOT PASSWORD
    ========================================== */

    const forgotPassword =
        document.getElementById(
            "forgotPassword"
        );


    if (forgotPassword) {

        forgotPassword.addEventListener(
            "click",
            function (event) {

                event.preventDefault();

                alert(
                    "Password recovery will be available soon."
                );

            }
        );
    }


    /* =========================================
       CAROUSEL
    ========================================== */

    const slides =
        document.querySelectorAll(
            ".login-carousel-slide"
        );

    const dots =
        document.querySelectorAll(
            ".login-carousel-dot"
        );


    if (
        slides.length > 0 &&
        dots.length > 0
    ) {

        let currentSlide = 0;

        let carouselTimer;


        function showSlide(index) {

            if (
                index < 0 ||
                index >= slides.length
            ) {
                return;
            }


            slides.forEach(function (slide) {

                slide.classList.remove(
                    "active"
                );

            });


            dots.forEach(function (dot) {

                dot.classList.remove(
                    "active"
                );

            });


            slides[index].classList.add(
                "active"
            );


            dots[index].classList.add(
                "active"
            );


            currentSlide = index;
        }


        function nextSlide() {

            let next =
                currentSlide + 1;


            if (
                next >= slides.length
            ) {

                next = 0;
            }


            showSlide(next);
        }


        function startCarousel() {

            clearInterval(
                carouselTimer
            );


            carouselTimer =
                setInterval(
                    nextSlide,
                    5000
                );
        }


        dots.forEach(function (
            dot,
            index
        ) {

            dot.addEventListener(
                "click",
                function () {

                    showSlide(index);

                    startCarousel();

                }
            );

        });


        showSlide(0);

        startCarousel();
    }

});