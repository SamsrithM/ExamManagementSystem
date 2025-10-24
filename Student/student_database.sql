DROP DATABASE IF EXISTS student_data;
CREATE DATABASE student_data;
USE student_data;

CREATE TABLE students (
    sid INT AUTO_INCREMENT PRIMARY KEY,
    student_username VARCHAR(60) NOT NULL UNIQUE,
    student_password VARCHAR(255) NOT NULL
);

INSERT INTO students (student_username, student_password) VALUES
('s2021001', 'stud123'),
('s2021002', 'apple2025'),
('s2021003', 'welcome321'),
('s2021004', 'kurnool456'),
('s2021005', 'iiitdm999'),
('s2021006', 'password001'),
('s2021007', 'qwerty789'),
('s2021008', 'roll1008'),
('s2021009', 'hello2009'),
('s2021010', 'pass1010'),
('s2021011', 'student11'),
('s2021012', 'abc!234');
