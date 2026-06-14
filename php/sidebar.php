<?php
// // Pastikan session pengguna telah dimulakan
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$sidebar_name = $_SESSION['full_name'];
$sidebar_role = $_SESSION['role'];
$sidebar_initials = '';
$sidebar_parts = explode(' ', trim($sidebar_name));
if (count($sidebar_parts) >= 2) {
    $sidebar_initials = strtoupper(substr($sidebar_parts[0], 0, 1) . substr($sidebar_parts[1], 0, 1));
} else {
    $sidebar_initials = strtoupper(substr($sidebar_name, 0, 2));
}

// // Pengendali halaman aktif
if (!isset($active_page)) {
    $active_page = 'dashboard';
}
?>

<!-- // MENU NAVIGASI SISI (SIDEBAR) -->
<div id="sidebar">
  <div class="sb-logo">
    <div>
      <h2 style="margin:0; font-size: 20px; color: #333;">Homey</h2>
      <p style="margin:0; font-size: 12px; color: #666;"><?php echo ($sidebar_role === 'Admin') ? 'NutriLife Administrator' : 'NutriLife Member'; ?></p>
    </div>
  </div>

  <div id="nav-links">
    <div class="sb-sec">Main</div>
    
    <a href="dashboard.php" class="sb-item <?php echo ($active_page === 'dashboard') ? 'active' : ''; ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <rect x="3" y="3" width="7" height="7" rx="1" />
        <rect x="14" y="3" width="7" height="7" rx="1" />
        <rect x="3" y="14" width="7" height="7" rx="1" />
        <rect x="14" y="14" width="7" height="7" rx="1" />
      </svg>Dashboard
    </a>
    
    <a href="hydration.php" class="sb-item <?php echo ($active_page === 'hydration') ? 'active' : ''; ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M12 2C9 6.5 6 10 6 14a6 6 0 0 0 12 0c0-4-3-7.5-6-12z" />
      </svg>Hydration Tracker
    </a>
    
    <a href="calories.php" class="sb-item <?php echo ($active_page === 'calories') ? 'active' : ''; ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="9" />
        <polyline points="12 7 12 12 15 15" />
      </svg>Calorie Tracker
    </a>

    <a href="recipes.php" class="sb-item <?php echo ($active_page === 'recipes') ? 'active' : ''; ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M4 6h16M4 10h16M4 14h10" />
      </svg>Recipes
    </a>

    <div class="sb-sec">Community</div>

    <a href="share.php" class="sb-item <?php echo ($active_page === 'share') ? 'active' : ''; ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M12 5v14M5 12l7-7 7 7" />
      </svg>Share a Recipe
    </a>

    <div class="sb-sec">Account</div>

    <a href="profile.php" class="sb-item <?php echo ($active_page === 'profile') ? 'active' : ''; ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
        <circle cx="12" cy="7" r="4" />
      </svg>My Profile
    </a>

    <!-- // Kalau admin, paparkan panel admin -->
    <?php if ($sidebar_role === 'Admin'): ?>
      <div class="sb-sec">Administration</div>
      <a href="admin.php" class="sb-item <?php echo ($active_page === 'admin') ? 'active' : ''; ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
          <line x1="9" y1="3" x2="9" y2="21" />
        </svg>Admin Portal
      </a>
    <?php endif; ?>

    <a href="logout.php" class="sb-item">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
        <polyline points="16 17 21 12 16 7" />
        <line x1="21" y1="12" x2="9" y2="12" />
      </svg>Logout
    </a>
  </div>
</div>
