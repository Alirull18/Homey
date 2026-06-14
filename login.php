<?php
session_start();
// // Hubungkan ke pangkalan data (database) menggunakan cara prosedural
require_once 'php/db_connect.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';

  if (empty($email) || empty($password)) {
    $error = "Please enter both email and password.";
  } else {
    // // Dapatkan rekod pengguna berdasarkan email menggunakan MySQLi prosedural
    $q_select = "SELECT user_id, full_name, role, password FROM users WHERE email = '$email'";
    $res_select = mysqli_query($conn, $q_select);

    if (mysqli_num_rows($res_select) === 1) {
      $row = mysqli_fetch_array($res_select);

      // // Bandingkan password teks biasa secara langsung (tanpa hashing)
      if ($row['password'] === $password) {
        // // Sekiranya padan, mulakan session pengguna
        $_SESSION['user_id'] = $row['user_id'];
        $_SESSION['full_name'] = $row['full_name'];
        $_SESSION['email'] = $email;
        $_SESSION['role'] = $row['role'];

        header("Location: dashboard.php");
        exit;
      } else {
        $error = "Invalid email or password.";
      }
    } else {
      $error = "Invalid email or password.";
    }
    mysqli_free_result($res_select);
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login – Homey</title>
  <link rel="stylesheet" href="css/style.css">
  <style>
    body {
      background: var(--bg);
      margin: 0;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }

    .auth-container {
      background: var(--white);
      width: 100%;
      max-width: 400px;
      padding: 40px;
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      text-align: center;
    }

    .auth-logo {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 56px;
      height: 56px;
      background: var(--g500);
      color: white;
      border-radius: 14px;
      font-size: 28px;
      margin-bottom: 20px;
    }

    .auth-header {
      font-size: 26px;
      font-weight: 700;
      color: #333;
      margin-bottom: 8px;
    }

    .auth-sub {
      font-size: 14px;
      color: var(--gray500);
      margin-bottom: 30px;
    }

    .auth-form {
      text-align: left;
    }

    .auth-form .form-group {
      margin-bottom: 20px;
    }

    .auth-form .form-label {
      font-size: 13px;
      display: block;
      margin-bottom: 6px;
    }

    .auth-form .form-input {
      width: 100%;
      padding: 12px 14px;
      font-size: 14px;
      border-radius: 8px;
      background: var(--gray50);
      border: 1px solid var(--gray200);
    }

    .auth-form .form-input:focus {
      border-color: var(--g500);
      outline: none;
    }

    .btn-login {
      padding: 14px;
      font-size: 15px;
      font-weight: 600;
      border-radius: 8px;
      margin-top: 10px;
    }

    .auth-footer {
      margin-top: 24px;
      font-size: 13.5px;
      color: var(--gray600);
    }

    .auth-footer a {
      color: var(--g600);
      font-weight: 600;
      text-decoration: none;
    }

    .auth-footer a:hover {
      text-decoration: underline;
    }
  </style>
</head>

<body>
  <div class="auth-container">
    <h1 class="auth-header">Welcome to Homey</h1>
    <p class="auth-sub">Enter your details to sign in.</p>

    <form class="auth-form" method="POST" action="login.php">
      <?php if ($error): ?>
        <div
          style="background: #fee2e2; color: #dc2626; padding: 12px; border-radius: 8px; margin-bottom: 16px; font-size: 14px;">
          <?php echo htmlspecialchars($error); ?>
        </div>
      <?php endif; ?>
      <div class="form-group">
        <label class="form-label">Email address</label>
        <input type="email" name="email" class="form-input" placeholder="Enter your email" required
          value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
      </div>

      <div class="form-group">
        <label class="form-label" style="display:flex; justify-content:space-between;">
          <span>Password</span>
        </label>
        <input type="password" name="password" class="form-input" placeholder="••••••••" required>
      </div>

      <button type="submit" class="btn btn-primary btn-full btn-login">Sign in</button>
    </form>

    <div class="auth-footer">
      Don't have an account? <a href="signup.php">Sign Up here</a>
    </div>
  </div>
</body>

</html>