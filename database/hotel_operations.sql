CREATE TABLE IF NOT EXISTS staff (
 id INT AUTO_INCREMENT PRIMARY KEY, employee_no VARCHAR(50) NOT NULL UNIQUE, full_name VARCHAR(150) NOT NULL, department VARCHAR(100), job_title VARCHAR(100), phone VARCHAR(50), salary DECIMAL(12,2) DEFAULT 0, status VARCHAR(30) DEFAULT 'Active', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS staff_attendance (
 id INT AUTO_INCREMENT PRIMARY KEY, staff_id INT NOT NULL, attendance_date DATE NOT NULL, check_in TIME NULL, check_out TIME NULL, status VARCHAR(30) DEFAULT 'Present', notes TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uq_staff_day(staff_id,attendance_date)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS staff_shifts (
 id INT AUTO_INCREMENT PRIMARY KEY, staff_id INT NOT NULL, shift_date DATE NOT NULL, shift_name VARCHAR(40), start_time TIME, end_time TIME, notes TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS staff_leave (
 id INT AUTO_INCREMENT PRIMARY KEY, staff_id INT NOT NULL, leave_type VARCHAR(40), start_date DATE NOT NULL, end_date DATE NOT NULL, status VARCHAR(30) DEFAULT 'Pending', reason TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS housekeeping_tasks (
 id INT AUTO_INCREMENT PRIMARY KEY, room_id INT NULL, task_date DATE NOT NULL, task_type VARCHAR(60), assigned_to INT NULL, priority VARCHAR(20) DEFAULT 'Normal', status VARCHAR(30) DEFAULT 'Pending', notes TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS maintenance_requests (
 id INT AUTO_INCREMENT PRIMARY KEY, room_id INT NULL, reported_by INT NULL, request_date DATE NOT NULL, category VARCHAR(60), priority VARCHAR(20) DEFAULT 'Normal', status VARCHAR(30) DEFAULT 'Open', cost DECIMAL(12,2) DEFAULT 0, description TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS hotel_expenses (
 id INT AUTO_INCREMENT PRIMARY KEY, expense_date DATE NOT NULL, category VARCHAR(80), description VARCHAR(255) NOT NULL, amount DECIMAL(12,2) NOT NULL DEFAULT 0, payment_method VARCHAR(40), reference_no VARCHAR(80), notes TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS hotel_suppliers (
 id INT AUTO_INCREMENT PRIMARY KEY, supplier_name VARCHAR(150) NOT NULL, contact_person VARCHAR(120), phone VARCHAR(50), email VARCHAR(150), address TEXT, status VARCHAR(30) DEFAULT 'Active', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS inventory_items (
 id INT AUTO_INCREMENT PRIMARY KEY, sku VARCHAR(80) NOT NULL UNIQUE, item_name VARCHAR(150) NOT NULL, category VARCHAR(80), unit VARCHAR(30), quantity DECIMAL(12,2) DEFAULT 0, reorder_level DECIMAL(12,2) DEFAULT 0, unit_cost DECIMAL(12,2) DEFAULT 0, supplier_id INT NULL, status VARCHAR(40) DEFAULT 'In Stock', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS hotel_services (
 id INT AUTO_INCREMENT PRIMARY KEY, service_name VARCHAR(150) NOT NULL, category VARCHAR(80), price DECIMAL(12,2) DEFAULT 0, status VARCHAR(30) DEFAULT 'Active', description TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS service_orders (
 id INT AUTO_INCREMENT PRIMARY KEY, booking_id INT NULL, service_id INT NOT NULL, order_date DATETIME NOT NULL, quantity DECIMAL(10,2) DEFAULT 1, unit_price DECIMAL(12,2) DEFAULT 0, status VARCHAR(30) DEFAULT 'Pending', notes TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS laundry_orders (
 id INT AUTO_INCREMENT PRIMARY KEY, customer_id INT NULL, room_id INT NULL, order_date DATE NOT NULL, item_count INT DEFAULT 0, total_amount DECIMAL(12,2) DEFAULT 0, status VARCHAR(30) DEFAULT 'Received', notes TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS restaurant_orders (
 id INT AUTO_INCREMENT PRIMARY KEY, booking_id INT NULL, order_date DATETIME NOT NULL, table_no VARCHAR(30), items TEXT NOT NULL, subtotal DECIMAL(12,2) DEFAULT 0, tax DECIMAL(12,2) DEFAULT 0, total_amount DECIMAL(12,2) DEFAULT 0, status VARCHAR(30) DEFAULT 'Open', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS staff_payroll (
 id INT AUTO_INCREMENT PRIMARY KEY, staff_id INT NOT NULL, pay_period VARCHAR(50) NOT NULL, basic_salary DECIMAL(12,2) DEFAULT 0, allowances DECIMAL(12,2) DEFAULT 0, deductions DECIMAL(12,2) DEFAULT 0, net_salary DECIMAL(12,2) DEFAULT 0, payment_date DATE NULL, status VARCHAR(30) DEFAULT 'Draft', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS payment_refunds (
 id INT AUTO_INCREMENT PRIMARY KEY, payment_id INT NOT NULL, refund_date DATE NOT NULL, amount DECIMAL(12,2) NOT NULL, reason TEXT, status VARCHAR(30) DEFAULT 'Pending', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS room_transfers (
 id INT AUTO_INCREMENT PRIMARY KEY, booking_id INT NOT NULL, from_room_id INT NOT NULL, to_room_id INT NOT NULL, transfer_date DATETIME NOT NULL, reason TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS daily_closing (
 id INT AUTO_INCREMENT PRIMARY KEY, closing_date DATE NOT NULL UNIQUE, cash_total DECIMAL(12,2) DEFAULT 0, card_total DECIMAL(12,2) DEFAULT 0, bank_total DECIMAL(12,2) DEFAULT 0, expense_total DECIMAL(12,2) DEFAULT 0, notes TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO system_pages (page_name,url,category,icon_class,page_type) SELECT 'Staff Management','admin/operations/staff.php','Hotel Operations','fas fa-user-tie','Sidebar' WHERE NOT EXISTS (SELECT 1 FROM system_pages WHERE url='admin/operations/staff.php');
INSERT INTO system_pages (page_name,url,category,icon_class,page_type) SELECT 'Staff Attendance','admin/operations/attendance.php','Hotel Operations','fas fa-user-clock','Sidebar' WHERE NOT EXISTS (SELECT 1 FROM system_pages WHERE url='admin/operations/attendance.php');
INSERT INTO system_pages (page_name,url,category,icon_class,page_type) SELECT 'Staff Shifts','admin/operations/shifts.php','Hotel Operations','fas fa-business-time','Sidebar' WHERE NOT EXISTS (SELECT 1 FROM system_pages WHERE url='admin/operations/shifts.php');
INSERT INTO system_pages (page_name,url,category,icon_class,page_type) SELECT 'Staff Leave','admin/operations/leave.php','Hotel Operations','fas fa-calendar-minus','Sidebar' WHERE NOT EXISTS (SELECT 1 FROM system_pages WHERE url='admin/operations/leave.php');
INSERT INTO system_pages (page_name,url,category,icon_class,page_type) SELECT 'Housekeeping','admin/operations/housekeeping.php','Hotel Operations','fas fa-broom','Sidebar' WHERE NOT EXISTS (SELECT 1 FROM system_pages WHERE url='admin/operations/housekeeping.php');
INSERT INTO system_pages (page_name,url,category,icon_class,page_type) SELECT 'Maintenance','admin/operations/maintenance.php','Hotel Operations','fas fa-tools','Sidebar' WHERE NOT EXISTS (SELECT 1 FROM system_pages WHERE url='admin/operations/maintenance.php');
INSERT INTO system_pages (page_name,url,category,icon_class,page_type) SELECT 'Expenses','admin/operations/expenses.php','Finance','fas fa-money-bill-wave','Sidebar' WHERE NOT EXISTS (SELECT 1 FROM system_pages WHERE url='admin/operations/expenses.php');
INSERT INTO system_pages (page_name,url,category,icon_class,page_type) SELECT 'Inventory','admin/operations/inventory.php','Inventory','fas fa-boxes','Sidebar' WHERE NOT EXISTS (SELECT 1 FROM system_pages WHERE url='admin/operations/inventory.php');
INSERT INTO system_pages (page_name,url,category,icon_class,page_type) SELECT 'Suppliers','admin/operations/suppliers.php','Inventory','fas fa-truck','Sidebar' WHERE NOT EXISTS (SELECT 1 FROM system_pages WHERE url='admin/operations/suppliers.php');
INSERT INTO system_pages (page_name,url,category,icon_class,page_type) SELECT 'Hotel Services','admin/operations/services.php','Hotel Operations','fas fa-concierge-bell','Sidebar' WHERE NOT EXISTS (SELECT 1 FROM system_pages WHERE url='admin/operations/services.php');
INSERT INTO system_pages (page_name,url,category,icon_class,page_type) SELECT 'Service Orders','admin/operations/service_orders.php','Hotel Operations','fas fa-concierge-bell','Sidebar' WHERE NOT EXISTS (SELECT 1 FROM system_pages WHERE url='admin/operations/service_orders.php');
INSERT INTO system_pages (page_name,url,category,icon_class,page_type) SELECT 'Laundry','admin/operations/laundry.php','Hotel Operations','fas fa-tshirt','Sidebar' WHERE NOT EXISTS (SELECT 1 FROM system_pages WHERE url='admin/operations/laundry.php');
INSERT INTO system_pages (page_name,url,category,icon_class,page_type) SELECT 'Restaurant Orders','admin/operations/restaurant.php','Food & Beverage','fas fa-utensils','Sidebar' WHERE NOT EXISTS (SELECT 1 FROM system_pages WHERE url='admin/operations/restaurant.php');
INSERT INTO system_pages (page_name,url,category,icon_class,page_type) SELECT 'Payroll','admin/operations/payroll.php','Finance','fas fa-file-invoice-dollar','Sidebar' WHERE NOT EXISTS (SELECT 1 FROM system_pages WHERE url='admin/operations/payroll.php');
INSERT INTO system_pages (page_name,url,category,icon_class,page_type) SELECT 'Refunds','admin/operations/refunds.php','Finance','fas fa-undo','Sidebar' WHERE NOT EXISTS (SELECT 1 FROM system_pages WHERE url='admin/operations/refunds.php');
INSERT INTO system_pages (page_name,url,category,icon_class,page_type) SELECT 'Room Transfers','admin/operations/room_transfers.php','Front Desk','fas fa-exchange-alt','Sidebar' WHERE NOT EXISTS (SELECT 1 FROM system_pages WHERE url='admin/operations/room_transfers.php');
INSERT INTO system_pages (page_name,url,category,icon_class,page_type) SELECT 'Daily Closing','admin/operations/daily_closing.php','Finance','fas fa-cash-register','Sidebar' WHERE NOT EXISTS (SELECT 1 FROM system_pages WHERE url='admin/operations/daily_closing.php');
