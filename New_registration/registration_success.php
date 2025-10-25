<?php
// Start session if needed
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Registration Successful</title>
<style>
body {
  background: linear-gradient(135deg, #d4edda, #ffffff);
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  margin: 0;
  padding: 20px;
}

.success-container {
  background: #fff;
  padding: 35px 25px;
  border-radius: 15px;
  max-width: 420px;
  width: 100%;
  text-align: center;
  box-shadow: 0 15px 35px rgba(0,0,0,0.12);
  opacity: 0;
  transform: translateY(25px);
  animation: fadeInUp 0.8s ease forwards;
}

.success-icon {
  font-size: 65px;
  color: #28a745;
  margin-bottom: 20px;
  animation: bouncePulse 1.5s infinite;
  transform-origin: center;
}

h1 {
  color: #28a745;
  font-weight: 700;
  margin-bottom: 10px;
  font-size: 1.8rem;
  opacity: 0;
  animation: fadeInText 1.2s ease forwards;
}

p {
  font-size: 1rem;
  color: #333;
  margin-bottom: 30px;
  opacity: 0;
  animation: fadeInText 1.4s ease forwards;
}

a {
  display: inline-block;
  padding: 12px 28px;
  background-color: #28a745;
  color: #fff;
  font-weight: 600;
  text-decoration: none;
  border-radius: 10px;
  transition: 0.3s ease;
  opacity: 0;
  animation: fadeInText 1.6s ease forwards;
}

a:hover {
  background-color: #218838;
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
  0%, 100% { transform: scale(1); color: #28a745; }
  50% { transform: scale(1.15); color: #1e7e34; }
}

@keyframes fadeInText {
  to { opacity: 1; }
}

/* Responsive */
@media (max-width: 480px) {
  .success-container {
    padding: 25px 15px;
  }
  h1 { font-size: 1.5rem; }
  .success-icon { font-size: 50px; }
  a { padding: 10px 22px; }
}
</style>
</head>
<body>

<div class="success-container">
  <div class="success-icon" aria-label="Success checkmark" role="img">✔️</div>
  <h1>Registration Successful!</h1>
  <p>Thank you for registering. Your details have been saved successfully.</p>
  <a href="Ems_start/frontpage.php">Go To Home</a>
</div>

</body>
</html>
