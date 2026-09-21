/* =========================================
   SIGNUP PAGE JAVASCRIPT
   EVENT SOLUTIONS BY S.H.E
========================================= */

document.addEventListener("DOMContentLoaded", function () {


    /* =========================================
       FORM ELEMENTS
    ========================================= */

    const form =
        document.getElementById("signupForm");

    const fullname =
        document.getElementById("fullname");

    const email =
        document.getElementById("email");

    const address =
        document.getElementById("address");

    const password =
        document.getElementById("password");

    const confirmPassword =
        document.getElementById("confirm_password");

    const passwordStrength =
        document.getElementById("passwordStrength");

    const strengthText =
        document.getElementById("strengthText");


    /* =========================================
       PASSWORD SHOW / HIDE
    ========================================= */

    const passwordToggles =
        document.querySelectorAll(
            ".password-toggle"
        );


    passwordToggles.forEach(function (button) {

        button.addEventListener(
            "click",
            function () {

                const target =
                    button.getAttribute(
                        "data-target"
                    );

                const input =
                    document.getElementById(
                        target
                    );


                if (!input) {
                    return;
                }


                if (input.type === "password") {

                    input.type = "text";

                    button.classList.add(
                        "showing"
                    );

                    button.setAttribute(
                        "aria-label",
                        "Hide password"
                    );

                    button.setAttribute(
                        "title",
                        "Hide password"
                    );

                } else {

                    input.type = "password";

                    button.classList.remove(
                        "showing"
                    );

                    button.setAttribute(
                        "aria-label",
                        "Show password"
                    );

                    button.setAttribute(
                        "title",
                        "Show password"
                    );

                }

            }
        );

    });


    /* =========================================
       PASSWORD STRENGTH
    ========================================= */

    function checkPasswordStrength(value) {

        let score = 0;


        if (value.length >= 6) {
            score++;
        }


        if (value.length >= 10) {
            score++;
        }


        if (/[a-z]/.test(value)) {
            score++;
        }


        if (/[A-Z]/.test(value)) {
            score++;
        }


        if (/[0-9]/.test(value)) {
            score++;
        }


        if (/[^A-Za-z0-9]/.test(value)) {
            score++;
        }


        if (value.length === 0) {

            return {
                level: "",
                text: "—"
            };

        }


        if (
            value.length < 6 ||
            score <= 2
        ) {

            return {
                level: "weak",
                text: "Weak"
            };

        }


        if (score <= 4) {

            return {
                level: "medium",
                text: "Medium"
            };

        }


        return {
            level: "strong",
            text: "Strong"
        };

    }


    function updatePasswordStrength() {

        if (!passwordStrength || !password) {
            return;
        }


        const result =
            checkPasswordStrength(
                password.value
            );


        passwordStrength.classList.remove(
            "weak",
            "medium",
            "strong"
        );


        if (result.level !== "") {

            passwordStrength.classList.add(
                result.level
            );

        }


        if (strengthText) {

            strengthText.textContent =
                result.text;

        }

    }


    if (password) {

        password.addEventListener(
            "input",
            function () {

                updatePasswordStrength();

                clearError(
                    "password",
                    "passwordError"
                );


                if (
                    confirmPassword &&
                    confirmPassword.value.length > 0
                ) {

                    validateConfirmPassword();

                }

            }
        );

    }


    /* =========================================
       ERROR HELPERS
    ========================================= */

    function showError(
        inputId,
        errorId,
        message
    ) {

        const input =
            document.getElementById(
                inputId
            );

        const error =
            document.getElementById(
                errorId
            );


        if (input) {

            const group =
                input.closest(
                    ".form-group"
                );

            if (group) {

                group.classList.add(
                    "has-error"
                );

                group.classList.remove(
                    "has-success"
                );

            }

        }


        if (error) {

            error.textContent =
                message;

        }

        return false;

    }


    function showSuccess(inputId) {

        const input =
            document.getElementById(
                inputId
            );


        if (!input) {
            return true;
        }


        const group =
            input.closest(
                ".form-group"
            );


        if (group) {

            group.classList.remove(
                "has-error"
            );

            group.classList.add(
                "has-success"
            );

        }


        return true;

    }


    function clearError(
        inputId,
        errorId
    ) {

        const input =
            document.getElementById(
                inputId
            );

        const error =
            document.getElementById(
                errorId
            );


        if (input) {

            const group =
                input.closest(
                    ".form-group"
                );

            if (group) {

                group.classList.remove(
                    "has-error"
                );

            }

        }


        if (error) {

            error.textContent = "";

        }

    }


    /* =========================================
       FULL NAME VALIDATION
    ========================================= */

    function validateFullName() {

        if (!fullname) {
            return true;
        }


        const value =
            fullname.value.trim();


        if (value.length === 0) {

            return showError(
                "fullname",
                "fullnameError",
                "Full name is required."
            );

        }


        if (value.length < 2) {

            return showError(
                "fullname",
                "fullnameError",
                "Please enter your full name."
            );

        }


        return showSuccess(
            "fullname"
        );

    }


    /* =========================================
       EMAIL VALIDATION
    ========================================= */

    function validateEmail() {

        if (!email) {
            return true;
        }


        const value =
            email.value.trim();


        if (value.length === 0) {

            return showError(
                "email",
                "emailError",
                "Email address is required."
            );

        }


        const emailPattern =
            /^[^\s@]+@[^\s@]+\.[^\s@]+$/;


        if (!emailPattern.test(value)) {

            return showError(
                "email",
                "emailError",
                "Please enter a valid email address."
            );

        }


        return showSuccess(
            "email"
        );

    }


   

    function validateAddress() {

    if (!address) {
        return true;
    }

    const value = address.value.trim();


    /* =========================================
       REQUIRED CHECK
    ========================================= */

    if (value.length === 0) {

        return showError(
            "address",
            "addressError",
            "Address is required."
        );

    }


    /* =========================================
       MINIMUM LENGTH
    ========================================= */

    if (value.length < 5) {

        return showError(
            "address",
            "addressError",
            "Please enter a complete address."
        );

    }


    /* =========================================
       MAXIMUM LENGTH
    ========================================= */

    if (value.length > 255) {

        return showError(
            "address",
            "addressError",
            "Address must not exceed 255 characters."
        );

    }


    /* =========================================
       INVALID CHARACTERS
       Allows:
       - Letters
       - Numbers
       - Spaces
       - Comma
       - Period
       - Hyphen
       - Slash
       - # 
       - Apostrophe
    ========================================= */

    const addressPattern =
        /^[a-zA-Z0-9À-ÿ\s,.\-\/#']+$/;

    if (!addressPattern.test(value)) {

        return showError(
            "address",
            "addressError",
            "Please enter a valid address."
        );

    }


    /* =========================================
       MUST CONTAIN A LETTER
    ========================================= */

    if (!/[a-zA-ZÀ-ÿ]/.test(value)) {

        return showError(
            "address",
            "addressError",
            "Address must contain letters."
        );

    }


    /* =========================================
       PREVENT EXCESSIVE SPACES
    ========================================= */

    if (/\s{2,}/.test(value)) {

        return showError(
            "address",
            "addressError",
            "Please remove extra spaces from the address."
        );

    }


    /* =========================================
       PREVENT REPEATED PUNCTUATION
    ========================================= */

    if (/[,.#\/\-]{2,}/.test(value)) {

        return showError(
            "address",
            "addressError",
            "Please enter a properly formatted address."
        );

    }


    /* =========================================
       SUCCESS
    ========================================= */

    return showSuccess(
        "address"
    );
}



    /* =========================================
       PASSWORD VALIDATION
    ========================================= */

    function validatePassword() {

        if (!password) {
            return true;
        }


        const value =
            password.value;


        if (value.length === 0) {

            return showError(
                "password",
                "passwordError",
                "Password is required."
            );

        }


        if (value.length < 6) {

            return showError(
                "password",
                "passwordError",
                "Password must be at least 6 characters."
            );

        }


        return showSuccess(
            "password"
        );

    }


    /* =========================================
       CONFIRM PASSWORD
    ========================================= */

    function validateConfirmPassword() {

        if (!confirmPassword) {
            return true;
        }


        const value =
            confirmPassword.value;


        if (value.length === 0) {

            return showError(
                "confirm_password",
                "confirmPasswordError",
                "Please confirm your password."
            );

        }


        if (
            password &&
            value !== password.value
        ) {

            return showError(
                "confirm_password",
                "confirmPasswordError",
                "Passwords do not match."
            );

        }


        return showSuccess(
            "confirm_password"
        );

    }


    /* =========================================
       LIVE VALIDATION
    ========================================= */

    if (fullname) {

        fullname.addEventListener(
            "blur",
            validateFullName
        );


        fullname.addEventListener(
            "input",
            function () {

                if (
                    fullname.value.trim()
                        .length > 0
                ) {

                    clearError(
                        "fullname",
                        "fullnameError"
                    );

                }

            }
        );

    }


    if (email) {

        email.addEventListener(
            "blur",
            validateEmail
        );


        email.addEventListener(
            "input",
            function () {

                if (
                    email.value.trim()
                        .length > 0
                ) {

                    clearError(
                        "email",
                        "emailError"
                    );

                }

            }
        );

    }

     if (address) {

        address.addEventListener(
            "blur",
            validateAddress
        );


        address.addEventListener(
            "input",
            function () {

                if (
                    email.value.trim()
                        .length > 0
                ) {

                    clearError(
                        "address",
                        "addressError"
                    );

                }

            }
        );

    }


    if (password) {

        password.addEventListener(
            "blur",
            validatePassword
        );

    }


    if (confirmPassword) {

        confirmPassword.addEventListener(
            "blur",
            validateConfirmPassword
        );


        confirmPassword.addEventListener(
            "input",
            function () {

                if (
                    confirmPassword.value.length > 0
                ) {

                    validateConfirmPassword();

                }

            }
        );

    }


    /* =========================================
       FORM SUBMISSION
    ========================================= */

    if (form) {

        form.addEventListener(
            "submit",
            function (event) {

                const validName =
                    validateFullName();

                const validEmail =
                    validateEmail();

                const validAddress=
                    validateAddress();

                const validPassword =
                    validatePassword();

                const validConfirm =
                    validateConfirmPassword();


                if (
                    !validName ||
                    !validEmail ||
                    !validAddress||
                    !validPassword ||
                    !validConfirm
                ) {

                    event.preventDefault();


                    const firstError =
                        form.querySelector(
                            ".has-error input"
                        );


                    if (firstError) {

                        firstError.focus();

                    }

                    return false;

                }

            }
        );

    }


    /* =========================================
       CAROUSEL
    ========================================= */

    const slides =
        document.querySelectorAll(
            ".carousel-slide"
        );

    const dots =
        document.querySelectorAll(
            ".carousel-dot"
        );


    let currentSlide = 0;

    let carouselTimer = null;


    function showSlide(index) {

        if (!slides.length) {
            return;
        }


        if (index >= slides.length) {
            index = 0;
        }


        if (index < 0) {
            index = slides.length - 1;
        }


        slides.forEach(
            function (slide, i) {

                slide.classList.toggle(
                    "active",
                    i === index
                );

            }
        );


        dots.forEach(
            function (dot, i) {

                dot.classList.toggle(
                    "active",
                    i === index
                );

            }
        );


        currentSlide = index;

    }


    function nextSlide() {

        showSlide(
            currentSlide + 1
        );

    }


    function startCarousel() {

        if (slides.length <= 1) {
            return;
        }


        stopCarousel();


        carouselTimer =
            setInterval(
                nextSlide,
                5000
            );

    }


    function stopCarousel() {

        if (carouselTimer !== null) {

            clearInterval(
                carouselTimer
            );

            carouselTimer = null;

        }

    }


    dots.forEach(
        function (dot, index) {

            dot.addEventListener(
                "click",
                function () {

                    showSlide(index);

                    startCarousel();

                }
            );

        }
    );


    /* =========================================
       PAUSE CAROUSEL ON HOVER
    ========================================= */

    const carousel =
        document.querySelector(
            ".signup-carousel"
        );


    if (carousel) {

        carousel.addEventListener(
            "mouseenter",
            stopCarousel
        );


        carousel.addEventListener(
            "mouseleave",
            startCarousel
        );

    }


    /* =========================================
       INITIALIZE
    ========================================= */

    updatePasswordStrength();


    if (slides.length > 0) {

        showSlide(0);

        startCarousel();

    }

});