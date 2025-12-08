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

// Delete Product
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete']; // Cast to int for security
    // Fetch image path before deleting product
    $image_path_query = $conn->prepare("SELECT image FROM products WHERE id = ?");
    $image_path_query->bind_param("i", $id);
    $image_path_query->execute();
    $result_image = $image_path_query->get_result();
    $image_to_delete = $result_image->fetch_assoc();
    $image_path_query->close();

    // Delete product from database
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        // If product deleted, attempt to delete the image file
        if ($image_to_delete && !empty($image_to_delete['image'])) {
            $full_image_path = '../' . $image_to_delete['image']; // Assuming image path is relative to root, and this file is in 'buttons/'
            if (file_exists($full_image_path)) {
                unlink($full_image_path); // Delete the file
            }
        }
        $_SESSION['success_message'] = "Product and associated image deleted successfully!";
    } else {
        $_SESSION['error_message'] = "Error deleting product: " . $stmt->error;
    }
    $stmt->close();
    header("Location: view_products.php");
    exit();
}

// Get all Products
$products_result = $conn->query("SELECT * FROM products ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en" class="light"> <head>
    <meta charset="UTF-8">
    <title>View Products - ShadaShop Admin</title>
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
    <h2 class="text-3xl font-bold text-center mb-6 text-yellow-600 dark:text-yellow-400">View All Products</h2>

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

    <div class="flex justify-between items-center mb-4">
        <a href="../admin.php" class="bg-gray-600 hover:bg-gray-700 dark:bg-gray-700 dark:hover:bg-gray-600 text-white font-bold py-2 px-4 rounded-md transition-colors duration-200">
            &larr; Back to Admin Panel
        </a>
        <span class="text-lg font-semibold text-gray-700 dark:text-gray-300">Total Products: <?= $products_result->num_rows ?></span>
    </div>

    <?php if ($products_result->num_rows > 0): ?>
        <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded-lg shadow-md">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-200 dark:bg-gray-700">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">ID</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Product Name</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Brand</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Category</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Price</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Image</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <?php while($row = $products_result->fetch_assoc()): ?>
                        <tr class="bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100"><?= $row['id']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300"><?= htmlspecialchars($row['name']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300"><?= htmlspecialchars($row['brand']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300"><?= htmlspecialchars($row['category']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-gray-100">$<?= number_format($row['price'], 2); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <?php if ($row['image'] && file_exists('../' . $row['image'])): // Check if image exists on server ?>
                                    <img src="../<?= htmlspecialchars($row['image']); ?>" alt="Product Image" class="w-16 h-16 object-cover rounded-md shadow-sm">
                                <?php else: ?>
                                    <span class="text-gray-400 dark:text-gray-600 text-xs">No Image</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="?delete=<?= $row['id']; ?>" 
                                   onclick="return confirm('Are you sure you want to delete this product? This action will also delete the associated image and cannot be undone.');" 
                                   class="inline-block bg-red-600 hover:bg-red-700 text-white font-bold py-1.5 px-3 rounded-md transition-colors duration-200">
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
            <h4 class="font-bold text-xl mb-2">No Products Found!</h4>
            <p>There are no products to display yet. Consider adding some from the admin panel.</p>
        </div>
    <?php endif; ?>
</div>

</body>
</html>