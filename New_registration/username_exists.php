<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Login Alert</title>
<style>
body {
  background: linear-gradient(135deg, #f8d7da, #ffffff);
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  margin: 0;
  padding: 20px;
}

.alert-container {
  background: #fff;
  padding: 35px 25px;
  border-radius: 15px;
  max-width: 400px;
  width: 100%;
  text-align: center;
  box-shadow: 0 15px 35px rgba(0,0,0,0.12);
  opacity: 0;
  transform: translateY(25px);
  animation: fadeInUp 0.8s ease forwards;
}

.alert-icon {
  font-size: 60px;
  color: #e63946;
  margin-bottom: 20px;
  animation: bouncePulse 1.5s infinite;
}

h3 {
  color: #e63946;
  font-weight: 700;
  margin-bottom: 15px;
  font-size: 1.6rem;
  opacity: 0;
  animation: fadeInText 1.2s ease forwards;
}

p {
  font-size: 1rem;
  color: #333;
  margin-bottom: 25px;
  opacity: 0;
  animation: fadeInText 1.4s ease forwards;
}

a {
  display: inline-block;
  padding: 12px 25px;
  background-color: #e63946;
  color: white;
  text-decoration: none;
  font-weight: bold;
  border-radius: 8px;
  transition: background-color 0.3s ease, transform 0.3s ease;
  opacity: 0;
  animation: fadeInText 1.6s ease forwards;
}

a:hover {
  background-color: #c0392b;
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
  0%, 100% { transform: scale(1); color: #e63946; }
  50% { transform: scale(1.15); color: #c0392b; }
}

@keyframes fadeInText { to { opacity: 1; } }

/* Responsive */
@media (max-width: 480px) {
  .alert-container { padding: 25px 15px; }
  h3 { font-size: 1.4rem; }
  .alert-icon { font-size: 50px; }
  a { padding: 10px 22px; }
}
</style>
</head>
<body>

<div class="alert-container">
  <div class="alert-icon" aria-label="Warning icon" role="img">❌</div>
  <h3>Username Found!</h3>
  <p>Your username exists, please login with your credentials.</p>
  <a href="http://localhost/Exam_Management_System/Ems_start/frontpage.php">Go to Login</a>
</div>

</body>
</html>
