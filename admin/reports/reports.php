<?php
require_once '../../includes/auth_check.php';
require_once '../../config/database.php';
requirePermission('admin/reports/reports.php');

// Set default date range for the input fields ONLY (does not fetch data)
$start_date = date('Y-m-01');
$end_date = date('Y-m-t');
$is_filtered = false; 

$total_revenue = 0;
$total_bookings = 0;
$report_data = null;

// ONLY run the database queries IF the user clicks "Generate Report"
if (isset($_GET['filter']) && !empty($_GET['start_date']) && !empty($_GET['end_date'])) {
    // Secure the dates
    $start_date = $conn->real_escape_string($_GET['start_date']);
    $end_date = $conn->real_escape_string($_GET['end_date']);
    $is_filtered = true; 

    // 1. Calculate Summary Statistics (Revenue based on payment date)
    $revenue_sql = "SELECT SUM(amount) as total FROM payments WHERE status IN ('Paid', 'Completed') AND DATE(payment_date) BETWEEN '$start_date' AND '$end_date'";
    $total_revenue = $conn->query($revenue_sql)->fetch_assoc()['total'] ?? 0;

    // STRICT CHECK: Both check-in and check-out must fall within the user's selected dates
    $bookings_sql = "SELECT COUNT(*) as cnt FROM bookings WHERE status != 'Cancelled' AND DATE(check_in) >= '$start_date' AND DATE(check_out) <= '$end_date'";
    $total_bookings = $conn->query($bookings_sql)->fetch_assoc()['cnt'] ?? 0;

    // 2. Fetch Detailed Report Data (Strictly filtering by BOTH Check-In and Check-Out Date)
    $report_sql = "
        SELECT b.id as booking_id, b.check_in, b.check_out, b.status as booking_status, b.total_price,
               c.full_name, r.room_number,
               IFNULL(SUM(p.amount), 0) as amount_paid,
               (b.total_price - IFNULL(SUM(p.amount), 0)) as balance
        FROM bookings b
        LEFT JOIN customers c ON b.customer_id = c.id
        LEFT JOIN rooms r ON b.room_id = r.id
        LEFT JOIN payments p ON b.id = p.booking_id AND p.status IN ('Paid', 'Completed')
        WHERE DATE(b.check_in) >= '$start_date' AND DATE(b.check_out) <= '$end_date'
        GROUP BY b.id
        ORDER BY b.check_in DESC
    ";
    $report_data = $conn->query($report_sql);
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/navbar.php';
?>

<!-- DataTables CSS for Export -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap4.min.css">

<style>
    .page-header { margin-bottom: 30px; }
    .stat-card { border: 1px solid #edf2f7; border-radius: 12px; background: #fff; padding: 20px; }
    .stat-title { color: #64748b; font-size: 0.9rem; margin-bottom: 8px; }
    .stat-value { color: #0f172a; font-size: 2rem; font-weight: 700; margin: 0; line-height: 1; }
    .list-card { border: 1px solid #edf2f7; border-radius: 12px; background: #fff; }
    .filter-box { background: #f8fafc; border-radius: 12px; padding: 20px; margin-bottom: 25px; border: 1px solid #e2e8f0; }
    
    .dt-buttons .btn { background-color: #f8fafc; border: 1px solid #e2e8f0; color: #475569; margin-right: 5px; border-radius: 6px; }
    .dt-buttons .btn:hover { background-color: #e2e8f0; }
    
    @media print {
        #sidebar, .top-navbar, .filter-box, .dt-buttons, .dataTables_filter { display: none !important; }
        #content { width: 100% !important; margin: 0 !important; padding: 0 !important; }
        .list-card, .stat-card { border: none; box-shadow: none; }
    }
</style>

<div class="container-fluid p-0">
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h1 class="h3 font-weight-bold text-dark mb-1">Financial Reports</h1>
            <p class="text-muted small mb-0">Generate, view, and export business performance data.</p>
        </div>
        <button onclick="window.print()" class="btn btn-light shadow-sm border"><i class="fas fa-print mr-1"></i> Print Report</button>
    </div>

    <!-- Date Filter Form -->
    <div class="filter-box shadow-sm">
        <form method="GET" action="" class="row align-items-end">
            <div class="col-md-3 form-group mb-md-0">
                <label class="font-weight-bold text-muted small text-uppercase">Start Date</label>
                <input type="date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($start_date); ?>" required>
            </div>
            <div class="col-md-3 form-group mb-md-0">
                <label class="font-weight-bold text-muted small text-uppercase">End Date</label>
                <input type="date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($end_date); ?>" required>
            </div>
            <div class="col-md-6 d-flex">
                <button type="submit" name="filter" value="1" class="btn btn-success font-weight-bold flex-grow-1 mr-2" style="background-color: #10b981; border: none;">
                    <i class="fas fa-filter mr-1"></i> Generate Report
                </button>
                <a href="reports.php" class="btn btn-light border font-weight-bold px-4">
                    <i class="fas fa-redo-alt mr-1"></i> Reset
                </a>
            </div>
        </form>
    </div>

    <!-- IF FILTERED: Show the Data -->
    <?php if ($is_filtered): ?>
        
        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="stat-card shadow-sm text-center">
                    <div class="stat-title text-uppercase font-weight-bold">Total Revenue (Selected Period)</div>
                    <div class="stat-value text-success">$<?php echo number_format($total_revenue, 2); ?></div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="stat-card shadow-sm text-center">
                    <div class="stat-title text-uppercase font-weight-bold">Total Bookings (Selected Period)</div>
                    <div class="stat-value text-primary"><?php echo $total_bookings; ?></div>
                </div>
            </div>
        </div>

        <!-- Detailed Report Table -->
        <div class="list-card shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="reportTable" width="100%">
                        <thead class="bg-light text-muted small text-uppercase">
                            <tr>
                                <th>Booking ID</th>
                                <th>Guest Name</th>
                                <th>Room</th>
                                <th>Stay Dates</th>
                                <th>Total Billed</th>
                                <th>Amount Paid</th>
                                <th>Balance Due</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($report_data && $report_data->num_rows > 0): ?>
                                <?php while($row = $report_data->fetch_assoc()): ?>
                                <tr>
                                    <td class="font-weight-bold text-muted">#<?php echo str_pad($row['booking_id'], 5, '0', STR_PAD_LEFT); ?></td>
                                    <td class="font-weight-bold text-dark"><?php echo htmlspecialchars($row['full_name']); ?></td>
                                    <td>
                                        <span class="badge badge-light border text-dark px-2 py-1">Room <?php echo $row['room_number']; ?></span>
                                    </td>
                                    <td class="small">
                                        <span class="text-success font-weight-bold"><?php echo date('M d, Y', strtotime($row['check_in'])); ?></span> to <br>
                                        <span class="text-danger font-weight-bold"><?php echo date('M d, Y', strtotime($row['check_out'])); ?></span>
                                    </td>
                                    <td class="font-weight-bold text-dark">$<?php echo number_format($row['total_price'], 2); ?></td>
                                    <td class="text-success font-weight-bold">$<?php echo number_format($row['amount_paid'], 2); ?></td>
                                    <td>
                                        <?php if($row['balance'] > 0): ?>
                                            <span class="badge badge-danger px-2 py-1">$<?php echo number_format($row['balance'], 2); ?></span>
                                        <?php else: ?>
                                            <span class="badge badge-soft-success px-2 py-1">Settled</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="7" class="text-center py-5 text-muted font-weight-bold">No bookings found strictly within these dates.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <!-- IF NOT FILTERED: Show the Empty State -->
    <?php else: ?>
        <div class="card shadow-sm border-0 text-center py-5" style="border-radius: 12px; background: #fff; margin-top: 20px;">
            <div class="card-body py-5">
                <div style="width: 80px; height: 80px; background: #f1f5f9; color: #94a3b8; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; margin: 0 auto 20px auto;">
                    <i class="fas fa-search-dollar"></i>
                </div>
                <h4 class="font-weight-bold text-dark mb-2">Ready to Generate Report</h4>
                <p class="text-muted mx-auto mb-0" style="font-size: 1.1rem; max-width: 500px;">
                    Please select a start and end date above, then click <strong>Generate Report</strong> to view your financial data and bookings.
                </p>
            </div>
        </div>
    <?php endif; ?>

</div>

<?php include '../../includes/footer.php'; ?>

<!-- DataTables Scripts -->
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
    // Only initialize DataTable if the table actually exists on the screen
    if ($('#reportTable').length) {
        $('#reportTable').DataTable({
            dom: '<"row"<"col-sm-12 col-md-6"B><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
            buttons: [
                { 
                    extend: 'excelHtml5', 
                    text: '<i class="fas fa-file-excel mr-1"></i> Export Excel', 
                    className: 'btn btn-sm font-weight-bold',
                    title: 'Afrik Hotel - Financial Report'
                },
                { 
                    extend: 'pdfHtml5', 
                    text: '<i class="fas fa-file-pdf mr-1"></i> Export PDF', 
                    className: 'btn btn-sm font-weight-bold',
                    title: 'Afrik Hotel - Financial Report'
                }
            ],
            order: [[0, 'desc']], // Sort by Booking ID descending
            pageLength: 25,
            language: { search: "", searchPlaceholder: "Search records..." }
        });
    }
});
</script>