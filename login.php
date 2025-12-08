<?php
session_start();
$conn = new mysqli("localhost", "root", "", "my_store");

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $isAdminLogin = isset($_POST['admin_login']); // Check if admin login button clicked

    if ($isAdminLogin) {
        $stmt = $conn->prepare("SELECT * FROM admins WHERE username=?");
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username=?");
    }

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows == 1) {
        $row = $res->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['username'] = $username;
            $_SESSION['is_admin'] = $isAdminLogin ? true : false;
            header("Location: index.php");
            exit();
        } else {
            $error = "Invalid password";
        }
    } else {
        $error = $isAdminLogin ? "Admin not found" : "User not found";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en" class="light"> <head>
    <meta charset="UTF-8">
    <title>Login - SmartShop</title>
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
    <h3 class="text-3xl font-bold text-center mb-6 text-gray-800 dark:text-gray-100">Login</h3>

    <?php if (!empty($error)): ?>
        <div class="bg-red-100 dark:bg-red-900 border border-red-400 text-red-700 dark:text-red-200 px-4 py-3 rounded relative mb-4" role="alert">
            <strong class="font-bold">Error!</strong>
            <span class="block sm:inline"><?php echo $error; ?></span>
        </div>
    <?php endif; ?>

    <form method="post" class="space-y-5">
        <div>
            <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Username</label>
            <input type="text" name="username" id="username" class="mt-1 block w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-yellow-500 focus:border-yellow-500" required>
        </div>
        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Password</label>
            <input type="password" name="password" id="password" class="mt-1 block w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-yellow-500 focus:border-yellow-500" required>
        </div>

        <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-lg font-semibold text-gray-900 bg-yellow-500 hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 transition-colors duration-200">
            Login as User
        </button>

        <button type="submit" name="admin_login" value="1" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-lg font-semibold text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors duration-200 mt-3">
            Login as Admin
        </button>
    </form>

    <p class="text-center text-sm mt-6 text-gray-600 dark:text-gray-300">
        Don't have an account? <a href="register.php" class="text-yellow-500 hover:text-yellow-600 font-semibold transition-colors duration-200">Register</a><br>
        <a href="forgot_password.php" class="text-blue-500 hover:text-blue-600 font-semibold transition-colors duration-200 mt-2 inline-block">Forgot password?</a>
    </p>
</div>

</body>
</html>