<?php
session_start();
require 'db_connection.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    if (!isset($_SESSION['username']) || empty($_SESSION['cart'])) {
        $_SESSION['error_message'] = "Your cart is empty or you need to login.";
        header('Location: index.php');
        exit();
    }

    $conn->begin_transaction();

    $stmt = $conn->prepare("INSERT INTO orders (username, product_name, quantity, total_price, status, order_date) VALUES (?, ?, ?, ?, 'Pending', NOW())");

    foreach ($_SESSION['cart'] as $item) {
        $stmt->bind_param("ssid", $_SESSION['username'], $item['name'], $item['quantity'], $item['price']);
        $stmt->execute();
    }

    $stmt->close();
    $conn->commit();

    unset($_SESSION['cart']);
    $_SESSION['success_message'] = "Order placed successfully!";
    header('Location: my_orders.php');
    exit();
} catch (Exception $e) {
    if ($conn->errno === 0) {
        $conn->rollback();
    }
    $_SESSION['error_message'] = "Error processing your order: " . $e->getMessage();
    header('Location: index.php');
    exit();
}
?>
