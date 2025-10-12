-- Create a new database
CREATE DATABASE IF NOT EXISTS iiitdm_registration_db;
USE iiitdm_registration_db;

-- Table for students
CREATE TABLE IF NOT EXISTS students (
    student_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    gender ENUM('male','female','other') NOT NULL,
    dob DATE,
    batch YEAR,
    department VARCHAR(50),
    roll_number VARCHAR(20) UNIQUE,
    institute_email VARCHAR(100) UNIQUE,
    course VARCHAR(20),
    semester INT,
    password VARCHAR(255) NOT NULL
);

-- Table for faculty
CREATE TABLE IF NOT EXISTS faculty (
    faculty_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    gender ENUM('male','female','other') NOT NULL,
    email VARCHAR(100) UNIQUE,
    department VARCHAR(50),
    designation VARCHAR(50),
    password VARCHAR(255) NOT NULL
);
