<?php
// $current_page: 'home' | 'about' | 'products' | 'news' | 'contacts' | null (no highlight)
$current_page = $current_page ?? null;
$nav_items = [
    'home'     => ['label' => 'Home',     'url' => '/'],
    'about'    => ['label' => 'About',    'url' => '/about'],
    'products' => ['label' => 'Products', 'url' => '/products'],
    'news'     => ['label' => 'News',     'url' => '/news'],
    'contacts' => ['label' => 'Contacts', 'url' => '/contacts'],
];
?>
<nav class="site-nav" aria-label="Main navigation">
    <ul>
        <?php foreach ($nav_items as $key => $item): ?>
            <li>
                <a href="<?= htmlspecialchars($item['url']) ?>"
                   <?= ($current_page === $key) ? ' class="active" aria-current="page"' : '' ?>>
                    <?= htmlspecialchars($item['label']) ?>
                </a>
            </li>
        <?php endforeach; ?>
        <li><a href="/demo.php">Try the demo</a></li>
    </ul>
</nav>
