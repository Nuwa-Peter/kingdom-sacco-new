<?php
require_once 'includes/auth_check.php';
// Accessible by Root (1), Chairman (2), Secretary (3), and Treasurer (4)
check_permissions([1, 2, 3, 4]);

require_once 'config/db_connect.php';
require_once 'templates/header.php';

$user_id = $_GET['id'] ?? null;

if (!$user_id) {
    echo "<div class='container mx-auto mt-10 p-4'><p class='text-red-500'>No member ID specified.</p></div>";
    require_once 'templates/footer.php';
    exit;
}

try {
    // Fetch member details
    $user_stmt = $pdo->prepare("SELECT first_name, surname, account_no FROM users WHERE id = ?");
    $user_stmt->execute([$user_id]);
    $user = $user_stmt->fetch();

    if (!$user) {
        echo "<div class='container mx-auto mt-10 p-4'><p class='text-red-500'>Member not found.</p></div>";
        require_once 'templates/footer.php';
        exit;
    }

    // Fetch individual savings transactions
    $search = $_GET['search'] ?? '';
    $query = "SELECT id, amount, created_at, proof_image_path FROM savings WHERE user_id = :user_id";
    $params = ['user_id' => $user_id];

    if (!empty($search)) {
        $query .= " AND amount LIKE :search";
        $params['search'] = "%$search%";
    }

    $query .= " ORDER BY created_at DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $savings = $stmt->fetchAll();

    // Fetch total savings
    $total_stmt = $pdo->prepare("SELECT SUM(amount) FROM savings WHERE user_id = ?");
    $total_stmt->execute([$user_id]);
    $total_savings = $total_stmt->fetchColumn() ?: 0;

} catch (PDOException $e) {
    $error = "Failed to load savings data: " . $e->getMessage();
}
?>

<div class="container mx-auto mt-10 p-4">
    <div class="bg-white dark:bg-gray-800 p-8 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
        <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
            <h1 class="text-3xl font-bold text-gray-800 dark:text-white">Savings for <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['surname']); ?></h1>

            <div class="flex items-center gap-4">
                <form action="view_member_savings.php" method="GET" class="flex">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($user_id); ?>">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search amount..."
                           class="rounded-l-lg border-gray-300 dark:bg-gray-700 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border py-2 px-4">
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-r-lg">
                        Search
                    </button>
                </form>
                <a href="export_member_savings_csv.php?id=<?php echo $user_id; ?>" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                      <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                    Export CSV
                </a>
                <a href="view_all_savings.php" class="text-indigo-600 hover:text-indigo-900 font-semibold">&larr; Back to Overview</a>
            </div>
        </div>

        <p class="text-gray-600 dark:text-gray-400 mb-4">Account No: <span class="font-semibold"><?php echo htmlspecialchars($user['account_no']); ?></span></p>

        <?php if (isset($error)): ?>
            <div class="p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-lg dark:bg-red-200 dark:text-red-800" role="alert">
                <span class="font-medium">Error!</span> <?php echo $error; ?>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Amount (UGX)</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Proof</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php if (empty($savings)): ?>
                            <tr>
                                <td colspan="4" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-center">No transactions found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($savings as $saving): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        <?php echo date('d M Y, H:i', strtotime($saving['created_at'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-semibold text-green-600">
                                        <?php echo number_format($saving['amount'], 2); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                        <?php if ($saving['proof_image_path']): ?>
                                            <a href="<?php echo htmlspecialchars($saving['proof_image_path']); ?>" target="_blank" class="text-indigo-600 hover:text-indigo-900">View Proof</a>
                                        <?php else: ?>
                                            <span class="text-gray-400 italic">No proof</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center font-medium space-x-3">
                                        <a href="edit_saving.php?id=<?php echo $saving['id']; ?>" class="text-indigo-600 hover:text-indigo-900">Rectify</a>
                                        <a href="delete_saving.php?id=<?php echo $saving['id']; ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure you want to delete this saving transaction?');">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="bg-gray-50 dark:bg-gray-700 font-bold">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white text-right uppercase tracking-wider">Total Savings</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-green-700">
                                    <?php echo number_format($total_savings, 2); ?> UGX
                                </td>
                                <td></td>
                                <td></td>
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
