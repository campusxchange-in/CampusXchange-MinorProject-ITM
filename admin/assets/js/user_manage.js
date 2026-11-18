/* =====================================================
   CAMPUSXCHANGE
   USER MANAGEMENT JAVASCRIPT

   Handles:
   - Status confirmation
   - Verification approval
   - Verification rejection
   - Logout all devices
   - Delete confirmation
   - Modal closing
   - Escape key
   - Character counter
===================================================== */


/* =====================================================
   GLOBAL FORM REFERENCES
===================================================== */

let statusForm = null;
let approveVerificationForm = null;
let logoutAllForm = null;


/* =====================================================
   DOM READY
===================================================== */

document.addEventListener('DOMContentLoaded', function () {

    const statusFormElement =
        document.getElementById('statusForm');

    const statusOptions =
        document.querySelectorAll(
            '.status-option input[name="status"]'
        );

    const reasonField =
        document.getElementById('status_reason');

    const reasonRequired =
        document.getElementById('statusReasonRequired');

    const reasonHelp =
        document.getElementById('statusReasonHelp');

    const reasonCounter =
        document.getElementById('statusReasonCounter');


    /* =================================================
       STATUS UI
    ================================================= */

    function updateStatusSelection() {

        const selected =
            document.querySelector(
                'input[name="status"]:checked'
            );

        if (!selected) {
            return;
        }


        document.querySelectorAll(
            '.status-option'
        ).forEach(function (option) {

            option.classList.remove('selected');

        });


        const selectedOption =
            selected.closest('.status-option');

        if (selectedOption) {
            selectedOption.classList.add('selected');
        }


        const status = selected.value;

        const requiresReason =
            status === 'Suspended' ||
            status === 'Banned';


        if (reasonRequired) {

            reasonRequired.style.display =
                requiresReason
                    ? 'inline'
                    : 'none';

        }


        if (reasonHelp) {

            reasonHelp.textContent =
                requiresReason
                    ? 'A reason is required for this status change.'
                    : 'A reason is optional for activating an account.';

        }


        if (reasonField) {

            if (requiresReason) {

                reasonField.classList.add(
                    'required-reason'
                );

                reasonField.placeholder =
                    'Explain why this account is being '
                    + status.toLowerCase()
                    + '...';

            } else {

                reasonField.classList.remove(
                    'required-reason'
                );

                reasonField.placeholder =
                    'Explain why you are changing this user\'s account status...';

            }

        }

    }


    /* =================================================
       STATUS RADIO EVENTS
    ================================================= */

    statusOptions.forEach(function (radio) {

        radio.addEventListener(
            'change',
            updateStatusSelection
        );

    });


    /* =================================================
       REASON COUNTER
    ================================================= */

    if (reasonField && reasonCounter) {

        function updateReasonCounter() {

            reasonCounter.textContent =
                reasonField.value.length
                + ' / 255';

        }


        reasonField.addEventListener(
            'input',
            function () {

                updateReasonCounter();

                reasonField.classList.remove(
                    'field-error'
                );

            }
        );


        updateReasonCounter();

    }


    /* =================================================
       STATUS FORM
    ================================================= */

    if (statusFormElement) {

        statusFormElement.addEventListener(
            'submit',
            function (event) {

                event.preventDefault();
                event.stopPropagation();


                const selected =
                    document.querySelector(
                        'input[name="status"]:checked'
                    );


                if (!selected) {

                    showStatusValidation(
                        'Please select an account status.'
                    );

                    return;

                }


                const newStatus =
                    selected.value;


                const currentStatus =
                    statusFormElement.dataset.currentStatus
                    || window.CX_CURRENT_STATUS
                    || '';


                const reason =
                    reasonField
                        ? reasonField.value.trim()
                        : '';


                const requiresReason =
                    newStatus === 'Suspended' ||
                    newStatus === 'Banned';


                if (newStatus === currentStatus) {

                    showStatusValidation(
                        'Please select a different account status.'
                    );

                    return;

                }


                if (
                    requiresReason &&
                    reason === ''
                ) {

                    if (reasonField) {

                        reasonField.classList.add(
                            'field-error'
                        );

                        reasonField.focus();

                    }

                    showStatusValidation(
                        'Please provide a reason for changing the account status to '
                        + newStatus
                        + '.'
                    );

                    return;

                }


                if (
                    requiresReason &&
                    reason.length > 255
                ) {

                    if (reasonField) {

                        reasonField.classList.add(
                            'field-error'
                        );

                    }

                    showStatusValidation(
                        'The status change reason must not exceed 255 characters.'
                    );

                    return;

                }


                if (reasonField) {

                    reasonField.classList.remove(
                        'field-error'
                    );

                }


                statusForm =
                    statusFormElement;


                openStatusModal(
                    currentStatus,
                    newStatus,
                    reason
                );

            },
            true
        );

    }


    /* =================================================
       CURRENT STATUS
    ================================================= */

    /*
     * The PHP page does not need another inline script.
     *
     * Read the current status directly from the
     * visible status badge.
     */

    const currentStatusElement =
        document.querySelector(
            '.manage-current-status .manage-status'
        );


    if (
        statusFormElement &&
        currentStatusElement
    ) {

        statusFormElement.dataset.currentStatus =
            currentStatusElement.textContent.trim();

    }


    /* =================================================
       INITIAL STATUS UI
    ================================================= */

    updateStatusSelection();


    /* =================================================
       APPROVE VERIFICATION FORM
    ================================================= */

    const approveForm =
        document.querySelector(
            '.approve-verification-form'
        );


    if (approveForm) {

        approveForm.addEventListener(
            'submit',
            function (event) {

                event.preventDefault();
                event.stopPropagation();

                approveVerificationForm =
                    approveForm;

                openApproveModal();

            },
            true
        );

    }


    /* =================================================
       LOGOUT FORM
    ================================================= */

    const logoutFormElement =
        document.getElementById(
            'logoutAllForm'
        );


    if (logoutFormElement) {

        logoutFormElement.addEventListener(
            'submit',
            function (event) {

                event.preventDefault();
                event.stopPropagation();

                logoutAllForm =
                    logoutFormElement;

                openLogoutModal();

            },
            true
        );

    }


    /* =================================================
       DELETE FORM VALIDATION
    ================================================= */

    const deleteConfirmation =
        document.getElementById(
            'delete_confirmation'
        );


    if (deleteConfirmation) {

        deleteConfirmation.addEventListener(
            'input',
            function () {

                this.value =
                    this.value.toUpperCase();

            }
        );

    }

});


/* =====================================================
   SHOW STATUS VALIDATION
===================================================== */

function showStatusValidation(message) {

    const modal =
        document.getElementById(
            'statusConfirmModal'
        );

    const warning =
        document.getElementById(
            'statusModalWarning'
        );

    const reasonBox =
        document.getElementById(
            'statusModalReasonBox'
        );

    const confirmButton =
        document.getElementById(
            'confirmStatusButton'
        );


    if (!modal || !warning) {
        return;
    }


    warning.textContent =
        message;

    warning.style.display =
        'block';


    if (reasonBox) {
        reasonBox.style.display = 'none';
    }


    modal.classList.add('show');

    modal.setAttribute(
        'aria-hidden',
        'false'
    );

    document.body.classList.add(
        'modal-open'
    );


    if (confirmButton) {

        confirmButton.textContent =
            'Close';

        confirmButton.onclick =
            function () {

                closeStatusModal();

            };

    }

}


/* =====================================================
   GENERIC FORM SUBMISSION
===================================================== */

function submitForm(form) {

    if (!form) {
        return;
    }


    HTMLFormElement.prototype.submit.call(
        form
    );

}


/* =====================================================
   OPEN STATUS MODAL
===================================================== */

function openStatusModal(
    currentStatus,
    newStatus,
    reason
) {

    const modal =
        document.getElementById(
            'statusConfirmModal'
        );


    if (!modal) {
        return;
    }


    const currentElement =
        document.getElementById(
            'statusModalCurrent'
        );

    const newElement =
        document.getElementById(
            'statusModalNew'
        );

    const warningElement =
        document.getElementById(
            'statusModalWarning'
        );

    const reasonBox =
        document.getElementById(
            'statusModalReasonBox'
        );

    const reasonElement =
        document.getElementById(
            'statusModalReason'
        );

    const confirmButton =
        document.getElementById(
            'confirmStatusButton'
        );


    if (currentElement) {

        currentElement.textContent =
            currentStatus;

    }


    if (newElement) {

        newElement.textContent =
            newStatus;

        newElement.className =
            'status-modal-new status-modal-'
            + newStatus.toLowerCase();

    }


    if (warningElement) {

        let warningMessage =
            'The account status will be changed.';


        if (newStatus === 'Suspended') {

            warningMessage =
                'This will temporarily restrict the user account and invalidate existing sessions.';

        }

        else if (newStatus === 'Banned') {

            warningMessage =
                'This will block the user account and invalidate existing sessions.';

        }

        else if (newStatus === 'Active') {

            warningMessage =
                'The user will regain normal access to CampusXchange.';

        }


        warningElement.textContent =
            warningMessage;

        warningElement.style.display =
            'block';

    }


    if (reasonBox && reasonElement) {

        reasonElement.textContent =
            reason !== ''
                ? reason
                : 'No reason provided.';

        reasonBox.style.display =
            'block';

    }


    if (confirmButton) {

        confirmButton.textContent =
            'Confirm Status Change';

        confirmButton.onclick =
            function () {

                confirmStatusChange();

            };

    }


    modal.classList.add('show');

    modal.setAttribute(
        'aria-hidden',
        'false'
    );

    document.body.classList.add(
        'modal-open'
    );

}


/* =====================================================
   CLOSE STATUS MODAL
===================================================== */

function closeStatusModal() {

    const modal =
        document.getElementById(
            'statusConfirmModal'
        );


    if (!modal) {
        return;
    }


    modal.classList.remove(
        'show'
    );

    modal.setAttribute(
        'aria-hidden',
        'true'
    );

    document.body.classList.remove(
        'modal-open'
    );


    const warning =
        document.getElementById(
            'statusModalWarning'
        );


    const reasonBox =
        document.getElementById(
            'statusModalReasonBox'
        );


    if (warning) {
        warning.style.display = 'none';
        warning.textContent = '';
    }


    if (reasonBox) {
        reasonBox.style.display = 'none';
    }


    const confirmButton =
        document.getElementById(
            'confirmStatusButton'
        );


    if (confirmButton) {

        confirmButton.textContent =
            'Confirm Status Change';

        confirmButton.onclick = null;

    }


    statusForm = null;

}


/* =====================================================
   CONFIRM STATUS CHANGE
===================================================== */

function confirmStatusChange() {

    if (!statusForm) {

        closeStatusModal();

        return;

    }


    const form =
        statusForm;


    statusForm = null;

    closeStatusModal();

    submitForm(form);

}


/* =====================================================
   OPEN APPROVE MODAL
===================================================== */

function openApproveModal() {

    const modal =
        document.getElementById(
            'approveVerificationModal'
        );


    if (!modal) {
        return;
    }


    modal.classList.add(
        'show'
    );

    modal.setAttribute(
        'aria-hidden',
        'false'
    );

    document.body.classList.add(
        'modal-open'
    );

}


/* =====================================================
   CLOSE APPROVE MODAL
===================================================== */

function closeApproveModal() {

    const modal =
        document.getElementById(
            'approveVerificationModal'
        );


    if (!modal) {
        return;
    }


    modal.classList.remove(
        'show'
    );

    modal.setAttribute(
        'aria-hidden',
        'true'
    );

    document.body.classList.remove(
        'modal-open'
    );


    approveVerificationForm =
        null;

}


/* =====================================================
   CONFIRM APPROVE
===================================================== */

function confirmApproveVerification() {

    if (!approveVerificationForm) {

        closeApproveModal();

        return;

    }


    const form =
        approveVerificationForm;


    approveVerificationForm =
        null;

    closeApproveModal();

    submitForm(form);

}


/* =====================================================
   OPEN REJECT MODAL
===================================================== */

function openRejectModal() {

    const modal =
        document.getElementById(
            'rejectModal'
        );


    if (!modal) {
        return;
    }


    modal.classList.add(
        'show'
    );

    modal.setAttribute(
        'aria-hidden',
        'false'
    );

    document.body.classList.add(
        'modal-open'
    );


    const textarea =
        document.getElementById(
            'rejection_reason'
        );


    if (textarea) {

        textarea.value = '';

        setTimeout(
            function () {

                textarea.focus();

            },
            100
        );

    }

}


/* =====================================================
   CLOSE REJECT MODAL
===================================================== */

function closeRejectModal() {

    const modal =
        document.getElementById(
            'rejectModal'
        );


    if (!modal) {
        return;
    }


    modal.classList.remove(
        'show'
    );

    modal.setAttribute(
        'aria-hidden',
        'true'
    );

    document.body.classList.remove(
        'modal-open'
    );

}


/* =====================================================
   OPEN LOGOUT MODAL
===================================================== */

function openLogoutModal() {

    const modal =
        document.getElementById(
            'logoutAllModal'
        );


    if (!modal) {
        return;
    }


    modal.classList.add(
        'show'
    );

    modal.setAttribute(
        'aria-hidden',
        'false'
    );

    document.body.classList.add(
        'modal-open'
    );

}


/* =====================================================
   CLOSE LOGOUT MODAL
===================================================== */

function closeLogoutModal() {

    const modal =
        document.getElementById(
            'logoutAllModal'
        );


    if (!modal) {
        return;
    }


    modal.classList.remove(
        'show'
    );

    modal.setAttribute(
        'aria-hidden',
        'true'
    );

    document.body.classList.remove(
        'modal-open'
    );


    logoutAllForm =
        null;

}


/* =====================================================
   CONFIRM LOGOUT
===================================================== */

function confirmLogoutAll() {

    if (!logoutAllForm) {

        closeLogoutModal();

        return;

    }


    const form =
        logoutAllForm;


    logoutAllForm =
        null;

    closeLogoutModal();

    submitForm(form);

}


/* =====================================================
   OPEN DELETE MODAL
===================================================== */

function openDeleteModal() {

    const modal =
        document.getElementById(
            'deleteModal'
        );


    if (!modal) {
        return;
    }


    modal.classList.add(
        'show'
    );

    modal.setAttribute(
        'aria-hidden',
        'false'
    );

    document.body.classList.add(
        'modal-open'
    );


    const input =
        document.getElementById(
            'delete_confirmation'
        );


    if (input) {

        input.value = '';

        setTimeout(
            function () {

                input.focus();

            },
            100
        );

    }

}


/* =====================================================
   CLOSE DELETE MODAL
===================================================== */

function closeDeleteModal() {

    const modal =
        document.getElementById(
            'deleteModal'
        );


    if (!modal) {
        return;
    }


    modal.classList.remove(
        'show'
    );

    modal.setAttribute(
        'aria-hidden',
        'true'
    );

    document.body.classList.remove(
        'modal-open'
    );

}


/* =====================================================
   ESCAPE KEY
===================================================== */

document.addEventListener(
    'keydown',
    function (event) {

        if (event.key !== 'Escape') {
            return;
        }


        const statusModal =
            document.getElementById(
                'statusConfirmModal'
            );

        const approveModal =
            document.getElementById(
                'approveVerificationModal'
            );

        const rejectModal =
            document.getElementById(
                'rejectModal'
            );

        const logoutModal =
            document.getElementById(
                'logoutAllModal'
            );

        const deleteModal =
            document.getElementById(
                'deleteModal'
            );


        if (
            statusModal &&
            statusModal.classList.contains('show')
        ) {

            closeStatusModal();
            return;

        }


        if (
            approveModal &&
            approveModal.classList.contains('show')
        ) {

            closeApproveModal();
            return;

        }


        if (
            rejectModal &&
            rejectModal.classList.contains('show')
        ) {

            closeRejectModal();
            return;

        }


        if (
            logoutModal &&
            logoutModal.classList.contains('show')
        ) {

            closeLogoutModal();
            return;

        }


        if (
            deleteModal &&
            deleteModal.classList.contains('show')
        ) {

            closeDeleteModal();
            return;

        }

    }
);


/* =====================================================
   CLICK OUTSIDE MODAL
===================================================== */

document.addEventListener(
    'click',
    function (event) {

        if (
            !event.target.classList.contains(
                'manage-modal-overlay'
            )
        ) {
            return;
        }


        const modal =
            event.target.closest(
                '.manage-modal'
            );


        if (!modal) {
            return;
        }


        switch (modal.id) {

            case 'statusConfirmModal':
                closeStatusModal();
                break;

            case 'approveVerificationModal':
                closeApproveModal();
                break;

            case 'rejectModal':
                closeRejectModal();
                break;

            case 'logoutAllModal':
                closeLogoutModal();
                break;

            case 'deleteModal':
                closeDeleteModal();
                break;

        }

    }
);


/* =====================================================
   AUTO HIDE MESSAGE
===================================================== */

setTimeout(
    function () {

        const message =
            document.querySelector(
                '.manage-message'
            );


        if (!message) {
            return;
        }


        message.style.opacity =
            '0';


        setTimeout(
            function () {

                if (
                    message &&
                    message.parentNode
                ) {

                    message.remove();

                }

            },
            300
        );

    },
    5000
);