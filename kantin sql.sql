-- Database: kantin_sehat
CREATE DATABASE IF NOT EXISTS kantin_sehat;
USE kantin_sehat;

-- Tabel Users
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Products
CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama VARCHAR(100) NOT NULL,
    jenis VARCHAR(50) NOT NULL,
    harga INT NOT NULL,
    ukuran VARCHAR(50),
    stok INT DEFAULT 0
);

-- Tabel Transactions
CREATE TABLE transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    total INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Tabel Transaction Items
CREATE TABLE transaction_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    transaction_id INT,
    product_id INT,
    qty INT NOT NULL,
    subtotal INT NOT NULL,
    FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
);

-- Insert default user (admin/admin)
INSERT INTO users (username, password) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'); -- password: admin

-- Insert 5 produk spesifik
INSERT INTO products (nama, jenis, harga, ukuran, stok) VALUES
('Aqua', 'Minuman', 4000, '600 ml', 50),
('Milo UHT', 'Minuman', 6000, '180 ml', 30),
('Sari Roti Tawar Kupas', 'Makanan', 12000, '200 g', 20),
('Beng-Beng Wafer', 'Snack', 3500, '20 g', 40),
('Pop Mie Mini', 'Makanan', 7000, '75 g', 25);