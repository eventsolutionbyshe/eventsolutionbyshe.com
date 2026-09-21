document.addEventListener(
    "DOMContentLoaded",
    function () {

        const bookingId =
            window.guestListConfig.bookingId;

        const bookingType =
            window.guestListConfig.bookingType;


        const capacityNumber =
            document.getElementById(
                "capacityNumber"
            );


        const confirmedNumber =
            document.getElementById(
                "confirmedNumber"
            );


        const remainingNumber =
            document.getElementById(
                "remainingNumber"
            );


        const guestTable =
            document.getElementById(
                "guestTable"
            );


        const guestTableBody =
            document.getElementById(
                "guestTableBody"
            );


        const emptyGuests =
            document.getElementById(
                "emptyGuests"
            );


        /*
        |--------------------------------------------------------------------------
        | INVITATION LINK
        |--------------------------------------------------------------------------
        */

        const linkInput =
            document.getElementById(
                "invitationLink"
            );


        const copyButton =
            document.getElementById(
                "copyLinkButton"
            );


        const shareButton =
            document.getElementById(
                "shareButton"
            );


        if (copyButton && linkInput) {

            copyButton.addEventListener(
                "click",
                async function () {

                    try {

                        await navigator.clipboard.writeText(
                            linkInput.value
                        );


                        copyButton.innerHTML = `

                            <span class="material-symbols-outlined">

                                check

                            </span>

                            Copied

                        `;


                        setTimeout(
                            function () {

                                copyButton.innerHTML = `

                                    <span class="material-symbols-outlined">

                                        content_copy

                                    </span>

                                    Copy

                                `;

                            },
                            1800
                        );


                    } catch (error) {

                        linkInput.select();

                        document.execCommand(
                            "copy"
                        );

                    }

                }
            );

        }


        /*
        |--------------------------------------------------------------------------
        | SHARE
        |--------------------------------------------------------------------------
        */

        if (shareButton && linkInput) {

            shareButton.addEventListener(
                "click",
                async function () {

                    const link =
                        linkInput.value;


                    if (
                        navigator.share
                    ) {

                        try {

                            await navigator.share(

                                {

                                    title:
                                        window.guestListConfig.eventName,

                                    text:
                                        "You're invited to this event.",

                                    url:
                                        link

                                }

                            );

                        } catch (error) {

                            /*
                             * User cancelled.
                             */

                        }


                    } else {

                        try {

                            await navigator.clipboard.writeText(
                                link
                            );


                            alert(
                                "Invitation link copied. You can paste it into Messenger or another app."
                            );


                        } catch (error) {

                            alert(
                                link
                            );

                        }

                    }

                }
            );

        }


        /*
        |--------------------------------------------------------------------------
        | GUEST TABLE
        |--------------------------------------------------------------------------
        */

        let knownGuestIds =
            new Set();


        function initializeKnownGuests() {

            document
                .querySelectorAll(
                    "#guestTableBody tr[data-guest-id]"
                )
                .forEach(
                    function (row) {

                        knownGuestIds.add(
                            String(
                                row.dataset.guestId
                            )
                        );

                    }
                );

        }


        initializeKnownGuests();


        /*
        |--------------------------------------------------------------------------
        | ESCAPE HTML
        |--------------------------------------------------------------------------
        */

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


        /*
        |--------------------------------------------------------------------------
        | FORMAT DATE
        |--------------------------------------------------------------------------
        */

        function formatDate(value) {

            if (!value) {

                return "—";

            }


            const date =
                new Date(
                    value.replace(
                        " ",
                        "T"
                    )
                );


            if (
                Number.isNaN(
                    date.getTime()
                )
            ) {

                return value;

            }


            return date.toLocaleString(
                undefined,
                {

                    month:
                        "short",

                    day:
                        "2-digit",

                    year:
                        "numeric",

                    hour:
                        "2-digit",

                    minute:
                        "2-digit"

                }
            );

        }


        /*
        |--------------------------------------------------------------------------
        | RENDER TABLE
        |--------------------------------------------------------------------------
        */

        function renderGuests(
            guests
        ) {

            if (!guestTableBody) {

                return;

            }


            if (
                !guests ||
                guests.length === 0
            ) {

                guestTableBody.innerHTML =
                    "";


                if (guestTable) {

                    guestTable.style.display =
                        "none";

                }


                if (emptyGuests) {

                    emptyGuests.style.display =
                        "block";

                }


                return;

            }


            if (guestTable) {

                guestTable.style.display =
                    "table";

            }


            if (emptyGuests) {

                emptyGuests.style.display =
                    "none";

            }


            guestTableBody.innerHTML =
                "";


            guests.forEach(
                function (guest, index) {

                    const status =
                        String(
                            guest.status ||
                            "pending"
                        ).toLowerCase();


                    const qrHtml =
                        guest.qrcode_image

                            ? `

                                <a

                                    href="${escapeHtml(guest.qrcode_image)}"

                                    class="qr-button"

                                    target="_blank"

                                    rel="noopener"

                                    title="View QR code"

                                >

                                    <span class="material-symbols-outlined">

                                        qr_code_2

                                    </span>

                                </a>

                            `

                            : "—";


                    const row =
                        document.createElement(
                            "tr"
                        );


                    row.dataset.guestId =
                        guest.id;


                    if (
                        !knownGuestIds.has(
                            String(
                                guest.id
                            )
                        )
                    ) {

                        row.classList.add(
                            "new-row"
                        );


                        knownGuestIds.add(
                            String(
                                guest.id
                            )
                        );

                    }


                    row.innerHTML = `

                        <td>

                            <span class="guest-number">

                                ${index + 1}

                            </span>

                        </td>


                        <td>

                            <div class="guest-name">

                                ${escapeHtml(
                                    guest.guest_name
                                )}

                            </div>

                        </td>


                        <td>

                            <div class="guest-email">

                                ${escapeHtml(
                                    guest.guest_email
                                )}

                            </div>

                        </td>


                        <td>

                            <span

                                class="status status-${escapeHtml(status)}"

                            >

                                ${escapeHtml(

                                    status.charAt(0).toUpperCase() +

                                    status.slice(1)

                                )}

                            </span>

                        </td>


                        <td>

                            ${escapeHtml(

                                formatDate(
                                    guest.confirmed_at
                                )

                            )}

                        </td>


                        <td>

                            ${qrHtml}

                        </td>

                    `;


                    guestTableBody.appendChild(
                        row
                    );

                }
            );

        }


        /*
        |--------------------------------------------------------------------------
        | FETCH GUESTS
        |--------------------------------------------------------------------------
        */

        let requestInProgress =
            false;


        async function fetchGuests() {

            if (requestInProgress) {

                return;

            }


            requestInProgress =
                true;


            try {

                const url =
                    "guestlist.php" +

                    "?action=fetch_guests" +

                    "&booking_id=" +

                    encodeURIComponent(
                        bookingId
                    ) +

                    "&booking_type=" +

                    encodeURIComponent(
                        bookingType
                    );


                const response =
                    await fetch(
                        url,
                        {

                            method:
                                "GET",

                            cache:
                                "no-store",

                            headers: {

                                "Accept":
                                    "application/json"

                            }

                        }
                    );


                if (!response.ok) {

                    throw new Error(
                        "Request failed."
                    );

                }


                const data =
                    await response.json();


                if (!data.success) {

                    return;

                }


                /*
                 * Update statistics.
                 */

                if (capacityNumber) {

                    capacityNumber.textContent =
                        Number(
                            data.capacity || 0
                        ).toLocaleString();

                }


                if (confirmedNumber) {

                    confirmedNumber.textContent =
                        Number(
                            data.confirmed || 0
                        ).toLocaleString();

                }


                if (remainingNumber) {

                    remainingNumber.textContent =
                        Number(
                            data.remaining || 0
                        ).toLocaleString();

                }


                /*
                 * Render latest guest list.
                 *
                 * Because SQL uses ORDER BY id DESC,
                 * newest guests automatically appear
                 * at the top.
                 */

                renderGuests(
                    data.guests || []
                );


            } catch (error) {

                console.error(
                    "Guest list update failed:",
                    error
                );


            } finally {

                requestInProgress =
                    false;

            }

        }


        /*
        |--------------------------------------------------------------------------
        | INITIAL REFRESH
        |--------------------------------------------------------------------------
        */

        fetchGuests();


        /*
        |--------------------------------------------------------------------------
        | AUTO UPDATE EVERY 3 SECONDS
        |--------------------------------------------------------------------------
        */

        setInterval(
            fetchGuests,
            3000
        );

    }
);