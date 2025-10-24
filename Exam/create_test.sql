CREATE DATABASE IF NOT EXISTS test_creation;
USE test_creation;

CREATE TABLE tests (
  test_id INT AUTO_INCREMENT PRIMARY KEY,
  branch VARCHAR(50),
  test_title VARCHAR(100),
  test_date DATE,
  available_from TIME,
  duration INT,
  test_type VARCHAR(50)
);

CREATE TABLE questions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  test_id INT,
  question_text TEXT,
  question_type VARCHAR(50),
  options JSON,
  correct_answer VARCHAR(10),
  descriptive_answer TEXT,
  FOREIGN KEY (test_id) REFERENCES tests(test_id)
);

