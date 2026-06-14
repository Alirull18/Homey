<?php
session_start();
require_once 'php/db_connect.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];

$success_msg = '';
$error_msg = '';

// // Uruskan tindakan mendaftar log makanan daripada resipi terpilih via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  if ($action === 'log_recipe_meal') {
    $recipe_id = (int) $_POST['recipe_id'];
    $log_meal_type = $_POST['log_meal_type'] ?? 'Lunch';

    $q_fetch = "SELECT title, calories, instructions FROM recipes WHERE recipe_id = $recipe_id";
    $res_fetch = mysqli_query($conn, $q_fetch);

    if ($res_fetch && mysqli_num_rows($res_fetch) > 0) {
      $row_r = mysqli_fetch_array($res_fetch);
      $title = $row_r['title'];
      $calories = (int) $row_r['calories'];
      $instructions = $row_r['instructions'];
      mysqli_free_result($res_fetch);

      // // Kira sasaran makronutrisi standard (Protein: 30%, Karbohidrat: 40%, Lemak: 30%)
      $p = round($calories * 0.3 / 4);
      $c = round($calories * 0.4 / 4);
      $f = round($calories * 0.3 / 9);

      $today = date('Y-m-d');
      $log_time = date('H:i:s');

      $q_insert = "INSERT INTO meal_logs 
                         (user_id, meal_name, meal_type, calories, protein_g, carbs_g, fat_g, log_date, log_time) 
                         VALUES 
                         ('$user_id', '$title', '$log_meal_type', $calories, $p, $c, $f, '$today', '$log_time')";

      if (mysqli_query($conn, $q_insert)) {
        $success_msg = "Successfully logged \"" . htmlspecialchars($title) . "\" (" . $calories . " kcal) to your diet! <a href='calories.php' style='color:inherit; text-decoration:underline; font-weight:700'>Go to Calorie Tracker &rarr;</a>";
      } else {
        $error_msg = "Failed to log meal: " . mysqli_error($conn);
      }
    } else {
      $error_msg = "Selected recipe could not be found.";
    }
  }
}

// // Dapatkan parameter carian dan filter jenis makanan
$search = trim($_GET['search'] ?? '');
$meal_filter = $_GET['meal_filter'] ?? 'All';

// // Bina query secara prosedural berdasarkan filter
$q_select = "SELECT r.recipe_id, r.title, r.meal_type, r.prep_time_min, r.calories, r.instructions, u.full_name as author_name 
             FROM recipes r 
             JOIN users u ON r.author_id = u.user_id 
             WHERE r.status = 'Approved'";

if ($meal_filter !== 'All' && $meal_filter !== 'All types') {
  $q_select .= " AND r.meal_type = '$meal_filter'";
}

if (!empty($search)) {
  $q_select .= " AND (r.title LIKE '%$search%' OR r.instructions LIKE '%$search%')";
}

$q_select .= " ORDER BY r.created_at DESC";
$res_recipes = mysqli_query($conn, $q_select);

$recipes = [];
if ($res_recipes) {
  while ($row = mysqli_fetch_array($res_recipes)) {
    $recipes[] = $row;
  }
  mysqli_free_result($res_recipes);
}

$active_page = 'recipes';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Recipes Library – Homey</title>
  <link rel="stylesheet" href="css/style.css">
</head>

<body>
  <div id="app">
    <!-- // NAVIGASI SISI (SIDEBAR) -->
    <?php include 'php/sidebar.php'; ?>

    <!-- // BAHAGIAN UTAMA HALAMAN RESIPI -->
    <div id="main">
      <div id="topbar">
        <div>
          <div class="tb-title">Recipe Library</div>
          <div class="tb-sub">Discover healthy approved community meals</div>
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
              style="background:#dcfce7; color:#166534; padding:12px; border-radius:8px; margin-bottom:16px; font-size:14px">
              🎉 <?php echo $success_msg; ?>
            </div>
          <?php endif; ?>
          <?php if ($error_msg): ?>
            <div
              style="background:#fee2e2; color:#dc2626; padding:12px; border-radius:8px; margin-bottom:16px; font-size:14px; font-weight:600">
              ❌ <?php echo htmlspecialchars($error_msg); ?>
            </div>
          <?php endif; ?>

          <!-- // BAHAGIAN FILTER DAN CARIAN -->
          <div class="card" style="margin-bottom:16px">
            <form method="GET" action="recipes.php" style="display:flex;gap:8px;flex-wrap:wrap">
              <input name="search" class="form-input" placeholder="Search recipes by keyword..."
                style="flex:1; min-width:200px" value="<?php echo htmlspecialchars($search); ?>">

              <select name="meal_filter" class="form-input form-select" style="width:160px"
                onchange="this.form.submit()">
                <option value="All" <?php echo ($meal_filter === 'All') ? 'selected' : ''; ?>>All Categories</option>
                <option value="Breakfast" <?php echo ($meal_filter === 'Breakfast') ? 'selected' : ''; ?>>Breakfast
                </option>
                <option value="Lunch" <?php echo ($meal_filter === 'Lunch') ? 'selected' : ''; ?>>Lunch</option>
                <option value="Dinner" <?php echo ($meal_filter === 'Dinner') ? 'selected' : ''; ?>>Dinner</option>
                <option value="Snack" <?php echo ($meal_filter === 'Snack') ? 'selected' : ''; ?>>Snack</option>
              </select>

              <button type="submit" class="btn btn-primary">Search</button>
              <?php if (!empty($search) || $meal_filter !== 'All'): ?>
                <a href="recipes.php" class="btn btn-secondary" style="text-decoration:none; text-align:center">Reset</a>
              <?php endif; ?>
            </form>
          </div>

          <!-- // SENARAI KAD RESIPI DARI KOMUNITI -->
          <?php if (count($recipes) === 0): ?>
            <div
              style="background:var(--white); text-align:center; padding:50px 20px; border-radius:var(--radius); box-shadow:var(--shadow)">
              <h3 style="margin:0 0 8px 0; color:var(--gray800)">No Approved Recipes Found</h3>
              <p style="margin:0; font-size:13.5px; color:var(--gray400)">Try searching for another keyword or check back
                later!</p>
            </div>
          <?php else: ?>
            <div class="recipe-grid"
              style="display:grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap:16px; align-items: start">
              <?php foreach ($recipes as $r): ?>
                <div class="rcard"
                  style="background:var(--white); border-radius:var(--radius); box-shadow:var(--shadow); display:flex; flex-direction:column; overflow:hidden">
                  <div class="rcard-body" style="padding:15px; display:flex; flex-direction:column; flex:1">
                    <div class="rcard-name"
                      style="font-size:15px; font-weight:700; color:var(--gray800); margin-bottom:4px">
                      <?php echo htmlspecialchars($r['title']); ?></div>
                    <div class="rcard-meta" style="font-size:12px; color:var(--gray400); margin-bottom:8px">
                      <?php echo $r['calories']; ?> kcal &middot; <?php echo $r['prep_time_min']; ?> min &middot; Shared by:
                      <?php echo htmlspecialchars($r['author_name']); ?>
                    </div>

                    <span class="tag tag-g"
                      style="align-self:flex-start; margin-bottom:12px"><?php echo htmlspecialchars($r['meal_type']); ?></span>

                    <!-- Form log makanan resipi secara langsung -->
                    <form method="POST" action="recipes.php"
                      style="margin-bottom:12px; border-top:1px solid var(--gray100); padding-top:10px">
                      <input type="hidden" name="action" value="log_recipe_meal">
                      <input type="hidden" name="recipe_id" value="<?php echo $r['recipe_id']; ?>">

                      <div style="display:flex; gap:6px; align-items:center">
                        <select name="log_meal_type" class="form-input form-select"
                          style="font-size:11.5px; padding:4px 8px; height:32px; width:110px; line-height:1; margin:0">
                          <option value="Breakfast">Breakfast</option>
                          <option value="Lunch" <?php echo $r['meal_type'] === 'Lunch' ? 'selected' : ''; ?>>Lunch</option>
                          <option value="Dinner" <?php echo $r['meal_type'] === 'Dinner' ? 'selected' : ''; ?>>Dinner</option>
                          <option value="Snack" <?php echo $r['meal_type'] === 'Snack' ? 'selected' : ''; ?>>Snack</option>
                        </select>
                        <button type="submit" class="btn btn-primary"
                          style="flex:1; height:32px; font-size:11.5px; padding:0 8px; font-weight:700">Log Meal</button>
                      </div>
                    </form>

                    <!-- Gunakan tag details untuk memaparkan langkah penyediaan resipi -->
                    <details style="margin-top:auto; padding-top:6px; border-top:1px dashed var(--gray100)">
                      <summary style="font-size:12.5px; font-weight:700; color:var(--g600); cursor:pointer">View Preparation
                        Steps</summary>
                      <p
                        style="font-size:12px; color:var(--gray600); line-height:1.6; margin-top:8px; white-space:pre-line">
                        <?php echo htmlspecialchars($r['instructions']); ?>
                      </p>
                    </details>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</body>

</html>