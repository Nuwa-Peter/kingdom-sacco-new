<?php
// Securely start a session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$is_logged_in = isset($_SESSION['user_id']);
$role_id = $_SESSION['role_id'] ?? 0; // Default to 0 if not logged in
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KINGDOM SACCO Management System</title>
    <link rel="icon" href="assets/images/kingdomsacco_light.png" type="image/png">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/theme.css">
    <link rel="manifest" href="manifest.json">
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('sw.js')
                    .then(reg => console.log('Service Worker Registered'))
                    .catch(err => console.log('Service Worker Registration Failed', err));
            });
        }
    </script>
    <script src="assets/js/main.js" defer></script>
    <script src="assets/js/theme.js" defer></script>
    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous" defer></script>
</head>
<body class="bg-gray-100 block">
    <?php
    if ($is_logged_in) {
        // Include notification functions
        require_once 'includes/notifications.php';
        // Include new avatar functions
        require_once 'includes/avatar_functions.php';

        // Fetch user's avatar path for display
        // In a real app, you would have this from the initial login query
        require_once 'config/db_connect.php';
        try {
            $user_id = $_SESSION['user_id'];
            // Fetch user details for avatar and display
            $stmt = $pdo->prepare("SELECT username, first_name, surname, avatar FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user_details = $stmt->fetch(PDO::FETCH_ASSOC);

            // Fetch unread notification count
            $notify_stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
            $notify_stmt->execute([$user_id]);
            $unread_notifications_count = $notify_stmt->fetchColumn();

        } catch (PDOException $e) {
            $user_avatar = null; // Default on error
            $unread_notifications_count = 0;
        }
    }
    ?>
    <?php if ($is_logged_in): ?>

    <!-- Header & Top Navigation -->
    <header class="shadow-md w-full sticky top-0 z-50">
        <nav class="navbar navbar-expand-lg py-2 px-4" style="background-color: var(--bg-header);">
            <div class="container-fluid flex justify-between items-center">
                <a class="navbar-brand" href="dashboard.php">
                    <img id="logo" src="assets/images/kingdomsacco_dark.png" alt="KINGDOM SACCO Logo" class="h-8">
                </a>

                <div class="flex items-center space-x-2">
                    <!-- Notification Bell -->
                    <div class="relative">
                        <a href="notifications.php" class="relative">
                            <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                            <?php if ($unread_notifications_count > 0): ?>
                                <span class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-xs text-white"><?php echo $unread_notifications_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </div>

                    <!-- Install App Button -->
                    <button id="installApp" class="hidden text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-green-700 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:focus:ring-gray-700 rounded-lg text-sm p-2.5">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    </button>

                    <!-- Theme Toggle Button -->
                    <button id="theme-toggle" type="button" class="text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-green-700 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:focus:ring-gray-700 rounded-lg text-sm p-2.5">
                        <svg id="theme-toggle-dark-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path></svg>
                        <svg id="theme-toggle-light-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 5.05A1 1 0 016.465 3.636l.707.707a1 1 0 01-1.414 1.414l-.707-.707a1 1 0 010-1.414zM5 11a1 1 0 100-2H4a1 1 0 100 2h1zM8 16a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM3.636 6.465a1 1 0 011.414 0l.707.707a1 1 0 01-1.414 1.414l-.707-.707a1 1 0 010-1.414z"></path></svg>
                    </button>

                    <!-- User Avatar & Dropdown Toggle -->
                    <button class="navbar-toggler p-0 border-0 flex items-center" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
                         <?php
                         if ($user_details) {
                             display_avatar($user_details['avatar'], $user_details['username'], $user_details['first_name'], $user_details['surname']);
                         }
                         ?>
                    </button>
                </div>
            </div>

            <!-- Collapsible Menu -->
            <div class="collapse navbar-collapse mt-3 lg:mt-0" id="mainNavbar">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 space-y-1 lg:space-y-0 lg:flex lg:items-center lg:ms-4">
                    <li class="nav-item"><a href="dashboard.php" class="nav-link py-2 px-3 rounded hover:bg-green-300">Dashboard</a></li>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle py-2 px-3 rounded hover:bg-green-300" href="#" id="memberActionsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Member Actions
                        </a>
                        <ul class="dropdown-menu border-0 shadow" aria-labelledby="memberActionsDropdown">
                            <li><a href="withdraw.php" class="dropdown-item">Request Withdrawal</a></li>
                            <li><a href="request_loan.php" class="dropdown-item">Request Loan</a></li>
                            <li><a href="repay_loan.php" class="dropdown-item">Repay Loan</a></li>
                            <li><a href="guarantor_requests.php" class="dropdown-item">Guarantor Requests</a></li>
                            <li><a href="generate_statement.php" class="dropdown-item">Download Statement</a></li>
                            <li><a href="member_directory.php" class="dropdown-item">Member Directory</a></li>
                        </ul>
                    </li>

                    <?php if (in_array($role_id, [1, 2, 3, 4])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle py-2 px-3 rounded hover:bg-green-300" href="#" id="adminControlsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Admin Controls
                            </a>
                            <ul class="dropdown-menu border-0 shadow" aria-labelledby="adminControlsDropdown">
                                <?php if (in_array($role_id, [1, 2, 3])): ?>
                                    <li><a href="add_user.php" class="dropdown-item">Add Member</a></li>
                                <?php endif; ?>
                                <li><a href="add_saving.php" class="dropdown-item">Add Saving</a></li>
                                <li><a href="view_all_savings.php" class="dropdown-item">View Savings</a></li>
                                <li><a href="manage_requests.php" class="dropdown-item">Manage Requests</a></li>
                                <?php if (in_array($role_id, [1, 2])): ?>
                                    <li><a href="apply_interest.php" class="dropdown-item">Apply Interest</a></li>
                                <?php endif; ?>
                            </ul>
                        </li>
                        <li class="nav-item"><a href="reports.php" class="nav-link py-2 px-3 rounded hover:bg-green-300">Reports</a></li>
                    <?php endif; ?>

                    <?php if (in_array($role_id, [1, 2])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle py-2 px-3 rounded hover:bg-green-300" href="#" id="advancedDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Advanced
                            </a>
                            <ul class="dropdown-menu border-0 shadow" aria-labelledby="advancedDropdown">
                                <li><a href="admin_reset_password.php" class="dropdown-item">Reset Password</a></li>
                                <li><a href="admin_audit_view.php" class="dropdown-item">Audit View</a></li>
                                <li><a href="admin_analytics_view.php" class="dropdown-item">Analytics View</a></li>
                                <li><a href="admin_tabular_view.php" class="dropdown-item">Tabular View</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>

                    <?php if ($role_id == 2): ?>
                        <li class="nav-item"><a href="handover.php" class="nav-link py-2 px-3 rounded hover:bg-green-300">Handover</a></li>
                    <?php endif; ?>

                    <li class="nav-item lg:hidden border-t mt-2 pt-2">
                        <a href="settings.php" class="nav-link py-2 px-3 rounded hover:bg-green-300">Settings</a>
                        <a href="logout.php" class="nav-link py-2 px-3 rounded hover:bg-green-300 text-red-600">Logout</a>
                    </li>
                </ul>

                <!-- Desktop Settings/Logout -->
                <div class="hidden lg:flex items-center space-x-2 ms-auto">
                    <a href="settings.php" class="text-sm font-medium hover:text-purple-600">Settings</a>
                    <span class="text-gray-300">|</span>
                    <a href="logout.php" class="text-sm font-medium text-red-600 hover:text-red-800">Logout</a>
                </div>
            </div>
        </nav>
    </header>

    <main class="container mx-auto p-4 md:p-6">
    <?php endif; ?>
