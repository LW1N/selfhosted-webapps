<?php
declare(strict_types=1);

$dbHost = getenv('DB_HOST') ?: 'mysql';
$dbName = getenv('DB_NAME') ?: 'demo';
$dbUser = getenv('DB_USER') ?: 'demo';
$dbPass = getenv('DB_PASS') ?: '';

$error   = '';
$success = '';

try {
    $pdo = new PDO(
        "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS messages (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            content    VARCHAR(500) NOT NULL,
            created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['content'])) {
        $stmt = $pdo->prepare("INSERT INTO messages (content) VALUES (:content)");
        $stmt->execute(['content' => substr(trim($_POST['content']), 0, 500)]);
        $success = 'Message saved!';
    }

    $messages = $pdo->query(
        "SELECT id, content, created_at FROM messages ORDER BY created_at DESC LIMIT 20"
    )->fetchAll();

} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
    $messages = [];
}

$page_title = 'Demo — Work in progress';
$current_page = null; // demo is not in main nav as "current"
require __DIR__ . '/includes/header.php';
?>
<h1>Messages demo <span class="meta">(WORK IN PROGRESS)</span></h1>
<p class="meta">PHP <?= PHP_VERSION ?> &middot; MySQL &middot; Hostname: <?= htmlspecialchars(gethostname()) ?></p>

<?php if ($error):   ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<?php if ($success): ?><p class="success"><?= htmlspecialchars($success) ?></p><?php endif; ?>

<form method="POST" action="">
    <input type="text" name="content" placeholder="Type a message…" required maxlength="500" autocomplete="off">
    <button type="submit" class="btn btn-primary">Send</button>
</form>

<?php if (empty($messages) && !$error): ?>
    <div class="contacts-empty">No messages yet. Add the first one!</div>
<?php else: ?>
    <div class="contacts-table-wrap">
        <table class="contacts-table">
            <thead><tr><th>#</th><th>Message</th><th>Time</th></tr></thead>
            <tbody>
            <?php foreach ($messages as $m): ?>
                <tr>
                    <td><?= (int)$m['id'] ?></td>
                    <td><?= htmlspecialchars($m['content']) ?></td>
                    <td><?= htmlspecialchars($m['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
