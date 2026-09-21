<?php

session_start();

require_once __DIR__ . "/config/database.php";

/*
|--------------------------------------------------------------------------
| Helper
|--------------------------------------------------------------------------
*/

if (!function_exists("e")) {
    function e($value)
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
    }
}

/*
|--------------------------------------------------------------------------
| Host Type Filter
|--------------------------------------------------------------------------
*/

$hostType = trim($_GET["host_type"] ?? "");

/*
|--------------------------------------------------------------------------
| Load All Hosts + Packages
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        h.id AS host_id,
        h.host_name,
        h.host_type,
        h.description AS host_description,
        h.image AS host_image,
        h.email AS host_email,
        h.phone AS host_phone,

        hp.id AS package_id,
        hp.package_name,
        hp.price,
        hp.inclusions

    FROM hosts h

    LEFT JOIN host_packages hp
        ON hp.host_id = h.id

    ORDER BY
        h.host_name ASC,
        hp.price ASC,
        hp.package_name ASC
";

$result = $conn->query($sql);

$hosts = [];

if ($result) {

    while ($row = $result->fetch_assoc()) {

        $hostId = (int)$row["host_id"];

        if (!isset($hosts[$hostId])) {

            $hosts[$hostId] = [
                "id" => $hostId,
                "host_name" => $row["host_name"],
                "host_type" => $row["host_type"],
                "description" => $row["host_description"],
                "image" => $row["host_image"],
                "email" => $row["host_email"],
                "phone" => $row["host_phone"],
                "packages" => []
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Add Package
        |--------------------------------------------------------------------------
        */

        if (!empty($row["package_id"])) {

            $hosts[$hostId]["packages"][] = [
                "id" => (int)$row["package_id"],
                "package_name" => $row["package_name"],
                "price" => $row["price"],
                "inclusions" => $row["inclusions"]
            ];
        }
    }
}

$hosts = array_values($hosts);

/*
|--------------------------------------------------------------------------
| Host Types
|--------------------------------------------------------------------------
*/

$hostTypes = [];

foreach ($hosts as $host) {

    if (!empty($host["host_type"])) {

        $type = trim($host["host_type"]);

        if ($type !== "") {
            $hostTypes[$type] = $type;
        }
    }
}

ksort($hostTypes);

/*
|--------------------------------------------------------------------------
| Apply Host Type Filter
|--------------------------------------------------------------------------
*/

if ($hostType !== "") {

    $hosts = array_values(
        array_filter(
            $hosts,
            function ($host) use ($hostType) {

                return strcasecmp(
                    trim((string)$host["host_type"]),
                    $hostType
                ) === 0;
            }
        )
    );
}

/*
|--------------------------------------------------------------------------
| Counts
|--------------------------------------------------------------------------
*/

$totalHosts = count($hosts);

$totalPackages = 0;

foreach ($hosts as $host) {
    $totalPackages += count($host["packages"]);
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        Explore Our Hosts | Event Solutions by S.H.E.
    </title>

    <!-- GOOGLE FONTS -->

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com">

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=Monsieur+La+Doulaise&family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <!-- Material Symbols -->

    <link
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,200,0..1,0"
        rel="stylesheet" />

    <!-- Header -->

    <link
        rel="stylesheet"
        href="css/header.css?v=4">

    <!-- Footer -->

    <link
        rel="stylesheet"
        href="css/footer.css?v=4">

    <!-- Host Page -->

    <link
        rel="stylesheet"
        href="css/host.css?v=4">

</head>

<body>

    <?php include "includes/header.php"; ?>


    <main class="hosts-page">

        <!-- =========================================================
         HERO
    ========================================================== -->

        <section class="hosts-hero">

            <div class="hosts-hero-overlay"></div>

            <div class="hosts-hero-content">

                <span class="hosts-eyebrow">
                    EVENT SOLUTIONS BY S.H.E.
                </span>

                <h1>
                    Our
                    <span>Professional Hosts</span>
                </h1>

                <p>
                    Discover our professional hosts and their available
                    packages for your special occasion.
                </p>

            </div>

        </section>


        <!-- =========================================================
         FILTER AREA
    ========================================================== -->

        <section class="hosts-filter-section">

            <div class="hosts-container">

                <div class="hosts-filter-bar">

                    <div class="filter-heading">

                        <span class="material-symbols-outlined">
                            groups
                        </span>

                        <div>
                            <span class="filter-label">
                                FIND YOUR HOST
                            </span>

                            <strong>
                                Explore our professionals
                            </strong>
                        </div>

                    </div>


                    <!-- HOST TYPE -->

                    <form
                        method="GET"
                        action="host.php"
                        class="host-type-form">

                        <div class="host-type-filter">

                            <span class="material-symbols-outlined filter-icon">
                                tune
                            </span>

                            <select
                                name="host_type"
                                id="hostTypeFilter"
                                aria-label="Filter by host type"
                                onchange="this.form.submit()">

                                <option value="">
                                    All Host Types
                                </option>

                                <?php foreach ($hostTypes as $type): ?>

                                    <option
                                        value="<?= e($type) ?>"
                                        <?= $hostType === $type ? "selected" : "" ?>>
                                        <?= e($type) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                    </form>

                </div>


                <!-- =====================================================
                 ACTIVE FILTER
            ====================================================== -->

                <?php if ($hostType !== ""): ?>

                    <div class="active-filter">

                        <span class="material-symbols-outlined">
                            filter_alt
                        </span>

                        <span>
                            Showing hosts under
                            <strong><?= e($hostType) ?></strong>
                        </span>

                        <a
                            href="host.php"
                            class="remove-filter"
                            aria-label="Remove host type filter"
                            title="Clear filter">

                            <span class="material-symbols-outlined">
                                close
                            </span>

                        </a>

                    </div>

                <?php endif; ?>


                <!-- =====================================================
                 RESULT HEADER
            ====================================================== -->

                <div class="hosts-result-header">

                    <div class="result-title">

                        <span class="result-number">
                            <?= $totalHosts ?>
                        </span>

                        <div class="result-text">

                            <strong>
                                <?= $totalHosts === 1 ? "Professional Host" : "Professional Hosts" ?>
                            </strong>

                            <span>
                                Available for your event
                            </span>

                        </div>

                    </div>


                    <div class="result-packages">

                        <div class="result-package-icon">

                            <span class="material-symbols-outlined">
                                inventory_2
                            </span>

                        </div>

                        <div>

                            <strong>
                                <?= $totalPackages ?>
                            </strong>

                            <span>
                                <?= $totalPackages === 1 ? "Package" : "Packages" ?>
                            </span>

                        </div>

                    </div>

                </div>


                <!-- =====================================================
                 HOST LIST
            ====================================================== -->

                <div class="hosts-list">

                    <?php if (!empty($hosts)): ?>

                        <?php foreach ($hosts as $host): ?>

                            <article class="host-block">

                                <!-- =================================================
                                 HOST PROFILE LEFT
                            ================================================== -->

                                <div class="host-profile">

                                    <div class="host-image-wrapper">

                                        <?php

                                        $hostImage = trim(
                                            (string)($host["image"] ?? "")
                                        );

                                        if ($hostImage === "") {

                                            $hostImage =
                                                "images/host-placeholder.jpg";
                                        } else {

                                            /*
                                        |--------------------------------------------------------------------------
                                        | Handle Image Paths
                                        |--------------------------------------------------------------------------
                                        */

                                            if (
                                                !preg_match(
                                                    '/^(https?:\/\/|\/)/i',
                                                    $hostImage
                                                )
                                            ) {

                                                if (
                                                    strpos($hostImage, "images/") !== 0 &&
                                                    strpos($hostImage, "../") !== 0
                                                ) {

                                                    $hostImage =
                                                        "images/" . $hostImage;
                                                }
                                            }
                                        }

                                        ?>

                                        <img
                                            src="<?= e($hostImage) ?>"
                                            alt="<?= e($host["host_name"]) ?>"
                                            class="host-main-image"
                                            loading="lazy"
                                            onerror="this.onerror=null;this.src='images/host-placeholder.jpg';">

                                        <div class="host-image-label">

                                            <span class="material-symbols-outlined">
                                                mic
                                            </span>

                                            Professional Host

                                        </div>

                                    </div>


                                    <div class="host-profile-content">

                                        <span class="host-label">
                                            PROFESSIONAL HOST
                                        </span>

                                        <h2>
                                            <?= e($host["host_name"]) ?>
                                        </h2>


                                        <?php if (!empty($host["host_type"])): ?>

                                            <div class="host-type-badge">

                                                <span class="material-symbols-outlined">
                                                    verified
                                                </span>

                                                <?= e($host["host_type"]) ?>

                                            </div>

                                        <?php endif; ?>


                                        <?php if (!empty($host["description"])): ?>

                                            <p class="host-description">
                                                <?= e($host["description"]) ?>
                                            </p>

                                        <?php else: ?>

                                            <p class="host-description">
                                                Professional event host ready
                                                to make your special occasion
                                                memorable.
                                            </p>

                                        <?php endif; ?>


                                        <!-- CONTACT -->

                                        <div class="host-contact">

                                            <?php if (!empty($host["email"])): ?>

                                                <div class="contact-item">

                                                    <span class="material-symbols-outlined">
                                                        mail
                                                    </span>

                                                    <span>
                                                        <?= e($host["email"]) ?>
                                                    </span>

                                                </div>

                                            <?php endif; ?>


                                            <?php if (!empty($host["phone"])): ?>

                                                <div class="contact-item">

                                                    <span class="material-symbols-outlined">
                                                        call
                                                    </span>

                                                    <span>
                                                        <?= e($host["phone"]) ?>
                                                    </span>

                                                </div>

                                            <?php endif; ?>

                                        </div>

                                    </div>

                                </div>


                                <!-- =================================================
                                 PACKAGES RIGHT
                            ================================================== -->

                                <div class="host-packages">

                                    <div class="packages-heading">

                                        <div>

                                            <span class="packages-eyebrow">
                                                AVAILABLE PACKAGES
                                            </span>

                                            <h3>
                                                Host Packages
                                            </h3>

                                        </div>


                                        <span class="packages-count">

                                            <span class="package-count-number">
                                                <?= count($host["packages"]) ?>
                                            </span>

                                            <?= count($host["packages"]) === 1
                                                ? "Package"
                                                : "Packages" ?>

                                        </span>

                                    </div>


                                    <?php if (!empty($host["packages"])): ?>

                                        <div class="packages-grid">

                                            <?php foreach ($host["packages"] as $package): ?>

                                                <?php

                                                /*
                                            |--------------------------------------------------------------------------
                                            | Process Inclusions
                                            |--------------------------------------------------------------------------
                                            */

                                                $rawInclusions =
                                                    $package["inclusions"] ?? [];

                                                $inclusions = [];


                                                /*
                                            |--------------------------------------------------------------------------
                                            | Already Array
                                            |--------------------------------------------------------------------------
                                            */

                                                if (is_array($rawInclusions)) {

                                                    $inclusions =
                                                        $rawInclusions;
                                                }


                                                /*
                                            |--------------------------------------------------------------------------
                                            | String
                                            |--------------------------------------------------------------------------
                                            */ elseif (is_string($rawInclusions)) {

                                                    $rawInclusions =
                                                        trim($rawInclusions);

                                                    if ($rawInclusions !== "") {

                                                        /*
                                                    |--------------------------------------------------------------------------
                                                    | Try JSON
                                                    |--------------------------------------------------------------------------
                                                    */

                                                        $decoded =
                                                            json_decode(
                                                                $rawInclusions,
                                                                true
                                                            );


                                                        if (
                                                            json_last_error() === JSON_ERROR_NONE &&
                                                            is_array($decoded)
                                                        ) {

                                                            $inclusions =
                                                                $decoded;
                                                        } else {

                                                            /*
                                                        |--------------------------------------------------------------------------
                                                        | FIXED:
                                                        | Split regular text by line
                                                        |--------------------------------------------------------------------------
                                                        */

                                                            $inclusions =
                                                                preg_split(
                                                                    "/\r\n|\r|\n/",
                                                                    $rawInclusions
                                                                );
                                                        }
                                                    }
                                                }


                                                /*
                                            |--------------------------------------------------------------------------
                                            | Safety
                                            |--------------------------------------------------------------------------
                                            */

                                                if (!is_array($inclusions)) {

                                                    $inclusions = [];
                                                }


                                                /*
                                            |--------------------------------------------------------------------------
                                            | Clean Inclusions
                                            |--------------------------------------------------------------------------
                                            */

                                                $cleanInclusions = [];

                                                foreach ($inclusions as $item) {

                                                    if (is_array($item)) {

                                                        if (
                                                            isset($item["name"])
                                                        ) {

                                                            $item =
                                                                $item["name"];
                                                        } elseif (
                                                            isset($item["title"])
                                                        ) {

                                                            $item =
                                                                $item["title"];
                                                        } else {

                                                            $item = "";
                                                        }
                                                    }

                                                    $item =
                                                        trim((string)$item);

                                                    if ($item !== "") {

                                                        $cleanInclusions[] =
                                                            $item;
                                                    }
                                                }

                                                $inclusions =
                                                    array_values(
                                                        $cleanInclusions
                                                    );

                                                ?>


                                                <!-- PACKAGE CARD -->

                                                <article class="package-card">

                                                    <div class="package-card-top">

                                                        <div class="package-icon">

                                                            <span class="material-symbols-outlined">
                                                                celebration
                                                            </span>

                                                        </div>


                                                        <div class="package-title-area">

                                                            <h4>
                                                                <?= e($package["package_name"]) ?>
                                                            </h4>

                                                            <span class="package-subtitle">
                                                                Professional host package
                                                            </span>

                                                        </div>

                                                    </div>


                                                    <!-- PRICE -->

                                                    <?php if (
                                                        $package["price"] !== null &&
                                                        $package["price"] !== ""
                                                    ): ?>

                                                        <div class="package-price">

                                                            <span class="price-label">
                                                                Starting at
                                                            </span>

                                                            <strong>

                                                                ₱<?= number_format(
                                                                        (float)$package["price"],
                                                                        2
                                                                    ) ?>

                                                            </strong>

                                                        </div>

                                                    <?php endif; ?>


                                                    <!-- INCLUSIONS -->

                                                    <?php if (!empty($inclusions)): ?>

                                                        <div class="package-inclusions-wrapper">

                                                            <span class="inclusions-label">
                                                                Includes
                                                            </span>

                                                            <ul class="package-inclusions">

                                                                <?php foreach (
                                                                    $inclusions
                                                                    as $inclusion
                                                                ): ?>

                                                                    <li>

                                                                        <span class="material-symbols-outlined">
                                                                            check_circle
                                                                        </span>

                                                                        <span>
                                                                            <?= e($inclusion) ?>
                                                                        </span>

                                                                    </li>

                                                                <?php endforeach; ?>

                                                            </ul>

                                                        </div>

                                                    <?php else: ?>

                                                        <div class="package-no-inclusions">

                                                            <span class="material-symbols-outlined">
                                                                info
                                                            </span>

                                                            Package details available
                                                            upon inquiry.

                                                        </div>

                                                    <?php endif; ?>


                                                    <!-- FOOTER -->

                                                    <div class="package-card-footer">

                                                        <a
                                                            href="book_host.php?package_id=<?= (int)$package["id"] ?>"
                                                            class="package-book-button">

                                                            <span>
                                                                Book Host
                                                            </span>

                                                            <span class="material-symbols-outlined">
                                                                arrow_forward
                                                            </span>

                                                        </a>

                                                    </div>

                                                </article>

                                            <?php endforeach; ?>

                                        </div>

                                    <?php else: ?>

                                        <div class="no-packages">

                                            <div class="no-packages-icon">

                                                <span class="material-symbols-outlined">
                                                    inventory_2
                                                </span>

                                            </div>

                                            <div>

                                                <strong>
                                                    No packages available
                                                </strong>

                                                <p>
                                                    This host currently has no
                                                    available packages.
                                                </p>

                                            </div>

                                        </div>

                                    <?php endif; ?>

                                </div>

                            </article>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <!-- EMPTY STATE -->

                        <div class="hosts-empty-state">

                            <div class="empty-icon">

                                <span class="material-symbols-outlined">
                                    groups
                                </span>

                            </div>

                            <h3>
                                No hosts found
                            </h3>

                            <p>
                                There are currently no hosts available
                                for this host type.
                            </p>

                            <a
                                href="host.php"
                                class="empty-button">

                                <span class="material-symbols-outlined">
                                    restart_alt
                                </span>

                                View All Hosts

                            </a>

                        </div>

                    <?php endif; ?>

                </div>

            </div>

        </section>


        <!-- =========================================================
         CTA
    ========================================================== -->

        <section class="hosts-cta">

            <div class="hosts-container">

                <div class="hosts-cta-card">

                    <div class="cta-icon">

                        <span class="material-symbols-outlined">
                            event_available
                        </span>

                    </div>


                    <div class="cta-content">

                        <span class="section-eyebrow">
                            PLAN YOUR HOST/SINGER
                        </span>

                        <h2>
                            Need the perfect host for your event?
                        </h2>

                        <p>
                            Choose from our professional hosts and
                            find the package that fits your occasion.
                        </p>

                    </div>


                    <a
                        href="host.php"
                        class="cta-button">

                        Explore Hosts

                        <span class="material-symbols-outlined">
                            arrow_forward
                        </span>

                    </a>

                </div>

            </div>

        </section>

    </main>


    <?php include "includes/footer.php"; ?>


    <!-- JAVASCRIPT -->

    <script src="js/header.js"></script>

    <script src="js/hosts.js?v=4"></script>

</body>

</html>
