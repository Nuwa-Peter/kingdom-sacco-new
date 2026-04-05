<?php
/**
 * db_update.php
 * Use this script to apply database updates safely without losing existing data.
 */

require_once 'config/db_connect.php';

// Start session if not already started to check permissions
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security check: only allow CLI or authenticated admin
if (php_sapi_name() !== 'cli' && (!isset($_SESSION['role_id']) || $_SESSION['role_id'] > 2)) {
    die("Access denied. This script can only be run from the command line or by an administrator.");
}

try {
    echo "Starting safe database update...\n";

    // 1. Remove default Root and Chairman accounts as requested
    $stmt = $pdo->prepare("DELETE FROM users WHERE username IN ('root', 'chairman')");
    $stmt->execute();
    $deletedCount = $stmt->rowCount();

    if ($deletedCount > 0) {
        echo "- Successfully removed $deletedCount default accounts (root/chairman).\n";
    } else {
        echo "- No default accounts (root/chairman) found to remove.\n";
    }

    // 2. Future updates can be added here (e.g., ALTER TABLE ...)
    // ...

    echo "Database update complete!\n";

} catch (PDOException $e) {
    die("Database update failed: " . $e->getMessage() . "\n");
}
?>
