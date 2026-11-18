<?php

/*
=========================================================
DYNAMIC BREADCRUMB
=========================================================

Usage:

$breadcrumbs = [
    ['label' => 'Users', 'url' => 'users'],
    ['label' => 'User Details']
];

include 'includes/breadcrumb.php';

The last item automatically becomes the current page
and is NOT clickable.

=========================================================
*/

if (!isset($breadcrumbs) || !is_array($breadcrumbs)) {
    $breadcrumbs = [];
}

?>

<nav class="admin-breadcrumb" aria-label="Breadcrumb">

    <!-- HOME -->

    <a
        href="dashboard"
        class="breadcrumb-item breadcrumb-home"
    >

        <i class="fa-solid fa-house"></i>

        <span>Home</span>

    </a>


    <?php foreach ($breadcrumbs as $index => $breadcrumb): ?>

        <?php

        $isLast = ($index === array_key_last($breadcrumbs));

        $label = htmlspecialchars(
            $breadcrumb['label'] ?? ''
        );

        $url = $breadcrumb['url'] ?? '';

        ?>


        <!-- SEPARATOR -->

        <span
            class="breadcrumb-separator"
            aria-hidden="true"
        >
            /
        </span>


        <?php if (!$isLast && !empty($url)): ?>

            <!-- CLICKABLE PREVIOUS PAGE -->

            <a
                href="<?= htmlspecialchars($url) ?>"
                class="breadcrumb-item breadcrumb-link"
            >
                <?= $label ?>
            </a>

        <?php else: ?>

            <!-- CURRENT PAGE -->

            <span
                class="breadcrumb-item breadcrumb-current"
            >
                <?= $label ?>
            </span>

        <?php endif; ?>


    <?php endforeach; ?>

</nav>