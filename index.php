<?php
require_once 'includes/auth_check.php';
require_once 'config/database.php';

/*
|--------------------------------------------------------------------------
| Main Dashboard Statistics
|--------------------------------------------------------------------------
*/

$total_rooms = 0;
$available_rooms = 0;
$total_guests = 0;
$todays_bookings = 0;

$query = $conn->query("SELECT COUNT(*) AS count FROM rooms");
if ($query) {
    $total_rooms = (int) $query->fetch_assoc()['count'];
}

$query = $conn->query("SELECT COUNT(*) AS count FROM rooms WHERE status = 'Available'");
if ($query) {
    $available_rooms = (int) $query->fetch_assoc()['count'];
}

$query = $conn->query("SELECT COUNT(*) AS count FROM customers");
if ($query) {
    $total_guests = (int) $query->fetch_assoc()['count'];
}

$query = $conn->query("
    SELECT COUNT(*) AS count
    FROM bookings
    WHERE DATE(created_at) = CURDATE()
");
if ($query) {
    $todays_bookings = (int) $query->fetch_assoc()['count'];
}

/*
|--------------------------------------------------------------------------
| Hotel Operations Snapshot
|--------------------------------------------------------------------------
*/

$ops = [
    'staff'       => 0,
    'attendance'  => 0,
    'housekeeping'=> 0,
    'maintenance' => 0,
    'stock'       => 0
];

$query = $conn->query("
    SELECT COUNT(*) AS count
    FROM staff
    WHERE status = 'Active'
");
if ($query) {
    $ops['staff'] = (int) $query->fetch_assoc()['count'];
}

$query = $conn->query("
    SELECT COUNT(*) AS count
    FROM staff_attendance
    WHERE attendance_date = CURDATE()
    AND status = 'Present'
");
if ($query) {
    $ops['attendance'] = (int) $query->fetch_assoc()['count'];
}

$query = $conn->query("
    SELECT COUNT(*) AS count
    FROM housekeeping_tasks
    WHERE status IN ('Pending', 'In Progress')
");
if ($query) {
    $ops['housekeeping'] = (int) $query->fetch_assoc()['count'];
}

$query = $conn->query("
    SELECT COUNT(*) AS count
    FROM maintenance_requests
    WHERE status NOT IN ('Resolved', 'Closed')
");
if ($query) {
    $ops['maintenance'] = (int) $query->fetch_assoc()['count'];
}

$query = $conn->query("
    SELECT COUNT(*) AS count
    FROM inventory_items
    WHERE status IN ('Low Stock', 'Out of Stock')
");
if ($query) {
    $ops['stock'] = (int) $query->fetch_assoc()['count'];
}

/*
|--------------------------------------------------------------------------
| Recent Bookings
|--------------------------------------------------------------------------
*/

$recent_query = "
    SELECT 
        b.id,
        b.check_in,
        b.status,
        c.full_name,
        r.room_number
    FROM bookings b
    INNER JOIN customers c ON b.customer_id = c.id
    INNER JOIN rooms r ON b.room_id = r.id
    ORDER BY b.created_at DESC
    LIMIT 6
";

$recent_bookings = $conn->query($recent_query);

include 'includes/header.php';
include 'includes/sidebar.php';
include 'includes/navbar.php';
?>

<style>
    /* Default (Light Mode) Variables */
    :root {
        --primary-gradient: linear-gradient(135deg, #003f88 0%, #0056b3 100%);
        --card-bg: #ffffff;
        --text-main: #1e293b;
        --text-muted: #64748b;
        --border-color: #f1f5f9;
        --body-bg: #f8fafc;
        --table-hover: #f8fafc;
    }

    /* Dark Mode Variables (Waxay shaqeynaysaa marka body-ga ama html-ka uu leeyahay class-ka 'dark-mode') */
    body.dark-mode, .dark-mode {
        --primary-gradient: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        --card-bg: #1e293b;
        --text-main: #f8fafc;
        --text-muted: #94a3b8;
        --border-color: #334155;
        --body-bg: #0f172a;
        --table-hover: #273548;
    }

    body {
        background: var(--body-bg);
        color: var(--text-main);
        transition: background 0.3s ease, color 0.3s ease;
    }

    .dashboard-wrapper {
        padding: 30px;
    }

    /* Modern Hero Header Banner */
    .dashboard-banner {
        background: var(--primary-gradient);
        border-radius: 20px;
        padding: 30px 35px;
        color: #fff;
        margin-bottom: 30px;
        box-shadow: 0 10px 25px rgba(0, 63, 136, 0.15);
        position: relative;
        overflow: hidden;
    }

    .dashboard-banner::after {
        content: "";
        position: absolute;
        right: -30px;
        bottom: -50px;
        width: 200px;
        height: 200px;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 50%;
    }

    .dashboard-banner h1 {
        font-size: 26px;
        font-weight: 800;
        margin-bottom: 8px;
        letter-spacing: -0.5px;
    }

    .dashboard-banner p {
        font-size: 14px;
        opacity: 0.85;
        margin-bottom: 0;
    }

    /* Stat Cards */
    .stat-card {
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
        transition: all 0.3s ease;
        position: relative;
        height: 100%;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.06);
    }

    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }

    .stat-label {
        color: var(--text-muted);
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
    }

    .stat-number {
        font-size: 28px;
        font-weight: 800;
        color: var(--text-main);
        line-height: 1;
        margin-bottom: 5px;
    }

    .stat-desc {
        font-size: 11px;
        color: var(--text-muted);
        margin: 0;
    }

    /* Operations Grid Cards */
    .operation-card {
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 14px;
        padding: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.01);
        transition: all 0.2s ease;
        height: 100%;
    }

    .operation-card:hover {
        border-color: var(--text-muted);
        box-shadow: 0 6px 15px rgba(0,0,0,0.04);
    }

    .operation-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: var(--border-color);
        color: #003f88;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        margin-bottom: 15px;
    }

    .operation-title {
        font-size: 11px;
        font-weight: 700;
        color: var(--text-muted);
        text-transform: uppercase;
        margin-bottom: 5px;
    }

    .operation-count {
        font-size: 22px;
        font-weight: 800;
        color: var(--text-main);
        margin-bottom: 10px;
    }

    .operation-link {
        font-size: 12px;
        font-weight: 700;
        color: #3b82f6;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
    }

    .operation-link:hover {
        text-decoration: underline;
    }

    /* Recent Bookings Table Card */
    .table-card {
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 18px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.02);
        overflow: hidden;
    }

    .table-header {
        padding: 22px 25px;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .table-header h5 {
        font-size: 16px;
        font-weight: 800;
        color: var(--text-main);
        margin: 0;
    }

    .custom-table {
        margin-bottom: 0;
        color: var(--text-main);
    }

    .custom-table th {
        background: var(--body-bg);
        color: var(--text-muted);
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-top: none;
        border-bottom: 1px solid var(--border-color);
        padding: 15px 20px;
    }

    .custom-table td {
        padding: 16px 20px;
        vertical-align: middle;
        font-size: 13px;
        color: var(--text-main);
        border-top: 1px solid var(--border-color);
    }

    .custom-table tbody tr:hover {
        background: var(--table-hover);
    }

    /* Status Badges */
    .badge-status {
        padding: 6px 12px;
        border-radius: 30px;
        font-size: 11px;
        font-weight: 700;
    }

    .badge-pending { background: rgba(245, 158, 11, 0.15); color: #f59e0b; }
    .badge-confirmed { background: rgba(16, 185, 129, 0.15); color: #10b981; }
    .badge-cancelled { background: rgba(239, 68, 68, 0.15); color: #ef4444; }
</style>

<div class="container-fluid dashboard-wrapper">

    <!-- Hero Banner -->
    <div class="dashboard-banner d-flex justify-content-between align-items-center">
        <div>
            <h1>Dashboard Overview</h1>
            <p>Welcome back to Afrik Hotel Management System. Monitor your key metrics and hotel activities here.</p>
        </div>
        <div class="d-none d-md-block">
            <span class="badge badge-light px-3 py-2 rounded-pill text-dark font-weight-bold shadow-sm">
                <i class="fas fa-calendar-alt mr-2 text-success"></i><?php echo date('l, M d, Y'); ?>
            </span>
        </div>
    </div>

    <!-- Main Statistics Cards -->
    <div class="row mb-4">
        
        <!-- Total Rooms -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-label">Total Rooms</div>
                        <div class="stat-number"><?php echo number_format($total_rooms); ?></div>
                        <p class="stat-desc">Registered hotel rooms</p>
                    </div>
                    <div class="stat-icon" style="background: rgba(0, 63, 136, 0.15); color: #3b82f6;">
                        <i class="fas fa-bed"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Available Rooms -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-label">Available Rooms</div>
                        <div class="stat-number"><?php echo number_format($available_rooms); ?></div>
                        <p class="stat-desc">Ready for booking</p>
                    </div>
                    <div class="stat-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
                        <i class="fas fa-door-open"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Guests -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-label">Total Guests</div>
                        <div class="stat-number"><?php echo number_format($total_guests); ?></div>
                        <p class="stat-desc">Registered customers</p>
                    </div>
                    <div class="stat-icon" style="background: rgba(14, 165, 233, 0.15); color: #0ea5e9;">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Today's Bookings -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-label">Today's Bookings</div>
                        <div class="stat-number"><?php echo number_format($todays_bookings); ?></div>
                        <p class="stat-desc">Bookings created today</p>
                    </div>
                    <div class="stat-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Operations Snapshot -->
    <div class="mb-4">
        <h5 class="font-weight-bold mb-3" style="color: var(--text-main); font-size: 1.1rem;">
            <i class="fas fa-chart-pie mr-2 text-primary"></i> Hotel Operations Snapshot
        </h5>

        <div class="row">
            
            <!-- Active Staff -->
            <div class="col-xl col-lg-4 col-md-6 mb-3">
                <div class="operation-card">
                    <div class="operation-icon"><i class="fas fa-user-tie"></i></div>
                    <div class="operation-title">Active Staff</div>
                    <div class="operation-count"><?php echo number_format($ops['staff']); ?></div>
                    <a href="admin/operations/staff.php" class="operation-link">Manage staff <i class="fas fa-arrow-right ml-1 small"></i></a>
                </div>
            </div>

            <!-- Present Today -->
            <div class="col-xl col-lg-4 col-md-6 mb-3">
                <div class="operation-card">
                    <div class="operation-icon"><i class="fas fa-user-check"></i></div>
                    <div class="operation-title">Present Today</div>
                    <div class="operation-count"><?php echo number_format($ops['attendance']); ?></div>
                    <a href="admin/operations/attendance.php" class="operation-link">View attendance <i class="fas fa-arrow-right ml-1 small"></i></a>
                </div>
            </div>

            <!-- Housekeeping -->
            <div class="col-xl col-lg-4 col-md-6 mb-3">
                <div class="operation-card">
                    <div class="operation-icon"><i class="fas fa-broom"></i></div>
                    <div class="operation-title">Housekeeping</div>
                    <div class="operation-count"><?php echo number_format($ops['housekeeping']); ?></div>
                    <a href="admin/operations/housekeeping.php" class="operation-link">Open tasks <i class="fas fa-arrow-right ml-1 small"></i></a>
                </div>
            </div>

            <!-- Maintenance -->
            <div class="col-xl col-lg-4 col-md-6 mb-3">
                <div class="operation-card">
                    <div class="operation-icon"><i class="fas fa-tools"></i></div>
                    <div class="operation-title">Maintenance</div>
                    <div class="operation-count"><?php echo number_format($ops['maintenance']); ?></div>
                    <a href="admin/operations/maintenance.php" class="operation-link">Open requests <i class="fas fa-arrow-right ml-1 small"></i></a>
                </div>
            </div>

            <!-- Low Stock -->
            <div class="col-xl col-lg-4 col-md-6 mb-3">
                <div class="operation-card">
                    <div class="operation-icon"><i class="fas fa-boxes"></i></div>
                    <div class="operation-title">Low Stock Alerts</div>
                    <div class="operation-count"><?php echo number_format($ops['stock']); ?></div>
                    <a href="admin/operations/inventory.php" class="operation-link">View inventory <i class="fas fa-arrow-right ml-1 small"></i></a>
                </div>
            </div>

        </div>
    </div>

    <!-- Recent Bookings Table -->
    <div class="table-card">
        <div class="table-header">
            <h5><i class="far fa-clock mr-2 text-primary"></i> Recent Bookings</h5>
            <a href="admin/bookings/bookings.php" class="btn btn-sm btn-outline-primary font-weight-bold px-3" style="border-radius: 8px;">
                View All <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>

        <div class="table-responsive">
            <table class="table custom-table">
                <thead>
                    <tr>
                        <th class="pl-4">Booking ID</th>
                        <th>Guest Name</th>
                        <th>Room</th>
                        <th>Check-In Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recent_bookings && $recent_bookings->num_rows > 0): ?>
                        <?php while ($row = $recent_bookings->fetch_assoc()): ?>
                            <?php
                            $status = $row['status'];
                            $badge_class = 'badge-secondary';
                            if ($status === 'Pending') $badge_class = 'badge-pending';
                            elseif ($status === 'Confirmed') $badge_class = 'badge-confirmed';
                            elseif ($status === 'Cancelled') $badge_class = 'badge-cancelled';
                            ?>
                            <tr>
                                <td class="pl-4 font-weight-bold text-muted">#<?php echo str_pad($row['id'], 4, '0', STR_PAD_LEFT); ?></td>
                                <td class="font-weight-bold" style="color: var(--text-main);"><?php echo htmlspecialchars($row['full_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <span class="badge badge-light border px-2 py-1" style="background: var(--body-bg) !important; color: var(--text-main); border-color: var(--border-color) !important;">
                                        <i class="fas fa-key text-primary mr-1"></i> <?php echo htmlspecialchars($row['room_number'], ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </td>
                                <td><?php echo !empty($row['check_in']) ? date('M d, Y', strtotime($row['check_in'])) : 'N/A'; ?></td>
                                <td>
                                    <span class="badge-status <?php echo $badge_class; ?>">
                                        <?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="far fa-calendar-times fa-2x mb-2 d-block"></i> No recent bookings found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php include 'includes/footer.php'; ?>