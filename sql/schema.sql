CREATE DATABASE IF NOT EXISTS proweblanjut_crud_a12_2025_07414 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE proweblanjut_crud_a12_2025_07414;

CREATE TABLE IF NOT EXISTS items (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    item_name    VARCHAR(255) NOT NULL,
    quantity     INT UNSIGNED NOT NULL DEFAULT 0,
    price        DECIMAL(15,2) NOT NULL,
    entry_date   DATE NOT NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO items (item_name, quantity, price, entry_date) VALUES
('Wireless Mouse', 45, 129000, '2025-01-10'),
('Mechanical Keyboard', 3, 850000, '2025-02-14'),
('USB-C Hub', 20, 320000, '2025-03-01'),
('HDMI Cable 2m', 2, 75000, '2025-03-05'),
('Laptop Stand', 12, 275000, '2025-03-08');
