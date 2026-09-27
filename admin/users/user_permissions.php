<?php
require_once '../../includes/auth_check.php';
require_once '../../config/database.php';
requirePermission('admin/users/user_permissions.php');

$success_msg = '';
$selected_user = $_GET['user_id'] ?? '';
$current_admin_id = intval($_SESSION['admin_id']);

// Handle Form Save
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_perms'])) {
    $uid = intval($_POST['user_id']);
    
    // Clear old permissions for this specific user
    $conn->query("DELETE FROM user_permissions WHERE admin_id = $uid");
    
    // Insert new checked permissions
    if (isset($_POST['pages']) && is_array($_POST['pages'])) {
        $stmt = $conn->prepare("INSERT INTO user_permissions (admin_id, page_id) VALUES (?, ?)");
        foreach ($_POST['pages'] as $page_id) {
            $stmt->bind_param("ii", $uid, $page_id);
            $stmt->execute();
        }
    }
    $success_msg = "Permissions updated successfully!";
    $selected_user = $uid;
}

// Fetch users (Excluding the currently logged-in admin to prevent self-lockout)
$users = $conn->query("SELECT id, name, full_name, role FROM admin WHERE id != $current_admin_id ORDER BY name ASC");

// Fetch grouped pages
$system_pages = [];
$pages_res = $conn->query("SELECT * FROM system_pages ORDER BY category ASC, id ASC");
while($p = $pages_res->fetch_assoc()) {
    $system_pages[$p['category']][] = $p;
}

// Fetch currently allowed pages for the selected user
$allowed_pages = [];
if ($selected_user) {
    $perm_res = $conn->query("SELECT page_id FROM user_permissions WHERE admin_id = " . intval($selected_user));
    while($pr = $perm_res->fetch_assoc()) {
        $allowed_pages[] = $pr['page_id'];
    }
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/navbar.php';
?>

<div class="container-fluid p-0">
    <div class="mb-4">
        <h1 class="h3 font-weight-bold text-dark mb-1">User Permissions</h1>
        <p class="text-muted small">Manage module access for your staff members.</p>
    </div>

    <?php if($success_msg): ?>
        <div class="alert alert-success font-weight-bold shadow-sm" style="border-radius: 8px;"><i class="fas fa-check-circle mr-1"></i> <?php echo $success_msg; ?></div>
    <?php endif; ?>

    <!-- User Selection Card -->
    <div class="card shadow-sm border-0 mb-4" style="border-radius: 12px;">
        <div class="card-body p-4 bg-light" style="border-radius: 12px;">
            <form method="GET" action="" id="userSelectForm">
                <label class="font-weight-bold text-dark text-uppercase small">Select User:</label>
                <?php if($users->num_rows > 0): ?>
                    <select name="user_id" class="form-control font-weight-bold shadow-sm" style="border: 1px solid #cbd5e1;" onchange="document.getElementById('userSelectForm').submit();">
                        <option value="">-- Choose a user to manage --</option>
                        <?php while($u = $users->fetch_assoc()): ?>
                            <?php $display_name = !empty($u['full_name']) ? $u['full_name'] : $u['name']; ?>
                            <option value="<?php echo $u['id']; ?>" <?php echo ($selected_user == $u['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($display_name) . ' (' . htmlspecialchars($u['role']) . ')'; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                <?php else: ?>
                    <div class="alert alert-warning mb-0 border-0 shadow-sm">
                        <i class="fas fa-exclamation-circle mr-1"></i> You are the only user in the system. <a href="users.php" class="font-weight-bold text-dark">Add a staff member</a> to manage permissions.
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <?php if ($selected_user): ?>
    <form method="POST" action="">
        <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($selected_user); ?>">
        
        <div class="d-flex justify-content-between align-items-center mb-3 px-1">
            <h5 class="font-weight-bold text-dark mb-0">Assigned Category & Page Access</h5>
            <div>
                <button type="button" class="btn btn-sm btn-outline-success font-weight-bold mr-2 bg-white" onclick="selectAll()">Select All Pages</button>
                <button type="button" class="btn btn-sm btn-outline-danger font-weight-bold bg-white" onclick="deselectAll()">Deselect All Pages</button>
            </div>
        </div>

        <!-- Loop through Categories -->
        <?php foreach($system_pages as $category => $pages): ?>
            <?php $cat_class = "cat_" . md5($category); ?>
            
            <div class="card shadow-sm border-0 mb-4" style="border-radius: 12px;">
                <div class="card-header bg-white border-bottom pt-3 pb-3 d-flex justify-content-between align-items-center" style="border-radius: 12px 12px 0 0;">
                    <h6 class="font-weight-bold text-dark mb-0">
                        <i class="fas fa-folder text-warning mr-2"></i> <?php echo htmlspecialchars($category); ?> 
                        <span class="badge badge-secondary ml-2" style="border-radius: 12px;"><?php echo count($pages); ?> pages</span>
                    </h6>
                    <div>
                        <button type="button" class="btn btn-xs btn-light border font-weight-bold text-primary mr-1" onclick="selectCategory('<?php echo $cat_class; ?>')">Select Category</button>
                        <button type="button" class="btn btn-xs btn-light border font-weight-bold text-secondary" onclick="deselectCategory('<?php echo $cat_class; ?>')">Deselect</button>
                    </div>
                </div>
                
                <div class="card-body p-4 bg-light" style="border-radius: 0 0 12px 12px;">
                    <div class="row">
                        <?php foreach($pages as $page): ?>
                            <div class="col-xl-3 col-lg-4 col-md-6 mb-3">
                                <div class="p-3 bg-white border shadow-sm d-flex align-items-center justify-content-between" style="border-radius: 8px; transition: all 0.2s;">
                                    <div class="custom-control custom-checkbox w-100">
                                        <input type="checkbox" class="custom-control-input page-checkbox <?php echo $cat_class; ?>" id="page_<?php echo $page['id']; ?>" name="pages[]" value="<?php echo $page['id']; ?>" <?php echo in_array($page['id'], $allowed_pages) ? 'checked' : ''; ?>>
                                        <label class="custom-control-label text-dark font-weight-bold w-100" for="page_<?php echo $page['id']; ?>" style="cursor:pointer; padding-top: 2px;">
                                            <i class="<?php echo $page['icon_class']; ?> text-muted mr-1" style="width:16px; text-align:center;"></i> <?php echo htmlspecialchars($page['page_name']); ?>
                                        </label>
                                    </div>
                                    <span class="badge badge-primary" style="font-size: 0.65rem; border-radius: 4px;"><?php echo htmlspecialchars(strtolower($page['page_type'])); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        
        <!-- Sticky Save Button -->
        <div class="card shadow border-0 mt-4 mb-5" style="border-radius: 12px; position: sticky; bottom: 20px; z-index: 1000;">
            <div class="card-body p-3 d-flex justify-content-end bg-white" style="border-radius: 12px;">
                <button type="submit" name="save_perms" class="btn btn-green shadow-sm font-weight-bold px-5 py-2" style="font-size: 1.1rem; border-radius: 8px;">
                    <i class="fas fa-save mr-2"></i> Save Permissions
                </button>
            </div>
        </div>
    </form>
    <?php endif; ?>
</div>

<?php include '../../includes/footer.php'; ?>

<style>
    /* Slight hover effect for the checkbox cards */
    .bg-white.border:hover {
        border-color: #10b981 !important;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.1) !important;
    }
    .btn-xs {
        padding: 0.15rem 0.5rem;
        font-size: 0.75rem;
    }
</style>

<script>
    function selectAll() {
        document.querySelectorAll('.page-checkbox').forEach(cb => cb.checked = true);
    }
    function deselectAll() {
        document.querySelectorAll('.page-checkbox').forEach(cb => cb.checked = false);
    }
    function selectCategory(catClass) {
        document.querySelectorAll('.' + catClass).forEach(cb => cb.checked = true);
    }
    function deselectCategory(catClass) {
        document.querySelectorAll('.' + catClass).forEach(cb => cb.checked = false);
    }
</script>