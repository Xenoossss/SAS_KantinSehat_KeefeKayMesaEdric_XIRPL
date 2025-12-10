<?php
// config/functions.php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function redirect($url) {
    header("Location: " . $url);
    exit();
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function formatRupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}

function getProducts($conn) {
    $query = "SELECT * FROM products ORDER BY nama";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getProduct($conn, $id) {
    $query = "SELECT * FROM products WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(":id", $id);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function updateStock($conn, $product_id, $qty) {
    $query = "UPDATE products SET stok = stok - :qty WHERE id = :id AND stok >= :qty";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(":id", $product_id);
    $stmt->bindParam(":qty", $qty);
    return $stmt->execute();
}
?>