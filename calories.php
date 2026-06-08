<?php
session_start();
require_once 'php/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];
$today = date('Y-m-d');

$success_msg = '';
$error_msg = '';

// // Padam log makanan jika ada request POST dari pengguna
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $log_id = (int)($_POST['log_id'] ?? 0);
        
        $q_delete = "DELETE FROM meal_logs WHERE log_id = $log_id AND user_id = '$user_id'";
        if (mysqli_query($conn, $q_delete)) {
            $success_msg = "Meal deleted successfully!";
        } else {
            $error_msg = "Failed to delete meal: " . mysqli_error($conn);
        }
    }
}

// // 1. Dapatkan profil kesihatan pengguna dari database
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

// // Kira BMR dan TDEE mengikut formula Mifflin-St Jeor
if ($is_new_user) {
    $bmr = 0;
    $tdee = 0;
    $targetKcal = 0;
    $targetP = 0;
    $targetC = 0;
    $targetF = 0;
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

    // // Sasaran nutrisi makro (Protein: 30%, Karbohidrat: 40%, Lemak: 30%)
    $targetP = round($targetKcal * 0.3 / 4);
    $targetC = round($targetKcal * 0.4 / 4);
    $targetF = round($targetKcal * 0.3 / 9);
}

// // 2. Dapatkan log makanan yang telah didaftarkan hari ini
$q_meals = "SELECT log_id, meal_name, meal_type, log_time, calories, protein_g, carbs_g, fat_g FROM meal_logs WHERE user_id = '$user_id' AND log_date = '$today' ORDER BY log_time ASC";
$res_meals = mysqli_query($conn, $q_meals);
$meals = [];
$totalKcal = 0; $totP = 0; $totC = 0; $totF = 0;
if ($res_meals) {
    while ($row = mysqli_fetch_array($res_meals)) {
        $meals[] = $row;
        $totalKcal += $row['calories'];
        $totP += $row['protein_g'];
        $totC += $row['carbs_g'];
        $totF += $row['fat_g'];
    }
    mysqli_free_result($res_meals);
}

// // Kira peratusan pencapaian kalori dan makro semasa
$remKcal = $targetKcal - $totalKcal;
if ($remKcal < 0) $remKcal = 0;
$kcalPct = ($targetKcal > 0) ? min(100, round(($totalKcal / $targetKcal) * 100)) : 0;

$pctP = ($targetP > 0) ? min(100, round(($totP / $targetP) * 100)) : 0;
$pctC = ($targetC > 0) ? min(100, round(($totC / $targetC) * 100)) : 0;
$pctF = ($targetF > 0) ? min(100, round(($totF / $targetF) * 100)) : 0;

// // Tetapkan menu aktif di bahagian sidebar
$active_page = 'calories';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Calorie Tracker – Homey</title>
  <link rel="stylesheet" href="css/style.css">
</head>

<body>
  <div id="app">
    <!-- // NAVIGASI SISI (SIDEBAR) -->
    <?php include 'php/sidebar.php'; ?>

    <!-- // BAHAGIAN UTAMA HALAMAN KALORI -->
    <div id="main">
      <div id="topbar">
        <div>
          <div class="tb-title">Calorie Tracker</div>
          <div class="tb-sub">Monitor your macronutrients and daily calorie deficits</div>
        </div>
        <div class="tb-right">
          <div class="avatar"><?php echo htmlspecialchars($sidebar_initials); ?></div>
          <span style="font-size:13px;color:var(--gray500)"><?php echo htmlspecialchars($full_name); ?></span>
        </div>
      </div>

      <div id="content">
        <div class="page">
          <?php if ($success_msg): ?>
            <div style="background:#dcfce7; color:#166534; padding:12px; border-radius:8px; margin-bottom:16px; font-size:14px; font-weight:600">
              🎉 <?php echo htmlspecialchars($success_msg); ?>
            </div>
          <?php endif; ?>
          <?php if ($error_msg): ?>
            <div style="background:#fee2e2; color:#dc2626; padding:12px; border-radius:8px; margin-bottom:16px; font-size:14px; font-weight:600">
              ❌ <?php echo htmlspecialchars($error_msg); ?>
            </div>
          <?php endif; ?>

          <!-- // RINGKASAN HARI INI -->
          <div class="card" style="margin-bottom:16px">
            <div class="card-title">
              <span>Daily calorie summary</span>
              <a href="recipes.php" class="btn btn-primary btn-sm" style="text-decoration:none; font-weight:700">Browse Recipes</a>
            </div>
            <div class="g3" style="margin-bottom:12px">
              <div><div class="metric-lbl">Consumed</div><div class="metric-val" style="font-size:20px"><?php echo $totalKcal; ?> <small>kcal</small></div></div>
              <div><div class="metric-lbl">Daily Target</div><div class="metric-val" style="font-size:20px; color:var(--g600)"><?php echo $targetKcal; ?> <small>kcal</small></div></div>
              <div><div class="metric-lbl">Remaining</div><div class="metric-val" style="font-size:20px; color:var(--b600)"><?php echo $remKcal; ?> <small>kcal</small></div></div>
            </div>
            
            <div class="bar-wrap" style="height:12px; margin-bottom:16px"><div class="bar bar-g" style="width:<?php echo $kcalPct; ?>%"></div></div>

            <!-- // PROGRESS BAR UNTUK MAKRONUTRISI -->
            <div class="macro-row" style="margin-bottom:10px">
              <div class="macro-head" style="display:flex; justify-content:space-between; font-size:12px; font-weight:700; margin-bottom:4px">
                <span class="macro-name" style="color:var(--b600)">Protein (P)</span>
                <span class="macro-val"><?php echo $totP; ?>g / <?php echo $targetP; ?>g</span>
              </div>
              <div class="bar-wrap" style="height:8px"><div class="bar bar-b" style="width:<?php echo $pctP; ?>%"></div></div>
            </div>

            <div class="macro-row" style="margin-bottom:10px">
              <div class="macro-head" style="display:flex; justify-content:space-between; font-size:12px; font-weight:700; margin-bottom:4px">
                <span class="macro-name" style="color:var(--a600)">Carbohydrates (C)</span>
                <span class="macro-val"><?php echo $totC; ?>g / <?php echo $targetC; ?>g</span>
              </div>
              <div class="bar-wrap" style="height:8px"><div class="bar bar-a" style="width:<?php echo $pctC; ?>%"></div></div>
            </div>

            <div class="macro-row" style="margin-bottom:10px">
              <div class="macro-head" style="display:flex; justify-content:space-between; font-size:12px; font-weight:700; margin-bottom:4px">
                <span class="macro-name" style="color:var(--r500)">Fat (F)</span>
                <span class="macro-val"><?php echo $totF; ?>g / <?php echo $targetF; ?>g</span>
              </div>
              <div class="bar-wrap" style="height:8px"><div class="bar bar-r" style="width:<?php echo $pctF; ?>%"></div></div>
            </div>
          </div>

          <!-- // SENARAI LOG MAKANAN PENGGUNA UNTUK HARI INI -->
          <div class="card" style="margin-bottom:16px">
            <div class="card-title">Today's meal list</div>
            
            <?php if (count($meals) === 0): ?>
              <p style="color:var(--gray400); font-size:13px; text-align:center; padding:30px 0; border:2px dashed var(--gray100); border-radius:8px">
                No meals logged for today. Browse the recipes catalog to log a healthy meal!
              </p>
            <?php else: ?>
              <div style="display:flex; flex-direction:column; gap:8px">
                <?php foreach ($meals as $m): ?>
                  <div class="entry" style="display:flex; justify-content:space-between; align-items:center; padding:12px; border:1px solid var(--gray100); border-radius:8px">
                    <div style="flex:1">
                      <div class="en" style="font-weight:700; color:var(--gray800); margin-bottom:2px"><?php echo htmlspecialchars($m['meal_name']); ?></div>
                      <div class="es" style="font-size:12px; color:var(--gray400)">
                        Category: <?php echo htmlspecialchars($m['meal_type']); ?> &middot; Time: <?php echo date('h:i a', strtotime($m['log_time'])); ?> &middot; Macros: <strong>P:</strong><?php echo $m['protein_g']; ?>g <strong>C:</strong><?php echo $m['carbs_g']; ?>g <strong>F:</strong><?php echo $m['fat_g']; ?>g
                      </div>
                    </div>
                    <div style="display:flex; align-items:center; gap:12px">
                      <div class="ec" style="font-weight:700; color:var(--g600)"><?php echo $m['calories']; ?> kcal</div>
                      <form method="POST" action="calories.php" onsubmit="return confirm('Delete this meal log?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="log_id" value="<?php echo $m['log_id']; ?>">
                        <button type="submit" class="abtn abtn-r" style="border:none; background:none; cursor:pointer; font-size:14px; font-weight:700; color:var(--r500)">✕</button>
                      </form>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>


        </div>
      </div>
    </div>
  </div>
</body>

</html>
