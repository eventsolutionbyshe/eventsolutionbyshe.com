document.addEventListener("DOMContentLoaded", function () {

    "use strict";


    /* =========================================================
       HELPERS
    ========================================================= */

    function getElement(id) {
        return document.getElementById(id);
    }


    function setText(id, value) {

        const element =
            getElement(id);

        if (!element) {
            return;
        }

        element.textContent =
            value === null ||
            value === undefined ||
            value === ""
                ? "—"
                : value;
    }


    function openModal(modal) {

        if (!modal) {
            return;
        }

        modal.classList.add("active");

        modal.setAttribute(
            "aria-hidden",
            "false"
        );

        document.body.classList.add(
            "modal-open"
        );
    }


    function closeModal(modal) {

        if (!modal) {
            return;
        }

        modal.classList.remove("active");

        modal.setAttribute(
            "aria-hidden",
            "true"
        );

        /*
         * Only remove modal-open if no other
         * supported modal is currently open.
         */

        const openModals =
            document.querySelectorAll(
                ".booking-modal.active, " +
                ".guest-create-modal.active, " +
                ".guest-token-modal.active"
            );

        if (openModals.length === 0) {

            document.body.classList.remove(
                "modal-open"
            );
        }
    }


    /* =========================================================
       BOOKING DETAILS MODAL
    ========================================================= */

    const bookingModal =
        getElement(
            "bookingModal"
        );


    const bookingModalClose =
        getElement(
            "bookingModalClose"
        );


    const bookingModalCancel =
        getElement(
            "bookingModalCancel"
        );


    const bookingStatusForm =
        getElement(
            "bookingStatusForm"
        );


    const bookingStatusBookingId =
        getElement(
            "bookingStatusBookingId"
        );


    const bookingSelectedStatus =
        getElement(
            "bookingSelectedStatus"
        );


    const bookingStatusConfirm =
        getElement(
            "bookingStatusConfirm"
        );


    const bookingStatusOptions =
        document.querySelectorAll(
            "[data-status-option]"
        );


    let currentBookingStatus =
        "";


    let selectedBookingStatus =
        "";


    /* =========================================================
       OPEN BOOKING DETAILS
    ========================================================= */

    document
        .querySelectorAll(
            ".btn-booking-view"
        )
        .forEach(function (button) {

            button.addEventListener(
                "click",
                function () {

                    const data =
                        button.dataset;


                    /*
                     * BOOKING INFORMATION
                     */

                    setText(
                        "detailBookingId",
                        data.bookingId
                    );


                    setText(
                        "detailUserId",
                        data.userId
                    );


                    setText(
                        "detailEventName",
                        data.eventName
                    );


                    setText(
                        "detailEventType",
                        data.eventType
                    );


                    setText(
                        "detailPackage",
                        data.packageName
                    );


                    setText(
                        "detailEventDate",
                        data.eventDate
                    );


                    setText(
                        "detailStartTime",
                        data.startTime
                    );


                    setText(
                        "detailPhone",
                        data.phone
                    );


                    /*
                     * VENUE
                     */

                    const venueElement =
                        getElement(
                            "detailVenue"
                        );


                    if (venueElement) {

                        let venueHTML =
                            "";


                        if (
                            data.venueName &&
                            data.venueName.trim() !== ""
                        ) {

                            venueHTML =
                                escapeHtml(
                                    data.venueName
                                );


                            if (
                                data.venueLocation &&
                                data.venueLocation.trim() !== ""
                            ) {

                                venueHTML +=
                                    "<br>" +
                                    "<small>" +
                                    escapeHtml(
                                        data.venueLocation
                                    ) +
                                    "</small>";
                            }

                        } else {

                            venueHTML =
                                "No venue";
                        }


                        venueElement.innerHTML =
                            venueHTML;
                    }


                    /*
                     * GUESTS
                     */

                    let guestText =
                        data.guestCount || "0";


                    if (
                        data.invitationGuestCount &&
                        parseInt(
                            data.invitationGuestCount,
                            10
                        ) > 0
                    ) {

                        guestText +=
                            " / " +
                            data.invitationGuestCount +
                            " invited";
                    }


                    setText(
                        "detailGuests",
                        guestText
                    );


                    /*
                     * WEDDING INFORMATION
                     */

                    const weddingDetail =
                        getElement(
                            "weddingDetail"
                        );


                    const churchDetail =
                        getElement(
                            "churchDetail"
                        );


                    const bride =
                        data.brideName
                            ? data.brideName.trim()
                            : "";


                    const groom =
                        data.groomName
                            ? data.groomName.trim()
                            : "";


                    const church =
                        data.church
                            ? data.church.trim()
                            : "";


                    if (
                        bride !== "" ||
                        groom !== ""
                    ) {

                        setText(
                            "detailCouple",
                            bride +
                            (
                                bride !== "" &&
                                groom !== ""
                                    ? " & "
                                    : ""
                            ) +
                            groom
                        );


                        if (weddingDetail) {

                            weddingDetail.style.display =
                                "";
                        }

                    } else {

                        if (weddingDetail) {

                            weddingDetail.style.display =
                                "none";
                        }
                    }


                    if (church !== "") {

                        setText(
                            "detailChurch",
                            church
                        );


                        if (churchDetail) {

                            churchDetail.style.display =
                                "";
                        }

                    } else {

                        if (churchDetail) {

                            churchDetail.style.display =
                                "none";
                        }
                    }


                    /*
                     * SPECIAL REQUEST
                     */

                    const specialRequestDetail =
                        getElement(
                            "specialRequestDetail"
                        );


                    const specialRequest =
                        data.specialRequest
                            ? data.specialRequest.trim()
                            : "";


                    if (
                        specialRequest !== ""
                    ) {

                        setText(
                            "detailSpecialRequest",
                            specialRequest
                        );


                        if (
                            specialRequestDetail
                        ) {

                            specialRequestDetail.style.display =
                                "";
                        }

                    } else {

                        if (
                            specialRequestDetail
                        ) {

                            specialRequestDetail.style.display =
                                "none";
                        }
                    }


                    /*
                     * CREATED
                     */

                    setText(
                        "detailCreatedAt",
                        data.createdAt
                    );


                    /*
                     * GUEST LIST STATUS
                     */

                    const guestListStatus =
                        getElement(
                            "detailGuestListStatus"
                        );


                    if (guestListStatus) {

                        if (
                            data.token &&
                            data.token.trim() !== ""
                        ) {

                            guestListStatus.innerHTML =
                                '<span class="has-token">' +
                                '<i class="fa-solid fa-circle-check"></i> ' +
                                "Available" +
                                "</span>";

                        } else {

                            guestListStatus.innerHTML =
                                '<span class="no-token">' +
                                '<i class="fa-solid fa-circle-xmark"></i> ' +
                                "Not Created" +
                                "</span>";
                        }
                    }


                    /*
                     * STATUS
                     */

                    currentBookingStatus =
                        (
                            data.status ||
                            "pending"
                        ).toLowerCase();


                    selectedBookingStatus =
                        currentBookingStatus;


                    if (
                        bookingStatusBookingId
                    ) {

                        bookingStatusBookingId.value =
                            data.bookingId || "";
                    }


                    updateStatusSelection(
                        currentBookingStatus
                    );


                    updateStatusButton();


                    openModal(
                        bookingModal
                    );
                }
            );
        });


    /* =========================================================
       STATUS OPTION CLICK
    ========================================================= */

    bookingStatusOptions.forEach(
        function (button) {

            button.addEventListener(
                "click",
                function () {

                    const newStatus =
                        (
                            button.dataset.statusOption ||
                            ""
                        ).toLowerCase();


                    if (
                        newStatus === ""
                    ) {
                        return;
                    }


                    selectedBookingStatus =
                        newStatus;


                    updateStatusSelection(
                        newStatus
                    );


                    updateStatusButton();
                }
            );
        }
    );


    /* =========================================================
       UPDATE STATUS SELECTION
    ========================================================= */

    function updateStatusSelection(
        selectedStatus
    ) {

        bookingStatusOptions.forEach(
            function (button) {

                const status =
                    (
                        button.dataset.statusOption ||
                        ""
                    ).toLowerCase();


                if (
                    status === selectedStatus
                ) {

                    button.classList.add(
                        "active"
                    );

                    button.setAttribute(
                        "aria-pressed",
                        "true"
                    );

                } else {

                    button.classList.remove(
                        "active"
                    );

                    button.setAttribute(
                        "aria-pressed",
                        "false"
                    );
                }
            }
        );


        if (
            bookingSelectedStatus
        ) {

            bookingSelectedStatus.value =
                selectedStatus;
        }
    }


    /* =========================================================
       UPDATE STATUS BUTTON
    ========================================================= */

    function updateStatusButton() {

        if (
            !bookingStatusConfirm
        ) {
            return;
        }


        const changed =
            selectedBookingStatus !==
            currentBookingStatus;


        bookingStatusConfirm.disabled =
            !changed;


        if (changed) {

            bookingStatusConfirm.classList.add(
                "ready"
            );

        } else {

            bookingStatusConfirm.classList.remove(
                "ready"
            );
        }
    }


    /* =========================================================
       STATUS FORM SUBMIT
    ========================================================= */

    if (bookingStatusForm) {

        bookingStatusForm.addEventListener(
            "submit",
            function (event) {

                if (
                    selectedBookingStatus ===
                    currentBookingStatus
                ) {

                    event.preventDefault();

                    return;
                }


                if (
                    !selectedBookingStatus
                ) {

                    event.preventDefault();

                    return;
                }


                /*
                 * Prevent accidental double submit.
                 */

                if (
                    bookingStatusConfirm
                ) {

                    bookingStatusConfirm.disabled =
                        true;


                    bookingStatusConfirm.innerHTML =
                        '<i class="fa-solid fa-spinner fa-spin"></i> ' +
                        "Updating...";
                }
            }
        );
    }


    /* =========================================================
       CLOSE BOOKING MODAL
    ========================================================= */

    if (bookingModalClose) {

        bookingModalClose.addEventListener(
            "click",
            function () {

                closeModal(
                    bookingModal
                );
            }
        );
    }


    if (bookingModalCancel) {

        bookingModalCancel.addEventListener(
            "click",
            function () {

                closeModal(
                    bookingModal
                );
            }
        );
    }


    document
        .querySelectorAll(
            "[data-close-booking-modal]"
        )
        .forEach(function (element) {

            element.addEventListener(
                "click",
                function () {

                    closeModal(
                        bookingModal
                    );
                }
            );
        });


    /* =========================================================
       GUEST CREATE MODAL
    ========================================================= */

    const guestCreateModal =
        getElement(
            "guestCreateModal"
        );


    const guestCreateClose =
        getElement(
            "guestCreateClose"
        );


    const guestCreateCancel =
        getElement(
            "guestCreateCancel"
        );


    const guestCreateForm =
        getElement(
            "guestCreateForm"
        );


    const guestCreateBookingId =
        getElement(
            "guestCreateBookingId"
        );


    const guestCreateGuestCount =
        getElement(
            "guestCreateGuestCount"
        );


    const guestCreateCount =
        getElement(
            "guestCreateCount"
        );


    const guestCreateGenerate =
        getElement(
            "guestCreateGenerate"
        );


    const guestCreateEventName =
        getElement(
            "guestCreateEventName"
        );


    /* =========================================================
       OPEN GUEST CREATE MODAL
    ========================================================= */

    document
        .querySelectorAll(
            "[data-create-guest-link]"
        )
        .forEach(function (button) {

            button.addEventListener(
                "click",
                function () {

                    const bookingId =
                        button.dataset.bookingId ||
                        "";


                    const eventName =
                        button.dataset.eventName ||
                        "Event";


                    let guestCount =
                        parseInt(
                            button.dataset.guestCount ||
                            "0",
                            10
                        );


                    if (
                        Number.isNaN(
                            guestCount
                        ) ||
                        guestCount < 1
                    ) {

                        guestCount =
                            1;
                    }


                    if (
                        guestCreateBookingId
                    ) {

                        guestCreateBookingId.value =
                            bookingId;
                    }


                    if (
                        guestCreateEventName
                    ) {

                        guestCreateEventName.textContent =
                            eventName;
                    }


                    if (
                        guestCreateCount
                    ) {

                        guestCreateCount.value =
                            guestCount;
                    }


                    updateGuestCreateButton();


                    openModal(
                        guestCreateModal
                    );


                    setTimeout(
                        function () {

                            if (
                                guestCreateCount
                            ) {

                                guestCreateCount.focus();

                                guestCreateCount.select();
                            }

                        },
                        100
                    );
                }
            );
        });


    /* =========================================================
       GUEST COUNT VALIDATION
    ========================================================= */

    if (guestCreateCount) {

        guestCreateCount.addEventListener(
            "input",
            function () {

                updateGuestCreateButton();
            }
        );


        guestCreateCount.addEventListener(
            "change",
            function () {

                updateGuestCreateButton();
            }
        );
    }


    function updateGuestCreateButton() {

        if (
            !guestCreateCount ||
            !guestCreateGenerate
        ) {
            return;
        }


        let value =
            parseInt(
                guestCreateCount.value,
                10
            );


        const valid =
            !Number.isNaN(value) &&
            value >= 1 &&
            value <= 10000;


        guestCreateGenerate.disabled =
            !valid;


        if (valid) {

            guestCreateGenerate.classList.add(
                "ready"
            );

        } else {

            guestCreateGenerate.classList.remove(
                "ready"
            );
        }
    }


    /* =========================================================
       GUEST CREATE SUBMIT
    ========================================================= */

    if (guestCreateForm) {

        guestCreateForm.addEventListener(
            "submit",
            function (event) {

                const value =
                    parseInt(
                        guestCreateCount
                            ? guestCreateCount.value
                            : "0",
                        10
                    );


                if (
                    Number.isNaN(value) ||
                    value < 1 ||
                    value > 10000
                ) {

                    event.preventDefault();

                    if (
                        guestCreateCount
                    ) {

                        guestCreateCount.focus();
                    }

                    return;
                }


                if (
                    guestCreateGuestCount
                ) {

                    guestCreateGuestCount.value =
                        value;
                }


                if (
                    guestCreateGenerate
                ) {

                    guestCreateGenerate.disabled =
                        true;


                    guestCreateGenerate.innerHTML =
                        '<i class="fa-solid fa-spinner fa-spin"></i> ' +
                        "Generating...";
                }
            }
        );
    }


    /* =========================================================
       CLOSE GUEST CREATE MODAL
    ========================================================= */

    if (guestCreateClose) {

        guestCreateClose.addEventListener(
            "click",
            function () {

                closeModal(
                    guestCreateModal
                );
            }
        );
    }


    if (guestCreateCancel) {

        guestCreateCancel.addEventListener(
            "click",
            function () {

                closeModal(
                    guestCreateModal
                );
            }
        );
    }


    document
        .querySelectorAll(
            "[data-close-guest-create]"
        )
        .forEach(function (element) {

            element.addEventListener(
                "click",
                function () {

                    closeModal(
                        guestCreateModal
                    );
                }
            );
        });


    /* =========================================================
       GUEST TOKEN MODAL
    ========================================================= */

    const guestTokenModal =
        getElement(
            "guestTokenModal"
        );


    const guestTokenClose =
        getElement(
            "guestTokenClose"
        );


    const guestTokenModalCancel =
        getElement(
            "guestTokenModalCancel"
        );


    const guestTokenValue =
        getElement(
            "guestTokenValue"
        );


    const guestTokenCopy =
        getElement(
            "guestTokenCopy"
        );


    const guestListOpen =
        getElement(
            "guestListOpen"
        );


    /* =========================================================
       TOKEN MODAL SUPPORT
    ========================================================= */

    function openGuestToken(
        token,
        bookingId
    ) {

        if (!guestTokenModal) {
            return;
        }


        if (guestTokenValue) {

            guestTokenValue.value =
                token || "";
        }


        if (guestListOpen) {

            if (token) {

                guestListOpen.href =
                    "admin_guestlist.php?token="
                    +
                    encodeURIComponent(
                        token
                    );

            } else {

                guestListOpen.href =
                    "admin_guestlist.php";
            }
        }


        openModal(
            guestTokenModal
        );
    }


    /* =========================================================
       OPEN EXISTING TOKEN
    ========================================================= */

    document
        .querySelectorAll(
            ".btn-booking-guest-list"
        )
        .forEach(function (button) {

            button.addEventListener(
                "contextmenu",
                function () {

                    /*
                     * Normal click remains the original
                     * navigation behavior.
                     */

                }
            );
        });


    /* =========================================================
       COPY TOKEN
    ========================================================= */

    if (guestTokenCopy) {

        guestTokenCopy.addEventListener(
            "click",
            async function () {

                if (
                    !guestTokenValue ||
                    !guestTokenValue.value
                ) {
                    return;
                }


                const token =
                    guestTokenValue.value;


                try {

                    await navigator.clipboard.writeText(
                        token
                    );


                    guestTokenCopy.innerHTML =
                        '<i class="fa-solid fa-check"></i>';


                    setTimeout(
                        function () {

                            guestTokenCopy.innerHTML =
                                '<i class="fa-regular fa-copy"></i>';

                        },
                        1500
                    );

                } catch (error) {

                    guestTokenValue.focus();

                    guestTokenValue.select();

                    document.execCommand(
                        "copy"
                    );
                }
            }
        );
    }


    /* =========================================================
       CLOSE TOKEN MODAL
    ========================================================= */

    if (guestTokenClose) {

        guestTokenClose.addEventListener(
            "click",
            function () {

                closeModal(
                    guestTokenModal
                );
            }
        );
    }


    if (guestTokenModalCancel) {

        guestTokenModalCancel.addEventListener(
            "click",
            function () {

                closeModal(
                    guestTokenModal
                );
            }
        );
    }


    document
        .querySelectorAll(
            "[data-close-token-modal]"
        )
        .forEach(function (element) {

            element.addEventListener(
                "click",
                function () {

                    closeModal(
                        guestTokenModal
                    );
                }
            );
        });


    /* =========================================================
       ESCAPE KEY
    ========================================================= */

    document.addEventListener(
        "keydown",
        function (event) {

            if (
                event.key !== "Escape"
            ) {
                return;
            }


            if (
                bookingModal &&
                bookingModal.classList.contains(
                    "active"
                )
            ) {

                closeModal(
                    bookingModal
                );

                return;
            }


            if (
                guestCreateModal &&
                guestCreateModal.classList.contains(
                    "active"
                )
            ) {

                closeModal(
                    guestCreateModal
                );

                return;
            }


            if (
                guestTokenModal &&
                guestTokenModal.classList.contains(
                    "active"
                )
            ) {

                closeModal(
                    guestTokenModal
                );
            }
        }
    );


    /* =========================================================
       ENTER KEY - GUEST CREATE
    ========================================================= */

    if (guestCreateCount) {

        guestCreateCount.addEventListener(
            "keydown",
            function (event) {

                if (
                    event.key !== "Enter"
                ) {
                    return;
                }


                event.preventDefault();


                if (
                    guestCreateGenerate &&
                    !guestCreateGenerate.disabled
                ) {

                    guestCreateForm.submit();
                }
            }
        );
    }


    /* =========================================================
       TOAST
    ========================================================= */

    const bookingsToast =
        getElement(
            "bookingsToast"
        );


    const bookingsToastClose =
        getElement(
            "bookingsToastClose"
        );


    if (bookingsToastClose) {

        bookingsToastClose.addEventListener(
            "click",
            function () {

                hideToast();
            }
        );
    }


    function hideToast() {

        if (!bookingsToast) {
            return;
        }

        bookingsToast.classList.add(
            "hide"
        );


        setTimeout(
            function () {

                if (
                    bookingsToast
                ) {

                    bookingsToast.remove();
                }

            },
            100
        );
    }


    if (bookingsToast) {

        setTimeout(
            function () {

                hideToast();

            },
            1000
        );
    }


    /* =========================================================
       PRINT REPORT
    ========================================================= */

    const btnBookingsReport =
        getElement(
            "btnBookingsReport"
        );


    if (btnBookingsReport) {

        btnBookingsReport.addEventListener(
            "click",
            function () {

                window.print();
            }
        );
    }


    /* =========================================================
       SEARCH ENTER SUPPORT
    ========================================================= */

    const filterForm =
        getElement(
            "bookingsFilterForm"
        );


    if (filterForm) {

        const searchInput =
            filterForm.querySelector(
                'input[name="search"]'
            );


        if (searchInput) {

            searchInput.addEventListener(
                "keydown",
                function (event) {

                    if (
                        event.key === "Enter"
                    ) {

                        filterForm.submit();
                    }
                }
            );
        }
    }


    /* =========================================================
       HTML ESCAPE
    ========================================================= */

    function escapeHtml(value) {

        if (
            value === null ||
            value === undefined
        ) {

            return "";
        }


        return String(value)
            .replace(
                /&/g,
                "&amp;"
            )
            .replace(
                /</g,
                "&lt;"
            )
            .replace(
                />/g,
                "&gt;"
            )
            .replace(
                /"/g,
                "&quot;"
            )
            .replace(
                /'/g,
                "&#039;"
            );
    }


    /* =========================================================
       BODY CLICK
    ========================================================= */

    document.addEventListener(
        "click",
        function (event) {

            /*
             * Do not accidentally submit forms when
             * clicking disabled buttons.
             */

            const disabledButton =
                event.target.closest(
                    "button[disabled]"
                );


            if (
                disabledButton
            ) {

                event.preventDefault();
            }
        }
    );


    /*
     * Keep function available for future use.
     */

    window.openGuestToken =
        openGuestToken;

}); 