<?php
require_once '../../includes/auth_check.php';
require_once '../../config/database.php';

// ONLY Admins can access this page
$role = $_SESSION['admin_role'] ?? '';
if ($role !== 'Admin' && $role !== 'Administrator') {
    echo "<h2 style='text-align:center; margin-top:50px; color:red;'>Access Denied: Master Admins Only</h2>";
    exit();
}

$success_msg = '';

// Handle Form Save
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Clear all permissions for non-admin users to rebuild them
    $conn->query("DELETE FROM user_permissions WHERE admin_id IN (SELECT id FROM admin WHERE role NOT IN ('Admin', 'Administrator'))");
    
    if (isset($_POST['perms']) && is_array($_POST['perms'])) {
        $stmt = $conn->prepare("INSERT INTO user_permissions (admin_id, page_name) VALUES (?, ?)");
        foreach ($_POST['perms'] as $user_id => $modules) {
            foreach ($modules as $module) {
                $stmt->bind_param("is", $user_id, $module);
                $stmt->execute();
            }
        }
    }
    $success_msg = "Staff permissions updated successfully!";
}

// Fetch all staff members (Assuming $conn->query returns an Array directly)
$staff_rows = $conn->query("SELECT id, name, role FROM admin WHERE role NOT IN ('Admin', 'Administrator') ORDER BY name ASC");
if (!is_array($staff_rows)) {
    $staff_rows = [];
}

// Fetch existing permissions to populate checkboxes
$all_perms = [];
$perm_q = $conn->query("SELECT admin_id, page_name FROM user_permissions");
if (is_array($perm_q)) {
    foreach($perm_q as $p) {
        $all_perms[$p['admin_id']][] = $p['page_name'];
    }
}

function isChecked($uid, $module, $all_perms) {
    return (isset($all_perms[$uid]) && in_array($module, $all_perms[$uid])) ? 'checked' : '';
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/navbar.php';
?>

<div class="container-fluid p-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 font-weight-bold text-dark mb-1">Page Permissions</h1>
            <p class="text-muted small mb-0">Control exact module access for each staff member.</p>
        </div>
        <a href="users.php" class="btn btn-light shadow-sm border"><i class="fas fa-arrow-left"></i> Back to Staff</a>
    </div>

    <?php if($success_msg): ?>
        <div class="alert alert-success shadow-sm font-weight-bold"><i class="fas fa-check-circle"></i> <?php echo $success_msg; ?></div>
    <?php endif; ?>

    <div class="card shadow-sm border-0" style="border-radius: 12px;">
        <div class="card-body p-0">
            <form method="POST" action="">
                <div class="table-responsive">
                    <table class="table table-hover align-middle text-center mb-0">
                        <thead class="bg-light text-muted small text-uppercase">
                            <tr>
                                <th class="text-left pl-4 py-3">Staff Member</th>
                                <th>Rooms</th>
                                <th>Room Types</th>
                                <th>Bookings</th>
                                <th>Guests</th>
                                <th>Payments</th>
                                <th>Reports</th>
                                <th>Settings</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($staff_rows) > 0): ?>
                                <?php foreach($staff_rows as $row): $uid = $row['id']; ?>
                                <tr>
                                    <td class="text-left pl-4">
                                        <strong class="d-block text-dark"><?php echo htmlspecialchars($row['name']); ?></strong>
                                        <span class="text-muted small"><?php echo $row['role']; ?></span>
                                    </td>
                                    <td><input type="checkbox" name="perms[<?php echo $uid; ?>][]" value="rooms" <?php echo isChecked($uid, 'rooms', $all_perms); ?> style="transform: scale(1.3);"></td>
                                    <td><input type="checkbox" name="perms[<?php echo $uid; ?>][]" value="room_types" <?php echo isChecked($uid, 'room_types', $all_perms); ?> style="transform: scale(1.3);"></td>
                                    <td><input type="checkbox" name="perms[<?php echo $uid; ?>][]" value="bookings" <?php echo isChecked($uid, 'bookings', $all_perms); ?> style="transform: scale(1.3);"></td>
                                    <td><input type="checkbox" name="perms[<?php echo $uid; ?>][]" value="guests" <?php echo isChecked($uid, 'guests', $all_perms); ?> style="transform: scale(1.3);"></td>
                                    <td><input type="checkbox" name="perms[<?php echo $uid; ?>][]" value="payments" <?php echo isChecked($uid, 'payments', $all_perms); ?> style="transform: scale(1.3);"></td>
                                    <td><input type="checkbox" name="perms[<?php echo $uid; ?>][]" value="reports" <?php echo isChecked($uid, 'reports', $all_perms); ?> style="transform: scale(1.3);"></td>
                                    <td><input type="checkbox" name="perms[<?php echo $uid; ?>][]" value="settings" <?php echo isChecked($uid, 'settings', $all_perms); ?> style="transform: scale(1.3);"></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="8" class="text-muted py-4">No non-admin staff found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="p-3 bg-light border-top text-right" style="border-radius: 0 0 12px 12px;">
                    <button type="submit" class="btn btn-green font-weight-bold px-4"><i class="fas fa-save"></i> Save Permissions</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>