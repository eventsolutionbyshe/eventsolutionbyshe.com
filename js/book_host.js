/* =========================================================
   BOOK HOST JAVASCRIPT
   Event Solutions by S.H.E.
========================================================= */

document.addEventListener("DOMContentLoaded", function () {

    "use strict";

    /* =====================================================
       ELEMENTS
    ===================================================== */

    const form =
        document.getElementById("hostBookingForm");

    const phoneInput =
        document.getElementById("phone");

    const eventDateInput =
        document.getElementById("eventDate");

    const startTimeInput =
        document.getElementById("startTime");

    const specialRequestsInput =
        document.getElementById("specialRequests");

    const agreementInput =
        document.getElementById("bookingAgreement");

    const agreementError =
        document.getElementById("agreementError");

    const submitButton =
        document.getElementById("submitBooking");

    const characterCount =
        document.getElementById("characterCount");

    const calendarSelectedInfo =
        document.getElementById("calendarSelectedInfo");

    const bookingLoadingOverlay =
        document.getElementById("bookingLoadingOverlay");


    /* =====================================================
       GLOBAL DATA FROM PHP
    ===================================================== */

    const hostBookedDates =
        Array.isArray(window.hostBookedDates)
            ? window.hostBookedDates
            : [];

    const eventBookedDates =
        Array.isArray(window.eventBookedDates)
            ? window.eventBookedDates
            : [];

    const today =
        typeof window.hostBookingToday === "string" &&
        window.hostBookingToday !== ""
            ? window.hostBookingToday
            : getToday();


    /* =====================================================
       NORMALIZE BOOKED DATES
    ===================================================== */

    const hostBookedSet =
        new Set(
            hostBookedDates
                .map(normalizeDate)
                .filter(Boolean)
        );

    const eventBookedSet =
        new Set(
            eventBookedDates
                .map(normalizeDate)
                .filter(Boolean)
        );


    /* =====================================================
       UTILITY FUNCTIONS
    ===================================================== */

    function getToday() {

        const date = new Date();

        const year =
            date.getFullYear();

        const month =
            String(date.getMonth() + 1)
                .padStart(2, "0");

        const day =
            String(date.getDate())
                .padStart(2, "0");

        return `${year}-${month}-${day}`;
    }


    function normalizeDate(value) {

        if (!value) {
            return "";
        }

        const stringValue =
            String(value).trim();

        if (!stringValue) {
            return "";
        }

        return stringValue.substring(0, 10);
    }


    function formatDateForDisplay(dateString) {

        if (!dateString) {
            return "";
        }

        const parts =
            dateString.split("-");

        if (parts.length !== 3) {
            return dateString;
        }

        const year =
            Number(parts[0]);

        const month =
            Number(parts[1]);

        const day =
            Number(parts[2]);

        if (
            Number.isNaN(year) ||
            Number.isNaN(month) ||
            Number.isNaN(day)
        ) {
            return dateString;
        }

        const date =
            new Date(
                year,
                month - 1,
                day
            );

        return date.toLocaleDateString(
            "en-US",
            {
                month: "long",
                day: "numeric",
                year: "numeric"
            }
        );
    }


    function isValidDateString(value) {

        if (
            !/^\d{4}-\d{2}-\d{2}$/.test(value)
        ) {
            return false;
        }

        const parts =
            value.split("-");

        const year =
            Number(parts[0]);

        const month =
            Number(parts[1]);

        const day =
            Number(parts[2]);

        const date =
            new Date(
                year,
                month - 1,
                day
            );

        return (
            date.getFullYear() === year &&
            date.getMonth() === month - 1 &&
            date.getDate() === day
        );
    }


    function isPastDate(dateString) {

        if (
            !isValidDateString(dateString)
        ) {
            return false;
        }

        return dateString < today;
    }


    function isHostBooked(dateString) {

        return hostBookedSet.has(
            normalizeDate(dateString)
        );
    }


    function hasExistingEvent(dateString) {

        return eventBookedSet.has(
            normalizeDate(dateString)
        );
    }


    /* =====================================================
       ERROR HELPERS
    ===================================================== */

    function getFieldError(input) {

        if (!input) {
            return null;
        }

        const parent =
            input.closest(".form-group");

        if (!parent) {
            return null;
        }

        return parent.querySelector(
            ".field-error"
        );
    }


    function setFieldError(
        input,
        message
    ) {

        if (!input) {
            return;
        }

        const errorElement =
            getFieldError(input);

        input.classList.add(
            "input-error"
        );

        input.setAttribute(
            "aria-invalid",
            "true"
        );

        if (errorElement) {

            errorElement.textContent =
                message || "";

            errorElement.classList.toggle(
                "show",
                Boolean(message)
            );
        }
    }


    function clearFieldError(input) {

        if (!input) {
            return;
        }

        const errorElement =
            getFieldError(input);

        input.classList.remove(
            "input-error"
        );

        input.removeAttribute(
            "aria-invalid"
        );

        if (errorElement) {

            errorElement.textContent =
                "";

            errorElement.classList.remove(
                "show"
            );
        }
    }


    function clearAllFieldErrors() {

        [
            phoneInput,
            eventDateInput,
            startTimeInput,
            specialRequestsInput
        ].forEach(function (input) {

            clearFieldError(input);

        });

        if (agreementInput) {

            agreementInput.classList.remove(
                "input-error"
            );

            agreementInput.removeAttribute(
                "aria-invalid"
            );
        }

        if (agreementError) {

            agreementError.textContent =
                "";

            agreementError.classList.remove(
                "show"
            );
        }
    }


    /* =====================================================
       PHONE NUMBER
    ===================================================== */

    function cleanPhone(value) {

        return String(value || "")
            .replace(
                /[\s\-()]/g,
                ""
            );
    }


    function isValidPhilippinePhone(value) {

        const cleaned =
            cleanPhone(value);

        return (
            /^09\d{9}$/.test(cleaned) ||
            /^\+639\d{9}$/.test(cleaned)
        );
    }


    if (phoneInput) {

        phoneInput.addEventListener(
            "input",
            function () {

                this.value =
                    this.value.replace(
                        /[^0-9+\-()\s]/g,
                        ""
                    );

                clearFieldError(this);
            }
        );


        phoneInput.addEventListener(
            "blur",
            function () {

                if (
                    this.value.trim() !== "" &&
                    !isValidPhilippinePhone(
                        this.value
                    )
                ) {

                    setFieldError(
                        this,
                        "Please enter a valid Philippine phone number."
                    );
                }
            }
        );
    }


    /* =====================================================
       FLATPICKR CALENDAR
    ===================================================== */

    let calendarInstance = null;


    if (
        eventDateInput &&
        typeof flatpickr !== "undefined"
    ) {

        calendarInstance =
            flatpickr(
                eventDateInput,
                {

                    dateFormat: "Y-m-d",

                    altInput: true,

                    altFormat: "F j, Y",

                    minDate: today,

                    allowInput: false,

                    disableMobile: false,

                    monthSelectorType:
                        "dropdown",

                    yearSelectorType:
                        "dropdown",

                    showMonths: 1,

                    animate: true,

                    clickOpens: true,

                    disable: [

                        function (date) {

                            const year =
                                date.getFullYear();

                            const month =
                                String(
                                    date.getMonth() + 1
                                ).padStart(
                                    2,
                                    "0"
                                );

                            const day =
                                String(
                                    date.getDate()
                                ).padStart(
                                    2,
                                    "0"
                                );

                            const dateString =
                                `${year}-${month}-${day}`;

                            return isHostBooked(
                                dateString
                            );
                        }

                    ],


                    onOpen: function () {

                        updateCalendarDayStyles();
                    },


                    onReady: function () {

                        updateCalendarDayStyles();

                        updateCalendarSelectedInfo(
                            this.input.value
                        );
                    },


                    onMonthChange: function () {

                        setTimeout(
                            updateCalendarDayStyles,
                            10
                        );
                    },


                    onYearChange: function () {

                        setTimeout(
                            updateCalendarDayStyles,
                            10
                        );
                    },


                    onChange: function (
                        selectedDates,
                        dateStr
                    ) {

                        clearFieldError(
                            eventDateInput
                        );

                        updateCalendarSelectedInfo(
                            dateStr
                        );

                        updateCalendarDayStyles();
                    }

                }
            );
    }


    /* =====================================================
       STYLE CALENDAR DAYS
    ===================================================== */

    function updateCalendarDayStyles() {

        if (!calendarInstance) {
            return;
        }

        const days =
            calendarInstance
                .calendarContainer
                .querySelectorAll(
                    ".flatpickr-day"
                );

        days.forEach(
            function (dayElement) {

                const dateObject =
                    dayElement.dateObj;

                if (!dateObject) {
                    return;
                }

                const year =
                    dateObject.getFullYear();

                const month =
                    String(
                        dateObject.getMonth() + 1
                    ).padStart(
                        2,
                        "0"
                    );

                const day =
                    String(
                        dateObject.getDate()
                    ).padStart(
                        2,
                        "0"
                    );

                const dateString =
                    `${year}-${month}-${day}`;


                dayElement.classList.remove(
                    "host-booked-date",
                    "birthday-booked-date",
                    "existing-event-date"
                );

                dayElement.removeAttribute(
                    "title"
                );


                if (
                    isHostBooked(dateString)
                ) {

                    dayElement.classList.add(
                        "host-booked-date",
                        "birthday-booked-date"
                    );

                    dayElement.setAttribute(
                        "title",
                        "This host is already booked on this date."
                    );
                }


                if (
                    hasExistingEvent(dateString)
                ) {

                    dayElement.classList.add(
                        "existing-event-date"
                    );

                    if (
                        !isHostBooked(
                            dateString
                        )
                    ) {

                        dayElement.setAttribute(
                            "title",
                            "You already have a confirmed event on this date."
                        );
                    }
                }

            }
        );
    }


    /* =====================================================
       CALENDAR SELECTED INFORMATION
    ===================================================== */

    function updateCalendarSelectedInfo(
        dateString
    ) {

        if (!calendarSelectedInfo) {
            return;
        }

        dateString =
            normalizeDate(dateString);


        if (!dateString) {

            calendarSelectedInfo.textContent =
                "Select a date to view availability.";

            calendarSelectedInfo.classList.remove(
                "is-available",
                "is-booked",
                "has-event"
            );

            return;
        }


        if (
            isHostBooked(dateString)
        ) {

            calendarSelectedInfo.innerHTML = `
                <strong>Host unavailable.</strong>
                ${escapeHtml(
                    formatDateForDisplay(
                        dateString
                    )
                )}
                is already booked for this host.
                Please choose another date.
            `;

            calendarSelectedInfo.classList.remove(
                "is-available",
                "has-event"
            );

            calendarSelectedInfo.classList.add(
                "is-booked"
            );

            return;
        }


        if (
            hasExistingEvent(dateString)
        ) {

            calendarSelectedInfo.innerHTML = `
                <strong>Date available.</strong>
                You have a confirmed event on
                ${escapeHtml(
                    formatDateForDisplay(
                        dateString
                    )
                )}.
                This does not automatically prevent
                you from requesting this host.
            `;

            calendarSelectedInfo.classList.remove(
                "is-booked",
                "is-available"
            );

            calendarSelectedInfo.classList.add(
                "has-event"
            );

            return;
        }


        calendarSelectedInfo.innerHTML = `
            <strong>Date available.</strong>
            ${escapeHtml(
                formatDateForDisplay(
                    dateString
                )
            )}
            is currently available for this host.
        `;

        calendarSelectedInfo.classList.remove(
            "is-booked",
            "has-event"
        );

        calendarSelectedInfo.classList.add(
            "is-available"
        );
    }


    /* =====================================================
       ESCAPE HTML
    ===================================================== */

    function escapeHtml(value) {

        return String(value || "")
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


    /* =====================================================
       DATE INPUT CHANGE
    ===================================================== */

    if (eventDateInput) {

        eventDateInput.addEventListener(
            "change",
            function () {

                const value =
                    normalizeDate(
                        this.value
                    );

                if (!value) {
                    return;
                }


                if (
                    isHostBooked(value)
                ) {

                    setFieldError(
                        this,
                        "This host is already booked on the selected date."
                    );

                    updateCalendarSelectedInfo(
                        value
                    );

                    return;
                }


                if (
                    isPastDate(value)
                ) {

                    setFieldError(
                        this,
                        "Event date cannot be in the past."
                    );

                    return;
                }


                clearFieldError(this);

                updateCalendarSelectedInfo(
                    value
                );
            }
        );
    }


    /* =====================================================
       START TIME
    ===================================================== */

    if (startTimeInput) {

        startTimeInput.addEventListener(
            "change",
            function () {

                if (
                    this.value.trim() !== ""
                ) {

                    clearFieldError(
                        this
                    );
                }
            }
        );
    }


    /* =====================================================
       SPECIAL REQUEST CHARACTER COUNTER
    ===================================================== */

    function updateCharacterCount() {

        if (
            !specialRequestsInput ||
            !characterCount
        ) {
            return;
        }

        const length =
            specialRequestsInput.value.length;

        characterCount.textContent =
            `${length} / 2000`;

        characterCount.classList.toggle(
            "near-limit",
            length >= 1800 &&
            length < 2000
        );

        characterCount.classList.toggle(
            "at-limit",
            length >= 2000
        );
    }


    if (specialRequestsInput) {

        updateCharacterCount();

        specialRequestsInput.addEventListener(
            "input",
            updateCharacterCount
        );
    }


    /* =====================================================
       AGREEMENT
    ===================================================== */

    if (agreementInput) {

        agreementInput.addEventListener(
            "change",
            function () {

                if (this.checked) {

                    this.classList.remove(
                        "input-error"
                    );

                    this.removeAttribute(
                        "aria-invalid"
                    );

                    if (agreementError) {

                        agreementError.textContent =
                            "";

                        agreementError.classList.remove(
                            "show"
                        );
                    }
                }
            }
        );
    }


    /* =====================================================
       FORM VALIDATION
    ===================================================== */

    function validateForm() {

        let valid = true;

        clearAllFieldErrors();


        /* PHONE */

        if (phoneInput) {

            const phone =
                phoneInput.value.trim();

            if (phone === "") {

                setFieldError(
                    phoneInput,
                    "Phone number is required."
                );

                valid = false;

            } else if (
                !isValidPhilippinePhone(
                    phone
                )
            ) {

                setFieldError(
                    phoneInput,
                    "Please enter a valid Philippine phone number."
                );

                valid = false;
            }
        }


        /* EVENT DATE */

        if (eventDateInput) {

            const eventDate =
                normalizeDate(
                    eventDateInput.value
                );

            if (eventDate === "") {

                setFieldError(
                    eventDateInput,
                    "Event date is required."
                );

                valid = false;

            } else if (
                !isValidDateString(
                    eventDate
                )
            ) {

                setFieldError(
                    eventDateInput,
                    "Please select a valid event date."
                );

                valid = false;

            } else if (
                isPastDate(eventDate)
            ) {

                setFieldError(
                    eventDateInput,
                    "Event date cannot be in the past."
                );

                valid = false;

            } else if (
                isHostBooked(eventDate)
            ) {

                setFieldError(
                    eventDateInput,
                    "This host is already booked on the selected date. Please choose another date."
                );

                valid = false;
            }
        }


        /* START TIME */

        if (startTimeInput) {

            const startTime =
                startTimeInput.value.trim();

            if (startTime === "") {

                setFieldError(
                    startTimeInput,
                    "Start time is required."
                );

                valid = false;

            } else if (
                !/^\d{2}:\d{2}$/.test(
                    startTime
                )
            ) {

                setFieldError(
                    startTimeInput,
                    "Please select a valid start time."
                );

                valid = false;
            }
        }


        /* SPECIAL REQUESTS */

        if (specialRequestsInput) {

            if (
                specialRequestsInput.value.length >
                2000
            ) {

                specialRequestsInput.value =
                    specialRequestsInput.value
                        .substring(
                            0,
                            2000
                        );

                updateCharacterCount();

                setFieldError(
                    specialRequestsInput,
                    "Special requests cannot exceed 2000 characters."
                );

                valid = false;
            }
        }


        /* AGREEMENT */

        if (
            agreementInput &&
            !agreementInput.checked
        ) {

            agreementInput.classList.add(
                "input-error"
            );

            agreementInput.setAttribute(
                "aria-invalid",
                "true"
            );

            if (agreementError) {

                agreementError.textContent =
                    "Please confirm that the information provided is correct.";

                agreementError.classList.add(
                    "show"
                );
            }

            valid = false;
        }


        /* FOCUS FIRST ERROR */

        if (!valid) {

            const firstError =
                document.querySelector(
                    ".input-error"
                );

            if (firstError) {

                if (
                    firstError === eventDateInput &&
                    calendarInstance &&
                    calendarInstance.altInput
                ) {

                    calendarInstance
                        .altInput
                        .focus();

                } else {

                    firstError.focus();
                }
            }
        }


        return valid;
    }


    /* =====================================================
       BOOKING LOADING POPUP
    ===================================================== */

    function showBookingLoading() {

        if (!bookingLoadingOverlay) {

            console.warn(
                "Booking loading overlay not found."
            );

            return;
        }


        /*
         * SUPPORT BOTH CLASS NAMES
         * IN CASE YOUR CSS USES .active OR .show
         */

        bookingLoadingOverlay.classList.add(
            "show",
            "active"
        );


        bookingLoadingOverlay.setAttribute(
            "aria-hidden",
            "false"
        );


        document.body.classList.add(
            "booking-is-loading"
        );


        document.body.style.overflow =
            "hidden";


        if (submitButton) {

            submitButton.disabled =
                true;

            submitButton.classList.add(
                "is-loading"
            );

            submitButton.setAttribute(
                "aria-busy",
                "true"
            );


            const buttonText =
                submitButton.querySelector(
                    ".button-text"
                );

            const buttonArrow =
                submitButton.querySelector(
                    ".button-arrow"
                );

            const buttonLoading =
                submitButton.querySelector(
                    ".button-loading"
                );


            if (buttonText) {

                buttonText.style.display =
                    "none";
            }


            if (buttonArrow) {

                buttonArrow.style.display =
                    "none";
            }


            if (buttonLoading) {

                buttonLoading.style.display =
                    "inline-flex";
            }
        }
    }


    /* =====================================================
       HIDE BOOKING LOADING POPUP
    ===================================================== */

    function hideBookingLoading() {

        if (!bookingLoadingOverlay) {
            return;
        }


        bookingLoadingOverlay.classList.remove(
            "show",
            "active"
        );


        bookingLoadingOverlay.setAttribute(
            "aria-hidden",
            "true"
        );


        document.body.classList.remove(
            "booking-is-loading"
        );


        document.body.style.overflow =
            "";


        if (submitButton) {

            submitButton.disabled =
                false;

            submitButton.classList.remove(
                "is-loading"
            );

            submitButton.removeAttribute(
                "aria-busy"
            );


            const buttonText =
                submitButton.querySelector(
                    ".button-text"
                );

            const buttonArrow =
                submitButton.querySelector(
                    ".button-arrow"
                );

            const buttonLoading =
                submitButton.querySelector(
                    ".button-loading"
                );


            if (buttonText) {

                buttonText.style.display =
                    "";
            }


            if (buttonArrow) {

                buttonArrow.style.display =
                    "";
            }


            if (buttonLoading) {

                buttonLoading.style.display =
                    "";
            }
        }
    }


    /* =====================================================
       SUBMITTING STATE
    ===================================================== */

    function setSubmittingState(
        submitting
    ) {

        if (!submitButton) {
            return;
        }


        submitButton.disabled =
            submitting;


        submitButton.classList.toggle(
            "is-loading",
            submitting
        );


        if (submitting) {

            submitButton.setAttribute(
                "aria-busy",
                "true"
            );

        } else {

            submitButton.removeAttribute(
                "aria-busy"
            );
        }
    }


    /* =====================================================
       FORM SUBMISSION
    ===================================================== */

    let isSubmitting = false;


    if (form) {

        form.addEventListener(
            "submit",
            function (event) {

                /*
                 * PREVENT DUPLICATE SUBMISSION
                 */

                if (isSubmitting) {

                    event.preventDefault();

                    return;
                }


                /*
                 * VALIDATE BEFORE SHOWING LOADING
                 */

                if (!validateForm()) {

                    event.preventDefault();

                    return;
                }


                /*
                 * LOCK FORM
                 */

                isSubmitting = true;


                /*
                 * SHOW BUTTON LOADING
                 */

                setSubmittingState(
                    true
                );


                /*
                 * SHOW FULL SCREEN LOADING
                 */

                showBookingLoading();


                /*
                 * IMPORTANT:
                 *
                 * DO NOT USE:
                 *
                 * event.preventDefault();
                 *
                 * HERE.
                 *
                 * The browser will continue
                 * the normal POST request to
                 * book_host.php.
                 */

            }
        );
    }


    /* =====================================================
       EXTRA DOUBLE-CLICK PROTECTION
    ===================================================== */

    if (submitButton) {

        submitButton.addEventListener(
            "click",
            function () {

                if (isSubmitting) {

                    this.disabled =
                        true;
                }
            }
        );
    }


    /* =====================================================
       PAGE RESTORE
    ===================================================== */

    window.addEventListener(
        "pageshow",
        function () {

            isSubmitting =
                false;

            hideBookingLoading();
        }
    );


    /* =====================================================
       ESC KEY
       Only closes calendar.
    ===================================================== */

    document.addEventListener(
        "keydown",
        function (event) {

            if (
                event.key === "Escape" &&
                calendarInstance &&
                calendarInstance.isOpen
            ) {

                calendarInstance.close();
            }
        }
    );


    /* =====================================================
       INITIALIZE CALENDAR INFORMATION
    ===================================================== */

    if (
        eventDateInput &&
        eventDateInput.value
    ) {

        updateCalendarSelectedInfo(
            eventDateInput.value
        );
    }


    /* =====================================================
       INITIAL CHARACTER COUNT
    ===================================================== */

    updateCharacterCount();


    /* =====================================================
       DEBUG
    ===================================================== */

    console.log(
        "book_host.js loaded successfully."
    );

    console.log(
        "Host booking form:",
        Boolean(form)
    );

    console.log(
        "Booking loading overlay:",
        Boolean(
            bookingLoadingOverlay
        )
    );

    console.log(
        "Submit button:",
        Boolean(
            submitButton
        )
    );

});