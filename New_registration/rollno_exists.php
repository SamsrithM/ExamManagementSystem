<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Login Failed</title>
  <style>
    /* Base styles */
    body {
      background: linear-gradient(135deg, #d4edda, #ffffff);
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
      text-align: center;
      animation: fadeInUp 0.8s ease forwards;
      opacity: 0;
      transform: translateY(20px);
      position: relative;
    }

    /* Checkmark icon with bounce and color pulse */
    .success-icon {
      font-size: 60px;
      color: #28a745;
      margin-bottom: 20px;
      animation: bouncePulse 1.5s infinite;
      transform-origin: center;
    }

    h1 {
      color: #28a745;
      margin-bottom: 10px;
      font-weight: 700;
      letter-spacing: 1px;
      animation: fadeInText 1.2s ease forwards;
      opacity: 0;
    }

    p {
      font-size: 1.1rem;
      color: #333;
      margin-bottom: 30px;
      animation: fadeInText 1.4s ease forwards;
      opacity: 0;
    }
    a {
    display: inline-block;
    margin: 10px;  /* ✅ This adds space between the two buttons */
    padding: 12px 25px;
    background-color: #129a1b; /* red color for failed state */
    color: white;
    text-decoration: none;
    font-weight: bold;
    border-radius: 8px;
    transition: background-color 0.3s ease, transform 0.3s ease;
    animation: fadeInText 1.6s ease forwards;
    opacity: 0;
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
      0%, 100% {
        transform: scale(1);
        color: #28a745;
        filter: drop-shadow(0 0 0 #28a745);
      }
      50% {
        transform: scale(1.15);
        color: #1e7e34;
        filter: drop-shadow(0 0 10px #1e7e34);
      }
    }

    @keyframes fadeInText {
      to {
        opacity: 1;
      }
    }
  </style>
</head>
<body>

  <div class="success-container">
    <div class="success-icon" aria-label="Success checkmark" role="img">✔️</div>
    <h3>Username Found!</h3>
    <a href="http://localhost/Exam_Management_System/Ems_start/frontpage.php">Please Login</a>
  </div>

</body>
</html>