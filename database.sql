-- First force close all connections to the database
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS visits;
DROP TABLE IF EXISTS dental_treatments;
DROP TABLE IF EXISTS billing;
DROP TABLE IF EXISTS medical_history;
DROP TABLE IF EXISTS patients;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- Create tables in correct order
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'doctor', 'staff') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE patients (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    date DATE NOT NULL,
    sector VARCHAR(50),
    street_no VARCHAR(50),
    house_no VARCHAR(50),
    non_islamabad_address TEXT,
    phone VARCHAR(20) NOT NULL,
    age INT,
    gender ENUM('Male', 'Female', 'Other'),
    occupation VARCHAR(100),
    email VARCHAR(100),
    diagnosis TEXT NULL,
    treatment_advised TEXT NULL,
    selected_teeth TEXT NULL,
    signature MEDIUMTEXT,
    doctor_signature MEDIUMTEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE medical_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    heart_problem BOOLEAN DEFAULT FALSE,
    blood_pressure BOOLEAN DEFAULT FALSE,
    bleeding_disorder BOOLEAN DEFAULT FALSE,
    blood_thinners BOOLEAN DEFAULT FALSE,
    hepatitis BOOLEAN DEFAULT FALSE,
    diabetes BOOLEAN DEFAULT FALSE,
    fainting_spells BOOLEAN DEFAULT FALSE,
    allergy_anesthesia BOOLEAN DEFAULT FALSE,
    malignancy BOOLEAN DEFAULT FALSE,
    previous_surgery BOOLEAN DEFAULT FALSE,
    epilepsy BOOLEAN DEFAULT FALSE,
    asthma BOOLEAN DEFAULT FALSE,
    pregnant BOOLEAN DEFAULT FALSE,
    phobia BOOLEAN DEFAULT FALSE,
    stomach BOOLEAN DEFAULT FALSE,
    allergy BOOLEAN DEFAULT FALSE,
    drug_allergy BOOLEAN DEFAULT FALSE,
    smoker BOOLEAN DEFAULT FALSE,
    alcoholic BOOLEAN DEFAULT FALSE,
    other_conditions TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
);

CREATE TABLE billing (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    discount_type ENUM('none', 'percentage', 'fixed') DEFAULT 'none',
    discount_value DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    UNIQUE KEY unique_patient (patient_id)
);

CREATE TABLE dental_treatments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    tooth_number TEXT,
    treatment_name VARCHAR(100) NOT NULL,
    quantity INT DEFAULT 1,
    price_per_unit DECIMAL(10,2),
    total_price DECIMAL(10,2),
    status VARCHAR(20) DEFAULT 'planned',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
);

CREATE TABLE visits (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    visit_date DATE,
    treatment_done TEXT,
    visit_amount DECIMAL(10,2) DEFAULT 0,
    visit_mode ENUM('cash', 'card', 'insurance') DEFAULT 'cash',
    balance DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
);

-- Insert default admin user
INSERT INTO users (username, password, name, role) 
VALUES ('admin', '123', 'Administrator', 'admin'); 