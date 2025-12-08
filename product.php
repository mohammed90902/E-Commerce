<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'my_store');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Secure SQL query using prepared statements
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

// Check if product exists before proceeding
if (!$product) {
    // Close database connections
    $stmt->close();
    $conn->close();
    ?>
    <!DOCTYPE html>
    <html lang="en" class="light">
    <head>
        <meta charset="UTF-8">
        <title>Product Not Found - Smart Shop</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-gradient-to-tr from-gray-100 to-gray-200 text-gray-900 min-h-screen flex flex-col">
        <div class="flex-grow flex items-center justify-center">
            <div class="text-center">
                <p class="text-lg text-gray-600 mb-6">The product you're looking for doesn't exist or has been removed.</p>
                <a href="index.php" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-md transition-colors duration-200">
                    ← Back to Home
                </a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit; // Stop execution here
}

// Close database connections
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($product['name']) ?> - Product Details</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
        };
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        });
    </script>
    <style>
        .background-pattern {
            position: fixed;
            inset: 0;
            background-image: url('https://www.toptal.com/designers/subtlepatterns/patterns/memphis-mini.png');
            opacity: 0.05;
            pointer-events: none;
            z-index: -1;
        }
    </style>
</head>
<body class="bg-gradient-to-tr from-gray-100 to-gray-200 dark:from-gray-900 dark:to-gray-800 text-gray-900 dark:text-gray-100 min-h-screen flex flex-col font-sans transition-colors duration-300">
<div class="background-pattern"></div>

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

<div class="max-w-4xl mx-auto p-6">
    <a href="index.php" class="block w-full text-center mt-3 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 font-bold py-2 px-4 rounded-md transition-colors duration-200">
        ← Back to Home
    </a>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md mt-6 p-6 flex flex-col md:flex-row gap-6 items-start">
        <div class="w-full md:w-1/2">
            <img src="<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="w-full h-auto max-h-96 object-contain rounded-lg border dark:border-gray-700 shadow">
        </div>
        <div class="w-full md:w-1/2 flex flex-col justify-between space-y-4">
            <div>
                <h1 class="text-3xl font-bold mb-2"><?= htmlspecialchars($product['name']) ?></h1>
                <p class="text-gray-600 dark:text-gray-300 mb-4"><?= htmlspecialchars($product['description']) ?></p>
                <p class="text-xl text-blue-600 dark:text-blue-400 font-bold">$<?= number_format($product['price'], 2) ?></p>
            </div>
            <?php if (isset($_SESSION['username'])): ?>
                <form action="index.php" method="POST" class="flex items-center gap-4">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                    <input type="number" name="quantity" value="1" min="1" max="999" class="w-20 p-2 border border-gray-300 dark:border-gray-600 rounded-md bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                    <button type="submit" name="add_to_cart" class="bg-yellow-500 hover:bg-yellow-600 text-gray-900 font-bold py-2 px-4 rounded-md transition-colors duration-200">🛒 Add to Cart</button>
                </form>
            <?php else: ?>
                <a href="login.php" class="inline-block w-fit bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-6 rounded-md transition">Login to Add</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<footer class="bg-gray-800 dark:bg-gray-950 text-white p-6 text-center mt-auto shadow-inner">
    <p>&copy; 2025 Smart Shop. All rights reserved.</p>
</footer>

<script>
    function confirmLogout() {
        if (confirm('Are you sure you want to logout?')) {
            window.location.href = 'index.php?logout=true';
        }
    }

    // Dark mode toggle functionality
    const themeToggleBtn = document.getElementById('theme-toggle');
    if (themeToggleBtn) {
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

    if (accountDropdownToggle && accountDropdownMenu) {
        accountDropdownToggle.addEventListener('click', (event) => {
            event.stopPropagation();
            const isExpanded = accountDropdownToggle.getAttribute('aria-expanded') === 'true';
            accountDropdownToggle.setAttribute('aria-expanded', !isExpanded);
            accountDropdownMenu.classList.toggle('hidden');
        });

        document.addEventListener('click', (event) => {
            if (!accountDropdownToggle.contains(event.target) && !accountDropdownMenu.contains(event.target)) {
                accountDropdownMenu.classList.add('hidden');
                accountDropdownToggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    // Form validation for quantity input
    const quantityInput = document.querySelector('input[name="quantity"]');
    if (quantityInput) {
        quantityInput.addEventListener('input', function() {
            const value = parseInt(this.value);
            if (value < 1) {
                this.value = 1;
            } else if (value > 999) {
                this.value = 999;
            }
        });
    }
</script>

</body>
</html>