<?php

/* =====================================================
   ADMIN VENUES
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

$pageTitle = "Venues";
$pageSection = "Administrator";
$pageHeading = "Venues";

$pageAdminCss = "admin_venues.css";
$pageAdminJs = "admin_venues.js";


/* =====================================================
   ADMIN INFORMATION
===================================================== */

$adminName = $_SESSION["fullname"] ?? "Admin";


/* =====================================================
   MESSAGE
===================================================== */

$venueMessage = "";
$venueMessageType = "";


/* =====================================================
   IMAGE SETTINGS

   PHYSICAL LOCATION:
   /images/

   DATABASE:
   images/venue_name.jpeg
===================================================== */

$uploadDirectory = __DIR__ . "/../images/";

$allowedExtensions = [
    "jpg",
    "jpeg",
    "png",
    "webp"
];

$allowedMimeTypes = [
    "image/jpeg",
    "image/png",
    "image/webp"
];

$maxImageSize = 5 * 1024 * 1024;


/* =====================================================
   CREATE IMAGE DIRECTORY
===================================================== */

if (!is_dir($uploadDirectory)) {

    if (!@mkdir($uploadDirectory, 0777, true)) {
        die("Unable to create image directory.");
    }
}


/* =====================================================
   HELPER
   CREATE SAFE VENUE IMAGE FILE NAME
===================================================== */

function createVenueImageName(
    string $venueName,
    string $extension
): string {

    $fileName = strtolower(
        trim($venueName)
    );

    $fileName = preg_replace(
        "/[^a-z0-9]+/",
        "_",
        $fileName
    );

    $fileName = trim(
        $fileName,
        "_"
    );

    if ($fileName === "") {
        $fileName = "venue";
    }


    try {

        $random =
            bin2hex(
                random_bytes(3)
            );

    } catch (Exception $e) {

        $random =
            uniqid();
    }


    return
        $fileName .
        "_" .
        date("YmdHis") .
        "_" .
        $random .
        "." .
        strtolower($extension);
}


/* =====================================================
   HELPER
   GET LOCAL IMAGE FILE PATH
===================================================== */

function getVenueImageFilePath(
    string $image
): string {

    global $uploadDirectory;

    $image = trim($image);

    if ($image === "") {
        return "";
    }


    /* External URL */

    if (
        preg_match(
            '/^https?:\/\//i',
            $image
        )
    ) {
        return "";
    }


    $image = str_replace(
        "\\",
        "/",
        $image
    );

    $image = ltrim(
        $image,
        "/"
    );


    /* New format */

    if (
        strpos(
            $image,
            "images/"
        ) === 0
    ) {

        return
            __DIR__ .
            "/../" .
            $image;
    }


    /* Legacy uploads */

    if (
        strpos(
            $image,
            "uploads/"
        ) === 0
    ) {

        return
            __DIR__ .
            "/../" .
            $image;
    }


    /* Filename only */

    return
        $uploadDirectory .
        basename($image);
}


/* =====================================================
   HELPER
   DELETE LOCAL VENUE IMAGE
===================================================== */

function deleteVenueImage(
    string $image
): void {

    if ($image === "") {
        return;
    }


    if (
        preg_match(
            '/^https?:\/\//i',
            $image
        )
    ) {
        return;
    }


    $imagePath =
        getVenueImageFilePath(
            $image
        );


    if (
        $imagePath !== "" &&
        is_file($imagePath)
    ) {

        @unlink(
            $imagePath
        );
    }
}


/* =====================================================
   ADD VENUE
===================================================== */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["add_venue"])
) {

    $venueName = trim(
        $_POST["venue_name"] ?? ""
    );

    $venueLocation = trim(
        $_POST["location"] ?? ""
    );

    $venueType = trim(
        $_POST["venue_type"] ?? ""
    );

    $venueDescription = trim(
        $_POST["description"] ?? ""
    );

    $venueStatus = strtolower(
        trim(
            $_POST["status"] ?? "available"
        )
    );


    /* =================================================
       VALIDATION
    ================================================= */

    if (
        $venueName === "" ||
        $venueLocation === "" ||
        $venueType === ""
    ) {

        $venueMessage =
            "Please complete all required venue fields.";

        $venueMessageType =
            "error";

    } elseif (
        !in_array(
            $venueStatus,
            [
                "available",
                "unavailable"
            ],
            true
        )
    ) {

        $venueMessage =
            "Invalid venue status.";

        $venueMessageType =
            "error";

    } else {

        $imageDbPath = "";


        /* =============================================
           IMAGE UPLOAD
        ============================================= */

        if (
            isset($_FILES["venue_image"]) &&
            $_FILES["venue_image"]["error"] !==
            UPLOAD_ERR_NO_FILE
        ) {

            if (
                $_FILES["venue_image"]["error"] !==
                UPLOAD_ERR_OK
            ) {

                $venueMessage =
                    "There was an error uploading the venue image.";

                $venueMessageType =
                    "error";

            } else {

                $originalName =
                    $_FILES["venue_image"]["name"];

                $tmpName =
                    $_FILES["venue_image"]["tmp_name"];

                $fileSize =
                    (int)$_FILES["venue_image"]["size"];


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

                    $venueMessage =
                        "Invalid image format. Please upload JPG, JPEG, PNG, or WEBP.";

                    $venueMessageType =
                        "error";

                } elseif (
                    $fileSize <= 0
                ) {

                    $venueMessage =
                        "The selected image file is empty.";

                    $venueMessageType =
                        "error";

                } elseif (
                    $fileSize > $maxImageSize
                ) {

                    $venueMessage =
                        "Image size must not exceed 5 MB.";

                    $venueMessageType =
                        "error";

                } else {

                    $imageInfo =
                        @getimagesize(
                            $tmpName
                        );


                    if ($imageInfo === false) {

                        $venueMessage =
                            "The uploaded file is not a valid image.";

                        $venueMessageType =
                            "error";

                    } else {

                        $imageMime =
                            $imageInfo["mime"] ?? "";


                        if (
                            !in_array(
                                $imageMime,
                                $allowedMimeTypes,
                                true
                            )
                        ) {

                            $venueMessage =
                                "Invalid image type.";

                            $venueMessageType =
                                "error";

                        } else {

                            $imageName =
                                createVenueImageName(
                                    $venueName,
                                    $extension
                                );


                            $imageDestination =
                                $uploadDirectory .
                                $imageName;


                            $imageDbPath =
                                "images/" .
                                $imageName;


                            /* =================================
                               SAVE IMAGE
                            ================================= */

                            if (
                                !move_uploaded_file(
                                    $tmpName,
                                    $imageDestination
                                )
                            ) {

                                $venueMessage =
                                    "Unable to save the venue image.";

                                $venueMessageType =
                                    "error";

                                $imageDbPath =
                                    "";
                            }
                        }
                    }
                }
            }
        }


        /* =============================================
           INSERT VENUE
        ============================================= */

        if ($venueMessageType !== "error") {

            $insertSql = "

                INSERT INTO venues
                (
                    venue_name,
                    location,
                    venue_type,
                    description,
                    image,
                    status
                )

                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?
                )

            ";


            $insertStmt =
                $conn->prepare(
                    $insertSql
                );


            if ($insertStmt) {

                $insertStmt->bind_param(
                    "ssssss",
                    $venueName,
                    $venueLocation,
                    $venueType,
                    $venueDescription,
                    $imageDbPath,
                    $venueStatus
                );


                if (
                    $insertStmt->execute()
                ) {

                    $insertStmt->close();


                    header(
                        "Location: admin_venues.php?success=added"
                    );

                    exit;

                } else {

                    if (
                        $imageDbPath !== ""
                    ) {

                        deleteVenueImage(
                            $imageDbPath
                        );
                    }


                    $venueMessage =
                        "Unable to save the venue.";

                    $venueMessageType =
                        "error";
                }


                $insertStmt->close();

            } else {

                if (
                    $imageDbPath !== ""
                ) {

                    deleteVenueImage(
                        $imageDbPath
                    );
                }


                $venueMessage =
                    "Unable to prepare the venue query.";

                $venueMessageType =
                    "error";
            }
        }
    }
}


/* =====================================================
   UPDATE VENUE
===================================================== */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["update_venue"])
) {

    $venueId =
        (int)(
            $_POST["venue_id"] ?? 0
        );

    $venueName = trim(
        $_POST["venue_name"] ?? ""
    );

    $venueLocation = trim(
        $_POST["location"] ?? ""
    );

    $venueType = trim(
        $_POST["venue_type"] ?? ""
    );

    $venueDescription = trim(
        $_POST["description"] ?? ""
    );

    $venueStatus = strtolower(
        trim(
            $_POST["status"] ?? "available"
        )
    );


    /* =================================================
       VALIDATION
    ================================================= */

    if (
        $venueId <= 0 ||
        $venueName === "" ||
        $venueLocation === "" ||
        $venueType === ""
    ) {

        $venueMessage =
            "Please complete all required venue fields.";

        $venueMessageType =
            "error";

    } elseif (
        !in_array(
            $venueStatus,
            [
                "available",
                "unavailable"
            ],
            true
        )
    ) {

        $venueMessage =
            "Invalid venue status.";

        $venueMessageType =
            "error";

    } else {


        /* =============================================
           GET CURRENT IMAGE
        ============================================= */

        $currentImage = "";

        $getImageSql = "

            SELECT image

            FROM venues

            WHERE id = ?

            LIMIT 1

        ";


        $getImageStmt =
            $conn->prepare(
                $getImageSql
            );


        if ($getImageStmt) {

            $getImageStmt->bind_param(
                "i",
                $venueId
            );


            if (
                $getImageStmt->execute()
            ) {

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
            }


            $getImageStmt->close();
        }


        /* =============================================
           CHECK IF VENUE EXISTS
        ============================================= */

        if ($currentImage === "") {

            $checkVenueSql = "

                SELECT id

                FROM venues

                WHERE id = ?

                LIMIT 1

            ";


            $checkVenueStmt =
                $conn->prepare(
                    $checkVenueSql
                );


            if ($checkVenueStmt) {

                $checkVenueStmt->bind_param(
                    "i",
                    $venueId
                );

                $checkVenueStmt->execute();

                $checkVenueResult =
                    $checkVenueStmt->get_result();


                if (
                    !$checkVenueResult ||
                    $checkVenueResult->num_rows === 0
                ) {

                    $venueMessage =
                        "The selected venue does not exist.";

                    $venueMessageType =
                        "error";
                }


                $checkVenueStmt->close();
            }
        }


        /* =============================================
           KEEP CURRENT IMAGE
        ============================================= */

        if ($venueMessageType !== "error") {

            $newImage =
                $currentImage;

            $newUploadedImage =
                "";


            /* =============================================
               NEW IMAGE
            ============================================= */

            if (
                isset($_FILES["venue_image"]) &&
                $_FILES["venue_image"]["error"] !==
                UPLOAD_ERR_NO_FILE
            ) {

                if (
                    $_FILES["venue_image"]["error"] !==
                    UPLOAD_ERR_OK
                ) {

                    $venueMessage =
                        "There was an error uploading the new venue image.";

                    $venueMessageType =
                        "error";

                } else {

                    $originalName =
                        $_FILES["venue_image"]["name"];

                    $tmpName =
                        $_FILES["venue_image"]["tmp_name"];

                    $fileSize =
                        (int)$_FILES["venue_image"]["size"];


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

                        $venueMessage =
                            "Invalid image format. Please upload JPG, JPEG, PNG, or WEBP.";

                        $venueMessageType =
                            "error";

                    } elseif (
                        $fileSize <= 0
                    ) {

                        $venueMessage =
                            "The selected image file is empty.";

                        $venueMessageType =
                            "error";

                    } elseif (
                        $fileSize > $maxImageSize
                    ) {

                        $venueMessage =
                            "Image size must not exceed 5 MB.";

                        $venueMessageType =
                            "error";

                    } else {

                        $imageInfo =
                            @getimagesize(
                                $tmpName
                            );


                        if ($imageInfo === false) {

                            $venueMessage =
                                "The uploaded file is not a valid image.";

                            $venueMessageType =
                                "error";

                        } else {

                            $imageMime =
                                $imageInfo["mime"] ?? "";


                            if (
                                !in_array(
                                    $imageMime,
                                    $allowedMimeTypes,
                                    true
                                )
                            ) {

                                $venueMessage =
                                    "Invalid image type.";

                                $venueMessageType =
                                    "error";

                            } else {

                                $newImageName =
                                    createVenueImageName(
                                        $venueName,
                                        $extension
                                    );


                                $newImagePath =
                                    $uploadDirectory .
                                    $newImageName;


                                $newImageDbPath =
                                    "images/" .
                                    $newImageName;


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

                                    $venueMessage =
                                        "Unable to save the new venue image.";

                                    $venueMessageType =
                                        "error";
                                }
                            }
                        }
                    }
                }
            }


            /* =============================================
               UPDATE VENUE
            ============================================= */

            if (
                $venueMessageType !== "error"
            ) {

                $updateSql = "

                    UPDATE venues

                    SET
                        venue_name = ?,
                        location = ?,
                        venue_type = ?,
                        description = ?,
                        image = ?,
                        status = ?

                    WHERE id = ?

                ";


                $updateStmt =
                    $conn->prepare(
                        $updateSql
                    );


                if ($updateStmt) {

                    $updateStmt->bind_param(
                        "ssssssi",
                        $venueName,
                        $venueLocation,
                        $venueType,
                        $venueDescription,
                        $newImage,
                        $venueStatus,
                        $venueId
                    );


                    if (
                        $updateStmt->execute()
                    ) {

                        $updateStmt->close();


                        /* =================================
                           DELETE OLD IMAGE
                        ================================= */

                        if (
                            $newUploadedImage !== "" &&
                            $currentImage !== "" &&
                            $currentImage !== $newUploadedImage
                        ) {

                            deleteVenueImage(
                                $currentImage
                            );
                        }


                        header(
                            "Location: admin_venues.php?success=updated"
                        );

                        exit;

                    } else {


                        /* Delete newly uploaded image */

                        if (
                            $newUploadedImage !== ""
                        ) {

                            deleteVenueImage(
                                $newUploadedImage
                            );
                        }


                        $venueMessage =
                            "Unable to update the venue.";

                        $venueMessageType =
                            "error";
                    }


                    $updateStmt->close();

                } else {


                    if (
                        $newUploadedImage !== ""
                    ) {

                        deleteVenueImage(
                            $newUploadedImage
                        );
                    }


                    $venueMessage =
                        "Unable to prepare the update query.";

                    $venueMessageType =
                        "error";
                }
            }
        }
    }
}


/* =====================================================
   CHANGE VENUE STATUS
===================================================== */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["change_venue_status"])
) {

    $venueId =
        (int)(
            $_POST["venue_id"] ?? 0
        );

    $newStatus =
        strtolower(
            trim(
                $_POST["new_status"] ?? ""
            )
        );


    /* =================================================
       VALIDATION
    ================================================= */

    if (
        $venueId <= 0 ||
        !in_array(
            $newStatus,
            [
                "available",
                "unavailable"
            ],
            true
        )
    ) {

        $venueMessage =
            "Invalid venue status request.";

        $venueMessageType =
            "error";

    } else {


        /* =============================================
           UPDATE STATUS
        ============================================= */

        $statusSql = "

            UPDATE venues

            SET status = ?

            WHERE id = ?

            LIMIT 1

        ";


        $statusStmt =
            $conn->prepare(
                $statusSql
            );


        if ($statusStmt) {

            $statusStmt->bind_param(
                "si",
                $newStatus,
                $venueId
            );


            if (
                $statusStmt->execute()
            ) {

                if (
                    $statusStmt->affected_rows > 0
                ) {

                    $statusStmt->close();


                    header(
                        "Location: admin_venues.php?success=status_changed"
                    );

                    exit;

                } else {

                    $statusStmt->close();


                    header(
                        "Location: admin_venues.php?success=status_changed"
                    );

                    exit;
                }

            } else {

                $venueMessage =
                    "Unable to change the venue status.";

                $venueMessageType =
                    "error";
            }


            $statusStmt->close();

        } else {

            $venueMessage =
                "Unable to prepare the status update query.";

            $venueMessageType =
                "error";
        }
    }
}


/* =====================================================
   SUCCESS MESSAGE
===================================================== */

if (isset($_GET["success"])) {

    if (
        $_GET["success"] === "added"
    ) {

        $venueMessage =
            "Venue successfully added.";

        $venueMessageType =
            "success";

    } elseif (
        $_GET["success"] === "updated"
    ) {

        $venueMessage =
            "Venue successfully updated.";

        $venueMessageType =
            "success";

    } elseif (
        $_GET["success"] === "status_changed"
    ) {

        $venueMessage =
            "Venue status successfully changed.";

        $venueMessageType =
            "success";
    }
}


/* =====================================================
   GET SEARCH
===================================================== */

$search = "";

if (isset($_GET["search"])) {

    $search =
        trim(
            $_GET["search"]
        );
}


/* =====================================================
   GET STATUS FILTER
===================================================== */

$statusFilter = "";

if (isset($_GET["status"])) {

    $statusFilter =
        strtolower(
            trim(
                $_GET["status"]
            )
        );
}


/* =====================================================
   GET VENUE TYPES
===================================================== */

$venueTypes = [];

$venueTypesSql = "

    SELECT DISTINCT venue_type

    FROM venues

    WHERE venue_type IS NOT NULL
      AND venue_type <> ''

    ORDER BY venue_type ASC

";


$venueTypesResult =
    $conn->query(
        $venueTypesSql
    );


if ($venueTypesResult) {

    while (
        $row =
        $venueTypesResult->fetch_assoc()
    ) {

        $venueTypes[] =
            $row["venue_type"];
    }


    $venueTypesResult->free();
}


/* =====================================================
   GET TOTAL VENUES
===================================================== */

$totalVenues = 0;

$totalSql = "

    SELECT COUNT(*) AS total

    FROM venues

";


$totalResult =
    $conn->query(
        $totalSql
    );


if ($totalResult) {

    $totalRow =
        $totalResult->fetch_assoc();

    $totalVenues =
        (int)(
            $totalRow["total"] ?? 0
        );


    $totalResult->free();
}


/* =====================================================
   GET AVAILABLE VENUES
===================================================== */

$availableVenues = 0;

$availableSql = "

    SELECT COUNT(*) AS total

    FROM venues

    WHERE status = 'available'

";


$availableResult =
    $conn->query(
        $availableSql
    );


if ($availableResult) {

    $availableRow =
        $availableResult->fetch_assoc();

    $availableVenues =
        (int)(
            $availableRow["total"] ?? 0
        );


    $availableResult->free();
}


/* =====================================================
   GET UNAVAILABLE VENUES
===================================================== */

$unavailableVenues = 0;

$unavailableSql = "

    SELECT COUNT(*) AS total

    FROM venues

    WHERE status = 'unavailable'

";


$unavailableResult =
    $conn->query(
        $unavailableSql
    );


if ($unavailableResult) {

    $unavailableRow =
        $unavailableResult->fetch_assoc();

    $unavailableVenues =
        (int)(
            $unavailableRow["total"] ?? 0
        );


    $unavailableResult->free();
}


/* =====================================================
   BUILD VENUE QUERY
===================================================== */

$sql = "

    SELECT
        id,
        venue_name,
        location,
        venue_type,
        description,
        image,
        status,
        created_at,
        updated_at

    FROM venues

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
            venue_name LIKE ?
            OR location LIKE ?
            OR venue_type LIKE ?
            OR description LIKE ?
        )

    ";


    $searchValue =
        "%" .
        $search .
        "%";


    $types .=
        "ssss";


    $params[] =
        $searchValue;

    $params[] =
        $searchValue;

    $params[] =
        $searchValue;

    $params[] =
        $searchValue;
}


/* =====================================================
   STATUS FILTER
===================================================== */

if (
    $statusFilter !== "" &&
    in_array(
        $statusFilter,
        [
            "available",
            "unavailable"
        ],
        true
    )
) {

    $sql .= "

        AND status = ?

    ";


    $types .=
        "s";


    $params[] =
        $statusFilter;
}


/* =====================================================
   ORDER
===================================================== */

$sql .= "

    ORDER BY
        created_at DESC,
        id DESC

";


/* =====================================================
   PREPARE
===================================================== */

$stmt =
    $conn->prepare(
        $sql
    );


if (!$stmt) {

    die(
        "Unable to load venues."
    );
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

$venues = [];


if ($result) {

    while (
        $row =
        $result->fetch_assoc()
    ) {

        $venues[] =
            $row;
    }
}


$stmt->close();


$filteredVenueCount =
    count(
        $venues
    );


/* =====================================================
   VENUE IMAGE URL
===================================================== */

function getVenueImageUrl(
    $image
): string {

    $image =
        trim(
            (string)$image
        );


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

        return
            "../" .
            $image;
    }


    /* Legacy uploads */

    if (
        strpos(
            $image,
            "uploads/"
        ) === 0
    ) {

        return
            "../" .
            $image;
    }


    /* Filename only */

    return
        "../images/" .
        basename($image);
}


/* =====================================================
   PAGE CONTENT
===================================================== */

ob_start();

?>

<section class="admin-venues">


    <!-- =================================================
         PAGE HEADER
    ================================================== -->

    <div class="event-packages-header">

        <div class="event-packages-title">

            <div class="page-title-icon">

                <i class="fa-solid fa-building"></i>

            </div>

            <div>

                <span class="page-label">
                    VENUE MANAGEMENT
                </span>

                <h2>
                    Venues
                </h2>

                <p>
                    Manage and organize your available
                    event venues and locations.
                </p>

            </div>

        </div>


        <div class="event-packages-actions">

            <button
                type="button"
                class="btn-report"
                id="btnVenueReport"
            >

                <i class="fa-solid fa-file-pdf"></i>

                <span>
                    Report
                </span>

            </button>


            <button
                type="button"
                class="btn-add-package"
                id="btnOpenAddVenue"
            >

                <i class="fa-solid fa-plus"></i>

                <span>
                    Add Venue
                </span>

            </button>

        </div>

    </div>


    <!-- =================================================
         STATISTICS
    ================================================== -->

    <div class="package-stat-grid">


        <!-- TOTAL -->

        <div class="package-stat-card">

            <div class="package-stat-icon">

                <i class="fa-solid fa-building"></i>

            </div>

            <div class="package-stat-content">

                <span>
                    Total Venues
                </span>

                <strong>
                    <?= number_format($totalVenues) ?>
                </strong>

            </div>

        </div>


        <!-- AVAILABLE -->

        <div class="package-stat-card">

            <div class="package-stat-icon">

                <i class="fa-solid fa-circle-check"></i>

            </div>

            <div class="package-stat-content">

                <span>
                    Available
                </span>

                <strong>
                    <?= number_format($availableVenues) ?>
                </strong>

            </div>

        </div>


        <!-- UNAVAILABLE -->

        <div class="package-stat-card">

            <div class="package-stat-icon">

                <i class="fa-solid fa-circle-xmark"></i>

            </div>

            <div class="package-stat-content">

                <span>
                    Unavailable
                </span>

                <strong>
                    <?= number_format($unavailableVenues) ?>
                </strong>

            </div>

        </div>

    </div>


    <!-- =================================================
         VENUE MANAGEMENT PANEL
    ================================================== -->

    <section class="event-packages-panel">


        <div class="packages-panel-header">

            <div>

                <span class="panel-label">
                    VENUE LIST
                </span>

                <h3>
                    Manage Venues
                </h3>

            </div>


            <div class="package-total">

                <i class="fa-solid fa-building"></i>

                <?= number_format($filteredVenueCount) ?>

                venue<?= $filteredVenueCount !== 1 ? "s" : "" ?>

            </div>

        </div>


        <!-- =================================================
             FILTER BAR
        ================================================== -->

        <form
            method="GET"
            class="package-filter-bar"
            id="venueFilterForm"
        >

            <div class="search-container">

                <i class="fa-solid fa-magnifying-glass"></i>

                <input
                    type="text"
                    name="search"
                    id="venueSearch"
                    placeholder="Search venue, location, type, or description..."
                    value="<?= htmlspecialchars(
                        $search,
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>"
                >

            </div>


            <div class="filter-container">

                <i class="fa-solid fa-filter"></i>

                <select
                    name="status"
                    id="venueStatusFilter"
                >

                    <option value="">
                        All Status
                    </option>

                    <option
                        value="available"
                        <?= $statusFilter === "available"
                            ? "selected"
                            : ""
                        ?>
                    >
                        Available
                    </option>

                    <option
                        value="unavailable"
                        <?= $statusFilter === "unavailable"
                            ? "selected"
                            : ""
                        ?>
                    >
                        Unavailable
                    </option>

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
                $statusFilter !== ""
            ): ?>

                <a
                    href="admin_venues.php"
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
                id="venuesTable"
            >

                <thead>

                    <tr>

                        <th>
                            Venue
                        </th>

                        <th>
                            Location
                        </th>

                        <th>
                            Type
                        </th>

                        <th>
                            Status
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

                    <?php if (!empty($venues)): ?>

                        <?php foreach ($venues as $venue): ?>

                            <?php

                            $venueImage =
                                getVenueImageUrl(
                                    $venue["image"] ?? ""
                                );

                            ?>

                            <tr>


                                <!-- VENUE -->

                                <td>

                                    <div class="package-cell">

                                        <div class="package-image">

                                            <?php if ($venueImage !== ""): ?>

                                                <img
                                                    src="<?= htmlspecialchars(
                                                        $venueImage,
                                                        ENT_QUOTES,
                                                        "UTF-8"
                                                    ) ?>"
                                                    alt="<?= htmlspecialchars(
                                                        $venue["venue_name"]
                                                            ?? "Venue",
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

                                                    <i class="fa-solid fa-building"></i>

                                                </div>

                                            <?php else: ?>

                                                <div class="package-image-fallback">

                                                    <i class="fa-solid fa-building"></i>

                                                </div>

                                            <?php endif; ?>

                                        </div>


                                        <div class="package-information">

                                            <strong>

                                                <?= htmlspecialchars(
                                                    $venue["venue_name"]
                                                        ?? "Unnamed Venue",
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                ) ?>

                                            </strong>

                                            <span>

                                                Venue ID:
                                                #<?= (int)$venue["id"] ?>

                                            </span>

                                        </div>

                                    </div>

                                </td>


                                <!-- LOCATION -->

                                <td>

                                    <div class="date-cell">

                                        <i class="fa-solid fa-location-dot"></i>

                                        <span>

                                            <?= htmlspecialchars(
                                                $venue["location"]
                                                    ?? "No location",
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>

                                        </span>

                                    </div>

                                </td>


                                <!-- TYPE -->

                                <td>

                                    <span class="category-badge">

                                        <i class="fa-solid fa-building-columns"></i>

                                        <?= htmlspecialchars(
                                            $venue["venue_type"]
                                                ?? "General",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>

                                    </span>

                                </td>


                                <!-- STATUS -->

                                <td>

                                    <?php if (
                                        strtolower(
                                            $venue["status"] ?? ""
                                        ) === "available"
                                    ): ?>

                                        <span class="venue-status-badge available">

                                            <i class="fa-solid fa-circle-check"></i>

                                            Available

                                        </span>

                                    <?php else: ?>

                                        <span class="venue-status-badge unavailable">

                                            <i class="fa-solid fa-circle-xmark"></i>

                                            Unavailable

                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- CREATED -->

                                <td>

                                    <div class="date-cell">

                                        <i class="fa-regular fa-calendar"></i>

                                        <span>

                                            <?= !empty(
                                                $venue["created_at"]
                                            )

                                                ? date(
                                                    "M d, Y",
                                                    strtotime(
                                                        $venue["created_at"]
                                                    )
                                                )

                                                : "N/A"
                                            ?>

                                        </span>

                                    </div>

                                </td>


                                <!-- ACTION -->

                                <td class="action-column">


                                    <!-- EDIT BUTTON -->

                                    <button
                                        type="button"
                                        class="btn-edit-package btn-edit-venue"
                                        title="Edit Venue"

                                        data-id="<?= (int)$venue["id"] ?>"

                                        data-name="<?= htmlspecialchars(
                                            $venue["venue_name"] ?? "",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>"

                                        data-location="<?= htmlspecialchars(
                                            $venue["location"] ?? "",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>"

                                        data-type="<?= htmlspecialchars(
                                            $venue["venue_type"] ?? "",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>"

                                        data-description="<?= htmlspecialchars(
                                            $venue["description"] ?? "",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>"

                                        data-status="<?= htmlspecialchars(
                                            $venue["status"] ?? "available",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>"

                                        data-image="<?= htmlspecialchars(
                                            $venue["image"] ?? "",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>"
                                    >

                                        <i class="fa-solid fa-pen-to-square"></i>

                                        <span>
                                            Edit
                                        </span>

                                    </button>


                                    <?php

                                    $currentVenueStatus =
                                        strtolower(
                                            trim(
                                                $venue["status"]
                                                    ?? "available"
                                            )
                                        );

                                    $isAvailable =
                                        $currentVenueStatus ===
                                        "available";

                                    $newVenueStatus =
                                        $isAvailable
                                            ? "unavailable"
                                            : "available";

                                    ?>


                                    <!-- CHANGE STATUS -->

                                    <form
                                        method="POST"
                                        class="venue-status-form"
                                        onsubmit="return confirmVenueStatusChange(
                                            this,
                                            '<?= htmlspecialchars(
                                                $venue["venue_name"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>',
                                            '<?= $newVenueStatus ?>'
                                        );"
                                    >

                                        <input
                                            type="hidden"
                                            name="change_venue_status"
                                            value="1"
                                        >

                                        <input
                                            type="hidden"
                                            name="venue_id"
                                            value="<?= (int)$venue["id"] ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="new_status"
                                            value="<?= htmlspecialchars(
                                                $newVenueStatus,
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>"
                                        >


                                        <button
                                            type="submit"
                                            class="btn-status-venue <?=
                                                $isAvailable
                                                    ? "make-unavailable"
                                                    : "make-available"
                                            ?>"
                                            title="<?=
                                                $isAvailable
                                                    ? "Set venue as unavailable"
                                                    : "Set venue as available"
                                            ?>"
                                        >

                                            <?php if ($isAvailable): ?>

                                                <i class="fa-solid fa-ban"></i>

                                                <span>
                                                    Unavailable
                                                </span>

                                            <?php else: ?>

                                                <i class="fa-solid fa-circle-check"></i>

                                                <span>
                                                    Available
                                                </span>

                                            <?php endif; ?>

                                        </button>

                                    </form>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>

                            <td colspan="6">

                                <div class="empty-packages">

                                    <div class="empty-icon">

                                        <i class="fa-solid fa-building"></i>

                                    </div>

                                    <strong>
                                        No Venues Found
                                    </strong>

                                    <span>

                                        No venues match your current
                                        search or filter.

                                    </span>

                                    <button
                                        type="button"
                                        class="empty-add-button"
                                        id="btnOpenAddVenueEmpty"
                                    >

                                        <i class="fa-solid fa-plus"></i>

                                        Add Venue

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
     ADD VENUE MODAL
===================================================== -->

<div
    class="package-modal"
    id="addVenueModal"
    aria-hidden="true"
>

    <div class="package-modal-overlay"></div>


    <div
        class="package-modal-content"
        role="dialog"
        aria-modal="true"
        aria-labelledby="addVenueModalTitle"
    >

        <div class="package-modal-header">

            <div class="package-modal-title">

                <div class="package-modal-icon">

                    <i class="fa-solid fa-building"></i>

                </div>

                <div>

                    <span class="package-modal-label">
                        VENUE MANAGEMENT
                    </span>

                    <h3 id="addVenueModalTitle">
                        Add Venue
                    </h3>

                    <p>
                        Create a new venue for your event management system.
                    </p>

                </div>

            </div>


            <button
                type="button"
                class="package-modal-close"
                data-close-venue-modal
            >

                <i class="fa-solid fa-xmark"></i>

            </button>

        </div>


        <form
            method="POST"
            enctype="multipart/form-data"
            class="package-modal-form"
            id="addVenueForm"
        >

            <input
                type="hidden"
                name="add_venue"
                value="1"
            >


            <!-- VENUE NAME -->

            <div class="package-form-group">

                <label for="addVenueName">

                    Venue Name

                    <span>*</span>

                </label>


                <div class="package-input-wrapper">

                    <i class="fa-solid fa-building"></i>

                    <input
                        type="text"
                        name="venue_name"
                        id="addVenueName"
                        placeholder="Enter venue name"
                        maxlength="150"
                        required
                    >

                </div>

            </div>


            <!-- LOCATION -->

            <div class="package-form-group">

                <label for="addVenueLocation">

                    Location

                    <span>*</span>

                </label>


                <div class="package-input-wrapper">

                    <i class="fa-solid fa-location-dot"></i>

                    <input
                        type="text"
                        name="location"
                        id="addVenueLocation"
                        placeholder="Enter venue location"
                        maxlength="255"
                        required
                    >

                </div>

            </div>


            <!-- VENUE TYPE -->

            <div class="package-form-group">

                <label for="addVenueType">

                    Venue Type

                    <span>*</span>

                </label>


                <div class="package-input-wrapper">

                    <i class="fa-solid fa-building-columns"></i>

                    <input
                        type="text"
                        name="venue_type"
                        id="addVenueType"
                        placeholder="e.g. Hotel, Ballroom, Garden"
                        maxlength="100"
                        required
                    >

                </div>

            </div>


            <!-- STATUS -->

            <div class="package-form-group">

                <label for="addVenueStatus">

                    Status

                    <span>*</span>

                </label>


                <div class="package-input-wrapper">

                    <i class="fa-solid fa-circle-check"></i>

                    <select
                        name="status"
                        id="addVenueStatus"
                        required
                    >

                        <option value="available">
                            Available
                        </option>

                        <option value="unavailable">
                            Unavailable
                        </option>

                    </select>

                </div>

            </div>


            <!-- DESCRIPTION -->

            <div class="package-form-group">

                <label for="addVenueDescription">

                    Description

                </label>


                <div class="package-textarea-wrapper">

                    <i class="fa-solid fa-align-left"></i>

                    <textarea
                        name="description"
                        id="addVenueDescription"
                        rows="5"
                        placeholder="Enter venue description..."
                    ></textarea>

                </div>


                <small>
                    Add information about the venue,
                    facilities, capacity, and features.
                </small>

            </div>


            <!-- IMAGE -->

            <div class="package-form-group package-image-form-group">

                <label for="addVenueImage">
                    Venue Image
                </label>


                <div
                    class="package-picturebox"
                    id="addVenuePictureBox"
                >

                    <div
                        class="package-picturebox-placeholder"
                        id="addVenuePicturePlaceholder"
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
                        alt="Venue Image Preview"
                        id="addVenueImagePreview"
                        class="package-picturebox-image"
                        style="display:none;"
                    >

                </div>


                <div class="package-file-wrapper">

                    <input
                        type="file"
                        name="venue_image"
                        id="addVenueImage"
                        accept="image/jpeg,image/png,image/webp"
                    >


                    <label
                        for="addVenueImage"
                        class="package-file-label"
                    >

                        <i class="fa-solid fa-cloud-arrow-up"></i>

                        <span id="addVenueFileText">
                            Choose venue image
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
                    data-close-venue-modal
                >
                    Cancel
                </button>


                <button
                    type="submit"
                    class="package-modal-save"
                >

                    <i class="fa-solid fa-floppy-disk"></i>

                    Save Venue

                </button>

            </div>

        </form>

    </div>

</div>


<!-- =====================================================
     EDIT VENUE MODAL
===================================================== -->

<div
    class="package-modal"
    id="editVenueModal"
    aria-hidden="true"
>

    <div class="package-modal-overlay"></div>


    <div
        class="package-modal-content"
        role="dialog"
        aria-modal="true"
        aria-labelledby="editVenueModalTitle"
    >

        <div class="package-modal-header">

            <div class="package-modal-title">

                <div class="package-modal-icon">

                    <i class="fa-solid fa-pen-to-square"></i>

                </div>

                <div>

                    <span class="package-modal-label">
                        VENUE MANAGEMENT
                    </span>

                    <h3 id="editVenueModalTitle">
                        Edit Venue
                    </h3>

                    <p>
                        Update the selected venue information.
                    </p>

                </div>

            </div>


            <button
                type="button"
                class="package-modal-close"
                data-close-venue-modal
            >

                <i class="fa-solid fa-xmark"></i>

            </button>

        </div>


        <form
            method="POST"
            enctype="multipart/form-data"
            class="package-modal-form"
            id="editVenueForm"
        >

            <input
                type="hidden"
                name="update_venue"
                value="1"
            >


            <input
                type="hidden"
                name="venue_id"
                id="editVenueId"
                value=""
            >


            <!-- VENUE NAME -->

            <div class="package-form-group">

                <label for="editVenueName">

                    Venue Name

                    <span>*</span>

                </label>


                <div class="package-input-wrapper">

                    <i class="fa-solid fa-building"></i>

                    <input
                        type="text"
                        name="venue_name"
                        id="editVenueName"
                        placeholder="Enter venue name"
                        maxlength="150"
                        required
                    >

                </div>

            </div>


            <!-- LOCATION -->

            <div class="package-form-group">

                <label for="editVenueLocation">

                    Location

                    <span>*</span>

                </label>


                <div class="package-input-wrapper">

                    <i class="fa-solid fa-location-dot"></i>

                    <input
                        type="text"
                        name="location"
                        id="editVenueLocation"
                        placeholder="Enter venue location"
                        maxlength="255"
                        required
                    >

                </div>

            </div>


            <!-- VENUE TYPE -->

            <div class="package-form-group">

                <label for="editVenueType">

                    Venue Type

                    <span>*</span>

                </label>


                <div class="package-input-wrapper">

                    <i class="fa-solid fa-building-columns"></i>

                    <input
                        type="text"
                        name="venue_type"
                        id="editVenueType"
                        placeholder="e.g. Hotel, Ballroom, Garden"
                        maxlength="100"
                        required
                    >

                </div>

            </div>


            <!-- STATUS -->

            <div class="package-form-group">

                <label for="editVenueStatus">

                    Status

                    <span>*</span>

                </label>


                <div class="package-input-wrapper">

                    <i class="fa-solid fa-circle-check"></i>

                    <select
                        name="status"
                        id="editVenueStatus"
                        required
                    >

                        <option value="available">
                            Available
                        </option>

                        <option value="unavailable">
                            Unavailable
                        </option>

                    </select>

                </div>

            </div>


            <!-- DESCRIPTION -->

            <div class="package-form-group">

                <label for="editVenueDescription">
                    Description
                </label>


                <div class="package-textarea-wrapper">

                    <i class="fa-solid fa-align-left"></i>

                    <textarea
                        name="description"
                        id="editVenueDescription"
                        rows="5"
                        placeholder="Enter venue description..."
                    ></textarea>

                </div>


                <small>
                    Update the venue description and information.
                </small>

            </div>


            <!-- IMAGE -->

            <div class="package-form-group package-image-form-group">

                <label for="editVenueImage">
                    Venue Image
                </label>


                <div
                    class="package-picturebox"
                    id="editVenuePictureBox"
                >

                    <div
                        class="package-picturebox-placeholder"
                        id="editVenuePicturePlaceholder"
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
                        alt="Current Venue Image"
                        id="editVenueImagePreview"
                        class="package-picturebox-image"
                        style="display:none;"
                    >

                </div>


                <div class="package-file-wrapper">

                    <input
                        type="file"
                        name="venue_image"
                        id="editVenueImage"
                        accept="image/jpeg,image/png,image/webp"
                    >


                    <label
                        for="editVenueImage"
                        class="package-file-label"
                    >

                        <i class="fa-solid fa-cloud-arrow-up"></i>

                        <span id="editVenueFileText">
                            Choose new venue image
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
                    data-close-venue-modal
                >
                    Cancel
                </button>


                <button
                    type="submit"
                    class="package-modal-save"
                >

                    <i class="fa-solid fa-floppy-disk"></i>

                    Update Venue

                </button>

            </div>

        </form>

    </div>

</div>


<!-- =====================================================
     VENUE STATUS CONFIRMATION POPUP
===================================================== -->

<div
    class="venue-status-popup"
    id="venueStatusPopup"
    aria-hidden="true"
>

    <div
        class="venue-status-popup-overlay"
    ></div>


    <div
        class="venue-status-popup-content"
        role="dialog"
        aria-modal="true"
        aria-labelledby="venueStatusPopupTitle"
    >


        <!-- CLOSE -->

        <button
            type="button"
            class="venue-status-popup-close"
            id="venueStatusPopupClose"
            aria-label="Close"
        >

            <i class="fa-solid fa-xmark"></i>

        </button>


        <!-- ICON -->

        <div
            class="venue-status-popup-icon"
            id="venueStatusPopupIcon"
        >

            <i class="fa-solid fa-circle-check"></i>

        </div>


        <!-- CONTENT -->

        <div class="venue-status-popup-body">

            <h3
                id="venueStatusPopupTitle"
            >
                Change Venue Status?
            </h3>


            <p
                id="venueStatusPopupMessage"
            >
                Are you sure you want to change this venue status?
            </p>

        </div>


        <!-- ACTIONS -->

        <div class="venue-status-popup-actions">

            <button
                type="button"
                class="venue-status-popup-cancel"
                id="venueStatusPopupCancel"
            >

                Cancel

            </button>


            <button
                type="button"
                class="venue-status-popup-confirm"
                id="venueStatusPopupConfirm"
            >

                <i class="fa-solid fa-circle-check"></i>

                <span>
                    Confirm
                </span>

            </button>

        </div>

    </div>

</div>


<!-- =====================================================
     PDF REPORT AREA
===================================================== -->

<div
    id="venueReport"
    class="print-report"
>

    <div class="report-header">

        <div>

            <h1>
                Event Solutions by S.H.E.
            </h1>

            <h2>
                Venues Report
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

                <th>
                    ID
                </th>

                <th>
                    Venue Name
                </th>

                <th>
                    Location
                </th>

                <th>
                    Type
                </th>

                <th>
                    Status
                </th>

                <th>
                    Created
                </th>

            </tr>

        </thead>


        <tbody>

            <?php if (!empty($venues)): ?>

                <?php foreach ($venues as $venue): ?>

                    <tr>

                        <td>
                            #<?= (int)$venue["id"] ?>
                        </td>

                        <td>

                            <?= htmlspecialchars(
                                $venue["venue_name"]
                                    ?? "",
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>

                        </td>

                        <td>

                            <?= htmlspecialchars(
                                $venue["location"]
                                    ?? "",
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>

                        </td>

                        <td>

                            <?= htmlspecialchars(
                                $venue["venue_type"]
                                    ?? "General",
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>

                        </td>

                        <td>

                            <?= htmlspecialchars(
                                ucfirst(
                                    $venue["status"]
                                        ?? ""
                                ),
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>

                        </td>

                        <td>

                            <?= !empty(
                                $venue["created_at"]
                            )

                                ? date(
                                    "M d, Y",
                                    strtotime(
                                        $venue["created_at"]
                                    )
                                )

                                : "N/A"
                            ?>

                        </td>

                    </tr>

                <?php endforeach; ?>

            <?php else: ?>

                <tr>

                    <td
                        colspan="6"
                        style="text-align:center;"
                    >

                        No venue records found.

                    </td>

                </tr>

            <?php endif; ?>

        </tbody>

    </table>

</div>


<script>

/* =====================================================
   VENUE PAGE DATA
===================================================== */

window.adminVenuesData = <?= json_encode(
    [
        "totalVenues" =>
            $totalVenues,

        "availableVenues" =>
            $availableVenues,

        "unavailableVenues" =>
            $unavailableVenues,

        "displayedVenues" =>
            $filteredVenueCount,

        "selectedStatus" =>
            $statusFilter,

        "message" =>
            $venueMessage,

        "messageType" =>
            $venueMessageType
    ],

    JSON_UNESCAPED_SLASHES |
    JSON_UNESCAPED_UNICODE |
    JSON_HEX_TAG |
    JSON_HEX_AMP |
    JSON_HEX_APOS |
    JSON_HEX_QUOT

) ?>;


/* =====================================================
   CHANGE VENUE STATUS POPUP
===================================================== */

function openVenueStatusPopup(
    form,
    venueName,
    newStatus
) {

    const popup =
        document.getElementById(
            "venueStatusPopup"
        );

    const title =
        document.getElementById(
            "venueStatusPopupTitle"
        );

    const message =
        document.getElementById(
            "venueStatusPopupMessage"
        );

    const icon =
        document.getElementById(
            "venueStatusPopupIcon"
        );

    const confirmButton =
        document.getElementById(
            "venueStatusPopupConfirm"
        );


    if (
        !popup ||
        !title ||
        !message ||
        !icon ||
        !confirmButton
    ) {
        return;
    }


    window.pendingVenueStatusForm =
        form;


    if (
        newStatus === "available"
    ) {

        title.textContent =
            "Make Venue Available?";


        message.textContent =
            `Are you sure you want to make "${venueName}" available?`;


        icon.className =
            "venue-status-popup-icon available";


        icon.innerHTML =
            '<i class="fa-solid fa-circle-check"></i>';


        confirmButton.className =
            "venue-status-popup-confirm available";


        confirmButton.innerHTML =
            '<i class="fa-solid fa-circle-check"></i>' +
            '<span>Make Available</span>';

    } else {

        title.textContent =
            "Make Venue Unavailable?";


        message.textContent =
            `Are you sure you want to make "${venueName}" unavailable?`;


        icon.className =
            "venue-status-popup-icon unavailable";


        icon.innerHTML =
            '<i class="fa-solid fa-ban"></i>';


        confirmButton.className =
            "venue-status-popup-confirm unavailable";


        confirmButton.innerHTML =
            '<i class="fa-solid fa-ban"></i>' +
            '<span>Make Unavailable</span>';
    }


    popup.setAttribute(
        "aria-hidden",
        "false"
    );


    popup.classList.add(
        "show"
    );


    document.body.classList.add(
        "venue-popup-open"
    );
}


/* =====================================================
   CHANGE VENUE STATUS
===================================================== */

function confirmVenueStatusChange(
    form,
    venueName,
    newStatus
) {

    openVenueStatusPopup(
        form,
        venueName,
        newStatus
    );

    return false;
}


/* =====================================================
   CLOSE VENUE STATUS POPUP
===================================================== */

function closeVenueStatusPopup() {

    const popup =
        document.getElementById(
            "venueStatusPopup"
        );


    if (!popup) {
        return;
    }


    popup.classList.remove(
        "show"
    );


    popup.setAttribute(
        "aria-hidden",
        "true"
    );


    document.body.classList.remove(
        "venue-popup-open"
    );


    const confirmButton =
        document.getElementById(
            "venueStatusPopupConfirm"
        );


    if (confirmButton) {

        confirmButton.disabled =
            false;


        confirmButton.classList.remove(
            "loading"
        );
    }


    window.pendingVenueStatusForm =
        null;
}


/* =====================================================
   DOM READY
===================================================== */

document.addEventListener(
    "DOMContentLoaded",
    function () {


        /* =============================================
           ELEMENTS
        ============================================= */

        const addModal =
            document.getElementById(
                "addVenueModal"
            );

        const editModal =
            document.getElementById(
                "editVenueModal"
            );

        const addForm =
            document.getElementById(
                "addVenueForm"
            );

        const editForm =
            document.getElementById(
                "editVenueForm"
            );


        /* =============================================
           ADD VENUE BUTTONS
        ============================================= */

        const addButtons =
            document.querySelectorAll(
                "#btnOpenAddVenue, #btnOpenAddVenueEmpty"
            );


        addButtons.forEach(
            function (button) {

                button.addEventListener(
                    "click",
                    function () {

                        resetAddVenueForm();

                        openVenueModal(
                            addModal
                        );

                    }
                );

            }
        );


        /* =============================================
           EDIT VENUE BUTTONS
        ============================================= */

        const editButtons =
            document.querySelectorAll(
                ".btn-edit-venue"
            );


        editButtons.forEach(
            function (button) {

                button.addEventListener(
                    "click",
                    function () {

                        openEditVenueModal(
                            button
                        );

                    }
                );

            }
        );


        /* =============================================
           CLOSE VENUE MODALS
        ============================================= */

        document.querySelectorAll(
            "[data-close-venue-modal]"
        ).forEach(
            function (button) {

                button.addEventListener(
                    "click",
                    function () {

                        closeAllVenueModals();

                    }
                );

            }
        );


        /* =============================================
           MODAL OVERLAYS
        ============================================= */

        document.querySelectorAll(
            ".package-modal-overlay"
        ).forEach(
            function (overlay) {

                overlay.addEventListener(
                    "click",
                    function () {

                        closeAllVenueModals();

                    }
                );

            }
        );


        /* =============================================
           ADD IMAGE PREVIEW
        ============================================= */

        const addImage =
            document.getElementById(
                "addVenueImage"
            );


        if (addImage) {

            addImage.addEventListener(
                "change",
                function () {

                    previewVenueImage(
                        addImage,
                        "addVenueImagePreview",
                        "addVenuePicturePlaceholder",
                        "addVenueFileText"
                    );

                }
            );
        }


        /* =============================================
           EDIT IMAGE PREVIEW
        ============================================= */

        const editImage =
            document.getElementById(
                "editVenueImage"
            );


        if (editImage) {

            editImage.addEventListener(
                "change",
                function () {

                    previewVenueImage(
                        editImage,
                        "editVenueImagePreview",
                        "editVenuePicturePlaceholder",
                        "editVenueFileText"
                    );

                }
            );
        }


        /* =============================================
           IMAGE VALIDATION
        ============================================= */

        if (addForm) {

            addForm.addEventListener(
                "submit",
                function (event) {

                    if (
                        !validateVenueImage(
                            addImage
                        )
                    ) {

                        event.preventDefault();
                    }

                }
            );
        }


        if (editForm) {

            editForm.addEventListener(
                "submit",
                function (event) {

                    if (
                        !validateVenueImage(
                            editImage
                        )
                    ) {

                        event.preventDefault();
                    }

                }
            );
        }


        /* =============================================
           STATUS POPUP
        ============================================= */

        const statusPopup =
            document.getElementById(
                "venueStatusPopup"
            );

        const statusClose =
            document.getElementById(
                "venueStatusPopupClose"
            );

        const statusCancel =
            document.getElementById(
                "venueStatusPopupCancel"
            );

        const statusConfirm =
            document.getElementById(
                "venueStatusPopupConfirm"
            );


        const statusOverlay =
            statusPopup
                ? statusPopup.querySelector(
                    ".venue-status-popup-overlay"
                )
                : null;


        if (statusClose) {

            statusClose.addEventListener(
                "click",
                closeVenueStatusPopup
            );
        }


        if (statusCancel) {

            statusCancel.addEventListener(
                "click",
                closeVenueStatusPopup
            );
        }


        if (statusOverlay) {

            statusOverlay.addEventListener(
                "click",
                closeVenueStatusPopup
            );
        }


        if (statusConfirm) {

            statusConfirm.addEventListener(
                "click",
                function () {

                    const form =
                        window.pendingVenueStatusForm;


                    if (!form) {

                        closeVenueStatusPopup();

                        return;
                    }


                    statusConfirm.disabled =
                        true;


                    statusConfirm.classList.add(
                        "loading"
                    );


                    /*
                     * Use form.submit() so the
                     * onsubmit event does not
                     * reopen the popup.
                     */

                    form.submit();

                }
            );
        }


        /* =============================================
           ESCAPE KEY
        ============================================= */

        document.addEventListener(
            "keydown",
            function (event) {

                if (
                    event.key !== "Escape"
                ) {
                    return;
                }


                if (
                    statusPopup &&
                    statusPopup.classList.contains(
                        "show"
                    )
                ) {

                    closeVenueStatusPopup();

                    return;
                }


                closeAllVenueModals();

            }
        );


        /* =============================================
           STATUS FILTER
        ============================================= */

        const statusFilter =
            document.getElementById(
                "venueStatusFilter"
            );


        const filterForm =
            document.getElementById(
                "venueFilterForm"
            );


        if (
            statusFilter &&
            filterForm
        ) {

            statusFilter.addEventListener(
                "change",
                function () {

                    filterForm.submit();

                }
            );
        }


        /* =============================================
           REPORT
        ============================================= */

        const reportButton =
            document.getElementById(
                "btnVenueReport"
            );


        if (reportButton) {

            reportButton.addEventListener(
                "click",
                function () {

                    printVenueReport();

                }
            );
        }


        /* =============================================
           SEARCH ENTER
        ============================================= */

        const search =
            document.getElementById(
                "venueSearch"
            );


        if (
            search &&
            filterForm
        ) {

            search.addEventListener(
                "keydown",
                function (event) {

                    if (
                        event.key === "Enter"
                    ) {

                        event.preventDefault();

                        filterForm.submit();

                    }

                }
            );
        }

    }
);


/* =====================================================
   OPEN VENUE MODAL
===================================================== */

function openVenueModal(
    modal
) {

    if (!modal) {
        return;
    }


    modal.classList.add(
        "show"
    );


    modal.setAttribute(
        "aria-hidden",
        "false"
    );


    document.body.classList.add(
        "venue-modal-open"
    );


    setTimeout(
        function () {

            const firstInput =
                modal.querySelector(
                    "input:not([type='hidden']), select, textarea"
                );


            if (firstInput) {

                firstInput.focus();

            }

        },
        100
    );
}


/* =====================================================
   CLOSE ALL VENUE MODALS
===================================================== */

function closeAllVenueModals() {

    document.querySelectorAll(
        ".package-modal"
    ).forEach(
        function (modal) {

            modal.classList.remove(
                "show"
            );


            modal.setAttribute(
                "aria-hidden",
                "true"
            );

        }
    );


    document.body.classList.remove(
        "venue-modal-open"
    );
}


/* =====================================================
   RESET ADD VENUE FORM
===================================================== */

function resetAddVenueForm() {

    const form =
        document.getElementById(
            "addVenueForm"
        );


    if (form) {

        form.reset();

    }


    const preview =
        document.getElementById(
            "addVenueImagePreview"
        );

    const placeholder =
        document.getElementById(
            "addVenuePicturePlaceholder"
        );

    const fileText =
        document.getElementById(
            "addVenueFileText"
        );


    if (preview) {

        preview.src =
            "";

        preview.style.display =
            "none";

    }


    if (placeholder) {

        placeholder.style.display =
            "flex";

    }


    if (fileText) {

        fileText.textContent =
            "Choose venue image";

    }

}


/* =====================================================
   OPEN EDIT VENUE MODAL
===================================================== */

function openEditVenueModal(
    button
) {

    const modal =
        document.getElementById(
            "editVenueModal"
        );


    if (
        !modal ||
        !button
    ) {
        return;
    }


    const dataset =
        button.dataset;


    const id =
        dataset.id || "";

    const name =
        dataset.name || "";

    const location =
        dataset.location || "";

    const type =
        dataset.type || "";

    const description =
        dataset.description || "";

    const status =
        dataset.status || "available";

    const image =
        dataset.image || "";


    const idInput =
        document.getElementById(
            "editVenueId"
        );

    const nameInput =
        document.getElementById(
            "editVenueName"
        );

    const locationInput =
        document.getElementById(
            "editVenueLocation"
        );

    const typeInput =
        document.getElementById(
            "editVenueType"
        );

    const descriptionInput =
        document.getElementById(
            "editVenueDescription"
        );

    const statusInput =
        document.getElementById(
            "editVenueStatus"
        );

    const imageInput =
        document.getElementById(
            "editVenueImage"
        );


    if (idInput) {

        idInput.value =
            id;

    }


    if (nameInput) {

        nameInput.value =
            name;

    }


    if (locationInput) {

        locationInput.value =
            location;

    }


    if (typeInput) {

        typeInput.value =
            type;

    }


    if (descriptionInput) {

        descriptionInput.value =
            description;

    }


    if (statusInput) {

        statusInput.value =
            status;

    }


    if (imageInput) {

        imageInput.value =
            "";

    }


    setEditVenueImage(
        image
    );


    const fileText =
        document.getElementById(
            "editVenueFileText"
        );


    if (fileText) {

        fileText.textContent =
            "Choose new venue image";

    }


    openVenueModal(
        modal
    );

}


/* =====================================================
   SET EDIT VENUE IMAGE
===================================================== */

function setEditVenueImage(
    image
) {

    const preview =
        document.getElementById(
            "editVenueImagePreview"
        );

    const placeholder =
        document.getElementById(
            "editVenuePicturePlaceholder"
        );


    if (
        !preview ||
        !placeholder
    ) {
        return;
    }


    if (!image) {

        preview.src =
            "";

        preview.style.display =
            "none";

        placeholder.style.display =
            "flex";

        return;
    }


    const imageUrl =
        getVenueImageUrl(
            image
        );


    if (!imageUrl) {

        preview.src =
            "";

        preview.style.display =
            "none";

        placeholder.style.display =
            "flex";

        return;
    }


    preview.onload =
        function () {

            preview.style.display =
                "block";

            placeholder.style.display =
                "none";

        };


    preview.onerror =
        function () {

            preview.style.display =
                "none";

            placeholder.style.display =
                "flex";

        };


    preview.src =
        imageUrl;

}


/* =====================================================
   GET VENUE IMAGE URL
===================================================== */

function getVenueImageUrl(
    image
) {

    if (!image) {
        return "";
    }


    image =
        String(image)
            .trim()
            .replace(
                /\\/g,
                "/"
            );


    if (
        /^https?:\/\//i.test(
            image
        )
    ) {

        return image;

    }


    image =
        image.replace(
            /^\/+/,
            ""
        );


    if (
        image.indexOf(
            "images/"
        ) === 0
    ) {

        return "../" +
            image;

    }


    if (
        image.indexOf(
            "uploads/"
        ) === 0
    ) {

        return "../" +
            image;

    }


    return
        "../images/" +
        image.split("/").pop();

}


/* =====================================================
   PREVIEW VENUE IMAGE
===================================================== */

function previewVenueImage(
    input,
    previewId,
    placeholderId,
    fileTextId
) {

    if (!input) {
        return;
    }


    const preview =
        document.getElementById(
            previewId
        );

    const placeholder =
        document.getElementById(
            placeholderId
        );

    const fileText =
        document.getElementById(
            fileTextId
        );


    if (
        !input.files ||
        !input.files[0]
    ) {

        if (preview) {

            preview.src =
                "";

            preview.style.display =
                "none";

        }


        if (placeholder) {

            placeholder.style.display =
                "flex";

        }


        return;
    }


    const file =
        input.files[0];


    const allowedTypes = [
        "image/jpeg",
        "image/png",
        "image/webp"
    ];


    if (
        !allowedTypes.includes(
            file.type
        )
    ) {

        input.value =
            "";


        if (fileText) {

            fileText.textContent =
                "Choose venue image";

        }


        if (preview) {

            preview.src =
                "";

            preview.style.display =
                "none";

        }


        if (placeholder) {

            placeholder.style.display =
                "flex";

        }


        alert(
            "Please select a valid JPG, PNG, or WEBP image."
        );

        return;

    }


    if (
        file.size <= 0
    ) {

        input.value =
            "";


        alert(
            "The selected image file is empty."
        );

        return;
    }


    if (
        file.size >
        5 * 1024 * 1024
    ) {

        input.value =
            "";


        if (fileText) {

            fileText.textContent =
                "Choose venue image";

        }


        if (preview) {

            preview.src =
                "";

            preview.style.display =
                "none";

        }


        if (placeholder) {

            placeholder.style.display =
                "flex";

        }


        alert(
            "Image size must not exceed 5 MB."
        );

        return;

    }


    if (fileText) {

        fileText.textContent =
            file.name;

    }


    const reader =
        new FileReader();


    reader.onload =
        function (event) {

            if (preview) {

                preview.src =
                    event.target.result;

                preview.style.display =
                    "block";

            }


            if (placeholder) {

                placeholder.style.display =
                    "none";

            }

        };


    reader.onerror =
        function () {

            alert(
                "Unable to preview the selected image."
            );

        };


    reader.readAsDataURL(
        file
    );

}


/* =====================================================
   VALIDATE VENUE IMAGE
===================================================== */

function validateVenueImage(
    input
) {

    if (
        !input ||
        !input.files ||
        !input.files.length
    ) {

        return true;

    }


    const file =
        input.files[0];


    const allowedTypes = [
        "image/jpeg",
        "image/png",
        "image/webp"
    ];


    if (
        !allowedTypes.includes(
            file.type
        )
    ) {

        alert(
            "Invalid image format. Please upload JPG, JPEG, PNG, or WEBP."
        );


        input.focus();

        return false;

    }


    if (
        file.size <= 0
    ) {

        alert(
            "The selected image file is empty."
        );


        input.focus();

        return false;

    }


    if (
        file.size >
        5 * 1024 * 1024
    ) {

        alert(
            "Image size must not exceed 5 MB."
        );


        input.focus();

        return false;

    }


    return true;

}


/* =====================================================
   PRINT VENUE REPORT
===================================================== */

function printVenueReport() {

    const report =
        document.getElementById(
            "venueReport"
        );


    if (!report) {
        return;
    }


    const reportWindow =
        window.open(
            "",
            "_blank",
            "width=1100,height=800"
        );


    if (!reportWindow) {

        alert(
            "Please allow pop-ups to generate the venue report."
        );

        return;

    }


    const reportContent =
        report.innerHTML;


    reportWindow.document.open();


    reportWindow.document.write(`

        <!DOCTYPE html>

        <html lang="en">

        <head>

            <meta charset="UTF-8">

            <meta
                name="viewport"
                content="width=device-width, initial-scale=1.0"
            >

            <title>
                Venues Report
            </title>


            <style>

                * {
                    box-sizing: border-box;
                }


                body {
                    margin: 0;
                    padding: 30px;
                    font-family: Arial, sans-serif;
                    color: #222;
                    background: #fff;
                }


                .report-header {
                    display: flex;
                    align-items: flex-start;
                    justify-content: space-between;
                    gap: 30px;
                    margin-bottom: 25px;
                    padding-bottom: 18px;
                    border-bottom: 2px solid #222;
                }


                .report-header h1 {
                    margin: 0 0 5px;
                    font-size: 22px;
                }


                .report-header h2 {
                    margin: 0;
                    font-size: 17px;
                    font-weight: 600;
                }


                .report-date {
                    font-size: 12px;
                    white-space: nowrap;
                    text-align: right;
                }


                .report-table {
                    width: 100%;
                    border-collapse: collapse;
                    font-size: 12px;
                }


                .report-table th {
                    padding: 10px 8px;
                    text-align: left;
                    background: #f1f1f1;
                    border: 1px solid #ccc;
                    font-weight: 700;
                }


                .report-table td {
                    padding: 9px 8px;
                    border: 1px solid #ccc;
                    vertical-align: middle;
                }


                .report-table tr {
                    page-break-inside: avoid;
                }


                @page {
                    size: A4 landscape;
                    margin: 12mm;
                }


                @media print {

                    body {
                        padding: 0;
                    }

                }

            </style>

        </head>


        <body>

            ${reportContent}

        </body>

        </html>

    `);


    reportWindow.document.close();


    reportWindow.focus();


    setTimeout(
        function () {

            reportWindow.print();

        },
        500
    );

}

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

require_once
    __DIR__ .
    "/admin_include/admin_header.php";

?>