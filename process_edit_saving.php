<?php
require_once 'includes/auth_check.php';
// Accessible by Root, Chairman, Secretary, and the member who owns the record
check_permissions([1, 2, 3, 5]);

require_once 'config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $saving_id = $_POST['saving_id'] ?? null;
    $old_amount = $_POST['old_amount'] ?? 0;
    $new_amount = $_POST['new_amount'] ?? 0;
    $reason = trim($_POST['reason'] ?? '');
    $admin_id = $_SESSION['user_id'];

    // Validation
    if (empty($saving_id) || empty($new_amount) || empty($reason)) {
        header("Location: edit_saving.php?id={$saving_id}&error=Missing required fields.");
        exit;
    }

    if (!is_numeric($new_amount) || $new_amount < 0) {
        header("Location: edit_saving.php?id={$saving_id}&error=Invalid amount.");
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Fetch the saving record to check permissions
        $stmt = $pdo->prepare("SELECT user_id, verified_by_user_id FROM savings WHERE id = ?");
        $stmt->execute([$saving_id]);
        $saving = $stmt->fetch();

        if (!$saving) {
            throw new Exception("Saving record not found.");
        }

        // Permission check: Non-admins can only edit their own UNVERIFIED savings
        if (!in_array($_SESSION['role_id'], [1, 2, 3])) {
            if ($saving['user_id'] != $_SESSION['user_id']) {
                throw new Exception("You do not have permission to edit this record.");
            }
            if ($saving['verified_by_user_id'] !== null) {
                throw new Exception("Verified savings cannot be edited by members. Please contact an administrator.");
            }
        }

        // 1. Update the saving record
        $stmt = $pdo->prepare("UPDATE savings SET amount = ? WHERE id = ?");
        $stmt->execute([$new_amount, $saving_id]);

        // 2. Log the change in the new savings_log table
        $stmt = $pdo->prepare(
            "INSERT INTO savings_log (saving_id, admin_id, old_amount, new_amount, reason) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$saving_id, $admin_id, $old_amount, $new_amount, $reason]);

        // 3. Log the generic action
        $log_action = "Rectified saving record #{$saving_id}. Changed amount from {$old_amount} to {$new_amount}. Reason: {$reason}";
        $stmt = $pdo->prepare("INSERT INTO logs (user_id, action) VALUES (?, ?)");
        $stmt->execute([$admin_id, $log_action]);

        $pdo->commit();

        $redirect = in_array($_SESSION['role_id'], [1, 2, 3, 4]) ? "view_member_savings.php?id={$saving['user_id']}" : "dashboard.php";
        header("Location: $redirect&success=Saving updated successfully.");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        // Redirect with a generic error; specific errors could be logged for the admin
        header("Location: edit_saving.php?id={$saving_id}&error=Database error occurred.");
        exit;
    }
} else {
    // Redirect if accessed directly
    header("Location: dashboard.php");
    exit;
}
?>
