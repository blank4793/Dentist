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

-- Patients table with all fields
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
    selected_teeth VARCHAR(255),
    
    -- System fields
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_name (name),
    INDEX idx_phone (phone),
    INDEX idx_email (email),
    INDEX idx_sector (sector),
    INDEX idx_gender (gender)
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
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    INDEX idx_patient_treatment (patient_id, treatment_name)
);

-- Visits table
CREATE TABLE visits (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    visit_number INT NOT NULL,
    visit_date DATE,
    treatment VARCHAR(255),
    amount_paid DECIMAL(10,2),
    balance DECIMAL(10,2),
    payment_mode VARCHAR(50),
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    INDEX idx_patient_visit (patient_id, visit_date)
);

-- Dental treatments table
CREATE TABLE dental_treatments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    tooth_number VARCHAR(2) NOT NULL,
    treatment_type VARCHAR(50) NOT NULL,
    notes TEXT,
    status ENUM('planned', 'in_progress', 'completed') DEFAULT 'planned',
    treatment_date DATE,
    price DECIMAL(10,2),
    surface VARCHAR(50),
    material VARCHAR(50),
    shade VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    INDEX idx_patient_tooth (patient_id, tooth_number)
);

-- Tooth conditions table
CREATE TABLE tooth_conditions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    tooth_number VARCHAR(2) NOT NULL,
    condition_type ENUM('caries', 'missing', 'filled', 'crown', 'bridge', 'implant', 'root_canal'),
    notes TEXT,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    INDEX idx_patient_tooth_condition (patient_id, tooth_number)
);

-- Treatment history table
CREATE TABLE treatment_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    dental_treatment_id INT NOT NULL,
    status_change ENUM('planned', 'in_progress', 'completed'),
    notes TEXT,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (dental_treatment_id) REFERENCES dental_treatments(id) ON DELETE CASCADE
);

-- Insert default admin user
INSERT INTO users (username, password, name, role) 
VALUES ('admin', '$2y$10$YourHashedPasswordHere', 'Administrator', 'admin'); 