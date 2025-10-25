<?php
// faculty_login.php — Faculty Login Page
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Faculty Login</title>
  <style>
* {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

body {
  background-color: #f1f2f6;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  padding: 20px;
}

.login-container {
  background: white;
  padding: 40px 30px;
  border-radius: 12px;
  box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
  width: 100%;
  max-width: 400px;
}

.header-logo {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  margin-bottom: 25px;
}

.header-logo img {
  width: 40px;
  height: 40px;
  object-fit: cover;
  border-radius: 50%;
}

h2 {
  color: #1a73e8;
  margin: 0;
}

label {
  display: block;
  margin-bottom: 8px;
  font-weight: 600;
}

.password-container {
  position: relative;
  width: 100%;
}

input[type="text"],
input[type="password"] {
  width: 100%;
  padding: 12px;
  margin-bottom: 20px;
  border: 1px solid #ccc;
  border-radius: 8px;
  font-size: 1rem;
}

.toggle-password {
  position: absolute;
  right: 12px;
  top: 12px;
  cursor: pointer;
  font-size: 1.2rem;
  color: #888;
}

.toggle-password:hover {
  color: #1a73e8;
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
  display: block;
  margin-top: 20px;
  text-align: center;
  color: #555;
  text-decoration: none;
  font-size: 0.95rem;
}

.back-link:hover {
  text-decoration: underline;
}

/* Responsive Design */
@media (max-width: 480px) {
  .login-container {
    padding: 30px 20px;
  }

  .header-logo img {
    width: 35px;
    height: 35px;
  }

  h2 {
    font-size: 1.3rem;
  }

  input[type="text"],
  input[type="password"] {
    padding: 10px;
    font-size: 0.95rem;
  }

  button {
    padding: 12px;
    font-size: 0.95rem;
  }

  .toggle-password {
    font-size: 1rem;
  }
}
</style>
</head>
<body>

  <div class="login-container">
    <div class="header-logo">
      <img src="https://media.istockphoto.com/id/1455786460/vector/the-person-giving-the-lecture-speech-silhouette-icon-vector.jpg?s=612x612&w=0&k=20&c=FXJxAXD0XsfnLGQE5ssBnwZ3NbrsgyUXspbx_FkaQds=" alt="Faculty Logo" />
      <h2>Faculty Login</h2>
    </div>

    <form action="faculty_login_action.php" method="POST">
      <label for="username">Faculty ID</label>
      <input type="text" id="username" name="username" required />

      <label for="password">Password</label>
      <div class="password-container">
        <input type="password" id="password" name="password" required />
        <span class="toggle-password" onclick="togglePassword()">👁️</span>
      </div>

      <button type="submit">Login</button>
      <a href="forget_password/password.php" class="back-link">Forgot Password?</a>
    </form>

    <a href="Ems_start/frontpage.php" class="back-link">← Back to Home</a>
  </div>

  <script>
    function togglePassword() {
      const passwordInput = document.getElementById("password");
      const toggleIcon = document.querySelector(".toggle-password");

      if (passwordInput.type === "password") {
        passwordInput.type = "text";
        toggleIcon.textContent = "🙈";
      } else {
        passwordInput.type = "password";
        toggleIcon.textContent = "👁️";
      }
    }
  </script>

</body>
</html>
