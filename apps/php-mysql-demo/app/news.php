<?php
declare(strict_types=1);
$page_title = 'News';
$current_page = 'news';
$news_file = __DIR__ . '/data/news.json';
$news = [];
if (is_readable($news_file)) {
    $raw = file_get_contents($news_file);
    if ($raw !== false) {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $news = $decoded;
            usort($news, function ($a, $b) {
                return strcmp($b['date'] ?? '', $a['date'] ?? '');
            });
        }
    }
}
require __DIR__ . '/includes/header.php';
?>
<h1>News</h1>
<p class="meta">Latest updates from Pass &amp; Play.</p>

<?php if (empty($news)): ?>
    <p>No news posts at the moment. Check back soon.</p>
<?php else: ?>
    <ul class="news-list">
        <?php foreach ($news as $item):
            $date = $item['date'] ?? '';
            $title = $item['title'] ?? 'Untitled';
            $summary = $item['summary'] ?? '';
            $link = $item['link'] ?? '';
            $displayDate = $date !== '' ? date('M j, Y', strtotime($date)) : '';
        ?>
            <li class="news-item">
                <span class="news-date"><?= htmlspecialchars($displayDate) ?></span>
                <?php if ($link !== ''): ?>
                    <h2 class="news-title"><a href="<?= htmlspecialchars($link) ?>"><?= htmlspecialchars($title) ?></a></h2>
                <?php else: ?>
                    <h2 class="news-title"><?= htmlspecialchars($title) ?></h2>
                <?php endif; ?>
                <p class="news-summary"><?= htmlspecialchars($summary) ?></p>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
