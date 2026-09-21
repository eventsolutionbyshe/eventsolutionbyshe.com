document.addEventListener("DOMContentLoaded", function () {

    /* =====================================================
       ELEMENTS
    ====================================================== */

    const form =
        document.getElementById("bookingForm");

    /*
     * NEW:
     * Use celebrantName for all event types.
     *
     * The birthdayName fallback keeps compatibility
     * with your old book_birthday.php if it still uses
     * id="birthdayName".
     */
    const celebrantName =
        document.getElementById("celebrantName") ||
        document.getElementById("birthdayName");

    const eventName =
        document.getElementById("eventName");

    const eventDate =
        document.getElementById("eventDate");

    const startTime =
        document.getElementById("startTime");

    const venueId =
        document.getElementById("venueId");

    const phone =
        document.getElementById("phone");

    const address =
        document.getElementById("address");

    const agreement =
        document.getElementById("bookingAgreement");

    const specialRequests =
        document.getElementById("specialRequests");

    const characterCount =
        document.getElementById("characterCount");

    const submitButton =
        document.getElementById("submitBooking");


    /* =====================================================
       DYNAMIC EVENT TYPE
    ====================================================== */

    /*
     * book_birthday.php should define:
     *
     * window.bookingEventType = "Birthday";
     *
     * or:
     *
     * window.bookingEventType = "Debut";
     *
     * or:
     *
     * window.bookingEventType = "Graduation";
     *
     * etc.
     */

    const eventType =
        String(
            window.bookingEventType ||
            (form ? form.dataset.eventType : "") ||
            "Event"
        ).trim() || "Event";


    const eventTypeLower =
        eventType.toLowerCase();


    /*
     * Example:
     *
     * Birthday -> Birthday Celebrant
     * Debut -> Debut Celebrant
     * Graduation -> Graduation Celebrant
     */
    const celebrantLabel =
        eventType + " Celebrant";


    /* =====================================================
       BOOKING LOADING POPUP
    ====================================================== */

    const bookingLoadingOverlay =
        document.getElementById(
            "bookingLoadingOverlay"
        );


    /* =====================================================
       BOOKED DATES
    ====================================================== */

    const bookedDates =
        Array.isArray(window.bookedBirthdayDates)
            ? window.bookedBirthdayDates.map(function (date) {

                return String(date)
                    .trim()
                    .substring(0, 10);

            })
            : [];


    /* =====================================================
       CHECK IF DATE IS BOOKED
    ====================================================== */

    function isBookedDate(date) {

        if (!date) {
            return false;
        }

        const normalizedDate =
            String(date)
                .trim()
                .substring(0, 10);

        return bookedDates.includes(
            normalizedDate
        );
    }


    /* =====================================================
       HTML ESCAPE HELPER
    ====================================================== */

    function escapeHtml(value) {

        return String(value)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }


    /* =====================================================
       BOOKED DATE POPUP
    ====================================================== */

    const popup =
        document.createElement("div");

    popup.className =
        "birthday-date-alert-overlay";

    popup.id =
        "birthdayDateAlert";

    popup.setAttribute(
        "aria-hidden",
        "true"
    );


    popup.innerHTML = `
        <div
            class="birthday-date-alert"
            role="alertdialog"
            aria-modal="true"
            aria-labelledby="birthdayDateAlertTitle"
        >

            <button
                type="button"
                class="birthday-date-alert-close"
                id="birthdayDateAlertClose"
                aria-label="Close"
            >
                ×
            </button>


            <div class="birthday-date-alert-icon">

                <span class="material-symbols-outlined">
                    event_busy
                </span>

            </div>


            <span class="birthday-date-alert-label">
                DATE UNAVAILABLE
            </span>


            <h3 id="birthdayDateAlertTitle">
                This date is already booked.
            </h3>


            <p>
                Please select a new date for your
                ${escapeHtml(eventTypeLower)}
                celebration.
            </p>


            <button
                type="button"
                id="birthdayDateAlertOkay"
            >
                Select New Date
            </button>

        </div>
    `;


    document.body.appendChild(
        popup
    );


    const popupOkay =
        document.getElementById(
            "birthdayDateAlertOkay"
        );


    const popupClose =
        document.getElementById(
            "birthdayDateAlertClose"
        );


    /* =====================================================
       SHOW POPUP
    ====================================================== */

    function showBookedDatePopup() {

        popup.classList.add(
            "show"
        );

        popup.setAttribute(
            "aria-hidden",
            "false"
        );

        document.body.classList.add(
            "birthday-alert-open"
        );


        setTimeout(function () {

            if (popupOkay) {

                popupOkay.focus();

            }

        }, 100);
    }


    /* =====================================================
       FLATPICKR INSTANCE
    ====================================================== */

    let birthdayCalendar = null;


    /* =====================================================
       CLOSE POPUP
       AND CLEAR DATE
    ====================================================== */

    function closeBookedDatePopup() {

        popup.classList.remove(
            "show"
        );

        popup.setAttribute(
            "aria-hidden",
            "true"
        );

        document.body.classList.remove(
            "birthday-alert-open"
        );


        /*
         * Clear selected date.
         */
        if (birthdayCalendar) {

            birthdayCalendar.clear();

        } else if (eventDate) {

            eventDate.value = "";

        }


        /*
         * Focus date input.
         */
        if (eventDate) {

            setTimeout(function () {

                eventDate.focus();

            }, 100);

        }
    }


    /* =====================================================
       POPUP BUTTON
    ====================================================== */

    if (popupOkay) {

        popupOkay.addEventListener(
            "click",
            function () {

                closeBookedDatePopup();

            }
        );
    }


    /* =====================================================
       POPUP CLOSE BUTTON
    ====================================================== */

    if (popupClose) {

        popupClose.addEventListener(
            "click",
            function () {

                closeBookedDatePopup();

            }
        );
    }


    /* =====================================================
       CLICK OUTSIDE POPUP
    ====================================================== */

    popup.addEventListener(
        "click",
        function (event) {

            if (
                event.target === popup
            ) {

                closeBookedDatePopup();

            }

        }
    );


    /* =====================================================
       ESCAPE KEY
    ====================================================== */

    document.addEventListener(
        "keydown",
        function (event) {

            if (
                event.key === "Escape" &&
                popup.classList.contains("show")
            ) {

                closeBookedDatePopup();

            }

        }
    );


    /* =====================================================
       FLATPICKR
    ====================================================== */

    if (
        eventDate &&
        typeof flatpickr !== "undefined"
    ) {

        birthdayCalendar =
            flatpickr(
                eventDate,
                {

                    /* -------------------------------------
                       DATE FORMAT
                    -------------------------------------- */

                    dateFormat:
                        "Y-m-d",


                    /* -------------------------------------
                       TODAY OR FUTURE
                    -------------------------------------- */

                    minDate:
                        "today",


                    /* -------------------------------------
                       ALLOW MANUAL INPUT
                    -------------------------------------- */

                    allowInput:
                        true,


                    /* -------------------------------------
                       USE FLATPICKR ON MOBILE
                    -------------------------------------- */

                    disableMobile:
                        true,


                    /* -------------------------------------
                       IMPORTANT:

                       DO NOT DISABLE BOOKED DATES.

                       Customer can still click them.
                    -------------------------------------- */

                    onDayCreate:
                        function (
                            dObj,
                            dStr,
                            fp,
                            dayElem
                        ) {

                            const calendarDate =
                                fp.formatDate(
                                    dayElem.dateObj,
                                    "Y-m-d"
                                );


                            /*
                             * If this date already exists
                             * in the booked date list,
                             * color it gold.
                             */
                            if (
                                isBookedDate(
                                    calendarDate
                                )
                            ) {

                                dayElem.classList.add(
                                    "birthday-booked-date"
                                );


                                dayElem.setAttribute(
                                    "title",
                                    "This date is already booked"
                                );


                                dayElem.setAttribute(
                                    "data-booked",
                                    "true"
                                );

                            }

                        },


                    /* -------------------------------------
                       DATE SELECTION
                    -------------------------------------- */

                    onChange:
                        function (
                            selectedDates,
                            dateStr,
                            instance
                        ) {

                            /*
                             * Booked dates are NOT disabled.
                             *
                             * Customer can click them.
                             *
                             * Popup will appear.
                             */
                            if (
                                isBookedDate(
                                    dateStr
                                )
                            ) {

                                showBookedDatePopup();

                                return;

                            }


                            clearFieldError(
                                "event_date"
                            );

                        }

                }
            );
    }


    /* =====================================================
       MANUAL DATE INPUT CHECK
    ====================================================== */

    if (eventDate) {

        eventDate.addEventListener(
            "change",
            function () {

                const date =
                    eventDate.value.trim();


                if (
                    isBookedDate(
                        date
                    )
                ) {

                    showBookedDatePopup();

                    return;

                }


                clearFieldError(
                    "event_date"
                );

            }
        );

    }


    /* =====================================================
       CELEBRANT NAME
       -> EVENT NAME
    ====================================================== */

    if (
        celebrantName &&
        eventName
    ) {

        function updateEventName() {

            const name =
                celebrantName.value.trim();


            if (
                name === ""
            ) {

                eventName.value =
                    "";

            } else {

                /*
                 * Dynamic event name.
                 *
                 * Birthday:
                 * John Doe's Birthday
                 *
                 * Debut:
                 * John Doe's Debut
                 *
                 * Graduation:
                 * John Doe's Graduation
                 */
                eventName.value =
                    name +
                    "'s " +
                    eventType;

            }

        }


        celebrantName.addEventListener(
            "input",
            updateEventName
        );


        updateEventName();

    }


    /* =====================================================
       CHARACTER COUNT
    ====================================================== */

    if (
        specialRequests &&
        characterCount
    ) {

        function updateCharacterCount() {

            characterCount.textContent =
                specialRequests.value.length +
                " / 2000";

        }


        specialRequests.addEventListener(
            "input",
            updateCharacterCount
        );


        updateCharacterCount();

    }


    /* =====================================================
       ERROR HELPERS
    ====================================================== */

    function getFieldError(
        field
    ) {

        return document.querySelector(
            `[data-error-for="${field}"]`
        );

    }


    function showFieldError(
        field,
        message
    ) {

        const error =
            getFieldError(
                field
            );


        if (error) {

            error.textContent =
                message;

        }

    }


    function clearFieldError(
        field
    ) {

        const error =
            getFieldError(
                field
            );


        if (error) {

            error.textContent =
                "";

        }

    }


    function clearAllErrors() {

        document
            .querySelectorAll(
                ".field-error"
            )
            .forEach(
                function (element) {

                    element.textContent =
                        "";

                }
            );


        if (agreement) {

            const agreementError =
                document.getElementById(
                    "agreementError"
                );


            if (agreementError) {

                agreementError.textContent =
                    "";

            }

        }

    }


    /* =====================================================
       PHONE VALIDATION
    ====================================================== */

    function isValidPhilippinePhone(
        value
    ) {

        const clean =
            value.replace(
                /[\s\-()]/g,
                ""
            );


        return /^(09\d{9}|\+639\d{9})$/
            .test(
                clean
            );

    }


    /* =====================================================
       BOOKING LOADING POPUP
    ====================================================== */

    function showBookingLoading() {

        if (
            !bookingLoadingOverlay
        ) {

            return;

        }


        /*
         * Show loading popup.
         */
        bookingLoadingOverlay.classList.add(
            "show"
        );


        bookingLoadingOverlay.setAttribute(
            "aria-hidden",
            "false"
        );


        /*
         * Prevent scrolling while
         * booking is being submitted.
         */
        document.body.style.overflow =
            "hidden";


        /*
         * Disable submit button
         * to prevent duplicate bookings.
         */
        if (submitButton) {

            submitButton.disabled =
                true;


            submitButton.classList.add(
                "is-loading"
            );


            /*
             * Hide normal button text.
             */
            const buttonText =
                submitButton.querySelector(
                    ".button-text"
                );


            if (buttonText) {

                buttonText.style.display =
                    "none";

            }


            /*
             * Hide arrow.
             */
            const buttonArrow =
                submitButton.querySelector(
                    ".button-arrow"
                );


            if (buttonArrow) {

                buttonArrow.style.display =
                    "none";

            }


            /*
             * Show button loading text.
             */
            const buttonLoading =
                submitButton.querySelector(
                    ".button-loading"
                );


            if (buttonLoading) {

                buttonLoading.style.display =
                    "inline-flex";

            }

        }

    }


    /* =====================================================
       FORM SUBMISSION
    ====================================================== */

    if (form) {

        form.addEventListener(
            "submit",
            function (event) {

                clearAllErrors();

                let valid = true;


                /* =========================================
                   CELEBRANT NAME
                ========================================== */

                if (
                    celebrantName &&
                    celebrantName.value.trim() === ""
                ) {

                    const message =
                        celebrantLabel +
                        " name is required.";


                    /*
                     * New dynamic field.
                     */
                    showFieldError(
                        "celebrant_name",
                        message
                    );


                    /*
                     * Backward compatibility
                     * with old birthday field.
                     */
                    showFieldError(
                        "birthday_name",
                        message
                    );


                    valid = false;

                }


                /* =========================================
                   EVENT DATE
                ========================================== */

                const selectedDate =
                    eventDate
                        ? eventDate.value.trim()
                        : "";


                if (
                    selectedDate === ""
                ) {

                    showFieldError(
                        "event_date",
                        "Event date is required."
                    );


                    valid = false;

                }


                /* =========================================
                   BOOKED DATE
                ========================================== */

                if (
                    selectedDate !== "" &&
                    isBookedDate(
                        selectedDate
                    )
                ) {

                    event.preventDefault();

                    showBookedDatePopup();

                    return;

                }


                /* =========================================
                   START TIME
                ========================================== */

                if (
                    startTime &&
                    startTime.value === ""
                ) {

                    showFieldError(
                        "start_time",
                        "Start time is required."
                    );


                    valid = false;

                }


                /* =========================================
                   PHONE
                ========================================== */

                if (
                    phone &&
                    phone.value.trim() === ""
                ) {

                    showFieldError(
                        "phone",
                        "Phone number is required."
                    );


                    valid = false;

                } else if (
                    phone &&
                    !isValidPhilippinePhone(
                        phone.value.trim()
                    )
                ) {

                    showFieldError(
                        "phone",
                        "Please enter a valid Philippine phone number."
                    );


                    valid = false;

                }


                /* =========================================
                   ADDRESS
                ========================================== */

                if (
                    address &&
                    address.value.trim() === ""
                ) {

                    showFieldError(
                        "address",
                        "Please add your address in your profile."
                    );


                    valid = false;

                }


                /* =========================================
                   VENUE
                ========================================== */

                if (
                    venueId &&
                    venueId.value === ""
                ) {

                    showFieldError(
                        "venue_id",
                        "Please select a venue."
                    );


                    valid = false;

                }


                /* =========================================
                   AGREEMENT
                ========================================== */

                if (
                    agreement &&
                    !agreement.checked
                ) {

                    const agreementError =
                        document.getElementById(
                            "agreementError"
                        );


                    if (agreementError) {

                        agreementError.textContent =
                            "Please agree to the booking terms.";

                    }


                    valid = false;

                }


                /* =========================================
                   INVALID
                ========================================== */

                if (!valid) {

                    event.preventDefault();

                    return;

                }


                /* =========================================
                   SUBMIT LOADING
                ========================================== */

                /*
                 * IMPORTANT:
                 *
                 * Do NOT use event.preventDefault()
                 * here.
                 *
                 * The normal PHP form submission
                 * must continue.
                 */

                showBookingLoading();

            }
        );

    }

});