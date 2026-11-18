<?php

/* =====================================================
   ACCOUNT ALERT MESSAGES
===================================================== */

$error_message = $_GET['error'] ?? '';
$success_message = $_GET['success'] ?? '';

?>

<?php if (!empty($error_message)): ?>

    <div class="admin-alert admin-alert-error">

        <i class="fa-solid fa-circle-exclamation"></i>

        <span>
            <?= htmlspecialchars($error_message) ?>
        </span>

        <button
            type="button"
            class="alert-close"
            aria-label="Close"
        >
            <i class="fa-solid fa-xmark"></i>
        </button>

    </div>

<?php endif; ?>


<?php if (!empty($success_message)): ?>

    <div class="admin-alert admin-alert-success">

        <i class="fa-solid fa-circle-check"></i>

        <span>
            <?= htmlspecialchars($success_message) ?>
        </span>

        <button
            type="button"
            class="alert-close"
            aria-label="Close"
        >
            <i class="fa-solid fa-xmark"></i>
        </button>

    </div>

<?php endif; ?>