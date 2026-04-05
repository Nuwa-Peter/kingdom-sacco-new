<?php
require_once 'includes/auth_check.php';
// Accessible by Root (1), Chairman (2), Secretary (3), and Treasurer (4)
check_permissions([1, 2, 3, 4]);

require_once 'config/db_connect.php';

$user_id = $_GET['id'] ?? null;

if (!$user_id) {
    die("No member ID specified.");
}

try {
    // Fetch member details
    $user_stmt = $pdo->prepare("SELECT first_name, surname, account_no FROM users WHERE id = ?");
    $user_stmt->execute([$user_id]);
    $user = $user_stmt->fetch();

    if (!$user) {
        die("Member not found.");
    }

    // Fetch individual savings transactions
    $stmt = $pdo->prepare("SELECT amount, created_at FROM savings WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $savings = $stmt->fetchAll();

    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=savings_member_' . $user['account_no'] . '_' . date('Y-m-d') . '.csv');

    // Create a file pointer connected to the output stream
    $output = fopen('php://output', 'w');

    // Output member info
    fputcsv($output, ['Member', $user['first_name'] . ' ' . $user['surname']]);
    fputcsv($output, ['Account No', $user['account_no']]);
    fputcsv($output, []);

    // Output the column headings
    fputcsv($output, ['Date', 'Amount (UGX)']);

    // Loop over the rows
    $total = 0;
    foreach ($savings as $saving) {
        $total += $saving['amount'];
        fputcsv($output, [
            $saving['created_at'],
            $saving['amount']
        ]);
    }

    // Grand Total Row
    fputcsv($output, ['TOTAL SAVINGS', $total]);

    fclose($output);
    exit;

} catch (PDOException $e) {
    die("Error generating CSV: " . $e->getMessage());
}
?>
