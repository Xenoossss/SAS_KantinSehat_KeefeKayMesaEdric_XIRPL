<?php
require_once 'config/database.php';
require_once 'config/functions.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$database = new Database();
$conn = $database->getConnection();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_to_cart':
                $product_id = $_POST['product_id'];
                addToCart($conn, $product_id);
                break;
                
            case 'update_cart':
                $product_id = $_POST['product_id'];
                $qty = $_POST['qty'];
                updateCart($product_id, $qty);
                break;
                
            case 'save_transaction':
                saveTransaction($conn);
                break;
                
            case 'remove_item':
                $product_id = $_POST['product_id'];
                removeFromCart($product_id);
                break;
        }
    }
    redirect('transaksi.php');
}

// Get cart items with product details
$cart_items = [];
$total_items = 0;
$total_price = 0;

if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $product_id => $qty) {
        $product = getProduct($conn, $product_id);
        if ($product) {
            $subtotal = $product['harga'] * $qty;
            $cart_items[] = [
                'id' => $product['id'],
                'nama' => $product['nama'],
                'harga' => $product['harga'],
                'qty' => $qty,
                'subtotal' => $subtotal,
                'ukuran' => $product['ukuran'],
                'stok' => $product['stok']
            ];
            $total_items += $qty;
            $total_price += $subtotal;
        }
    }
}

// Get transaction history
$history = [];
$query = "SELECT t.*, u.username FROM transactions t 
          LEFT JOIN users u ON t.user_id = u.id 
          ORDER BY t.created_at DESC LIMIT 5";
$stmt = $conn->prepare($query);
$stmt->execute();
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($transactions as $transaction) {
    $query = "SELECT ti.*, p.nama FROM transaction_items ti 
              LEFT JOIN products p ON ti.product_id = p.id 
              WHERE ti.transaction_id = :transaction_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(":transaction_id", $transaction['id']);
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $history[] = [
        'transaction' => $transaction,
        'items' => $items
    ];
}

// Cart functions
function addToCart($conn, $product_id) {
    $product = getProduct($conn, $product_id);
    if (!$product || $product['stok'] <= 0) {
        $_SESSION['error'] = "Produk tidak tersedia atau stok habis";
        return;
    }
    
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    if (isset($_SESSION['cart'][$product_id])) {
        if ($_SESSION['cart'][$product_id] < $product['stok']) {
            $_SESSION['cart'][$product_id]++;
        } else {
            $_SESSION['error'] = "Stok tidak mencukupi";
        }
    } else {
        $_SESSION['cart'][$product_id] = 1;
    }
}

function updateCart($product_id, $qty) {
    if ($qty <= 0) {
        unset($_SESSION['cart'][$product_id]);
    } else {
        $_SESSION['cart'][$product_id] = $qty;
    }
}

function removeFromCart($product_id) {
    unset($_SESSION['cart'][$product_id]);
}

function saveTransaction($conn) {
    if (empty($_SESSION['cart'])) {
        $_SESSION['error'] = "Keranjang belanja kosong";
        return;
    }
    
    try {
        $conn->beginTransaction();
        
        // Create transaction
        $query = "INSERT INTO transactions (user_id, total) VALUES (:user_id, 0)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(":user_id", $_SESSION['user_id']);
        $stmt->execute();
        $transaction_id = $conn->lastInsertId();
        
        $total = 0;
        
        // Add transaction items and update stock
        foreach ($_SESSION['cart'] as $product_id => $qty) {
            $product = getProduct($conn, $product_id);
            if (!$product || $product['stok'] < $qty) {
                throw new Exception("Stok " . $product['nama'] . " tidak mencukupi");
            }
            
            $subtotal = $product['harga'] * $qty;
            $total += $subtotal;
            
            // Insert transaction item
            $query = "INSERT INTO transaction_items (transaction_id, product_id, qty, subtotal) 
                      VALUES (:transaction_id, :product_id, :qty, :subtotal)";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(":transaction_id", $transaction_id);
            $stmt->bindParam(":product_id", $product_id);
            $stmt->bindParam(":qty", $qty);
            $stmt->bindParam(":subtotal", $subtotal);
            $stmt->execute();
            
            // Update stock
            updateStock($conn, $product_id, $qty);
        }
        
        // Update transaction total
        $query = "UPDATE transactions SET total = :total WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(":total", $total);
        $stmt->bindParam(":id", $transaction_id);
        $stmt->execute();
        
        $conn->commit();
        
        // Clear cart
        unset($_SESSION['cart']);
        $_SESSION['success'] = "Transaksi berhasil disimpan! Total: " . formatRupiah($total);
        
    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['error'] = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi - Kantin Sehat</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <div class="header-content">
            <h1>🛒 Transaksi Pembelian</h1>
            <div class="user-info">
                <span>Welcome, <?php echo $_SESSION['username']; ?></span>
                <a href="logout.php" class="btn-logout">Logout</a>
            </div>
        </div>
    </header>
    
    <main class="container">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <?php 
                echo $_SESSION['error']; 
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php 
                echo $_SESSION['success']; 
                unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>
        
        <div class="page-header">
            <h2>Keranjang Belanja</h2>
            <p>Atur jumlah dan simpan transaksi</p>
        </div>
        
        <div class="transaction-section">
            <div class="cart-items">
                <?php if (empty($cart_items)): ?>
                    <p class="empty-cart">Keranjang belanja kosong</p>
                <?php else: ?>
                    <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item">
                            <div class="cart-item-info">
                                <h4><?php echo $item['nama']; ?></h4>
                                <p><?php echo formatRupiah($item['harga']); ?> | <?php echo $item['ukuran']; ?></p>
                            </div>
                            <form method="POST" action="" class="quantity-controls">
                                <input type="hidden" name="action" value="update_cart">
                                <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" name="decrease" class="qty-btn" 
                                        onclick="this.form.qty.value=parseInt(this.form.qty.value)-1; return true;">-</button>
                                <input type="number" name="qty" class="quantity" value="<?php echo $item['qty']; ?>" 
                                       min="1" max="<?php echo $item['stok']; ?>" 
                                       onchange="this.form.submit()">
                                <button type="submit" name="increase" class="qty-btn" 
                                        onclick="this.form.qty.value=parseInt(this.form.qty.value)+1; return true;">+</button>
                            </form>
                            <div class="item-total"><?php echo formatRupiah($item['subtotal']); ?></div>
                            <form method="POST" action="" style="margin-left: 10px;">
                                <input type="hidden" name="action" value="remove_item">
                                <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" class="btn-remove">×</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="transaction-summary">
                <h3>Ringkasan Transaksi</h3>
                <div class="summary-item">
                    <span>Total Item:</span>
                    <span><?php echo $total_items; ?></span>
                </div>
                <div class="summary-item">
                    <span>Total Harga:</span>
                    <span><?php echo formatRupiah($total_price); ?></span>
                </div>
                
                <?php if (!empty($cart_items)): ?>
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="save_transaction">
                        <button type="submit" class="btn-primary">Simpan Transaksi</button>
                    </form>
                <?php endif; ?>
                
                <a href="menu.php" class="btn-secondary">Tambah Produk Lain</a>
            </div>
        </div>
        
        <div class="transaction-history">
            <h3>Riwayat Transaksi (5 Terakhir)</h3>
            <?php if (empty($history)): ?>
                <p class="empty-history">Belum ada transaksi</p>
            <?php else: ?>
                <?php foreach ($history as $record): ?>
                    <div class="history-item">
                        <div class="history-item-header">
                            <div class="history-date">
                                <?php echo date('d/m/Y H:i', strtotime($record['transaction']['created_at'])); ?>
                            </div>
                            <div class="history-total">
                                <?php echo formatRupiah($record['transaction']['total']); ?>
                            </div>
                        </div>
                        <div class="history-products">
                            <?php 
                            $items_text = [];
                            foreach ($record['items'] as $item) {
                                $items_text[] = $item['nama'] . " (" . $item['qty'] . " × " . formatRupiah($item['subtotal'] / $item['qty']) . ")";
                            }
                            echo implode(', ', $items_text);
                            ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>