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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $meal_type = $_POST['meal_type'] ?? 'Lunch';
    $prep_time = (int)($_POST['prep_time'] ?? 0);
    $calories = (int)($_POST['calories'] ?? 0);
    $instructions = trim($_POST['instructions'] ?? '');

    if (empty($title) || empty($instructions) || $prep_time <= 0 || $calories <= 0) {
        $error_msg = "Please fill out all fields with valid information.";
    } else {
        $q_insert = "INSERT INTO recipes 
                     (author_id, title, meal_type, prep_time_min, calories, instructions, status) 
                     VALUES 
                     ('$user_id', '$title', '$meal_type', $prep_time, $calories, '$instructions', 'Pending')";

        if (mysqli_query($conn, $q_insert)) {
            $success_msg = "Recipe successfully submitted! An administrator will review and publish it shortly.";
        } else {
            $error_msg = "Database insert failed: " . mysqli_error($conn);
        }
    }
}

// // Dapatkan semua bahan mentah standard dari database untuk pengiraan Javascript secara dinamik
$ingredients_array = [];
$q_ing = "SELECT name, category, kcal_per_100g, protein_g, carbs_g, fat_g FROM ingredients ORDER BY name ASC";
$res_ing = mysqli_query($conn, $q_ing);
if ($res_ing) {
    while ($row = mysqli_fetch_array($res_ing)) {
        $ingredients_array[] = [
            'name' => $row['name'],
            'kcal' => (int)$row['kcal_per_100g'],
            'protein' => (float)$row['protein_g'],
            'carbs' => (float)$row['carbs_g'],
            'fat' => (float)$row['fat_g']
        ];
    }
    mysqli_free_result($res_ing);
}

$active_page = 'share';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Share a Recipe – Homey</title>
  <link rel="stylesheet" href="css/style.css">
  <script>
    // // Simpan senarai bahan mentah dari pangkalan data ke dalam array Javascript
    var dbIngredients = <?php echo json_encode($ingredients_array); ?>;
    var selectedRecipeIngredients = [];

    function filterIngredients() {
      var searchVal = document.getElementById('ing-search').value.toLowerCase();
      var select = document.getElementById('ing-select');
      
      // // Padamkan pilihan sedia ada dan tetapkan nilai lalai (default)
      select.innerHTML = '<option value="">-- Choose Ingredient --</option>';
      
      for (var i = 0; i < dbIngredients.length; i++) {
        var ing = dbIngredients[i];
        if (ing.name.toLowerCase().indexOf(searchVal) !== -1) {
          var opt = document.createElement('option');
          opt.value = i;
          opt.textContent = ing.name + " (" + ing.kcal + " kcal/100g)";
          select.appendChild(opt);
        }
      }
    }

    function addIngredientToRecipe() {
      var select = document.getElementById('ing-select');
      var index = select.value;
      if (index === "") {
        alert("Please select an ingredient first.");
        return;
      }
      
      var weight = parseFloat(document.getElementById('ing-weight').value);
      if (isNaN(weight) || weight <= 0) {
        alert("Please enter a valid weight in grams.");
        return;
      }
      
      var ing = dbIngredients[index];
      var kcal = Math.round((ing.kcal * weight) / 100);
      var p = Math.round(((ing.protein * weight) / 100) * 10) / 10;
      var c = Math.round(((ing.carbs * weight) / 100) * 10) / 10;
      var f = Math.round(((ing.fat * weight) / 100) * 10) / 10;
      
      selectedRecipeIngredients.push({
        name: ing.name,
        weight: weight,
        kcal: kcal,
        protein: p,
        carbs: c,
        fat: f
      });
      
      updateRecipeIngredientsUI();
    }

    function removeRecipeIngredient(idx) {
      selectedRecipeIngredients.splice(idx, 1);
      updateRecipeIngredientsUI();
    }

    function updateRecipeIngredientsUI() {
      var table = document.getElementById('recipe-ing-table');
      var tbody = document.getElementById('recipe-ing-list');
      var summary = document.getElementById('recipe-macros-summary');
      
      tbody.innerHTML = '';
      
      if (selectedRecipeIngredients.length === 0) {
        table.style.display = 'none';
        summary.style.display = 'none';
        document.getElementById('recipe-calories-input').value = '';
        return;
      }
      
      table.style.display = 'table';
      summary.style.display = 'block';
      
      var totalKcal = 0;
      var totalP = 0;
      var totalC = 0;
      var totalF = 0;
      var ingredientsSummaryText = "\n\n[Ingredients Used:\n";
      
      for (var i = 0; i < selectedRecipeIngredients.length; i++) {
        var item = selectedRecipeIngredients[i];
        totalKcal += item.kcal;
        totalP += item.protein;
        totalC += item.carbs;
        totalF += item.fat;
        
        ingredientsSummaryText += "- " + item.name + " (" + item.weight + "g) -> " + item.kcal + " kcal (P:" + item.protein + "g C:" + item.carbs + "g F:" + item.fat + "g)\n";
        
        var tr = document.createElement('tr');
        tr.innerHTML = "<td><strong>" + item.name + "</strong></td>" +
                       "<td>" + item.weight + "g</td>" +
                       "<td><strong>" + item.kcal + " kcal</strong></td>" +
                       "<td>P:" + item.protein + " C:" + item.carbs + " F:" + item.fat + "</td>" +
                       "<td><button type='button' class='abtn abtn-r' onclick='removeRecipeIngredient(" + i + ")' style='border:none; background:none; cursor:pointer'>✕</button></td>";
        tbody.appendChild(tr);
      }
      
      ingredientsSummaryText += "]";
      
      totalP = Math.round(totalP * 10) / 10;
      totalC = Math.round(totalC * 10) / 10;
      totalF = Math.round(totalF * 10) / 10;
      
      document.getElementById('sum-kcal').textContent = totalKcal;
      document.getElementById('sum-p').textContent = totalP;
      document.getElementById('sum-c').textContent = totalC;
      document.getElementById('sum-f').textContent = totalF;
      
      // // Isi secara automatik nilai kalori keseluruhan bagi resipi
      document.getElementById('recipe-calories-input').value = totalKcal;
      window.recipeIngredientsSummary = ingredientsSummaryText;
    }

    function submitRecipeWithIngredients() {
      var instructionsEl = document.getElementsByName('instructions')[0];
      if (window.recipeIngredientsSummary && instructionsEl) {
        // // Buang ringkasan bahan lama sekiranya sudah wujud sebelum ini
        var idx = instructionsEl.value.indexOf('[Ingredients Used:');
        if (idx !== -1) {
          instructionsEl.value = instructionsEl.value.substring(0, idx).trim();
        }
        instructionsEl.value = instructionsEl.value.trim() + window.recipeIngredientsSummary;
      }
      return true;
    }

    // // Jalankan tapisan bahan mentah sebaik sahaja halaman selesai dimuatkan
    window.onload = function() {
      filterIngredients();
    };
  </script>
</head>

<body>
  <div id="app">
    <!-- // NAVIGASI SISI (SIDEBAR) -->
    <?php include 'php/sidebar.php'; ?>

    <!-- // BAHAGIAN UTAMA HALAMAN KONGSI RESIPI -->
    <div id="main">
      <div id="topbar">
        <div>
          <div class="tb-title">Share a Recipe</div>
          <div class="tb-sub">Publish your healthy culinary ideas with the community</div>
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

          <div class="card" style="max-width: 700px; margin: 0 auto">
            <div class="card-title" style="border-bottom: 1px solid var(--gray100); padding-bottom:10px; margin-bottom:15px">
              Share a recipe with the community
            </div>
            
            <form method="POST" action="share.php" onsubmit="return submitRecipeWithIngredients()">
              <div class="form-group" style="margin-bottom:12px">
                <label class="form-label">Recipe Name</label>
                <input name="title" class="form-input" placeholder="e.g. Avocado Salad Wrap, Protein Pancake Bowl" required>
              </div>
              
              <div class="form-grid" style="margin-bottom:12px">
                <div class="form-group">
                  <label class="form-label">Meal Type</label>
                  <select name="meal_type" class="form-input form-select">
                    <option value="Breakfast">Breakfast</option>
                    <option value="Lunch">Lunch</option>
                    <option value="Dinner">Dinner</option>
                    <option value="Snack">Snack</option>
                  </select>
                </div>
                <div class="form-group">
                  <label class="form-label">Prep Time (minutes)</label>
                  <input name="prep_time" class="form-input" type="number" placeholder="e.g. 15" required min="1" max="600">
                </div>
              </div>

              <!-- // BAHAGIAN PENGIRA BAHAN MENTAH RESIPI  -->
              <div style="background:var(--gray50); border:1px dashed var(--g200); padding:14px; border-radius:var(--radius); margin-bottom:16px">
                <div style="font-size:13px; font-weight:700; color:var(--g800); margin-bottom:4px">Recipe Ingredient Calculator</div>
                <div style="font-size:11.5px; color:var(--gray500); margin-bottom:12px">Search standard ingredients to automatically compute total calories and nutrients.</div>
                
                <div class="form-grid" style="margin-bottom:8px">
                  <div class="form-group">
                    <label class="form-label">Search Ingredient</label>
                    <input type="text" id="ing-search" class="form-input" placeholder="Type to search..." oninput="filterIngredients()">
                  </div>
                  <div class="form-group">
                    <label class="form-label">Select Ingredient</label>
                    <select id="ing-select" class="form-input form-select">
                    </select>
                  </div>
                </div>

                <div class="form-grid" style="margin-bottom:10px">
                  <div class="form-group">
                    <label class="form-label">Weight (grams)</label>
                    <input type="number" id="ing-weight" class="form-input" value="100" min="1">
                  </div>
                  <div class="form-group" style="justify-content:flex-end">
                    <button type="button" class="btn btn-secondary" onclick="addIngredientToRecipe()" style="height:36px; width:100%; border-color:var(--g400); color:var(--g700)">Add Ingredient</button>
                  </div>
                </div>

                <!-- // Jadual senarai bahan resipi semasa yang dipilih -->
                <table class="a-table" id="recipe-ing-table" style="display:none; background:var(--white); border-radius:var(--radius-sm); border:1px solid var(--gray200); margin-top:12px">
                  <thead>
                    <tr>
                      <th>Ingredient</th>
                      <th>Weight</th>
                      <th>Kcal</th>
                      <th>Macros</th>
                      <th></th>
                    </tr>
                  </thead>
                  <tbody id="recipe-ing-list">
                    <!-- Populated dynamically -->
                  </tbody>
                </table>

                <!-- // Ringkasan jumlah makronutrisi semasa -->
                <div id="recipe-macros-summary" style="display:none; background:var(--white); border-radius:var(--radius-sm); border:1px solid var(--gray200); padding:10px; margin-top:10px; font-size:12px; color:var(--gray700)">
                  <strong>Calculated Nutrients:</strong> <span id="sum-kcal" style="font-weight:700; color:var(--g700)">0</span> kcal &middot; 
                  P: <span id="sum-p" style="font-weight:700">0</span>g &middot; 
                  C: <span id="sum-c" style="font-weight:700">0</span>g &middot; 
                  F: <span id="sum-f" style="font-weight:700">0</span>g
                </div>
              </div>
              
              <div class="form-group" style="margin-bottom:12px">
                <label class="form-label">Total Estimated Calories (kcal)</label>
                <input name="calories" id="recipe-calories-input" class="form-input" type="number" placeholder="e.g. 350" required min="1" max="5000">
              </div>
              
              <div class="form-group" style="margin-bottom:16px">
                <label class="form-label">Preparation & Cooking Steps</label>
                <textarea name="instructions" class="form-input" rows="6" placeholder="Step 1: Slice avocados and tomatoes...&#10;Step 2: Toss veggies together in olive oil...&#10;Step 3: Serve chilled." style="resize:none" required></textarea>
              </div>
              
              <div class="alert alert-a" style="margin-bottom:16px">
                <strong>Note:</strong> In compliance with security standards, your submission will be routed to a moderation queue for administrator review before appearing publicly.
              </div>
              
              <button type="submit" class="btn btn-primary btn-full">Submit recipe for admin review</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>

</html>
