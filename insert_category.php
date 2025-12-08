<?php
session_start();
$conn = new mysqli("localhost", "root", "", "my_store");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure user is logged in and is an admin
if (!isset($_SESSION['username']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: ../login.php"); // Redirect to login if not an admin
    exit();
}

$msg = ""; // Initialize message variable

if (isset($_POST['submit'])) {
    $category_name = trim($_POST['category_name']);

    if (!empty($category_name)) {
        // Prepare and execute statement to check for existing category
        $check = $conn->prepare("SELECT * FROM categories WHERE category_name=?");
        $check->bind_param("s", $category_name);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            $msg = "Category already exists!";
        } else {
            // Prepare and execute statement to insert new category
            $insert = $conn->prepare("INSERT INTO categories (category_name) VALUES (?)");
            $insert->bind_param("s", $category_name);
            if ($insert->execute()) {
                $msg = "Category inserted successfully!";
            } else {
                $msg = "Error: " . $conn->error;
            }
            $insert->close();
        }
        $check->close();
    } else {
        $msg = "Please enter a category name.";
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="light"> <head>
    <meta charset="UTF-8">
    <title>Insert Category - ShadaShop Admin</title>
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

<div class="w-full max-w-md bg-white dark:bg-gray-800 rounded-lg shadow-xl p-8 transform transition-all duration-300">
    <h2 class="text-3xl font-bold text-center mb-6 text-green-600 dark:text-green-400">Insert New Category</h2>

    <?php if (!empty($msg)): ?>
        <div class="
            <?php 
                // Determine alert style based on message content
                if (strpos($msg, 'successfully') !== false) {
                    echo 'bg-green-100 dark:bg-green-900 border border-green-400 text-green-700 dark:text-green-200';
                } else {
                    echo 'bg-red-100 dark:bg-red-900 border border-red-400 text-red-700 dark:text-red-200';
                }
            ?>
            px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline"><?= htmlspecialchars($msg) ?></span>
        </div>
    <?php endif; ?>

    <form method="post" class="space-y-5">
        <div>
            <label for="category_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Category Name</label>
            <input type="text" name="category_name" id="category_name" class="mt-1 block w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-green-500 focus:border-green-500" placeholder="Enter Category Name" required>
        </div>
        <button type="submit" name="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-lg font-semibold text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200">
            Insert Category
        </button>
        <a href="../admin.php" class="w-full flex justify-center py-3 px-4 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-lg font-semibold text-gray-700 dark:text-gray-200 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors duration-200 mt-3">
            Back to Admin Panel
        </a>
    </form>
</div>

</body>
</html>