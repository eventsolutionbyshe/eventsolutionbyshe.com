<?php

/* =====================================================
   ADMIN EVENT PACKAGES
   Event Solutions by S.H.E.
===================================================== */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* =====================================================
   ADMIN ACCESS
===================================================== */

if (
    empty($_SESSION["user_id"]) ||
    empty($_SESSION["role"]) ||
    strtolower(trim($_SESSION["role"])) !== "admin"
) {
    header("Location: ../auth/login.php");
    exit;
}


/* =====================================================
   DATABASE
===================================================== */

require_once __DIR__ . "/../config/database.php";

if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Database connection is not available.");
}

mysqli_report(MYSQLI_REPORT_OFF);


/* =====================================================
   PAGE SETTINGS
===================================================== */

$pageTitle = "Event Packages";
$pageSection = "Administrator";
$pageHeading = "Event Packages";

$pageAdminCss = "admin_eventpackages.css";
$pageAdminJs = "admin_eventpackages.js";


/* =====================================================
   ADMIN INFORMATION
===================================================== */

$adminName = $_SESSION["fullname"] ?? "Admin";


/* =====================================================
   MESSAGE
===================================================== */

$packageMessage = "";
$packageMessageType = "";


/* =====================================================
   IMAGE SETTINGS

   PHYSICAL LOCATION:
   /images/

   DATABASE:
   images/debut_package.jpeg
===================================================== */

$uploadDirectory = __DIR__ . "/../images/";

$allowedExtensions = [
    "jpg",
    "jpeg",
    "png",
    "webp"
];

$maxImageSize = 5 * 1024 * 1024;


/* =====================================================
   CREATE IMAGE DIRECTORY
===================================================== */

if (!is_dir($uploadDirectory)) {
    @mkdir($uploadDirectory, 0777, true);
}


/* =====================================================
   HELPER
   CREATE SAFE IMAGE FILE NAME
===================================================== */

function createPackageImageName(
    string $packageName,
    string $extension
): string {

    $fileName = strtolower(trim($packageName));

    $fileName = preg_replace(
        "/[^a-z0-9]+/",
        "_",
        $fileName
    );

    $fileName = trim($fileName, "_");

    if ($fileName === "") {
        $fileName = "package";
    }

    return $fileName .
        "_" .
        date("YmdHis") .
        "_" .
        bin2hex(random_bytes(3)) .
        "." .
        strtolower($extension);
}


/* =====================================================
   HELPER
   GET LOCAL IMAGE FILE PATH
===================================================== */

function getPackageImageFilePath(
    string $image
): string {

    global $uploadDirectory;

    $image = trim($image);

    if ($image === "") {
        return "";
    }

    /* External URL */
    if (preg_match('/^https?:\/\//i', $image)) {
        return "";
    }

    $image = str_replace("\\", "/", $image);
    $image = ltrim($image, "/");


    /* New format:
       images/example.jpg
    */
    if (strpos($image, "images/") === 0) {
        return __DIR__ . "/../" . $image;
    }


    /* Legacy uploads */
    if (strpos($image, "uploads/") === 0) {
        return __DIR__ . "/../" . $image;
    }


    /* Legacy filename only */
    return $uploadDirectory . basename($image);
}


/* =====================================================
   ADD EVENT PACKAGE
===================================================== */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["add_package"])
) {

    $packageName = trim(
        $_POST["package_name"] ?? ""
    );

    $packageEventTypeId = (int)(
        $_POST["event_type_id"] ?? 0
    );

    $packageInclusions = trim(
        $_POST["inclusions"] ?? ""
    );


    /* =================================================
       VALIDATION
    ================================================= */

    if (
        $packageName === "" ||
        $packageEventTypeId <= 0 ||
        $packageInclusions === ""
    ) {

        $packageMessage =
            "Please complete all required package fields.";

        $packageMessageType = "error";

    } else {

        $imageDbPath = "";


        /* =============================================
           IMAGE UPLOAD
        ============================================= */

        if (
            isset($_FILES["package_image"]) &&
            $_FILES["package_image"]["error"] === UPLOAD_ERR_OK
        ) {

            $originalName =
                $_FILES["package_image"]["name"];

            $tmpName =
                $_FILES["package_image"]["tmp_name"];

            $fileSize =
                (int)$_FILES["package_image"]["size"];

            $extension =
                strtolower(
                    pathinfo(
                        $originalName,
                        PATHINFO_EXTENSION
                    )
                );


            /* =========================================
               VALIDATE EXTENSION
            ========================================= */

            if (
                !in_array(
                    $extension,
                    $allowedExtensions,
                    true
                )
            ) {

                $packageMessage =
                    "Invalid image format. Please upload JPG, JPEG, PNG, or WEBP.";

                $packageMessageType = "error";

            } elseif ($fileSize > $maxImageSize) {

                $packageMessage =
                    "Image size must not exceed 5 MB.";

                $packageMessageType = "error";

            } elseif (
                @getimagesize($tmpName) === false
            ) {

                $packageMessage =
                    "The uploaded file is not a valid image.";

                $packageMessageType = "error";

            } else {

                $imageName =
                    createPackageImageName(
                        $packageName,
                        $extension
                    );

                $imageDestination =
                    $uploadDirectory . $imageName;

                $imageDbPath =
                    "images/" . $imageName;


                /* =====================================
                   SAVE IMAGE
                ===================================== */

                if (
                    !move_uploaded_file(
                        $tmpName,
                        $imageDestination
                    )
                ) {

                    $packageMessage =
                        "Unable to save the package image.";

                    $packageMessageType = "error";

                    $imageDbPath = "";
                }
            }
        }


        /* =============================================
           INSERT PACKAGE
        ============================================= */

        if ($packageMessageType !== "error") {

            $insertSql = "
                INSERT INTO event_packages
                (
                    event_type_id,
                    package_name,
                    inclusions,
                    image
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?
                )
            ";

            $insertStmt =
                $conn->prepare($insertSql);


            if ($insertStmt) {

                $insertStmt->bind_param(
                    "isss",
                    $packageEventTypeId,
                    $packageName,
                    $packageInclusions,
                    $imageDbPath
                );


                if ($insertStmt->execute()) {

                    header(
                        "Location: admin_eventpackages.php?success=added"
                    );

                    exit;

                } else {

                    if ($imageDbPath !== "") {

                        $uploadedImagePath =
                            getPackageImageFilePath(
                                $imageDbPath
                            );

                        if (
                            $uploadedImagePath !== "" &&
                            is_file($uploadedImagePath)
                        ) {
                            @unlink(
                                $uploadedImagePath
                            );
                        }
                    }

                    $packageMessage =
                        "Unable to save the event package.";

                    $packageMessageType = "error";
                }


                $insertStmt->close();

            } else {

                if ($imageDbPath !== "") {

                    $uploadedImagePath =
                        getPackageImageFilePath(
                            $imageDbPath
                        );

                    if (
                        $uploadedImagePath !== "" &&
                        is_file($uploadedImagePath)
                    ) {
                        @unlink(
                            $uploadedImagePath
                        );
                    }
                }

                $packageMessage =
                    "Unable to prepare the package query.";

                $packageMessageType = "error";
            }
        }
    }
}


/* =====================================================
   UPDATE EVENT PACKAGE
===================================================== */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["update_package"])
) {

    $packageId = (int)(
        $_POST["package_id"] ?? 0
    );

    $packageName = trim(
        $_POST["package_name"] ?? ""
    );

    $packageEventTypeId = (int)(
        $_POST["event_type_id"] ?? 0
    );

    $packageInclusions = trim(
        $_POST["inclusions"] ?? ""
    );


    /* =================================================
       VALIDATION
    ================================================= */

    if (
        $packageId <= 0 ||
        $packageName === "" ||
        $packageEventTypeId <= 0 ||
        $packageInclusions === ""
    ) {

        $packageMessage =
            "Please complete all required package fields.";

        $packageMessageType = "error";

    } else {

        /* =============================================
           GET CURRENT IMAGE
        ============================================= */

        $currentImage = "";

        $getImageSql = "
            SELECT image
            FROM event_packages
            WHERE id = ?
            LIMIT 1
        ";

        $getImageStmt =
            $conn->prepare($getImageSql);


        if ($getImageStmt) {

            $getImageStmt->bind_param(
                "i",
                $packageId
            );

            $getImageStmt->execute();

            $getImageResult =
                $getImageStmt->get_result();


            if ($getImageResult) {

                $imageRow =
                    $getImageResult->fetch_assoc();

                if ($imageRow) {

                    $currentImage =
                        $imageRow["image"] ?? "";
                }
            }

            $getImageStmt->close();
        }


        /* =============================================
           KEEP CURRENT IMAGE
        ============================================= */

        $newImage = $currentImage;
        $newUploadedImage = "";


        /* =============================================
           NEW IMAGE
        ============================================= */

        if (
            isset($_FILES["package_image"]) &&
            $_FILES["package_image"]["error"] === UPLOAD_ERR_OK
        ) {

            $originalName =
                $_FILES["package_image"]["name"];

            $tmpName =
                $_FILES["package_image"]["tmp_name"];

            $fileSize =
                (int)$_FILES["package_image"]["size"];

            $extension =
                strtolower(
                    pathinfo(
                        $originalName,
                        PATHINFO_EXTENSION
                    )
                );


            /* =========================================
               VALIDATE IMAGE
            ========================================= */

            if (
                !in_array(
                    $extension,
                    $allowedExtensions,
                    true
                )
            ) {

                $packageMessage =
                    "Invalid image format. Please upload JPG, JPEG, PNG, or WEBP.";

                $packageMessageType = "error";

            } elseif ($fileSize > $maxImageSize) {

                $packageMessage =
                    "Image size must not exceed 5 MB.";

                $packageMessageType = "error";

            } elseif (
                @getimagesize($tmpName) === false
            ) {

                $packageMessage =
                    "The uploaded file is not a valid image.";

                $packageMessageType = "error";

            } else {

                $newImageName =
                    createPackageImageName(
                        $packageName,
                        $extension
                    );

                $newImagePath =
                    $uploadDirectory . $newImageName;

                $newImageDbPath =
                    "images/" . $newImageName;


                if (
                    move_uploaded_file(
                        $tmpName,
                        $newImagePath
                    )
                ) {

                    $newImage =
                        $newImageDbPath;

                    $newUploadedImage =
                        $newImageDbPath;

                } else {

                    $newImage =
                        $currentImage;

                    $packageMessage =
                        "Unable to save the new package image.";

                    $packageMessageType = "error";
                }
            }
        }


        /* =============================================
           UPDATE PACKAGE
        ============================================= */

        if ($packageMessageType !== "error") {

            $updateSql = "
                UPDATE event_packages
                SET
                    event_type_id = ?,
                    package_name = ?,
                    inclusions = ?,
                    image = ?
                WHERE id = ?
            ";

            $updateStmt =
                $conn->prepare($updateSql);


            if ($updateStmt) {

                $updateStmt->bind_param(
                    "isssi",
                    $packageEventTypeId,
                    $packageName,
                    $packageInclusions,
                    $newImage,
                    $packageId
                );


                if ($updateStmt->execute()) {

                    /* =================================
                       DELETE OLD IMAGE
                    ================================= */

                    if (
                        $newUploadedImage !== "" &&
                        $currentImage !== "" &&
                        !preg_match(
                            '/^https?:\/\//i',
                            $currentImage
                        )
                    ) {

                        $oldImagePath =
                            getPackageImageFilePath(
                                $currentImage
                            );

                        if (
                            $oldImagePath !== "" &&
                            is_file($oldImagePath)
                        ) {
                            @unlink(
                                $oldImagePath
                            );
                        }
                    }


                    header(
                        "Location: admin_eventpackages.php?success=updated"
                    );

                    exit;

                } else {

                    if ($newUploadedImage !== "") {

                        $newUploadedImagePath =
                            getPackageImageFilePath(
                                $newUploadedImage
                            );

                        if (
                            $newUploadedImagePath !== "" &&
                            is_file($newUploadedImagePath)
                        ) {
                            @unlink(
                                $newUploadedImagePath
                            );
                        }
                    }


                    $packageMessage =
                        "Unable to update the event package.";

                    $packageMessageType = "error";
                }


                $updateStmt->close();

            } else {

                if ($newUploadedImage !== "") {

                    $newUploadedImagePath =
                        getPackageImageFilePath(
                            $newUploadedImage
                        );

                    if (
                        $newUploadedImagePath !== "" &&
                        is_file($newUploadedImagePath)
                    ) {
                        @unlink(
                            $newUploadedImagePath
                        );
                    }
                }


                $packageMessage =
                    "Unable to prepare the update query.";

                $packageMessageType = "error";
            }
        }
    }
}


/* =====================================================
   SUCCESS MESSAGE
===================================================== */

if (isset($_GET["success"])) {

    if ($_GET["success"] === "added") {

        $packageMessage =
            "Event package successfully added.";

        $packageMessageType = "success";

    } elseif ($_GET["success"] === "updated") {

        $packageMessage =
            "Event package successfully updated.";

        $packageMessageType = "success";
    }
}


/* =====================================================
   GET SEARCH
===================================================== */

$search = "";

if (isset($_GET["search"])) {
    $search = trim($_GET["search"]);
}


/* =====================================================
   GET CATEGORY FILTER
===================================================== */

$eventTypeId = 0;

if (
    isset($_GET["event_type_id"]) &&
    is_numeric($_GET["event_type_id"])
) {
    $eventTypeId =
        (int)$_GET["event_type_id"];
}


/* =====================================================
   GET EVENT TYPES
===================================================== */

$eventTypes = [];

$eventTypesSql = "
    SELECT
        id,
        name,
        description
    FROM event_types
    ORDER BY name ASC
";

$eventTypesResult =
    $conn->query($eventTypesSql);


if ($eventTypesResult) {

    while (
        $row =
        $eventTypesResult->fetch_assoc()
    ) {
        $eventTypes[] = $row;
    }
}


/* =====================================================
   GET TOTAL EVENT PACKAGES
===================================================== */

$totalPackages = 0;

$totalSql = "
    SELECT COUNT(*) AS total
    FROM event_packages
";

$totalResult =
    $conn->query($totalSql);


if ($totalResult) {

    $totalRow =
        $totalResult->fetch_assoc();

    $totalPackages =
        (int)$totalRow["total"];
}


/* =====================================================
   BUILD EVENT PACKAGE QUERY
===================================================== */

$sql = "
    SELECT
        ep.id,
        ep.event_type_id,
        ep.package_name,
        ep.inclusions,
        ep.image,
        ep.created_at,
        ep.updated_at,
        et.name AS event_type_name,
        et.description AS event_type_description
    FROM event_packages ep
    LEFT JOIN event_types et
        ON ep.event_type_id = et.id
    WHERE 1 = 1
";

$types = "";
$params = [];


/* =====================================================
   SEARCH FILTER
===================================================== */

if ($search !== "") {

    $sql .= "
        AND (
            ep.package_name LIKE ?
            OR ep.inclusions LIKE ?
            OR et.name LIKE ?
        )
    ";

    $searchValue =
        "%" . $search . "%";

    $types .= "sss";

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
}


/* =====================================================
   CATEGORY FILTER
===================================================== */

if ($eventTypeId > 0) {

    $sql .= "
        AND ep.event_type_id = ?
    ";

    $types .= "i";
    $params[] = $eventTypeId;
}


/* =====================================================
   ORDER
===================================================== */

$sql .= "
    ORDER BY
        ep.created_at DESC,
        ep.id DESC
";


/* =====================================================
   PREPARE
===================================================== */

$stmt =
    $conn->prepare($sql);


if (!$stmt) {
    die("Unable to load event packages.");
}


/* =====================================================
   BIND
===================================================== */

if (!empty($params)) {

    $stmt->bind_param(
        $types,
        ...$params
    );
}


/* =====================================================
   EXECUTE
===================================================== */

$stmt->execute();

$result =
    $stmt->get_result();

$eventPackages = [];


if ($result) {

    while (
        $row =
        $result->fetch_assoc()
    ) {
        $eventPackages[] = $row;
    }
}


$filteredPackageCount =
    count($eventPackages);


/* =====================================================
   PACKAGE IMAGE URL
===================================================== */

function getPackageImageUrl(
    $image
): string {

    $image =
        trim((string)$image);


    if ($image === "") {
        return "";
    }


    /* External image */
    if (
        preg_match(
            '/^https?:\/\//i',
            $image
        )
    ) {
        return $image;
    }


    $image =
        str_replace(
            "\\",
            "/",
            $image
        );

    $image =
        ltrim(
            $image,
            "/"
        );


    /* New database format */
    if (
        strpos(
            $image,
            "images/"
        ) === 0
    ) {
        return "../" . $image;
    }


    /* Legacy uploads */
    if (
        strpos(
            $image,
            "uploads/"
        ) === 0
    ) {
        return "../" . $image;
    }


    /* Filename only */
    return "../images/" .
        basename($image);
}


/* =====================================================
   PAGE CONTENT
===================================================== */

ob_start();

?>

<section class="admin-event-packages">

    <!-- =================================================
         PAGE HEADER
    ================================================== -->

    <div class="event-packages-header">

        <div class="event-packages-title">

            <div class="page-title-icon">
                <i class="fa-solid fa-box-open"></i>
            </div>

            <div>

                <span class="page-label">
                    PACKAGE MANAGEMENT
                </span>

                <h2>
                    Event Packages
                </h2>

                <p>
                    Manage and organize your available
                    event packages and inclusions.
                </p>

            </div>

        </div>


        <div class="event-packages-actions">

            <button
                type="button"
                class="btn-report"
                id="btnPackageReport"
            >
                <i class="fa-solid fa-file-pdf"></i>
                <span>Report</span>
            </button>


            <button
                type="button"
                class="btn-add-package"
                id="btnOpenAddPackage"
            >
                <i class="fa-solid fa-plus"></i>
                <span>Add Package</span>
            </button>

        </div>

    </div>


    <!-- =================================================
         STATISTICS
    ================================================== -->

    <div class="package-stat-grid">

        <div class="package-stat-card">

            <div class="package-stat-icon">
                <i class="fa-solid fa-box-open"></i>
            </div>

            <div class="package-stat-content">

                <span>
                    Total Packages
                </span>

                <strong>
                    <?= number_format($totalPackages) ?>
                </strong>

            </div>

        </div>


        <div class="package-stat-card">

            <div class="package-stat-icon">
                <i class="fa-solid fa-filter"></i>
            </div>

            <div class="package-stat-content">

                <span>
                    Displayed Packages
                </span>

                <strong>
                    <?= number_format($filteredPackageCount) ?>
                </strong>

            </div>

        </div>


        <div class="package-stat-card">

            <div class="package-stat-icon">
                <i class="fa-solid fa-layer-group"></i>
            </div>

            <div class="package-stat-content">

                <span>
                    Categories
                </span>

                <strong>
                    <?= number_format(count($eventTypes)) ?>
                </strong>

            </div>

        </div>

    </div>


    <!-- =================================================
         PACKAGE MANAGEMENT PANEL
    ================================================== -->

    <section class="event-packages-panel">

        <div class="packages-panel-header">

            <div>

                <span class="panel-label">
                    EVENT PACKAGE LIST
                </span>

                <h3>
                    Manage Packages
                </h3>

            </div>


            <div class="package-total">

                <i class="fa-solid fa-box"></i>

                <?= number_format($filteredPackageCount) ?>

                package<?= $filteredPackageCount !== 1 ? "s" : "" ?>

            </div>

        </div>


        <!-- FILTER BAR -->

        <form
            method="GET"
            class="package-filter-bar"
            id="packageFilterForm"
        >

            <div class="search-container">

                <i class="fa-solid fa-magnifying-glass"></i>

                <input
                    type="text"
                    name="search"
                    id="packageSearch"
                    placeholder="Search package, inclusion, or category..."
                    value="<?= htmlspecialchars(
                        $search,
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>"
                >

            </div>


            <div class="filter-container">

                <i class="fa-solid fa-layer-group"></i>

                <select
                    name="event_type_id"
                    id="eventTypeFilter"
                >

                    <option value="0">
                        All Categories
                    </option>

                    <?php foreach ($eventTypes as $eventType): ?>

                        <option
                            value="<?= (int)$eventType["id"] ?>"
                            <?= $eventTypeId === (int)$eventType["id"]
                                ? "selected"
                                : ""
                            ?>
                        >
                            <?= htmlspecialchars(
                                $eventType["name"],
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <button
                type="submit"
                class="btn-search"
            >
                <i class="fa-solid fa-magnifying-glass"></i>
                Search
            </button>


            <?php if (
                $search !== "" ||
                $eventTypeId > 0
            ): ?>

                <a
                    href="admin_eventpackages.php"
                    class="btn-reset"
                >
                    <i class="fa-solid fa-rotate-left"></i>
                    Reset
                </a>

            <?php endif; ?>

        </form>


        <!-- =================================================
             DATA GRID
        ================================================== -->

        <div class="event-packages-table-wrapper">

            <table
                class="event-packages-table"
                id="eventPackagesTable"
            >

                <thead>

                    <tr>

                        <th>
                            Package
                        </th>

                        <th>
                            Category
                        </th>

                        <th>
                            Inclusions
                        </th>

                        <th>
                            Created
                        </th>

                        <th class="action-column">
                            Action
                        </th>

                    </tr>

                </thead>


                <tbody>

                    <?php if (!empty($eventPackages)): ?>

                        <?php foreach ($eventPackages as $package): ?>

                            <?php
                            $packageImage =
                                getPackageImageUrl(
                                    $package["image"] ?? ""
                                );
                            ?>

                            <tr>

                                <td>

                                    <div class="package-cell">

                                        <div class="package-image">

                                            <?php if ($packageImage !== ""): ?>

                                                <img
                                                    src="<?= htmlspecialchars(
                                                        $packageImage,
                                                        ENT_QUOTES,
                                                        "UTF-8"
                                                    ) ?>"
                                                    alt="<?= htmlspecialchars(
                                                        $package["package_name"],
                                                        ENT_QUOTES,
                                                        "UTF-8"
                                                    ) ?>"
                                                    onerror="
                                                        this.style.display='none';
                                                        this.nextElementSibling.style.display='flex';
                                                    "
                                                >

                                                <div
                                                    class="package-image-fallback"
                                                    style="display:none;"
                                                >
                                                    <i class="fa-solid fa-box-open"></i>
                                                </div>

                                            <?php else: ?>

                                                <div class="package-image-fallback">

                                                    <i class="fa-solid fa-box-open"></i>

                                                </div>

                                            <?php endif; ?>

                                        </div>


                                        <div class="package-information">

                                            <strong>
                                                <?= htmlspecialchars(
                                                    $package["package_name"]
                                                    ?? "Unnamed Package",
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                ) ?>
                                            </strong>

                                            <span>
                                                Package ID:
                                                #<?= (int)$package["id"] ?>
                                            </span>

                                        </div>

                                    </div>

                                </td>


                                <td>

                                    <span class="category-badge">

                                        <i class="fa-solid fa-tag"></i>

                                        <?= htmlspecialchars(
                                            $package["event_type_name"]
                                            ?? "Uncategorized",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>

                                    </span>

                                </td>


                                <td>

                                    <div class="package-inclusions">

                                        <?= htmlspecialchars(
                                            $package["inclusions"]
                                            ?? "No inclusions specified.",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>

                                    </div>

                                </td>


                                <td>

                                    <div class="date-cell">

                                        <i class="fa-regular fa-calendar"></i>

                                        <span>

                                            <?= !empty(
                                                $package["created_at"]
                                            )
                                                ? date(
                                                    "M d, Y",
                                                    strtotime(
                                                        $package["created_at"]
                                                    )
                                                )
                                                : "N/A"
                                            ?>

                                        </span>

                                    </div>

                                </td>


                                <td class="action-column">

                                    <button
                                        type="button"
                                        class="btn-edit-package"
                                        title="Edit Package"

                                        data-id="<?= (int)$package["id"] ?>"

                                        data-name="<?= htmlspecialchars(
                                            $package["package_name"] ?? "",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>"

                                        data-event-type="<?= (int)$package["event_type_id"] ?>"

                                        data-inclusions="<?= htmlspecialchars(
                                            $package["inclusions"] ?? "",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>"

                                        data-image="<?= htmlspecialchars(
                                            $package["image"] ?? "",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>"
                                    >

                                        <i class="fa-solid fa-pen-to-square"></i>

                                        <span>
                                            Edit
                                        </span>

                                    </button>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>

                            <td colspan="5">

                                <div class="empty-packages">

                                    <div class="empty-icon">
                                        <i class="fa-solid fa-box-open"></i>
                                    </div>

                                    <strong>
                                        No Event Packages Found
                                    </strong>

                                    <span>
                                        No event packages match
                                        your current search or filter.
                                    </span>

                                    <button
                                        type="button"
                                        class="empty-add-button"
                                        id="btnOpenAddPackageEmpty"
                                    >
                                        <i class="fa-solid fa-plus"></i>
                                        Add Package
                                    </button>

                                </div>

                            </td>

                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

</section>


<!-- =====================================================
     ADD PACKAGE MODAL
===================================================== -->

<div
    class="package-modal"
    id="addPackageModal"
    aria-hidden="true"
>

    <div class="package-modal-overlay"></div>


    <div
        class="package-modal-content"
        role="dialog"
        aria-modal="true"
        aria-labelledby="addPackageModalTitle"
    >

        <div class="package-modal-header">

            <div class="package-modal-title">

                <div class="package-modal-icon">
                    <i class="fa-solid fa-box-open"></i>
                </div>

                <div>

                    <span class="package-modal-label">
                        PACKAGE MANAGEMENT
                    </span>

                    <h3 id="addPackageModalTitle">
                        Add Event Package
                    </h3>

                    <p>
                        Create a new event package and its inclusions.
                    </p>

                </div>

            </div>


            <button
                type="button"
                class="package-modal-close"
                data-close-package-modal
            >
                <i class="fa-solid fa-xmark"></i>
            </button>

        </div>


        <form
            method="POST"
            enctype="multipart/form-data"
            class="package-modal-form"
            id="addPackageForm"
        >

            <input
                type="hidden"
                name="add_package"
                value="1"
            >


            <!-- PACKAGE NAME -->

            <div class="package-form-group">

                <label for="addPackageName">

                    Package Name
                    <span>*</span>

                </label>


                <div class="package-input-wrapper">

                    <i class="fa-solid fa-box"></i>

                    <input
                        type="text"
                        name="package_name"
                        id="addPackageName"
                        placeholder="Enter package name"
                        maxlength="150"
                        required
                    >

                </div>

            </div>


            <!-- CATEGORY -->

            <div class="package-form-group">

                <label for="addPackageCategory">

                    Event Category
                    <span>*</span>

                </label>


                <div class="package-input-wrapper">

                    <i class="fa-solid fa-layer-group"></i>

                    <select
                        name="event_type_id"
                        id="addPackageCategory"
                        required
                    >

                        <option value="">
                            Select event category
                        </option>

                        <?php foreach ($eventTypes as $eventType): ?>

                            <option
                                value="<?= (int)$eventType["id"] ?>"
                            >
                                <?= htmlspecialchars(
                                    $eventType["name"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

            </div>


            <!-- INCLUSIONS -->

            <div class="package-form-group">

                <label for="addPackageInclusions">

                    Package Inclusions
                    <span>*</span>

                </label>


                <div class="package-textarea-wrapper">

                    <i class="fa-solid fa-list-check"></i>

                    <textarea
                        name="inclusions"
                        id="addPackageInclusions"
                        rows="5"
                        placeholder="Enter package inclusions..."
                        required
                    ></textarea>

                </div>


                <small>
                    List the services, items, or features included
                    in this package.
                </small>

            </div>


            <!-- IMAGE -->

            <div class="package-form-group package-image-form-group">

                <label for="addPackageImage">
                    Package Image
                </label>


                <div
                    class="package-picturebox"
                    id="addPackagePictureBox"
                >

                    <div
                        class="package-picturebox-placeholder"
                        id="addPackagePicturePlaceholder"
                    >

                        <i class="fa-solid fa-image"></i>

                        <span>
                            No image selected
                        </span>

                        <small>
                            Choose an image below
                        </small>

                    </div>


                    <img
                        src=""
                        alt="Package Image Preview"
                        id="addPackageImagePreview"
                        class="package-picturebox-image"
                    >

                </div>


                <div class="package-file-wrapper">

                    <input
                        type="file"
                        name="package_image"
                        id="addPackageImage"
                        accept="image/jpeg,image/png,image/webp"
                    >


                    <label
                        for="addPackageImage"
                        class="package-file-label"
                    >

                        <i class="fa-solid fa-cloud-arrow-up"></i>

                        <span id="addPackageFileText">
                            Choose package image
                        </span>

                    </label>

                </div>


                <small>
                    JPG, PNG, or WEBP. Maximum 5 MB.
                </small>

            </div>


            <!-- ACTIONS -->

            <div class="package-modal-actions">

                <button
                    type="button"
                    class="package-modal-cancel"
                    data-close-package-modal
                >
                    Cancel
                </button>


                <button
                    type="submit"
                    class="package-modal-save"
                >

                    <i class="fa-solid fa-floppy-disk"></i>

                    Save Package

                </button>

            </div>

        </form>

    </div>

</div>


<!-- =====================================================
     EDIT PACKAGE MODAL
===================================================== -->

<div
    class="package-modal"
    id="editPackageModal"
    aria-hidden="true"
>

    <div class="package-modal-overlay"></div>


    <div
        class="package-modal-content"
        role="dialog"
        aria-modal="true"
        aria-labelledby="editPackageModalTitle"
    >

        <div class="package-modal-header">

            <div class="package-modal-title">

                <div class="package-modal-icon">
                    <i class="fa-solid fa-pen-to-square"></i>
                </div>

                <div>

                    <span class="package-modal-label">
                        PACKAGE MANAGEMENT
                    </span>

                    <h3 id="editPackageModalTitle">
                        Edit Event Package
                    </h3>

                    <p>
                        Update the selected event package.
                    </p>

                </div>

            </div>


            <button
                type="button"
                class="package-modal-close"
                data-close-package-modal
            >
                <i class="fa-solid fa-xmark"></i>
            </button>

        </div>


        <form
            method="POST"
            enctype="multipart/form-data"
            class="package-modal-form"
            id="editPackageForm"
        >

            <input
                type="hidden"
                name="update_package"
                value="1"
            >

            <input
                type="hidden"
                name="package_id"
                id="editPackageId"
                value=""
            >


            <!-- PACKAGE NAME -->

            <div class="package-form-group">

                <label for="editPackageName">

                    Package Name
                    <span>*</span>

                </label>


                <div class="package-input-wrapper">

                    <i class="fa-solid fa-box"></i>

                    <input
                        type="text"
                        name="package_name"
                        id="editPackageName"
                        placeholder="Enter package name"
                        maxlength="150"
                        required
                    >

                </div>

            </div>


            <!-- CATEGORY -->

            <div class="package-form-group">

                <label for="editPackageCategory">

                    Event Category
                    <span>*</span>

                </label>


                <div class="package-input-wrapper">

                    <i class="fa-solid fa-layer-group"></i>

                    <select
                        name="event_type_id"
                        id="editPackageCategory"
                        required
                    >

                        <option value="">
                            Select event category
                        </option>

                        <?php foreach ($eventTypes as $eventType): ?>

                            <option
                                value="<?= (int)$eventType["id"] ?>"
                            >
                                <?= htmlspecialchars(
                                    $eventType["name"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

            </div>


            <!-- INCLUSIONS -->

            <div class="package-form-group">

                <label for="editPackageInclusions">

                    Package Inclusions
                    <span>*</span>

                </label>


                <div class="package-textarea-wrapper">

                    <i class="fa-solid fa-list-check"></i>

                    <textarea
                        name="inclusions"
                        id="editPackageInclusions"
                        rows="5"
                        placeholder="Enter package inclusions..."
                        required
                    ></textarea>

                </div>


                <small>
                    Update the services, items, or features
                    included in this package.
                </small>

            </div>


            <!-- CURRENT / NEW IMAGE -->

            <div class="package-form-group package-image-form-group">

                <label for="editPackageImage">
                    Package Image
                </label>


                <div
                    class="package-picturebox"
                    id="editPackagePictureBox"
                >

                    <div
                        class="package-picturebox-placeholder"
                        id="editPackagePicturePlaceholder"
                    >

                        <i class="fa-solid fa-image"></i>

                        <span>
                            No image available
                        </span>

                        <small>
                            Choose a new image below
                        </small>

                    </div>


                    <img
                        src=""
                        alt="Current Package Image"
                        id="editPackageImagePreview"
                        class="package-picturebox-image"
                    >

                </div>


                <div class="package-file-wrapper">

                    <input
                        type="file"
                        name="package_image"
                        id="editPackageImage"
                        accept="image/jpeg,image/png,image/webp"
                    >


                    <label
                        for="editPackageImage"
                        class="package-file-label"
                    >

                        <i class="fa-solid fa-cloud-arrow-up"></i>

                        <span id="editPackageFileText">
                            Choose new package image
                        </span>

                    </label>

                </div>


                <small>
                    Leave empty to keep the current image.
                    Maximum 5 MB.
                </small>

            </div>


            <!-- ACTIONS -->

            <div class="package-modal-actions">

                <button
                    type="button"
                    class="package-modal-cancel"
                    data-close-package-modal
                >
                    Cancel
                </button>


                <button
                    type="submit"
                    class="package-modal-save"
                >

                    <i class="fa-solid fa-floppy-disk"></i>

                    Update Package

                </button>

            </div>

        </form>

    </div>

</div>


<!-- =====================================================
     PDF REPORT AREA
===================================================== -->

<div
    id="packageReport"
    class="print-report"
>

    <div class="report-header">

        <div>

            <h1>
                Event Solutions by S.H.E.
            </h1>

            <h2>
                Event Packages Report
            </h2>

        </div>


        <div class="report-date">

            Generated:
            <?= date("F d, Y h:i A") ?>

        </div>

    </div>


    <table class="report-table">

        <thead>

            <tr>

                <th>ID</th>
                <th>Package Name</th>
                <th>Category</th>
                <th>Inclusions</th>
                <th>Created</th>

            </tr>

        </thead>


        <tbody>

            <?php foreach ($eventPackages as $package): ?>

                <tr>

                    <td>
                        #<?= (int)$package["id"] ?>
                    </td>

                    <td>
                        <?= htmlspecialchars(
                            $package["package_name"],
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars(
                            $package["event_type_name"]
                            ?? "Uncategorized",
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars(
                            $package["inclusions"]
                            ?? "",
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>
                    </td>

                    <td>

                        <?= !empty(
                            $package["created_at"]
                        )
                            ? date(
                                "M d, Y",
                                strtotime(
                                    $package["created_at"]
                                )
                            )
                            : "N/A"
                        ?>

                    </td>

                </tr>

            <?php endforeach; ?>

        </tbody>

    </table>

</div>


<script>

window.adminEventPackagesData =
<?= json_encode(
    [
        "totalPackages" =>
            $totalPackages,

        "displayedPackages" =>
            $filteredPackageCount,

        "selectedCategory" =>
            $eventTypeId,

        "message" =>
            $packageMessage,

        "messageType" =>
            $packageMessageType
    ],
    JSON_UNESCAPED_SLASHES |
    JSON_UNESCAPED_UNICODE |
    JSON_HEX_TAG |
    JSON_HEX_AMP |
    JSON_HEX_APOS |
    JSON_HEX_QUOT
) ?>;

</script>


<?php

/* =====================================================
   GET PAGE CONTENT
===================================================== */

$pageContent =
    ob_get_clean();


/* =====================================================
   LOAD ADMIN HEADER
===================================================== */

require_once __DIR__ .
    "/admin_include/admin_header.php";

?>