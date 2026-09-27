<?php
require_once '../../includes/auth_check.php';
require_once '../../config/database.php';
requirePermission('admin/rooms/room-types.php');
$error = '';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: room-types.php");
    exit();
}

$id = $_GET['id'];

// Fetch existing room type data
$stmt = $conn->prepare("SELECT * FROM room_types WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$type = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$type) {
    header("Location: room-types.php");
    exit();
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $type_name = $_POST['type_name'];
    $price = $_POST['price_per_night'];
    $description = $_POST['description'];

    // Call the Stored Procedure (Integer, String, Double, String)
    $update_stmt = $conn->prepare("CALL UpdateRoomType(?, ?, ?, ?)");
    $update_stmt->bind_param("isds", $id, $type_name, $price, $description);
    
    if ($update_stmt->execute()) {
        header("Location: room-types.php");
        exit();
    } else {
        $error = "Database error: " . $conn->error;
    }
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/navbar.php';
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Edit Room Type</h1>
    
    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="card shadow-sm border-0" style="max-width: 600px; border-radius: 12px;">
        <div class="card-body p-4">
            <form method="POST" action="">
                <div class="form-group">
                    <label class="font-weight-bold text-muted small text-uppercase">Type Name</label>
                    <input type="text" name="type_name" class="form-control" value="<?php echo htmlspecialchars($type['type_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold text-muted small text-uppercase">Price Per Night ($)</label>
                    <input type="number" step="0.01" name="price_per_night" class="form-control" value="<?php echo htmlspecialchars($type['price_per_night']); ?>" required>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold text-muted small text-uppercase">Description</label>
                    <textarea name="description" class="form-control" rows="4" required><?php echo htmlspecialchars($type['description']); ?></textarea>
                </div>
                <div class="mt-4">
                    <button type="submit" class="btn btn-warning px-4">Update Room Type</button>
                    <a href="room-types.php" class="btn btn-light border px-4 ml-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>