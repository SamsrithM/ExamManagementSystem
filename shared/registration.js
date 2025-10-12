const express = require('express');
const mysql = require('mysql2/promise');
const bcrypt = require('bcryptjs');
const path = require('path');
const app = express();

app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Serve your registration HTML directly without a public folder
app.get('/register', (req, res) => {
  res.sendFile(path.join(__dirname, 'registration_page.html')); // Adjust filepath if needed
});

const dbConfig = {
  host: 'localhost',
  user: 'root',
  password: 'kingbharat@3833',
  database: 'reg',
};

app.post('/register', async (req, res) => {
  const { role } = req.body;
  let connection;

  try {
    connection = await mysql.createConnection(dbConfig);

    if (role === 'student') {
      const {
        'first-name-student': first_name,
        'last-name-student': last_name,
        'gender-student': gender,
        'dob-student': dob,
        'batch':batch,
        'department-student':department,
        'roll-number': roll_number,
        'institute-email-student': institute_email,
        course: programme,
        semester,
        'password-student': password,
        'confirm-password-student': confirm_password
      } = req.body;

      if (password !== confirm_password) {
        return res.status(400).json({ success: false, message: 'Passwords do not match.' });
      }

      const password_hash = await bcrypt.hash(password, 10);

      await connection.execute(
        `INSERT INTO students
         (first_name, last_name, gender, dob, batch, department, roll_number, institute_email, programme, semester, password_hash)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
        [first_name, last_name, gender, dob, batch, department, roll_number, institute_email, programme, semester, password_hash]
      );

      return res.json({ success: true, message: 'Student registered successfully.' });

    } else if (role === 'faculty') {
      const {
        'first-name-faculty': first_name,
        'last-name-faculty': last_name,
        'gender-faculty': gender,
        'email-faculty': institute_email,
        'department-faculty':department,
        designation,
        'password-faculty': password,
        'confirm-password-faculty': confirm_password
      } = req.body;

      if (password !== confirm_password) {
        return res.status(400).json({ success: false, message: 'Passwords do not match.' });
      }

      const password_hash = await bcrypt.hash(password, 10);

      await connection.execute(
        `INSERT INTO faculty
         (first_name, last_name, gender, institute_email, department, designation, password_hash)
         VALUES (?, ?, ?, ?, ?, ?, ?)`,
        [first_name, last_name, gender, institute_email, department, designation, password_hash]
      );

      return res.json({ success: true, message: 'Faculty registered successfully.' });

    } else {
      return res.status(400).json({ success: false, message: 'Invalid role selected.' });
    }
  } catch (err) {
    console.error(err);
    return res.status(500).json({ success: false, message: 'Server error.' });
  } finally {
    if (connection) await connection.end();
  }
});

const PORT = 3000;
app.listen(PORT, () => console.log(`Server running on port ${PORT}`));
