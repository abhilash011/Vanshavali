<?php

/**
 * Seed script — migrates data from legacy members.json into the new MySQL schema.
 * Usage: php migrations/seed.php
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

echo "🌱 Vanshavali Data Seeder\n";
echo "=========================\n\n";

// Path to the legacy data file
$legacyFile = dirname(__DIR__, 2) . '/members.json';

if (!file_exists($legacyFile)) {
    echo "⚠ No legacy members.json found at: {$legacyFile}\n";
    echo "  Skipping data migration.\n";
    exit(0);
}

try {
    $pdo = new PDO("mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    echo "✅ Connected to database '{$name}'\n";

    // Load legacy data
    $legacyMembers = json_decode(file_get_contents($legacyFile), true);
    if (!$legacyMembers) {
        echo "❌ Could not parse members.json\n";
        exit(1);
    }

    echo "📁 Found " . count($legacyMembers) . " members in legacy data\n\n";

    // Create a default community
    $pdo->exec("INSERT IGNORE INTO communities (id, name, slug, region, status)
                VALUES (1, 'Sonar Samaj', 'sonar-samaj', 'India', 'active')");
    echo "✅ Default community 'Sonar Samaj' ensured\n";

    // Create a default admin user
    $adminPassword = password_hash('admin123admin!', PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT IGNORE INTO users (id, name, email, password, role, community_id, status)
                           VALUES (1, 'Admin', 'admin@vanshavali.com', :password, 'platform_admin', 1, 'active')");
    $stmt->execute([':password' => $adminPassword]);
    echo "✅ Default admin user ensured (admin@vanshavali.com)\n\n";

    // Map old IDs to new IDs
    $idMap = [];

    // Insert members
    $insertMember = $pdo->prepare("
        INSERT INTO members (id, community_id, name, gender, birth_year, death_year,
                             relationship_label, profile_image, tags, order_id)
        VALUES (:id, 1, :name, :gender, :birth_year, :death_year,
                :rel_label, :profile_image, :tags, :order_id)
        ON DUPLICATE KEY UPDATE name = VALUES(name)
    ");

    foreach ($legacyMembers as $member) {
        $gender = strtolower($member['gender'] ?? 'male');
        if (!in_array($gender, ['male', 'female', 'other'])) {
            $gender = 'male';
        }

        $insertMember->execute([
            ':id'            => $member['id'],
            ':name'          => $member['name'],
            ':gender'        => $gender,
            ':birth_year'    => $member['bdate'] ?? null,
            ':death_year'    => $member['ddate'] ?? null,
            ':rel_label'     => $member['relationship'] ?? null,
            ':profile_image' => $member['img'] ?? null,
            ':tags'          => isset($member['tags']) ? json_encode($member['tags']) : null,
            ':order_id'      => $member['orderId'] ?? null,
        ]);

        $idMap[$member['id']] = $member['id'];
        echo "  ✅ Member: {$member['name']} (ID: {$member['id']})\n";
    }

    echo "\n📊 Migrated " . count($idMap) . " members\n\n";

    // Create relationships from legacy fid/mid/pids
    $insertRel = $pdo->prepare("
        INSERT IGNORE INTO relationships (member_id, related_member_id, relationship_type, verification_status)
        VALUES (:member_id, :related_id, :type, 'approved')
    ");

    $relCount = 0;
    foreach ($legacyMembers as $member) {
        $memberId = $member['id'];

        // Father (fid) → parent/child relationship
        if (!empty($member['fid']) && isset($idMap[$member['fid']])) {
            $insertRel->execute([':member_id' => $memberId, ':related_id' => $member['fid'], ':type' => 'child']);
            $insertRel->execute([':member_id' => $member['fid'], ':related_id' => $memberId, ':type' => 'parent']);
            $relCount += 2;
        }

        // Mother (mid) → parent/child relationship
        $mid = $member['mid'] ?? $member['Mid'] ?? null;
        if (!empty($mid) && isset($idMap[$mid])) {
            $insertRel->execute([':member_id' => $memberId, ':related_id' => $mid, ':type' => 'child']);
            $insertRel->execute([':member_id' => $mid, ':related_id' => $memberId, ':type' => 'parent']);
            $relCount += 2;
        }

        // Partners (pids) → spouse relationships
        if (!empty($member['pids']) && is_array($member['pids'])) {
            foreach ($member['pids'] as $partnerId) {
                if (isset($idMap[$partnerId])) {
                    $insertRel->execute([':member_id' => $memberId, ':related_id' => $partnerId, ':type' => 'spouse']);
                    $relCount++;
                }
            }
        }
    }

    echo "📊 Created {$relCount} relationships\n\n";
    echo "🎉 Data migration completed successfully!\n";
    echo "\n📝 Default admin credentials:\n";
    echo "   Email: admin@vanshavali.com\n";
    echo "   Password: admin123admin!\n";
    echo "   ⚠ CHANGE THIS IN PRODUCTION!\n";

} catch (PDOException $e) {
    echo "\n❌ Seeding failed: " . $e->getMessage() . "\n";
    exit(1);
}
