<?php
require_once '../../includes/auth_check.php';
require_once '../../config/database.php';
requirePermission('admin/settings/settings.php');

$success_msg = '';
$error_msg = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $hotel_name = $_POST['hotel_name'];
    $hotel_email = $_POST['hotel_email'];
    $hotel_phone = $_POST['hotel_phone'];
    $hotel_address = $_POST['hotel_address'];

    // 1. Update text fields using the existing Stored Procedure
    $stmt = $conn->prepare("CALL UpdateSettings(?, ?, ?, ?)");
    $stmt->bind_param("ssss", $hotel_name, $hotel_email, $hotel_phone, $hotel_address);
    
    if ($stmt->execute()) {
        $success_msg = "Hotel settings updated successfully!";
    } else {
        $error_msg = "Failed to update settings: " . $conn->error;
    }
    $stmt->close();

    // 2. Handle Logo Image Upload (if a file was selected)
    if (isset($_FILES['hotel_logo']) && $_FILES['hotel_logo']['error'] == 0) {
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
        $file_name = $_FILES['hotel_logo']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        if (in_array($file_ext, $allowed_ext)) {
            // Create a unique name for the image
            $new_logo_name = 'logo_' . time() . '.' . $file_ext;
            $upload_dir = '../../assets/images/';
            $upload_path = $upload_dir . $new_logo_name;
            
            // Create the directory if it doesn't exist yet
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            // Move the file and update the database
            if (move_uploaded_file($_FILES['hotel_logo']['tmp_name'], $upload_path)) {
                $logo_stmt = $conn->prepare("UPDATE settings SET hotel_logo = ? WHERE id = 1");
                $logo_stmt->bind_param("s", $new_logo_name);
                $logo_stmt->execute();
                $logo_stmt->close();
                $success_msg = "Hotel settings and logo updated successfully!";
            } else {
                $error_msg = "Settings saved, but failed to upload the logo image.";
            }
        } else {
            $error_msg = "Invalid image format. Only JPG, PNG, and GIF files are allowed.";
        }
    }
}

// Fetch Current Settings
$result = $conn->query("SELECT * FROM settings WHERE id = 1");
$settings = $result->fetch_assoc();

include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/navbar.php';
?>

<style>
    .page-header { margin-bottom: 30px; }
    .settings-card { border: 1px solid #edf2f7; border-radius: 12px; background: #fff; }
    .custom-file-label::after { content: "Browse"; background-color: #10b981; color: white; border-radius: 0 4px 4px 0; }
</style>

<div class="container-fluid p-0">
    <div class="page-header">
        <h1 class="h3 font-weight-bold text-dark mb-1">System Settings</h1>
        <p class="text-muted small mb-0">Manage your hotel's global configuration, contact details, and brand logo.</p>
    </div>

    <?php if($success_msg): ?>
        <div class="alert alert-success font-weight-bold shadow-sm" style="border-radius: 8px;"><i class="fas fa-check-circle mr-1"></i> <?php echo $success_msg; ?></div>
    <?php endif; ?>
    
    <?php if($error_msg): ?>
        <div class="alert alert-danger font-weight-bold shadow-sm" style="border-radius: 8px;"><i class="fas fa-exclamation-triangle mr-1"></i> <?php echo $error_msg; ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <div class="settings-card shadow-sm mb-4">
                <div class="card-body p-4">
                    <!-- Added enctype="multipart/form-data" for file uploads -->
                    <form method="POST" action="" enctype="multipart/form-data">
                        <h5 class="font-weight-bold text-dark mb-4 border-bottom pb-2">General Information</h5>
                        
                        <div class="form-group mb-4">
                            <label class="font-weight-bold text-muted small text-uppercase">Hotel Name</label>
                            <input type="text" name="hotel_name" class="form-control" value="<?php echo htmlspecialchars($settings['hotel_name'] ?? ''); ?>" required>
                        </div>

                        <!-- Logo Upload Field -->
                        <div class="form-group mb-4">
                            <label class="font-weight-bold text-muted small text-uppercase">Upload System Logo</label>
                            <div class="custom-file">
                                <input type="file" name="hotel_logo" class="custom-file-input" id="logoUpload" accept="image/png, image/jpeg, image/gif">
                                <label class="custom-file-label" for="logoUpload">Choose an image file...</label>
                            </div>
                            <small class="text-muted mt-1 d-block">Recommended format: PNG with transparent background. Max size: 2MB.</small>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6 form-group mb-md-0">
                                <label class="font-weight-bold text-muted small text-uppercase">Contact Email</label>
                                <input type="email" name="hotel_email" class="form-control" value="<?php echo htmlspecialchars($settings['hotel_email'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6 form-group mb-md-0">
                                <label class="font-weight-bold text-muted small text-uppercase">Phone Number</label>
                                <input type="text" name="hotel_phone" class="form-control" value="<?php echo htmlspecialchars($settings['hotel_phone'] ?? ''); ?>" required>
                            </div>
                        </div>

                        <div class="form-group mb-4">
                            <label class="font-weight-bold text-muted small text-uppercase">Physical Address</label>
                            <textarea name="hotel_address" class="form-control" rows="3" required><?php echo htmlspecialchars($settings['hotel_address'] ?? ''); ?></textarea>
                        </div>

                        <div class="mt-4 pt-3 border-top">
                            <button type="submit" class="btn btn-success px-4 py-2 font-weight-bold" style="background-color: #10b981; border: none;">
                                <i class="fas fa-save mr-1"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="settings-card shadow-sm p-4 text-center">
                <!-- Dynamic Logo Display -->
                <?php if(!empty($settings['hotel_logo']) && file_exists('../../assets/images/' . $settings['hotel_logo'])): ?>
                    <img src="../../assets/images/<?php echo htmlspecialchars($settings['hotel_logo']); ?>" alt="Hotel Logo" style="width: 120px; height: 120px; object-fit: contain; margin-bottom: 20px;">
                <?php else: ?>
                    <div style="width: 80px; height: 80px; background: #e0f2fe; color: #0369a1; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; margin: 0 auto 20px auto;">
                        <i class="fas fa-hotel"></i>
                    </div>
                <?php endif; ?>
                
                <h5 class="font-weight-bold text-dark"><?php echo htmlspecialchars($settings['hotel_name'] ?? 'Afrik Hotel'); ?></h5>
                <p class="text-muted small mb-0"><i class="fas fa-map-marker-alt mr-1"></i> <?php echo htmlspecialchars($settings['hotel_address'] ?? 'Mogadishu'); ?></p>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

<!-- Script to show selected file name in the upload box -->
<script>
    document.querySelector('.custom-file-input').addEventListener('change', function(e) {
        var fileName = document.getElementById("logoUpload").files[0].name;
        var nextSibling = e.target.nextElementSibling;
        nextSibling.innerText = fileName;
    });
</script>