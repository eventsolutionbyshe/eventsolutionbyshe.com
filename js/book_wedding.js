document.addEventListener(
    "DOMContentLoaded",
    function () {


        /* =====================================================
           FORM
        ====================================================== */

        const form =
            document.getElementById("bookingForm");

        const bookingForm =
            document.getElementById("bookingForm");


        if (!form) {
            return;
        }


        /* =====================================================
           ELEMENTS
        ====================================================== */

        const brideName =
            document.getElementById(
                "brideName"
            );

        const groomName =
            document.getElementById(
                "groomName"
            );

        const eventName =
            document.getElementById(
                "eventName"
            );

        const eventDate =
            document.getElementById(
                "eventDate"
            );

        const startTime =
            document.getElementById(
                "startTime"
            );

        const venueId =
            document.getElementById(
                "venueId"
            );

        const church =
            document.getElementById(
                "church"
            );

        const phone =
            document.getElementById(
                "phone"
            );

        const address =
            document.getElementById(
                "address"
            );

        const agreement =
            document.getElementById(
                "bookingAgreement"
            );

        const submitButton =
            document.getElementById(
                "submitBooking"
            );

        const specialRequests =
            document.getElementById(
                "specialRequests"
            );

        const characterCount =
            document.getElementById(
                "characterCount"
            );


        /* =====================================================
           LOADING POPUP
        ====================================================== */

        const bookingLoadingOverlay =
            document.getElementById(
                "bookingLoadingOverlay"
            );


        /* =====================================================
           SHOW LOADING POPUP
        ====================================================== */

        function showBookingLoading() {

            if (bookingLoadingOverlay) {

                bookingLoadingOverlay.classList.add(
                    "show"
                );

                bookingLoadingOverlay.setAttribute(
                    "aria-hidden",
                    "false"
                );

            }


            /*
             * Prevent scrolling while
             * the booking is processing.
             */

            document.body.style.overflow =
                "hidden";


            /*
             * Disable submit button.
             */

            if (submitButton) {

                submitButton.disabled =
                    true;

                submitButton.classList.add(
                    "processing"
                );

                submitButton.classList.add(
                    "is-loading"
                );


                /*
                 * Update button text.
                 */

                const buttonText =
                    submitButton.querySelector(
                        ".button-text"
                    );


                if (buttonText) {

                    buttonText.textContent =
                        "Processing Wedding Booking...";

                } else {

                    /*
                     * Fallback if
                     * .button-text does not exist.
                     */

                    const firstSpan =
                        submitButton.querySelector(
                            "span:first-child"
                        );


                    if (firstSpan) {

                        firstSpan.textContent =
                            "Processing Wedding Booking...";

                    }

                }

            }

        }


        /* =====================================================
           FLATPICKR WEDDING DATE PICKER
        ====================================================== */

        let weddingDatePicker = null;


        /*
         * Get booked wedding dates from PHP.
         *
         * New:
         * window.bookedWeddingDates
         *
         * Fallback:
         * window.confirmedBookingDates
         */

        const bookedWeddingDateSet =
            new Set(
                Array.isArray(
                    window.bookedWeddingDates
                )
                    ? window.bookedWeddingDates
                    : (
                        Array.isArray(
                            window.confirmedBookingDates
                        )
                            ? window.confirmedBookingDates
                            : []
                    )
            );


        /* =====================================================
           FORMAT DATE FOR COMPARISON
        ====================================================== */

        function formatDateForComparison(
            date
        ) {

            if (
                !(date instanceof Date)
            ) {

                return "";

            }


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


            return (
                year +
                "-" +
                month +
                "-" +
                day
            );

        }


        /* =====================================================
           CHECK BOOKED WEDDING DATE
        ====================================================== */

        function isBookedWeddingDate(
            value
        ) {

            let dateString =
                "";


            /*
             * Date object.
             */

            if (
                value instanceof Date
            ) {

                dateString =
                    formatDateForComparison(
                        value
                    );

            } else {

                /*
                 * String value.
                 */

                dateString =
                    String(
                        value || ""
                    ).trim();

            }


            return bookedWeddingDateSet.has(
                dateString
            );

        }


        /* =====================================================
           GET DISPLAY DATE
        ====================================================== */

        function getBookedDateDisplay(
            value
        ) {

            let date =
                null;


            /*
             * Date object.
             */

            if (
                value instanceof Date
            ) {

                date =
                    value;

            } else {

                const valueString =
                    String(
                        value || ""
                    ).trim();


                if (
                    !valueString
                ) {

                    return "This date";

                }


                /*
                 * Safely parse YYYY-MM-DD.
                 */

                const parts =
                    valueString.split(
                        "-"
                    );


                if (
                    parts.length === 3
                ) {

                    date =
                        new Date(
                            Number(
                                parts[0]
                            ),
                            Number(
                                parts[1]
                            ) - 1,
                            Number(
                                parts[2]
                            )
                        );

                }

            }


            if (
                !(date instanceof Date) ||
                isNaN(
                    date.getTime()
                )
            ) {

                return "This date";

            }


            return date.toLocaleDateString(
                "en-US",
                {
                    year:
                        "numeric",

                    month:
                        "long",

                    day:
                        "numeric"
                }
            );

        }


        /* =====================================================
           DATE PICKER STYLES
        ====================================================== */

        function addWeddingDatePickerStyles() {

            if (
                document.getElementById(
                    "weddingDatePickerStyles"
                )
            ) {

                return;

            }


            const style =
                document.createElement(
                    "style"
                );


            style.id =
                "weddingDatePickerStyles";


            style.textContent = `

                /* =========================================
                   FLATPICKR CALENDAR
                ========================================== */

                .flatpickr-calendar {

                    font-family:
                        Poppins,
                        sans-serif;

                    border:
                        1px solid
                        #e6dfd8;

                    border-radius:
                        14px;

                    box-shadow:
                        0 18px 45px
                        rgba(
                            36,
                            36,
                            36,
                            0.14
                        );

                    overflow:
                        hidden;

                }


                .flatpickr-months,
                .flatpickr-weekdays {

                    background:
                        #fcfaf7;

                }


                .flatpickr-current-month,
                .flatpickr-weekday {

                    color:
                        #242424;

                }


                .flatpickr-day {

                    border-radius:
                        8px;

                }


                /* =========================================
                   BOOKED DATE
                ========================================== */

                .flatpickr-day.confirmed-booking-date {

                    position:
                        relative !important;

                    color:
                        #9d7a32 !important;

                    background:
                        #f9e4d5 !important;

                    border-color:
                        #f3c7a8 !important;

                    font-weight:
                        700 !important;

                    cursor:
                        pointer !important;

                }


                .flatpickr-day.confirmed-booking-date:hover {

                    background:
                        #f3c7a8 !important;

                    border-color:
                        #b99445 !important;

                    color:
                        #242424 !important;

                }


                /* =========================================
                   BOOKED DATE MARKER
                ========================================== */

                .booking-date-mark {

                    position:
                        absolute;

                    left:
                        50%;

                    bottom:
                        3px;

                    width:
                        5px;

                    height:
                        5px;

                    border-radius:
                        50%;

                    transform:
                        translateX(-50%);

                    background:
                        #b99445;

                    pointer-events:
                        none;

                    box-shadow:
                        0 0 0 1px
                        rgba(
                            255,
                            255,
                            255,
                            0.55
                        );

                }


                /* =========================================
                   BOOKED DATE POPUP OVERLAY
                ========================================== */

                .wedding-booked-date-popup-overlay {

                    position:
                        fixed;

                    inset:
                        0;

                    z-index:
                        99999;

                    display:
                        flex;

                    align-items:
                        center;

                    justify-content:
                        center;

                    padding:
                        24px;

                    background:
                        rgba(
                            24,
                            24,
                            24,
                            0.52
                        );

                    backdrop-filter:
                        blur(4px);

                }


                /* =========================================
                   BOOKED DATE POPUP
                ========================================== */

                .wedding-booked-date-popup {

                    width:
                        min(
                            430px,
                            100%
                        );

                    padding:
                        34px 30px 30px;

                    text-align:
                        center;

                    background:
                        #ffffff;

                    border:
                        1px solid
                        #e6dfd8;

                    border-radius:
                        18px;

                    box-shadow:
                        0 25px 70px
                        rgba(
                            36,
                            36,
                            36,
                            0.22
                        );

                }


                /* =========================================
                   POPUP ICON
                ========================================== */

                .wedding-booked-date-popup-icon {

                    width:
                        58px;

                    height:
                        58px;

                    margin:
                        0 auto 18px;

                    display:
                        flex;

                    align-items:
                        center;

                    justify-content:
                        center;

                    border-radius:
                        50%;

                    background:
                        #f9e4d5;

                    color:
                        #9d7a32;

                    font-size:
                        25px;

                    font-weight:
                        700;

                }


                /* =========================================
                   POPUP TITLE
                ========================================== */

                .wedding-booked-date-popup h3 {

                    margin:
                        0 0 10px;

                    color:
                        #242424;

                    font-size:
                        21px;

                }


                /* =========================================
                   POPUP MESSAGE
                ========================================== */

                .wedding-booked-date-popup p {

                    margin:
                        0 0 22px;

                    color:
                        #6d665f;

                    line-height:
                        1.7;

                    font-size:
                        14px;

                }


                /* =========================================
                   POPUP BUTTON
                ========================================== */

                .wedding-booked-date-popup button {

                    width:
                        100%;

                    min-height:
                        46px;

                    border:
                        0;

                    border-radius:
                        10px;

                    background:
                        #b99445;

                    color:
                        #ffffff;

                    font-family:
                        Poppins,
                        sans-serif;

                    font-size:
                        14px;

                    font-weight:
                        600;

                    cursor:
                        pointer;

                    transition:
                        background
                        0.2s ease,

                        transform
                        0.2s ease;

                }


                .wedding-booked-date-popup button:hover {

                    background:
                        #9d7a32;

                    transform:
                        translateY(-1px);

                }

            `;


            document.head.appendChild(
                style
            );

        }


        /* =====================================================
           CLOSE BOOKED DATE POPUP
        ====================================================== */

        function closeBookedDatePopup() {

            const popup =
                document.getElementById(
                    "weddingBookedDatePopupOverlay"
                );


            if (popup) {

                popup.remove();

            }


            /*
             * Re-open calendar after
             * popup is closed.
             */

            if (
                weddingDatePicker
            ) {

                setTimeout(
                    function () {

                        weddingDatePicker.open();

                    },
                    50
                );

            }

        }


        /* =====================================================
           SHOW BOOKED DATE POPUP
        ====================================================== */

        function showBookedDatePopup(
            dateValue = ""
        ) {

            /*
             * Remove existing popup.
             */

            const existingPopup =
                document.getElementById(
                    "weddingBookedDatePopupOverlay"
                );


            if (
                existingPopup
            ) {

                existingPopup.remove();

            }


            const displayDate =
                getBookedDateDisplay(
                    dateValue
                );


            const overlay =
                document.createElement(
                    "div"
                );


            overlay.id =
                "weddingBookedDatePopupOverlay";


            overlay.className =
                "wedding-booked-date-popup-overlay";


            overlay.innerHTML = `

                <div
                    class="wedding-booked-date-popup"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="weddingBookedDatePopupTitle"
                >

                    <div
                        class="wedding-booked-date-popup-icon"
                        aria-hidden="true"
                    >
                        !
                    </div>


                    <h3
                        id="weddingBookedDatePopupTitle"
                    >
                        Date Already Booked
                    </h3>


                    <p>

                        ${displayDate}
                        is already reserved for
                        another wedding booking.

                        <br><br>

                        Please choose another
                        available wedding date.

                    </p>


                    <button
                        type="button"
                        id="closeWeddingBookedDatePopup"
                    >
                        Choose Another Date
                    </button>

                </div>

            `;


            document.body.appendChild(
                overlay
            );


            const closeButton =
                document.getElementById(
                    "closeWeddingBookedDatePopup"
                );


            if (
                closeButton
            ) {

                closeButton.addEventListener(
                    "click",
                    closeBookedDatePopup
                );

            }


            /*
             * Close when clicking
             * outside the popup.
             */

            overlay.addEventListener(
                "click",
                function (event) {

                    if (
                        event.target ===
                        overlay
                    ) {

                        closeBookedDatePopup();

                    }

                }
            );


            /*
             * Close with Escape.
             */

            function escapeHandler(
                event
            ) {

                if (
                    event.key ===
                    "Escape"
                ) {

                    closeBookedDatePopup();


                    document.removeEventListener(
                        "keydown",
                        escapeHandler
                    );

                }

            }


            document.addEventListener(
                "keydown",
                escapeHandler
            );


            /*
             * Focus button.
             */

            if (
                closeButton
            ) {

                setTimeout(
                    function () {

                        closeButton.focus();

                    },
                    50
                );

            }

        }


        /* =====================================================
           INITIALIZE WEDDING DATE PICKER
        ====================================================== */

        function initializeWeddingDatePicker() {

            if (
                !eventDate ||
                typeof flatpickr !==
                    "function"
            ) {

                return;

            }


            addWeddingDatePickerStyles();


            /*
             * Destroy an existing
             * Flatpickr instance.
             */

            if (
                weddingDatePicker
            ) {

                weddingDatePicker.destroy();

                weddingDatePicker =
                    null;

            }


            weddingDatePicker =
                flatpickr(
                    eventDate,
                    {

                        dateFormat:
                            "Y-m-d",


                        minDate:
                            "today",


                        closeOnSelect:
                            true,


                        altInput:
                            true,


                        altFormat:
                            "F j, Y",


                        disableMobile:
                            true,


                        allowInput:
                            false,


                        clickOpens:
                            true,


                        /* =================================
                           MARK BOOKED DATES
                        ================================== */

                        onDayCreate:
                            function (
                                dObj,
                                dStr,
                                fp,
                                dayElem
                            ) {

                                const date =
                                    dayElem.dateObj;


                                if (
                                    !date
                                ) {

                                    return;

                                }


                                const dateString =
                                    formatDateForComparison(
                                        date
                                    );


                                if (
                                    isBookedWeddingDate(
                                        dateString
                                    )
                                ) {

                                    dayElem.classList.add(
                                        "confirmed-booking-date"
                                    );


                                    dayElem.setAttribute(
                                        "title",
                                        "This date is already booked"
                                    );


                                    dayElem.setAttribute(
                                        "aria-label",
                                        getBookedDateDisplay(
                                            date
                                        ) +
                                        " - Already booked"
                                    );


                                    /*
                                     * Prevent duplicate
                                     * markers when
                                     * Flatpickr redraws.
                                     */

                                    if (
                                        !dayElem.querySelector(
                                            ".booking-date-mark"
                                        )
                                    ) {

                                        const marker =
                                            document.createElement(
                                                "span"
                                            );


                                        marker.className =
                                            "booking-date-mark";


                                        marker.setAttribute(
                                            "aria-hidden",
                                            "true"
                                        );


                                        dayElem.appendChild(
                                            marker
                                        );

                                    }

                                }

                            },


                        /* =================================
                           DATE CHANGE
                        ================================== */

                        onChange:
                            function (
                                selectedDates
                            ) {

                                if (
                                    !selectedDates ||
                                    !selectedDates.length
                                ) {

                                    return;

                                }


                                const selectedDate =
                                    selectedDates[0];


                                if (
                                    isBookedWeddingDate(
                                        selectedDate
                                    )
                                ) {

                                    if (
                                        weddingDatePicker
                                    ) {

                                        weddingDatePicker.clear();

                                    }


                                    eventDate.value =
                                        "";


                                    showBookedDatePopup(
                                        selectedDate
                                    );


                                    return;

                                }


                                clearError(
                                    "event_date"
                                );

                            }

                    }
                );


            /* =================================================
               BOOKED DATE CLICK PROTECTION
            ================================================= */

            /*
             * Capture the click before
             * Flatpickr selects the date.
             */

            document.addEventListener(
                "click",
                function (event) {

                    const target =
                        event.target.closest(
                            ".flatpickr-day.confirmed-booking-date"
                        );


                    if (
                        !target
                    ) {

                        return;

                    }


                    event.preventDefault();

                    event.stopPropagation();

                    event.stopImmediatePropagation();


                    const bookedDate =
                        target.dateObj;


                    if (
                        weddingDatePicker
                    ) {

                        weddingDatePicker.clear();

                    }


                    eventDate.value =
                        "";


                    showBookedDatePopup(
                        bookedDate
                    );

                },
                true
            );


            /* =================================================
               MANUAL DATE PROTECTION
            ================================================= */

            eventDate.addEventListener(
                "change",
                function () {

                    const value =
                        eventDate.value.trim();


                    if (
                        isBookedWeddingDate(
                            value
                        )
                    ) {

                        if (
                            weddingDatePicker
                        ) {

                            weddingDatePicker.clear();

                        }


                        eventDate.value =
                            "";


                        showBookedDatePopup(
                            value
                        );

                    }

                }
            );

        }


        /* =====================================================
           ERROR HELPERS
        ====================================================== */

        function getErrorElement(
            fieldName
        ) {

            return document.querySelector(
                '[data-error-for="' +
                    fieldName +
                    '"]'
            );

        }


        function showError(
            fieldName,
            message
        ) {

            const errorElement =
                getErrorElement(
                    fieldName
                );


            if (
                errorElement
            ) {

                errorElement.textContent =
                    message;

                errorElement.classList.add(
                    "show"
                );

            }

        }


        function clearError(
            fieldName
        ) {

            const errorElement =
                getErrorElement(
                    fieldName
                );


            if (
                errorElement
            ) {

                errorElement.textContent =
                    "";

                errorElement.classList.remove(
                    "show"
                );

            }

        }


        function clearAllErrors() {

            const errors =
                document.querySelectorAll(
                    ".field-error"
                );


            errors.forEach(
                function (error) {

                    error.textContent =
                        "";

                    error.classList.remove(
                        "show"
                    );

                }
            );


            if (
                agreement
            ) {

                agreement.classList.remove(
                    "input-error"
                );

            }

        }


        /* =====================================================
           UPDATE EVENT NAME
        ====================================================== */

        function updateEventName() {

            if (
                !eventName
            ) {

                return;

            }


            const bride =
                brideName
                    ? brideName.value.trim()
                    : "";


            const groom =
                groomName
                    ? groomName.value.trim()
                    : "";


            if (
                bride &&
                groom
            ) {

                eventName.value =
                    bride +
                    " & " +
                    groom +
                    " Wedding";

            } else if (
                bride
            ) {

                eventName.value =
                    bride +
                    " Wedding";

            } else if (
                groom
            ) {

                eventName.value =
                    groom +
                    " Wedding";

            } else {

                eventName.value =
                    "Wedding Event";

            }

        }


        /* =====================================================
           CHARACTER COUNT
        ====================================================== */

        function updateCharacterCount() {

            if (
                !specialRequests ||
                !characterCount
            ) {

                return;

            }


            const currentLength =
                specialRequests.value.length;


            characterCount.textContent =
                currentLength +
                " / 2000";

        }


        if (
            specialRequests
        ) {

            specialRequests.addEventListener(
                "input",
                updateCharacterCount
            );

        }        /* =====================================================
           LIVE ERROR CLEARING
        ====================================================== */

        const clearFieldError =
            function () {

                const field =
                    this;


                if (
                    field.id ===
                    "brideName"
                ) {

                    clearError(
                        "bride_name"
                    );

                }


                if (
                    field.id ===
                    "groomName"
                ) {

                    clearError(
                        "groom_name"
                    );

                }


                if (
                    field.id ===
                    "eventDate"
                ) {

                    clearError(
                        "event_date"
                    );

                }


                if (
                    field.id ===
                    "startTime"
                ) {

                    clearError(
                        "start_time"
                    );

                }


                if (
                    field.id ===
                    "venueId"
                ) {

                    clearError(
                        "venue_id"
                    );

                }


                if (
                    field.id ===
                    "church"
                ) {

                    clearError(
                        "church"
                    );

                }


                if (
                    field.id ===
                    "phone"
                ) {

                    clearError(
                        "phone"
                    );

                }


                if (
                    field.id ===
                    "address"
                ) {

                    clearError(
                        "address"
                    );

                }

            };


        if (
            brideName
        ) {

            brideName.addEventListener(
                "input",
                clearFieldError
            );

        }


        if (
            groomName
        ) {

            groomName.addEventListener(
                "input",
                clearFieldError
            );

        }


        if (
            eventDate
        ) {

            eventDate.addEventListener(
                "input",
                clearFieldError
            );

        }


        if (
            startTime
        ) {

            startTime.addEventListener(
                "input",
                clearFieldError
            );

        }


        if (
            venueId
        ) {

            venueId.addEventListener(
                "change",
                clearFieldError
            );

        }


        if (
            church
        ) {

            church.addEventListener(
                "input",
                clearFieldError
            );

        }


        if (
            phone
        ) {

            phone.addEventListener(
                "input",
                clearFieldError
            );

        }


        if (
            address
        ) {

            address.addEventListener(
                "input",
                clearFieldError
            );

        }


        /* =====================================================
           NAME INPUT VALIDATION
        ====================================================== */

        function validateName(
            field,
            errorName,
            label
        ) {

            if (
                !field
            ) {

                return true;

            }


            const value =
                field.value.trim();


            if (
                value === ""
            ) {

                showError(
                    errorName,
                    "Please enter the " +
                        label +
                        "."
                );

                return false;

            }


            if (
                value.length < 2
            ) {

                showError(
                    errorName,
                    label +
                        " must contain at least 2 characters."
                );

                return false;

            }


            if (
                value.length > 150
            ) {

                showError(
                    errorName,
                    label +
                        " cannot exceed 150 characters."
                );

                return false;

            }


            return true;

        }


        /* =====================================================
           FORM SUBMIT
        ====================================================== */

        form.addEventListener(
            "submit",
            function (event) {

                event.preventDefault();


                clearAllErrors();


                let valid =
                    true;


                let firstInvalid =
                    null;


                /* ---------------------------------------------
                   BRIDE NAME
                --------------------------------------------- */

                if (
                    !validateName(
                        brideName,
                        "bride_name",
                        "bride name"
                    )
                ) {

                    valid =
                        false;


                    if (
                        !firstInvalid
                    ) {

                        firstInvalid =
                            brideName;

                    }

                }


                /* ---------------------------------------------
                   GROOM NAME
                --------------------------------------------- */

                if (
                    !validateName(
                        groomName,
                        "groom_name",
                        "groom name"
                    )
                ) {

                    valid =
                        false;


                    if (
                        !firstInvalid
                    ) {

                        firstInvalid =
                            groomName;

                    }

                }


                /* ---------------------------------------------
                   DATE
                --------------------------------------------- */

                if (
                    !eventDate ||
                    eventDate.value.trim() === ""
                ) {

                    showError(
                        "event_date",
                        "Please select your wedding date."
                    );

                    valid =
                        false;


                    if (
                        !firstInvalid
                    ) {

                        firstInvalid =
                            eventDate;

                    }

                } else {

                    const selectedDate =
                        new Date(
                            eventDate.value +
                            "T00:00:00"
                        );


                    const today =
                        new Date();


                    today.setHours(
                        0,
                        0,
                        0,
                        0
                    );


                    if (
                        selectedDate < today
                    ) {

                        showError(
                            "event_date",
                            "Wedding date cannot be in the past."
                        );

                        valid =
                            false;


                        if (
                            !firstInvalid
                        ) {

                            firstInvalid =
                                eventDate;

                        }

                    } else if (
                        isBookedWeddingDate(
                            eventDate.value.trim()
                        )
                    ) {

                        showError(
                            "event_date",
                            "This wedding date is already booked. Please choose another date."
                        );

                        valid =
                            false;


                        if (
                            !firstInvalid
                        ) {

                            firstInvalid =
                                eventDate;

                        }

                    }

                }


                /* ---------------------------------------------
                   TIME
                --------------------------------------------- */

                if (
                    !startTime ||
                    startTime.value.trim() === ""
                ) {

                    showError(
                        "start_time",
                        "Please select your wedding time."
                    );

                    valid =
                        false;


                    if (
                        !firstInvalid
                    ) {

                        firstInvalid =
                            startTime;

                    }

                }


                /* ---------------------------------------------
                   VENUE
                --------------------------------------------- */

                if (
                    !venueId ||
                    venueId.value.trim() === ""
                ) {

                    showError(
                        "venue_id",
                        "Please select a wedding venue."
                    );

                    valid =
                        false;


                    if (
                        !firstInvalid
                    ) {

                        firstInvalid =
                            venueId;

                    }

                }


                /* ---------------------------------------------
                   CHURCH
                --------------------------------------------- */

                if (
                    !church ||
                    church.value.trim() === ""
                ) {

                    showError(
                        "church",
                        "Please enter the church name."
                    );

                    valid =
                        false;


                    if (
                        !firstInvalid
                    ) {

                        firstInvalid =
                            church;

                    }

                } else if (
                    church.value.trim().length > 255
                ) {

                    showError(
                        "church",
                        "Church name cannot exceed 255 characters."
                    );

                    valid =
                        false;


                    if (
                        !firstInvalid
                    ) {

                        firstInvalid =
                            church;

                    }

                }


                /* ---------------------------------------------
                   PHONE
                --------------------------------------------- */

                if (
                    !phone ||
                    phone.value.trim() === ""
                ) {

                    showError(
                        "phone",
                        "Please enter your phone number."
                    );

                    valid =
                        false;


                    if (
                        !firstInvalid
                    ) {

                        firstInvalid =
                            phone;

                    }

                } else if (
                    phone.value.trim().length < 7
                ) {

                    showError(
                        "phone",
                        "Please enter a valid phone number."
                    );

                    valid =
                        false;


                    if (
                        !firstInvalid
                    ) {

                        firstInvalid =
                            phone;

                    }

                }


                /* ---------------------------------------------
                   ADDRESS
                --------------------------------------------- */

                if (
                    !address ||
                    address.value.trim() === ""
                ) {

                    showError(
                        "address",
                        "Please add your address to your profile before booking."
                    );

                    valid =
                        false;


                    if (
                        !firstInvalid
                    ) {

                        firstInvalid =
                            address;

                    }

                }


                /* ---------------------------------------------
                   AGREEMENT
                --------------------------------------------- */

                if (
                    !agreement ||
                    !agreement.checked
                ) {

                    valid =
                        false;


                    if (
                        agreement
                    ) {

                        agreement.classList.add(
                            "input-error"
                        );


                        if (
                            !firstInvalid
                        ) {

                            firstInvalid =
                                agreement;

                        }

                    }

                }


                /* ---------------------------------------------
                   STOP INVALID FORM
                --------------------------------------------- */

                if (
                    !valid
                ) {

                    if (
                        firstInvalid
                    ) {

                        firstInvalid.scrollIntoView(
                            {
                                behavior:
                                    "smooth",

                                block:
                                    "center"
                            }
                        );


                        setTimeout(
                            function () {

                                try {

                                    firstInvalid.focus();

                                } catch (
                                    error
                                ) {

                                    /*
                                     * Ignore focus errors.
                                     */

                                }

                            },
                            300
                        );

                    }


                    return;

                }


                /* ---------------------------------------------
                   UPDATE EVENT NAME
                --------------------------------------------- */

                updateEventName();


                /* ---------------------------------------------
                   PREVENT DOUBLE SUBMISSION
                --------------------------------------------- */

                if (
                    submitButton &&
                    submitButton.disabled
                ) {

                    return;

                }


                /* ---------------------------------------------
                   SHOW LOADING POPUP
                --------------------------------------------- */

                showBookingLoading();


                /* ---------------------------------------------
                   SUBMIT FORM
                --------------------------------------------- */

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


        /* =====================================================
           AGREEMENT ERROR CLEAR
        ====================================================== */

        if (
            agreement
        ) {

            agreement.addEventListener(
                "change",
                function () {

                    agreement.classList.remove(
                        "input-error"
                    );

                }
            );

        }


        /* =====================================================
           INITIALIZE
        ====================================================== */

        initializeWeddingDatePicker();


        updateEventName();


        updateCharacterCount();


    }
);