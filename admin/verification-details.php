<?php

/* =====================================================
   AUTHENTICATION
===================================================== */

include 'includes/auth_check.php';


/* =====================================================
   DATABASE CONNECTION
===================================================== */

include '../server/database/db_connect.php';


/* =====================================================
   PAGE CONFIGURATION
===================================================== */

$page_title = 'Verification Details | CampusXchange';
$page_heading = 'Verification Details';
$page_description = 'Review user identity verification details.';
$active_page = 'verification-requests';
$page_css = 'verification-details.css';


/* =====================================================
   CSRF TOKEN
===================================================== */

if (empty($_SESSION['csrf_token'])) {

    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


/* =====================================================
   GET VERIFICATION ID
===================================================== */

$verification_id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);


if (!$verification_id || $verification_id <= 0) {

    header('Location: verification-requests.php');
    exit;
}


/* =====================================================
   FETCH VERIFICATION DETAILS
===================================================== */

$sql = "
    SELECT
        uv.verification_id,
        uv.user_id,
        uv.id_type,
        uv.id_number,
        uv.college_id_front_image,
        uv.college_id_back_image,
        uv.verification_status,
        uv.verified_by,
        uv.verified_at,
        uv.rejection_reason,
        uv.created_at,
        uv.updated_at,
        uv.submitted_at,

        u.full_name,
        u.email,
        u.phone,
        u.role,
        u.status AS user_status,
        u.email_verified_at,
        u.created_at AS user_created_at,
        u.updated_at AS user_updated_at

    FROM User_Verification uv

    INNER JOIN Users u
        ON u.user_id = uv.user_id

    WHERE
        uv.verification_id = ?
        AND uv.deleted_at IS NULL
        AND u.deleted_at IS NULL

    LIMIT 1
";


$stmt = $conn->prepare($sql);


if (!$stmt) {

    die('Database error: ' . htmlspecialchars($conn->error));
}


$stmt->bind_param('i', $verification_id);

$stmt->execute();

$result = $stmt->get_result();


if (!$result || $result->num_rows === 0) {

    $stmt->close();

    header('Location: verification-requests.php');
    exit;
}


$verification = $result->fetch_assoc();

$stmt->close();


/* =====================================================
   STATUS INFORMATION
===================================================== */

$verification_status = $verification['verification_status'] ?? 'Not Submitted';


$status_class = match ($verification_status) {

    'Pending' => 'pending',

    'Approved' => 'approved',

    'Rejected' => 'rejected',

    default => 'not-submitted'
};


/* =====================================================
   BREADCRUMBS
===================================================== */

$breadcrumbs = [

    [
        'label' => 'Verification Requests',
        'url' => 'verification-requests.php'
    ],

    [
        'label' => 'Verification #' . $verification['verification_id']
    ]
];


/* =====================================================
   FLASH MESSAGES
===================================================== */

$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';

unset($_SESSION['success_message']);
unset($_SESSION['error_message']);


/* =====================================================
   IMAGE PATH
===================================================== */

/* =====================================================
   ID CARD IMAGE PATHS
===================================================== */

$college_id_front_image = trim(
    $verification['college_id_front_image'] ?? ''
);

$college_id_back_image = trim(
    $verification['college_id_back_image'] ?? ''
);


/* FRONT IMAGE URL */

$front_image_url = '';

if ($college_id_front_image !== '') {

    $front_image_url =
        '../uploads/verification/' .
        ltrim($college_id_front_image, '/\\');
}


/* BACK IMAGE URL */

$back_image_url = '';

if ($college_id_back_image !== '') {

    $back_image_url =
        '../uploads/verification/' .
        ltrim($college_id_back_image, '/\\');
}


/* =====================================================
   DISPLAY DATE
===================================================== */

$submitted_timestamp = $verification['submitted_at']
    ?: $verification['created_at'];


/* =====================================================
   HEADER
===================================================== */

include 'includes/admin_header.php';

?>


<div class="verification-details-page">


    <!-- =================================================
         FLASH MESSAGES
    ================================================== -->

    <?php if ($success_message !== ''): ?>

        <div class="alert alert-success">

            <i class="fa-solid fa-circle-check"></i>

            <span>
                <?= htmlspecialchars($success_message); ?>
            </span>

        </div>

    <?php endif; ?>


    <?php if ($error_message !== ''): ?>

        <div class="alert alert-danger">

            <i class="fa-solid fa-circle-exclamation"></i>

            <span>
                <?= htmlspecialchars($error_message); ?>
            </span>

        </div>

    <?php endif; ?>


    <!-- =================================================
         PAGE HEADER
    ================================================== -->

    <div class="page-header">

        <div class="page-header-left">

            <a
                href="verification-requests.php"
                class="back-link vari-back-btn"
            >
                <i class="fa-solid fa-arrow-left"></i>
                Back to Verification Requests
            </a>

        </div>


        <div class="page-header-status">

            <span class="status-badge <?= htmlspecialchars($status_class); ?>">

                <?php if ($verification_status === 'Pending'): ?>

                    <i class="fa-solid fa-clock"></i>

                <?php elseif ($verification_status === 'Approved'): ?>

                    <i class="fa-solid fa-circle-check"></i>

                <?php elseif ($verification_status === 'Rejected'): ?>

                    <i class="fa-solid fa-circle-xmark"></i>

                <?php else: ?>

                    <i class="fa-solid fa-minus-circle"></i>

                <?php endif; ?>

                <?= htmlspecialchars($verification_status); ?>

            </span>

        </div>

    </div>


    <!-- =================================================
         MAIN GRID
    ================================================== -->

    <div class="details-grid">


        <!-- =================================================
             LEFT COLUMN
        ================================================== -->

        <div class="details-main">


            <!-- =============================================
                 USER INFORMATION
            ============================================== -->

            <section class="details-card">

                <div class="card-header">

                    <div>

                        <h2>
                            <i class="fa-solid fa-user"></i>
                            User Information
                        </h2>

                        <p>
                            Account information associated with this request.
                        </p>

                    </div>

                </div>


                <div class="info-grid">

                    <div class="info-item">

                        <span class="info-label">
                            Full Name
                        </span>

                        <strong class="info-value">
                            <?= htmlspecialchars($verification['full_name']); ?>
                        </strong>

                    </div>


                    <div class="info-item">

                        <span class="info-label">
                            Role
                        </span>

                        <strong class="info-value">
                            <?= htmlspecialchars($verification['role'] ?: 'Not provided'); ?>
                        </strong>

                    </div>


                    <div class="info-item">

                        <span class="info-label">
                            Email
                        </span>

                        <strong class="info-value">
                            <?= htmlspecialchars($verification['email']); ?>
                        </strong>

                    </div>


                    <div class="info-item">

                        <span class="info-label">
                            Phone
                        </span>

                        <strong class="info-value">
                            <?= htmlspecialchars(
                                $verification['phone'] ?: 'Not provided'
                            ); ?>
                        </strong>

                    </div>


                    <div class="info-item">

                        <span class="info-label">
                            Enrollment / Employee ID
                        </span>

                        <strong class="info-value">
                            <?= htmlspecialchars(
                                $verification['id_number'] ?: 'Not provided'
                            ); ?>
                        </strong>

                    </div>


                    <div class="info-item">

                        <span class="info-label">
                            Account Status
                        </span>

                        <strong class="info-value">
                            <?= htmlspecialchars(
                                $verification['user_status'] ?: 'Unknown'
                            ); ?>
                        </strong>

                    </div>


                    <div class="info-item">

                        <span class="info-label">
                            Email Verification
                        </span>

                        <strong class="info-value">

                            <?php if (!empty($verification['email_verified_at'])): ?>

                                <span class="inline-status success">
                                    <i class="fa-solid fa-circle-check"></i>
                                    Verified
                                </span>

                            <?php else: ?>

                                <span class="inline-status muted">
                                    <i class="fa-solid fa-circle-xmark"></i>
                                    Not Verified
                                </span>

                            <?php endif; ?>

                        </strong>

                    </div>


                    <div class="info-item">

                        <span class="info-label">
                            Account Created
                        </span>

                        <strong class="info-value">

                            <?= !empty($verification['user_created_at'])
                                ? date(
                                    'd M Y, h:i A',
                                    strtotime($verification['user_created_at'])
                                )
                                : 'Not available';
                            ?>

                        </strong>

                    </div>

                </div>

            </section>


            <!-- =============================================
                 ID INFORMATION
            ============================================== -->

            <section class="details-card">

                <div class="card-header">

                    <div>

                        <h2>
                            <i class="fa-solid fa-id-card"></i>
                            Identity Information
                        </h2>

                        <p>
                            Details submitted by the user for verification.
                        </p>

                    </div>

                </div>


                <div class="info-grid">

                    <div class="info-item">

                        <span class="info-label">
                            ID Type
                        </span>

                        <strong class="info-value">
                            <?= htmlspecialchars(
                                $verification['id_type'] ?: 'Not provided'
                            ); ?>
                        </strong>

                    </div>


                    <div class="info-item">

                        <span class="info-label">
                            ID Number
                        </span>

                        <strong class="info-value">
                            <?= htmlspecialchars(
                                $verification['id_number'] ?: 'Not provided'
                            ); ?>
                        </strong>

                    </div>


                    <div class="info-item full-width">

                        <span class="info-label">
                            Submitted / Updated
                        </span>

                        <strong class="info-value">

                            <?= !empty($submitted_timestamp)
                                ? date(
                                    'd M Y, h:i A',
                                    strtotime($submitted_timestamp)
                                )
                                : 'Not available';
                            ?>

                        </strong>

                    </div>

                </div>

            </section>


            <!-- =============================================
                 DOCUMENT
            ============================================== -->

            <!-- =============================================
                ID CARD DOCUMENTS
            ============================================== -->

            <section class="details-card document-card">

                <div class="card-header">

                    <div>

                        <h2>
                            <i class="fa-solid fa-id-card"></i>
                            College ID Documents
                        </h2>

                        <p>
                            Review both sides of the uploaded identity document.
                        </p>

                    </div>

                </div>


                <div class="id-card-images">


                    <!-- FRONT SIDE -->

                    <div class="id-card-image-item">

                        <h3>
                            Front Side
                        </h3>

                        <?php if ($front_image_url !== ''): ?>

                            <div class="document-viewer">

                                <a
                                    href="<?= htmlspecialchars(
                                        $front_image_url,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="document-image-link"
                                >

                                    <img
                                        src="<?= htmlspecialchars(
                                            $front_image_url,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>"
                                        alt="Front side of uploaded college ID"
                                        class="document-image"
                                    >

                                    <div class="image-overlay">

                                        <i class="fa-solid fa-up-right-and-down-left-from-center"></i>

                                        <span>
                                            Open Full Size
                                        </span>

                                    </div>

                                </a>

                            </div>

                        <?php else: ?>

                            <div class="document-empty">

                                <div class="document-empty-icon">
                                    <i class="fa-solid fa-file-circle-xmark"></i>
                                </div>

                                <h3>
                                    Front image not uploaded
                                </h3>

                            </div>

                        <?php endif; ?>

                    </div>


                    <!-- BACK SIDE -->

                    <div class="id-card-image-item">

                        <h3>
                            Back Side
                        </h3>

                        <?php if ($back_image_url !== ''): ?>

                            <div class="document-viewer">

                                <a
                                    href="<?= htmlspecialchars(
                                        $back_image_url,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="document-image-link"
                                >

                                    <img
                                        src="<?= htmlspecialchars(
                                            $back_image_url,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>"
                                        alt="Back side of uploaded college ID"
                                        class="document-image"
                                    >

                                    <div class="image-overlay">

                                        <i class="fa-solid fa-up-right-and-down-left-from-center"></i>

                                        <span>
                                            Open Full Size
                                        </span>

                                    </div>

                                </a>

                            </div>

                        <?php else: ?>

                            <div class="document-empty">

                                <div class="document-empty-icon">
                                    <i class="fa-solid fa-file-circle-xmark"></i>
                                </div>

                                <h3>
                                    Back image not uploaded
                                </h3>

                            </div>

                        <?php endif; ?>

                    </div>


                </div>

            </section>


            <!-- =============================================
                 REJECTION INFORMATION
            ============================================== -->

            <?php if (
                $verification_status === 'Rejected' &&
                !empty($verification['rejection_reason'])
            ): ?>

                <section class="details-card rejection-card">

                    <div class="card-header">

                        <div>

                            <h2>
                                <i class="fa-solid fa-circle-exclamation"></i>
                                Rejection Information
                            </h2>

                        </div>

                    </div>


                    <div class="rejection-reason">

                        <span class="info-label">
                            Reason
                        </span>

                        <p>
                            <?= nl2br(
                                htmlspecialchars(
                                    $verification['rejection_reason']
                                )
                            ); ?>
                        </p>

                    </div>


                    <?php if (!empty($verification['verified_at'])): ?>

                        <div class="verification-date">

                            <span class="info-label">
                                Reviewed On
                            </span>

                            <strong>
                                <?= date(
                                    'd M Y, h:i A',
                                    strtotime($verification['verified_at'])
                                ); ?>
                            </strong>

                        </div>

                    <?php endif; ?>

                </section>

            <?php endif; ?>


        </div>


        <!-- =================================================
             RIGHT COLUMN
        ================================================== -->

        <aside class="details-sidebar">


            <!-- =============================================
                 REQUEST SUMMARY
            ============================================== -->

            <section class="details-card summary-card">

                <div class="card-header">

                    <div>

                        <h2>
                            Request Summary
                        </h2>

                    </div>

                </div>


                <div class="summary-list">

                    <div class="summary-item">

                        <span>
                            Verification ID
                        </span>

                        <strong>
                            #<?= (int) $verification['verification_id']; ?>
                        </strong>

                    </div>


                    <div class="summary-item">

                        <span>
                            User ID
                        </span>

                        <strong>
                            #<?= (int) $verification['user_id']; ?>
                        </strong>

                    </div>


                    <div class="summary-item">

                        <span>
                            Request Status
                        </span>

                        <strong class="summary-status <?= htmlspecialchars($status_class); ?>">
                            <?= htmlspecialchars($verification_status); ?>
                        </strong>

                    </div>


                    <div class="summary-item">

                        <span>
                            Submitted / Updated
                        </span>

                        <strong>
                            <?= !empty($submitted_timestamp)
                                ? date(
                                    'd M Y',
                                    strtotime($submitted_timestamp)
                                )
                                : 'N/A';
                            ?>
                        </strong>

                    </div>

                </div>

            </section>


            <!-- =============================================
                 ACTIONS
            ============================================== -->

            <?php if ($verification_status === 'Pending'): ?>

                <section class="details-card action-card">

                    <div class="card-header">

                        <div>

                            <h2>
                                Review Request
                            </h2>

                            <p>
                                Choose an action after reviewing the submitted
                                information and document.
                            </p>

                        </div>

                    </div>


                    <div class="verification-actions">

                        <button
                            type="button"
                            class="btn btn-approve"
                            id="approveVerificationBtn"
                        >
                            <i class="fa-solid fa-circle-check"></i>
                            Approve Verification
                        </button>


                        <button
                            type="button"
                            class="btn btn-reject"
                            id="rejectVerificationBtn"
                        >
                            <i class="fa-solid fa-circle-xmark"></i>
                            Reject Verification
                        </button>

                    </div>

                </section>


            <?php elseif ($verification_status === 'Approved'): ?>

                <section class="details-card status-message approved-message">

                    <div class="status-message-icon">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>

                    <h3>
                        Verification Approved
                    </h3>

                    <p>
                        This user's identity verification has already been
                        approved.
                    </p>

                    <?php if (!empty($verification['verified_at'])): ?>

                        <span>
                            <?= date(
                                'd M Y, h:i A',
                                strtotime($verification['verified_at'])
                            ); ?>
                        </span>

                    <?php endif; ?>

                </section>


            <?php elseif ($verification_status === 'Rejected'): ?>

                <section class="details-card status-message rejected-message">

                    <div class="status-message-icon">
                        <i class="fa-solid fa-circle-xmark"></i>
                    </div>

                    <h3>
                        Verification Rejected
                    </h3>

                    <p>
                        This request was rejected. The user can correct the
                        submitted information and resubmit their verification.
                    </p>

                </section>

            <?php endif; ?>


            <!-- =============================================
                 BACK
            ============================================== -->

            <a
                href="verification-requests.php"
                class="back-button"
            >
                <i class="fa-solid fa-arrow-left"></i>
                Back to All Requests
            </a>

        </aside>

    </div>

</div>


<!-- =====================================================
     APPROVE MODAL
====================================================== -->

<?php if ($verification_status === 'Pending'): ?>

<div
    class="verification-modal"
    id="approveModal"
    aria-hidden="true"
>

    <div class="modal-overlay"></div>


    <div
        class="modal-box"
        role="dialog"
        aria-modal="true"
        aria-labelledby="approveModalTitle"
    >

        <button
            type="button"
            class="modal-close"
            data-close-modal="approveModal"
            aria-label="Close"
        >
            <i class="fa-solid fa-xmark"></i>
        </button>


        <div class="modal-icon approve">
            <i class="fa-solid fa-circle-check"></i>
        </div>


        <h3 id="approveModalTitle">
            Approve Verification?
        </h3>


        <p>
            Are you sure you want to approve this verification request?
            If the user's account is currently Pending, it will become Active.
        </p>


        <form
            method="POST"
            action="verification-action.php"
            id="approveForm"
        >

            <input
                type="hidden"
                name="action"
                value="approve"
            >

            <input
                type="hidden"
                name="verification_id"
                value="<?= (int) $verification['verification_id']; ?>"
            >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars(
                    $_SESSION['csrf_token'],
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>"
            >


            <div class="modal-actions">

                <button
                    type="button"
                    class="btn btn-secondary"
                    data-close-modal="approveModal"
                >
                    Cancel
                </button>


                <button
                    type="submit"
                    class="btn btn-approve"
                    id="confirmApproveBtn"
                >
                    <i class="fa-solid fa-circle-check"></i>
                    Confirm Approval
                </button>

            </div>

        </form>

    </div>

</div>


<!-- =====================================================
     REJECT MODAL
====================================================== -->

<div
    class="verification-modal"
    id="rejectModal"
    aria-hidden="true"
>

    <div class="modal-overlay"></div>


    <div
        class="modal-box"
        role="dialog"
        aria-modal="true"
        aria-labelledby="rejectModalTitle"
    >

        <button
            type="button"
            class="modal-close"
            data-close-modal="rejectModal"
            aria-label="Close"
        >
            <i class="fa-solid fa-xmark"></i>
        </button>


        <div class="modal-icon reject">
            <i class="fa-solid fa-circle-xmark"></i>
        </div>


        <h3 id="rejectModalTitle">
            Reject Verification?
        </h3>


        <p>
            Provide a clear reason so the user knows what needs to be
            corrected before resubmitting.
        </p>


        <form
            method="POST"
            action="verification-action.php"
            id="rejectForm"
        >

            <input
                type="hidden"
                name="action"
                value="reject"
            >

            <input
                type="hidden"
                name="verification_id"
                value="<?= (int) $verification['verification_id']; ?>"
            >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars(
                    $_SESSION['csrf_token'],
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>"
            >


            <div class="form-group">

                <label for="rejection_reason">

                    Rejection Reason

                    <span>*</span>

                </label>


                <textarea
                    id="rejection_reason"
                    name="rejection_reason"
                    rows="5"
                    maxlength="255"
                    required
                    placeholder="Enter the reason for rejecting this verification request..."
                ></textarea>


                <div class="character-count">
                    Maximum 255 characters.
                </div>

            </div>


            <div class="modal-actions">

                <button
                    type="button"
                    class="btn btn-secondary"
                    data-close-modal="rejectModal"
                >
                    Cancel
                </button>


                <button
                    type="submit"
                    class="btn btn-reject"
                    id="confirmRejectBtn"
                >
                    <i class="fa-solid fa-circle-xmark"></i>
                    Confirm Rejection
                </button>

            </div>

        </form>

    </div>

</div>

<?php endif; ?>


<script>

document.addEventListener('DOMContentLoaded', function () {

    const approveButton =
        document.getElementById('approveVerificationBtn');

    const rejectButton =
        document.getElementById('rejectVerificationBtn');

    const approveModal =
        document.getElementById('approveModal');

    const rejectModal =
        document.getElementById('rejectModal');


    /* =================================================
       OPEN MODAL
    ================================================== */

    function openModal(modal) {

        if (!modal) {
            return;
        }

        modal.classList.add('show');

        modal.setAttribute('aria-hidden', 'false');

        document.body.classList.add('modal-open');

    }


    /* =================================================
       CLOSE MODAL
    ================================================== */

    function closeModal(modal) {

        if (!modal) {
            return;
        }

        modal.classList.remove('show');

        modal.setAttribute('aria-hidden', 'true');

        document.body.classList.remove('modal-open');

    }


    /* =================================================
       APPROVE
    ================================================== */

    if (approveButton) {

        approveButton.addEventListener('click', function (event) {

            event.preventDefault();

            openModal(approveModal);

        });

    }


    /* =================================================
       REJECT
    ================================================== */

    if (rejectButton) {

        rejectButton.addEventListener('click', function (event) {

            event.preventDefault();

            openModal(rejectModal);

        });

    }


    /* =================================================
       CLOSE BUTTONS
    ================================================== */

    document
        .querySelectorAll('[data-close-modal]')
        .forEach(function (button) {

            button.addEventListener('click', function () {

                const modalId =
                    this.getAttribute('data-close-modal');

                closeModal(
                    document.getElementById(modalId)
                );

            });

        });


    /* =================================================
       OVERLAY CLICK
    ================================================== */

    document
        .querySelectorAll('.modal-overlay')
        .forEach(function (overlay) {

            overlay.addEventListener('click', function () {

                const modal =
                    this.closest('.verification-modal');

                closeModal(modal);

            });

        });


    /* =================================================
       ESC KEY
    ================================================== */

    document.addEventListener('keydown', function (event) {

        if (event.key !== 'Escape') {
            return;
        }

        document
            .querySelectorAll('.verification-modal.show')
            .forEach(function (modal) {

                closeModal(modal);

            });

    });


    /* =================================================
       APPROVE FORM
    ================================================== */

    const approveForm =
        document.getElementById('approveForm');

    if (approveForm) {

        approveForm.addEventListener('submit', function () {

            const button =
                document.getElementById('confirmApproveBtn');

            if (button) {

                button.disabled = true;

                button.innerHTML =
                    '<i class="fa-solid fa-spinner fa-spin"></i> Approving...';

            }

        });

    }


    /* =================================================
       REJECT FORM
    ================================================== */

    const rejectForm =
        document.getElementById('rejectForm');

    if (rejectForm) {

        rejectForm.addEventListener('submit', function () {

            const button =
                document.getElementById('confirmRejectBtn');

            if (button) {

                button.disabled = true;

                button.innerHTML =
                    '<i class="fa-solid fa-spinner fa-spin"></i> Rejecting...';

            }

        });

    }

});

</script>


<?php

include 'includes/admin_footer.php';

?>