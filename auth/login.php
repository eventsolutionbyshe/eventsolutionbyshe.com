<?php

/* =========================================================
   SESSION
========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* =========================================================
   PREVENT BROWSER CACHE
========================================================= */

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");


/* =========================================================
   DATABASE
========================================================= */

require_once "../config/database.php";


/* =========================================================
   REDIRECT IF ALREADY LOGGED IN
========================================================= */

if (isset($_SESSION["user_id"])) {

    $role = strtolower(
        trim($_SESSION["role"] ?? "")
    );


    /* =====================================================
       ADMIN
    ===================================================== */

    if ($role === "admin") {

        header(
            "Location: ../admin8765/admin_dashboard.php",
            true,
            303
        );

        exit;
    }


    /* =====================================================
       CUSTOMER
    ===================================================== */

    if ($role === "customer") {

        header(
            "Location: ../index.php",
            true,
            303
        );

        exit;
    }


    /* =====================================================
       INVALID SESSION
    ===================================================== */

    unset(
        $_SESSION["user_id"],
        $_SESSION["fullname"],
        $_SESSION["email"],
        $_SESSION["role"],
        $_SESSION["profile_image"],
        $_SESSION["remember"]
    );

    session_regenerate_id(true);
}


/* =========================================================
   VARIABLES
========================================================= */

$errors = [];

$email = "";


/* =========================================================
   LOGIN
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim(
        $_POST["email"] ?? ""
    );

    $password = $_POST["password"] ?? "";


    /* =====================================================
       VALIDATION
    ===================================================== */

    if ($email === "") {

        $errors[] = "Email address is required.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $errors[] = "Please enter a valid email address.";

    }


    if ($password === "") {

        $errors[] = "Password is required.";

    }


    /* =====================================================
       CHECK DATABASE
    ===================================================== */

    if (empty($errors)) {

        $stmt = $conn->prepare("
            SELECT
                id,
                fullname,
                email,
                password,
                role,
                profile_image
            FROM users
            WHERE email = ?
            LIMIT 1
        ");


        if (!$stmt) {

            $errors[] =
                "Unable to process your login. Please try again.";

        } else {

            $stmt->bind_param(
                "s",
                $email
            );

            $stmt->execute();

            $result = $stmt->get_result();


            /* =================================================
               USER FOUND
            ================================================= */

            if (
                $result &&
                $result->num_rows === 1
            ) {

                $user = $result->fetch_assoc();


                /* =============================================
                   VERIFY PASSWORD
                ============================================= */

                if (
                    password_verify(
                        $password,
                        $user["password"]
                    )
                ) {


                    /* =========================================
                       CHECK ROLE
                    ========================================= */

                    $role = strtolower(
                        trim(
                            $user["role"] ?? "customer"
                        )
                    );


                    /* =========================================
                       VALID ROLE
                    ========================================= */

                    if (
                        $role !== "admin" &&
                        $role !== "customer"
                    ) {

                        $errors[] =
                            "Your account role is invalid.";

                    } else {


                        /* =====================================
                           REGENERATE SESSION ID
                        ===================================== */

                        session_regenerate_id(true);


                        /* =====================================
                           CLEAR OLD SESSION DATA
                        ===================================== */

                        unset(
                            $_SESSION["user_id"],
                            $_SESSION["fullname"],
                            $_SESSION["email"],
                            $_SESSION["role"],
                            $_SESSION["profile_image"],
                            $_SESSION["remember"]
                        );


                        /* =====================================
                           STORE USER SESSION
                        ===================================== */

                        $_SESSION["user_id"] =
                            (int)$user["id"];

                        $_SESSION["fullname"] =
                            $user["fullname"];

                        $_SESSION["email"] =
                            $user["email"];

                        $_SESSION["role"] =
                            $role;


                        /* =====================================
                           STORE PROFILE IMAGE
                        ===================================== */

                        $_SESSION["profile_image"] =
                            trim(
                                (string)(
                                    $user["profile_image"]
                                    ?? ""
                                )
                            );


                        /* =====================================
                           OPTIONAL REMEMBER ME
                        ===================================== */

                        if (
                            isset(
                                $_POST["remember"]
                            )
                        ) {

                            $_SESSION["remember"] =
                                true;

                        } else {

                            unset(
                                $_SESSION["remember"]
                            );
                        }


                        /* =====================================
                           ADMIN
                        ===================================== */

                        if (
                            $_SESSION["role"] === "admin"
                        ) {

                            header(
                                "Location: ../admin8765/admin_dashboard.php",
                                true,
                                303
                            );

                            exit;
                        }


                        /* =====================================
                           CUSTOMER
                        ===================================== */

                        header(
                            "Location: ../index.php",
                            true,
                            303
                        );

                        exit;
                    }


                } else {

                    $errors[] =
                        "Incorrect email or password.";
                }


            } else {

                $errors[] =
                    "Incorrect email or password.";
            }


            $stmt->close();
        }
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Login | Event Solutions by S.H.E
    </title>


    <!-- =====================================================
         GOOGLE FONTS
    ====================================================== -->

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Monsieur+La+Doulaise&family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet"
    >


    <!-- =====================================================
         HEADER CSS
    ====================================================== -->

    <link
        rel="stylesheet"
        href="../css/header.css?v=2"
    >

    <link
        rel="stylesheet"
        href="../css/footer.css"
    >


    <!-- =====================================================
         LOGIN CSS
    ====================================================== -->

    <link
        rel="stylesheet"
        href="../css/login.css?v=2"
    >

</head>


<body>


    <!-- =====================================================
         HEADER
    ====================================================== -->

    <?php include "../includes/header.php"; ?>


    <!-- =====================================================
         LOGIN PAGE
    ====================================================== -->

    <main class="login-page">

        <div class="login-container">


            <!-- =================================================
                 LEFT CAROUSEL
            ================================================== -->

            <section
                class="login-carousel"
                aria-label="Event highlights"
            >

                <div class="login-carousel-slide active">

                    <img
                        src="../images/img1.jpeg"
                        alt="Event celebration"
                    >

                    <div class="login-carousel-overlay"></div>

                    <div class="login-carousel-content">

                        <span>
                            EVENT SOLUTIONS BY S.H.E
                        </span>

                        <h1>
                            Welcome
                            <strong>
                                Back
                            </strong>
                        </h1>

                        <p>
                            Your next unforgettable event
                            starts with us.
                        </p>

                    </div>

                </div>


                <!-- SLIDE 2 -->

                <div class="login-carousel-slide">

                    <img
                        src="../images/img2.jpeg"
                        alt="Beautiful event setup"
                    >

                    <div class="login-carousel-overlay"></div>

                    <div class="login-carousel-content">

                        <span>
                            EVENT SOLUTIONS BY S.H.E
                        </span>

                        <h1>
                            Celebrate
                            <strong>
                                Beautifully
                            </strong>
                        </h1>

                        <p>
                            Every detail matters when
                            creating memorable moments.
                        </p>

                    </div>

                </div>


                <!-- SLIDE 3 -->

                <div class="login-carousel-slide">

                    <img
                        src="../images/img3.jpeg"
                        alt="Elegant event"
                    >

                    <div class="login-carousel-overlay"></div>

                    <div class="login-carousel-content">

                        <span>
                            EVENT SOLUTIONS BY S.H.E
                        </span>

                        <h1>
                            Make It
                            <strong>
                                Unforgettable
                            </strong>
                        </h1>

                        <p>
                            Let us help bring your special
                            occasion to life.
                        </p>

                    </div>

                </div>


                <!-- =================================================
                     CAROUSEL DOTS
                ================================================== -->

                <div class="login-carousel-dots">

                    <button
                        type="button"
                        class="login-carousel-dot active"
                        data-slide="0"
                        aria-label="Slide 1"
                    ></button>

                    <button
                        type="button"
                        class="login-carousel-dot"
                        data-slide="1"
                        aria-label="Slide 2"
                    ></button>

                    <button
                        type="button"
                        class="login-carousel-dot"
                        data-slide="2"
                        aria-label="Slide 3"
                    ></button>

                </div>

            </section>


            <!-- =================================================
                 LOGIN CARD
            ================================================== -->

            <section class="login-card">


                <!-- CARD HEADER -->

                <div class="login-card-header">

                    <span>
                        WELCOME BACK
                    </span>

                    <h2>
                        Sign In
                    </h2>

                    <p>
                        Login to continue to your account.
                    </p>

                </div>


                <!-- ERROR MESSAGE -->

                <?php if (!empty($errors)): ?>

                    <div
                        class="login-message error"
                        role="alert"
                    >

                        <?php foreach ($errors as $error): ?>

                            <p>
                                <?php
                                echo htmlspecialchars(
                                    $error,
                                    ENT_QUOTES,
                                    "UTF-8"
                                );
                                ?>
                            </p>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>


                <!-- LOGIN FORM -->

                <form
                    action="login.php"
                    method="POST"
                    class="login-form"
                    id="loginForm"
                    novalidate
                >

                    <!-- EMAIL -->

                    <div class="login-form-group">

                        <label for="email">
                            Email Address
                        </label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="<?php
                                echo htmlspecialchars(
                                    $email,
                                    ENT_QUOTES,
                                    "UTF-8"
                                );
                            ?>"
                            placeholder="Enter your email address"
                            autocomplete="email"
                            required
                        >

                        <span
                            class="login-field-error"
                            id="emailError"
                        ></span>

                    </div>


                    <!-- PASSWORD -->

                    <div class="login-form-group">

                        <label for="password">
                            Password
                        </label>

                        <div class="login-password-wrapper">

                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="Enter your password"
                                autocomplete="current-password"
                                required
                            >

                            <button
                                type="button"
                                class="login-password-toggle"
                                data-target="password"
                                aria-label="Show password"
                                title="Show password"
                            >

                                <svg
                                    class="login-eye-icon"
                                    viewBox="0 0 24 24"
                                    aria-hidden="true"
                                >

                                    <path
                                        d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"
                                    ></path>

                                    <circle
                                        cx="12"
                                        cy="12"
                                        r="2.5"
                                    ></circle>

                                </svg>

                                <svg
                                    class="login-eye-off-icon"
                                    viewBox="0 0 24 24"
                                    aria-hidden="true"
                                >

                                    <path d="M3 3l18 18"></path>

                                    <path
                                        d="M10.6 6.2A9.7 9.7 0 0 1 12 6c6.5 0 10 6 10 6a18 18 0 0 1-3.1 3.7"
                                    ></path>

                                    <path
                                        d="M6.2 6.2C3.5 8.2 2 12 2 12s3.5 6 10 6c1.6 0 3-.3 4.2-.8"
                                    ></path>

                                    <path
                                        d="M9.9 9.9a3 3 0 0 0 4.2 4.2"
                                    ></path>

                                </svg>

                            </button>

                        </div>

                        <span
                            class="login-field-error"
                            id="passwordError"
                        ></span>

                    </div>


                    <!-- REMEMBER / FORGOT -->

                    <div class="login-options">

                        <label class="remember-me">

                            <input
                                type="checkbox"
                                name="remember"
                                id="remember"
                            >

                            <span class="custom-checkbox"></span>

                            <span class="remember-text">
                                Remember me
                            </span>

                        </label>


                        <a
                            href="#"
                            class="forgot-password"
                            id="forgotPassword"
                        >
                            Forgot Password?
                        </a>

                    </div>


                    <!-- LOGIN BUTTON -->

                    <button
                        type="submit"
                        class="login-submit"
                        id="loginSubmit"
                    >

                        <span>
                            Login
                        </span>

                        <svg
                            viewBox="0 0 24 24"
                            aria-hidden="true"
                        >

                            <path d="M5 12h14"></path>

                            <path d="m13 6 6 6-6 6"></path>

                        </svg>

                    </button>

                </form>


                <!-- SIGN UP -->

                <div class="login-signup">

                    <span>
                        Don't have an account?
                    </span>

                    <a href="signup.php">
                        Create Account
                    </a>

                </div>

            </section>

        </div>

    </main>


    <!-- =====================================================
         FOOTER
    ====================================================== -->

    <?php include "../includes/footer.php"; ?>


    <!-- =====================================================
         JAVASCRIPT
    ====================================================== -->

    <script src="../js/header.js?v=2"></script>

    <script src="../js/login.js?v=2"></script>


</body>

</html>