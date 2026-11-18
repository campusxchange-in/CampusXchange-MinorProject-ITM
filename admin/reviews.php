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
   CURRENT ADMIN
===================================================== */

$admin_id = $_SESSION['admin_id'] ?? 0;

if (!$admin_id) {
    header("Location: " . FRONTEND_URL . "/admin-login");
    exit;
}


/* =====================================================
   PAGE CONFIGURATION
===================================================== */

$page_title = 'Reviews | CampusXchange';
$page_heading = 'Reviews';
$page_description = 'Monitor and moderate product and seller reviews.';
$active_page = 'reviews';
$page_css = 'reviews.css';


/* =====================================================
   BREADCRUMBS
===================================================== */

$breadcrumbs = [
    ['label' => 'Reviews']
];


/* =====================================================
   STATISTICS
===================================================== */

$stats = [
    'total'   => 0,
    'visible' => 0,
    'hidden'  => 0,
    'removed' => 0
];


/*
 * Product reviews + seller reviews are counted separately
 * and then combined.
 */

$sql = "
    SELECT
        COUNT(*) AS total,
        COALESCE(SUM(status = 'Visible'), 0) AS visible,
        COALESCE(SUM(status = 'Hidden'), 0) AS hidden,
        COALESCE(SUM(status = 'Removed'), 0) AS removed
    FROM (
        SELECT status
        FROM Product_Reviews
        WHERE deleted_at IS NULL

        UNION ALL

        SELECT status
        FROM Seller_Reviews
        WHERE deleted_at IS NULL
    ) AS all_reviews
";

$result = $conn->query($sql);

if ($result) {

    $row = $result->fetch_assoc();

    $stats['total']   = (int) ($row['total'] ?? 0);
    $stats['visible'] = (int) ($row['visible'] ?? 0);
    $stats['hidden']  = (int) ($row['hidden'] ?? 0);
    $stats['removed'] = (int) ($row['removed'] ?? 0);

}


/* =====================================================
   REVIEW DATA
===================================================== */

$reviews = [];


/*
 * We normalize both review tables into the same structure.
 *
 * review_id
 * review_type
 * reviewer_id
 * reviewer_name
 * reviewer_email
 * target_id
 * target_name
 * rating
 * review_text
 * status
 * transaction_id
 * transaction_status
 * created_at
 */

$sql = "

    SELECT *
    FROM (

        /* =============================================
           PRODUCT REVIEWS
        ============================================= */

        SELECT

            pr.product_review_id AS review_id,

            'Product' AS review_type,

            pr.reviewer_id,

            reviewer.full_name AS reviewer_name,

            reviewer.email AS reviewer_email,

            pr.product_id AS target_id,

            p.title AS target_name,

            p.seller_id AS target_owner_id,

            seller.full_name AS target_owner_name,

            pr.rating,

            pr.review_text,

            pr.status,

            pr.transaction_id,

            t.transaction_status,

            t.selling_price,

            pr.created_at

        FROM Product_Reviews pr

        INNER JOIN Users reviewer
            ON reviewer.user_id = pr.reviewer_id

        INNER JOIN Products p
            ON p.product_id = pr.product_id

        INNER JOIN Users seller
            ON seller.user_id = p.seller_id

        INNER JOIN Transactions t
            ON t.transaction_id = pr.transaction_id

        WHERE pr.deleted_at IS NULL


        UNION ALL


        /* =============================================
           SELLER REVIEWS
        ============================================= */

        SELECT

            sr.seller_review_id AS review_id,

            'Seller' AS review_type,

            sr.reviewer_id,

            reviewer.full_name AS reviewer_name,

            reviewer.email AS reviewer_email,

            sr.seller_id AS target_id,

            seller.full_name AS target_name,

            sr.seller_id AS target_owner_id,

            seller.full_name AS target_owner_name,

            sr.rating,

            sr.review_text,

            sr.status,

            sr.transaction_id,

            t.transaction_status,

            t.selling_price,

            sr.created_at

        FROM Seller_Reviews sr

        INNER JOIN Users reviewer
            ON reviewer.user_id = sr.reviewer_id

        INNER JOIN Users seller
            ON seller.user_id = sr.seller_id

        INNER JOIN Transactions t
            ON t.transaction_id = sr.transaction_id

        WHERE sr.deleted_at IS NULL

    ) AS all_reviews

    ORDER BY created_at DESC

";


$result = $conn->query($sql);

if ($result) {

    while ($row = $result->fetch_assoc()) {
        $reviews[] = $row;
    }

}


/* =====================================================
   HELPER FUNCTIONS
===================================================== */

function reviewStatusClass(string $status): string
{
    return match ($status) {

        'Visible' => 'visible',

        'Hidden' => 'hidden',

        'Removed' => 'removed',

        default => ''

    };
}


function reviewTypeClass(string $type): string
{
    return strtolower($type);
}


function renderStars(int $rating): string
{
    $html = '<span class="review-stars">';

    for ($i = 1; $i <= 5; $i++) {

        if ($i <= $rating) {

            $html .= '<i class="fa-solid fa-star"></i>';

        } else {

            $html .= '<i class="fa-regular fa-star"></i>';

        }

    }

    $html .= '</span>';

    return $html;
}


function formatReviewDate(string $date): string
{
    return date('d M Y, h:i A', strtotime($date));
}


/* =====================================================
   HEADER
===================================================== */

include 'includes/admin_header.php';

?>

<div class="reviews-page">


<?php
/* =====================================================
   ACTION MESSAGE
===================================================== */

$action_status = $_GET['status'] ?? '';
$action_message = $_GET['message'] ?? '';

$allowed_message_statuses = ['success', 'error'];

if (!in_array($action_status, $allowed_message_statuses, true)) {
    $action_status = '';
}

if ($action_message !== '') {
    $action_message = htmlspecialchars(
        $action_message,
        ENT_QUOTES,
        'UTF-8'
    );
}
?>

<?php if ($action_status !== '' && $action_message !== ''): ?>

    <div
        class="review-alert review-alert-<?= $action_status ?>"
        id="reviewActionAlert"
    >

        <div class="review-alert-icon">

            <?php if ($action_status === 'success'): ?>

                <i class="fa-solid fa-circle-check"></i>

            <?php else: ?>

                <i class="fa-solid fa-circle-exclamation"></i>

            <?php endif; ?>

        </div>

        <div class="review-alert-content">

            <strong>
                <?= $action_status === 'success'
                    ? 'Success'
                    : 'Action Failed'
                ?>
            </strong>

            <span>
                <?= $action_message ?>
            </span>

        </div>

        <button
            type="button"
            class="review-alert-close"
            id="reviewAlertClose"
            aria-label="Close"
        >
            <i class="fa-solid fa-xmark"></i>
        </button>

    </div>

<?php endif; ?>

    <!-- =================================================
         STATISTICS
    ================================================== -->

    <div class="reviews-stats">


        <div class="review-stat-card">

            <div class="review-stat-icon total">
                <i class="fa-solid fa-star"></i>
            </div>

            <div>

                <span class="review-stat-label">
                    Total Reviews
                </span>

                <strong>
                    <?= number_format($stats['total']) ?>
                </strong>

            </div>

        </div>


        <div class="review-stat-card">

            <div class="review-stat-icon visible">
                <i class="fa-solid fa-eye"></i>
            </div>

            <div>

                <span class="review-stat-label">
                    Visible
                </span>

                <strong>
                    <?= number_format($stats['visible']) ?>
                </strong>

            </div>

        </div>


        <div class="review-stat-card">

            <div class="review-stat-icon hidden">
                <i class="fa-solid fa-eye-slash"></i>
            </div>

            <div>

                <span class="review-stat-label">
                    Hidden
                </span>

                <strong>
                    <?= number_format($stats['hidden']) ?>
                </strong>

            </div>

        </div>


        <div class="review-stat-card">

            <div class="review-stat-icon removed">
                <i class="fa-solid fa-trash"></i>
            </div>

            <div>

                <span class="review-stat-label">
                    Removed
                </span>

                <strong>
                    <?= number_format($stats['removed']) ?>
                </strong>

            </div>

        </div>


    </div>


    <!-- =================================================
         FILTERS
    ================================================== -->

    <div class="reviews-filter-card">

        <div class="reviews-filter-header">

            <div>

                <h2>Review Filters</h2>

                <span id="reviewResultCount">
                    <?= count($reviews) ?> reviews
                </span>

            </div>

            <button
                type="button"
                class="btn btn-secondary"
                id="resetReviewFilters"
            >
                <i class="fa-solid fa-rotate-left"></i>
                Reset
            </button>

        </div>


        <div class="reviews-filters">


            <!-- SEARCH -->

            <div class="filter-group">

                <label for="reviewSearch">
                    Search
                </label>

                <div class="search-input-wrapper">

                    <i class="fa-solid fa-magnifying-glass"></i>

                    <input
                        type="text"
                        id="reviewSearch"
                        placeholder="Reviewer, product, seller or review ID..."
                    >

                </div>

            </div>


            <!-- TYPE -->

            <div class="filter-group">

                <label for="reviewTypeFilter">
                    Review Type
                </label>

                <select id="reviewTypeFilter">

                    <option value="all">
                        All Types
                    </option>

                    <option value="product">
                        Product Reviews
                    </option>

                    <option value="seller">
                        Seller Reviews
                    </option>

                </select>

            </div>


            <!-- STATUS -->

            <div class="filter-group">

                <label for="reviewStatusFilter">
                    Status
                </label>

                <select id="reviewStatusFilter">

                    <option value="all">
                        All Statuses
                    </option>

                    <option value="Visible">
                        Visible
                    </option>

                    <option value="Hidden">
                        Hidden
                    </option>

                    <option value="Removed">
                        Removed
                    </option>

                </select>

            </div>


            <!-- RATING -->

            <div class="filter-group">

                <label for="reviewRatingFilter">
                    Rating
                </label>

                <select id="reviewRatingFilter">

                    <option value="all">
                        All Ratings
                    </option>

                    <option value="5">5 Stars</option>
                    <option value="4">4 Stars</option>
                    <option value="3">3 Stars</option>
                    <option value="2">2 Stars</option>
                    <option value="1">1 Star</option>

                </select>

            </div>


        </div>

    </div>


    <!-- =================================================
         REVIEWS TABLE
    ================================================== -->

    <div class="reviews-table-card">

        <?php if (!empty($reviews)): ?>

            <div class="table-responsive">

                <table class="reviews-table">

                    <thead>

                        <tr>

                            <th>Review</th>

                            <th>Type</th>

                            <th>Reviewer</th>

                            <th>Target</th>

                            <th>Rating</th>

                            <th>Status</th>

                            <th>Date</th>

                            <th>Action</th>

                        </tr>

                    </thead>


                    <tbody id="reviewsTableBody">

                    <?php foreach ($reviews as $review): ?>

                        <?php

                        $searchText = strtolower(
                            $review['review_id'] . ' ' .
                            $review['reviewer_name'] . ' ' .
                            $review['reviewer_email'] . ' ' .
                            $review['target_name'] . ' ' .
                            $review['target_owner_name'] . ' ' .
                            ($review['review_text'] ?? '')
                        );

                        ?>

                        <tr

                            data-search="<?= htmlspecialchars($searchText) ?>"

                            data-type="<?= htmlspecialchars(
                                strtolower($review['review_type'])
                            ) ?>"

                            data-status="<?= htmlspecialchars(
                                $review['status']
                            ) ?>"

                            data-rating="<?= (int) $review['rating'] ?>"
                        >


                            <!-- REVIEW -->

                            <td>

                                <div class="review-content">

                                    <span class="review-id">
                                        #<?= (int) $review['review_id'] ?>
                                    </span>

                                    <p>
                                        <?=
                                        htmlspecialchars(
                                            $review['review_text']
                                            ?: 'No written review.'
                                        )
                                        ?>
                                    </p>

                                </div>

                            </td>


                            <!-- TYPE -->

                            <td>

                                <span
                                    class="review-type-badge <?= reviewTypeClass(
                                        $review['review_type']
                                    ) ?>"
                                >

                                    <?php if ($review['review_type'] === 'Product'): ?>

                                        <i class="fa-solid fa-box"></i>

                                    <?php else: ?>

                                        <i class="fa-solid fa-user"></i>

                                    <?php endif; ?>

                                    <?= htmlspecialchars(
                                        $review['review_type']
                                    ) ?>

                                </span>

                            </td>


                            <!-- REVIEWER -->

                            <td>

                                <div class="reviewer-info">

                                    <strong>
                                        <?= htmlspecialchars(
                                            $review['reviewer_name']
                                        ) ?>
                                    </strong>

                                    <span>
                                        <?= htmlspecialchars(
                                            $review['reviewer_email']
                                        ) ?>
                                    </span>

                                </div>

                            </td>


                            <!-- TARGET -->

                            <td>

                                <div class="review-target">

                                    <?php if ($review['review_type'] === 'Product'): ?>

                                        <strong>
                                            <?= htmlspecialchars(
                                                $review['target_name']
                                            ) ?>
                                        </strong>

                                        <span>
                                            Seller:
                                            <?= htmlspecialchars(
                                                $review['target_owner_name']
                                            ) ?>
                                        </span>

                                    <?php else: ?>

                                        <strong>
                                            <?= htmlspecialchars(
                                                $review['target_name']
                                            ) ?>
                                        </strong>

                                        <span>
                                            Seller Review
                                        </span>

                                    <?php endif; ?>

                                </div>

                            </td>


                            <!-- RATING -->

                            <td>

                                <div class="rating-display">

                                    <?= renderStars(
                                        (int) $review['rating']
                                    ) ?>

                                    <span>
                                        <?= (int) $review['rating'] ?>/5
                                    </span>

                                </div>

                            </td>


                            <!-- STATUS -->

                            <td>

                                <span
                                    class="review-status-badge <?= reviewStatusClass(
                                        $review['status']
                                    ) ?>"
                                >

                                    <?php if ($review['status'] === 'Visible'): ?>

                                        <i class="fa-solid fa-eye"></i>

                                    <?php elseif ($review['status'] === 'Hidden'): ?>

                                        <i class="fa-solid fa-eye-slash"></i>

                                    <?php else: ?>

                                        <i class="fa-solid fa-trash"></i>

                                    <?php endif; ?>

                                    <?= htmlspecialchars(
                                        $review['status']
                                    ) ?>

                                </span>

                            </td>


                            <!-- DATE -->

                            <td>

                                <span class="review-date">

                                    <?= formatReviewDate(
                                        $review['created_at']
                                    ) ?>

                                </span>

                            </td>


                            <!-- ACTION -->

                            <td>

                                <div class="review-actions">

                                    <?php if ($review['status'] === 'Visible'): ?>

                                        <form
                                            action="review-action.php"
                                            method="POST"
                                            class="review-action-form"
                                        >

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="hide"
                                            >

                                            <input
                                                type="hidden"
                                                name="review_id"
                                                value="<?= (int) $review['review_id'] ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="review_type"
                                                value="<?= htmlspecialchars(
                                                    $review['review_type']
                                                ) ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="review-action-button hide"
                                                title="Hide Review"
                                            >
                                                <i class="fa-solid fa-eye-slash"></i>
                                            </button>

                                        </form>


                                        <form
                                            action="review-action.php"
                                            method="POST"
                                            class="review-action-form"
                                        >

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="remove"
                                            >

                                            <input
                                                type="hidden"
                                                name="review_id"
                                                value="<?= (int) $review['review_id'] ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="review_type"
                                                value="<?= htmlspecialchars(
                                                    $review['review_type']
                                                ) ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="review-action-button remove"
                                                title="Remove Review"
                                            >
                                                <i class="fa-solid fa-trash"></i>
                                            </button>

                                        </form>


                                    <?php elseif ($review['status'] === 'Hidden'): ?>

                                        <form
                                            action="review-action.php"
                                            method="POST"
                                            class="review-action-form"
                                        >

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="restore"
                                            >

                                            <input
                                                type="hidden"
                                                name="review_id"
                                                value="<?= (int) $review['review_id'] ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="review_type"
                                                value="<?= htmlspecialchars(
                                                    $review['review_type']
                                                ) ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="review-action-button restore"
                                                title="Restore Review"
                                            >
                                                <i class="fa-solid fa-eye"></i>
                                            </button>

                                        </form>


                                        <form
                                            action="review-action.php"
                                            method="POST"
                                            class="review-action-form"
                                        >

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="remove"
                                            >

                                            <input
                                                type="hidden"
                                                name="review_id"
                                                value="<?= (int) $review['review_id'] ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="review_type"
                                                value="<?= htmlspecialchars(
                                                    $review['review_type']
                                                ) ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="review-action-button remove"
                                                title="Remove Review"
                                            >
                                                <i class="fa-solid fa-trash"></i>
                                            </button>

                                        </form>


                                    <?php else: ?>

                                        <span class="review-removed-label">
                                            No actions
                                        </span>

                                    <?php endif; ?>

                                </div>

                            </td>


                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>


            <!-- EMPTY FILTER STATE -->

            <div
                class="reviews-filter-empty"
                id="reviewsFilterEmpty"
                style="display: none;"
            >

                <i class="fa-solid fa-filter-circle-xmark"></i>

                <h3>
                    No reviews found
                </h3>

                <p>
                    No reviews match the selected filters.
                </p>

            </div>


        <?php else: ?>

            <div class="reviews-empty-state">

                <div class="reviews-empty-icon">

                    <i class="fa-regular fa-star"></i>

                </div>

                <h3>
                    No Reviews Found
                </h3>

                <p>
                    There are currently no reviews available for moderation.
                </p>

            </div>

        <?php endif; ?>

    </div>

</div>


<!-- =================================================
     REVIEW ACTION CONFIRMATION MODAL
================================================== -->

<div
    class="review-modal-overlay"
    id="reviewActionModal"
    aria-hidden="true"
>

    <div
        class="review-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="reviewModalTitle"
    >

        <button
            type="button"
            class="review-modal-close"
            id="reviewModalClose"
            aria-label="Close"
        >
            <i class="fa-solid fa-xmark"></i>
        </button>


        <div
            class="review-modal-icon"
            id="reviewModalIcon"
        >
            <i class="fa-solid fa-circle-question"></i>
        </div>


        <div class="review-modal-content">

            <h3 id="reviewModalTitle">
                Confirm Action
            </h3>

            <p id="reviewModalMessage">
                Are you sure you want to continue?
            </p>

        </div>


        <div class="review-modal-actions">

            <button
                type="button"
                class="btn btn-secondary"
                id="reviewModalCancel"
            >
                <i class="fa-solid fa-xmark"></i>
                Cancel
            </button>

            <button
                type="button"
                class="btn review-modal-confirm"
                id="reviewModalConfirm"
            >
                <i class="fa-solid fa-check"></i>
                Confirm
            </button>

        </div>

    </div>

</div>




<script>

document.addEventListener('DOMContentLoaded', function () {


    /* =====================================================
       REVIEW FILTERING
    ===================================================== */

    const searchInput = document.getElementById('reviewSearch');
    const typeFilter = document.getElementById('reviewTypeFilter');
    const statusFilter = document.getElementById('reviewStatusFilter');
    const ratingFilter = document.getElementById('reviewRatingFilter');
    const resetButton = document.getElementById('resetReviewFilters');

    const rows = document.querySelectorAll(
        '#reviewsTableBody tr'
    );

    const resultCount = document.getElementById(
        'reviewResultCount'
    );

    const emptyState = document.getElementById(
        'reviewsFilterEmpty'
    );


    function applyFilters() {

        const search = searchInput
            ? searchInput.value.toLowerCase().trim()
            : '';

        const type = typeFilter
            ? typeFilter.value
            : 'all';

        const status = statusFilter
            ? statusFilter.value
            : 'all';

        const rating = ratingFilter
            ? ratingFilter.value
            : 'all';


        let visibleCount = 0;


        rows.forEach(function (row) {

            const rowSearch = row.dataset.search || '';
            const rowType = row.dataset.type || '';
            const rowStatus = row.dataset.status || '';
            const rowRating = row.dataset.rating || '';


            const matchesSearch =
                !search ||
                rowSearch.includes(search);


            const matchesType =
                type === 'all' ||
                rowType === type;


            const matchesStatus =
                status === 'all' ||
                rowStatus === status;


            const matchesRating =
                rating === 'all' ||
                rowRating === rating;


            const matches =
                matchesSearch &&
                matchesType &&
                matchesStatus &&
                matchesRating;


            row.style.display = matches
                ? ''
                : 'none';


            if (matches) {
                visibleCount++;
            }

        });


        if (resultCount) {

            resultCount.textContent =
                visibleCount +
                (visibleCount === 1
                    ? ' review'
                    : ' reviews');

        }


        if (emptyState) {

            emptyState.style.display =
                visibleCount === 0
                    ? 'flex'
                    : 'none';

        }

    }


    if (searchInput) {
        searchInput.addEventListener(
            'input',
            applyFilters
        );
    }


    if (typeFilter) {
        typeFilter.addEventListener(
            'change',
            applyFilters
        );
    }


    if (statusFilter) {
        statusFilter.addEventListener(
            'change',
            applyFilters
        );
    }


    if (ratingFilter) {
        ratingFilter.addEventListener(
            'change',
            applyFilters
        );
    }


    if (resetButton) {

        resetButton.addEventListener(
            'click',
            function () {

                if (searchInput) {
                    searchInput.value = '';
                }

                if (typeFilter) {
                    typeFilter.value = 'all';
                }

                if (statusFilter) {
                    statusFilter.value = 'all';
                }

                if (ratingFilter) {
                    ratingFilter.value = 'all';
                }

                applyFilters();

            }
        );

    }


    /* =====================================================
       REVIEW ACTION MODAL
    ===================================================== */

    const modal = document.getElementById(
        'reviewActionModal'
    );

    const modalTitle = document.getElementById(
        'reviewModalTitle'
    );

    const modalMessage = document.getElementById(
        'reviewModalMessage'
    );

    const modalIcon = document.getElementById(
        'reviewModalIcon'
    );

    const modalConfirm = document.getElementById(
        'reviewModalConfirm'
    );

    const modalCancel = document.getElementById(
        'reviewModalCancel'
    );

    const modalClose = document.getElementById(
        'reviewModalClose'
    );


    let activeForm = null;


    function openReviewModal(form) {

        const actionInput = form.querySelector(
            'input[name="action"]'
        );

        const action = actionInput
            ? actionInput.value
            : '';

        const reviewTypeInput = form.querySelector(
            'input[name="review_type"]'
        );

        const reviewIdInput = form.querySelector(
            'input[name="review_id"]'
        );

        const reviewType = reviewTypeInput
            ? reviewTypeInput.value
            : 'Review';

        const reviewId = reviewIdInput
            ? reviewIdInput.value
            : '';


        activeForm = form;


        /* ---------------------------------------------
           RESET MODAL CLASSES
        --------------------------------------------- */

        modalIcon.className = 'review-modal-icon';

        modalConfirm.className =
            'btn review-modal-confirm';


        /* ---------------------------------------------
           ACTION: HIDE
        --------------------------------------------- */

        if (action === 'hide') {

            modalTitle.textContent = 'Hide Review';

            modalMessage.textContent =
                'Are you sure you want to hide ' +
                reviewType.toLowerCase() +
                ' review #' +
                reviewId +
                '? The review will no longer be visible to users.';

            modalIcon.innerHTML =
                '<i class="fa-solid fa-eye-slash"></i>';

            modalIcon.classList.add('hide');

            modalConfirm.innerHTML =
                '<i class="fa-solid fa-eye-slash"></i>' +
                ' Hide Review';

            modalConfirm.classList.add('hide');

        }


        /* ---------------------------------------------
           ACTION: RESTORE
        --------------------------------------------- */

        else if (action === 'restore') {

            modalTitle.textContent = 'Restore Review';

            modalMessage.textContent =
                'Are you sure you want to restore ' +
                reviewType.toLowerCase() +
                ' review #' +
                reviewId +
                '? The review will become visible again.';

            modalIcon.innerHTML =
                '<i class="fa-solid fa-eye"></i>';

            modalIcon.classList.add('restore');

            modalConfirm.innerHTML =
                '<i class="fa-solid fa-eye"></i>' +
                ' Restore Review';

            modalConfirm.classList.add('restore');

        }


        /* ---------------------------------------------
           ACTION: REMOVE
        --------------------------------------------- */

        else if (action === 'remove') {

            modalTitle.textContent = 'Remove Review';

            modalMessage.textContent =
                'Are you sure you want to remove ' +
                reviewType.toLowerCase() +
                ' review #' +
                reviewId +
                '? This review will be permanently removed from moderation visibility.';

            modalIcon.innerHTML =
                '<i class="fa-solid fa-trash"></i>';

            modalIcon.classList.add('remove');

            modalConfirm.innerHTML =
                '<i class="fa-solid fa-trash"></i>' +
                ' Remove Review';

            modalConfirm.classList.add('remove');

        }


        modal.classList.add('active');

        modal.setAttribute(
            'aria-hidden',
            'false'
        );

        document.body.classList.add(
            'modal-open'
        );

    }


    function closeReviewModal() {

        modal.classList.remove('active');

        modal.setAttribute(
            'aria-hidden',
            'true'
        );

        document.body.classList.remove(
            'modal-open'
        );

        activeForm = null;

    }


    /* ---------------------------------------------
       FORM SUBMISSION INTERCEPTION
    --------------------------------------------- */

    const actionForms = document.querySelectorAll(
        '.review-action-form'
    );


    actionForms.forEach(function (form) {

        form.addEventListener(
            'submit',
            function (event) {

                event.preventDefault();

                openReviewModal(form);

            }
        );

    });


    /* ---------------------------------------------
       CONFIRM
    --------------------------------------------- */

    if (modalConfirm) {

        modalConfirm.addEventListener(
            'click',
            function () {

                if (!activeForm) {
                    return;
                }

                const formToSubmit = activeForm;

                closeReviewModal();

                formToSubmit.submit();

            }
        );

    }


    /* ---------------------------------------------
       CANCEL
    --------------------------------------------- */

    if (modalCancel) {

        modalCancel.addEventListener(
            'click',
            closeReviewModal
        );

    }


    /* ---------------------------------------------
       CLOSE BUTTON
    --------------------------------------------- */

    if (modalClose) {

        modalClose.addEventListener(
            'click',
            closeReviewModal
        );

    }


    /* ---------------------------------------------
       CLICK OUTSIDE MODAL
    --------------------------------------------- */

    if (modal) {

        modal.addEventListener(
            'click',
            function (event) {

                if (event.target === modal) {
                    closeReviewModal();
                }

            }
        );

    }


    /* ---------------------------------------------
       ESC KEY
    --------------------------------------------- */

    document.addEventListener(
        'keydown',
        function (event) {

            if (
                event.key === 'Escape' &&
                modal &&
                modal.classList.contains('active')
            ) {
                closeReviewModal();
            }

        }
    );


    /* =====================================================
       ALERT CLOSE
    ===================================================== */

    const alert = document.getElementById(
        'reviewActionAlert'
    );

    const alertClose = document.getElementById(
        'reviewAlertClose'
    );


    if (alertClose && alert) {

        alertClose.addEventListener(
            'click',
            function () {

                alert.remove();

            }
        );

    }


    /* ---------------------------------------------
       AUTO HIDE SUCCESS / ERROR ALERT
    --------------------------------------------- */

    if (alert) {

        setTimeout(function () {

            if (alert) {
                alert.classList.add('hide');
            }

        }, 5000);

    }

});

</script>


<?php include 'includes/admin_footer.php'; ?>