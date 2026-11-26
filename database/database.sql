CREATE DATABASE IF NOT EXISTS celebi_checker_v2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE celebi_checker_v2;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('checker','supervisor') NOT NULL DEFAULT 'checker',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE flights (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  flight_number VARCHAR(20) NOT NULL,
  registration VARCHAR(20) NULL,
  route VARCHAR(50) NOT NULL,
  type ENUM('arrival','departure') NOT NULL,
  schedule TIME NULL,
  flight_date DATE NOT NULL,
  status ENUM('scheduled','on_progress','completed','cancelled') NOT NULL DEFAULT 'scheduled',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_flight_date (flight_date),
  INDEX idx_type (type),
  INDEX idx_flight_number (flight_number)
) ENGINE=InnoDB;

CREATE TABLE baggage_records (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  flight_id INT UNSIGNED NOT NULL UNIQUE,
  total_koli INT UNSIGNED NOT NULL DEFAULT 0,
  total_kg DECIMAL(10,2) NOT NULL DEFAULT 0,
  koli_selesai INT UNSIGNED NOT NULL DEFAULT 0,
  sisa_koli INT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_baggage_flight FOREIGN KEY (flight_id) REFERENCES flights(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE baggage_scans (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  flight_id INT UNSIGNED NOT NULL,
  baggage_label VARCHAR(100) NOT NULL,
  scanned_by INT UNSIGNED NULL,
  scanned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_flight_label (flight_id, baggage_label),
  INDEX idx_label (baggage_label),
  CONSTRAINT fk_scan_flight FOREIGN KEY (flight_id) REFERENCES flights(id) ON DELETE CASCADE,
  CONSTRAINT fk_scan_user FOREIGN KEY (scanned_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Password sementara untuk akun demo: password
INSERT INTO users (username, password, role)
VALUES ('checker', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro8x0mJ7M6Q8s8bKQ4lQ5L9O', 'checker');
