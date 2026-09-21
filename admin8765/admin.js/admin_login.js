/* =========================================
   ADMIN LOGIN
========================================= */

document.addEventListener("DOMContentLoaded", function () {

    const form = document.getElementById("adminLoginForm");
    const passwordInput = document.getElementById("password");
    const passwordToggle = document.getElementById("passwordToggle");
    const passwordToggleIcon = document.getElementById("passwordToggleIcon");
    const loginButton = document.getElementById("adminLoginButton");

    /*
    |--------------------------------------------------------------------------
    | Show / Hide Password
    |--------------------------------------------------------------------------
    */

    if (passwordToggle && passwordInput) {

        passwordToggle.addEventListener("click", function () {

            const isPassword =
                passwordInput.type === "password";

            if (isPassword) {

                passwordInput.type = "text";

                passwordToggleIcon.textContent =
                    "visibility_off";

                passwordToggle.setAttribute(
                    "aria-label",
                    "Hide password"
                );

                passwordToggle.setAttribute(
                    "title",
                    "Hide password"
                );

            } else {

                passwordInput.type = "password";

                passwordToggleIcon.textContent =
                    "visibility";

                passwordToggle.setAttribute(
                    "aria-label",
                    "Show password"
                );

                passwordToggle.setAttribute(
                    "title",
                    "Show password"
                );
            }

        });

    }


    /*
    |--------------------------------------------------------------------------
    | Form Validation
    |--------------------------------------------------------------------------
    */

    if (form) {

        form.addEventListener("submit", function (event) {

            const emailInput =
                document.getElementById("email");

            const email =
                emailInput
                    ? emailInput.value.trim()
                    : "";

            const password =
                passwordInput
                    ? passwordInput.value
                    : "";

            if (email === "" || password === "") {

                event.preventDefault();

                if (email === "" && emailInput) {
                    emailInput.focus();
                } else if (passwordInput) {
                    passwordInput.focus();
                }

                return;
            }


            /*
            |--------------------------------------------------------------------------
            | Loading State
            |--------------------------------------------------------------------------
            */

            if (loginButton) {

                loginButton.classList.add("loading");

                loginButton.disabled = true;
            }

        });

    }


    /*
    |--------------------------------------------------------------------------
    | Remove Loading State When Browser Goes Back
    |--------------------------------------------------------------------------
    */

    window.addEventListener("pageshow", function () {

        if (loginButton) {

            loginButton.classList.remove("loading");

            loginButton.disabled = false;
        }

    });

});