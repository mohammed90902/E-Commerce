<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'my_store');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure user is logged in and is an admin
// Changed from `$_SESSION['username'] !== 'admin'` to `!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true`
// This is more robust as it checks the boolean admin flag set during login.
if (!isset($_SESSION['username']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['accept']) && is_numeric($_GET['accept'])) {
    $order_id = intval($_GET['accept']);
    $conn->query("UPDATE orders SET status='Accepted' WHERE order_id=$order_id"); // Assuming 'order_id' is the primary key
    $_SESSION['success_message'] = "Order #$order_id has been accepted!"; // Add success message
    header("Location: admin-orders.php");
    exit();
}

$orders = $conn->query("SELECT * FROM orders ORDER BY order_date DESC");
?>

<!DOCTYPE html>
<html lang="en" class="light"> <head>
    <meta charset="UTF-8">
    <title>Admin - Orders - ShadaShop</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class', // Enable dark mode based on 'dark' class on html
        }
    </script>
    <script>
        // On page load, apply theme based on localStorage or system preference
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
</head>
<body class="bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-gray-100 min-h-screen flex flex-col items-center p-4">

<div class="w-full max-w-6xl bg-white dark:bg-gray-800 rounded-lg shadow-xl p-8 transform transition-all duration-300">
    <h2 class="text-3xl font-bold text-center mb-6 text-blue-600 dark:text-blue-400">Manage Orders</h2>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="bg-green-100 dark:bg-green-900 border border-green-400 text-green-700 dark:text-green-200 px-4 py-3 rounded relative mb-4" role="alert">
            <strong class="font-bold">Success!</strong>
            <span class="block sm:inline"><?= $_SESSION['success_message']; unset($_SESSION['success_message']); ?></span>
        </div>
    <?php endif; ?>

    <div class="flex justify-between items-center mb-4">
        <a href="admin.php" class="bg-gray-600 hover:bg-gray-700 dark:bg-gray-700 dark:hover:bg-gray-600 text-white font-bold py-2 px-4 rounded-md transition-colors duration-200">
            &larr; Back to Admin Panel
        </a>
        <span class="text-lg font-semibold text-gray-700 dark:text-gray-300">Total Orders: <?= $orders->num_rows ?></span>
    </div>

    <?php if ($orders->num_rows > 0): ?>
    <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded-lg shadow-md">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-200 dark:bg-gray-700">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Order ID</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Username</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Product</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Qty</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Total ($)</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Order Date</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                <?php while ($row = $orders->fetch_assoc()): ?>
                <tr class="bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">#<?= $row['order_id'] ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300"><?= htmlspecialchars($row['username']) ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300"><?= htmlspecialchars($row['product_name']) ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300"><?= $row['quantity'] ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-gray-100">$<?= number_format($row['total_price'], 2) ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold 
                        <?php 
                            if ($row['status'] === 'Pending') {
                                echo 'text-yellow-500 dark:text-yellow-400';
                            } elseif ($row['status'] === 'Accepted') {
                                echo 'text-green-600 dark:text-green-400';
                            } else {
                                echo 'text-blue-500 dark:text-blue-400'; // For 'Paid' or other statuses
                            }
                        ?>">
                        <?= htmlspecialchars($row['status']) ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300"><?= date('M j, Y H:i', strtotime($row['order_date'])) ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <?php if ($row['status'] === 'Pending'): ?>
                            <a href="admin-orders.php?accept=<?= $row['order_id'] ?>" class="inline-block bg-green-500 hover:bg-green-600 text-white font-bold py-1.5 px-3 rounded-md transition-colors duration-200">Accept</a>
                        <?php else: ?>
                            <span class="text-gray-500 dark:text-gray-400">✅</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
        <div class="bg-blue-100 dark:bg-blue-900 border-l-4 border-blue-500 dark:border-blue-400 text-blue-700 dark:text-blue-200 p-4 rounded-lg my-6" role="alert">
            <h4 class="font-bold text-xl mb-2">No orders found!</h4>
            <p>There are currently no orders to display.</p>
        </div>
    <?php endif; ?>
</div>

</body>
</html>