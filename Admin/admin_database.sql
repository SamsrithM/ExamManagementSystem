CREATE DATABASE IF NOT EXISTS admin_data;
USE admin_data;

CREATE TABLE IF NOT EXISTS admin (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    admin_username VARCHAR(60) NOT NULL UNIQUE,
    admin_password VARCHAR(255) NOT NULL
);

-- 5 sample admin usernames and dummy passwords (for local testing)
INSERT INTO admin (admin_username, admin_password) VALUES
('admin_bharat', 'bharat@123'),
('admin_deepa', 'deepa2025'),
('admin_rajesh', 'rajesh#456'),
('admin_neha', 'neha_pass'),
('admin_prasad', 'prasad789');