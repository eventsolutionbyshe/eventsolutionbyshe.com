/* =========================================================
   MY EVENT PAGE JAVASCRIPT
   Event Solutions by S.H.E.
========================================================= */

document.addEventListener("DOMContentLoaded", function () {

    "use strict";


    /* =====================================================
       HELPERS
    ====================================================== */

    const $ = function (selector, parent) {
        return (parent || document).querySelector(selector);
    };


    const $$ = function (selector, parent) {
        return Array.from(
            (parent || document).querySelectorAll(selector)
        );
    };


    const getValue = function (element, fallback) {

        if (!element) {
            return fallback || "";
        }

        return element.value !== undefined
            ? element.value
            : fallback || "";

    };


    const escapeHtml = function (value) {

        if (value === null || value === undefined) {
            return "";
        }

        const div = document.createElement("div");

        div.textContent = String(value);

        return div.innerHTML;

    };


    const capitalize = function (value) {

        if (!value) {
            return "";
        }

        return String(value).charAt(0).toUpperCase() +
            String(value).slice(1);

    };


    const sanitizeClass = function (value) {

        return String(value || "")
            .toLowerCase()
            .replace(/[^a-z0-9_-]/g, "-");

    };


    const formatDate = function (dateString) {

        if (!dateString) {
            return "—";
        }

        const date = new Date(dateString + "T00:00:00");

        if (isNaN(date.getTime())) {
            return dateString;
        }

        return date.toLocaleDateString(
            "en-US",
            {
                year: "numeric",
                month: "long",
                day: "2-digit"
            }
        );

    };


    const formatTime = function (timeString) {

        if (!timeString) {
            return "—";
        }

        let value = String(timeString);

        /*
         * Convert HH:MM:SS / HH:MM to a Date object.
         */

        const parts = value.split(":");

        if (parts.length < 2) {
            return value;
        }

        let hours = parseInt(parts[0], 10);
        let minutes = parseInt(parts[1], 10);

        if (
            isNaN(hours) ||
            isNaN(minutes)
        ) {
            return value;
        }

        const suffix = hours >= 12
            ? "PM"
            : "AM";

        hours = hours % 12;

        if (hours === 0) {
            hours = 12;
        }

        return String(hours).padStart(2, "0") +
            ":" +
            String(minutes).padStart(2, "0") +
            " " +
            suffix;

    };


    /* =====================================================
       BODY MODAL STATE
    ====================================================== */

    function updateBodyModalState() {

        const anyModalOpen = document.querySelector(
            ".event-modal.show, " +
            ".event-modal.active, " +
            ".image-lightbox.show, " +
            ".image-lightbox.active, " +
            ".cancel-modal.show, " +
            ".cancel-modal.active, " +
            ".invitation-modal.show, " +
            ".invitation-modal.active, " +
            ".cancellation-loading.is-active"
        );

        if (anyModalOpen) {

            document.body.classList.add(
                "modal-open"
            );

        } else {

            document.body.classList.remove(
                "modal-open"
            );

        }

    }


    /* =====================================================
       GENERIC MODAL FUNCTIONS
    ====================================================== */

    function openModal(modal) {

        if (!modal) {
            return;
        }


        /*
         * Support both .show and .active.
         */

        modal.classList.add("show");
        modal.classList.add("active");


        modal.setAttribute(
            "aria-hidden",
            "false"
        );


        document.body.classList.add(
            "modal-open"
        );


        updateBodyModalState();

    }


    function closeModal(modal) {

        if (!modal) {
            return;
        }


        /*
         * Remove BOTH modal classes.
         */

        modal.classList.remove("show");
        modal.classList.remove("active");


        modal.setAttribute(
            "aria-hidden",
            "true"
        );


        updateBodyModalState();

    }


    /* =====================================================
       EVENT VIEW MODAL
    ====================================================== */

    const eventModal =
        $("#eventModal");

    const eventModalOverlay =
        $("#eventModalOverlay");

    const closeEventModalButton =
        $("#closeEventModal");


    const eventModalTitle =
        $("#eventModalTitle");

    const modalEventName =
        $("#modalEventName");

    const modalEventType =
        $("#modalEventType");

    const modalEventDate =
        $("#modalEventDate");

    const modalStartTime =
        $("#modalStartTime");

    const modalGuestCount =
        $("#modalGuestCount");

    const modalVenue =
        $("#modalVenue");

    const modalPackage =
        $("#modalPackage");

    const modalStatus =
        $("#modalStatus");

    const modalWeddingDetails =
        $("#modalWeddingDetails");

    const modalChurchWrapper =
        $("#modalChurchWrapper");

    const modalChurch =
        $("#modalChurch");

    const modalSpecialRequestWrapper =
        $("#modalSpecialRequestWrapper");

    const modalSpecialRequest =
        $("#modalSpecialRequest");

    const eventModalImage =
        $("#eventModalImage");

    const eventModalImageWrapper =
        $("#eventModalImageWrapper");

    const eventModalImageEmpty =
        $("#eventModalImageEmpty");


    /* =====================================================
       OPEN EVENT MODAL
    ====================================================== */

    function openEventModal(button) {

        if (!eventModal || !button) {
            return;
        }


        const eventName =
            button.getAttribute(
                "data-event-name"
            ) ||
            "Event";


        const eventType =
            button.getAttribute(
                "data-event-type"
            ) ||
            "Event";


        const eventDate =
            button.getAttribute(
                "data-event-date"
            ) ||
            "";


        const startTime =
            button.getAttribute(
                "data-start-time"
            ) ||
            "";


        const guestCount =
            button.getAttribute(
                "data-guest-count"
            ) ||
            "0";


        const venue =
            button.getAttribute(
                "data-venue"
            ) ||
            "Not specified";


        const packageName =
            button.getAttribute(
                "data-package-name"
            ) ||
            "Not specified";


        const packageImage =
            button.getAttribute(
                "data-package-image"
            ) ||
            "";


        const status =
            button.getAttribute(
                "data-status"
            ) ||
            "pending";


        const bookingType =
            button.getAttribute(
                "data-booking-type"
            ) ||
            "regular";


        const church =
            button.getAttribute(
                "data-church"
            ) ||
            "";


        const specialRequest =
            button.getAttribute(
                "data-special-request"
            ) ||
            "";


        /* =================================================
           EVENT TITLE
        ================================================== */

        if (eventModalTitle) {

            eventModalTitle.textContent =
                eventName;

        }


        if (modalEventName) {

            modalEventName.textContent =
                eventName;

        }


        if (modalEventType) {

            modalEventType.textContent =
                eventType;

        }


        /* =================================================
           DATE
        ================================================== */

        if (modalEventDate) {

            modalEventDate.textContent =
                formatDate(eventDate);

        }


        /* =================================================
           TIME
        ================================================== */

        if (modalStartTime) {

            modalStartTime.textContent =
                formatTime(startTime);

        }


        /* =================================================
           GUESTS
        ================================================== */

        if (modalGuestCount) {

            const number =
                parseInt(
                    guestCount,
                    10
                );


            modalGuestCount.textContent =
                isNaN(number)
                    ? guestCount
                    : number.toLocaleString();

        }


        /* =================================================
           VENUE
        ================================================== */

        if (modalVenue) {

            modalVenue.textContent =
                venue || "Not specified";

        }


        /* =================================================
           PACKAGE
        ================================================== */

        if (modalPackage) {

            modalPackage.textContent =
                packageName || "Not specified";

        }


        /* =================================================
           STATUS
        ================================================== */

        if (modalStatus) {

            modalStatus.textContent =
                capitalize(status);


            modalStatus.className =
                "status-value status-" +
                sanitizeClass(status);

        }


        /* =================================================
           EVENT IMAGE
        ================================================== */

        if (eventModalImage) {

            if (packageImage) {

                eventModalImage.src =
                    packageImage;


                eventModalImage.alt =
                    eventName +
                    " package image";


                eventModalImage.style.display =
                    "block";


                if (eventModalImageEmpty) {

                    eventModalImageEmpty.style.display =
                        "none";

                }

            } else {

                eventModalImage.removeAttribute(
                    "src"
                );


                eventModalImage.style.display =
                    "none";


                if (eventModalImageEmpty) {

                    eventModalImageEmpty.style.display =
                        "flex";

                }

            }

        }


        if (eventModalImageWrapper) {

            eventModalImageWrapper.style.display =
                "block";

        }


        /* =================================================
           WEDDING DETAILS
        ================================================== */

        const isWedding =
            String(bookingType).toLowerCase() ===
            "wedding";


        if (
            isWedding &&
            (
                church ||
                specialRequest
            )
        ) {

            if (modalWeddingDetails) {

                modalWeddingDetails.style.display =
                    "";

            }


            if (modalChurchWrapper) {

                modalChurchWrapper.style.display =
                    church
                        ? ""
                        : "none";

            }


            if (modalChurch) {

                modalChurch.textContent =
                    church || "—";

            }


            if (modalSpecialRequestWrapper) {

                modalSpecialRequestWrapper.style.display =
                    specialRequest
                        ? ""
                        : "none";

            }


            if (modalSpecialRequest) {

                modalSpecialRequest.textContent =
                    specialRequest || "—";

            }

        } else {

            if (modalWeddingDetails) {

                modalWeddingDetails.style.display =
                    "none";

            }

        }


        /* =================================================
           SHOW EVENT MODAL
        ================================================== */

        openModal(
            eventModal
        );

    }


    /* =====================================================
       VIEW BUTTONS
    ====================================================== */

    $$(".view-button").forEach(
        function (button) {

            button.addEventListener(
                "click",
                function (event) {

                    event.preventDefault();


                    openEventModal(
                        button
                    );

                }
            );

        }
    );


    /* =====================================================
       CLOSE EVENT MODAL
    ====================================================== */

    if (closeEventModalButton) {

        closeEventModalButton.addEventListener(
            "click",
            function (event) {

                event.preventDefault();


                closeModal(
                    eventModal
                );

            }
        );

    }


    if (eventModalOverlay) {

        eventModalOverlay.addEventListener(
            "click",
            function (event) {

                if (
                    event.target ===
                    eventModalOverlay
                ) {

                    closeModal(
                        eventModal
                    );

                }

            }
        );

    }


    /* =====================================================
       IMAGE LIGHTBOX
    ====================================================== */

    const imageLightbox =
        $("#imageLightbox");

    const imageLightboxOverlay =
        $("#imageLightboxOverlay");

    const closeImageLightbox =
        $("#closeImageLightbox");

    const lightboxImage =
        $("#lightboxImage");


    function openImageLightbox(
        src,
        alt
    ) {

        if (
            !imageLightbox ||
            !lightboxImage ||
            !src
        ) {

            return;

        }


        lightboxImage.src =
            src;


        lightboxImage.alt =
            alt ||
            "Event image";


        openModal(
            imageLightbox
        );

    }


    function closeImageLightboxModal() {

        closeModal(
            imageLightbox
        );

    }


    if (eventModalImage) {

        eventModalImage.addEventListener(
            "click",
            function () {

                if (
                    eventModalImage.src &&
                    eventModalImage.style.display !==
                    "none"
                ) {

                    openImageLightbox(
                        eventModalImage.src,
                        eventModalImage.alt
                    );

                }

            }
        );

    }


    if (closeImageLightbox) {

        closeImageLightbox.addEventListener(
            "click",
            function (event) {

                event.preventDefault();


                closeImageLightboxModal();

            }
        );

    }


    if (imageLightboxOverlay) {

        imageLightboxOverlay.addEventListener(
            "click",
            function (event) {

                if (
                    event.target ===
                    imageLightboxOverlay
                ) {

                    closeImageLightboxModal();

                }

            }
        );

    }


    /* =====================================================
       CANCEL BOOKING MODAL
    ====================================================== */

    const cancelModal =
        $("#cancelModal");

    const cancelModalOverlay =
        $("#cancelModalOverlay");

    const cancelEventName =
        $("#cancelEventName");

    const closeCancelModalButton =
        $("#closeCancelModal");

    const confirmCancelBooking =
        $("#confirmCancelBooking");


    /*
     * Cancellation loading popup.
     *
     * This popup is displayed after the user confirms
     * cancellation and before the form is submitted.
     *
     * PHP will then:
     *
     * 1. Update the booking.
     * 2. Send the administrator email.
     * 3. Commit only when the email succeeds.
     *
     * If the email fails, PHP rolls the transaction back.
     */

    const cancellationLoading =
        $("#cancellationLoading");


    /*
     * Store the original cancellation form.
     */

    let selectedCancelForm =
        null;


    /*
     * This flag is important.
     *
     * When true, the form has already been
     * confirmed and is being submitted.
     *
     * It prevents the normal submit listener
     * from opening the confirmation modal again.
     */

    let isCancellingBooking =
        false;


    /* =====================================================
       OPEN CANCELLATION LOADING POPUP
    ====================================================== */

    function openCancellationLoading() {

        if (!cancellationLoading) {
            return;
        }


        /*
         * Show loading popup.
         */

        cancellationLoading.classList.add(
            "is-active"
        );


        cancellationLoading.setAttribute(
            "aria-hidden",
            "false"
        );


        /*
         * Prevent the page behind the popup
         * from being interacted with.
         */

        document.body.classList.add(
            "modal-open"
        );


        updateBodyModalState();

    }


    /* =====================================================
       CLOSE CANCELLATION LOADING POPUP
    ====================================================== */

    function closeCancellationLoading() {

        if (!cancellationLoading) {
            return;
        }


        cancellationLoading.classList.remove(
            "is-active"
        );


        cancellationLoading.setAttribute(
            "aria-hidden",
            "true"
        );


        updateBodyModalState();

    }


    /* =====================================================
       OPEN CANCEL MODAL
    ====================================================== */

    function openCancelModal(form) {

        if (!cancelModal) {
            return;
        }


        /*
         * Do not allow another cancellation
         * while the current cancellation is
         * being processed.
         */

        if (isCancellingBooking) {
            return;
        }


        /*
         * Store the form.
         */

        selectedCancelForm =
            form || null;


        /* =================================================
           GET EVENT NAME
        ================================================== */

        let eventName =
            "this event";


        if (form) {

            const button =
                form.querySelector(
                    ".cancel-button"
                );


            if (button) {

                eventName =
                    button.getAttribute(
                        "data-event-name"
                    ) ||
                    "this event";

            }

        }


        /* =================================================
           DISPLAY EVENT NAME
        ================================================== */

        if (cancelEventName) {

            cancelEventName.textContent =
                eventName;

        }


        /* =================================================
           RESET CONFIRM BUTTON
        ================================================== */

        if (confirmCancelBooking) {

            confirmCancelBooking.disabled =
                false;


            confirmCancelBooking.classList.remove(
                "loading"
            );


            confirmCancelBooking.innerHTML =
                `
                    <span class="material-symbols-outlined">
                        cancel
                    </span>

                    <span>
                        Confirm Cancellation
                    </span>
                `;

        }


        /* =================================================
           MAKE SURE LOADING IS HIDDEN
        ================================================== */

        closeCancellationLoading();


        /* =================================================
           OPEN MODAL
        ================================================== */

        openModal(
            cancelModal
        );


        /* =================================================
           FOCUS KEEP BOOKING
        ================================================== */

        setTimeout(
            function () {

                if (
                    closeCancelModalButton
                ) {

                    closeCancelModalButton.focus();

                }

            },
            50
        );

    }


    /* =====================================================
       CLOSE CANCEL MODAL
    ====================================================== */

    function closeCancelConfirmation() {

        if (!cancelModal) {
            return;
        }


        /*
         * Do not close while cancellation
         * is actively being submitted.
         */

        if (isCancellingBooking) {
            return;
        }


        /* =================================================
           CLOSE MODAL
        ================================================== */

        closeModal(
            cancelModal
        );


        /* =================================================
           CLEAR SELECTED FORM
        ================================================== */

        selectedCancelForm =
            null;


        /* =================================================
           UPDATE BODY STATE
        ================================================== */

        updateBodyModalState();

    }


    /* =====================================================
       CANCEL BOOKING FORMS
    ====================================================== */

    $$(".cancel-booking-form").forEach(
        function (form) {

            form.addEventListener(
                "submit",
                function (event) {

                    /*
                     * IMPORTANT:
                     *
                     * When the user has already confirmed
                     * the cancellation, allow the form
                     * submission to continue.
                     */

                    if (isCancellingBooking) {

                        return;

                    }


                    /*
                     * Normal first click:
                     * stop the form from submitting.
                     */

                    event.preventDefault();

                    event.stopPropagation();


                    /*
                     * Show confirmation modal.
                     */

                    openCancelModal(
                        form
                    );

                }
            );

        }
    );


    /* =====================================================
       CLOSE CANCEL MODAL
    ====================================================== */

    if (closeCancelModalButton) {

        closeCancelModalButton.addEventListener(
            "click",
            function (event) {

                event.preventDefault();

                event.stopPropagation();


                closeCancelConfirmation();

            }
        );

    }


    /* =====================================================
       CANCEL MODAL OVERLAY
    ====================================================== */

    if (cancelModalOverlay) {

        cancelModalOverlay.addEventListener(
            "click",
            function (event) {

                /*
                 * Only close when the actual
                 * overlay is clicked.
                 */

                if (
                    event.target ===
                    cancelModalOverlay
                ) {

                    closeCancelConfirmation();

                }

            }
        );

    }


    /* =====================================================
       CONFIRM CANCELLATION
    ====================================================== */

    if (confirmCancelBooking) {

        confirmCancelBooking.addEventListener(
            "click",
            function (event) {

                event.preventDefault();

                event.stopPropagation();


                /* =================================================
                   MAKE SURE FORM EXISTS
                ================================================== */

                if (
                    !selectedCancelForm
                ) {

                    return;

                }


                /* =================================================
                   PREVENT DOUBLE CLICK
                ================================================== */

                if (
                    isCancellingBooking
                ) {

                    return;

                }


                /* =================================================
                   SET SUBMISSION STATE
                ================================================== */

                isCancellingBooking =
                    true;


                /* =================================================
                   SAVE FORM REFERENCE
                ================================================== */

                const form =
                    selectedCancelForm;


                /* =================================================
                   DISABLE BUTTON
                ================================================== */

                confirmCancelBooking.disabled =
                    true;


                confirmCancelBooking.classList.add(
                    "loading"
                );


                /* =================================================
                   CHANGE BUTTON TEXT
                ================================================== */

                confirmCancelBooking.innerHTML =
                    `
                        <span class="material-symbols-outlined">
                            progress_activity
                        </span>

                        <span>
                            Processing...
                        </span>
                    `;


                /* =================================================
                   CLOSE CONFIRMATION MODAL
                ================================================== */

                /*
                 * Do NOT call closeCancelConfirmation()
                 * here because isCancellingBooking is
                 * intentionally true.
                 */

                closeModal(
                    cancelModal
                );


                updateBodyModalState();


                /* =================================================
                   SHOW CANCELLATION LOADING POPUP
                ================================================== */

                openCancellationLoading();


                /* =================================================
                   SUBMIT ORIGINAL FORM
                ================================================== */

                /*
                 * IMPORTANT:
                 *
                 * Do NOT use:
                 *
                 * form.requestSubmit()
                 *
                 * because requestSubmit() triggers
                 * the submit event again.
                 *
                 * Your submit event opens the
                 * confirmation modal.
                 *
                 * form.submit() directly submits
                 * the form without triggering the
                 * submit event listener.
                 */

                setTimeout(
                    function () {

                        if (
                            form &&
                            typeof form.submit ===
                            "function"
                        ) {

                            form.submit();

                        }

                    },
                    100
                );

            }
        );

    }


    /* =====================================================
       CANCELLATION LOADING OVERLAY
    ====================================================== */

    if (cancellationLoading) {

        const cancellationLoadingOverlay =
            $(".cancellation-loading-overlay", cancellationLoading);


        if (cancellationLoadingOverlay) {

            cancellationLoadingOverlay.addEventListener(
                "click",
                function (event) {

                    /*
                     * IMPORTANT:
                     *
                     * The loading popup must NOT be
                     * closable while the server is
                     * processing the cancellation.
                     */

                    event.preventDefault();

                    event.stopPropagation();

                }
            );

        }

    }


    /* =====================================================
       CANCEL MODAL ADDITIONAL CLOSE BUTTONS
    ====================================================== */

    if (cancelModal) {

        $$(".modal-close", cancelModal)
            .forEach(
                function (button) {

                    button.addEventListener(
                        "click",
                        function (event) {

                            event.preventDefault();

                            event.stopPropagation();


                            if (
                                !isCancellingBooking
                            ) {

                                closeCancelConfirmation();

                            }

                        }
                    );

                }
            );

    }


    /* =====================================================
       CREATE INVITATION MODAL
    ====================================================== */

    const invitationModal =
        $("#invitationModal");

    const invitationModalOverlay =
        $(".invitation-modal-overlay");

    const invitationModalFrame =
        $("#invitationModalFrame");

    const closeInvitationModalButton =
        $("#closeInvitationModal");

    const createInvitationButton =
        $("#createInvitationButton");

    const invitationLoading =
        $(".invitation-loading");


    /* =====================================================
       SELECTED EVENT
    ====================================================== */

    let selectedInvitationCheckbox =
        null;


    /* =====================================================
       GET SELECTED CHECKBOXES
    ====================================================== */

    function getSelectedInvitationCheckboxes() {

        return $$(".event-select:checked");

    }


    /* =====================================================
       UPDATE INVITATION BUTTON
    ====================================================== */

    function updateInvitationButton() {

        if (!createInvitationButton) {
            return;
        }


        const selected =
            getSelectedInvitationCheckboxes();


        /*
         * Only ONE event can be selected.
         */

        if (selected.length === 1) {

            createInvitationButton.disabled =
                false;


            createInvitationButton.classList.add(
                "ready"
            );


            selectedInvitationCheckbox =
                selected[0];

        } else {

            createInvitationButton.disabled =
                true;


            createInvitationButton.classList.remove(
                "ready"
            );


            selectedInvitationCheckbox =
                null;

        }

    }


    /* =====================================================
       EVENT CHECKBOXES
    ====================================================== */

    $$(".event-select").forEach(
        function (checkbox) {

            checkbox.addEventListener(
                "change",
                function () {

                    const checkboxes =
                        getSelectedInvitationCheckboxes();


                    /*
                     * Only allow one checkbox.
                     */

                    if (
                        checkboxes.length >
                        1
                    ) {

                        $$(".event-select")
                            .forEach(
                                function (item) {

                                    if (
                                        item !==
                                        checkbox
                                    ) {

                                        item.checked =
                                            false;

                                    }

                                }
                            );

                    }


                    updateInvitationButton();

                }
            );

        }
    );


    /* =====================================================
       OPEN INVITATION MODAL
    ====================================================== */

    function openInvitationModal() {

        if (
            !invitationModal ||
            !selectedInvitationCheckbox
        ) {

            return;

        }


        const invitationUrl =
            selectedInvitationCheckbox.getAttribute(
                "data-invitation-url"
            );


        if (!invitationUrl) {

            return;

        }


        if (invitationModalFrame) {

            invitationModalFrame.src =
                invitationUrl;

        }


        if (invitationLoading) {

            invitationLoading.style.display =
                "flex";

        }


        openModal(
            invitationModal
        );

    }


    /* =====================================================
       INVITATION IFRAME LOAD
    ====================================================== */

    if (invitationModalFrame) {

        invitationModalFrame.addEventListener(
            "load",
            function () {

                if (
                    invitationLoading
                ) {

                    invitationLoading.style.display =
                        "none";

                }

            }
        );

    }


    /* =====================================================
       CREATE INVITATION BUTTON
    ====================================================== */

    if (createInvitationButton) {

        createInvitationButton.addEventListener(
            "click",
            function (event) {

                event.preventDefault();


                if (
                    createInvitationButton.disabled
                ) {

                    return;

                }


                openInvitationModal();

            }
        );

    }


    /* =====================================================
       CLOSE INVITATION MODAL
    ====================================================== */

    function closeInvitationModal() {

        closeModal(
            invitationModal
        );


        /*
         * Clear iframe after closing.
         */

        setTimeout(
            function () {

                if (
                    invitationModalFrame
                ) {

                    invitationModalFrame.src =
                        "about:blank";

                }


                if (
                    invitationLoading
                ) {

                    invitationLoading.style.display =
                        "flex";

                }

            },
            200
        );

    }


    if (closeInvitationModalButton) {

        closeInvitationModalButton.addEventListener(
            "click",
            function (event) {

                event.preventDefault();


                closeInvitationModal();

            }
        );

    }


    if (invitationModalOverlay) {

        invitationModalOverlay.addEventListener(
            "click",
            function (event) {

                if (
                    event.target ===
                    invitationModalOverlay
                ) {

                    closeInvitationModal();

                }

            }
        );

    }


    /* =====================================================
       GENERIC MODAL CLOSE BUTTONS
    ====================================================== */

    $$(".modal-close").forEach(
        function (button) {

            button.addEventListener(
                "click",
                function (event) {

                    /*
                     * Specific modal handlers already
                     * handle their own buttons.
                     *
                     * This is a fallback.
                     */

                    const modal =
                        button.closest(
                            ".event-modal, " +
                            ".image-lightbox, " +
                            ".cancel-modal, " +
                            ".invitation-modal"
                        );


                    if (!modal) {
                        return;
                    }


                    event.preventDefault();


                    if (
                        modal.id ===
                        "cancelModal"
                    ) {

                        if (
                            !isCancellingBooking
                        ) {

                            closeCancelConfirmation();

                        }

                    } else if (
                        modal.id ===
                        "invitationModal"
                    ) {

                        closeInvitationModal();

                    } else {

                        closeModal(
                            modal
                        );

                    }

                }
            );

        }
    );


    /* =====================================================
       CLICK OUTSIDE DIALOG
    ====================================================== */

    [
        eventModal,
        imageLightbox,
        cancelModal,
        invitationModal
    ].forEach(
        function (modal) {

            if (!modal) {
                return;
            }


            modal.addEventListener(
                "click",
                function (event) {

                    /*
                     * Do not close when clicking
                     * inside the dialog.
                     *
                     * Overlay handlers above handle
                     * the actual outside click.
                     */

                    const dialog =
                        modal.querySelector(
                            '[role="dialog"], ' +
                            ".event-modal-dialog, " +
                            ".image-lightbox-content, " +
                            ".cancel-modal-dialog, " +
                            ".invitation-modal-dialog"
                        );


                    if (
                        dialog &&
                        dialog.contains(
                            event.target
                        )
                    ) {

                        return;

                    }

                }
            );

        }
    );


    /* =====================================================
       ESCAPE KEY
    ====================================================== */

    document.addEventListener(
        "keydown",
        function (event) {

            if (
                event.key !==
                "Escape"
            ) {

                return;

            }


            /* =============================================
               CANCELLATION LOADING
            ============================================== */

            if (
                cancellationLoading &&
                cancellationLoading.classList.contains(
                    "is-active"
                )
            ) {

                /*
                 * Do NOT allow Escape to close the
                 * cancellation loading popup.
                 *
                 * The cancellation request is already
                 * being processed by PHP.
                 */

                event.preventDefault();

                event.stopPropagation();

                return;

            }


            /* =============================================
               CANCEL MODAL FIRST
            ============================================== */

            if (
                cancelModal &&
                (
                    cancelModal.classList.contains(
                        "show"
                    ) ||
                    cancelModal.classList.contains(
                        "active"
                    )
                )
            ) {

                if (
                    !isCancellingBooking
                ) {

                    closeCancelConfirmation();

                }

                return;

            }


            /* =============================================
               INVITATION MODAL
            ============================================== */

            if (
                invitationModal &&
                (
                    invitationModal.classList.contains(
                        "show"
                    ) ||
                    invitationModal.classList.contains(
                        "active"
                    )
                )
            ) {

                closeInvitationModal();

                return;

            }


            /* =============================================
               IMAGE LIGHTBOX
            ============================================== */

            if (
                imageLightbox &&
                (
                    imageLightbox.classList.contains(
                        "show"
                    ) ||
                    imageLightbox.classList.contains(
                        "active"
                    )
                )
            ) {

                closeImageLightboxModal();

                return;

            }


            /* =============================================
               EVENT MODAL
            ============================================== */

            if (
                eventModal &&
                (
                    eventModal.classList.contains(
                        "show"
                    ) ||
                    eventModal.classList.contains(
                        "active"
                    )
                )
            ) {

                closeModal(
                    eventModal
                );

            }

        }
    );


    /* =====================================================
       PREVENT ENTER FROM ACCIDENTALLY SUBMITTING
       CANCEL FORM
    ====================================================== */

    $$(".cancel-booking-form").forEach(
        function (form) {

            form.addEventListener(
                "keydown",
                function (event) {

                    if (
                        event.key ===
                        "Enter"
                    ) {

                        /*
                         * Let actual buttons handle
                         * their own actions.
                         */

                        if (
                            event.target &&
                            event.target.tagName !==
                            "BUTTON"
                        ) {

                            event.preventDefault();

                        }

                    }

                }
            );

        }
    );


    /* =====================================================
       MUTATION OBSERVER
       Helps when table rows are dynamically updated.
    ====================================================== */

    const eventTableBody =
        $(".events-table tbody");


    if (eventTableBody) {

        const observer =
            new MutationObserver(
                function () {

                    updateInvitationButton();

                }
            );


        observer.observe(
            eventTableBody,
            {
                childList: true,
                subtree: true
            }
        );

    }


    /* =====================================================
       INITIAL STATE
    ====================================================== */

    updateInvitationButton();

    updateBodyModalState();


    /* =====================================================
       GLOBAL API
       Useful if other scripts need to open/close modals.
    ====================================================== */

    window.MyEventPage = {

        openEventModal:
            openEventModal,

        openCancelModal:
            openCancelModal,

        closeCancelModal:
            closeCancelConfirmation,

        openInvitationModal:
            openInvitationModal,

        closeInvitationModal:
            closeInvitationModal,

        openImageLightbox:
            openImageLightbox,

        closeImageLightbox:
            closeImageLightboxModal,

        openModal:
            openModal,

        closeModal:
            closeModal,

        updateInvitationButton:
            updateInvitationButton,

        openCancellationLoading:
            openCancellationLoading,

        closeCancellationLoading:
            closeCancellationLoading

    };


});