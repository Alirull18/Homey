<?php
session_start();
require_once 'php/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];

// // 1. Ambil data profil pengguna dari database
$q_user = "SELECT age, gender, weight_kg, height_cm, activity_level, goal_type FROM users WHERE user_id = '$user_id'";
$res_user = mysqli_query($conn, $q_user);

$is_new_user = true;
$age = 0; $gender = 'Male'; $weight_kg = 0; $height_cm = 0; $activity_level = 'Lightly active (1–3x/week)'; $goal_type = 'Deficit';

if ($res_user && mysqli_num_rows($res_user) > 0) {
    $row_u = mysqli_fetch_array($res_user);
    if ($row_u['weight_kg'] !== null && $row_u['height_cm'] !== null && $row_u['age'] !== null) {
        $is_new_user = false;
        $age = (int)$row_u['age'];
        $gender = $row_u['gender'] ? $row_u['gender'] : 'Male';
        $weight_kg = (float)$row_u['weight_kg'];
        $height_cm = (float)$row_u['height_cm'];
        $activity_level = $row_u['activity_level'] ? $row_u['activity_level'] : 'Lightly active (1–3x/week)';
        $goal_type = $row_u['goal_type'] ? $row_u['goal_type'] : 'Deficit';
    }
    mysqli_free_result($res_user);
}

// // Kira semula TDEE & sasaran kalori menggunakan formula Mifflin-St Jeor
if ($is_new_user) {
    $bmr = 0;
    $tdee = 0;
    $targetKcal = 0;
    $goal_type_display = 'Not Configured';
} else {
    $bmr = ($gender === 'Male') ? (10 * $weight_kg + 6.25 * $height_cm - 5 * $age + 5) : (10 * $weight_kg + 6.25 * $height_cm - 5 * $age - 161);
    $multiplier = 1.375;
    if (strpos($activity_level, 'Sedentary') !== false) $multiplier = 1.2;
    elseif (strpos($activity_level, 'Lightly') !== false) $multiplier = 1.375;
    elseif (strpos($activity_level, 'Moderately') !== false) $multiplier = 1.55;
    elseif (strpos($activity_level, 'Very') !== false) $multiplier = 1.725;

    $tdee = round($bmr * $multiplier);
    if ($goal_type === 'Deficit') $targetKcal = $tdee - 400;
    elseif ($goal_type === 'Surplus') $targetKcal = $tdee + 300;
    else $targetKcal = $tdee;
    
    $goal_type_display = $goal_type;
}

// // 2. Dapatkan rekod makanan pengguna untuk hari ini
$today = date('Y-m-d');
$q_meals = "SELECT log_id, meal_name, meal_type, log_time, calories FROM meal_logs WHERE user_id = '$user_id' AND log_date = '$today' ORDER BY log_time ASC";
$res_meals = mysqli_query($conn, $q_meals);
$meals = [];
$totalKcal = 0;
if ($res_meals) {
    while ($row = mysqli_fetch_array($res_meals)) {
        $meals[] = $row;
        $totalKcal += $row['calories'];
    }
    mysqli_free_result($res_meals);
}

$remKcal = $targetKcal - $totalKcal;
if ($remKcal < 0) $remKcal = 0;
$kcalPct = $targetKcal > 0 ? round(($totalKcal / $targetKcal) * 100) : 0;
if ($kcalPct > 100) $kcalPct = 100;

// // 3. Dapatkan rekod air minuman pengguna untuk hari ini
$cupsCount = 0;
$q_hydro = "SELECT cups_drank FROM hydration_logs WHERE user_id = '$user_id' AND log_date = '$today'";
$res_hydro = mysqli_query($conn, $q_hydro);
if ($res_hydro && mysqli_num_rows($res_hydro) > 0) {
    $row_h = mysqli_fetch_array($res_hydro);
    $cupsCount = $row_h['cups_drank'];
    mysqli_free_result($res_hydro);
}
$waterLiters = $cupsCount * 0.25;
$waterPct = round(($cupsCount / 10) * 100);
if ($waterPct > 100) $waterPct = 100;

// // Set menu aktif untuk paparan di sidebar
$active_page = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Homey – Smart Recipe & Food Suggestion System</title>
  <link rel="stylesheet" href="css/style.css">
</head>

<body>
  <div id="app">
    <!-- // NAVIGASI SISI (SIDEBAR) -->
    <?php include 'php/sidebar.php'; ?>

    <!-- // BAHAGIAN UTAMA HALAMAN DASHBOARD -->
    <div id="main">
      <div id="topbar">
        <div>
          <div class="tb-title">Dashboard</div>
          <div class="tb-sub"><?php echo date('l, d F Y'); ?></div>
        </div>
        <div class="tb-right">
          <div class="avatar"><?php echo htmlspecialchars($sidebar_initials); ?></div>
          <span style="font-size:13px;color:var(--gray500)"><?php echo htmlspecialchars($full_name); ?></span>
        </div>
      </div>

      <div id="content">
        <div class="page">
          <!-- // PAPARAN MAKLUMAT MATLAMAT KALORI -->
          <div class="goal-banner">
            <div style="display:flex;align-items:center;gap:12px">
              <div class="goal-icon">🎯</div>
              <div>
                <div class="goal-title">Calorie <?php echo htmlspecialchars($goal_type_display); ?> Active — <?php echo $targetKcal; ?> kcal/day</div>
                <div class="goal-sub"><?php echo $remKcal; ?> kcal remaining today · TDEE: <?php echo $tdee; ?> kcal</div>
              </div>
            </div>
            <span class="tag tag-g">Active Goal</span>
          </div>

          <!-- // BAHAGIAN KAD STATISTIK UTAMA -->
          <div class="g4" style="margin-bottom:16px">
            <div class="card">
              <div class="metric-lbl">Today's intake</div>
              <div class="metric-val"><?php echo $totalKcal; ?> <small>kcal</small></div>
              <div class="bar-wrap"><div class="bar bar-g" style="width:<?php echo $kcalPct; ?>%"></div></div>
              <div class="metric-sub"><?php echo $remKcal; ?> kcal remaining</div>
            </div>
            
            <div class="card">
              <div class="metric-lbl">Water today</div>
              <div class="metric-val" style="color:var(--b500)"><?php echo number_format($waterLiters, 1); ?> <small>L</small></div>
              <div class="bar-wrap"><div class="bar bar-b" style="width:<?php echo $waterPct; ?>%"></div></div>
              <div class="metric-sub">Goal: 2.5 L (10 Cups)</div>
            </div>
            
            <div class="card">
              <div class="metric-lbl">Daily target</div>
              <div class="metric-val"><?php echo $targetKcal; ?> <small>kcal</small></div>
              <div class="bar-wrap"><div class="bar bar-g" style="width:<?php echo $is_new_user ? '0' : '100'; ?>%"></div></div>
              <div class="metric-sub"><?php echo $is_new_user ? 'Setup required' : 'Based on Mifflin-St Jeor'; ?></div>
            </div>
            
            <div class="card">
              <div class="metric-lbl">Meals logged</div>
              <div class="metric-val"><?php echo count($meals); ?> <small>/ 4</small></div>
              <div class="bar-wrap"><div class="bar bar-a" style="width:<?php echo min(100, count($meals)*25); ?>%"></div></div>
              <div class="metric-sub">Target: 3 meals + 1 snack</div>
            </div>
          </div>

          <!-- // SENARAI LOG MAKANAN PENGGUNA HARI INI -->
          <div class="card" style="margin-bottom:16px">
            <div class="card-title">Today's meal log <span class="card-link"><a href="calories.php" style="color:inherit;text-decoration:none">+ Log</a></span></div>
            <div style="display:flex;flex-direction:column;gap:6px">
              <?php if (count($meals) === 0): ?>
                <p style="color:var(--gray400);font-size:13px;padding:10px 0;margin:0">No meals logged for today yet.</p>
              <?php else: ?>
                <?php foreach ($meals as $m): ?>
                  <div class="entry" style="display:flex; justify-content:space-between; align-items:center">
                    <div>
                      <div class="en" style="font-weight:700; color:var(--gray800); margin-bottom:2px"><?php echo htmlspecialchars($m['meal_name']); ?></div>
                      <div class="es" style="font-size:11px; color:var(--gray400)"><?php echo htmlspecialchars($m['meal_type']); ?> · <?php echo date('h:i a', strtotime($m['log_time'])); ?></div>
                    </div>
                    <div class="ec" style="font-weight:700; color:var(--g600)"><?php echo $m['calories']; ?> kcal</div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
              <a href="calories.php" class="btn btn-secondary btn-full" style="margin-top:4px; text-align:center; text-decoration:none; font-weight:700">+ Go to calorie tracker</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>

</html>
