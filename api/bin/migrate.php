<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Database;

$pdo = Database::connection();

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS schema_migrations (
        migration VARCHAR(255) NOT NULL PRIMARY KEY,
        applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
);

/** @var string[] $applied */
$applied = $pdo->query('SELECT migration FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);

$files = glob(__DIR__ . '/../migrations/*.sql');
sort($files);

foreach ($files as $file) {
    $name = basename($file);

    if (in_array($name, $applied, true)) {
        echo "skip  $name\n";
        continue;
    }

    $sql = file_get_contents($file);
    if ($sql === false) {
        fwrite(STDERR, "failed to read $name\n");
        exit(1);
    }

    try {
        $pdo->exec($sql);
        $stmt = $pdo->prepare('INSERT INTO schema_migrations (migration) VALUES (?)');
        $stmt->execute([$name]);
        echo "apply $name\n";
    } catch (Throwable $e) {
        fwrite(STDERR, "failed $name: " . $e->getMessage() . "\n");
        exit(1);
    }
}

echo "done\n";
