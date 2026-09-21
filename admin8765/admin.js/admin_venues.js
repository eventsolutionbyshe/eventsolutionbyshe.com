/* =====================================================
   ADMIN VENUES JAVASCRIPT
   Event Solutions by S.H.E.
===================================================== */


/* =====================================================
   GLOBAL VARIABLES
===================================================== */

window.pendingVenueStatusForm = null;


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


    if (!popup) {

        return;

    }


    window.pendingVenueStatusForm =
        form;


    if (
        newStatus === "available"
    ) {

        if (title) {

            title.textContent =
                "Make Venue Available?";

        }


        if (message) {

            message.textContent =
                `Are you sure you want to make "${venueName}" available?`;

        }


        if (icon) {

            icon.className =
                "venue-status-popup-icon available";

            icon.innerHTML =
                '<i class="fa-solid fa-circle-check"></i>';

        }


        if (confirmButton) {

            confirmButton.className =
                "venue-status-popup-confirm available";

            confirmButton.innerHTML =
                '<i class="fa-solid fa-circle-check"></i>' +
                '<span>Make Available</span>';

        }

    } else {

        if (title) {

            title.textContent =
                "Make Venue Unavailable?";

        }


        if (message) {

            message.textContent =
                `Are you sure you want to make "${venueName}" unavailable?`;

        }


        if (icon) {

            icon.className =
                "venue-status-popup-icon unavailable";

            icon.innerHTML =
                '<i class="fa-solid fa-ban"></i>';

        }


        if (confirmButton) {

            confirmButton.className =
                "venue-status-popup-confirm unavailable";

            confirmButton.innerHTML =
                '<i class="fa-solid fa-ban"></i>' +
                '<span>Make Unavailable</span>';

        }

    }


    popup.classList.add(
        "show"
    );


    popup.setAttribute(
        "aria-hidden",
        "false"
    );


    document.body.classList.add(
        "venue-popup-open"
    );

}


/* =====================================================
   CONFIRM VENUE STATUS CHANGE
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


    window.pendingVenueStatusForm =
        null;

}


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

        preview.removeAttribute(
            "src"
        );

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

        preview.removeAttribute(
            "src"
        );

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

        preview.removeAttribute(
            "src"
        );

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


    /* External URL */

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


    /* New format */

    if (
        image.indexOf(
            "images/"
        ) === 0
    ) {

        return "../" +
            image;

    }


    /* Legacy uploads */

    if (
        image.indexOf(
            "uploads/"
        ) === 0
    ) {

        return "../" +
            image;

    }


    /* Filename only */

    return "../images/" +
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


    /* No File */

    if (
        !input.files ||
        !input.files[0]
    ) {

        if (preview) {

            preview.removeAttribute(
                "src"
            );

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


    /* =================================================
       VALIDATE TYPE
    ================================================= */

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

            preview.removeAttribute(
                "src"
            );

            preview.style.display =
                "none";

        }


        if (placeholder) {

            placeholder.style.display =
                "flex";

        }


        alert(
            "Invalid image format. Please select JPG, JPEG, PNG, or WEBP."
        );


        return;

    }


    /* =================================================
       VALIDATE SIZE
    ================================================= */

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

            preview.removeAttribute(
                "src"
            );

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


    /* =================================================
       FILE NAME
    ================================================= */

    if (fileText) {

        fileText.textContent =
            file.name;

    }


    /* =================================================
       PREVIEW
    ================================================= */

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

                    font-size: 12px;

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

                    border-bottom:
                        2px solid #222;

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

                    font-size: 11px;

                    white-space: nowrap;

                    text-align: right;

                }


                .report-table {

                    width: 100%;

                    border-collapse: collapse;

                    font-size: 11px;

                }


                .report-table th {

                    padding: 8px;

                    text-align: left;

                    background: #f1f1f1;

                    border:
                        1px solid #ccc;

                    font-weight: 700;

                }


                .report-table td {

                    padding: 7px 8px;

                    border:
                        1px solid #ccc;

                    vertical-align: middle;

                }


                .report-table tr {

                    page-break-inside: avoid;

                }


                @page {

                    size: A4 landscape;

                    margin: 12mm;

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

            reportWindow.close();

        },
        500
    );

}


/* =====================================================
   DOM READY
===================================================== */

document.addEventListener(
    "DOMContentLoaded",
    function () {


        /* =================================================
           ADD VENUE BUTTONS
        ================================================= */

        const addModal =
            document.getElementById(
                "addVenueModal"
            );


        document.querySelectorAll(
            "#btnOpenAddVenue, #btnOpenAddVenueEmpty"
        ).forEach(
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


        /* =================================================
           EDIT BUTTONS
        ================================================= */

        document.querySelectorAll(
            ".btn-edit-venue"
        ).forEach(
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


        /* =================================================
           CLOSE MODALS
        ================================================= */

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


        /* =================================================
           CLOSE MODAL OVERLAY
        ================================================= */

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


        /* =================================================
           ADD IMAGE PREVIEW
        ================================================= */

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


        /* =================================================
           EDIT IMAGE PREVIEW
        ================================================= */

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


        /* =================================================
           ADD FORM VALIDATION
        ================================================= */

        const addForm =
            document.getElementById(
                "addVenueForm"
            );


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


        /* =================================================
           EDIT FORM VALIDATION
        ================================================= */

        const editForm =
            document.getElementById(
                "editVenueForm"
            );


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


        /* =================================================
           STATUS POPUP
        ================================================= */

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


                    form.submit();

                }
            );

        }


        /* =================================================
           STATUS FILTER
        ================================================= */

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


        /* =================================================
           SEARCH ENTER
        ================================================= */

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


        /* =================================================
           REPORT
        ================================================= */

        const reportButton =
            document.getElementById(
                "btnVenueReport"
            );


        if (reportButton) {

            reportButton.addEventListener(
                "click",
                printVenueReport
            );

        }


        /* =================================================
           ESCAPE KEY
        ================================================= */

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


    }
);