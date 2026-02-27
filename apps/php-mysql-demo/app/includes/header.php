<?php
$page_title = $page_title ?? 'Pass & Play';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="dark">
    <title><?= htmlspecialchars($page_title) ?> — Pass & Play</title>
    <link rel="stylesheet" href="/css/site.css">
</head>
<body>
<a href="#main-content" class="skip-link">Skip to main content</a>
<header class="site-header">
    <div class="header-inner">
        <a href="/" class="logo">Pass & Play</a>
        <?php include __DIR__ . '/nav.php'; ?>
    </div>
</header>
<main id="main-content" class="site-main" tabindex="-1">
