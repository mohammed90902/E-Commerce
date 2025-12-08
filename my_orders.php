<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'my_store');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

$username = $_SESSION['username'];

// Pay for order
if (isset($_GET['pay'])) {
    $order_id = intval($_GET['pay']);
    $conn->query("UPDATE orders SET status='Paid' WHERE order_id=$order_id AND username='$username'");
    $_SESSION['success_message'] = "Order #$order_id paid successfully!";
    header('Location: my_orders.php');
    exit();
}

// Delete order (only if Pending)
if (isset($_GET['delete'])) {
    $order_id = intval($_GET['delete']);
    
    // Verify order exists and is Pending
    $check_order = $conn->query("SELECT * FROM orders WHERE order_id=$order_id AND username='$username'");
    
    if ($check_order->num_rows > 0) {
        $order = $check_order->fetch_assoc();
        if ($order['status'] === 'Pending') {
            $conn->query("DELETE FROM orders WHERE order_id=$order_id");
            $_SESSION['success_message'] = "Order #$order_id cancelled successfully!";
        } else {
            $_SESSION['error_message'] = "Cannot cancel order #$order_id - Status is already " . $order['status'];
        }
    } else {
        $_SESSION['error_message'] = "Order #$order_id not found or doesn't belong to you";
    }
    
    header('Location: my_orders.php');
    exit();
}

// Logout logic (from index.php, included for consistency)
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

// Get all user orders
$orders = $conn->query("SELECT * FROM orders WHERE username='$username' ORDER BY order_date DESC");
?>

<!DOCTYPE html>
<html lang="en" class="light"> <head>
    <meta charset="UTF-8">
    <title>My Orders - ShadaShop</title>
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
<body class="bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-gray-100 min-h-screen flex flex-col">

<nav class="bg-gray-800 dark:bg-gray-950 p-4 shadow-md">
    <div class="container mx-auto flex flex-col md:flex-row justify-between items-center">
        <a class="flex items-center text-white text-2xl font-bold mb-4 md:mb-0" href="index.php">
            <img src="https://cdn-icons-png.flaticon.com/512/263/263115.png" class="w-8 h-8 mr-2" alt="ShadaShop Logo">
            ShadaShop
        </a>
        <div class="flex flex-col md:flex-row items-center space-y-4 md:space-y-0 md:space-x-6 w-full md:w-auto">
            <ul class="flex flex-col md:flex-row space-y-2 md:space-y-0 md:space-x-6 text-white text-lg">
                <li><a class="hover:text-yellow-400 transition-colors duration-200" href="index.php">Home</a></li>
                <li><a class="hover:text-yellow-400 transition-colors duration-200" href="register.php">Register</a></li>
                <li>
                    <a class="flex items-center hover:text-yellow-400 transition-colors duration-200" href="my_orders.php">
                        🛒 My Orders
                        <?php if (!empty($_SESSION['cart'])): ?>
                            <span class="ml-1 bg-red-500 text-white text-xs font-semibold px-2 py-1 rounded-full">
                                <?= array_sum(array_column($_SESSION['cart'], 'quantity')) ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </li>
            </ul>
            <button id="theme-toggle" class="ml-4 p-2 rounded-full bg-gray-700 dark:bg-gray-600 text-white hover:bg-gray-600 dark:hover:bg-gray-500 transition-colors duration-200" aria-label="Toggle dark mode">
                ☀️ / 🌙
            </button>
        </div>
    </div>
</nav>

<?php if (isset($_SESSION['error_message'])): ?>
    <div class="bg-red-500 text-white p-3 text-center rounded-md mx-auto my-4 max-w-lg">
        <?= $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['success_message'])): ?>
    <div class="bg-green-500 text-white p-3 text-center rounded-md mx-auto my-4 max-w-lg">
        <?= $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
    </div>
<?php endif; ?>

<div class="bg-gray-700 dark:bg-gray-800 text-white p-3 flex flex-col md:flex-row justify-between items-center shadow-md">
    <?php if (isset($_SESSION['username'])): ?>
        <div class="flex items-center mb-2 md:mb-0">
            Welcome <strong class="ml-1 text-yellow-400"><?= htmlspecialchars($_SESSION['username']); ?></strong>
            <div class="relative ml-4">
                <button id="account-dropdown-toggle" type="button" class="bg-gray-600 hover:bg-gray-500 text-white font-semibold py-1 px-3 rounded-md transition-colors duration-200" aria-haspopup="true" aria-expanded="false">Account ▼</button>
                <ul id="account-dropdown-menu" class="absolute hidden bg-gray-700 dark:bg-gray-900 text-white rounded-md shadow-lg z-10 w-40 mt-2">
                    <li><a class="block px-4 py-2 hover:bg-gray-600 dark:hover:bg-gray-800 transition-colors duration-200" href="account.php">Manage Account</a></li>
                    <li><a class="block px-4 py-2 hover:bg-gray-600 dark:hover:bg-gray-800 transition-colors duration-200" href="my_orders.php">My Orders</a></li>
                    <li><hr class="border-gray-600 dark:border-gray-700"></li>
                    <li><a class="block px-4 py-2 hover:bg-gray-600 dark:hover:bg-gray-800 transition-colors duration-200" href="#" onclick="confirmLogout()">Logout</a></li>
                </ul>
            </div>
        </div>
        <?php if (!empty($_SESSION['is_admin']) && $_SESSION['is_admin'] === true): ?>
            <a href="admin.php" class="bg-yellow-500 hover:bg-yellow-600 text-gray-900 font-bold py-1 px-3 rounded-md transition-colors duration-200 mt-2 md:mt-0">Admin Panel</a>
        <?php endif; ?>
    <?php else: ?>
        Welcome Guest <a href="login.php" class="text-yellow-400 ml-2 hover:underline">Login</a>
    <?php endif; ?>
</div>

<div class="container mx-auto my-6 p-4 flex-grow">
    <h2 class="text-3xl font-bold text-center mb-6 dark:text-gray-100">My Orders</h2>
    
    <?php if ($orders->num_rows > 0): ?>
    <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded-lg shadow-lg">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-yellow-500 dark:bg-yellow-600 text-gray-900 dark:text-gray-100">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider">Order ID</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider">Product</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider">Quantity</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider">Total</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider">Date</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                <?php while($row = $orders->fetch_assoc()): ?>
                <tr class="bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">#<?= $row['order_id'] ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300"><?= htmlspecialchars($row['product_name']) ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300"><?= $row['quantity'] ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 font-bold">$<?= number_format($row['total_price'], 2) ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300"><?= date('M j, Y g:i a', strtotime($row['order_date'])) ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold 
                        <?php 
                            echo $row['status'] === 'Pending' ? 'text-yellow-500 dark:text-yellow-400' : 'text-green-600 dark:text-green-400'; 
                        ?>">
                        <?= htmlspecialchars($row['status']) ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <?php if ($row['status'] === 'Pending'): ?>
                            <a href="my_orders.php?pay=<?= $row['order_id'] ?>" class="inline-block bg-green-500 hover:bg-green-600 text-white font-bold py-1 px-3 rounded-md transition-colors duration-200 mr-2">Pay</a>
                            <a href="my_orders.php?delete=<?= $row['order_id'] ?>" 
                               onclick="return confirm('Are you sure you want to cancel order #<?= $row['order_id'] ?>?')" 
                               class="inline-block bg-red-500 hover:bg-red-600 text-white font-bold py-1 px-3 rounded-md transition-colors duration-200">Cancel</a>
                        <?php else: ?>
                            <span class="text-gray-500 dark:text-gray-400">No actions available</span>
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
            <p>You haven't placed any orders yet. Start exploring our products!</p>
            <a href="index.php" class="inline-block mt-4 bg-yellow-500 hover:bg-yellow-600 text-gray-900 font-bold py-2 px-4 rounded-md transition-colors duration-200">Start Shopping</a>
        </div>
    <?php endif; ?>
    
    <div class="text-center mt-8">
        <a href="index.php" class="inline-block bg-gray-600 hover:bg-gray-700 dark:bg-gray-700 dark:hover:bg-gray-600 text-white font-bold py-2 px-6 rounded-md transition-colors duration-200">Back to Shop</a>
    </div>
</div>

<script>
    function confirmLogout() {
        if (confirm('Are you sure you want to logout?')) {
            window.location.href = 'index.php?logout=true';
        }
    }

    // Dark mode toggle functionality
    const themeToggleBtn = document.getElementById('theme-toggle');
    if (themeToggleBtn) { // Ensure button exists before adding listener
        themeToggleBtn.addEventListener('click', () => {
            if (document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark');
                localStorage.theme = 'light';
            } else {
                document.documentElement.classList.add('dark');
                localStorage.theme = 'dark';
            }
        });
    }

    // Account dropdown functionality
    const accountDropdownToggle = document.getElementById('account-dropdown-toggle');
    const accountDropdownMenu = document.getElementById('account-dropdown-menu');

    if (accountDropdownToggle && accountDropdownMenu) { // Ensure elements exist
        accountDropdownToggle.addEventListener('click', (event) => {
            event.stopPropagation(); // Prevent document click listener from closing immediately
            const isExpanded = accountDropdownToggle.getAttribute('aria-expanded') === 'true';
            accountDropdownToggle.setAttribute('aria-expanded', !isExpanded);
            accountDropdownMenu.classList.toggle('hidden');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', (event) => {
            if (!accountDropdownToggle.contains(event.target) && !accountDropdownMenu.contains(event.target)) {
                accountDropdownMenu.classList.add('hidden');
                accountDropdownToggle.setAttribute('aria-expanded', 'false');
            }
        });
    }
</script>

<footer class="bg-gray-800 dark:bg-gray-950 text-white p-6 text-center mt-auto shadow-inner">
    <p>&copy; 2025 ShadaShop. All rights reserved.</p>
</footer>

</body>
</html>