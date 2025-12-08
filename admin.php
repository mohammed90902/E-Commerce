<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'my_store');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if user is logged in AND is an admin
if (!isset($_SESSION['username']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: login.php'); // Redirect to login if not an admin
    exit();
}


// Delete product (this logic is usually in view_products.php, but kept here as per your original)
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM products WHERE id = $id");
    // You might want to add a success message here too, e.g.:
    $_SESSION['success_message'] = "Product deleted successfully!";
    header('Location: admin.php'); // Redirect back to admin panel
    exit();
}

// Register admin logic
$message = "";
if (isset($_GET['register_admin']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $conn->real_escape_string($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    // Check if admin already exists
    $check_stmt = $conn->prepare("SELECT id FROM admins WHERE username = ?");
    $check_stmt->bind_param("s", $username);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        $message = "Admin username already exists!";
    } else {
        $sql = "INSERT INTO admins (username, password) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $username, $password);
        if ($stmt->execute()) {
            $message = "New admin registered successfully!";
        } else {
            $message = "Error: " . $conn->error;
        }
        $stmt->close();
    }
    $check_stmt->close();
}

// Get products (optional, not shown in HTML but can be used)
$products = $conn->query("SELECT * FROM products");
?>

<!DOCTYPE html>
<html lang="en" class="light"> <head>
    <meta charset="UTF-8">
    <title>Admin Panel - ShadaShop</title>
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
<body class="bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-gray-100 min-h-screen flex items-center justify-center p-4">

<div class="w-full max-w-4xl bg-white dark:bg-gray-800 rounded-lg shadow-xl p-8 transform transition-all duration-300">
    
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="bg-green-100 dark:bg-green-900 border border-green-400 text-green-700 dark:text-green-200 px-4 py-3 rounded relative mb-4" role="alert">
            <strong class="font-bold">Success!</strong>
            <span class="block sm:inline"><?= $_SESSION['success_message']; unset($_SESSION['success_message']); ?></span>
        </div>
    <?php endif; ?>

    <?php if (!isset($_GET['register_admin'])): ?>
        <h2 class="text-3xl font-bold text-center mb-6 text-blue-600 dark:text-blue-400">Admin Panel</h2>

        <div class="flex flex-wrap justify-center gap-3 mb-6">
            <a href="buttons/insert_brand.php" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition-colors duration-200 shadow-md">Insert Brand</a>
            <a href="buttons/insert_category.php" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg transition-colors duration-200 shadow-md">Insert Category</a>
            <a href="buttons/insert_product.php" class="bg-yellow-600 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded-lg transition-colors duration-200 shadow-md">Insert Product</a>
            <a href="buttons/view_brands.php" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg transition-colors duration-200 border border-blue-500">View Brands</a>
            <a href="buttons/view_categories.php" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded-lg transition-colors duration-200 border border-green-500">View Categories</a>
            <a href="buttons/view_products.php" class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded-lg transition-colors duration-200 border border-yellow-500">View Products</a>
            <a href="buttons/view_users.php" class="bg-indigo-500 hover:bg-indigo-600 text-white font-bold py-2 px-4 rounded-lg transition-colors duration-200 border border-indigo-500">View Users</a>
            <a href="buttons/view_orders.php" class="bg-gray-700 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded-lg transition-colors duration-200 border border-gray-700">View Orders</a>
            <a href="?register_admin=1" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg transition-colors duration-200 shadow-md">Register New Admin</a>
        </div>

        <a href="index.php" class="block w-full text-center bg-gray-600 hover:bg-gray-700 dark:bg-gray-700 dark:hover:bg-gray-600 text-white font-bold py-2 px-4 rounded-lg transition-colors duration-200 mt-4">Back to Main Site</a>

    <?php else: ?>
        <h2 class="text-3xl font-bold text-center mb-6 text-red-600 dark:text-red-400">Register New Admin</h2>
        
        <?php if ($message): ?>
            <div class="bg-blue-100 dark:bg-blue-900 border border-blue-400 text-blue-700 dark:text-blue-200 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?= $message ?></span>
            </div>
        <?php endif; ?>

        <form method="post" action="?register_admin=1" class="space-y-5">
            <div>
                <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Username</label>
                <input type="text" name="username" id="username" class="mt-1 block w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Password</label>
                <input type="password" name="password" id="password" class="mt-1 block w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
            </div>
            <div class="flex justify-between space-x-4">
                <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-lg font-semibold text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                    Register Admin
                </button>
                <a href="admin.php" class="w-full flex justify-center py-3 px-4 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-lg font-semibold text-gray-700 dark:text-gray-200 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors duration-200">
                    Back
                </a>
            </div>
        </form>
    <?php endif; ?>
</div>

</body>
</html>