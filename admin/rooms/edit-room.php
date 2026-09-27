<?php
require_once '../../includes/auth_check.php';
require_once '../../config/database.php';
requirePermission('admin/rooms/rooms.php');

$error = '';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: rooms.php");
    exit();
}

$id = $_GET['id'];

// Fetch existing room data
$stmt = $conn->prepare("SELECT * FROM rooms WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$room = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$room) {
    header("Location: rooms.php");
    exit();
}

$types = $conn->query("SELECT id, type_name FROM room_types");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $room_number = $_POST['room_number'];
    $room_type_id = $_POST['room_type_id'];
    $floor = $_POST['floor'];
    $status = $_POST['status'];

    $check = $conn->prepare("SELECT id FROM rooms WHERE room_number = ? AND id != ?");
    $check->bind_param("si", $room_number, $id);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $error = "Room number already exists!";
    } else {
        // Calling the Stored Procedure
        $update_stmt = $conn->prepare("CALL UpdateRoom(?, ?, ?, ?, ?)");
        // bind_param: integer, string, integer, integer, string (isiis)
        $update_stmt->bind_param("isiis", $id, $room_number, $room_type_id, $floor, $status);
        
        if ($update_stmt->execute()) {
            header("Location: rooms.php");
            exit();
        } else {
            $error = "Database error: " . $conn->error;
        }
    }
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/navbar.php';
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Edit Room</h1>
    
    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="card shadow-sm border-0" style="max-width: 600px; border-radius: 12px;">
        <div class="card-body p-4">
            <form method="POST" action="">
                <div class="form-group">
                    <label class="font-weight-bold text-muted small text-uppercase">Room Number</label>
                    <input type="text" name="room_number" class="form-control" value="<?php echo htmlspecialchars($room['room_number']); ?>" required>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold text-muted small text-uppercase">Room Type</label>
                    <select name="room_type_id" class="form-control" required>
                        <?php while($t = $types->fetch_assoc()): ?>
                            <option value="<?php echo $t['id']; ?>" <?php if($room['room_type_id'] == $t['id']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($t['type_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold text-muted small text-uppercase">Floor</label>
                    <input type="number" name="floor" class="form-control" value="<?php echo htmlspecialchars($room['floor']); ?>" required>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold text-muted small text-uppercase">Status</label>
                    <select name="status" class="form-control">
                        <option value="Available" <?php if($room['status'] == 'Available') echo 'selected'; ?>>Available</option>
                        <option value="Occupied" <?php if($room['status'] == 'Occupied') echo 'selected'; ?>>Occupied</option>
                        <option value="Reserved" <?php if($room['status'] == 'Reserved') echo 'selected'; ?>>Reserved</option>
                        <option value="Maintenance" <?php if($room['status'] == 'Maintenance') echo 'selected'; ?>>Maintenance</option>
                    </select>
                </div>
                <div class="mt-4">
                    <button type="submit" class="btn btn-warning px-4">Update Room</button>
                    <a href="rooms.php" class="btn btn-light border px-4 ml-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>