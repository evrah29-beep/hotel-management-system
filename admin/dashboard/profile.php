<?php
require_once '../../includes/auth_check.php';
require_once '../../config/database.php';

$success_msg = '';
$error_msg = '';
$admin_id = $_SESSION['admin_id'];

// Fetch the user's current data (including the new profile_picture column)
$stmt = $conn->prepare("SELECT name, password, profile_picture FROM admin WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$current_username = $user['name'];
$current_pic = $user['profile_picture'] ?? '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_username = trim($_POST['username']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // 1. Verify current password
    if (password_verify($current_password, $user['password'])) {
        
        // 2. Check for username duplicates
        $check_stmt = $conn->prepare("SELECT id FROM admin WHERE name = ? AND id != ?");
        $check_stmt->bind_param("si", $new_username, $admin_id);
        $check_stmt->execute();
        
        if ($check_stmt->get_result()->num_rows > 0) {
            $error_msg = "That login name is already taken by another user.";
        } else {
            
            // 3. Handle File Upload
            $new_pic_filename = $current_pic; // Default to existing picture
            $upload_error = '';

            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
                $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
                $filename = $_FILES['profile_picture']['name'];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                if (in_array($ext, $allowed_ext)) {
                    // Create a unique file name
                    $new_pic_filename = 'avatar_' . $admin_id . '_' . time() . '.' . $ext;
                    $upload_path = '../../assets/images/' . $new_pic_filename;

                    if (!move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                        $upload_error = "Failed to save the uploaded image.";
                    }
                } else {
                    $upload_error = "Invalid image type. Only JPG, PNG, and GIF are allowed.";
                }
            }

            if ($upload_error) {
                $error_msg = $upload_error;
            } else {
                // 4. Update Database
                if (!empty($new_password)) {
                    if ($new_password === $confirm_password) {
                        if (strlen($new_password) < 6) {
                            $error_msg = "New password must be at least 6 characters long.";
                        } else {
                            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                            $update_stmt = $conn->prepare("UPDATE admin SET name = ?, password = ?, profile_picture = ? WHERE id = ?");
                            $update_stmt->bind_param("sssi", $new_username, $new_hash, $new_pic_filename, $admin_id);
                        }
                    } else {
                        $error_msg = "New passwords do not match.";
                    }
                } else {
                    // Update without changing password
                    $update_stmt = $conn->prepare("UPDATE admin SET name = ?, profile_picture = ? WHERE id = ?");
                    $update_stmt->bind_param("ssi", $new_username, $new_pic_filename, $admin_id);
                }

                // Execute the prepared query if no errors were triggered
                if (empty($error_msg) && isset($update_stmt)) {
                    if ($update_stmt->execute()) {
                        $_SESSION['admin_username'] = $new_username;
                        $_SESSION['admin_picture'] = $new_pic_filename; // Save image to session
                        $current_username = $new_username;
                        $current_pic = $new_pic_filename;
                        $success_msg = "Profile successfully updated!";
                    } else {
                        $error_msg = "Error updating profile: " . $conn->error;
                    }
                }
            }
        }
    } else {
        $error_msg = "Your current password is incorrect. Changes not saved.";
    }
}

// Determine which avatar to show
$avatar_url = (!empty($current_pic) && file_exists('../../assets/images/' . $current_pic)) 
    ? '../../assets/images/' . htmlspecialchars($current_pic) 
    : 'https://ui-avatars.com/api/?name=' . urlencode($current_username) . '&background=10b981&color=fff&size=140';

include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/navbar.php';
?>

<div class="container-fluid p-0">
    <div class="mb-4">
        <h1 class="h3 font-weight-bold text-dark mb-1">Account Settings</h1>
        <p class="text-muted small mb-0">Manage your profile details and security preferences.</p>
    </div>

    <?php if($success_msg): ?>
        <div class="alert alert-success shadow-sm font-weight-bold" style="border-radius: 10px;"><i class="fas fa-check-circle mr-1"></i> <?php echo $success_msg; ?></div>
    <?php endif; ?>
    
    <?php if($error_msg): ?>
        <div class="alert alert-danger shadow-sm font-weight-bold" style="border-radius: 10px;"><i class="fas fa-exclamation-triangle mr-1"></i> <?php echo $error_msg; ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-xl-4 col-lg-5 mb-4">
            <div class="card shadow-sm border-0 h-100" style="border-radius: 12px;">
                <div class="card-body text-center py-5">
                    <div class="mb-4 position-relative d-inline-block">
                        <img src="<?php echo $avatar_url; ?>" class="rounded-circle shadow" style="width: 140px; height: 140px; object-fit: cover;" alt="Profile Picture">
                    </div>
                    <h4 class="font-weight-bold text-dark mb-1"><?php echo htmlspecialchars($current_username); ?></h4>
                    <p class="text-muted small mb-3">System Administrator</p>
                    <span class="badge badge-soft-success px-3 py-2 mt-2" style="font-size: 0.85rem; border-radius: 20px;">
                        <i class="fas fa-shield-check mr-1"></i> Active Account
                    </span>
                </div>
            </div>
        </div>

        <div class="col-xl-8 col-lg-7">
            <!-- Add enctype="multipart/form-data" for file uploads -->
            <form method="POST" action="" enctype="multipart/form-data">
                
                <div class="card shadow-sm border-0 mb-4" style="border-radius: 12px;">
                    <div class="card-body p-4">
                        <h5 class="font-weight-bold text-dark mb-3"><i class="fas fa-user-edit text-muted mr-2"></i> General Information</h5>
                        <hr class="mb-4">
                        
                        <div class="form-group mb-4">
                            <label class="font-weight-bold text-muted small text-uppercase">Profile Picture</label>
                            <input type="file" name="profile_picture" class="form-control-file" accept="image/png, image/jpeg, image/gif">
                            <small class="text-muted">Leave blank to keep current picture. Max size: 2MB.</small>
                        </div>

                        <div class="form-group mb-0">
                            <label class="font-weight-bold text-muted small text-uppercase">Login Name (Username)</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text bg-light border-right-0"><i class="fas fa-user text-muted"></i></span>
                                </div>
                                <input type="text" name="username" class="form-control border-left-0 pl-0" value="<?php echo htmlspecialchars($current_username); ?>" required>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0 mb-4" style="border-radius: 12px;">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="font-weight-bold text-dark mb-0"><i class="fas fa-key text-muted mr-2"></i> Security Settings</h5>
                            <span class="badge badge-light border text-muted">Optional</span>
                        </div>
                        <hr class="mb-4">
                        <p class="text-muted small mb-4">Leave the fields below blank if you do not wish to change your password.</p>
                        
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="font-weight-bold text-muted small text-uppercase">New Password</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-light border-0"><i class="fas fa-lock text-muted"></i></span>
                                    </div>
                                    <input type="password" name="new_password" class="form-control bg-light border-0" placeholder="Enter new password">
                                </div>
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="font-weight-bold text-muted small text-uppercase">Confirm New Password</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-light border-0"><i class="fas fa-lock text-muted"></i></span>
                                    </div>
                                    <input type="password" name="confirm_password" class="form-control bg-light border-0" placeholder="Repeat new password">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0" style="border-radius: 12px; border-left: 4px solid #e11d48 !important;">
                    <div class="card-body p-4 bg-white" style="border-radius: 12px;">
                        <h5 class="font-weight-bold text-dark mb-3"><i class="fas fa-shield-alt text-danger mr-2"></i> Authorization Required</h5>
                        <p class="text-muted small mb-4">To save any changes to your profile or password, you must verify your identity.</p>
                        
                        <div class="form-group mb-4">
                            <label class="font-weight-bold text-danger small text-uppercase">Current Password</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text bg-white border-danger border-right-0"><i class="fas fa-unlock-alt text-danger"></i></span>
                                </div>
                                <input type="password" name="current_password" class="form-control border-danger border-left-0 pl-0" placeholder="Verify your current password" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-green btn-block py-3 font-weight-bold" style="font-size: 1.05rem; border-radius: 8px;">
                            <i class="fas fa-save mr-2"></i> Save All Changes
                        </button>
                    </div>
                </div>
                
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>