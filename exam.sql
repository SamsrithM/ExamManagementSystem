CREATE DATABASE IF NOT EXISTS iiitdm_faculty_db2;
USE iiitdm_faculty_db2;

CREATE TABLE IF NOT EXISTS faculty_users2 (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
);

-- Sample usernames and dummy passwords (for testing only)
INSERT INTO faculty_users2 (username, password) VALUES
('v_sivarama_krishnaiah', 'vsiva123'),
('dr_ramesh_babu', 'ramesh456'),
('prof_sarita_k', 'sarita789'),
('dr_venu_gopal', 'venu321'),
('prof_rama_devi', 'rama654'),
('dr_krishna_murthy', 'krishna987'),
('asstprof_priyanka', 'priya741'),
('lect_anil_kumar', 'anil852'),
('prof_suresh_p', 'suresh963'),
('hod_cse_mahesh', 'mahesh147');
