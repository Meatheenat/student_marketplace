-- สร้าง Database
CREATE DATABASE IF NOT EXISTS student_market_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE student_market_db;

-- 1. ตาราง categories (หมวดหมู่สินค้า)
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

-- 2. ตาราง users (ผู้ใช้งาน: นักเรียน, ครู, แอดมิน)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20) UNIQUE NOT NULL,
    fullname VARCHAR(100) NOT NULL,
    class_room VARCHAR(20),
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('buyer', 'seller', 'admin') DEFAULT 'buyer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 3. ตาราง shops (หน้าร้านค้าของนักเรียน)
CREATE TABLE shops (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    shop_name VARCHAR(100) NOT NULL,
    description TEXT,
    contact_line VARCHAR(50),
    contact_ig VARCHAR(50),
    status ENUM('pending', 'approved') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 4. ตาราง products (สินค้า)
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shop_id INT NOT NULL,
    category_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    image_url VARCHAR(255),
    description TEXT,
    product_status ENUM('in-stock', 'pre-order', 'out-of-stock') DEFAULT 'in-stock',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- --- Mock Data (ข้อมูลตัวอย่าง) ---

-- เพิ่มหมวดหมู่
INSERT INTO categories (category_name) VALUES ('อาหารและเครื่องดื่ม'), ('เครื่องเขียน'), ('เสื้อผ้า/แฟชั่น'), ('งานฝีมือ/DIY'), ('บริการ/ติวเตอร์');

-- เพิ่ม User (Password คือ '123456' ทั้งหมด - เข้ารหัสด้วย password_hash)
-- Admin
INSERT INTO users (student_id, fullname, class_room, email, password, role) 
VALUES ('ADMIN01', 'ครูสมชาย ใจดี', 'ห้องพักครู', 'somchai@school.ac.th', '$2y$10$8WkHptUqDkK.0tK8mS4mOuV.D5Vn8u7K1m1W8XnQ6f1A/jS6W0U7W', 'admin');

-- Seller
INSERT INTO users (student_id, fullname, class_room, email, password, role) 
VALUES ('STU001', 'นรินทร์ รักเรียน', 'ม.6/1', 'narin@school.ac.th', '$2y$10$8WkHptUqDkK.0tK8mS4mOuV.D5Vn8u7K1m1W8XnQ6f1A/jS6W0U7W', 'seller');

-- Buyer
INSERT INTO users (student_id, fullname, class_room, email, password, role) 
VALUES ('STU002', 'พรทิพย์ เรียนดี', 'ม.5/2', 'porntip@school.ac.th', '$2y$10$8WkHptUqDkK.0tK8mS4mOuV.D5Vn8u7K1m1W8XnQ6f1A/jS6W0U7W', 'buyer');

-- เพิ่มร้านค้า (สถานะ Approved เพื่อให้แสดงผลในหน้าแรกทันที)
INSERT INTO shops (user_id, shop_name, description, contact_line, contact_ig, status) 
VALUES (2, 'Narin Bakery', 'ขนมปังทำเอง สดใหม่ทุกวันจากบ้านนรินทร์', 'narin_line', 'narin_bakery_ig', 'approved');

-- เพิ่มสินค้าตัวอย่าง
INSERT INTO products (shop_id, category_id, title, price, image_url, description, product_status) 
VALUES (1, 1, 'คุกกี้เนยสด', 35.00, 'cookie.jpg', 'คุกกี้เนยแท้ หอมกรุ่น ไม่ใส่สารกันบูด', 'in-stock');

INSERT INTO products (shop_id, category_id, title, price, image_url, description, product_status) 
VALUES (1, 1, 'บราวนี่หนึบหนับ', 45.00, 'brownie.jpg', 'ช็อกโกแลตเข้มข้น หวานน้อย อร่อยมาก', 'pre-order');
-- อัปเดตรหัสผ่านทุกคนเป็น 123456 (Hash ที่ถูกต้องสำหรับ bcrypt)
UPDATE users SET password = '$2y$10$EygCRxXPUkMA1I/PlkY9HOuOCD119qYueDK1Or9dAF3J5290qciFe';
ALTER TABLE users ADD COLUMN department VARCHAR(100) AFTER class_room;
CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    otp VARCHAR(6) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
ALTER TABLE users 
ADD COLUMN profile_img VARCHAR(255) DEFAULT 'default_profile.png',
ADD COLUMN phone VARCHAR(15) NULL,
ADD COLUMN bio TEXT NULL;
-- 1. ตารางรีวิวสินค้า
CREATE TABLE reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 2. ตารางแท็ก (Master Tags)
CREATE TABLE tags (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tag_name VARCHAR(50) UNIQUE NOT NULL
);

-- 3. ตารางความสัมพันธ์สินค้ากับแท็ก (Junction Table)
CREATE TABLE product_tag_map (
    product_id INT NOT NULL,
    tag_id INT NOT NULL,
    PRIMARY KEY (product_id, tag_id),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
);

-- เพิ่มตัวอย่างแท็กเริ่มต้น
INSERT INTO tags (tag_name) VALUES ('มือหนึ่ง'), ('มือสอง'), ('ราคาประหยัด'), ('ของกิน'), ('งานฝีมือ');
CREATE TABLE wishlist (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_wish (user_id, product_id), -- ป้องกันการบันทึกซ้ำ
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);
-- เพิ่มคอลัมน์ status ในตาราง products
ALTER TABLE products 
ADD COLUMN status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending' AFTER category_id;
-- ถ้ายังไม่มี ให้รันคำสั่งนี้ครับ
ALTER TABLE shops 
ADD COLUMN line_user_id VARCHAR(255) NULL AFTER contact_ig;
-- เพิ่มคอลัมน์ line_user_id เข้าไปในตาราง users เพื่อรองรับ Admin
ALTER TABLE users ADD line_user_id VARCHAR(255) NULL AFTER role;
-- 1. เพิ่มสถานะการแบนในตาราง users และ shops
ALTER TABLE users ADD is_banned TINYINT(1) DEFAULT 0;
ALTER TABLE shops ADD is_blocked TINYINT(1) DEFAULT 0;

-- 2. สร้างตารางสำหรับการรีพอร์ต
CREATE TABLE reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    reporter_id INT NOT NULL, -- คนที่กดรีพอร์ต
    target_id INT NOT NULL,   -- ID ของสิ่งที่ถูกรีพอร์ต (comment_id, user_id, หรือ shop_id)
    target_type ENUM('comment', 'user', 'shop') NOT NULL, 
    reason TEXT NOT NULL,
    status ENUM('pending', 'resolved', 'dismissed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE admin_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT NOT NULL,      -- ID ของแอดมินที่ทำรายการ
    action_type VARCHAR(50),    -- ประเภทการกระทำ (เช่น ban_user, unblock_shop)
    target_type VARCHAR(20),    -- ประเภทเป้าหมาย (user หรือ shop)
    target_id INT NOT NULL,     -- ID ของคนที่โดนกระทำ
    details TEXT,               -- รายละเอียดเพิ่มเติม
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id)
);
-- 1. เพิ่มบทบาท teacher ในตาราง users
ALTER TABLE users MODIFY COLUMN role ENUM('buyer', 'seller', 'admin', 'teacher') DEFAULT 'buyer';

-- 2. ปรับแต่ง admin_logs ให้รองรับการเก็บเหตุผล (ถ้ามึงยังไม่มีคอลัมน์ details ให้เพิ่มตามนี้)
 ALTER TABLE admin_logs ADD COLUMN reason_text TEXT AFTER details;
 ALTER TABLE products ADD COLUMN is_deleted TINYINT(1) DEFAULT 0;
ALTER TABLE reviews ADD COLUMN is_deleted TINYINT(1) DEFAULT 0;
ALTER TABLE products ADD COLUMN deleted_by INT(11) NULL;
ALTER TABLE products ADD COLUMN deleted_at DATETIME NULL;
ALTER TABLE reviews ADD COLUMN deleted_by INT(11) NULL;
ALTER TABLE reviews ADD COLUMN deleted_at DATETIME NULL;
CREATE TABLE `messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `buyer_id` int(11) NOT NULL,
  `shop_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `status` enum('pending','preparing','completed','cancelled') DEFAULT 'pending',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
ALTER TABLE products ADD COLUMN views INT(11) DEFAULT 0;
CREATE TABLE `shop_reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `shop_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` int(1) NOT NULL,
  `comment` text DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
ALTER TABLE reports ADD COLUMN is_deleted TINYINT(1) DEFAULT 0;
-- สร้างตารางสำหรับเก็บรูปภาพสินค้าเพิ่มเติม
CREATE TABLE product_images (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    is_main TINYINT(1) DEFAULT 0, -- 1 คือรูปหลัก, 0 คือรูปเพิ่มเติม
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);
INSERT INTO categories (category_name) 
VALUES ('อื่นๆ (Others)');
-- ตารางสำหรับเก็บการติดตามร้านค้า
CREATE TABLE IF NOT EXISTS follows (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL, -- คนกดตาม
    shop_id INT NOT NULL, -- ร้านที่ถูกตาม
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_follow (user_id, shop_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
);
ALTER TABLE users ADD COLUMN otp_code VARCHAR(10) NULL AFTER password;
ALTER TABLE users ADD COLUMN is_verified TINYINT(1) NOT NULL DEFAULT 0 AFTER otp_code;
CREATE TABLE ban_appeals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    student_id VARCHAR(11) NOT NULL,
    reason TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
-- เปลี่ยนโครงสร้างคอลัมน์ status ให้รองรับค่าที่ส่งมาจาก PHP
ALTER TABLE shops MODIFY COLUMN status ENUM('pending', 'approved', 'blocked') DEFAULT 'pending';
ALTER TABLE orders ADD COLUMN meetup_location VARCHAR(255) NULL;
ALTER TABLE orders ADD COLUMN meetup_time VARCHAR(100) NULL;
ALTER TABLE orders ADD COLUMN buyer_note TEXT NULL;
ALTER TABLE products MODIFY COLUMN price DECIMAL(10,2) NOT NULL DEFAULT '0.00';
CREATE TABLE IF NOT EXISTS wtb_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    budget DECIMAL(10,2) DEFAULT NULL,
    status ENUM('active', 'closed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
ALTER TABLE wtb_posts 
ADD COLUMN category_id INT DEFAULT NULL AFTER user_id,
ADD COLUMN image_url VARCHAR(255) DEFAULT NULL AFTER description,
ADD COLUMN expected_condition VARCHAR(50) DEFAULT 'any' AFTER image_url;
ALTER TABLE wtb_posts MODIFY COLUMN status ENUM('pending', 'active', 'closed', 'rejected') DEFAULT 'pending';
ALTER TABLE wtb_posts ADD COLUMN is_deleted TINYINT(1) DEFAULT 0, ADD COLUMN deleted_at DATETIME NULL;



