<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'my_store');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Add to cart logic
if (isset($_POST['add_to_cart'])) {
    if (!isset($_SESSION['username'])) {
        $_SESSION['error_message'] = "❌ Please log in to add products to your cart.";
        header("Location: login.php");
        exit();
    }

    $product_id = intval($_POST['product_id']);
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

    if ($quantity <= 0) {
        $_SESSION['error_message'] = "❌ Quantity must be at least 1.";
        header("Location: index.php");
        exit();
    }

    $product = $conn->query("SELECT * FROM products WHERE id = $product_id")->fetch_assoc();

    if (!$product) {
        $_SESSION['error_message'] = "❌ Product not found!";
        header("Location: index.php");
        exit();
    }

    $cart_item = [
        'id' => $product['id'],
        'name' => $product['name'],
        'price' => $product['price'],
        'quantity' => $quantity
    ];

    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    $found = false;
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['id'] == $product_id) {
            $item['quantity'] += $quantity;
            $found = true;
            break;
        }
    }
    if (!$found) {
        $_SESSION['cart'][] = $cart_item;
    }

    $username = $_SESSION['username'];
    $total_price_for_order = $product['price'] * $quantity;

    $stmt = $conn->prepare("INSERT INTO orders (username, product_name, quantity, total_price, status) VALUES (?, ?, ?, ?, 'Pending')");
    $stmt->bind_param("ssid", $username, $product['name'], $quantity, $total_price_for_order);
    $stmt->execute();
    $stmt->close();

    $_SESSION['success_message'] = "✅ Product added to cart!";
    header('Location: index.php');
    exit();
}

// Logout logic
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

// Search & Filter
$search_query = "";
$category_filter = "";
$brand_filter = "";
$sql_conditions = [];

if (isset($_GET['search_query']) && !empty($_GET['search_query'])) {
    $search_query = mysqli_real_escape_string($conn, $_GET['search_query']);
    $sql_conditions[] = "(name LIKE '%$search_query%' OR description LIKE '%$search_query%')";
}

if (isset($_GET['category_id']) && !empty($_GET['category_id'])) {
    $category_id = intval($_GET['category_id']);
    // Try both common field names for category
    $sql_conditions[] = "(category = '$category_id' OR category_id = '$category_id')";
}

if (isset($_GET['brand_id']) && !empty($_GET['brand_id'])) {
    $brand_id = intval($_GET['brand_id']);
    $sql_conditions[] = "brand_id = '$brand_id'";
}

$where_clause = "";
if (count($sql_conditions) > 0) {
    $where_clause = " WHERE " . implode(" AND ", $sql_conditions);
}

$get_products = "SELECT * FROM products" . $where_clause;

// Debug: Show the SQL query (remove this after testing)
// echo "<!-- SQL Query: " . $get_products . " -->";

$run_products = mysqli_query($conn, $get_products);

// Debug: Show number of results (remove this after testing)  
// echo "<!-- Results found: " . mysqli_num_rows($run_products) . " -->";

// Get current filter info for display
$current_category = "";
$current_brand = "";
if (isset($_GET['category_id'])) {
    $cat_result = $conn->query("SELECT category_name FROM categories WHERE category_id = " . intval($_GET['category_id']));
    if ($cat_result && $cat_result->num_rows > 0) {
        $current_category = $cat_result->fetch_assoc()['category_name'];
    }
}
if (isset($_GET['brand_id'])) {
    $brand_result = $conn->query("SELECT brand_name FROM brands WHERE brand_id = " . intval($_GET['brand_id']));
    if ($brand_result && $brand_result->num_rows > 0) {
        $current_brand = $brand_result->fetch_assoc()['brand_name'];
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="light"> <head>
    <meta charset="UTF-8">
    <title>Smart-Shop</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <script src="https://cdn.tailwindcss.com"></script>

    <script>
        tailwind.config = {
            darkMode: 'class', // Enable dark mode based on 'dark' class on html
        }
    </script>

    <script>
        // On page load, apply theme based on localStorage or system preference
        // This script should be after the tailwind config to ensure correct setup
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
            Smart Shop
        </a>
        <div class="flex flex-col md:flex-row items-center space-y-4 md:space-y-0 md:space-x-6 w-full md:w-auto">
            <ul class="flex flex-col md:flex-row space-y-2 md:space-y-0 md:space-x-6 text-white text-lg">
                <li><a class="hover:text-yellow-400 transition-colors duration-200" href="index.php">Home</a></li>
                <li><a class="hover:text-yellow-400 transition-colors duration-200" href="register.php">Register</a></li>
                <li>
                    <a class="flex items-center hover:text-yellow-400 transition-colors duration-200" href="my_orders.php">
                        🛒 Cart
                        <?php if (!empty($_SESSION['cart'])): ?>
                            <span class="ml-1 bg-red-500 text-white text-xs font-semibold px-2 py-1 rounded-full">
                                <?= array_sum(array_column($_SESSION['cart'], 'quantity')) ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </li>
            </ul>
            <form class="flex items-center mt-4 md:mt-0" action="index.php" method="GET">
                <!-- Preserve current filters when searching -->
                <?php if (isset($_GET['category_id'])): ?>
                    <input type="hidden" name="category_id" value="<?= intval($_GET['category_id']) ?>">
                <?php endif; ?>
                <?php if (isset($_GET['brand_id'])): ?>
                    <input type="hidden" name="brand_id" value="<?= intval($_GET['brand_id']) ?>">
                <?php endif; ?>
                <input class="p-2 rounded-l-md border border-gray-600 focus:outline-none focus:ring-2 focus:ring-yellow-500 bg-gray-700 text-white" type="search" name="search_query" placeholder="Search" value="<?= htmlspecialchars($_GET['search_query'] ?? '') ?>">
                <button class="bg-yellow-500 hover:bg-yellow-600 text-gray-900 font-bold p-2 rounded-r-md transition-colors duration-200" type="submit">Search</button>
            </form>
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

<!-- Active Filters Display -->
<?php if ($current_category || $current_brand || (isset($_GET['search_query']) && !empty($_GET['search_query']))): ?>
<div class="container mx-auto my-4 p-4">
    <div class="bg-blue-100 dark:bg-blue-900 border border-blue-300 dark:border-blue-700 rounded-lg p-4">
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-blue-800 dark:text-blue-200 font-semibold">Active Filters:</span>
            
            <?php if (isset($_GET['search_query']) && !empty($_GET['search_query'])): ?>
                <span class="bg-blue-500 text-white px-3 py-1 rounded-full text-sm flex items-center">
                    Search: "<?= htmlspecialchars($_GET['search_query']) ?>"
                    <a href="<?= 
                        http_build_query(array_filter([
                            'category_id' => $_GET['category_id'] ?? null,
                            'brand_id' => $_GET['brand_id'] ?? null
                        ]))
                    ?>" class="ml-2 text-white hover:text-red-200">×</a>
                </span>
            <?php endif; ?>
            
            <?php if ($current_category): ?>
                <span class="bg-green-500 text-white px-3 py-1 rounded-full text-sm flex items-center">
                    Category: <?= htmlspecialchars($current_category) ?> (ID: <?= intval($_GET['category_id']) ?>)
                    <a href="<?= 
                        http_build_query(array_filter([
                            'search_query' => $_GET['search_query'] ?? null,
                            'brand_id' => $_GET['brand_id'] ?? null
                        ]))
                    ?>" class="ml-2 text-white hover:text-red-200">×</a>
                </span>
            <?php endif; ?>
            
            <?php if ($current_brand): ?>
                <span class="bg-purple-500 text-white px-3 py-1 rounded-full text-sm flex items-center">
                    Brand: <?= htmlspecialchars($current_brand) ?>
                    <a href="<?= 
                        http_build_query(array_filter([
                            'search_query' => $_GET['search_query'] ?? null,
                            'category_id' => $_GET['category_id'] ?? null
                        ]))
                    ?>" class="ml-2 text-white hover:text-red-200">×</a>
                </span>
            <?php endif; ?>
            
            <a href="index.php" class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-1 rounded-full text-sm transition-colors duration-200">
                Clear All
            </a>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Debug Information (remove after fixing) -->
<?php if (isset($_GET['debug']) && $_GET['debug'] == '1'): ?>
<div class="container mx-auto my-4 p-4">
    <div class="bg-yellow-100 dark:bg-yellow-900 border border-yellow-300 dark:border-yellow-700 rounded-lg p-4">
        <h3 class="font-bold text-yellow-800 dark:text-yellow-200 mb-2">Debug Information:</h3>
        <div class="text-sm text-yellow-700 dark:text-yellow-300 space-y-1">
            <p><strong>SQL Query:</strong> <?= htmlspecialchars($get_products) ?></p>
            <p><strong>Results Count:</strong> <?= mysqli_num_rows($run_products) ?></p>
            <?php if (isset($_GET['category_id'])): ?>
                <p><strong>Category ID from URL:</strong> <?= intval($_GET['category_id']) ?></p>
                <p><strong>Category Name Found:</strong> <?= $current_category ? htmlspecialchars($current_category) : 'Not found' ?></p>
            <?php endif; ?>
            
            <!-- Show sample product data -->
            <?php 
            $sample_product = $conn->query("SELECT id, name, category, category_id FROM products LIMIT 1");
            if ($sample_product && $sample_product->num_rows > 0):
                $sample = $sample_product->fetch_assoc();
            ?>
                <p><strong>Sample Product Fields:</strong></p>
                <ul class="ml-4">
                    <li>ID: <?= $sample['id'] ?? 'N/A' ?></li>
                    <li>Name: <?= htmlspecialchars($sample['name'] ?? 'N/A') ?></li>
                    <li>Category field: <?= $sample['category'] ?? 'N/A' ?></li>
                    <li>Category_id field: <?= $sample['category_id'] ?? 'N/A' ?></li>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="container mx-auto my-6 p-4 flex-grow">
    <div class="flex flex-col lg:flex-row gap-6">
        <div class="lg:w-3/4">
            <?php if (mysqli_num_rows($run_products) > 0): ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php while ($row = mysqli_fetch_assoc($run_products)): ?>
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden transform transition-transform duration-300 hover:scale-105">
                            <img src="<?= htmlspecialchars($row['image']) ?>" class="w-full h-48 object-cover" alt="<?= htmlspecialchars($row['name']) ?>">
                            <div class="p-4">
                                <h5 class="text-xl font-semibold mb-2 dark:text-gray-50"><?= htmlspecialchars($row['name']) ?></h5>
                                <p class="text-gray-600 dark:text-gray-300 text-sm mb-3 line-clamp-3"><?= htmlspecialchars($row['description']) ?></p>
                                <p class="text-blue-600 dark:text-blue-400 text-xl font-bold mb-4">$<?= number_format($row['price'], 2) ?></p>
                                <?php if (isset($_SESSION['username'])): ?>
                                    <form action="index.php" method="POST" class="flex items-center space-x-2">
                                        <input type="hidden" name="product_id" value="<?= $row['id'] ?>">
                                        <input type="number" name="quantity" value="1" min="1" class="w-20 p-2 border border-gray-300 dark:border-gray-600 rounded-md bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-yellow-500">
                                        <button type="submit" name="add_to_cart" class="flex-grow bg-yellow-500 hover:bg-yellow-600 text-gray-900 font-bold py-2 px-4 rounded-md transition-colors duration-200">🛒 Add to Cart</button>
                                    </form>
                                <?php else: ?>
                                    <a href="login.php" class="block w-full text-center bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded-md transition-colors duration-200">Login to Add</a>
                                <?php endif; ?>
                                <a href="product.php" class="block w-full text-center mt-3 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 font-bold py-2 px-4 rounded-md transition-colors duration-200">View More</a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-12">
                    <div class="text-gray-500 dark:text-gray-400 text-6xl mb-4">🔍</div>
                    <h3 class="text-2xl font-bold text-gray-700 dark:text-gray-300 mb-2">No Products Found</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">We couldn't find any products matching your criteria.</p>
                    <a href="index.php" class="bg-yellow-500 hover:bg-yellow-600 text-gray-900 font-bold py-2 px-4 rounded-md transition-colors duration-200">
                        View All Products
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <div class="lg:w-1/4 bg-gray-800 dark:bg-gray-950 p-6 rounded-lg shadow-lg h-fit">
            <div class="bg-yellow-500 text-gray-900 font-bold text-lg p-3 rounded-md mb-4 text-center">Delivery Brands</div>
            <div class="space-y-3">
                <a href="index.php<?= 
                    http_build_query(array_filter([
                        'search_query' => $_GET['search_query'] ?? null,
                        'category_id' => $_GET['category_id'] ?? null
                    ])) ? '?' . http_build_query(array_filter([
                        'search_query' => $_GET['search_query'] ?? null,
                        'category_id' => $_GET['category_id'] ?? null
                    ])) : ''
                ?>" class="block w-full text-left <?= !isset($_GET['brand_id']) ? 'bg-yellow-500 text-gray-900' : 'bg-gray-700 hover:bg-yellow-500 text-white hover:text-gray-900' ?> dark:bg-gray-800 dark:hover:bg-yellow-600 font-semibold py-2 px-4 rounded-md transition-colors duration-200">
                    All Brands
                </a>
                <?php
                $brands = $conn->query("SELECT * FROM brands ORDER BY brand_name");
                while ($row = $brands->fetch_assoc()) {
                    $is_active = isset($_GET['brand_id']) && $_GET['brand_id'] == $row['brand_id'];
                    $brand_url = http_build_query(array_filter([
                        'search_query' => $_GET['search_query'] ?? null,
                        'category_id' => $_GET['category_id'] ?? null,
                        'brand_id' => $row['brand_id']
                    ]));
                    
                    echo '<a href="index.php?' . $brand_url . '" class="block w-full text-left ' . 
                         ($is_active ? 'bg-yellow-500 text-gray-900' : 'bg-gray-700 hover:bg-yellow-500 text-white hover:text-gray-900') . 
                         ' dark:bg-gray-800 dark:hover:bg-yellow-600 font-semibold py-2 px-4 rounded-md transition-colors duration-200">' . 
                         htmlspecialchars($row['brand_name']) . '</a>';
                }
                ?>
            </div>
            
            <div class="bg-yellow-500 text-gray-900 font-bold text-lg p-3 rounded-md mt-6 mb-4 text-center">Categories</div>
            <div class="space-y-3">
                <a href="index.php<?= 
                    http_build_query(array_filter([
                        'search_query' => $_GET['search_query'] ?? null,
                        'brand_id' => $_GET['brand_id'] ?? null
                    ])) ? '?' . http_build_query(array_filter([
                        'search_query' => $_GET['search_query'] ?? null,
                        'brand_id' => $_GET['brand_id'] ?? null
                    ])) : ''
                ?>" class="block w-full text-left <?= !isset($_GET['category_id']) ? 'bg-yellow-500 text-gray-900' : 'bg-gray-700 hover:bg-yellow-500 text-white hover:text-gray-900' ?> dark:bg-gray-800 dark:hover:bg-yellow-600 font-semibold py-2 px-4 rounded-md transition-colors duration-200">
                    All Categories
                </a>
                <?php
                $categories = $conn->query("SELECT * FROM categories ORDER BY category_name");
                while ($row = $categories->fetch_assoc()) {
                    $is_active = isset($_GET['category_id']) && $_GET['category_id'] == $row['category_id'];
                    $category_url = http_build_query(array_filter([
                        'search_query' => $_GET['search_query'] ?? null,
                        'brand_id' => $_GET['brand_id'] ?? null,
                        'category_id' => $row['category_id']
                    ]));
                    
                    echo '<a href="index.php?' . $category_url . '" class="block w-full text-left ' . 
                         ($is_active ? 'bg-yellow-500 text-gray-900' : 'bg-gray-700 hover:bg-yellow-500 text-white hover:text-gray-900') . 
                         ' dark:bg-gray-800 dark:hover:bg-yellow-600 font-semibold py-2 px-4 rounded-md transition-colors duration-200">' . 
                         htmlspecialchars($row['category_name']) . '</a>';
                }
                ?>
            </div>
        </div>
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
    <p>&copy; 2025 Smart Shop. All rights reserved.</p>
</footer>

</body>
</html>