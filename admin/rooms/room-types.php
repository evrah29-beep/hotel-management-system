<?php
require_once '../../includes/auth_check.php';
require_once '../../config/database.php';
requirePermission('admin/rooms/room-types.php');

$success_msg = '';
$error_msg = '';

// 1. Handle Add Room Type Submission (Modal)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_type'])) {
    $type_name = $_POST['type_name'];
    $price = $_POST['price_per_night'];
    $description = $_POST['description'];

    $stmt = $conn->prepare("INSERT INTO room_types (type_name, price_per_night, description) VALUES (?, ?, ?)");
    $stmt->bind_param("sds", $type_name, $price, $description);
    if ($stmt->execute()) {
        $success_msg = "Room type added successfully!";
    } else {
        $error_msg = "Error adding room type: " . $conn->error;
    }
    $stmt->close();
}

// 2. Handle Edit Room Type Submission (Modal)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_type'])) {
    $id = intval($_POST['type_id']);
    $type_name = $_POST['type_name'];
    $price = $_POST['price_per_night'];
    $description = $_POST['description'];

    $stmt = $conn->prepare("UPDATE room_types SET type_name = ?, price_per_night = ?, description = ? WHERE id = ?");
    $stmt->bind_param("sdsi", $type_name, $price, $description, $id);
    if ($stmt->execute()) {
        $success_msg = "Room type updated successfully!";
    } else {
        $error_msg = "Error updating room type: " . $conn->error;
    }
    $stmt->close();
}

// 3. Handle Delete Request
if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    $check_stmt = $conn->prepare("SELECT id FROM rooms WHERE room_type_id = ?");
    $check_stmt->bind_param("i", $delete_id);
    $check_stmt->execute();
    if ($check_stmt->get_result()->num_rows > 0) {
        $error_msg = "Cannot delete! Physical rooms are currently assigned to this type.";
    } else {
        $del_stmt = $conn->prepare("DELETE FROM room_types WHERE id = ?");
        $del_stmt->bind_param("i", $delete_id);
        $del_stmt->execute();
        header("Location: room-types.php");
        exit();
    }
}

$types_result = $conn->query("SELECT * FROM room_types ORDER BY id DESC");
$total_types = $types_result->num_rows;

include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/navbar.php';
?>

<!-- DataTables CSS for Export Buttons -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap4.min.css">

<style>
    .page-header { margin-bottom: 30px; }
    .list-card { border: 1px solid #edf2f7; border-radius: 12px; background: #fff; }
    .list-header { border-bottom: 1px solid #edf2f7; padding: 20px; display: flex; justify-content: space-between; align-items: center; }
    
    .type-icon {
        width: 40px; height: 40px; background-color: #f1f5f9; color: #10b981; 
        border-radius: 8px; display: flex; align-items: center; justify-content: center; 
        font-size: 16px; margin-right: 15px;
    }
    
    .dt-buttons .btn { background-color: #f8fafc; border: 1px solid #e2e8f0; color: #475569; margin-right: 5px; border-radius: 6px; }
    .dt-buttons .btn:hover { background-color: #e2e8f0; }
    
    @media print {
        #sidebar, .top-navbar, .dt-buttons, .dataTables_filter, .action-col { display: none !important; }
        #content { width: 100% !important; margin: 0 !important; padding: 0 !important; }
        .list-card { border: none; box-shadow: none; }
    }
</style>

<div class="container-fluid p-0">

    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h1 class="h3 font-weight-bold text-dark mb-1">Room Types</h1>
            <p class="text-muted small mb-0">Manage pricing and categories for your rooms.</p>
        </div>
        <div>
            <button onclick="window.print()" class="btn btn-light shadow-sm mr-2 border"><i class="fas fa-print"></i> Print</button>
            <button class="btn btn-green shadow-sm" data-toggle="modal" data-target="#addTypeModal"><i class="fas fa-plus"></i> Add Room Type</button>
        </div>
    </div>

    <?php if($success_msg): ?>
        <div class="alert alert-success font-weight-bold shadow-sm" style="border-radius: 8px;">
            <i class="fas fa-check-circle mr-2"></i> <?php echo $success_msg; ?>
        </div>
    <?php endif; ?>
    <?php if($error_msg): ?>
        <div class="alert alert-danger font-weight-bold shadow-sm" style="border-radius: 8px;">
            <i class="fas fa-exclamation-circle mr-2"></i> <?php echo $error_msg; ?>
        </div>
    <?php endif; ?>

    <div class="list-card shadow-sm mb-4">
        <div class="list-header">
            <h5 class="font-weight-bold text-dark mb-0">Category List</h5>
            <div class="text-primary font-weight-bold small"><?php echo $total_types; ?> types</div>
        </div>
        
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="exportTable" width="100%">
                    <thead class="bg-light text-muted small text-uppercase">
                        <tr>
                            <th>Type Name</th>
                            <th>Price / Night</th>
                            <th>Description</th>
                            <th class="action-col text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($types_result->num_rows > 0): ?>
                            <?php while($row = $types_result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="type-icon"><i class="fas fa-tags"></i></div>
                                        <strong class="text-dark"><?php echo htmlspecialchars($row['type_name']); ?></strong>
                                    </div>
                                </td>
                                <td class="font-weight-bold text-dark">$<?php echo number_format($row['price_per_night'], 2); ?></td>
                                <td class="text-muted small"><?php echo htmlspecialchars($row['description']); ?></td>
                                <td class="action-col text-right">
                                    <!-- Changed from Link to Modal Button -->
                                    <button class="btn btn-light btn-sm text-primary font-weight-bold mr-1" data-toggle="modal" data-target="#editTypeModal<?php echo $row['id']; ?>">Edit</button>
                                    <a href="room-types.php?delete=<?php echo $row['id']; ?>" class="btn btn-light btn-sm text-danger font-weight-bold" onclick="return confirm('Delete this room type?');">Delete</a>
                                </td>
                            </tr>

                            <!-- Edit Room Type Modal -->
                            <div class="modal fade" id="editTypeModal<?php echo $row['id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content border-0" style="border-radius: 12px;">
                                        <div class="modal-header bg-light border-0">
                                            <h5 class="modal-title font-weight-bold">Edit Category</h5>
                                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                                        </div>
                                        <form method="POST" action="">
                                            <div class="modal-body p-4 text-left">
                                                <input type="hidden" name="type_id" value="<?php echo $row['id']; ?>">
                                                
                                                <div class="form-group">
                                                    <label class="font-weight-bold text-muted small text-uppercase">Type Name</label>
                                                    <input type="text" name="type_name" class="form-control" value="<?php echo htmlspecialchars($row['type_name']); ?>" required>
                                                </div>
                                                <div class="form-group">
                                                    <label class="font-weight-bold text-muted small text-uppercase">Price Per Night ($)</label>
                                                    <input type="number" step="0.01" name="price_per_night" class="form-control" value="<?php echo htmlspecialchars($row['price_per_night']); ?>" required>
                                                </div>
                                                <div class="form-group">
                                                    <label class="font-weight-bold text-muted small text-uppercase">Description</label>
                                                    <textarea name="description" class="form-control" rows="3" required><?php echo htmlspecialchars($row['description']); ?></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer border-0">
                                                <button type="button" class="btn btn-light border" data-dismiss="modal">Cancel</button>
                                                <button type="submit" name="edit_type" class="btn btn-primary font-weight-bold" style="background-color: #10b981; border: none;">Save Changes</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Room Type Modal -->
<div class="modal fade" id="addTypeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0" style="border-radius: 12px;">
            <div class="modal-header bg-light border-0">
                <h5 class="modal-title font-weight-bold">Add New Category</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form method="POST" action="">
                <div class="modal-body p-4">
                    <div class="form-group">
                        <label class="font-weight-bold text-muted small text-uppercase">Type Name</label>
                        <input type="text" name="type_name" class="form-control" required placeholder="e.g. Presidential Suite">
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold text-muted small text-uppercase">Price Per Night ($)</label>
                        <input type="number" step="0.01" name="price_per_night" class="form-control" required placeholder="e.g. 150.00">
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold text-muted small text-uppercase">Description</label>
                        <textarea name="description" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light border" data-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_type" class="btn btn-green font-weight-bold">Save Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

<!-- DataTables & Export Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap4.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>

<script>
$(document).ready(function() {
    $('#exportTable').DataTable({
        dom: '<"row"<"col-sm-12 col-md-6"B><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        buttons: [
            { extend: 'excelHtml5', text: '<i class="fas fa-file-excel"></i> Excel', className: 'btn btn-sm', exportOptions: { columns: [0, 1, 2] } },
            { extend: 'pdfHtml5', text: '<i class="fas fa-file-pdf"></i> PDF', className: 'btn btn-sm', exportOptions: { columns: [0, 1, 2] } }
        ],
        pageLength: 10,
        language: { search: "", searchPlaceholder: "Search categories..." }
    });
});
</script>