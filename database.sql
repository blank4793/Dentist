CREATE DATABASE dental_clinic;
USE dental_clinic;

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
    address TEXT NOT NULL,
    phone VARCHAR(20) NOT NULL,
    age VARCHAR(10),
    gender VARCHAR(10),
    occupation VARCHAR(100),
    email VARCHAR(100),
    diagnosis TEXT,
    visit_signature VARCHAR(255),
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
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
);

CREATE TABLE treatments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    treatment_name VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'completed') DEFAULT 'pending',
    treatment_date DATE,
    notes TEXT,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
);

CREATE TABLE visits (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    visit_date DATE,
    treatment VARCHAR(255),
    amount DECIMAL(10,2),
    payment_mode VARCHAR(50),
    balance DECIMAL(10,2),
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
);

-- Insert default admin user
INSERT INTO users (username, password, name, role) 
VALUES ('admin', '$2y$10$YourHashedPasswordHere', 'Administrator', 'admin'); 