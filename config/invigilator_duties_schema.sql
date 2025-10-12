-- Database schema for Invigilator Duty Management System
-- Extends the existing reg database

USE reg;

-- Exams table
CREATE TABLE IF NOT EXISTS exams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    exam_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    venue VARCHAR(100) NOT NULL,
    subject_code VARCHAR(20),
    subject_name VARCHAR(100),
    total_students INT DEFAULT 0,
    status ENUM('scheduled', 'ongoing', 'completed', 'cancelled') DEFAULT 'scheduled',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES faculty(id) ON DELETE SET NULL
);

-- Invigilator duties table
CREATE TABLE IF NOT EXISTS invigilator_duties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exam_id INT NOT NULL,
    faculty_id INT NOT NULL,
    duty_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    venue VARCHAR(100) NOT NULL,
    status ENUM('assigned', 'confirmed', 'present', 'absent') DEFAULT 'assigned',
    attendance_marked_at TIMESTAMP NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
    FOREIGN KEY (faculty_id) REFERENCES faculty(id) ON DELETE CASCADE,
    UNIQUE KEY unique_exam_faculty (exam_id, faculty_id)
);

-- Classes table for exam sessions
CREATE TABLE IF NOT EXISTS exam_classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exam_id INT NOT NULL,
    class_name VARCHAR(50) NOT NULL,
    subject_code VARCHAR(20),
    subject_name VARCHAR(100),
    student_count INT DEFAULT 0,
    room_number VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
);

-- Insert sample data
INSERT INTO exams (title, exam_date, start_time, end_time, venue, subject_code, subject_name, total_students, created_by) VALUES
('Midterm Exam', '2025-01-13', '10:00:00', '12:00:00', 'Lecture Hall A-101', 'CS101', 'Computer Science Fundamentals', 95, 1),
('Quiz', '2025-01-25', '14:00:00', '15:00:00', 'Seminar Room B-205', 'CS102', 'Data Structures', 115, 1),
('Final Exam', '2024-12-15', '09:00:00', '11:00:00', 'Auditorium Main Hall', 'MA101', 'Mathematics', 150, 1),
('Midterm Quiz', '2024-12-05', '11:00:00', '12:00:00', 'Lab Room C-110', 'EE201', 'Electrical Engineering', 30, 1);

-- Insert sample invigilator duties
INSERT INTO invigilator_duties (exam_id, faculty_id, duty_date, start_time, end_time, venue, status) VALUES
(1, 1, '2025-01-13', '10:00:00', '12:00:00', 'Lecture Hall A-101', 'assigned'),
(1, 2, '2025-01-13', '10:00:00', '12:00:00', 'Lecture Hall A-101', 'assigned'),
(1, 3, '2025-01-13', '10:00:00', '12:00:00', 'Lecture Hall A-101', 'assigned'),
(2, 1, '2025-01-25', '14:00:00', '15:00:00', 'Seminar Room B-205', 'assigned'),
(2, 4, '2025-01-25', '14:00:00', '15:00:00', 'Seminar Room B-205', 'assigned'),
(3, 1, '2024-12-15', '09:00:00', '11:00:00', 'Auditorium Main Hall', 'present'),
(3, 5, '2024-12-15', '09:00:00', '11:00:00', 'Auditorium Main Hall', 'present'),
(4, 1, '2024-12-05', '11:00:00', '12:00:00', 'Lab Room C-110', 'absent');

-- Insert sample exam classes
INSERT INTO exam_classes (exam_id, class_name, subject_code, subject_name, student_count, room_number) VALUES
(1, 'Class A', 'CS101', 'Computer Science Fundamentals', 50, 'A-101'),
(1, 'Class B', 'CS102', 'Data Structures', 45, 'A-101'),
(2, 'Class C', 'CS201', 'Algorithms', 60, 'B-205'),
(2, 'Class D', 'CS202', 'Database Systems', 55, 'B-205'),
(3, 'Class F', 'MA101', 'Mathematics', 80, 'Main Hall'),
(3, 'Class G', 'PH102', 'Physics', 70, 'Main Hall'),
(4, 'Class H', 'EE201', 'Electrical Engineering', 30, 'C-110');
