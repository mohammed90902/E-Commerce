<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "my_store");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$username = $_SESSION['username'];
$stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$update_message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_username = $_POST["username"];
    $email = $_POST["email"];
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT);
    $address = $_POST["address"];
    $contact = $_POST["contact"];

    $stmt = $conn->prepare("UPDATE users SET username=?, email=?, password=?, address=?, contact=? WHERE username=?");
    $stmt->bind_param("ssssss", $new_username, $email, $password, $address, $contact, $username);

    if ($stmt->execute()) {
        $_SESSION['username'] = $new_username;
        $update_message = "Account updated successfully!";
        $username = $new_username;
    } else {
        $update_message = "Update failed.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Account</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .container { max-width: 600px; margin-top: 50px; }
    </style>
</head>
<body>

<div class="container">
    <h2 class="text-center">Manage Account</h2>

    <?php if ($update_message): ?>
        <div class="alert alert-info text-center"><?php echo $update_message; ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="mb-3">
            <label>Username</label>
            <input type="text" name="username" class="form-control" required value="<?php echo htmlspecialchars($user['username']); ?>">
        </div>
        <div class="mb-3">
            <label>Email</label>
            <input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($user['email']); ?>">
        </div>
        <div class="mb-3">
            <label>Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Address</label>
            <input type="text" name="address" class="form-control" required value="<?php echo htmlspecialchars($user['address']); ?>">
        </div>
        <div class="mb-3">
            <label>Contact Number</label>
            <input type="text" name="contact" class="form-control" required value="<?php echo htmlspecialchars($user['contact']); ?>">
        </div>
        <button type="submit" class="btn btn-primary w-100">Update</button>
    </form>

    <div class="text-center mt-3">
        <a href="index.php">← Back to Home</a>
    </div>
</div>

</body>
</html>
