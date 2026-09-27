<?php
require_once '../../includes/auth_check.php';
require_once '../../config/database.php';
requirePermission('admin/customers/customers.php');

$error_msg = '';
$success_msg = '';

// 1. Handle Add New Guest (Modal)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_customer'])) {
    $full_name = trim($_POST['full_name']);
    $id_number = trim($_POST['id_number']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $nationality = trim($_POST['nationality']);

    $check = $conn->prepare("SELECT id FROM customers WHERE phone = ? OR (email = ? AND email != '')");
    $check->bind_param("ss", $phone, $email);
    $check->execute();
    
    if ($check->get_result()->num_rows > 0) {
        $error_msg = "A guest with this phone number or email already exists.";
    } else {
        $stmt = $conn->prepare("INSERT INTO customers (full_name, id_number, phone, email, nationality, password) VALUES (?, ?, ?, ?, ?, '')");
        $stmt->bind_param("sssss", $full_name, $id_number, $phone, $email, $nationality);
        if ($stmt->execute()) {
            $success_msg = "Guest successfully added!";
        } else {
            $error_msg = "Error adding guest: " . $conn->error;
        }
        $stmt->close();
    }
    $check->close();
}

// 2. Calculate Card Statistics
$total_customers = $conn->query("SELECT COUNT(*) as cnt FROM customers")->fetch_assoc()['cnt'];

$new_this_month = $conn->query("
    SELECT COUNT(*) as cnt FROM customers 
    WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) 
    AND YEAR(created_at) = YEAR(CURRENT_DATE())
")->fetch_assoc()['cnt'];

$active_customers = $conn->query("SELECT COUNT(DISTINCT customer_id) as cnt FROM bookings")->fetch_assoc()['cnt'];
$inactive_customers = $total_customers - $active_customers;

// 3. Fetch all customers
$customers = $conn->query("SELECT * FROM customers ORDER BY id DESC");

include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/navbar.php';
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap4.min.css">

<style>
    .page-header { margin-bottom: 30px; }
    .stat-card { border: 1px solid #edf2f7; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); background: #fff; padding: 20px; height: 100%; }
    .stat-title { color: #64748b; font-size: 0.9rem; margin-bottom: 8px; }
    .stat-value { color: #0f172a; font-size: 2rem; font-weight: 700; margin: 0; line-height: 1; }
    .list-card { border: 1px solid #edf2f7; border-radius: 12px; background: #fff; }
    .list-header { border-bottom: 1px solid #edf2f7; padding: 20px; display: flex; justify-content: space-between; align-items: center; }
    .avatar-circle { width: 40px; height: 40px; background-color: #e0f2fe; color: #0369a1; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 14px; margin-right: 15px; }
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
            <h1 class="h3 font-weight-bold text-dark mb-1">Guests</h1>
            <p class="text-muted small mb-0">Manage and view all registered guests.</p>
        </div>
        <div>
            <button onclick="window.print()" class="btn btn-light shadow-sm mr-2 border"><i class="fas fa-print"></i> Print List</button>
            <button class="btn btn-green shadow-sm font-weight-bold" data-toggle="modal" data-target="#addCustomerModal" style="background-color: #10b981; color: #fff; border: none; border-radius: 8px; padding: 8px 16px;">
                <i class="fas fa-plus mr-1"></i> Add Guest
            </button>
        </div>
    </div>

    <?php if($success_msg): ?>
        <div class="alert alert-success font-weight-bold shadow-sm" style="border-radius: 8px;">
            <i class="fas fa-check-circle mr-1"></i> <?php echo $success_msg; ?>
        </div>
    <?php endif; ?>

    <?php if($error_msg): ?>
        <div class="alert alert-danger font-weight-bold shadow-sm" style="border-radius: 8px;">
            <i class="fas fa-exclamation-circle mr-1"></i> <?php echo $error_msg; ?>
        </div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-title">Total Guests</div>
                <div class="stat-value"><?php echo $total_customers; ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-title">Active Guests</div>
                <div class="stat-value text-primary"><?php echo $active_customers; ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-title">Inactive Guests</div>
                <div class="stat-value text-secondary"><?php echo $inactive_customers; ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-title">New This Month</div>
                <div class="stat-value text-success"><?php echo $new_this_month; ?></div>
            </div>
        </div>
    </div>

    <div class="list-card shadow-sm mb-4">
        <div class="list-header">
            <div>
                <h5 class="font-weight-bold text-dark mb-1">Guest List</h5>
                <p class="text-muted small mb-0">All registered guests in Afrik Hotel.</p>
            </div>
            <div class="text-primary font-weight-bold small">
                <?php echo $total_customers; ?> guests
            </div>
        </div>
        
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="exportTable" width="100%">
                    <thead class="bg-light text-muted small text-uppercase">
                        <tr>
                            <th>Guest</th>
                            <th>ID / Passport</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Nationality</th>
                            <th class="action-col text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($customers->num_rows > 0): ?>
                            <?php while($row = $customers->fetch_assoc()): 
                                $names = explode(" ", $row['full_name']);
                                $initials = substr($names[0], 0, 1) . (isset($names[1]) ? substr($names[1], 0, 1) : '');
                            ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle"><?php echo strtoupper($initials); ?></div>
                                        <div>
                                            <strong class="d-block text-dark"><?php echo htmlspecialchars($row['full_name']); ?></strong>
                                            <span class="text-muted small">ID: #<?php echo $row['id']; ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($row['id_number'] ?: '-'); ?></td>
                                <td><?php echo htmlspecialchars($row['phone']); ?></td>
                                <td><?php echo htmlspecialchars($row['email'] ?: 'N/A'); ?></td>
                                <td>
                                    <span class="badge badge-soft-info"><?php echo htmlspecialchars($row['nationality'] ?: 'N/A'); ?></span>
                                </td>
                                <td class="action-col text-right">
                                    <!-- Print Details triggers JavaScript modal -->
                                    <button type="button" onclick="openPrintPreview(<?php echo $row['id']; ?>)" class="btn btn-dark btn-sm font-weight-bold">
                                        <i class="fas fa-print"></i> Print Details
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ADD NEW GUEST MODAL -->
<div class="modal fade" id="addCustomerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0" style="border-radius: 12px;">
            <div class="modal-header border-0" style="background-color: #0c2340; color: #fff; border-radius: 12px 12px 0 0;">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-user-plus mr-2 text-success"></i> Register New Guest</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form method="POST" action="">
                <div class="modal-body p-4">
                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-muted small text-uppercase">Full Name</label>
                        <input type="text" name="full_name" class="form-control" required placeholder="e.g. Ali Hassan">
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group mb-3">
                            <label class="font-weight-bold text-muted small text-uppercase">Phone Number</label>
                            <input type="text" name="phone" class="form-control" required placeholder="e.g. 061XXXXXXX">
                        </div>
                        <div class="col-md-6 form-group mb-3">
                            <label class="font-weight-bold text-muted small text-uppercase">Email Address</label>
                            <input type="email" name="email" class="form-control" placeholder="Optional">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group mb-3">
                            <label class="font-weight-bold text-muted small text-uppercase">ID / Passport</label>
                            <input type="text" name="id_number" class="form-control" placeholder="Optional">
                        </div>
                        <div class="col-md-6 form-group mb-3">
                            <label class="font-weight-bold text-muted small text-uppercase">Nationality</label>
                            <input type="text" name="nationality" class="form-control" placeholder="e.g. Somali">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light" style="border-radius: 0 0 12px 12px;">
                    <button type="button" class="btn btn-secondary px-4" data-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_customer" class="btn btn-success px-4 font-weight-bold" style="background-color: #10b981; border: none;">Save Guest</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- PRINT PREVIEW MODAL -->
<div class="modal fade" id="printPreviewModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content border-0" style="border-radius: 12px; background: #e2e8f0;">
            <div class="modal-header border-0 bg-dark text-white" style="border-radius: 12px 12px 0 0;">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-file-invoice mr-2"></i> Document Preview</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <!-- The iframe holds the document securely -->
            <div class="modal-body p-0" style="height: 70vh;">
                <iframe id="printIframe" src="" style="width: 100%; height: 100%; border: none;"></iframe>
            </div>
            <div class="modal-footer border-0 bg-white" style="border-radius: 0 0 12px 12px;">
                <button type="button" class="btn btn-light border px-4" data-dismiss="modal">Close Preview</button>
                <!-- This button directly calls the browser's native print function targeting only the iframe -->
                <button type="button" class="btn btn-success font-weight-bold px-4" onclick="document.getElementById('printIframe').contentWindow.print();" style="background-color: #10b981; border: none;">
                    <i class="fas fa-print mr-2"></i> Print Document
                </button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript to control the Print Modal -->
<script>
    function openPrintPreview(customerId) {
        // Set the iframe source to the clean print page
        document.getElementById('printIframe').src = 'print-customer.php?id=' + customerId;
        // Open the modal
        $('#printPreviewModal').modal('show');
    }
</script>

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
            { extend: 'excelHtml5', text: '<i class="fas fa-file-excel"></i> Excel', className: 'btn btn-sm', exportOptions: { columns: [0, 1, 2, 3, 4] } },
            { extend: 'pdfHtml5', text: '<i class="fas fa-file-pdf"></i> PDF', className: 'btn btn-sm', exportOptions: { columns: [0, 1, 2, 3, 4] } }
        ],
        pageLength: 10,
        language: { search: "", searchPlaceholder: "Search guests..." }
    });
});
</script>

<?php include '../../includes/footer.php'; ?>