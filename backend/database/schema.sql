-- Users
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('owner','manager','staff') NOT NULL DEFAULT 'staff'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Customers
CREATE TABLE IF NOT EXISTS customers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  phone VARCHAR(32) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Jobs
CREATE TABLE IF NOT EXISTS jobs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  scheduled_at DATETIME NOT NULL,
  status ENUM('requested','confirmed','in_progress','completed','cancelled') NOT NULL DEFAULT 'requested',
  customer_id INT NOT NULL,
  CONSTRAINT fk_jobs_customer FOREIGN KEY (customer_id) REFERENCES customers(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Invoices
CREATE TABLE IF NOT EXISTS invoices (
  id INT AUTO_INCREMENT PRIMARY KEY,
  invoice_number VARCHAR(64) NOT NULL,
  job_id INT NOT NULL,
  total_amount DECIMAL(10,2) NOT NULL,
  status ENUM('draft','sent','paid','void') NOT NULL DEFAULT 'draft',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_invoices_job FOREIGN KEY (job_id) REFERENCES jobs(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Promotions
CREATE TABLE IF NOT EXISTS promotions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  description TEXT NULL,
  discount_percent INT NULL,
  active_from DATETIME NULL,
  active_to DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Reviews
CREATE TABLE IF NOT EXISTS reviews (
  id INT AUTO_INCREMENT PRIMARY KEY,
  rating TINYINT NOT NULL,
  comment TEXT NULL,
  customer_id INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_reviews_customer FOREIGN KEY (customer_id) REFERENCES customers(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Settings (simple key-value)
CREATE TABLE IF NOT EXISTS settings (
  `key` VARCHAR(64) PRIMARY KEY,
  `value` VARCHAR(1024) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
