<?php
// index.php — Exam Management System homepage

// Optional: You can later add PHP logic here.
// Example: session_start(); if user already logged in, redirect to dashboard, etc.
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Exam Management System</title>
  <style>
  * {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  }

  body {
    background-color: #f8f9fa;
    color: #333;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 20px;
    padding-bottom: 60px;
  }

  .container {
    background: #fff;
    width: 100%;
    max-width: 400px;
    padding: 40px 30px;
    border-radius: 15px;
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
    text-align: center;
    transition: transform 0.3s ease;
  }

  .container:hover {
    transform: scale(1.03);
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.15);
  }

  .logo {
    width: 80px;
    margin: 0 auto 25px;
    filter: drop-shadow(0 2px 3px rgba(0, 0, 0, 0.1));
  }

  h1 {
    font-size: 2.3rem;
    margin-bottom: 10px;
    color: #1a73e8;
    font-weight: 800;
    letter-spacing: 1px;
  }

  p.tagline {
    font-size: 1.1rem;
    color: #555;
    margin-bottom: 35px;
    font-style: italic;
    font-weight: 600;
  }

  .btn-group {
    display: flex;
    flex-direction: column;
    gap: 18px;
  }

  .btn {
    padding: 14px 25px;
    font-size: 1.1rem;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    color: white;
    font-weight: 700;
    transition: background-color 0.3s ease;
    box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
  }

  .btn-admin {
    background-color: #1a73e8;
  }

  .btn-admin:hover {
    background-color: #155ab6;
  }

  .btn-student {
    background-color: #34a853;
  }

  .btn-student:hover {
    background-color: #2c8c42;
  }

  .btn-faculty {
    background-color: #fbbc05;
    color: #333;
  }

  .btn-faculty:hover {
    background-color: #c99704;
    color: white;
  }

  .back-link {
    display: inline-block;
    margin-top: 10px;
    color: #1a73e8;
    text-decoration: none;
    font-weight: 600;
  }

  .back-link:hover {
    text-decoration: underline;
  }

  .support-info {
    margin: 30px 0;
    font-size: 1rem;
    font-weight: bold;
    color: #003366;
    text-align: center;
  }

  .support-info a {
    color: #1a73e8;
    text-decoration: underline;
  }

  footer {
    margin-top: 30px;
    font-size: 0.85rem;
    color: #666;
    font-weight: 600;
    text-align: center;
  }

  .scrolling-wrapper {
    position: fixed;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 40px;
    background-color: #ffeeba;
    overflow: hidden;
    z-index: 1000;
    border-top: 2px solid #ffc107;
  }

  .scrolling-content {
    display: inline-block;
    white-space: nowrap;
    padding-left: 100%;
    animation: scrollText 20s linear infinite;
  }

  .important-bottom {
    display: inline-block;
    white-space: nowrap;
    padding: 10px 0;
    font-size: 1rem;
    font-weight: bold;
    color: red;
    font-family: 'Georgia', serif;
  }

  @keyframes scrollText {
    0% { transform: translateX(0%); }
    100% { transform: translateX(-100%); }
  }

  @media (max-width: 768px) {
    .container {
      width: 90%;
      padding: 30px 20px;
    }

    h1 {
      font-size: 2rem;
    }

    .btn {
      font-size: 1rem;
    }

    .support-info {
      font-size: 0.95rem;
    }

    .important-bottom {
      font-size: 0.95rem;
    }
  }

  @media (max-width: 480px) {
    h1 {
      font-size: 1.8rem;
    }

    .btn {
      font-size: 0.95rem;
      padding: 12px 20px;
    }

    .support-info,
    .important-bottom {
      font-size: 0.9rem;
    }
  }
</style>
</head>
<body>

  <div class="container">
    <img src="https://upload.wikimedia.org/wikipedia/en/5/5f/Indian_Institute_of_Information_Technology_Design_and_Manufacturing%2C_Kurnool_logo.png" alt="Institution Logo" class="logo" />

    <h1>Exam Management System</h1>
    <p class="tagline">Efficiently managing exams and results</p>

    <div class="btn-group">
      <button class="btn btn-admin" onclick="location.href='http://localhost/Exam_Management_System/Admin/admin_login.php'">Admin Login</button>
      <button class="btn btn-faculty" onclick="location.href='http://localhost/Exam_Management_System/Faculty/faculty_login.php'">Faculty Login</button>
      <button class="btn btn-student" onclick="location.href='http://localhost/Exam_Management_System/Student/student_login.php'">Student Login</button>
      <a href="http://localhost/Exam_Management_System/new_registration/registration_page.php" class="back-link">New Registration</a>
    </div>

    <div class="support-info">
      📢 For assistance, contact support at 
      <a href="mailto:support@iiitdmk.ac.in">support@iiitdmk.ac.in</a><br />
      📞 Helpdesk: +91-12345-67890
    </div>

    <footer>&copy; 2025 INDIAN INSTITUTE OF INFORMATION TECHNOLOGY DESIGN AND MANUFACTURING KURNOOL</footer>
  </div>

  <div class="scrolling-wrapper">
    <div class="scrolling-content">
      <span class="important-bottom">
        ⚠️ Exam registration deadline: August 15, 2025. Please complete your registration on time!
      </span>
    </div>
  </div>

</body>
</html>
