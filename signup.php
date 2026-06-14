<?php
session_start();
// // Hubungkan ke pangkalan data (database) menggunakan cara prosedural
require_once 'php/db_connect.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $full_name = trim($_POST['full_name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';
  $confirm_password = $_POST['confirm_password'] ?? '';

  if (empty($full_name) || empty($email) || empty($password) || empty($confirm_password)) {
    $error = "Please fill in all fields.";
  } elseif ($password !== $confirm_password) {
    $error = "Passwords do not match.";
  } elseif (strlen($password) < 6) {
    $error = "Password must be at least 6 characters long.";
  } else {
    // // Semak sama ada email ini sudah didaftarkan sebelumnya
    $q_check = "SELECT user_id FROM users WHERE email = '$email'";
    $res_check = mysqli_query($conn, $q_check);

    if (mysqli_num_rows($res_check) > 0) {
      $error = "Email address is already registered.";
    } else {
      // // Masukkan rekod pengguna baru dengan password teks biasa (plain text)
      $q_insert = "INSERT INTO users (full_name, email, password, role) VALUES ('$full_name', '$email', '$password', 'User')";

      if (mysqli_query($conn, $q_insert)) {
        $success = "Registration successful! You can now sign in.";
      } else {
        $error = "Something went wrong: " . mysqli_error($conn);
      }
    }
    mysqli_free_result($res_check);
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign Up – Homey</title>
  <link rel="stylesheet" href="css/style.css">
  <script src="js/main.js"></script>
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
      max-width: 500px;
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
      margin-bottom: 16px;
    }

    .auth-form .form-label {
      font-size: 13px;
      display: block;
      margin-bottom: 6px;
      font-weight: 600;
    }

    .auth-form .form-input {
      width: 100%;
      padding: 10px 14px;
      font-size: 14px;
      border-radius: 8px;
      background: var(--gray50);
      border: 1px solid var(--gray200);
    }

    .auth-form .form-input:focus {
      border-color: var(--g500);
      outline: none;
    }

    .form-row {
      display: flex;
      gap: 16px;
    }

    .form-row .form-group {
      flex: 1;
    }

    .btn-signup {
      padding: 14px;
      font-size: 15px;
      font-weight: 600;
      border-radius: 8px;
      margin-top: 14px;
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
    <h1 class="auth-header">Create an Account</h1>
    <p class="auth-sub">Join Homey to start tracking your health today.</p>

    <form name="signupForm" class="auth-form" method="POST" action="signup.php" onsubmit="return validateSignupForm()">
      <?php if ($error): ?>
        <div
          style="background: #fee2e2; color: #dc2626; padding: 12px; border-radius: 8px; margin-bottom: 16px; font-size: 14px;">
          <?php echo htmlspecialchars($error); ?>
        </div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div
          style="background: #dcfce7; color: #166534; padding: 12px; border-radius: 8px; margin-bottom: 16px; font-size: 14px;">
          <?php echo htmlspecialchars($success); ?>
        </div>
      <?php endif; ?>

      <div class="form-group">
        <label class="form-label">Full Name</label>
        <input type="text" name="full_name" class="form-input" placeholder="e.g. Ahmad Rizal" required
          value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
      </div>

      <div class="form-group">
        <label class="form-label">Email address</label>
        <input type="email" name="email" class="form-input" placeholder="e.g. ahmad.rizal@email.com" required
          value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-input" placeholder="••••••••" required>
        </div>
        <div class="form-group">
          <label class="form-label">Confirm Password</label>
          <input type="password" name="confirm_password" class="form-input" placeholder="••••••••" required>
        </div>
      </div>

      <button type="submit" class="btn btn-primary btn-full btn-signup">Register Account</button>
    </form>

    <div class="auth-footer">
      Already have an account? <a href="login.php">Sign in here</a>
    </div>
  </div>
</body>

</html>