<?php

/* =========================================================
   FOOTER BASE PATH
   Automatically detects the project folder so the footer
   works from root pages, auth pages, admin pages, etc.
========================================================= */

$footerProjectRoot = realpath(__DIR__ . "/..");
$footerDocumentRoot = realpath($_SERVER["DOCUMENT_ROOT"]);

$footerBasePath = "";

if ($footerProjectRoot && $footerDocumentRoot) {

    $footerProjectRoot = str_replace("\\", "/", $footerProjectRoot);
    $footerDocumentRoot = str_replace("\\", "/", $footerDocumentRoot);

    if (strpos($footerProjectRoot, $footerDocumentRoot) === 0) {

        $footerRelativePath = substr(
            $footerProjectRoot,
            strlen($footerDocumentRoot)
        );

        $footerRelativePath = trim(
            str_replace("\\", "/", $footerRelativePath),
            "/"
        );

        if (!empty($footerRelativePath)) {
            $footerBasePath = "/" . $footerRelativePath;
        }
    }
}


/* =========================================================
   FOOTER URL HELPER
========================================================= */

if (!function_exists("footer_url")) {

    function footer_url($path = "")
    {
        global $footerBasePath;

        $path = ltrim($path, "/");

        if (empty($path)) {
            return $footerBasePath . "/";
        }

        return $footerBasePath . "/" . $path;
    }
}


/* =========================================================
   CURRENT YEAR
========================================================= */

$currentYear = date("Y");

?>

<!-- =========================================================
     FOOTER
========================================================= -->

<footer class="site-footer">

    <!-- Decorative background -->
    <div class="footer-decoration footer-decoration-one"></div>
    <div class="footer-decoration footer-decoration-two"></div>


    <div class="footer-container">


        <!-- =================================================
             BRAND
        ================================================== -->

        <div class="footer-brand">

            <a
                href="<?= htmlspecialchars(footer_url("index.php")) ?>"
                class="footer-logo"
                aria-label="Event Solutions by S.H.E. Home">

                <img
                    src="<?= htmlspecialchars(footer_url("images/logo.png")) ?>"
                    alt="Event Solutions by S.H.E.">

            </a>


            <p class="footer-description">

                Creating memorable experiences
                through professional event
                solutions.

            </p>


            <div class="footer-accent-line"></div>


            <p class="footer-tagline">

                <span>Celebrate.</span>
                <span>Connect.</span>
                <span>Create.</span>

            </p>

        </div>


        <!-- =================================================
             FOOTER LINKS
        ================================================== -->

        <div class="footer-links">


            <!-- =============================================
                 NAVIGATION
            ============================================== -->

            <div class="footer-column">

                <h4>
                    Navigation
                </h4>

                <a href="<?= htmlspecialchars(footer_url("index.php")) ?>">
                    Home
                </a>

                <a href="<?= htmlspecialchars(footer_url("events.php")) ?>">
                    Events
                </a>

                <a href="<?= htmlspecialchars(footer_url("venues.php")) ?>">
                    Venues
                </a>

                <a href="<?= htmlspecialchars(footer_url("hosts.php")) ?>">
                    Hosts
                </a>

            </div>


            <!-- =============================================
                 COMPANY
            ============================================== -->

            <div class="footer-column">

                <h4>
                    Company
                </h4>

                <a href="<?= htmlspecialchars(footer_url("services.php")) ?>">
                    Services
                </a>

                <a href="<?= htmlspecialchars(footer_url("contact.php")) ?>">
                    Contact
                </a>

                <a href="<?= htmlspecialchars(footer_url("auth/login.php")) ?>">
                    Login
                </a>

                <a href="<?= htmlspecialchars(footer_url("auth/signup.php")) ?>">
                    Sign Up
                </a>

            </div>


            <!-- =============================================
                 ACCOUNT
            ============================================== -->

            <div class="footer-column">

                <h4>
                    Account
                </h4>

                <a href="<?= htmlspecialchars(footer_url("profile.php")) ?>">
                    My Profile
                </a>

                <a href="<?= htmlspecialchars(footer_url("my-event.php")) ?>">
                    My Events
                </a>

            </div>


        </div>

    </div>


    <!-- =====================================================
         FOOTER BOTTOM
    ====================================================== -->

    <div class="footer-bottom">

        <div class="footer-bottom-inner">

            <p>

                &copy;
                <?= htmlspecialchars($currentYear) ?>
                Event Solutions by S.H.E.
                All rights reserved.

            </p>


            <div class="footer-bottom-links">

                <a href="<?= htmlspecialchars(footer_url("contact.php")) ?>">
                    Contact Us
                </a>

                <span aria-hidden="true">
                    •
                </span>

                <a href="<?= htmlspecialchars(footer_url("events.php")) ?>">
                    Explore Events
                </a>

            </div>

        </div>

    </div>

</footer>
