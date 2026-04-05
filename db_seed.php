<?php
require_once 'config/db_connect.php';

// --- Security Check: Ensure this script is run from the CLI ---
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.");
}

try {
    echo "Starting database seeding...\n";

    // Disable foreign key checks to allow truncation
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0;');
    echo "Foreign key checks disabled.\n";

    // List of tables to truncate
    $tables = [
        'users',
        'savings',
        'loans',
        'withdrawals',
        'loan_payments',
        'loan_guarantors',
        'notifications',
        'logs',
        'password_resets',
        'system_settings'
    ];

    // Truncate all tables
    foreach ($tables as $table) {
        $pdo->exec("TRUNCATE TABLE `{$table}`;");
        echo "Truncated table: {$table}\n";
    }

    // Re-enable foreign key checks
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1;');
    echo "Foreign key checks enabled.\n";

    // --- Seed Users Table ---
    // Default Root and Chairman accounts removed per user request.
    $users_to_seed = [];

    if (!empty($users_to_seed)) {
        $user_stmt = $pdo->prepare(
            "INSERT INTO `users` (`id`, `account_no`, `first_name`, `surname`, `username`, `email`, `phone`, `password`, `role_id`, `avatar`)
             VALUES (:id, :account_no, :first_name, :surname, :username, :email, :phone, :password, :role_id, :avatar)"
        );

        foreach ($users_to_seed as $user) {
            $user_stmt->execute($user);
        }
        echo "Seeded " . count($users_to_seed) . " users.\n";
    } else {
        echo "No users to seed.\n";
    }

    // --- Seed System Settings Table ---
    $settings_stmt = $pdo->prepare("INSERT INTO `system_settings` (`setting_key`, `setting_value`) VALUES (?, ?)");
    $settings_stmt->execute(['last_interest_run', null]);
    echo "Seeded system settings.\n";

    echo "--------------------------\n";
    echo "Database seeding complete!\n";

} catch (PDOException $e) {
    // Re-enable foreign key checks on error
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1;');
    die("Database seeding failed: " . $e->getMessage() . "\n");
}
?>
