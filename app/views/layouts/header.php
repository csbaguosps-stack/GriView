<?php
/* License: by cs.baguosps@gmail.com */
require_once __DIR__ . '/../../models/PlaceConfig.php';
$globalAppConfig = (new PlaceConfig())->getAll();
$appName = htmlspecialchars($globalAppConfig['app_name'] ?? APP_NAME);
$appTagline = htmlspecialchars($globalAppConfig['app_tagline'] ?? APP_SUBTITLE);
$faviconFile = ROOT_DIR . '/' . ($globalAppConfig['app_favicon'] ?? '');
$hasFavicon = !empty($globalAppConfig['app_favicon']) && file_exists($faviconFile);
$thumbFile = ROOT_DIR . '/' . ($globalAppConfig['app_thumbnail'] ?? '');
$hasThumbnail = !empty($globalAppConfig['app_thumbnail']) && file_exists($thumbFile);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $appName ?> - <?= $appTagline ?></title>
    <!-- Favicon -->
    <?php if ($hasFavicon): ?>
        <?php $favVer = filemtime($faviconFile); ?>
        <link rel="icon" type="image/png" href="<?= BASE_URL . '/' . $globalAppConfig['app_favicon'] . '?v=' . $favVer ?>">
        <link rel="shortcut icon" type="image/png" href="<?= BASE_URL . '/' . $globalAppConfig['app_favicon'] . '?v=' . $favVer ?>">
        <link rel="apple-touch-icon" href="<?= BASE_URL . '/' . $globalAppConfig['app_favicon'] . '?v=' . $favVer ?>">
    <?php else: ?>
        <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%234f46e5'><path d='M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z'/></svg>">
    <?php endif; ?>

    <!-- Open Graph Metadata -->
    <meta property="og:title" content="<?= $appName ?> - <?= $appTagline ?>">
    <meta property="og:description" content="<?= $appTagline ?>">
    <?php if ($hasThumbnail): ?>
        <meta property="og:image" content="<?= BASE_URL . '/' . $globalAppConfig['app_thumbnail'] . '?v=' . filemtime($thumbFile) ?>">
    <?php endif; ?>

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
    <!-- GriView Application Base Metadata -->
    <meta name="griview-app" content="<?= $appName ?>">
    <meta name="griview-base-url" content="<?= BASE_URL ?>">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
