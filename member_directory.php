<?php
require_once 'includes/auth_check.php';
// Accessible by all logged in members
check_permissions([1, 2, 3, 4, 5]);

require_once 'config/db_connect.php';
require_once 'templates/header.php';

// Logic for sorting and searching
$sort_column = $_GET['sort'] ?? 'id';
$sort_order = $_GET['order'] ?? 'asc';
$search = $_GET['search'] ?? '';

$valid_columns = ['id', 'username', 'first_name', 'surname', 'email', 'phone', 'account_no', 'created_at'];
$sort_column = in_array($sort_column, $valid_columns) ? $sort_column : 'id';
$sort_order = strtolower($sort_order) === 'desc' ? 'DESC' : 'ASC';

try {
    $query = "SELECT id, username, first_name, surname, email, phone, account_no, avatar, created_at FROM users";
    $params = [];

    if (!empty($search)) {
        $query .= " WHERE first_name LIKE :search
                    OR surname LIKE :search
                    OR username LIKE :search
                    OR email LIKE :search
                    OR account_no LIKE :search
                    OR phone LIKE :search";
        $params['search'] = "%$search%";
    }

    $query .= " ORDER BY {$sort_column} {$sort_order}";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $members = $stmt->fetchAll();
} catch (PDOException $e) {
    $members = [];
    $error = "Failed to load member list.";
}

// Function to generate sorting links
function sort_link($column, $text, $current_column, $current_order, $current_search = '') {
    $order = ($current_column === $column && $current_order === 'ASC') ? 'desc' : 'asc';
    $arrow = ($current_column === $column) ? ($current_order === 'ASC' ? ' &uarr;' : ' &darr;') : '';
    $search_param = !empty($current_search) ? '&search=' . urlencode($current_search) : '';
    return "<a href=\"?sort={$column}&order={$order}{$search_param}\">{$text}{$arrow}</a>";
}
?>

<div class="container mx-auto mt-10 p-4">
    <div class="bg-white dark:bg-gray-800 p-8 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
        <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
            <h1 class="text-3xl font-bold text-gray-800 dark:text-white">Member Directory</h1>

            <form action="member_directory.php" method="GET" class="flex w-full md:w-auto">
                <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort_column); ?>">
                <input type="hidden" name="order" value="<?php echo htmlspecialchars($sort_order); ?>">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search members..."
                       class="rounded-l-lg border-gray-300 dark:bg-gray-700 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border py-2 px-4">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-r-lg">
                    Search
                </button>
                <?php if (!empty($search)): ?>
                    <a href="member_directory.php" class="ml-2 text-gray-500 hover:text-gray-700 flex items-center">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <?php if (isset($error)): ?>
            <div class="p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-lg dark:bg-red-200 dark:text-red-800" role="alert">
                <span class="font-medium">Error!</span> <?php echo $error; ?>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Avatar</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"><?php echo sort_link('id', 'ID', $sort_column, $sort_order, $search); ?></th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"><?php echo sort_link('first_name', 'First Name', $sort_column, $sort_order, $search); ?></th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"><?php echo sort_link('surname', 'Surname', $sort_column, $sort_order, $search); ?></th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"><?php echo sort_link('username', 'Username', $sort_column, $sort_order, $search); ?></th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"><?php echo sort_link('email', 'Email', $sort_column, $sort_order, $search); ?></th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"><?php echo sort_link('phone', 'Phone', $sort_column, $sort_order, $search); ?></th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"><?php echo sort_link('account_no', 'Account No.', $sort_column, $sort_order, $search); ?></th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"><?php echo sort_link('created_at', 'Joined On', $sort_column, $sort_order, $search); ?></th>
                            <?php if (in_array($_SESSION['role_id'], [1, 2])): ?>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php if (empty($members)): ?>
                            <tr>
                                <td colspan="<?php echo in_array($_SESSION['role_id'], [1, 2]) ? '10' : '9'; ?>" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-center">No members found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($members as $member): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                        <?php display_avatar($member['avatar'], $member['username'], $member['first_name'], $member['surname']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($member['id'] ?? ''); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($member['first_name'] ?? ''); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($member['surname'] ?? ''); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($member['username'] ?? ''); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($member['email'] ?? ''); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($member['phone'] ?? ''); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($member['account_no'] ?? ''); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"><?php echo date('M j, Y', strtotime($member['created_at'])); ?></td>
                                    <?php if (in_array($_SESSION['role_id'], [1, 2])): ?>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="edit_user.php?id=<?php echo $member['id']; ?>" class="text-indigo-600 hover:text-indigo-900">Edit</a>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
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
