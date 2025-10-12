CREATE DATABASE reg;
USE reg;

CREATE TABLE students (
  id INT AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(50),
  last_name VARCHAR(50),
  gender VARCHAR(10),
  dob DATE,
  batch VARCHAR(10),
  department VARCHAR(30),
  roll_number VARCHAR(15) UNIQUE,
  institute_email VARCHAR(100) UNIQUE,
  programme VARCHAR(20),
  semester INT,
  password_hash VARCHAR(255),
  reg_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Faculty table
CREATE TABLE faculty (
  id INT AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(50),
  last_name VARCHAR(50),
  gender VARCHAR(10),
  institute_email VARCHAR(100) UNIQUE,
  department VARCHAR(30),
  designation VARCHAR(30),
  password_hash VARCHAR(255),
  reg_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


select * from students;
