/* =========================================================
   MY HOST PAGE JAVASCRIPT
   Event Solutions by S.H.E.
========================================================= */

document.addEventListener(
    "DOMContentLoaded",
    function () {

        "use strict";


        /* =====================================================
           HELPERS
        ====================================================== */

        const $ = function (
            selector,
            parent
        ) {

            return (
                parent || document
            ).querySelector(
                selector
            );

        };


        const $$ = function (
            selector,
            parent
        ) {

            return Array.from(
                (
                    parent || document
                ).querySelectorAll(
                    selector
                )
            );

        };


        const safeText = function (
            value,
            fallback
        ) {

            if (
                value === null ||
                value === undefined ||
                String(value).trim() === ""
            ) {

                return fallback || "—";

            }

            return String(value);

        };


        const normalizeStatus = function (
            value
        ) {

            return String(
                value || ""
            )
                .trim()
                .toLowerCase();

        };


        /* =====================================================
           ELEMENTS
        ====================================================== */

        const hostModal =
            $("#hostModal");


        const hostModalOverlay =
            $("#hostModalOverlay");


        const closeHostModalButton =
            $("#closeHostModal");


        const hostViewButtons =
            $$(".host-view-button");


        const hostCancelModal =
            $("#hostCancelModal");


        const hostCancelModalOverlay =
            $("#hostCancelModalOverlay");


        const closeHostCancelModalButton =
            $("#closeHostCancelModal");


        const confirmHostCancellation =
            $("#confirmHostCancellation");


        const hostCancellationLoading =
            $("#hostCancellationLoading");


        const cancellationForms =
            $$(".cancel-host-booking-form");


        let pendingCancellationForm =
            null;


        let pendingCancellationButton =
            null;


        /* =====================================================
           BODY SCROLL
        ====================================================== */

        const lockBodyScroll = function () {

            document.body.classList.add(
                "modal-open"
            );

        };


        const unlockBodyScroll = function () {

            if (
                hostModal &&
                hostModal.getAttribute(
                    "aria-hidden"
                ) === "false"
            ) {

                return;

            }


            if (
                hostCancelModal &&
                hostCancelModal.getAttribute(
                    "aria-hidden"
                ) === "false"
            ) {

                return;

            }


            if (
                hostCancellationLoading &&
                hostCancellationLoading.getAttribute(
                    "aria-hidden"
                ) === "false"
            ) {

                return;

            }


            document.body.classList.remove(
                "modal-open"
            );

        };


        /* =====================================================
           HOST VIEW MODAL
        ====================================================== */

        const openHostModal = function (
            button
        ) {

            if (!hostModal || !button) {

                return;

            }


            const hostName =
                button.dataset.hostName
                || "Host";


            const hostType =
                button.dataset.hostType
                || "Host";


            const hostImage =
                button.dataset.hostImage
                || "";


            const hostDescription =
                button.dataset.hostDescription
                || "";


            const hostEmail =
                button.dataset.hostEmail
                || "";


            const hostPhone =
                button.dataset.hostPhone
                || "";


            const eventName =
                button.dataset.eventName
                || "";


            const eventDate =
                button.dataset.eventDate
                || "";


            const startTime =
                button.dataset.startTime
                || "";


            const packageName =
                button.dataset.package
                || "";


            const price =
                button.dataset.price
                || "";


            const address =
                button.dataset.address
                || "";


            const specialRequest =
                button.dataset.specialRequest
                || "";


            const inclusions =
                button.dataset.inclusions
                || "";


            const status =
                normalizeStatus(
                    button.dataset.status
                );


            /* =================================================
               HOST NAME
            ================================================== */

            const modalHostName =
                $("#modalHostName");


            if (modalHostName) {

                modalHostName.textContent =
                    safeText(
                        hostName,
                        "Host"
                    );

            }


            /* =================================================
               HOST TYPE
            ================================================== */

            const modalHostType =
                $("#modalHostType");


            if (modalHostType) {

                modalHostType.textContent =
                    safeText(
                        hostType,
                        "Host"
                    );

            }


            /* =================================================
               DESCRIPTION
            ================================================== */

            const modalHostDescription =
                $("#modalHostDescription");


            if (modalHostDescription) {

                modalHostDescription.textContent =
                    safeText(
                        hostDescription,
                        "No description available."
                    );

            }


            /* =================================================
               EVENT
            ================================================== */

            const modalHostEvent =
                $("#modalHostEvent");


            if (modalHostEvent) {

                modalHostEvent.textContent =
                    safeText(
                        eventName,
                        "—"
                    );

            }


            /* =================================================
               DATE
            ================================================== */

            const modalHostDate =
                $("#modalHostDate");


            if (modalHostDate) {

                if (eventDate) {

                    const dateObject =
                        new Date(
                            eventDate +
                            "T00:00:00"
                        );


                    if (
                        !Number.isNaN(
                            dateObject.getTime()
                        )
                    ) {

                        modalHostDate.textContent =
                            dateObject.toLocaleDateString(
                                "en-US",
                                {
                                    month: "long",
                                    day: "2-digit",
                                    year: "numeric"
                                }
                            );

                    } else {

                        modalHostDate.textContent =
                            eventDate;

                    }

                } else {

                    modalHostDate.textContent =
                        "—";

                }

            }


            /* =================================================
               TIME
            ================================================== */

            const modalHostTime =
                $("#modalHostTime");


            if (modalHostTime) {

                if (startTime) {

                    let timeValue =
                        startTime;


                    if (
                        /^\d{2}:\d{2}(:\d{2})?$/.test(
                            timeValue
                        )
                    ) {

                        const parts =
                            timeValue.split(
                                ":"
                            );


                        let hours =
                            parseInt(
                                parts[0],
                                10
                            );


                        const minutes =
                            parts[1]
                            || "00";


                        const suffix =
                            hours >= 12
                                ? "PM"
                                : "AM";


                        hours =
                            hours % 12
                            || 12;


                        timeValue =
                            hours +
                            ":" +
                            minutes +
                            " " +
                            suffix;

                    }


                    modalHostTime.textContent =
                        timeValue;

                } else {

                    modalHostTime.textContent =
                        "—";

                }

            }


            /* =================================================
               PACKAGE
            ================================================== */

            const modalHostPackage =
                $("#modalHostPackage");


            if (modalHostPackage) {

                modalHostPackage.textContent =
                    safeText(
                        packageName,
                        "—"
                    );

            }


            /* =================================================
               PRICE
            ================================================== */

            const modalHostPrice =
                $("#modalHostPrice");


            if (modalHostPrice) {

                if (
                    price !== null &&
                    price !== undefined &&
                    String(price).trim() !== ""
                ) {

                    modalHostPrice.textContent =
                        "₱" +
                        String(price);

                } else {

                    modalHostPrice.textContent =
                        "—";

                }

            }


            /* =================================================
               STATUS
            ================================================== */

            const modalHostStatus =
                $("#modalHostStatus");


            if (modalHostStatus) {

                modalHostStatus.textContent =
                    status
                        ? (
                            status.charAt(0)
                            .toUpperCase()
                            +
                            status.slice(1)
                        )
                        : "—";

            }


            /* =================================================
               HOST EMAIL
            ================================================== */

            const modalHostEmail =
                $("#modalHostEmail");


            if (modalHostEmail) {

                modalHostEmail.textContent =
                    safeText(
                        hostEmail,
                        "—"
                    );

            }


            /* =================================================
               HOST PHONE
            ================================================== */

            const modalHostPhone =
                $("#modalHostPhone");


            if (modalHostPhone) {

                modalHostPhone.textContent =
                    safeText(
                        hostPhone,
                        "—"
                    );

            }


            /* =================================================
               ADDRESS
            ================================================== */

            const modalHostAddress =
                $("#modalHostAddress");


            if (modalHostAddress) {

                modalHostAddress.textContent =
                    safeText(
                        address,
                        "—"
                    );

            }


            /* =================================================
               INCLUSIONS
            ================================================== */

            const modalHostInclusions =
                $("#modalHostInclusions");


            const modalHostInclusionsWrapper =
                $("#modalHostInclusionsWrapper");


            if (modalHostInclusions) {

                modalHostInclusions.textContent =
                    safeText(
                        inclusions,
                        "No package inclusions available."
                    );

            }


            if (modalHostInclusionsWrapper) {

                modalHostInclusionsWrapper.classList.toggle(
                    "is-empty",
                    !String(
                        inclusions || ""
                    ).trim()
                );

            }


            /* =================================================
               SPECIAL REQUEST
            ================================================== */

            const modalHostSpecialRequest =
                $("#modalHostSpecialRequest");


            const modalHostSpecialRequestWrapper =
                $("#modalHostSpecialRequestWrapper");


            if (modalHostSpecialRequest) {

                modalHostSpecialRequest.textContent =
                    safeText(
                        specialRequest,
                        "None"
                    );

            }


            if (modalHostSpecialRequestWrapper) {

                modalHostSpecialRequestWrapper.classList.toggle(
                    "is-empty",
                    !String(
                        specialRequest || ""
                    ).trim()
                );

            }


            /* =================================================
               HOST IMAGE
            ================================================== */

            const modalHostImage =
                $("#hostModalImage");


            const modalHostImageEmpty =
                $("#hostModalImageEmpty");


            if (modalHostImage) {

                if (hostImage) {

                    modalHostImage.src =
                        hostImage;


                    modalHostImage.alt =
                        hostName || "Host";


                    modalHostImage.style.display =
                        "block";


                    if (modalHostImageEmpty) {

                        modalHostImageEmpty.style.display =
                            "none";

                    }

                } else {

                    modalHostImage.removeAttribute(
                        "src"
                    );


                    modalHostImage.style.display =
                        "none";


                    if (modalHostImageEmpty) {

                        modalHostImageEmpty.style.display =
                            "flex";

                    }

                }

            }


            /* =================================================
               OPEN
            ================================================== */

            hostModal.setAttribute(
                "aria-hidden",
                "false"
            );


            hostModal.classList.add(
                "active"
            );


            lockBodyScroll();

        };


        /* =====================================================
           CLOSE HOST VIEW MODAL
        ====================================================== */

        const closeHostModal = function () {

            if (!hostModal) {

                return;

            }


            hostModal.classList.remove(
                "active"
            );


            hostModal.setAttribute(
                "aria-hidden",
                "true"
            );


            unlockBodyScroll();

        };


        /* =====================================================
           HOST VIEW BUTTONS
        ====================================================== */

        hostViewButtons.forEach(
            function (button) {

                button.addEventListener(
                    "click",
                    function () {

                        openHostModal(
                            button
                        );

                    }
                );

            }
        );


        /* =====================================================
           CLOSE HOST MODAL
        ====================================================== */

        if (closeHostModalButton) {

            closeHostModalButton.addEventListener(
                "click",
                closeHostModal
            );

        }


        if (hostModalOverlay) {

            hostModalOverlay.addEventListener(
                "click",
                closeHostModal
            );

        }


        /* =====================================================
           ESCAPE — HOST MODAL
        ====================================================== */

        document.addEventListener(
            "keydown",
            function (event) {

                if (
                    event.key === "Escape"
                ) {

                    if (
                        hostModal &&
                        hostModal.classList.contains(
                            "active"
                        )
                    ) {

                        closeHostModal();

                        return;

                    }


                    if (
                        hostCancelModal &&
                        hostCancelModal.classList.contains(
                            "active"
                        )
                    ) {

                        closeCancelModal();

                        return;

                    }

                }

            }
        );


        /* =====================================================
           CANCELLATION STATUS
        ====================================================== */

        const isCancellationDisabledStatus =
            function (
                status
            ) {

                const normalizedStatus =
                    normalizeStatus(
                        status
                    );


                return (
                    normalizedStatus === "confirmed" ||
                    normalizedStatus === "completed" ||
                    normalizedStatus === "cancelled"
                );

            };


        /* =====================================================
           UPDATE CANCEL BUTTON STATE
           ONLY PENDING IS ENABLED
        ====================================================== */

        const updateCancelButtonState =
            function (
                form
            ) {

                if (!form) {

                    return;

                }


                const button =
                    $(".host-cancel-button", form);


                if (!button) {

                    return;

                }


                const row =
                    form.closest(
                        ".host-booking-row"
                    );


                let status =
                    form.dataset.status
                    || button.dataset.status
                    || (
                        row
                            ? row.dataset.status
                            : ""
                    );


                status =
                    normalizeStatus(
                        status
                    );


                /*
                |--------------------------------------------------------------------------
                | ONLY PENDING CAN CANCEL
                |--------------------------------------------------------------------------
                */

                const canCancel =
                    status === "pending";


                button.disabled =
                    !canCancel;


                button.dataset.status =
                    status;


                form.dataset.status =
                    status;


                if (!canCancel) {

                    button.classList.add(
                        "disabled"
                    );


                    button.setAttribute(
                        "aria-disabled",
                        "true"
                    );


                    if (
                        status === "confirmed"
                    ) {

                        button.title =
                            "A confirmed booking cannot be cancelled.";

                    } else if (
                        status === "completed"
                    ) {

                        button.title =
                            "A completed booking cannot be cancelled.";

                    } else if (
                        status === "cancelled"
                    ) {

                        button.title =
                            "This booking has already been cancelled.";

                    } else {

                        button.title =
                            "This booking cannot be cancelled.";

                    }

                } else {

                    button.classList.remove(
                        "disabled"
                    );


                    button.removeAttribute(
                        "aria-disabled"
                    );


                    button.title =
                        "Cancel host booking";

                }

            };


        /* =====================================================
           INITIAL CANCEL BUTTON STATES
        ====================================================== */

        cancellationForms.forEach(
            function (form) {

                updateCancelButtonState(
                    form
                );

            }
        );


        /* =====================================================
           OPEN CANCEL MODAL
        ====================================================== */

        const openCancelModal =
            function (
                form,
                button
            ) {

                if (
                    !hostCancelModal ||
                    !form ||
                    !button
                ) {

                    return;

                }


                const status =
                    normalizeStatus(
                        form.dataset.status
                        ||
                        button.dataset.status
                        ||
                        ""
                    );


                /*
                |--------------------------------------------------------------------------
                | DO NOT OPEN MODAL FOR CONFIRMED,
                | COMPLETED OR CANCELLED
                |--------------------------------------------------------------------------
                */

                if (
                    isCancellationDisabledStatus(
                        status
                    )
                ) {

                    return;

                }


                /*
                |--------------------------------------------------------------------------
                | ONLY PENDING
                |--------------------------------------------------------------------------
                */

                if (
                    status !== "pending"
                ) {

                    return;

                }


                pendingCancellationForm =
                    form;


                pendingCancellationButton =
                    button;


                const hostName =
                    button.dataset.hostName
                    || "this host";


                const eventName =
                    button.dataset.eventName
                    || "this event";


                const cancelHostName =
                    $("#cancelHostName");


                const cancelHostEvent =
                    $("#cancelHostEvent");


                if (cancelHostName) {

                    cancelHostName.textContent =
                        hostName;

                }


                if (cancelHostEvent) {

                    cancelHostEvent.textContent =
                        eventName;

                }


                /*
                |--------------------------------------------------------------------------
                | RESET CONFIRM BUTTON
                |--------------------------------------------------------------------------
                */

                if (
                    confirmHostCancellation
                ) {

                    confirmHostCancellation.disabled =
                        false;


                    confirmHostCancellation.innerHTML =
                        `
                            <span class="material-symbols-outlined">
                                cancel
                            </span>

                            <span>
                                Confirm Cancellation
                            </span>
                        `;

                }


                /*
                |--------------------------------------------------------------------------
                | OPEN
                |--------------------------------------------------------------------------
                */

                hostCancelModal.setAttribute(
                    "aria-hidden",
                    "false"
                );


                hostCancelModal.classList.add(
                    "active"
                );


                lockBodyScroll();

            };


        /* =====================================================
           CLOSE CANCEL MODAL
        ====================================================== */

        const closeCancelModal =
            function () {

                if (!hostCancelModal) {

                    return;

                }


                hostCancelModal.classList.remove(
                    "active"
                );


                hostCancelModal.setAttribute(
                    "aria-hidden",
                    "true"
                );


                /*
                |--------------------------------------------------------------------------
                | IMPORTANT
                |--------------------------------------------------------------------------
                |
                | DO NOT DISABLE THE ORIGINAL CANCEL BUTTON HERE.
                |
                | If the user chooses "Keep Booking",
                | the original button must remain enabled.
                |
                */

                pendingCancellationForm =
                    null;


                pendingCancellationButton =
                    null;


                unlockBodyScroll();

            };


        /* =====================================================
           CANCEL FORM SUBMIT
           ONLY ONE SUBMIT LISTENER
        ====================================================== */

        cancellationForms.forEach(
            function (form) {

                form.addEventListener(
                    "submit",
                    function (event) {

                        event.preventDefault();


                        const button =
                            $(".host-cancel-button", form);


                        if (!button) {

                            return;

                        }


                        const row =
                            form.closest(
                                ".host-booking-row"
                            );


                        const status =
                            normalizeStatus(
                                form.dataset.status
                                ||
                                button.dataset.status
                                ||
                                (
                                    row
                                        ? row.dataset.status
                                        : ""
                                )
                            );


                        /*
                        |--------------------------------------------------------------------------
                        | CONFIRMED / COMPLETED / CANCELLED
                        |--------------------------------------------------------------------------
                        */

                        if (
                            isCancellationDisabledStatus(
                                status
                            )
                        ) {

                            button.disabled =
                                true;

                            button.classList.add(
                                "disabled"
                            );

                            return;

                        }


                        /*
                        |--------------------------------------------------------------------------
                        | ONLY PENDING
                        |--------------------------------------------------------------------------
                        */

                        if (
                            status !== "pending"
                        ) {

                            return;

                        }


                        /*
                        |--------------------------------------------------------------------------
                        | OPEN CONFIRMATION MODAL
                        |--------------------------------------------------------------------------
                        */

                        openCancelModal(
                            form,
                            button
                        );

                    }
                );

            }
        );


        /* =====================================================
           KEEP BOOKING
        ====================================================== */

        if (
            closeHostCancelModalButton
        ) {

            closeHostCancelModalButton.addEventListener(
                "click",
                function () {

                    /*
                    |--------------------------------------------------------------------------
                    | IMPORTANT
                    |--------------------------------------------------------------------------
                    |
                    | Nothing is disabled here.
                    |
                    | The booking remains pending.
                    | The Cancel button remains enabled.
                    |
                    */

                    closeCancelModal();

                }
            );

        }


        /* =====================================================
           CANCEL MODAL OVERLAY
        ====================================================== */

        if (
            hostCancelModalOverlay
        ) {

            hostCancelModalOverlay.addEventListener(
                "click",
                function () {

                    closeCancelModal();

                }
            );

        }


        /* =====================================================
           CONFIRM CANCELLATION
        ====================================================== */

        if (
            confirmHostCancellation
        ) {

            confirmHostCancellation.addEventListener(
                "click",
                function () {

                    if (
                        !pendingCancellationForm
                    ) {

                        closeCancelModal();

                        return;

                    }


                    const form =
                        pendingCancellationForm;


                    const button =
                        pendingCancellationButton;


                    if (!form || !button) {

                        closeCancelModal();

                        return;

                    }


                    const row =
                        form.closest(
                            ".host-booking-row"
                        );


                    const status =
                        normalizeStatus(
                            form.dataset.status
                            ||
                            button.dataset.status
                            ||
                            (
                                row
                                    ? row.dataset.status
                                    : ""
                            )
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | SECURITY CHECK IN JAVASCRIPT
                    |--------------------------------------------------------------------------
                    */

                    if (
                        status !== "pending"
                    ) {

                        closeCancelModal();

                        updateCancelButtonState(
                            form
                        );

                        return;

                    }


                    /*
                    |--------------------------------------------------------------------------
                    | PREVENT DOUBLE CLICK
                    |--------------------------------------------------------------------------
                    */

                    confirmHostCancellation.disabled =
                        true;


                    confirmHostCancellation.innerHTML =
                        `
                            <span class="material-symbols-outlined">
                                sync
                            </span>

                            <span>
                                Processing...
                            </span>
                        `;


                    /*
                    |--------------------------------------------------------------------------
                    | SHOW LOADING POPUP
                    |--------------------------------------------------------------------------
                    */

                    if (
                        hostCancelModal
                    ) {

                        hostCancelModal.classList.remove(
                            "active"
                        );


                        hostCancelModal.setAttribute(
                            "aria-hidden",
                            "true"
                        );

                    }


                    if (
                        hostCancellationLoading
                    ) {

                        hostCancellationLoading.setAttribute(
                            "aria-hidden",
                            "false"
                        );


                        hostCancellationLoading.classList.add(
                            "active"
                        );

                    }


                    lockBodyScroll();


                    /*
                    |--------------------------------------------------------------------------
                    | DISABLE ORIGINAL BUTTON ONLY AFTER
                    | USER HAS ACTUALLY CONFIRMED
                    |--------------------------------------------------------------------------
                    */

                    button.disabled =
                        true;


                    button.classList.add(
                        "disabled"
                    );


                    button.setAttribute(
                        "aria-disabled",
                        "true"
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | SUBMIT FORM
                    |--------------------------------------------------------------------------
                    */

                    setTimeout(
                        function () {

                            HTMLFormElement.prototype.submit.call(
                                form
                            );

                        },
                        150
                    );

                }
            );

        }


        /* =====================================================
           FILTER FORM
        ====================================================== */

        const hostFilterForm =
            $("#hostFilterForm");


        if (hostFilterForm) {

            hostFilterForm.addEventListener(
                "submit",
                function () {

                    const submitButton =
                        $(
                            'button[type="submit"]',
                            hostFilterForm
                        );


                    if (submitButton) {

                        submitButton.disabled =
                            true;


                        submitButton.classList.add(
                            "is-loading"
                        );

                    }

                }
            );

        }


        /* =====================================================
           SEARCH INPUT
        ====================================================== */

        const hostSearch =
            $("#hostSearch");


        if (hostSearch) {

            hostSearch.addEventListener(
                "keydown",
                function (event) {

                    if (
                        event.key === "Escape"
                    ) {

                        hostSearch.value =
                            "";

                    }

                }
            );

        }


        /* =====================================================
           STATUS FILTER
        ====================================================== */

        const hostStatus =
            $("#hostStatus");


        if (hostStatus) {

            hostStatus.addEventListener(
                "change",
                function () {

                    const selectedStatus =
                        normalizeStatus(
                            hostStatus.value
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | VISUAL TABLE STATE
                    |--------------------------------------------------------------------------
                    */

                    $$(".host-booking-row")
                        .forEach(
                            function (row) {

                                const rowStatus =
                                    normalizeStatus(
                                        row.dataset.status
                                    );


                                if (
                                    !selectedStatus ||
                                    rowStatus ===
                                        selectedStatus
                                ) {

                                    row.classList.remove(
                                        "status-filter-hidden"
                                    );

                                }

                            }
                        );

                }
            );

        }


        /* =====================================================
           UPDATE BUTTONS AFTER BACK/FORWARD CACHE
        ====================================================== */

        window.addEventListener(
            "pageshow",
            function () {

                cancellationForms.forEach(
                    function (form) {

                        updateCancelButtonState(
                            form
                        );

                    }
                );


                if (
                    hostCancellationLoading
                ) {

                    hostCancellationLoading.classList.remove(
                        "active"
                    );


                    hostCancellationLoading.setAttribute(
                        "aria-hidden",
                        "true"
                    );

                }


                if (
                    hostCancelModal
                ) {

                    hostCancelModal.classList.remove(
                        "active"
                    );


                    hostCancelModal.setAttribute(
                        "aria-hidden",
                        "true"
                    );

                }


                unlockBodyScroll();

            }
        );


        /* =====================================================
           PREVENT ENTER KEY FROM BYPASSING CONFIRMATION
        ====================================================== */

        cancellationForms.forEach(
            function (form) {

                form.addEventListener(
                    "keydown",
                    function (event) {

                        if (
                            event.key !== "Enter"
                        ) {

                            return;

                        }


                        const button =
                            $(".host-cancel-button", form);


                        if (!button) {

                            return;

                        }


                        const status =
                            normalizeStatus(
                                form.dataset.status
                                ||
                                button.dataset.status
                                ||
                                ""
                            );


                        if (
                            status !== "pending"
                        ) {

                            event.preventDefault();

                            return;

                        }


                        /*
                        |--------------------------------------------------------------------------
                        | Allow the submit event to open the modal.
                        |--------------------------------------------------------------------------
                        */

                    }
                );

            }
        );


        /* =====================================================
           INITIALIZATION
        ====================================================== */

        cancellationForms.forEach(
            function (form) {

                updateCancelButtonState(
                    form
                );

            }
        );


        /* =====================================================
           DEBUG STATUS CHECK
           REMOVE LATER IF NOT NEEDED
        ====================================================== */

        cancellationForms.forEach(
            function (form) {

                const button =
                    $(".host-cancel-button", form);


                if (!button) {

                    return;

                }


                const row =
                    form.closest(
                        ".host-booking-row"
                    );


                const status =
                    normalizeStatus(
                        form.dataset.status
                        ||
                        button.dataset.status
                        ||
                        (
                            row
                                ? row.dataset.status
                                : ""
                        )
                    );


                if (
                    status === "confirmed" ||
                    status === "completed" ||
                    status === "cancelled"
                ) {

                    button.disabled =
                        true;


                    button.classList.add(
                        "disabled"
                    );

                }

            }
        );

    }
);