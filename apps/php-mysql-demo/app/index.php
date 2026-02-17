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

    // Create table if it does not exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS messages (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            content    VARCHAR(500) NOT NULL,
            created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['content'])) {
        $stmt = $pdo->prepare("INSERT INTO messages (content) VALUES (:content)");
        $stmt->execute(['content' => substr(trim($_POST['content']), 0, 500)]);
        $success = 'Message saved!';
    }

    // Fetch latest 20 messages
    $messages = $pdo->query(
        "SELECT id, content, created_at FROM messages ORDER BY created_at DESC LIMIT 20"
    )->fetchAll();

} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
    $messages = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP + MySQL Demo</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            max-width: 640px; margin: 2rem auto; padding: 0 1rem;
            color: #1a1a1a; background: #f5f5f5;
        }
        h1 { margin-bottom: .25rem; }
        .meta { color: #666; font-size: .85rem; margin-bottom: 1.5rem; }
        form { display: flex; gap: .5rem; margin-bottom: 1.5rem; }
        input[type="text"] {
            flex: 1; padding: .5rem .75rem; border: 1px solid #ccc; border-radius: 4px; font-size: 1rem;
        }
        button {
            padding: .5rem 1.25rem; background: #0070f3; color: #fff;
            border: none; border-radius: 4px; font-size: 1rem; cursor: pointer;
        }
        button:hover { background: #005bb5; }
        .success { color: #16a34a; margin-bottom: 1rem; }
        .error   { color: #dc2626; margin-bottom: 1rem; }
        table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 6px; overflow: hidden; }
        th, td { text-align: left; padding: .6rem .75rem; border-bottom: 1px solid #eee; }
        th { background: #fafafa; font-weight: 600; }
        .empty { padding: 1.5rem; text-align: center; color: #999; }
    </style>
</head>
<body>
    <h1>Messages</h1>
    <p class="meta">PHP <?= PHP_VERSION ?> &middot; MySQL &middot; Hostname: <?= gethostname() ?></p>

    <?php if ($error):   ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
    <?php if ($success): ?><p class="success"><?= htmlspecialchars($success) ?></p><?php endif; ?>

    <form method="POST" action="">
        <input type="text" name="content" placeholder="Type a message…" required maxlength="500" autocomplete="off">
        <button type="submit">Send</button>
    </form>

    <?php if (empty($messages) && !$error): ?>
        <div class="empty">No messages yet. Add the first one!</div>
    <?php else: ?>
        <table>
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
    <?php endif; ?>
</body>
</html>
