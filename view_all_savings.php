<?php
require_once 'includes/auth_check.php';
// Accessible by Root (1), Chairman (2), Secretary (3), and Treasurer (4)
check_permissions([1, 2, 3, 4]);

require_once 'config/db_connect.php';
require_once 'templates/header.php';

try {
    $sql = "SELECT
                u.id,
                u.first_name,
                u.surname,
                u.username,
                u.account_no,
                u.avatar,
                (SELECT COALESCE(SUM(amount), 0) FROM savings WHERE user_id = u.id) as total_contributions,
                (SELECT COALESCE(SUM(amount), 0) FROM withdrawals WHERE user_id = u.id AND status = 'approved') as total_withdrawals
            FROM users u
            ORDER BY u.surname ASC, u.first_name ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $members = $stmt->fetchAll();
} catch (PDOException $e) {
    $members = [];
    $error = "Failed to load savings data: " . $e->getMessage();
}
?>

<div class="container mx-auto mt-10 p-4">
    <div class="bg-white dark:bg-gray-800 p-8 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
        <h1 class="text-3xl font-bold text-gray-800 dark:text-white mb-6">Member Savings Overview</h1>

        <?php if (isset($error)): ?>
            <div class="p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-lg dark:bg-red-200 dark:text-red-800" role="alert">
                <span class="font-medium">Error!</span> <?php echo $error; ?>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Member</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Account No.</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total Contributions</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total Withdrawals</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Net Savings</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php if (empty($members)): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-center">No members found.</td>
                            </tr>
                        <?php else:
                            $grand_total_contributions = 0;
                            $grand_total_withdrawals = 0;
                            $grand_total_net = 0;
                            foreach ($members as $member):
                                $net_savings = $member['total_contributions'] - $member['total_withdrawals'];
                                $grand_total_contributions += $member['total_contributions'];
                                $grand_total_withdrawals += $member['total_withdrawals'];
                                $grand_total_net += $net_savings;
                            ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <?php display_avatar($member['avatar'], $member['username'], $member['first_name'], $member['surname']); ?>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                    <?php echo htmlspecialchars(($member['first_name'] ?? '') . ' ' . ($member['surname'] ?? '')); ?>
                                                </div>
                                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                                    @<?php echo htmlspecialchars($member['username'] ?? ''); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        <?php echo htmlspecialchars($member['account_no'] ?? ''); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-green-600 font-semibold">
                                        <?php echo number_format($member['total_contributions'], 2); ?> UGX
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-red-600">
                                        <?php echo number_format($member['total_withdrawals'], 2); ?> UGX
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold text-indigo-600 dark:text-indigo-400">
                                        <?php echo number_format($net_savings, 2); ?> UGX
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <!-- Grand Total Row -->
                            <tr class="bg-gray-100 dark:bg-gray-700 font-bold border-t-2 border-gray-300 dark:border-gray-600">
                                <td colspan="2" class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white text-right uppercase tracking-wider">Total</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-green-700">
                                    <?php echo number_format($grand_total_contributions, 2); ?> UGX
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-red-700">
                                    <?php echo number_format($grand_total_withdrawals, 2); ?> UGX
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-indigo-700 dark:text-indigo-300">
                                    <?php echo number_format($grand_total_net, 2); ?> UGX
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
require_once 'templates/footer.php';
?>
