<?php
session_start();
require_once 'php/db_connect.php';

// // Pastikan sesi pengguna adalah Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
  header("Location: dashboard.php");
  exit;
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];

$success_msg = isset($_GET['success']) ? $_GET['success'] : '';
$error_msg = isset($_GET['error']) ? $_GET['error'] : '';

// // Uruskan semua data pengurusan admin via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  if ($action === 'approve_recipe') {
    $recipe_id = (int) $_POST['recipe_id'];
    $q_mod = "UPDATE recipes SET status = 'Approved' WHERE recipe_id = $recipe_id";
    if (mysqli_query($conn, $q_mod)) {
      $success_msg = "Recipe approved and published successfully!";
    } else {
      $error_msg = "Error updating recipe: " . mysqli_error($conn);
    }
  } elseif ($action === 'reject_recipe') {
    $recipe_id = (int) $_POST['recipe_id'];
    $q_mod = "UPDATE recipes SET status = 'Rejected' WHERE recipe_id = $recipe_id";
    if (mysqli_query($conn, $q_mod)) {
      $success_msg = "Recipe rejected and archived.";
    } else {
      $error_msg = "Error updating recipe: " . mysqli_error($conn);
    }
  } elseif ($action === 'add_ingredient') {
    $name = trim($_POST['name'] ?? '');
    $category = $_POST['category'] ?? 'Other';
    $kcal = (int) ($_POST['kcal'] ?? 100);
    $p = (float) ($_POST['protein'] ?? 0);
    $c = (float) ($_POST['carbs'] ?? 0);
    $f = (float) ($_POST['fat'] ?? 0);

    if (empty($name)) {
      $error_msg = "Ingredient name cannot be empty.";
    } else {
      $q_insert = "INSERT INTO ingredients (name, category, kcal_per_100g, protein_g, carbs_g, fat_g) 
                         VALUES ('$name', '$category', $kcal, $p, $c, $f)";

      if (mysqli_query($conn, $q_insert)) {
        header("Location: admin.php?tab=ingredients&success=" . urlencode("New ingredient successfully added to central database!"));
        exit;
      } else {
        $error_msg = "Failed to insert ingredient: " . mysqli_error($conn);
      }
    }
  } elseif ($action === 'update_ingredient') {
    $ingredient_id = (int) $_POST['ingredient_id'];
    $name = trim($_POST['name'] ?? '');
    $category = $_POST['category'] ?? 'Other';
    $kcal = (int) ($_POST['kcal'] ?? 100);
    $p = (float) ($_POST['protein'] ?? 0);
    $c = (float) ($_POST['carbs'] ?? 0);
    $f = (float) ($_POST['fat'] ?? 0);

    if (empty($name)) {
      $error_msg = "Ingredient name cannot be empty.";
    } else {
      $q_update = "UPDATE ingredients SET 
                         name = '$name', 
                         category = '$category', 
                         kcal_per_100g = $kcal, 
                         protein_g = $p, 
                         carbs_g = $c, 
                         fat_g = $f 
                         WHERE ingredient_id = $ingredient_id";

      if (mysqli_query($conn, $q_update)) {
        header("Location: admin.php?tab=ingredients&success=" . urlencode("Ingredient updated successfully!"));
        exit;
      } else {
        $error_msg = "Failed to update ingredient: " . mysqli_error($conn);
      }
    }
  } elseif ($action === 'delete_ingredient') {
    $ingredient_id = (int) $_POST['ingredient_id'];
    $q_delete = "DELETE FROM ingredients WHERE ingredient_id = $ingredient_id";

    if (mysqli_query($conn, $q_delete)) {
      header("Location: admin.php?tab=ingredients&success=" . urlencode("Ingredient removed from central database."));
      exit;
    } else {
      $error_msg = "Failed to delete ingredient: " . mysqli_error($conn);
    }
  } elseif ($action === 'delete_user') {
    $target_user_id = (int) $_POST['target_user_id'];
    if ($target_user_id !== $user_id) {
      $q_del_user = "DELETE FROM users WHERE user_id = $target_user_id";
      if (mysqli_query($conn, $q_del_user)) {
        header("Location: admin.php?tab=users&success=" . urlencode("User account removed successfully."));
        exit;
      } else {
        $error_msg = "Failed to delete user: " . mysqli_error($conn);
      }
    } else {
      $error_msg = "You cannot delete your own admin account!";
    }
  }
}

// // Tentukan tab admin yang sedang aktif (analytics, approvals, ingredients, users)
$tab = $_GET['tab'] ?? 'analytics';

// // Dapatkan statistik ringkas pangkalan data
$q_total_users = "SELECT COUNT(*) as count FROM users WHERE role = 'User'";
$res_users = mysqli_query($conn, $q_total_users);
$total_users = 0;
if ($res_users) {
  $row = mysqli_fetch_array($res_users);
  $total_users = $row['count'];
  mysqli_free_result($res_users);
}

$q_pending_recipes = "SELECT COUNT(*) as count FROM recipes WHERE status = 'Pending'";
$res_pending = mysqli_query($conn, $q_pending_recipes);
$pending_count = 0;
if ($res_pending) {
  $row = mysqli_fetch_array($res_pending);
  $pending_count = $row['count'];
  mysqli_free_result($res_pending);
}

$q_total_recipes = "SELECT COUNT(*) as count FROM recipes WHERE status = 'Approved'";
$res_total_rec = mysqli_query($conn, $q_total_recipes);
$total_recipes = 0;
if ($res_total_rec) {
  $row = mysqli_fetch_array($res_total_rec);
  $total_recipes = $row['count'];
  mysqli_free_result($res_total_rec);
}

$q_total_meals = "SELECT COUNT(*) as count FROM meal_logs";
$res_meals_tot = mysqli_query($conn, $q_total_meals);
$total_meals_logged = 0;
if ($res_meals_tot) {
  $row = mysqli_fetch_array($res_meals_tot);
  $total_meals_logged = $row['count'];
  mysqli_free_result($res_meals_tot);
}

$active_page = 'admin';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Portal – Homey</title>
  <link rel="stylesheet" href="css/style.css">
  <style>
    .admin-tabs {
      display: flex;
      gap: 5px;
      margin-bottom: 16px;
      border-bottom: 2px solid var(--gray100);
      padding-bottom: 4px;
    }

    .admin-tab {
      padding: 10px 18px;
      font-size: 13.5px;
      font-weight: 700;
      color: var(--gray500);
      text-decoration: none;
      border-radius: var(--radius-sm);
      transition: all 0.2s;
    }

    .admin-tab:hover {
      background: var(--gray50);
      color: var(--gray800);
    }

    .admin-tab.active {
      background: var(--g100);
      color: var(--g700);
    }
  </style>
</head>

<body>
  <div id="app">
    <!-- // NAVIGASI SISI (SIDEBAR) -->
    <?php include 'php/sidebar.php'; ?>

    <!-- // BAHAGIAN UTAMA PORTAL ADMIN -->
    <div id="main">
      <div id="topbar">
        <div>
          <div class="tb-title">Admin Portal</div>
          <div class="tb-sub">Overview health stats and moderate community resources</div>
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

          <!-- // Bar Navigasi Tab Admin -->
          <div class="admin-tabs">
            <a href="admin.php?tab=analytics"
              class="admin-tab <?php echo $tab === 'analytics' ? 'active' : ''; ?>">Analytics Overview</a>
            <a href="admin.php?tab=approvals"
              class="admin-tab <?php echo $tab === 'approvals' ? 'active' : ''; ?>">Recipe Approvals
              (<?php echo $pending_count; ?>)</a>
            <a href="admin.php?tab=ingredients"
              class="admin-tab <?php echo $tab === 'ingredients' ? 'active' : ''; ?>">Ingredient Database</a>
            <a href="admin.php?tab=users" class="admin-tab <?php echo $tab === 'users' ? 'active' : ''; ?>">User
              Accounts</a>
          </div>

          <!-- // Kandungan Tab Semasa -->
          <?php if ($tab === 'analytics'): ?>
            <!-- // TAB 1: ANALYTICS OVERVIEW -->
            <div class="g4" style="margin-bottom: 16px">
              <div class="card">
                <div class="metric-lbl">Total Members</div>
                <div class="metric-val"><?php echo $total_users; ?></div>
                <div class="metric-sub" style="color:var(--g500)">Active track logs</div>
              </div>
              <div class="card">
                <div class="metric-lbl">Pending Review</div>
                <div class="metric-val" style="color:var(--r500)"><?php echo $pending_count; ?></div>
                <div class="metric-sub">Community recipes</div>
              </div>
              <div class="card">
                <div class="metric-lbl">Published Recipes</div>
                <div class="metric-val"><?php echo $total_recipes; ?></div>
                <div class="metric-sub">Vetted by admins</div>
              </div>
              <div class="card">
                <div class="metric-lbl">Total Logs</div>
                <div class="metric-val"><?php echo $total_meals_logged; ?></div>
                <div class="metric-sub">Meals calculated</div>
              </div>
            </div>

            <div class="g2">
              <div class="card">
                <div class="card-title">User Goal Distributions</div>
                <div style="display:flex; flex-direction:column; gap:12px">
                  <?php
                  $q_goal = "SELECT goal_type, COUNT(*) as count FROM users WHERE role='User' GROUP BY goal_type";
                  $res_goal = mysqli_query($conn, $q_goal);
                  $goals = ['Deficit' => 0, 'Surplus' => 0, 'Maintain' => 0, 'Not Configured' => 0];
                  $total_cohort = 0;
                  if ($res_goal) {
                    while ($row = mysqli_fetch_array($res_goal)) {
                      $g_type = $row['goal_type'];
                      if (empty($g_type)) {
                        $g_type = 'Not Configured';
                      }
                      if (isset($goals[$g_type])) {
                        $goals[$g_type] += $row['count'];
                        $total_cohort += $row['count'];
                      }
                    }
                    mysqli_free_result($res_goal);
                  }

                  foreach ($goals as $name => $count):
                    $pct = $total_cohort > 0 ? round(($count / $total_cohort) * 100) : 0;
                    if ($name === 'Deficit') {
                      $cls = 'bar-g';
                      $lbl = 'Calorie Deficit (Weight Loss)';
                    } elseif ($name === 'Surplus') {
                      $cls = 'bar-a';
                      $lbl = 'Calorie Surplus (Weight Gain)';
                    } elseif ($name === 'Maintain') {
                      $cls = 'bar-b';
                      $lbl = 'Maintain Weight';
                    } else {
                      $cls = 'bar-r';
                      $lbl = 'Profile Not Configured';
                    }
                    ?>
                    <div>
                      <div
                        style="display:flex; justify-content:space-between; font-size:12.5px; font-weight:700; margin-bottom:4px">
                        <span><?php echo htmlspecialchars($lbl); ?></span>
                        <span style="color:var(--gray400)"><?php echo $count; ?> members (<?php echo $pct; ?>%)</span>
                      </div>
                      <div class="bar-wrap" style="height:10px">
                        <div class="bar <?php echo $cls; ?>" style="width:<?php echo $pct; ?>%"></div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>

              <div class="card">
                <div class="card-title">Database Overview</div>
                <table class="a-table">
                  <thead>
                    <tr>
                      <th>Database Item</th>
                      <th>Total Count</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td>Ingredient Records</td>
                      <td><?php
                      $res = mysqli_query($conn, "SELECT COUNT(*) as c FROM ingredients");
                      $row = mysqli_fetch_array($res);
                      echo $row['c'];
                      ?> items</td>
                    </tr>
                    <tr>
                      <td>Hydration entries</td>
                      <td><?php
                      $res = mysqli_query($conn, "SELECT COUNT(*) as c FROM hydration_logs");
                      $row = mysqli_fetch_array($res);
                      echo $row['c'];
                      ?> logs</td>
                    </tr>
                    <tr>
                      <td>Admin accounts</td>
                      <td><?php
                      $res = mysqli_query($conn, "SELECT COUNT(*) as c FROM users WHERE role='Admin'");
                      $row = mysqli_fetch_array($res);
                      echo $row['c'];
                      ?> accounts</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

          <?php elseif ($tab === 'approvals'): ?>
            <!-- // TAB 2: RECIPE APPROVALS -->
            <div class="card">
              <div class="card-title">Pending Community Recipe Approvals <span
                  class="tag tag-r"><?php echo $pending_count; ?> pending</span></div>

              <?php
              $q_pend = "SELECT r.recipe_id, r.title, r.calories, r.prep_time_min, r.instructions, u.full_name as author_name 
                         FROM recipes r 
                         JOIN users u ON r.author_id = u.user_id 
                         WHERE r.status = 'Pending' 
                         ORDER BY r.created_at ASC";
              $res_pend = mysqli_query($conn, $q_pend);

              if ($res_pend && mysqli_num_rows($res_pend) > 0):
                ?>
                <table class="a-table">
                  <thead>
                    <tr>
                      <th>Author</th>
                      <th>Recipe Title</th>
                      <th>Calories</th>
                      <th>Prep Time</th>
                      <th>Steps Summary</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php while ($r = mysqli_fetch_array($res_pend)): ?>
                      <tr>
                        <td><strong><?php echo htmlspecialchars($r['author_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($r['title']); ?></td>
                        <td><strong><?php echo $r['calories']; ?> kcal</strong></td>
                        <td><?php echo $r['prep_time_min']; ?> mins</td>
                        <td>
                          <details style="cursor:pointer; font-size:11.5px">
                            <summary style="color:var(--g600)">Expand instructions</summary>
                            <p style="margin:4px 0; white-space:pre-line; color:var(--gray600)">
                              <?php echo htmlspecialchars($r['instructions']); ?></p>
                          </details>
                        </td>
                        <td>
                          <div class="a-btns" style="display:flex; gap:5px">
                            <form method="POST" action="admin.php?tab=approvals">
                              <input type="hidden" name="action" value="approve_recipe">
                              <input type="hidden" name="recipe_id" value="<?php echo $r['recipe_id']; ?>">
                              <button type="submit" class="abtn abtn-g">Approve</button>
                            </form>
                            <form method="POST" action="admin.php?tab=approvals">
                              <input type="hidden" name="action" value="reject_recipe">
                              <input type="hidden" name="recipe_id" value="<?php echo $r['recipe_id']; ?>">
                              <button type="submit" class="abtn abtn-r">Reject</button>
                            </form>
                          </div>
                        </td>
                      </tr>
                    <?php endwhile; ?>
                  </tbody>
                </table>
              <?php else: ?>
                <p
                  style="color:var(--gray400); font-size:13.5px; text-align:center; padding:30px 0; border:2px dashed var(--gray100); border-radius:8px">
                  All community submissions have been moderated. Clean plate!
                </p>
              <?php endif;
              if ($res_pend)
                mysqli_free_result($res_pend);
              ?>
            </div>

          <?php elseif ($tab === 'ingredients'):
            $edit_id = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
            $edit_name = '';
            $edit_category = 'Other';
            $edit_kcal = '';
            $edit_protein = '';
            $edit_carbs = '';
            $edit_fat = '';
            $is_editing = false;

            if ($edit_id > 0) {
              $q_edit = "SELECT * FROM ingredients WHERE ingredient_id = $edit_id";
              $res_edit = mysqli_query($conn, $q_edit);
              if ($res_edit && mysqli_num_rows($res_edit) > 0) {
                $row_e = mysqli_fetch_array($res_edit);
                $edit_name = $row_e['name'];
                $edit_category = $row_e['category'];
                $edit_kcal = $row_e['kcal_per_100g'];
                $edit_protein = $row_e['protein_g'];
                $edit_carbs = $row_e['carbs_g'];
                $edit_fat = $row_e['fat_g'];
                $is_editing = true;
                mysqli_free_result($res_edit);
              }
            }
            ?>
            <!-- // TAB 3: INGREDIENT DATABASE -->
            <div class="g2">
              <div class="card">
                <div class="card-title">
                  <?php echo $is_editing ? 'Edit standard ingredient' : 'Add standard ingredient'; ?></div>

                <form method="POST" action="admin.php?tab=ingredients">
                  <input type="hidden" name="action"
                    value="<?php echo $is_editing ? 'update_ingredient' : 'add_ingredient'; ?>">
                  <?php if ($is_editing): ?>
                    <input type="hidden" name="ingredient_id" value="<?php echo $edit_id; ?>">
                  <?php endif; ?>

                  <div class="form-group" style="margin-bottom:10px">
                    <label class="form-label">Ingredient name</label>
                    <input name="name" class="form-input" placeholder="e.g. Avocado, White Rice, Whey Protein"
                      value="<?php echo htmlspecialchars($edit_name); ?>" required>
                  </div>

                  <div class="form-grid" style="margin-bottom:10px">
                    <div class="form-group">
                      <label class="form-label">Category</label>
                      <select name="category" class="form-input form-select">
                        <option value="Meat & Poultry" <?php echo $edit_category === 'Meat & Poultry' ? 'selected' : ''; ?>>
                          Meat & Poultry</option>
                        <option value="Vegetables" <?php echo $edit_category === 'Vegetables' ? 'selected' : ''; ?>>
                          Vegetables</option>
                        <option value="Grains" <?php echo $edit_category === 'Grains' ? 'selected' : ''; ?>>Grains</option>
                        <option value="Dairy & Eggs" <?php echo $edit_category === 'Dairy & Eggs' ? 'selected' : ''; ?>>
                          Dairy & Eggs</option>
                        <option value="Legumes" <?php echo $edit_category === 'Legumes' ? 'selected' : ''; ?>>Legumes
                        </option>
                        <option value="Other" <?php echo $edit_category === 'Other' ? 'selected' : ''; ?>>Other</option>
                      </select>
                    </div>
                    <div class="form-group">
                      <label class="form-label">kcal / 100g</label>
                      <input name="kcal" class="form-input" type="number" placeholder="e.g. 160"
                        value="<?php echo htmlspecialchars($edit_kcal); ?>" required min="1">
                    </div>
                  </div>

                  <div class="form-grid" style="margin-bottom:12px">
                    <div class="form-group"><label class="form-label">Protein (g)</label><input name="protein"
                        class="form-input" type="number" step="0.1" placeholder="0"
                        value="<?php echo htmlspecialchars($edit_protein); ?>"></div>
                    <div class="form-group"><label class="form-label">Carbs (g)</label><input name="carbs"
                        class="form-input" type="number" step="0.1" placeholder="0"
                        value="<?php echo htmlspecialchars($edit_carbs); ?>"></div>
                    <div class="form-group"><label class="form-label">Fat (g)</label><input name="fat" class="form-input"
                        type="number" step="0.1" placeholder="0" value="<?php echo htmlspecialchars($edit_fat); ?>"></div>
                  </div>

                  <div style="display:flex; gap:8px">
                    <button type="submit" class="btn btn-primary btn-full"
                      style="flex:1"><?php echo $is_editing ? 'Update ingredient' : 'Insert ingredient'; ?></button>
                    <?php if ($is_editing): ?>
                      <a href="admin.php?tab=ingredients" class="btn btn-secondary" style="text-decoration:none">Cancel</a>
                    <?php endif; ?>
                  </div>
                </form>
              </div>

              <!-- // Senarai Bahan Mentah Catalog -->
              <div class="card">
                <div class="card-title">Active Ingredient Catalog</div>

                <?php
                $q_ing = "SELECT ingredient_id, name, category, kcal_per_100g, protein_g, carbs_g, fat_g FROM ingredients ORDER BY name ASC";
                $res_ing = mysqli_query($conn, $q_ing);

                if ($res_ing && mysqli_num_rows($res_ing) > 0):
                  ?>
                  <div style="max-height: 350px; overflow-y: auto">
                    <table class="a-table">
                      <thead>
                        <tr>
                          <th>Ingredient</th>
                          <th>Cat</th>
                          <th>kcal</th>
                          <th>Macros</th>
                          <th>Action</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php while ($ing = mysqli_fetch_array($res_ing)): ?>
                          <tr>
                            <td style="font-weight:700"><?php echo htmlspecialchars($ing['name']); ?></td>
                            <td><span class="tag tag-gray"><?php echo htmlspecialchars($ing['category']); ?></span></td>
                            <td><strong><?php echo $ing['kcal_per_100g']; ?></strong></td>
                            <td style="font-size:11px">P:<?php echo $ing['protein_g']; ?> C:<?php echo $ing['carbs_g']; ?>
                              F:<?php echo $ing['fat_g']; ?></td>
                            <td>
                              <div style="display:flex; gap:4px; align-items:center">
                                <a href="admin.php?tab=ingredients&edit=<?php echo $ing['ingredient_id']; ?>"
                                  class="abtn abtn-b" style="text-decoration:none">Edit</a>
                                <form method="POST" action="admin.php?tab=ingredients"
                                  onsubmit="return confirm('Remove this ingredient permanently?')" style="margin:0">
                                  <input type="hidden" name="action" value="delete_ingredient">
                                  <input type="hidden" name="ingredient_id" value="<?php echo $ing['ingredient_id']; ?>">
                                  <button type="submit" class="abtn abtn-r"
                                    style="border:none; background:none; cursor:pointer">✕</button>
                                </form>
                              </div>
                            </td>
                          </tr>
                        <?php endwhile; ?>
                      </tbody>
                    </table>
                  </div>
                <?php else: ?>
                  <p style="color:var(--gray400); font-size:13px; text-align:center">No custom ingredients cataloged.</p>
                <?php endif;
                if ($res_ing)
                  mysqli_free_result($res_ing);
                ?>
              </div>
            </div>

          <?php elseif ($tab === 'users'): ?>
            <!-- // TAB 4: USER ACCOUNTS -->
            <div class="card">
              <div class="card-title">Registered User Directory</div>

              <?php
              $q_users = "SELECT user_id, full_name, email, goal_type, role FROM users ORDER BY created_at DESC";
              $res_all_u = mysqli_query($conn, $q_users);

              if ($res_all_u && mysqli_num_rows($res_all_u) > 0):
                ?>
                <table class="a-table">
                  <thead>
                    <tr>
                      <th>Name</th>
                      <th>Email</th>
                      <th>Diet Goal</th>
                      <th>Plan Status</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php while ($u = mysqli_fetch_array($res_all_u)): ?>
                      <tr>
                        <td style="font-weight:700"><?php echo htmlspecialchars($u['full_name']); ?></td>
                        <td style="color:var(--gray400)"><?php echo htmlspecialchars($u['email']); ?></td>
                        <td><span class="tag tag-gray"><?php echo htmlspecialchars($u['goal_type'] ?? 'Deficit'); ?></span>
                        </td>
                        <td><span class="tag tag-g">Active Member</span></td>
                        <td>
                          <div class="a-btns">
                            <?php if ($u['user_id'] !== $user_id): ?>
                              <form method="POST" action="admin.php?tab=users" onsubmit="return confirm('Remove <?php echo htmlspecialchars($u['full_name'], ENT_QUOTES); ?> permanently?')" style="margin:0">
                                <input type="hidden" name="action" value="delete_user">
                                <input type="hidden" name="target_user_id" value="<?php echo $u['user_id']; ?>">
                                <button type="submit" class="abtn abtn-r" style="cursor:pointer">Remove Acc</button>
                              </form>
                            <?php else: ?>
                              <span class="tag tag-gray">Logged In Admin</span>
                            <?php endif; ?>
                          </div>
                        </td>
                      </tr>
                    <?php endwhile; ?>
                  </tbody>
                </table>
              <?php endif;
              if ($res_all_u)
                mysqli_free_result($res_all_u);
              ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</body>

</html>