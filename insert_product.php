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

$msg = ""; // Initialize message variable

// Fetch categories from database
$categories = [];
$cat_result = $conn->query("SELECT category_id, category_name FROM categories ORDER BY category_name");
if ($cat_result) {
    while ($row = $cat_result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Fetch brands from database
$brands = [];
$brand_result = $conn->query("SELECT brand_id, brand_name FROM brands ORDER BY brand_name");
if ($brand_result) {
    while ($row = $brand_result->fetch_assoc()) {
        $brands[] = $row;
    }
}

// Add new product
if (isset($_POST['add_product'])) {
    $product_name = trim($_POST['product_name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']); // Ensure price is a float
    $brand_id = intval($_POST['brand_id']); // Get brand ID
    $category_id = intval($_POST['category_id']); // Get category ID

    $image = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $image_dir = '../product_images/'; // Adjusted path, assuming this file is in 'buttons/'
        // Create directory if it doesn't exist
        if (!is_dir($image_dir)) {
            mkdir($image_dir, 0777, true); // Recursive create with full permissions
        }
        
        $image_name = basename($_FILES['image']['name']);
        $image_path = $image_dir . $image_name;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $image_path)) {
            $image = 'product_images/' . $image_name; // Store relative path for database
        } else {
            $msg = "Error uploading image.";
        }
    } else if ($_FILES['image']['error'] == UPLOAD_ERR_NO_FILE) {
        $msg = "Please select an image file.";
    } else {
        $msg = "Image upload error: " . $_FILES['image']['error'];
    }

    if (empty($msg)) { // Only proceed if no image upload error
        // Updated SQL to use category_id and brand_id instead of text values
        $stmt = $conn->prepare("INSERT INTO products (name, description, price, brand_id, category_id, image) 
                                VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdiss", $product_name, $description, $price, $brand_id, $category_id, $image);

        if ($stmt->execute()) {
            $msg = "Product added successfully!";
        } else {
            $msg = "Failed to add product: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="light"> <head>
    <meta charset="UTF-8">
    <title>Insert Product - ShadaShop Admin</title>
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

<div class="w-full max-w-lg bg-white dark:bg-gray-800 rounded-lg shadow-xl p-8 transform transition-all duration-300">
    <h2 class="text-3xl font-bold text-center mb-6 text-yellow-600 dark:text-yellow-400">Insert New Product</h2>

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

    <form method="POST" enctype="multipart/form-data" class="space-y-5">
        <div>
            <label for="product_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Product Name</label>
            <input type="text" id="product_name" class="mt-1 block w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-yellow-500 focus:border-yellow-500" name="product_name" placeholder="Product Name" required>
        </div>
        <div>
            <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
            <textarea id="description" class="mt-1 block w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-yellow-500 focus:border-yellow-500" name="description" placeholder="Description" required></textarea>
        </div>
        <div>
            <label for="price" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Price</label>
            <input type="number" step="0.01" id="price" class="mt-1 block w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-yellow-500 focus:border-yellow-500" name="price" placeholder="Price" required>
        </div>
        <div>
            <label for="brand_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Brand</label>
            <select id="brand_id" name="brand_id" class="mt-1 block w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-yellow-500 focus:border-yellow-500" required>
                <option value="">Select a Brand</option>
                <?php foreach ($brands as $brand): ?>
                    <option value="<?php echo $brand['brand_id']; ?>"><?php echo htmlspecialchars($brand['brand_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="category_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Category</label>
            <select id="category_id" name="category_id" class="mt-1 block w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-yellow-500 focus:border-yellow-500" required>
                <option value="">Select a Category</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['category_id']; ?>"><?php echo htmlspecialchars($category['category_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="image" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Product Image</label>
            <input type="file" id="image" class="mt-1 block w-full text-sm text-gray-900 dark:text-gray-100 
                   file:mr-4 file:py-2 file:px-4 
                   file:rounded-md file:border-0 
                   file:text-sm file:font-semibold 
                   file:bg-yellow-50 file:text-yellow-700 
                   hover:file:bg-yellow-100
                   dark:file:bg-yellow-900 dark:file:text-yellow-200 dark:hover:file:bg-yellow-800" name="image" accept="image/*" required>
        </div>
        <button type="submit" name="add_product" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-lg font-semibold text-gray-900 bg-yellow-500 hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 transition-colors duration-200">
            Add Product
        </button>
        <a href="../admin.php" class="w-full flex justify-center py-3 px-4 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-lg font-semibold text-gray-700 dark:text-gray-200 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors duration-200 mt-3">
            Back to Admin Panel
        </a>
    </form>
</div>

</body>
</html>