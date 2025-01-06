-- Create and use the database
DROP DATABASE IF EXISTS dental_clinic;
CREATE DATABASE dental_clinic;
USE dental_clinic;

-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'doctor', 'staff') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Patients table
CREATE TABLE patients (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    date DATE NOT NULL,
    
    -- Address fields
    sector VARCHAR(50),
    street_no VARCHAR(50),
    house_no VARCHAR(50),
    non_islamabad_address TEXT,
    
    -- Personal information
    phone VARCHAR(20) NOT NULL,
    age INT,
    gender ENUM('Male', 'Female', 'Other'),
    occupation VARCHAR(100),
    email VARCHAR(100),
    
    -- Medical information
    diagnosis TEXT,
    treatment_advised TEXT,
    selected_teeth TEXT,
    
    -- System fields
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_name (name),
    INDEX idx_phone (phone)
);

-- Medical history table
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

-- Treatments table
CREATE TABLE treatments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    treatment_name VARCHAR(100) NOT NULL,
    quantity INT DEFAULT 1,
    price_per_unit DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'completed') DEFAULT 'pending',
    treatment_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
);

-- Visits table
CREATE TABLE visits (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    visit_number INT NOT NULL,
    visit_date DATE,
    treatment_done TEXT,
    amount_paid DECIMAL(10,2) DEFAULT 0,
    balance DECIMAL(10,2) DEFAULT 0,
    payment_mode ENUM('cash', 'card', 'insurance') DEFAULT 'cash',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
);

-- Billing table
CREATE TABLE billing (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    discount_type ENUM('percentage', 'fixed') DEFAULT 'fixed',
    discount_value DECIMAL(10,2) DEFAULT 0,
    net_total DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
);

-- Insert default admin user
INSERT INTO users (username, password, name, role) 
VALUES ('admin', '$2y$10$YourHashedPasswordHere', 'Administrator', 'admin'); 