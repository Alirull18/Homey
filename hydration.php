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

// // Simpan rekod log air minuman pengguna ke database via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // // Ambil bilangan cup sedia ada untuk dikemaskini
    $curr_cups = 0;
    $q_curr = "SELECT cups_drank FROM hydration_logs WHERE user_id = '$user_id' AND log_date = '$today'";
    $res_curr = mysqli_query($conn, $q_curr);
    if ($res_curr && mysqli_num_rows($res_curr) > 0) {
        $row_c = mysqli_fetch_array($res_curr);
        $curr_cups = $row_c['cups_drank'];
        mysqli_free_result($res_curr);
    }

    if ($action === 'set_cups') {
        $cups = (int)($_POST['cups'] ?? 0);
    } elseif ($action === 'add') {
        $cups = min(10, $curr_cups + 1);
    } elseif ($action === 'reset') {
        $cups = 0;
    } else {
        $cups = $curr_cups;
    }

    // // Masukkan atau kemaskini data log air minuman dalam database
    $q_check = "SELECT hydro_id FROM hydration_logs WHERE user_id = '$user_id' AND log_date = '$today'";
    $res_check = mysqli_query($conn, $q_check);
    if (mysqli_num_rows($res_check) > 0) {
        $q_update = "UPDATE hydration_logs SET cups_drank = $cups WHERE user_id = '$user_id' AND log_date = '$today'";
        mysqli_query($conn, $q_update);
    } else {
        $q_insert = "INSERT INTO hydration_logs (user_id, cups_drank, log_date) VALUES ('$user_id', $cups, '$today')";
        mysqli_query($conn, $q_insert);
    }
    mysqli_free_result($res_check);
    
    // // Segarkan semula halaman untuk memaparkan nilai terkini
    header("Location: hydration.php");
    exit;
}

// // Dapatkan jumlah cup air minuman yang diambil hari ini
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

// // Dapatkan rekod air minuman mingguan (7 hari lepas) untuk graf bar
$weekly_data = [0, 0, 0, 0, 0, 0, 0];
for ($i = 6; $i >= 0; $i--) {
    $check_date = date('Y-m-d', strtotime("-$i days"));
    $q_wk = "SELECT cups_drank FROM hydration_logs WHERE user_id = '$user_id' AND log_date = '$check_date'";
    $res_wk = mysqli_query($conn, $q_wk);
    if ($res_wk && mysqli_num_rows($res_wk) > 0) {
        $row_w = mysqli_fetch_array($res_wk);
        $weekly_data[6 - $i] = $row_w['cups_drank'] * 0.25;
        mysqli_free_result($res_wk);
    } else {
        $weekly_data[6 - $i] = 0.0;
    }
}

$active_page = 'hydration';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Hydration Tracker – Homey</title>
  <link rel="stylesheet" href="css/style.css">
  <script>
    // // Hantar log air secara automatik menggunakan fungsi Javascript ini
    function submitCups(count) {
      document.getElementById('cupsInput').value = count;
      document.getElementById('actionInput').value = 'set_cups';
      document.getElementById('waterForm').submit();
    }
  </script>
</head>

<body>
  <div id="app">
    <!-- // NAVIGASI SISI (SIDEBAR) -->
    <?php include 'php/sidebar.php'; ?>

    <!-- // BAHAGIAN UTAMA HALAMAN HIDRASI AIR -->
    <div id="main">
      <div id="topbar">
        <div>
          <div class="tb-title">Hydration Tracker</div>
          <div class="tb-sub">Track your daily water intake and stay active</div>
        </div>
        <div class="tb-right">
          <div class="avatar"><?php echo htmlspecialchars($sidebar_initials); ?></div>
          <span style="font-size:13px;color:var(--gray500)"><?php echo htmlspecialchars($full_name); ?></span>
        </div>
      </div>

      <div id="content">
        <div class="page">
          <!-- // Form log air tersembunyi yang digerakkan oleh clicker cup -->
          <form id="waterForm" method="POST" action="hydration.php" style="display:none">
            <input type="hidden" name="cups" id="cupsInput" value="0">
            <input type="hidden" name="action" id="actionInput" value="set_cups">
          </form>

          <div class="g2" style="margin-bottom:16px">
            <!-- // KAD LOG MINUM AIR UTAMA -->
            <div class="card">
              <div class="card-title">Daily water intake <span class="tag tag-b"><?php echo $cupsCount * 250; ?> ml / 2500 ml</span></div>
              <div style="font-family:var(--font-display);font-size:42px;font-weight:800;color:var(--b500);margin-bottom:4px">
                <?php echo number_format($waterLiters, 2); ?> <span style="font-size:18px;color:var(--gray400)">L</span>
              </div>
              <div class="bar-wrap" style="height:10px;margin-bottom:14px">
                <div class="bar bar-b" style="width:<?php echo $waterPct; ?>%"></div>
              </div>
              <div style="font-size:13px;color:var(--gray500);margin-bottom:10px">Tap a cup to log 250 ml of water</div>
              
              <!-- // GRID CUP CLICKER DUA BARIS -->
              <div class="cups-grid" style="display:grid; grid-template-columns: repeat(5, 1fr); gap:10px; margin-bottom:15px">
                <?php for ($i = 0; $i < 10; $i++): 
                  $filled = $i < $cupsCount;
                  $val_to_set = $filled ? $i : $i + 1; // Toggle behavior
                ?>
                  <div class="cup <?php echo $filled ? 'filled' : ''; ?>" onclick="submitCups(<?php echo $val_to_set; ?>)" style="cursor:pointer; border:2px solid <?php echo $filled ? 'var(--b400)' : 'var(--gray200)'; ?>; background: <?php echo $filled ? 'var(--b50)' : 'var(--gray50)'; ?>; border-radius:8px; padding:12px; text-align:center; font-weight:700; color:<?php echo $filled ? 'var(--b600)' : 'var(--gray500)'; ?>; display:flex; justify-content:center; align-items:center; font-size:15px;">
                    <?php echo $i + 1; ?>
                  </div>
                <?php endfor; ?>
              </div>

              <!-- // PAPARAN MAKLUM BALAS SASARAN PENGGUNA -->
              <?php if ($cupsCount >= 10): ?>
                <div class="alert alert-b">Daily goal reached! Great hydration today.</div>
              <?php else: ?>
                <div class="alert alert-b">Keep going! You need <?php echo (10 - $cupsCount) * 250; ?> ml more to reach your daily 2.5L goal.</div>
              <?php endif; ?>

              <!-- // BUTANG LOG MANUAL AIR -->
              <div style="display:flex;gap:8px;margin-top:12px">
                <form method="POST" action="hydration.php" style="flex:1">
                  <input type="hidden" name="action" value="add">
                  <button type="submit" class="btn btn-blue btn-full">+ Log 250 ml</button>
                </form>
                <form method="POST" action="hydration.php">
                  <input type="hidden" name="action" value="reset">
                  <button type="submit" class="btn btn-secondary">Reset</button>
                </form>
              </div>
            </div>

            <div class="col">
              <!-- // CARTA PRESTASI MINUMAN AIR MINGGUAN -->
              <div class="card">
                <div class="card-title">Weekly hydration</div>
                
                <!-- // PAPARAN BAR STATISTIK PRESTASI MINGGUAN -->
                <div style="display:flex; justify-content:space-between; align-items:flex-end; height:120px; padding:10px 0; border-bottom:1px solid var(--gray100)">
                  <?php for ($i = 0; $i < 7; $i++): 
                    $pct = min(100, round(($weekly_data[$i] / 2.5) * 100));
                  ?>
                    <div style="text-align:center; width:12%">
                      <div style="font-size:9px; color:var(--gray400); margin-bottom:4px"><?php echo number_format($weekly_data[$i], 1); ?>L</div>
                      <div style="height:70px; background:var(--gray100); border-radius:4px; display:flex; align-items:flex-end">
                        <div style="height:<?php echo $pct; ?>%; width:100%; background:var(--b400); border-radius:4px"></div>
                      </div>
                      <div style="font-size:10px; font-weight:700; color:var(--gray500); margin-top:4px"><?php echo date('D', strtotime("-" . (6 - $i) . " days")); ?></div>
                    </div>
                  <?php endfor; ?>
                </div>
                <div style="font-size:12px;color:var(--gray400);margin-top:8px">Daily Goal: 2.5 Liters (10 Cups)</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>

</html>
