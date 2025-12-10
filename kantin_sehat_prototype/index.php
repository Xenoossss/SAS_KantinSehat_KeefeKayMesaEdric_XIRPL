<?php
require_once 'config/database.php';
require_once 'config/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $conn = $database->getConnection();
    
    $username = sanitize($_POST['username']);
    $password = sanitize($_POST['password']);
    
    $query = "SELECT * FROM users WHERE username = :username";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(":username", $username);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Verify password (using password_verify for hashed passwords)
        if ($password === 'admin' && $username === 'admin') {
            // For demo, we'll accept plain password
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            redirect('menu.php');
        } else {
            $error = "Username atau password salah!";
        }
    } else {
        $error = "Username tidak ditemukan!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Kantin Sehat</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="logo">
                <h1>🍎 Kantin Sehat</h1>
                <p>Aplikasi Pembelian Makanan & Minuman</p>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="input-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Masukkan username" required>
                </div>
                
                <div class="input-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Masukkan password" required>
                </div>
                
                <button type="submit" class="btn-login">Login</button>
                
                <div class="login-info">
                    <p>🔐 Gunakan: <strong>username = admin</strong> | <strong>password = admin</strong></p>
                </div>
            </form>
        </div>
    </div>
</body>
</html>