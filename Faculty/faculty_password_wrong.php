<?php
// Start session if you want to handle session messages (optional)
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Login Failed</title>
  <style>
/* Base styles */
body {
  background: linear-gradient(135deg, #f8d7da, #ffffff);
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100vh;
  margin: 0;
  padding: 20px;
  overflow: hidden;
}

.success-container {
  background: #fff;
  padding: 40px 30px;
  border-radius: 12px;
  box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
  max-width: 400px;
  width: 100%;
  text-align: center;
  animation: fadeInUp 0.8s ease forwards;
  opacity: 0;
  transform: translateY(20px);
  position: relative;
}

.success-icon {
  font-size: 60px;
  color: #dc3545; /* red for failure */
  margin-bottom: 20px;
  animation: bouncePulse 1.5s infinite;
  transform-origin: center;
}

h1 {
  color: #dc3545;
  margin-bottom: 10px;
  font-weight: 700;
  letter-spacing: 1px;
  animation: fadeInText 1.2s ease forwards;
  opacity: 0;
}

h3 {
  color: #333;
  margin-bottom: 30px;
  font-weight: 500;
  animation: fadeInText 1.4s ease forwards;
  opacity: 0;
}

a {
  display: inline-block;
  margin: 10px;
  padding: 12px 25px;
  background-color: #dc3545;
  color: white;
  text-decoration: none;
  font-weight: bold;
  border-radius: 8px;
  transition: background-color 0.3s ease, transform 0.3s ease;
  animation: fadeInText 1.6s ease forwards;
  opacity: 0;
}

a:hover {
  background-color: #c82333;
  transform: scale(1.05);
}

/* Animations */
@keyframes fadeInUp {
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes bouncePulse {
  0%, 100% {
    transform: scale(1);
    color: #dc3545;
    filter: drop-shadow(0 0 0 #dc3545);
  }
  50% {
    transform: scale(1.15);
    color: #a71d2a;
    filter: drop-shadow(0 0 10px #a71d2a);
  }
}

@keyframes fadeInText {
  to {
    opacity: 1;
  }
}

/* Responsive Design */
@media (max-width: 500px) {
  .success-container {
    padding: 30px 20px;
  }

  .success-icon {
    font-size: 45px;
    margin-bottom: 15px;
  }

  h1 {
    font-size: 1.5rem;
  }

  h3 {
    font-size: 1rem;
  }

  a {
    padding: 10px 20px;
    font-size: 0.9rem;
  }
}
</style>
</head>
<body>

  <div class="success-container">
    <div class="success-icon" aria-label="Failure icon" role="img">❌</div>
    <h1>Login Failed!</h1>
    <h3>Password Invalid!</h3>
    <a href="http://localhost/Exam_Management_System/Faculty/faculty_login.php">Go To Login Page</a>
  </div>

</body>
</html>
