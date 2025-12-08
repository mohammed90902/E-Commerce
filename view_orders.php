<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'my_store');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure user is logged in and is an admin
if (!isset($_SESSION['username']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: ../login.php"); // Redirect to login if not an admin
    exit();
}

// Accept order
if (isset($_GET['accept_order'])) {
    $order_id = intval($_GET['accept_order']);
    // Use prepared statement for security
    $stmt = $conn->prepare("UPDATE orders SET status = 'Accepted' WHERE order_id = ?");
    $stmt->bind_param("i", $order_id);
    if ($stmt->execute()) {
        $_SESSION['admin_message'] = "Order #$order_id accepted successfully!";
    } else {
        $_SESSION['admin_message'] = "Error accepting order #$order_id: " . $stmt->error;
    }
    $stmt->close();
    header("Location: view_orders.php");
    exit();
}

// Delete order
if (isset($_GET['delete_order'])) {
    $order_id = intval($_GET['delete_order']);
    // Use prepared statement for security
    $stmt = $conn->prepare("DELETE FROM orders WHERE order_id = ?");
    $stmt->bind_param("i", $order_id);
    if ($stmt->execute()) {
        $_SESSION['admin_message'] = "Order #$order_id deleted successfully!";
    } else {
        $_SESSION['admin_message'] = "Error deleting order #$order_id: " . $stmt->error;
    }
    $stmt->close();
    header("Location: view_orders.php");
    exit();
}

// Get all orders
$orders_result = $conn->query("SELECT * FROM orders ORDER BY order_date DESC");
?>

<!DOCTYPE html>
<html lang="en" class="light"> <head>
    <meta charset="UTF-8">
    <title>View Orders - ShadaShop Admin</title>
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

<div class="w-full max-w-7xl bg-white dark:bg-gray-800 rounded-lg shadow-xl p-8 transform transition-all duration-300">
    <h2 class="text-3xl font-bold text-center mb-6 text-gray-800 dark:text-gray-200">Manage Customer Orders</h2>

    <?php if (isset($_SESSION['admin_message'])): ?>
        <div class="bg-green-100 dark:bg-green-900 border border-green-400 text-green-700 dark:text-green-200 px-4 py-3 rounded relative mb-4" role="alert">
            <strong class="font-bold">Success!</strong>
            <span class="block sm:inline"><?= htmlspecialchars($_SESSION['admin_message']); unset($_SESSION['admin_message']); ?></span>
        </div>
    <?php endif; ?>

    <div class="flex justify-between items-center mb-4">
        <a href="../admin.php" class="bg-gray-600 hover:bg-gray-700 dark:bg-gray-700 dark:hover:bg-gray-600 text-white font-bold py-2 px-4 rounded-md transition-colors duration-200">
            &larr; Back to Admin Panel
        </a>
        <span class="text-lg font-semibold text-gray-700 dark:text-gray-300">Total Orders: <?= $orders_result->num_rows ?></span>
    </div>

    <?php if ($orders_result->num_rows > 0): ?>
        <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded-lg shadow-md">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-200 dark:bg-gray-700">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Order ID</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Customer</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Product</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Quantity</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Total ($)</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Order Date</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <?php while($order = $orders_result->fetch_assoc()): ?>
                    <tr class="bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">#<?= $order['order_id'] ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300"><?= htmlspecialchars($order['username']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300"><?= htmlspecialchars($order['product_name']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300"><?= (int)$order['quantity'] ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-gray-100">$<?= number_format($order['total_price'], 2) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300"><?= date('M j, Y H:i', strtotime($order['order_date'])) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold 
                            <?php 
                                if (strtolower($order['status']) === 'pending') {
                                    echo 'text-yellow-500 dark:text-yellow-400';
                                } elseif (strtolower($order['status']) === 'accepted') {
                                    echo 'text-green-600 dark:text-green-400';
                                } else {
                                    echo 'text-blue-500 dark:text-blue-400'; // For 'Paid' or other statuses
                                }
                            ?>">
                            <?= htmlspecialchars($order['status']) ?>
                            <?php if (strtolower($order['status']) === 'pending'): ?>
                                <br><small class="text-xs text-gray-500 dark:text-gray-400 italic">Awaiting approval</small>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-y-2">
                            <?php if (strtolower($order['status']) !== 'accepted'): ?>
                                <a href="?accept_order=<?= $order['order_id'] ?>"
                                   class="block w-full bg-green-500 hover:bg-green-600 text-white font-bold py-1.5 px-3 rounded-md transition-colors duration-200"
                                   onclick="return confirm('Are you sure you want to accept order #<?= $order['order_id'] ?>?');">
                                    Accept
                                </a>
                            <?php else: ?>
                                <button class="block w-full bg-gray-400 dark:bg-gray-600 text-white font-bold py-1.5 px-3 rounded-md cursor-not-allowed" disabled>
                                    Accepted
                                </button>
                            <?php endif; ?>
                            <a href="?delete_order=<?= $order['order_id'] ?>"
                               class="block w-full bg-red-600 hover:bg-red-700 text-white font-bold py-1.5 px-3 rounded-md transition-colors duration-200 mt-2"
                               onclick="return confirm('Are you sure you want to delete order #<?= $order['order_id'] ?>? This action cannot be undone.');">
                                Delete
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="bg-blue-100 dark:bg-blue-900 border-l-4 border-blue-500 dark:border-blue-400 text-blue-700 dark:text-blue-200 p-4 rounded-lg my-6" role="alert">
            <h4 class="font-bold text-xl mb-2">No Orders Found!</h4>
            <p>There are currently no customer orders to display. Check back later!</p>
        </div>
    <?php endif; ?>
</div>

</body>
</html>