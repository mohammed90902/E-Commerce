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

// Delete Category
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete']; // Cast to int for security
    $stmt = $conn->prepare("DELETE FROM categories WHERE category_id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Category deleted successfully!";
    } else {
        $_SESSION['error_message'] = "Error deleting category: " . $stmt->error;
    }
    $stmt->close();
    header("Location: view_categories.php");
    exit();
}

// Get all Categories
$categories = $conn->query("SELECT * FROM categories");
?>

<!DOCTYPE html>
<html lang="en" class="light"> <head>
    <meta charset="UTF-8">
    <title>View Categories - ShadaShop Admin</title>
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

<div class="w-full max-w-4xl bg-white dark:bg-gray-800 rounded-lg shadow-xl p-8 transform transition-all duration-300">
    <h2 class="text-3xl font-bold text-center mb-6 text-green-600 dark:text-green-400">View Categories</h2>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="bg-green-100 dark:bg-green-900 border border-green-400 text-green-700 dark:text-green-200 px-4 py-3 rounded relative mb-4" role="alert">
            <strong class="font-bold">Success!</strong>
            <span class="block sm:inline"><?= $_SESSION['success_message']; unset($_SESSION['success_message']); ?></span>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="bg-red-100 dark:bg-red-900 border border-red-400 text-red-700 dark:text-red-200 px-4 py-3 rounded relative mb-4" role="alert">
            <strong class="font-bold">Error!</strong>
            <span class="block sm:inline"><?= $_SESSION['error_message']; unset($_SESSION['error_message']); ?></span>
        </div>
    <?php endif; ?>

    <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded-lg shadow-md mb-6">
        <?php if ($categories->num_rows > 0): ?>
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-200 dark:bg-gray-700">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">ID</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Category Name</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <?php while($row = $categories->fetch_assoc()): ?>
                        <tr class="bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100"><?= $row['category_id']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300"><?= htmlspecialchars($row['category_name']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="?delete=<?= $row['category_id']; ?>" 
                                   onclick="return confirm('Are you sure you want to delete this category? This action cannot be undone.');" 
                                   class="inline-block bg-red-600 hover:bg-red-700 text-white font-bold py-1.5 px-3 rounded-md transition-colors duration-200">
                                    Delete
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="bg-blue-100 dark:bg-blue-900 border-l-4 border-blue-500 dark:border-blue-400 text-blue-700 dark:text-blue-200 p-4 rounded-lg my-6" role="alert">
                <h4 class="font-bold text-xl mb-2">No Categories Found!</h4>
                <p>There are no categories to display yet. Consider adding some from the admin panel.</p>
            </div>
        <?php endif; ?>
    </div>

    <a href="../admin.php" class="block w-full text-center bg-gray-600 hover:bg-gray-700 dark:bg-gray-700 dark:hover:bg-gray-600 text-white font-bold py-2 px-4 rounded-lg transition-colors duration-200 mt-4">
        Back to Admin Panel
    </a>
</div>

</body>
</html>