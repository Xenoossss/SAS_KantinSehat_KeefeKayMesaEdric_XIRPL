<?php
require_once 'config/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('index.php');
}

$conn = getConnection();
$products = getProducts($conn);
$restock_history = getRestockHistory($conn, 10);

// Handle restock
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restock'])) {
    $product_id = $_POST['product_id'];
    $qty = intval($_POST['qty']);
    $notes = sanitize($_POST['notes']);
    
    if ($qty > 0) {
        if (restockProduct($conn, $product_id, $qty, $notes)) {
            $_SESSION['success'] = "✅ Restock berhasil!";
            redirect('admin.php');
        } else {
            $_SESSION['error'] = "❌ Gagal melakukan restock!";
        }
    } else {
        $_SESSION['error'] = "❌ Jumlah restock harus lebih dari 0!";
    }
}

// Handle add new product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $data = [
        'nama' => sanitize($_POST['nama']),
        'jenis' => sanitize($_POST['jenis']),
        'harga' => intval($_POST['harga']),
        'ukuran' => sanitize($_POST['ukuran']),
        'stok' => intval($_POST['stok'])
    ];
    
    if (addNewProduct($conn, $data)) {
        $_SESSION['success'] = "✅ Produk baru berhasil ditambahkan!";
        redirect('admin.php');
    } else {
        $_SESSION['error'] = "❌ Gagal menambahkan produk!";
    }
}

// Handle delete product
if (isset($_GET['delete'])) {
    $product_id = $_GET['delete'];
    if (deleteProduct($conn, $product_id)) {
        $_SESSION['success'] = "✅ Produk berhasil dihapus!";
    } else {
        $_SESSION['error'] = "❌ Produk tidak dapat dihapus karena sudah ada dalam transaksi!";
    }
    redirect('admin.php');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Kantin Sehat</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Admin Page Specific Styles */
        .admin-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }
        
        @media (max-width: 992px) {
            .admin-section {
                grid-template-columns: 1fr;
            }
        }
        
        .admin-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            border: 1px solid #f1f2f6;
        }
        
        .product-list-admin {
            max-height: 500px;
            overflow-y: auto;
            padding-right: 10px;
        }
        
        /* Scrollbar styling */
        .product-list-admin::-webkit-scrollbar {
            width: 6px;
        }
        
        .product-list-admin::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        .product-list-admin::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 10px;
        }
        
        .product-list-admin::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
        
        /* Product item in admin */
        .product-item-admin {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #eee;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 10px;
            transition: all 0.2s;
        }
        
        .product-item-admin:hover {
            background: #e8f4fd;
            transform: translateX(5px);
        }
        
        .product-info-admin h4 {
            margin-bottom: 8px;
            color: #2c3e50;
            font-size: 16px;
            font-weight: 600;
        }
        
        .product-info-admin p {
            color: #7f8c8d;
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        /* Stock indicator */
        .stock-indicator {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            margin-left: 10px;
            border: 1px solid;
        }
        
        .stock-low {
            background: #ffcccc !important;
            color: #990000 !important;
            border-color: #ff6666 !important;
        }
        
        .stock-medium {
            background: #fff0cc !important;
            color: #996600 !important;
            border-color: #ffcc66 !important;
        }
        
        .stock-high {
            background: #ccffcc !important;
            color: #006600 !important;
            border-color: #66cc66 !important;
        }
        
        /* Admin buttons */
        .admin-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-small {
            padding: 6px 15px !important;
            font-size: 13px !important;
            border-radius: 6px !important;
            border: none !important;
            cursor: pointer !important;
            font-weight: 600 !important;
            transition: all 0.2s !important;
        }
        
        .btn-restock {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%) !important;
            color: white !important;
            box-shadow: 0 3px 10px rgba(52, 152, 219, 0.2) !important;
        }
        
        .btn-restock:hover {
            background: linear-gradient(135deg, #2980b9 0%, #2471a3 100%) !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3) !important;
        }
        
        .btn-delete {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%) !important;
            color: white !important;
            box-shadow: 0 3px 10px rgba(231, 76, 60, 0.2) !important;
        }
        
        .btn-delete:hover {
            background: linear-gradient(135deg, #c0392b 0%, #a93226 100%) !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3) !important;
        }
        
        /* Admin header */
        .admin-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f1f2f6;
        }
        
        .admin-card-header h3 {
            color: #2c3e50;
            font-size: 22px;
            font-weight: 700;
        }
        
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 15px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f1f2f6;
        }
        
        .close-modal {
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: #7f8c8d;
            transition: color 0.3s;
        }
        
        .close-modal:hover {
            color: #34495e;
        }
        
        /* Restock form */
        .restock-form .input-group {
            margin-bottom: 20px;
        }
        
        .restock-form label {
            display: block;
            margin-bottom: 8px;
            color: #34495e;
            font-weight: 600;
            font-size: 14px;
        }
        
        .restock-form input,
        .restock-form textarea,
        .restock-form select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #dfe6e9;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
            background: #f8f9fa;
        }
        
        .restock-form input:focus,
        .restock-form textarea:focus,
        .restock-form select:focus {
            outline: none;
            border-color: #3498db;
            background: white;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        
        .restock-form textarea {
            height: 100px;
            resize: vertical;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        /* Restock history */
        .history-item-restock {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            border-left: 4px solid #3498db;
            transition: all 0.2s;
        }
        
        .history-item-restock:hover {
            transform: translateX(5px);
            background: #e8f4fd;
        }
        
        .restock-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            align-items: center;
        }
        
        .restock-qty {
            font-weight: 700;
            color: #27ae60;
            font-size: 16px;
            background: rgba(39, 174, 96, 0.1);
            padding: 2px 10px;
            border-radius: 15px;
        }
        
        .restock-notes {
            font-size: 13px;
            color: #7f8c8d;
            font-style: italic;
            margin-top: 5px;
            padding-left: 10px;
            border-left: 2px solid #ecf0f1;
        }
        
        /* Responsive admin */
        @media (max-width: 768px) {
            .admin-card {
                padding: 20px;
            }
            
            .product-item-admin {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .admin-actions {
                width: 100%;
                justify-content: space-between;
            }
            
            .modal-content {
                padding: 20px;
                width: 95%;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <h1>⚙️ Admin Panel</h1>
            <div class="user-info">
                <span style="background: #2c3e50; padding: 8px 15px; border-radius: 20px;">
                    ⚙️ Admin: <?php echo $_SESSION['username']; ?>
                </span>
                
                <a href="menu.php" class="btn-menu" style="margin-right: 5px;">📋 Menu</a>
                <a href="transaksi.php" class="btn-cart" style="margin-right: 5px;">🛒 Transaksi</a>
                <a href="logout.php" class="btn-logout">🚪 Logout</a>
            </div>
        </div>
    </header>
    
    <main class="container">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <div class="page-header">
            <h2>Manajemen Stok Produk</h2>
            <p>Kelola produk dan lakukan restock</p>
        </div>
        
        <div class="admin-section">
            <!-- Product List -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3>📦 Daftar Produk</h3>
                    <button onclick="openAddProductModal()" class="btn-primary" style="padding: 10px 20px; font-size: 14px;">
                        ➕ Tambah Produk Baru
                    </button>
                </div>
                <div class="product-list-admin">
                    <?php foreach ($products as $product): 
                        // Determine stock status
                        $stock_class = '';
                        if ($product['stok'] < 10) {
                            $stock_class = 'stock-low';
                        } elseif ($product['stok'] < 20) {
                            $stock_class = 'stock-medium';
                        } else {
                            $stock_class = 'stock-high';
                        }
                        
                        // Determine product type color
                        $type_class = '';
                        switch(strtolower($product['jenis'])) {
                            case 'minuman': $type_class = 'product-type-minuman'; break;
                            case 'makanan': $type_class = 'product-type-makanan'; break;
                            case 'snack': $type_class = 'product-type-snack'; break;
                            default: $type_class = 'product-type-lainnya'; break;
                        }
                    ?>
                        <div class="product-item-admin">
                            <div class="product-info-admin">
                                <h4>
                                    <?php echo $product['nama']; ?>
                                    <span class="stock-indicator <?php echo $stock_class; ?>">
                                        Stok: <?php echo $product['stok']; ?>
                                    </span>
                                </h4>
                                <p>
                                    <span class="product-type <?php echo $type_class; ?>" style="display: inline-block; padding: 2px 8px; margin-right: 10px;">
                                        <?php echo $product['jenis']; ?>
                                    </span>
                                    <?php echo formatRupiah($product['harga']); ?> | 
                                    <?php echo $product['ukuran']; ?>
                                </p>
                            </div>
                            <div class="admin-actions">
                                <button onclick="openRestockModal(<?php echo $product['id']; ?>, '<?php echo $product['nama']; ?>')" 
                                        class="btn-small btn-restock">
                                    🔄 Restock
                                </button>
                                <a href="?delete=<?php echo $product['id']; ?>" 
                                   class="btn-small btn-delete"
                                   onclick="return confirm('Yakin ingin menghapus produk <?php echo $product['nama']; ?>?')">
                                    🗑️ Hapus
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Restock History -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3>📜 Riwayat Restock</h3>
                    <span style="color: #7f8c8d; font-size: 14px;">
                        Total: <?php echo count($restock_history); ?> restock
                    </span>
                </div>
                <div style="max-height: 400px; overflow-y: auto; padding-right: 5px;">
                    <?php if (empty($restock_history)): ?>
                        <div class="empty-history" style="text-align: center; padding: 40px 20px; color: #95a5a6;">
                            <div style="font-size: 40px; margin-bottom: 15px;">📭</div>
                            <p>Belum ada riwayat restock</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($restock_history as $restock): ?>
                            <div class="history-item-restock">
                                <div class="restock-info">
                                    <strong style="color: #2c3e50;"><?php echo $restock['nama']; ?></strong>
                                    <span class="restock-qty">+<?php echo $restock['qty']; ?> unit</span>
                                </div>
                                <div class="restock-info">
                                    <small style="color: #7f8c8d;">
                                        📅 <?php echo date('d/m/Y H:i', strtotime($restock['date'])); ?>
                                    </small>
                                </div>
                                <?php if (!empty($restock['notes'])): ?>
                                    <div class="restock-notes">
                                        📝 <?php echo $restock['notes']; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Navigation -->
        <div class="navigation">
            <a href="transaksi.php" class="btn-primary" style="padding: 15px 30px; font-size: 16px;">
                🛒 Kembali ke Transaksi
            </a>
            <a href="menu.php" class="btn-secondary" style="padding: 15px 30px; font-size: 16px;">
                📋 Lihat Menu
            </a>
        </div>
    </main>
    
    <!-- Restock Modal -->
    <div id="restockModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>🔄 Restock Produk</h3>
                <button class="close-modal" onclick="closeRestockModal()">&times;</button>
            </div>
            <form method="POST" action="" class="restock-form">
                <input type="hidden" name="restock" value="1">
                <input type="hidden" id="restock_product_id" name="product_id">
                
                <div class="input-group">
                    <label>Nama Produk</label>
                    <input type="text" id="restock_product_name" readonly style="background: #ecf0f1;">
                </div>
                
                <div class="input-group">
                    <label>Jumlah Restock</label>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <button type="button" onclick="adjustQty(-10)" style="padding: 8px 15px; background: #e74c3c; color: white; border: none; border-radius: 5px; cursor: pointer;">-10</button>
                        <button type="button" onclick="adjustQty(-5)" style="padding: 8px 15px; background: #f39c12; color: white; border: none; border-radius: 5px; cursor: pointer;">-5</button>
                        <input type="number" id="restock_qty" name="qty" min="1" max="1000" value="10" required style="flex: 1;">
                        <button type="button" onclick="adjustQty(5)" style="padding: 8px 15px; background: #2ecc71; color: white; border: none; border-radius: 5px; cursor: pointer;">+5</button>
                        <button type="button" onclick="adjustQty(10)" style="padding: 8px 15px; background: #27ae60; color: white; border: none; border-radius: 5px; cursor: pointer;">+10</button>
                    </div>
                </div>
                
                <div class="input-group">
                    <label>Catatan (Opsional)</label>
                    <textarea name="notes" placeholder="Contoh: Restock rutin, pesanan khusus, stok awal, dll."></textarea>
                </div>
                
                <button type="submit" class="btn-primary" style="width: 100%; padding: 14px; font-size: 16px;">
                    ✅ Simpan Restock
                </button>
            </form>
        </div>
    </div>
    
    <!-- Add Product Modal -->
    <div id="addProductModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>➕ Tambah Produk Baru</h3>
                <button class="close-modal" onclick="closeAddProductModal()">&times;</button>
            </div>
            <form method="POST" action="" class="restock-form">
                <input type="hidden" name="add_product" value="1">
                
                <div class="input-group">
                    <label>Nama Produk</label>
                    <input type="text" name="nama" placeholder="Contoh: Aqua, Milo UHT, dll." required>
                </div>
                
                <div class="input-group">
                    <label>Jenis</label>
                    <select name="jenis" required>
                        <option value="">-- Pilih Jenis --</option>
                        <option value="Minuman">🥤 Minuman</option>
                        <option value="Makanan">🍽️ Makanan</option>
                        <option value="Snack">🍫 Snack</option>
                        <option value="Lainnya">📦 Lainnya</option>
                    </select>
                </div>
                
                <div class="input-group">
                    <label>Harga (Rp)</label>
                    <input type="number" name="harga" min="100" max="1000000" placeholder="4000" required>
                </div>
                
                <div class="input-group">
                    <label>Ukuran</label>
                    <input type="text" name="ukuran" placeholder="Contoh: 600 ml, 200 g, 1 pcs" required>
                </div>
                
                <div class="input-group">
                    <label>Stok Awal</label>
                    <input type="number" name="stok" min="0" max="1000" value="10" required>
                </div>
                
                <button type="submit" class="btn-primary" style="width: 100%; padding: 14px; font-size: 16px;">
                    ✅ Tambah Produk
                </button>
            </form>
        </div>
    </div>
    
    <script>
        // Modal functions
        function openRestockModal(productId, productName) {
            document.getElementById('restockModal').style.display = 'flex';
            document.getElementById('restock_product_id').value = productId;
            document.getElementById('restock_product_name').value = productName;
            document.getElementById('restock_qty').focus();
        }
        
        function closeRestockModal() {
            document.getElementById('restockModal').style.display = 'none';
        }
        
        function openAddProductModal() {
            document.getElementById('addProductModal').style.display = 'flex';
        }
        
        function closeAddProductModal() {
            document.getElementById('addProductModal').style.display = 'none';
        }
        
        // Quantity adjustment
        function adjustQty(change) {
            const qtyInput = document.getElementById('restock_qty');
            let currentValue = parseInt(qtyInput.value) || 0;
            let newValue = currentValue + change;
            
            if (newValue < 1) newValue = 1;
            if (newValue > 1000) newValue = 1000;
            
            qtyInput.value = newValue;
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const restockModal = document.getElementById('restockModal');
            const addProductModal = document.getElementById('addProductModal');
            
            if (event.target === restockModal) {
                closeRestockModal();
            }
            if (event.target === addProductModal) {
                closeAddProductModal();
            }
        }
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeRestockModal();
                closeAddProductModal();
            }
        });
    </script>
</body>
</html>