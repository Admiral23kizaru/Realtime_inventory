-- 1. Create Database
CREATE DATABASE IF NOT EXISTS inventory_system;
USE inventory_system;

-- 2. Users Table (First because it's referenced by other tables)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE,
    full_name VARCHAR(255),
    role ENUM('admin', 'manager', 'staff') DEFAULT 'staff',
    is_active BOOLEAN DEFAULT TRUE,
    last_login DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_role (role),
    INDEX idx_email (email)
);

-- 3. Products Table
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    sku VARCHAR(50) UNIQUE,
    price DECIMAL(10,2) NOT NULL,
    cost_price DECIMAL(10,2),
    category VARCHAR(100),
    supplier VARCHAR(255),
    min_stock_level INT DEFAULT 10,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_supplier (supplier)
);

-- 4. Customers Table
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(50),
    email VARCHAR(100),
    address TEXT,
    city VARCHAR(100),
    state VARCHAR(100),
    postal_code VARCHAR(20),
    country VARCHAR(100),
    customer_type ENUM('retail', 'wholesale', 'distributor') DEFAULT 'retail',
    credit_limit DECIMAL(10,2) DEFAULT 0.00,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_customer_type (customer_type),
    INDEX idx_email (email)
);

-- 5. Stock Levels Table
CREATE TABLE stock_levels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    last_restock_date DATETIME,
    last_restock_quantity INT,
    location VARCHAR(100),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_location (location)
);

-- 6. Sales Table
CREATE TABLE sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(50) UNIQUE,
    product_id INT NOT NULL,
    customer_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    discount DECIMAL(10,2) DEFAULT 0.00,
    tax DECIMAL(10,2) DEFAULT 0.00,
    total_amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash', 'credit_card', 'bank_transfer', 'other') DEFAULT 'cash',
    payment_status ENUM('pending', 'paid', 'partial', 'cancelled') DEFAULT 'pending',
    sale_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    created_by INT,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_invoice (invoice_number),
    INDEX idx_sale_date (sale_date),
    INDEX idx_payment_status (payment_status)
);

-- 7. Purchase Orders Table
CREATE TABLE purchase_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    po_number VARCHAR(50) UNIQUE,
    supplier VARCHAR(255) NOT NULL,
    order_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    expected_delivery_date DATE,
    status ENUM('pending', 'ordered', 'received', 'cancelled') DEFAULT 'pending',
    total_amount DECIMAL(10,2) NOT NULL,
    notes TEXT,
    created_by INT,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_po_number (po_number),
    INDEX idx_status (status)
);

-- 8. Purchase Order Items Table
CREATE TABLE purchase_order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    po_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_cost DECIMAL(10,2) NOT NULL,
    total_cost DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (po_id) REFERENCES purchase_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- 9. Activity Log Table
CREATE TABLE activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(100),
    record_id INT,
    details TEXT,
    ip_address VARCHAR(45),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
);

-- 10. Trigger: Update stock_levels on new sale
DELIMITER $$
CREATE TRIGGER after_sale_insert
AFTER INSERT ON sales
FOR EACH ROW
BEGIN
    UPDATE stock_levels
    SET quantity = quantity - NEW.quantity,
        updated_at = CURRENT_TIMESTAMP
    WHERE product_id = NEW.product_id;
    
    -- Log the activity
    INSERT INTO activity_log (user_id, action, table_name, record_id, details)
    VALUES (NEW.created_by, 'SALE', 'sales', NEW.id, 
            CONCAT('Sale of ', NEW.quantity, ' units of product ID ', NEW.product_id));
END $$
DELIMITER ;

-- 11. Trigger: Update stock_levels on purchase order receipt
DELIMITER $$
CREATE TRIGGER after_po_received
AFTER UPDATE ON purchase_orders
FOR EACH ROW
BEGIN
    IF NEW.status = 'received' AND OLD.status != 'received' THEN
        UPDATE stock_levels sl
        JOIN purchase_order_items poi ON sl.product_id = poi.product_id
        SET sl.quantity = sl.quantity + poi.quantity,
            sl.last_restock_date = CURRENT_TIMESTAMP,
            sl.last_restock_quantity = poi.quantity
        WHERE poi.po_id = NEW.id;
        
        -- Log the activity
        INSERT INTO activity_log (user_id, action, table_name, record_id, details)
        VALUES (NEW.created_by, 'PO_RECEIVED', 'purchase_orders', NEW.id, 
                CONCAT('Purchase order ', NEW.po_number, ' received'));
    END IF;
END $$
DELIMITER ;

-- 12. Views

-- Sales Summary View
CREATE VIEW sales_summary AS
SELECT 
    DATE(sale_date) as sale_day,
    COUNT(DISTINCT invoice_number) as total_invoices,
    SUM(quantity) as total_quantity,
    SUM(total_amount) as total_revenue,
    AVG(total_amount) as average_sale
FROM sales
GROUP BY DATE(sale_date);

-- Product Performance View
CREATE VIEW product_performance AS
SELECT 
    p.id,
    p.name,
    p.sku,
    p.category,
    sl.quantity as current_stock,
    COUNT(s.id) as total_sales,
    SUM(s.quantity) as units_sold,
    SUM(s.total_amount) as revenue_generated,
    AVG(s.unit_price) as average_sale_price
FROM products p
LEFT JOIN stock_levels sl ON p.id = sl.product_id
LEFT JOIN sales s ON p.id = s.product_id
GROUP BY p.id;

-- Customer Purchase History View
CREATE VIEW customer_purchase_history AS
SELECT 
    c.id,
    c.name,
    c.customer_type,
    COUNT(DISTINCT s.invoice_number) as total_purchases,
    SUM(s.total_amount) as total_spent,
    MAX(s.sale_date) as last_purchase_date,
    AVG(s.total_amount) as average_purchase_value
FROM customers c
LEFT JOIN sales s ON c.id = s.customer_id
GROUP BY c.id;

-- Low Stock Alert View
CREATE VIEW low_stock_alert AS
SELECT 
    p.id,
    p.name,
    p.sku,
    p.category,
    sl.quantity,
    p.min_stock_level,
    p.supplier
FROM products p
JOIN stock_levels sl ON p.id = sl.product_id
WHERE sl.quantity <= p.min_stock_level; 