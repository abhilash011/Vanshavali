<?php

/**
 * Migration runner — creates the vanshavali database and runs SQL migrations.
 * Usage: php migrations/migrate.php
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$host = $_ENV['DB_HOST'] ?? 'localhost';
$port = $_ENV['DB_PORT'] ?? '3306';
$name = $_ENV['DB_NAME'] ?? 'vanshavali';
$user = $_ENV['DB_USER'] ?? 'root';
$pass = $_ENV['DB_PASS'] ?? '';

echo "🔧 Vanshavali Database Migration\n";
echo "================================\n\n";

try {
    // First connect without database to create it
    $pdo = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    echo "✅ Connected to MySQL server\n";

    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$name}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ Database '{$name}' ensured\n";

    // Switch to the database
    $pdo->exec("USE `{$name}`");

    // Run migration files in order
    $migrationsDir = __DIR__;
    $sqlFiles = glob($migrationsDir . '/*.sql');
    sort($sqlFiles);

    foreach ($sqlFiles as $file) {
        $filename = basename($file);
        echo "▶ Running: {$filename}... ";

        $sql = file_get_contents($file);
        $pdo->exec($sql);

        echo "✅ Done\n";
    }

    echo "\n🎉 All migrations completed successfully!\n";

} catch (PDOException $e) {
    echo "\n❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
