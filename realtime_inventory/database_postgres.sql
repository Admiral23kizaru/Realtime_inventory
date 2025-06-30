-- 1. Create Database
CREATE DATABASE inventory_system;

-- 2. Users Table
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE,
    full_name VARCHAR(255),
    role VARCHAR(20) CHECK (role IN ('admin', 'manager', 'staff')) DEFAULT 'staff',
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. Products Table
CREATE TABLE products (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    sku VARCHAR(50) UNIQUE,
    price DECIMAL(10,2) NOT NULL,
    cost_price DECIMAL(10,2),
    category VARCHAR(100),
    supplier VARCHAR(255),
    min_stock_level INTEGER DEFAULT 10,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 4. Customers Table
CREATE TABLE customers (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(50),
    email VARCHAR(100),
    address TEXT,
    city VARCHAR(100),
    state VARCHAR(100),
    postal_code VARCHAR(20),
    country VARCHAR(100),
    customer_type VARCHAR(20) CHECK (customer_type IN ('retail', 'wholesale', 'distributor')) DEFAULT 'retail',
    credit_limit DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 5. Stock Levels Table
CREATE TABLE stock_levels (
    id SERIAL PRIMARY KEY,
    product_id INTEGER NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    quantity INTEGER NOT NULL DEFAULT 0,
    last_restock_date TIMESTAMP,
    last_restock_quantity INTEGER,
    location VARCHAR(100)
);

-- 6. Sales Table
CREATE TABLE sales (
    id SERIAL PRIMARY KEY,
    invoice_number VARCHAR(50) UNIQUE,
    product_id INTEGER NOT NULL REFERENCES products(id),
    customer_id INTEGER NOT NULL REFERENCES customers(id),
    quantity INTEGER NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    discount DECIMAL(10,2) DEFAULT 0.00,
    tax DECIMAL(10,2) DEFAULT 0.00,
    total_amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(20) CHECK (payment_method IN ('cash', 'credit_card', 'bank_transfer', 'other')) DEFAULT 'cash',
    payment_status VARCHAR(20) CHECK (payment_status IN ('pending', 'paid', 'partial', 'cancelled')) DEFAULT 'pending',
    sale_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    created_by INTEGER REFERENCES users(id)
);

-- 7. Purchase Orders Table
CREATE TABLE purchase_orders (
    id SERIAL PRIMARY KEY,
    po_number VARCHAR(50) UNIQUE,
    supplier VARCHAR(255) NOT NULL,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expected_delivery_date DATE,
    status VARCHAR(20) CHECK (status IN ('pending', 'ordered', 'received', 'cancelled')) DEFAULT 'pending',
    total_amount DECIMAL(10,2) NOT NULL,
    notes TEXT,
    created_by INTEGER REFERENCES users(id)
);

-- 8. Purchase Order Items Table
CREATE TABLE purchase_order_items (
    id SERIAL PRIMARY KEY,
    po_id INTEGER NOT NULL REFERENCES purchase_orders(id) ON DELETE CASCADE,
    product_id INTEGER NOT NULL REFERENCES products(id),
    quantity INTEGER NOT NULL,
    unit_cost DECIMAL(10,2) NOT NULL,
    total_cost DECIMAL(10,2) NOT NULL
);

-- 9. Activity Log Table
CREATE TABLE activity_log (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id),
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(100),
    record_id INTEGER,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create indexes
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_products_category ON products(category);
CREATE INDEX idx_products_supplier ON products(supplier);
CREATE INDEX idx_customers_type ON customers(customer_type);
CREATE INDEX idx_customers_email ON customers(email);
CREATE INDEX idx_stock_levels_location ON stock_levels(location);
CREATE INDEX idx_sales_invoice ON sales(invoice_number);
CREATE INDEX idx_sales_date ON sales(sale_date);
CREATE INDEX idx_sales_payment_status ON sales(payment_status);
CREATE INDEX idx_purchase_orders_number ON purchase_orders(po_number);
CREATE INDEX idx_purchase_orders_status ON purchase_orders(status);
CREATE INDEX idx_activity_log_action ON activity_log(action);
CREATE INDEX idx_activity_log_created_at ON activity_log(created_at);

-- Create functions and triggers for updating timestamps
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Create triggers for updating timestamps
CREATE TRIGGER update_users_updated_at
    BEFORE UPDATE ON users
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_products_updated_at
    BEFORE UPDATE ON products
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_customers_updated_at
    BEFORE UPDATE ON customers
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

-- Create trigger for updating stock levels after sale
CREATE OR REPLACE FUNCTION update_stock_after_sale()
RETURNS TRIGGER AS $$
BEGIN
    UPDATE stock_levels
    SET quantity = quantity - NEW.quantity,
        updated_at = CURRENT_TIMESTAMP
    WHERE product_id = NEW.product_id;
    
    INSERT INTO activity_log (user_id, action, table_name, record_id, details)
    VALUES (NEW.created_by, 'SALE', 'sales', NEW.id, 
            'Sale of ' || NEW.quantity || ' units of product ID ' || NEW.product_id);
    
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER after_sale_insert
    AFTER INSERT ON sales
    FOR EACH ROW
    EXECUTE FUNCTION update_stock_after_sale();

-- Create trigger for updating stock levels after purchase order receipt
CREATE OR REPLACE FUNCTION update_stock_after_po_received()
RETURNS TRIGGER AS $$
BEGIN
    IF NEW.status = 'received' AND OLD.status != 'received' THEN
        UPDATE stock_levels sl
        SET quantity = sl.quantity + poi.quantity,
            last_restock_date = CURRENT_TIMESTAMP,
            last_restock_quantity = poi.quantity
        FROM purchase_order_items poi
        WHERE sl.product_id = poi.product_id
        AND poi.po_id = NEW.id;
        
        INSERT INTO activity_log (user_id, action, table_name, record_id, details)
        VALUES (NEW.created_by, 'PO_RECEIVED', 'purchase_orders', NEW.id, 
                'Purchase order ' || NEW.po_number || ' received');
    END IF;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER after_po_received
    AFTER UPDATE ON purchase_orders
    FOR EACH ROW
    EXECUTE FUNCTION update_stock_after_po_received();

-- Create views
CREATE VIEW sales_summary AS
SELECT 
    DATE(sale_date) as sale_day,
    COUNT(DISTINCT invoice_number) as total_invoices,
    SUM(quantity) as total_quantity,
    SUM(total_amount) as total_revenue,
    AVG(total_amount) as average_sale
FROM sales
GROUP BY DATE(sale_date);

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
GROUP BY p.id, p.name, p.sku, p.category, sl.quantity;

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
GROUP BY c.id, c.name, c.customer_type;

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