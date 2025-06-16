-- Create database
CREATE DATABASE IF NOT EXISTS users_db;
USE users_db;

-- Users table (students and admins)
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    permanent_address TEXT NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    guardian_name VARCHAR(255) NOT NULL,
    emergency_contact VARCHAR(20) NOT NULL,
    status ENUM('job', 'student', 'unemployed') NOT NULL,
    occupation VARCHAR(255) DEFAULT NULL,
    company_name VARCHAR(255) DEFAULT NULL,
    institution_name VARCHAR(255) DEFAULT NULL,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Rooms table
CREATE TABLE rooms (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(50) NOT NULL UNIQUE,
    capacity INT NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Allocations table: linking students to rooms
CREATE TABLE allocations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    room_id INT UNSIGNED NOT NULL,
    allocated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Payments table
CREATE TABLE payments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('Pending', 'Paid') NOT NULL DEFAULT 'Pending',
    payment_date TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Complaints table
CREATE TABLE complaints (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    complaint TEXT NOT NULL,
    status ENUM('Pending', 'Resolved') NOT NULL DEFAULT 'Pending',
    complaint_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Notices table
CREATE TABLE notices (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    posted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
