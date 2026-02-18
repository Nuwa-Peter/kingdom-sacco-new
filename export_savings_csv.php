<?php
require_once 'includes/auth_check.php';
// Accessible by Root (1), Chairman (2), Secretary (3), and Treasurer (4)
check_permissions([1, 2, 3, 4]);

require_once 'config/db_connect.php';

try {
    $sql = "SELECT
                u.account_no,
                u.first_name,
                u.surname,
                u.username,
                (SELECT COALESCE(SUM(amount), 0) FROM savings WHERE user_id = u.id) as total_contributions,
                (SELECT COALESCE(SUM(amount), 0) FROM withdrawals WHERE user_id = u.id AND status = 'approved') as total_withdrawals
            FROM users u
            ORDER BY u.surname ASC, u.first_name ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $members = $stmt->fetchAll();

    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=savings_overview_' . date('Y-m-d') . '.csv');

    // Create a file pointer connected to the output stream
    $output = fopen('php://output', 'w');

    // Output the column headings
    fputcsv($output, ['Account No', 'First Name', 'Surname', 'Username', 'Total Contributions (UGX)', 'Total Withdrawals (UGX)', 'Net Savings (UGX)']);

    // Loop over the rows, outputting them
    $grand_total_net = 0;
    foreach ($members as $member) {
        $net_savings = $member['total_contributions'] - $member['total_withdrawals'];
        $grand_total_net += $net_savings;
        fputcsv($output, [
            $member['account_no'],
            $member['first_name'],
            $member['surname'],
            $member['username'],
            $member['total_contributions'],
            $member['total_withdrawals'],
            $net_savings
        ]);
    }

    // Grand Total Row
    fputcsv($output, ['', '', '', 'GRAND TOTAL', '', '', $grand_total_net]);

    fclose($output);
    exit;

} catch (PDOException $e) {
    die("Error generating CSV: " . $e->getMessage());
}
?>
