<?php
// Fetch dynamic counts for notifications
$pending_notifications = 0;
if (isset($conn)) {
    $notif_query = $conn->query("SELECT COUNT(*) as cnt FROM bookings WHERE status = 'Pending'");
    if ($notif_query) {
        $pending_notifications = $notif_query->fetch_assoc()['cnt'];
    }
}

// Safely fetch user details from session
$nav_username = htmlspecialchars($_SESSION['admin_username'] ?? 'Admin');
$nav_role = htmlspecialchars($_SESSION['admin_role'] ?? 'Manager');

// Default avatar (Initials)
$nav_avatar = 'https://ui-avatars.com/api/?name=' . urlencode($nav_username) . '&background=10b981&color=fff';

if (isset($_SESSION['admin_id']) && isset($conn)) {
    $admin_id = intval($_SESSION['admin_id']);
    $pic_query = $conn->query("SELECT profile_picture FROM admin WHERE id = $admin_id");
    if ($pic_query && $pic_query->num_rows > 0) {
        $pic_data = $pic_query->fetch_assoc();
        if (!empty($pic_data['profile_picture'])) {
            $base = isset($basePath) ? $basePath : '../';
            $nav_avatar = $base . 'assets/images/' . htmlspecialchars($pic_data['profile_picture']);
        }
    }
}
?>

<div id="content">
    <div class="top-navbar px-4 py-2 d-flex align-items-center justify-content-between" style="background: var(--card-bg, #ffffff); border-bottom: 1px solid var(--border-color); position: sticky; top: 0; z-index: 999; transition: background 0.3s ease;">
        
        <!-- Left Side: Toggle & Search -->
        <div class="d-flex align-items-center flex-grow-1">
            <button type="button" id="sidebarCollapse" class="btn btn-link text-muted d-md-none p-0 mr-3" style="font-size: 1.25rem;">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="input-group" style="max-width: 350px;">
                <div class="input-group-prepend">
                    <span class="input-group-text bg-transparent border-0 pr-1">
                        <i class="fas fa-search text-muted" style="font-size: 0.85rem;"></i>
                    </span>
                </div>
                <input type="text" class="form-control bg-transparent border-0 shadow-none px-1" placeholder="Search menu, bookings, rooms..." style="color: var(--text-main); font-size: 0.88rem;">
            </div>
        </div>
        
        <!-- Right Side: Dark Mode, Notifications, Profile -->
        <div class="d-flex align-items-center">
            
            <button type="button" id="darkModeToggle" class="btn btn-link text-muted p-2 mr-2" style="font-size: 1.1rem; text-decoration: none;">
                <i class="fas fa-moon" id="themeIcon"></i>
            </button>

            <div class="vr mx-2" style="width: 1px; height: 24px; background: var(--border-color); opacity: 0.5;"></div>

            <!-- Notification Dropdown -->
            <div class="dropdown mx-2">
                <a href="#" class="text-muted position-relative d-inline-flex align-items-center p-2" id="notificationDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="font-size: 1.1rem;">
                    <i class="far fa-bell"></i>
                    <?php if($pending_notifications > 0): ?>
                        <span class="position-absolute badge badge-danger" style="top: 4px; right: 4px; font-size: 0.55rem; border-radius: 50%; padding: 2px 5px;">
                            <?php echo $pending_notifications; ?>
                        </span>
                    <?php endif; ?>
                </a>
                <div class="dropdown-menu dropdown-menu-right shadow border-0 mt-2 p-2" aria-labelledby="notificationDropdown" style="min-width: 260px; border-radius: 8px; background: var(--card-bg, #fff);">
                    <div class="d-flex align-items-center justify-content-between px-2 py-1 border-bottom">
                        <span class="font-weight-bold" style="color: var(--text-main); font-size: 0.85rem;">Notifications</span>
                        <span class="badge badge-danger badge-pill" style="font-size: 0.6rem;"><?php echo $pending_notifications; ?></span>
                    </div>
                    <div class="pt-2">
                        <?php if($pending_notifications > 0): ?>
                            <a class="dropdown-item d-flex align-items-center p-2 rounded" href="<?php echo $basePath ?? ''; ?>admin/bookings/bookings.php">
                                <div class="mr-2 bg-light text-warning rounded p-2 d-flex align-items-center justify-content-center">
                                    <i class="fas fa-calendar-alt" style="font-size: 0.8rem;"></i>
                                </div>
                                <div style="line-height: 1.2;">
                                    <span class="d-block font-weight-bold" style="color: var(--text-main); font-size: 0.8rem;">New Bookings</span>
                                    <span class="text-muted" style="font-size: 0.72rem;"><?php echo $pending_notifications; ?> pending</span>
                                </div>
                            </a>
                        <?php else: ?>
                            <div class="text-muted text-center py-2" style="font-size: 0.8rem;">No new notifications</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Profile Pill Dropdown (Saxday midabka qoraalka ee Dark Mode) -->
            <div class="dropdown ml-2">
                <a href="#" class="d-flex align-items-center text-decoration-none py-1 px-2 rounded-pill" id="profileDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="background: var(--body-bg, #f1f5f9); border: 1px solid var(--border-color);">
                    <img src="<?php echo $nav_avatar; ?>" class="rounded-circle mr-2" style="width: 30px; height: 30px; object-fit: cover;" alt="User">
                    <span class="font-weight-bold mr-2 d-none d-sm-inline" style="font-size: 0.82rem; color: var(--text-main);"><?php echo $nav_username; ?></span>
                    <i class="fas fa-chevron-down text-muted" style="font-size: 0.6rem;"></i>
                </a>
                
                <div class="dropdown-menu dropdown-menu-right shadow border-0 mt-2 p-1" aria-labelledby="profileDropdown" style="border-radius: 8px; min-width: 180px; background: var(--card-bg, #fff);">
                    <a class="dropdown-item py-2 px-3 rounded" href="<?php echo $basePath ?? ''; ?>admin/dashboard/profile.php" style="font-size: 0.82rem; color: var(--text-main);">
                        <i class="fas fa-user text-muted mr-2"></i> Profile
                    </a>
                    <a class="dropdown-item py-2 px-3 rounded" href="<?php echo $basePath ?? ''; ?>admin/settings/settings.php" style="font-size: 0.82rem; color: var(--text-main);">
                        <i class="fas fa-cog text-muted mr-2"></i> Settings
                    </a>
                    <div class="dropdown-divider my-1"></div>
                    <a class="dropdown-item py-2 px-3 rounded text-danger font-weight-bold" href="<?php echo $basePath ?? ''; ?>logout.php" style="font-size: 0.82rem;">
                        <i class="fas fa-sign-out-alt mr-2"></i> Logout
                    </a>
                </div>
            </div>

        </div>
    </div>
    <div class="content-wrapper">