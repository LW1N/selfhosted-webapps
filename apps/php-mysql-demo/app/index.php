<?php
declare(strict_types=1);
$page_title = 'Home';
$current_page = 'home';
require __DIR__ . '/includes/header.php';
?>
<section class="hero">
    <h1>Pass & Play</h1>
    <p class="tagline">A Discord-like space for communities — clearer, faster, and more fun.</p>
    <div class="feature-list">
        <ul>
            <li><strong>Instant voice rooms</strong> — jump in and out without friction</li>
            <li><strong>Threaded chat done right</strong> — keep conversations clear without losing context</li>
            <li><strong>Role-based spaces</strong> — organize by interest or permission</li>
            <li><strong>Built-in event scheduling</strong> — no more juggling external calendars</li>
            <li><strong>Lightweight mobile-first UI</strong> — fast and accessible everywhere</li>
        </ul>
    </div>
    <div class="cta-buttons">
        <a href="/demo.php" class="btn btn-primary">Try the demo</a>
        <a href="/contacts" class="btn btn-secondary">Contact us</a>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
