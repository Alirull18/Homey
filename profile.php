<?php
session_start();
require_once 'php/db_connect.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];
$email = $_SESSION['email'];

$success_msg = '';
$error_msg = '';

// // Kemaskini maklumat profil pengguna apabila form dihantar via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $age = (int) ($_POST['age'] ?? 0);
  $gender = $_POST['gender'] ?? 'Male';
  $weight = (float) ($_POST['weight'] ?? 0);
  $height = (float) ($_POST['height'] ?? 0);
  $activity = $_POST['activity'] ?? 'Lightly active';
  $goal = $_POST['goal_type'] ?? 'Deficit';

  if ($age <= 0 || $weight <= 0 || $height <= 0) {
    $error_msg = "Please enter valid measurements for age, height, and weight.";
  } else {
    $q_update = "UPDATE users SET 
                     age = $age, 
                     gender = '$gender', 
                     weight_kg = $weight, 
                     height_cm = $height, 
                     activity_level = '$activity', 
                     goal_type = '$goal' 
                     WHERE user_id = '$user_id'";

    if (mysqli_query($conn, $q_update)) {
      $success_msg = "Goal and health profile updated successfully!";
    } else {
      $error_msg = "Failed to update profile: " . mysqli_error($conn);
    }
  }
}

// // Ambil maklumat profil terkini pengguna
$q_user = "SELECT age, gender, weight_kg, height_cm, activity_level, goal_type FROM users WHERE user_id = '$user_id'";
$res_user = mysqli_query($conn, $q_user);

$is_new_user = true;
$age = 0;
$gender = 'Male';
$weight_kg = 0;
$height_cm = 0;
$activity_level = 'Lightly active (1–3x/week)';
$goal_type = 'Deficit';

if ($res_user && mysqli_num_rows($res_user) > 0) {
  $row_u = mysqli_fetch_array($res_user);
  if ($row_u['weight_kg'] !== null && $row_u['height_cm'] !== null && $row_u['age'] !== null) {
    $is_new_user = false;
    $age = (int) $row_u['age'];
    $gender = $row_u['gender'] ? $row_u['gender'] : 'Male';
    $weight_kg = (float) $row_u['weight_kg'];
    $height_cm = (float) $row_u['height_cm'];
    $activity_level = $row_u['activity_level'] ? $row_u['activity_level'] : 'Lightly active (1–3x/week)';
    $goal_type = $row_u['goal_type'] ? $row_u['goal_type'] : 'Deficit';
  }
  mysqli_free_result($res_user);
}

// // Hitung BMR & TDEE 
if ($is_new_user) {
  $bmr = 0;
  $tdee = 0;
  $targetKcal = 0;
  $bmi = 0;
  $bmi_status = 'Pending Setup';
} else {
  $bmr = ($gender === 'Male') ? (10 * $weight_kg + 6.25 * $height_cm - 5 * $age + 5) : (10 * $weight_kg + 6.25 * $height_cm - 5 * $age - 161);
  $multiplier = 1.375;
  if (strpos($activity_level, 'Sedentary') !== false)
    $multiplier = 1.2;
  elseif (strpos($activity_level, 'Lightly') !== false)
    $multiplier = 1.375;
  elseif (strpos($activity_level, 'Moderately') !== false)
    $multiplier = 1.55;
  elseif (strpos($activity_level, 'Very') !== false)
    $multiplier = 1.725;

  $tdee = round($bmr * $multiplier);
  if ($goal_type === 'Deficit')
    $targetKcal = $tdee - 400;
  elseif ($goal_type === 'Surplus')
    $targetKcal = $tdee + 300;
  else
    $targetKcal = $tdee;

  // // Kira klasifikasi BMI pengguna
  $height_m = $height_cm / 100;
  $bmi = $height_m > 0 ? round($weight_kg / ($height_m * $height_m), 1) : 0;
  $bmi_status = 'Normal';
  if ($bmi < 18.5)
    $bmi_status = 'Underweight';
  elseif ($bmi >= 25 && $bmi < 30)
    $bmi_status = 'Overweight';
  elseif ($bmi >= 30)
    $bmi_status = 'Obese';
}

// // Dapatkan jumlah hari makanan telah direkodkan
$q_days = "SELECT COUNT(DISTINCT log_date) as c FROM meal_logs WHERE user_id = '$user_id'";
$res_days = mysqli_query($conn, $q_days);
$days_tracked = 0;
if ($res_days) {
  $row_d = mysqli_fetch_array($res_days);
  $days_tracked = $row_d['c'];
  mysqli_free_result($res_days);
}

// // Dapatkan jumlah resipi yang telah dikongsi oleh pengguna
$q_shared = "SELECT COUNT(*) as c FROM recipes WHERE author_id = '$user_id'";
$res_shared = mysqli_query($conn, $q_shared);
$recipes_shared = 0;
if ($res_shared) {
  $row_s = mysqli_fetch_array($res_shared);
  $recipes_shared = $row_s['c'];
  mysqli_free_result($res_shared);
}

$active_page = 'profile';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Profile – Homey</title>
  <link rel="stylesheet" href="css/style.css">
  <script src="js/main.js"></script>
</head>

<body>
  <div id="app">
    <!-- //  (SIDEBAR) -->
    <?php include 'php/sidebar.php'; ?>

    <!-- // BAHAGIAN UTAMA HALAMAN PROFIL -->
    <div id="main">
      <div id="topbar">
        <div>
          <div class="tb-title">My Profile</div>
          <div class="tb-sub">Configure your weight targets and fitness settings</div>
        </div>
        <div class="tb-right">
          <div class="avatar"><?php echo htmlspecialchars($sidebar_initials); ?></div>
          <span style="font-size:13px;color:var(--gray500)"><?php echo htmlspecialchars($full_name); ?></span>
        </div>
      </div>

      <div id="content">
        <div class="page">
          <?php if ($success_msg): ?>
            <div
              style="background:#dcfce7; color:#166534; padding:12px; border-radius:8px; margin-bottom:16px; font-size:14px; font-weight:600">
              🎉 <?php echo htmlspecialchars($success_msg); ?>
            </div>
          <?php endif; ?>
          <?php if ($error_msg): ?>
            <div
              style="background:#fee2e2; color:#dc2626; padding:12px; border-radius:8px; margin-bottom:16px; font-size:14px; font-weight:600">
              ❌ <?php echo htmlspecialchars($error_msg); ?>
            </div>
          <?php endif; ?>

          <!-- // BAHAGIAN PROFIL PENGGUNA -->
          <div
            style="display:flex;gap:14px;align-items:center;background:var(--white);border-radius:var(--radius);box-shadow:var(--shadow);padding:18px 20px;margin-bottom:16px">
            <div
              style="width:54px;height:54px;border-radius:50%;background:var(--g100);display:flex;align-items:center;justify-content:center;font-family:var(--font-display);font-size:18px;font-weight:800;color:var(--g700)">
              <?php echo htmlspecialchars($sidebar_initials); ?></div>
            <div>
              <div style="font-size:17px;font-weight:700;font-family:var(--font-display)">
                <?php echo htmlspecialchars($full_name); ?></div>
              <div style="font-size:13px;color:var(--gray400)"><?php echo htmlspecialchars($email); ?></div>
              <span class="tag tag-g"
                style="margin-top:4px;display:inline-flex"><?php echo htmlspecialchars($sidebar_role); ?> Plan</span>
            </div>
          </div>

          <div class="g3" style="margin-bottom:16px">
            <div class="card" style="text-align:center">
              <div class="metric-lbl">Days tracked</div>
              <div class="metric-val"><?php echo $days_tracked; ?></div>
            </div>
            <div class="card" style="text-align:center">
              <div class="metric-lbl">TDEE Target</div>
              <div class="metric-val"><?php echo $targetKcal; ?> <small>kcal</small></div>
            </div>
            <div class="card" style="text-align:center">
              <div class="metric-lbl">Recipes shared</div>
              <div class="metric-val"><?php echo $recipes_shared; ?></div>
            </div>
          </div>

          <div style="max-width: 700px; margin: 0 auto">
            <div class="col">
              <!-- // KEMASKINI SASARAN DIET -->
              <div class="card">
                <div class="card-title">Setup / Update diet goal</div>

                <form name="profileForm" method="POST" action="profile.php" onsubmit="return validateProfileForm()">
                  <div class="form-grid" style="margin-bottom:10px">
                    <div class="form-group">
                      <label class="form-label">Age</label>
                      <input name="age" class="form-input" type="number" value="<?php echo $is_new_user ? '' : $age; ?>"
                        placeholder="e.g. 24" required min="1" max="120">
                    </div>
                    <div class="form-group">
                      <label class="form-label">Gender</label>
                      <select name="gender" class="form-input form-select">
                        <option value="Male" <?php echo ($gender === 'Male') ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo ($gender === 'Female') ? 'selected' : ''; ?>>Female</option>
                      </select>
                    </div>
                    <div class="form-group">
                      <label class="form-label">Weight (kg)</label>
                      <input name="weight" class="form-input" type="number" step="0.1"
                        value="<?php echo $is_new_user ? '' : $weight_kg; ?>" placeholder="e.g. 75" required min="1">
                    </div>
                    <div class="form-group">
                      <label class="form-label">Height (cm)</label>
                      <input name="height" class="form-input" type="number" step="0.1"
                        value="<?php echo $is_new_user ? '' : $height_cm; ?>" placeholder="e.g. 172" required min="1">
                    </div>
                  </div>

                  <div class="form-group" style="margin-bottom:10px">
                    <label class="form-label">Activity level</label>
                    <select name="activity" class="form-input form-select">
                      <option value="Sedentary (desk job)" <?php echo (strpos($activity_level, 'Sedentary') !== false) ? 'selected' : ''; ?>>Sedentary (desk job)</option>
                      <option value="Lightly active (1–3x/week)" <?php echo (strpos($activity_level, 'Lightly') !== false) ? 'selected' : ''; ?>>Lightly active (1–3x/week)</option>
                      <option value="Moderately active (3–5x/week)" <?php echo (strpos($activity_level, 'Moderately') !== false) ? 'selected' : ''; ?>>Moderately active (3–5x/week)</option>
                      <option value="Very active (6–7x/week)" <?php echo (strpos($activity_level, 'Very') !== false) ? 'selected' : ''; ?>>Very active (6–7x/week)</option>
                    </select>
                  </div>

                  <div class="form-group" style="margin-bottom:12px">
                    <label class="form-label">Goal type</label>
                    <select name="goal_type" class="form-input form-select">
                      <option value="Deficit" <?php echo ($goal_type === 'Deficit') ? 'selected' : ''; ?>>Calorie Deficit
                        (Weight Loss)</option>
                      <option value="Surplus" <?php echo ($goal_type === 'Surplus') ? 'selected' : ''; ?>>Calorie Surplus
                        (Weight Gain)</option>
                      <option value="Maintain" <?php echo ($goal_type === 'Maintain') ? 'selected' : ''; ?>>Maintain
                        Weight</option>
                    </select>
                  </div>

                  <div class="alert alert-b" style="margin-bottom:12px">
                    Calculated TDEE: <strong><?php echo $tdee; ?> kcal/day</strong> &middot; Recommended target:
                    <strong><?php echo $targetKcal; ?> kcal/day</strong>
                  </div>

                  <button type="submit" class="btn btn-primary btn-full" style="font-weight:700">Save profile &
                    recalculate goal</button>
                </form>
              </div>

              <!-- // BAHAGIAN  BMI PENGGUNA -->
              <div class="card">
                <div class="card-title">Body Mass Index (BMI)</div>
                <div class="form-grid">
                  <div class="form-group">
                    <label class="form-label">BMI Score</label>
                    <input class="form-input" value="<?php echo $bmi; ?>" readonly>
                  </div>
                  <div class="form-group">
                    <label class="form-label">Classification</label>
                    <input class="form-input" value="<?php echo htmlspecialchars($bmi_status); ?>" readonly>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>

</html>