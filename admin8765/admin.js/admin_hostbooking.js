/* =========================================================
   ADMIN HOST BOOKINGS
========================================================= */

document.addEventListener("DOMContentLoaded", function () {

    "use strict";


    /* =====================================================
       ELEMENTS
    ===================================================== */

    const statusModal =
        document.getElementById("hostBookingStatusModal");

    const statusClose =
        document.getElementById("hostBookingStatusClose");

    const statusCancel =
        document.getElementById("hostBookingStatusCancel");

    const statusForm =
        document.getElementById("hostBookingStatusForm");

    const statusBookingId =
        document.getElementById("hostBookingStatusBookingId");

    const selectedStatus =
        document.getElementById("hostBookingSelectedStatus");

    const statusConfirm =
        document.getElementById("hostBookingStatusConfirm");

    const statusTitle =
        document.getElementById("hostBookingStatusTitle");

    const statusMessage =
        document.getElementById("hostBookingStatusMessage");

    const statusIcon =
        document.getElementById("hostBookingStatusIcon");

    const statusOptions =
        document.querySelectorAll(
            ".host-booking-status-option"
        );

    const viewButtons =
        document.querySelectorAll(
            ".btn-host-booking-view"
        );

    const reportButton =
        document.getElementById(
            "btnHostBookingsReport"
        );

    const toast =
        document.getElementById(
            "hostBookingsToast"
        );

    const toastClose =
        document.getElementById(
            "hostBookingsToastClose"
        );


    /* =====================================================
       CURRENT BOOKING
    ===================================================== */

    let currentBooking = null;

    let selectedBookingStatus = "";


    /* =====================================================
       STATUS CONFIGURATION
    ===================================================== */

    const statusConfig = {

        pending: {
            title: "Pending Booking",
            message: "This booking is waiting for confirmation.",
            icon: "fa-clock"
        },

        confirmed: {
            title: "Confirmed Booking",
            message: "This booking has been confirmed.",
            icon: "fa-circle-check"
        },

        completed: {
            title: "Completed Booking",
            message: "This booking has been completed.",
            icon: "fa-flag-checkered"
        },

        cancelled: {
            title: "Cancelled Booking",
            message: "This booking has been cancelled.",
            icon: "fa-ban"
        }

    };


    /* =====================================================
       OPEN STATUS MODAL
    ===================================================== */

    function openStatusModal(booking) {

        if (!statusModal) {
            return;
        }

        currentBooking = booking;

        selectedBookingStatus =
            booking.status || "";

        if (statusBookingId) {

            statusBookingId.value =
                booking.bookingId || "";

        }

        if (selectedStatus) {

            selectedStatus.value =
                selectedBookingStatus;

        }

        updateStatusOptions();

        updateStatusModalContent();

        statusModal.classList.add("active");

        statusModal.setAttribute(
            "aria-hidden",
            "false"
        );

        document.body.style.overflow = "hidden";

    }


    /* =====================================================
       CLOSE STATUS MODAL
    ===================================================== */

    function closeStatusModal() {

        if (!statusModal) {
            return;
        }

        statusModal.classList.remove("active");

        statusModal.setAttribute(
            "aria-hidden",
            "true"
        );

        document.body.style.overflow = "";

        currentBooking = null;

        selectedBookingStatus = "";

        if (statusBookingId) {
            statusBookingId.value = "";
        }

        if (selectedStatus) {
            selectedStatus.value = "";
        }

        statusOptions.forEach(function (button) {

            button.classList.remove("selected");

        });

        if (statusConfirm) {

            statusConfirm.disabled = true;

        }

    }


    /* =====================================================
       UPDATE STATUS OPTIONS
    ===================================================== */

    function updateStatusOptions() {

        statusOptions.forEach(function (button) {

            const buttonStatus =
                button.getAttribute(
                    "data-status-option"
                );

            button.classList.toggle(
                "selected",
                buttonStatus === selectedBookingStatus
            );

        });

        if (statusConfirm) {

            statusConfirm.disabled =
                selectedBookingStatus === "";

        }

    }


    /* =====================================================
       UPDATE MODAL CONTENT
    ===================================================== */

    function updateStatusModalContent() {

        const config =
            statusConfig[selectedBookingStatus];

        if (!config) {

            if (statusTitle) {

                statusTitle.textContent =
                    "Update Booking Status";

            }

            if (statusMessage) {

                statusMessage.textContent =
                    "Select the new status for this host booking.";

            }

            if (statusIcon) {

                statusIcon.innerHTML =
                    '<i class="fa-solid fa-calendar-check"></i>';

            }

            return;
        }


        if (statusTitle) {

            statusTitle.textContent =
                config.title;

        }


        if (statusMessage) {

            if (
                currentBooking &&
                currentBooking.eventName
            ) {

                statusMessage.textContent =
                    currentBooking.eventName +
                    " — " +
                    config.message;

            } else {

                statusMessage.textContent =
                    config.message;

            }

        }


        if (statusIcon) {

            statusIcon.innerHTML =
                '<i class="fa-solid ' +
                config.icon +
                '"></i>';

        }

    }


    /* =====================================================
       VIEW BUTTONS
    ===================================================== */

    viewButtons.forEach(function (button) {

        button.addEventListener(
            "click",
            function () {

                const booking = {

                    bookingId:
                        button.dataset.bookingId || "",

                    eventId:
                        button.dataset.eventId || "",

                    userId:
                        button.dataset.userId || "",

                    hostId:
                        button.dataset.hostId || "",

                    hostPackageId:
                        button.dataset.hostPackageId || "",

                    eventName:
                        button.dataset.eventName || "",

                    eventDate:
                        button.dataset.eventDate || "",

                    startTime:
                        button.dataset.startTime || "",

                    phone:
                        button.dataset.phone || "",

                    address:
                        button.dataset.address || "",

                    specialRequests:
                        button.dataset.specialRequests || "",

                    hostName:
                        button.dataset.hostName || "",

                    hostType:
                        button.dataset.hostType || "",

                    status:
                        button.dataset.status || "pending",

                    createdAt:
                        button.dataset.createdAt || "",

                    updatedAt:
                        button.dataset.updatedAt || ""

                };


                openStatusModal(booking);

            }
        );

    });


    /* =====================================================
       STATUS OPTIONS CLICK
    ===================================================== */

    statusOptions.forEach(function (button) {

        button.addEventListener(
            "click",
            function () {

                const newStatus =
                    button.getAttribute(
                        "data-status-option"
                    );

                if (!newStatus) {
                    return;
                }

                selectedBookingStatus =
                    newStatus;

                if (selectedStatus) {

                    selectedStatus.value =
                        newStatus;

                }

                updateStatusOptions();

                updateStatusModalContent();

            }
        );

    });


    /* =====================================================
       CLOSE BUTTON
    ===================================================== */

    if (statusClose) {

        statusClose.addEventListener(
            "click",
            closeStatusModal
        );

    }


    /* =====================================================
       CANCEL BUTTON
    ===================================================== */

    if (statusCancel) {

        statusCancel.addEventListener(
            "click",
            closeStatusModal
        );

    }


    /* =====================================================
       OVERLAY CLOSE
    ===================================================== */

    document.querySelectorAll(
        "[data-close-host-booking-status]"
    ).forEach(function (element) {

        element.addEventListener(
            "click",
            closeStatusModal
        );

    });


    /* =====================================================
       ESCAPE KEY
    ===================================================== */

    document.addEventListener(
        "keydown",
        function (event) {

            if (
                event.key === "Escape" &&
                statusModal &&
                statusModal.classList.contains("active")
            ) {

                closeStatusModal();

            }

        }
    );


    /* =====================================================
       STATUS FORM VALIDATION
    ===================================================== */

    if (statusForm) {

        statusForm.addEventListener(
            "submit",
            function (event) {

                const bookingId =
                    statusBookingId
                        ? statusBookingId.value.trim()
                        : "";

                const status =
                    selectedStatus
                        ? selectedStatus.value.trim()
                        : "";

                if (
                    bookingId === "" ||
                    status === ""
                ) {

                    event.preventDefault();

                    return;

                }

            }
        );

    }


    /* =====================================================
       TOAST
    ===================================================== */

    let toastTimer = null;


    function closeToast() {

        if (!toast) {
            return;
        }

        toast.classList.add("hide");

        window.setTimeout(
            function () {

                if (toast) {

                    toast.style.display =
                        "none";

                }

            },
            250
        );

    }


    if (toast) {

        toastTimer = window.setTimeout(
            closeToast,
            4000
        );

    }


    if (toastClose) {

        toastClose.addEventListener(
            "click",
            function () {

                if (toastTimer) {

                    window.clearTimeout(
                        toastTimer
                    );

                }

                closeToast();

            }
        );

    }


    /* =====================================================
       PRINT REPORT
    ===================================================== */

    if (reportButton) {

        reportButton.addEventListener(
            "click",
            function () {

                window.print();

            }
        );

    }


    /* =====================================================
       SEARCH FORM
    ===================================================== */

    const filterForm =
        document.getElementById(
            "hostBookingsFilterForm"
        );

    if (filterForm) {

        filterForm.addEventListener(
            "submit",
            function () {

                const searchInput =
                    filterForm.querySelector(
                        'input[name="search"]'
                    );

                if (
                    searchInput &&
                    searchInput.value.trim() === ""
                ) {

                    searchInput.value = "";

                }

            }
        );

    }


    /* =====================================================
       IMAGE ERROR HANDLING
    ===================================================== */

    document.querySelectorAll(
        ".host-booking-avatar img"
    ).forEach(function (image) {

        image.addEventListener(
            "error",
            function () {

                image.style.display =
                    "none";

                const placeholder =
                    image.nextElementSibling;

                if (placeholder) {

                    placeholder.style.display =
                        "flex";

                }

            }
        );

    });


    /* =====================================================
       PREVENT BODY SCROLL WITH ACTIVE MODAL
    ===================================================== */

    if (
        statusModal &&
        statusModal.classList.contains("active")
    ) {

        document.body.style.overflow =
            "hidden";

    }


    /* =====================================================
       INITIAL STATUS
    ===================================================== */

    updateStatusOptions();

});