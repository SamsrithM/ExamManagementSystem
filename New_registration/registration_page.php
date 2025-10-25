<?php
// registration.php — New Registration Page
// Optional: Add PHP logic at the top if needed (e.g., session_start();)
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>New Registration</title>
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
      background: linear-gradient(135deg, #e0f7fa, #ffffff);
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      padding: 20px;
    }

    .register-container {
      background: #fff;
      padding: 40px 30px;
      border-radius: 12px;
      box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
      width: 100%;
      max-width: 600px;
    }

    .header-logo {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      margin-bottom: 30px;
    }

    .header-logo img {
      width: 40px;
      height: 40px;
      object-fit: contain;
    }

    h2 {
      color: #1a73e8;
    }

    label {
      display: block;
      margin-bottom: 6px;
      font-weight: 600;
    }

    input,
    select {
      width: 100%;
      padding: 12px;
      margin-bottom: 20px;
      border: 1px solid #ccc;
      border-radius: 8px;
      font-size: 1rem;
    }

    .row {
      display: flex;
      gap: 20px;
      flex-wrap: wrap;
    }

    .row > div {
      flex: 1;
      min-width: 100px;
    }

    button {
      width: 100%;
      padding: 14px;
      background-color: #1a73e8;
      color: white;
      font-size: 1rem;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-weight: bold;
      transition: background-color 0.3s ease;
    }

    button:hover {
      background-color: #155ab6;
    }

    .back-link {
      text-align: center;
      margin-top: 20px;
      display: block;
      color: #555;
      text-decoration: none;
    }

    .back-link:hover {
      text-decoration: underline;
    }

    .hidden {
      display: none;
    }

    @media (max-width: 480px) {
      .row {
        flex-direction: column;
      }

      .register-container {
        padding: 30px 20px;
      }
    }
  </style>
</head>
<body>

  <div class="register-container">
    <div class="header-logo">
      <img src="https://cdn.vectorstock.com/i/1000x1000/70/87/new-user-registration-color-icon-vector-44517087.webp" alt="Logo" />
      <h2>New Registration</h2>
    </div>

    <form action="registration_action.php" method="POST" id="registration-form">

      <label for="role">Registering As</label>
      <select id="role" name="role" required>
        <option value="">-- Select Role --</option>
        <option value="student">Student</option>
        <option value="faculty">Faculty</option>
      </select>

      <!-- STUDENT FIELDS -->
      <div id="student-fields" class="hidden">

        <div class="row">
          <div>
            <label for="first-name-student">First Name</label>
            <input type="text" id="first-name-student" name="first-name-student" placeholder="First Name" />
          </div>
          <div>
            <label for="last-name-student">Last Name</label>
            <input type="text" id="last-name-student" name="last-name-student" placeholder="Last Name" />
          </div>
        </div>

        <div class="row">
          <div>
            <label for="gender-student">Gender</label>
            <select id="gender-student" name="gender-student">
              <option value="">-- Select Gender --</option>
              <option value="male">Male</option>
              <option value="female">Female</option>
              <option value="other">Other</option>
            </select>
          </div>
          <div>
            <label for="dob-student">Date of Birth</label>
            <input type="date" id="dob-student" name="dob-student" />
          </div>
        </div>

        <div class="row">
          <div>
            <label for="batch">Batch</label>
            <select id="batch" name="batch">
              <option value="">-- Select Batch --</option>
              <option value="2021">2021</option>
              <option value="2022">2022</option>
              <option value="2023">2023</option>
              <option value="2024">2024</option>
              <option value="2025">2025</option>
            </select>
          </div>
          <div>
            <label for="student-department">Department</label>
            <select id="student-department" name="student-department">
              <option value="">-- Select Department --</option>
              <option value="Science">Science</option>
              <option value="CSE">CSE</option>
              <option value="ECE">ECE</option>
              <option value="MECH">MECH</option>
              <option value="AIDS">AIDS</option>
            </select>
          </div>
        </div>

        <div class="row">
          <div>
            <label for="roll-number">Roll Number</label>
            <input type="text" id="roll-number" name="roll-number" placeholder="Enter your roll number" />
          </div>
          <div>
            <label for="institute-email-student">Institute Mail ID</label>
            <input type="email" id="institute-email-student" name="institute-email-student" placeholder="rollnumber@iiitk.ac.in" />
          </div>
        </div>

        <div class="row">
          <div>
            <label for="course">Programme</label>
            <select id="course" name="course">
              <option value="">-- Select Programme --</option>
              <option value="btech">BTech</option>
              <option value="mtech">MTech</option>
              <option value="phd">PhD</option>
            </select>
          </div>
          <div>
            <label for="semester">Semester</label>
            <select id="semester" name="semester">
              <option value="">-- Select Semester --</option>
            </select>
          </div>
        </div>

        <label for="password-student">Create Password</label>
        <input type="password" id="password-student" name="password-student" placeholder="At least 6 characters" minlength="6" />

        <label for="confirm-password-student">Confirm Password</label>
        <input type="password" id="confirm-password-student" name="confirm-password-student" placeholder="Re-enter your password" minlength="6" />

      </div>

      <!-- FACULTY FIELDS -->
      <div id="faculty-fields" class="hidden">

        <div class="row">        
          <div>
            <label for="first-name-faculty">First Name</label>
            <input type="text" id="first-name-faculty" name="first-name-faculty" placeholder="First Name" />
          </div>
          <div>
            <label for="last-name-faculty">Last Name</label>
            <input type="text" id="last-name-faculty" name="last-name-faculty" placeholder="Last Name" />
          </div>
        </div>

        <label for="gender-faculty">Gender</label>
        <select id="gender-faculty" name="gender-faculty">
          <option value="">-- Select Gender --</option>
          <option value="male">Male</option>
          <option value="female">Female</option>
          <option value="other">Other</option>
        </select>

        <label for="email-faculty">Institute Mail ID</label>
        <input type="email" id="email-faculty" name="email-faculty" placeholder="Enter your email" />

        <div class="row">
          <div>
            <label for="faculty-department">Department</label>
            <select id="faculty-department" name="faculty-department">
              <option value="">-- Select Department --</option>
              <option value="Science">Science</option>
              <option value="CSE">CSE</option>
              <option value="ECE">ECE</option>
              <option value="MECH">MECH</option>
              <option value="AIDS">AIDS</option>
            </select>
          </div>
          <div>
            <label for="designation">Designation</label>
            <select id="designation" name="designation">
              <option value="">-- Select Designation --</option>
              <option value="Professor">Professor</option>
              <option value="Associate-professor">Associate Professor</option>
              <option value="Assistant-professor">Assistant Professor</option>
              <option value="Visiting-professor">Visiting Professor</option>
              <option value="Adjunct-professor">Adjunct Professor</option>
            </select>
          </div>
        </div>

        <label for="password-faculty">Create Password</label>
        <input type="password" id="password-faculty" name="password-faculty" placeholder="At least 6 characters" minlength="6" />

        <label for="confirm-password-faculty">Confirm Password</label>
        <input type="password" id="confirm-password-faculty" name="confirm-password-faculty" placeholder="Re-enter your password" minlength="6" />

      </div>

      <button type="submit">Register</button>
    </form>

    <a href="/index.php" class="back-link">← Back to Home</a>
  </div>

  <script>
    const roleSelect = document.getElementById('role');
    const studentFields = document.getElementById('student-fields');
    const facultyFields = document.getElementById('faculty-fields');

    const courseSelect = document.getElementById('course');
    const semesterSelect = document.getElementById('semester');

    const semestersByCourse = {
      btech: 8,
      mtech: 4,
      phd: 6,
    };

    roleSelect.addEventListener('change', function () {
      const role = this.value;

      if (role === 'student') {
        studentFields.classList.remove('hidden');
        facultyFields.classList.add('hidden');

        Array.from(studentFields.querySelectorAll('input, select')).forEach(el => {
          el.required = true;
        });
        Array.from(facultyFields.querySelectorAll('input, select')).forEach(el => {
          el.required = false;
          el.value = '';
        });
      } else if (role === 'faculty') {
        facultyFields.classList.remove('hidden');
        studentFields.classList.add('hidden');

        Array.from(facultyFields.querySelectorAll('input, select')).forEach(el => {
          el.required = true;
        });
        Array.from(studentFields.querySelectorAll('input, select')).forEach(el => {
          el.required = false;
          el.value = '';
        });
        semesterSelect.innerHTML = '<option value="">-- Select Semester --</option>';
      } else {
        studentFields.classList.add('hidden');
        facultyFields.classList.add('hidden');

        Array.from(studentFields.querySelectorAll('input, select')).forEach(el => el.required = false);
        Array.from(facultyFields.querySelectorAll('input, select')).forEach(el => el.required = false);
      }
    });

    courseSelect.addEventListener('change', function () {
      const course = this.value;
      semesterSelect.innerHTML = '<option value="">-- Select Semester --</option>';

      if (semestersByCourse[course]) {
        const maxSem = semestersByCourse[course];
        for (let i = 1; i <= maxSem; i++) {
          const option = document.createElement('option');
          option.value = i;
          option.textContent = `Semester ${i}`;
          semesterSelect.appendChild(option);
        }
      }
    });
  </script>

</body>
</html>