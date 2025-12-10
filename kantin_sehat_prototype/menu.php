<?php
require_once 'config/database.php';
require_once 'config/functions.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$database = new Database();
$conn = $database->getConnection();
$products = getProducts($conn);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Produk - Kantin Sehat</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <div class="header-content">
            <h1>🍽️ Menu Kantin Sehat</h1>
            <div class="user-info">
                <span>Welcome, <?php echo $_SESSION['username']; ?></span>
                <a href="logout.php" class="btn-logout">Logout</a>
            </div>
        </div>
    </header>
    
    <main class="container">
        <div class="page-header">
            <h2>Daftar Produk Tersedia</h2>
            <p>Pilih produk yang ingin dibeli</p>
        </div>
        
        <div class="products-grid">
            <?php foreach ($products as $product): ?>
                <div class="product-card">
                    <div class="product-header">
                        <div class="product-name"><?php echo $product['nama']; ?></div>
                        <div class="product-type"><?php echo $product['jenis']; ?></div>
                    </div>
                    <div class="product-price"><?php echo formatRupiah($product['harga']); ?></div>
                    <div class="product-size">Ukuran: <?php echo $product['ukuran']; ?></div>
                    <div class="product-stock">Stok: <?php echo $product['stok']; ?> unit</div>
                    
                    <form method="POST" action="transaksi.php" style="width: 100%;">
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        <input type="hidden" name="action" value="add_to_cart">
                        <button type="submit" class="btn-select">Pilih Produk</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="navigation">
            <a href="transaksi.php" class="btn-primary">Lihat Keranjang</a>
        </div>
    </main>
</body>
</html>