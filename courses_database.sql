-- Create admin_courses table in the course_registration_data database
USE course_registration_data;

CREATE TABLE IF NOT EXISTS admin_courses (
    course_id INT AUTO_INCREMENT PRIMARY KEY,
    course_name VARCHAR(100) NOT NULL UNIQUE,
    course_code VARCHAR(20) UNIQUE,
    description TEXT,
    assigned_faculty_id INT NULL,
    assigned_faculty_name VARCHAR(100) NULL,
    assigned_faculty_email VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
ALTER TABLE admin_courses 
ADD COLUMN IF NOT EXISTS assigned_faculty_id INT NULL,
ADD COLUMN IF NOT EXISTS assigned_faculty_name VARCHAR(100) NULL;
ALTER TABLE admin_courses 
ADD COLUMN IF NOT EXISTS assigned_faculty_email VARCHAR(100) NULL;