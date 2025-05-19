<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Include database connection
require_once '../db/db_config.php';

// Initialize variables
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'booking';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01'); // First day of current month
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d'); // Today's date
$airline = isset($_GET['airline']) ? $_GET['airline'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Get distinct airlines for filter
$airlines_result = $conn->query("SELECT DISTINCT airline FROM flights ORDER BY airline");
$airlines = [];
while ($row = $airlines_result->fetch_assoc()) {
    $airlines[] = $row['airline'];
}

// Base query for booking report
$booking_data = [];
$flight_data = [];
$revenue_data = [];
$user_data = [];

// Generate report based on type
switch ($report_type) {
    case 'booking':
        // Booking statistics
        $query = "SELECT DATE(b.booking_date) as date, COUNT(*) as count, 
                SUM(b.total_amount) as revenue,
                COUNT(CASE WHEN b.booking_status = 'confirmed' THEN 1 END) as confirmed,
                COUNT(CASE WHEN b.booking_status = 'cancelled' THEN 1 END) as cancelled,
                COUNT(CASE WHEN b.booking_status = 'pending' THEN 1 END) as pending
                FROM bookings b
                WHERE b.booking_date BETWEEN ? AND ?";
        
        $params = [$date_from . ' 00:00:00', $date_to . ' 23:59:59'];
        $types = "ss";
        
        if (!empty($airline)) {
            $query .= " AND b.flight_id IN (SELECT flight_id FROM flights WHERE airline = ?)";
            $params[] = $airline;
            $types .= "s";
        }
        
        if (!empty($status)) {
            $query .= " AND b.booking_status = ?";
            $params[] = $status;
            $types .= "s";
        }
        
        $query .= " GROUP BY DATE(b.booking_date) ORDER BY date DESC";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $booking_data[] = $row;
        }
        
        // Calculate totals
        $total_bookings = 0;
        $total_revenue = 0;
        $total_confirmed = 0;
        $total_cancelled = 0;
        $total_pending = 0;
        
        foreach ($booking_data as $day) {
            $total_bookings += $day['count'];
            $total_revenue += $day['revenue'];
            $total_confirmed += $day['confirmed'];
            $total_cancelled += $day['cancelled'];
            $total_pending += $day['pending'];
        }
        
        break;
        
    case 'flight':
        // Flight statistics
        $query = "SELECT f.airline, f.flight_number, f.departure_city, f.arrival_city, 
                COUNT(b.booking_id) as bookings,
                SUM(CASE WHEN b.booking_status = 'confirmed' OR b.booking_status = 'completed' THEN 1 ELSE 0 END) as filled_seats,
                f.total_seats,
                ROUND((SUM(CASE WHEN b.booking_status = 'confirmed' OR b.booking_status = 'completed' THEN 1 ELSE 0 END) / f.total_seats) * 100, 2) as occupancy_rate
                FROM flights f
                LEFT JOIN bookings b ON f.flight_id = b.flight_id
                WHERE f.departure_time BETWEEN ? AND ?";
        
        $params = [$date_from . ' 00:00:00', $date_to . ' 23:59:59'];
        $types = "ss";
        
        if (!empty($airline)) {
            $query .= " AND f.airline = ?";
            $params[] = $airline;
            $types .= "s";
        }
        
        if (!empty($status)) {
            $query .= " AND f.status = ?";
            $params[] = $status;
            $types .= "s";
        }
        
        $query .= " GROUP BY f.flight_id ORDER BY f.departure_time DESC";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $flight_data[] = $row;
        }
        break;
        
    case 'revenue':
        // Revenue statistics
        $query = "SELECT DATE(b.booking_date) as date, 
                SUM(b.total_amount) as total_revenue,
                COUNT(b.booking_id) as bookings,
                ROUND(SUM(b.total_amount) / COUNT(b.booking_id), 2) as average_booking_value
                FROM bookings b
                WHERE b.booking_date BETWEEN ? AND ? AND b.booking_status != 'cancelled'";
        
        $params = [$date_from . ' 00:00:00', $date_to . ' 23:59:59'];
        $types = "ss";
        
        if (!empty($airline)) {
            $query .= " AND b.flight_id IN (SELECT flight_id FROM flights WHERE airline = ?)";
            $params[] = $airline;
            $types .= "s";
        }
        
        $query .= " GROUP BY DATE(b.booking_date) ORDER BY date DESC";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $revenue_data[] = $row;
        }
        
        // Calculate total revenue
        $total_rev = 0;
        $total_bookings_rev = 0;
        foreach ($revenue_data as $day) {
            $total_rev += $day['total_revenue'];
            $total_bookings_rev += $day['bookings'];
        }
        $avg_booking_value = $total_bookings_rev > 0 ? round($total_rev / $total_bookings_rev, 2) : 0;
        break;
        
    case 'user':
        // User registration statistics
        $query = "SELECT DATE(created_at) as date, 
                COUNT(*) as registrations,
                COUNT(CASE WHEN role = 'admin' THEN 1 END) as admin_users,
                COUNT(CASE WHEN role = 'user' THEN 1 END) as regular_users
                FROM users
                WHERE created_at BETWEEN ? AND ?";
        
        $params = [$date_from . ' 00:00:00', $date_to . ' 23:59:59'];
        $types = "ss";
        
        $query .= " GROUP BY DATE(created_at) ORDER BY date DESC";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $user_data[] = $row;
        }
        
        // Calculate totals
        $total_registrations = 0;
        $total_admin_users = 0;
        $total_regular_users = 0;
        
        foreach ($user_data as $day) {
            $total_registrations += $day['registrations'];
            $total_admin_users += $day['admin_users'];
            $total_regular_users += $day['regular_users'];
        }
        break;
}

// Handle export functionality
if (isset($_GET['export']) && $_GET['export'] === 'true') {
    $filename = 'report_' . $report_type . '_' . date('Y-m-d') . '.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // Write headers based on report type
    switch ($report_type) {
        case 'booking':
            fputcsv($output, ['Date', 'Total Bookings', 'Confirmed', 'Pending', 'Cancelled', 'Revenue']);
            foreach ($booking_data as $row) {
                fputcsv($output, [
                    $row['date'], 
                    $row['count'], 
                    $row['confirmed'], 
                    $row['pending'], 
                    $row['cancelled'], 
                    $row['revenue']
                ]);
            }
            break;
            
        case 'flight':
            fputcsv($output, ['Airline', 'Flight Number', 'Route', 'Bookings', 'Filled Seats', 'Total Seats', 'Occupancy Rate']);
            foreach ($flight_data as $row) {
                fputcsv($output, [
                    $row['airline'],
                    $row['flight_number'],
                    $row['departure_city'] . ' → ' . $row['arrival_city'],
                    $row['bookings'],
                    $row['filled_seats'],
                    $row['total_seats'],
                    $row['occupancy_rate'] . '%'
                ]);
            }
            break;
            
        case 'revenue':
            fputcsv($output, ['Date', 'Revenue', 'Bookings', 'Average Booking Value']);
            foreach ($revenue_data as $row) {
                fputcsv($output, [
                    $row['date'],
                    $row['total_revenue'],
                    $row['bookings'],
                    $row['average_booking_value']
                ]);
            }
            break;
            
        case 'user':
            fputcsv($output, ['Date', 'Total Registrations', 'Regular Users', 'Admin Users']);
            foreach ($user_data as $row) {
                fputcsv($output, [
                    $row['date'],
                    $row['registrations'],
                    $row['regular_users'],
                    $row['admin_users']
                ]);
            }
            break;
    }
    
    fclose($output);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Admin Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-panel">
    <!-- Navigation -->
    <?php include '../includes/navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'sidebar.php'; ?>
            
            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Reports</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                                <i class="fas fa-print me-1"></i> Print
                            </button>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'true'])); ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-download me-1"></i> Export CSV
                            </a>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="refreshReport()">
                            <i class="fas fa-sync-alt me-1"></i> Refresh
                        </button>
                    </div>
                </div>

                <!-- Report Controls -->
                <div class="card mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">Report Options</h5>
                    </div>
                    <div class="card-body">
                        <form method="get" action="reports.php" class="row g-3">
                            <div class="col-md-3 col-6">
                                <label for="report_type" class="form-label">Report Type</label>
                                <select class="form-select" id="report_type" name="report_type">
                                    <option value="booking" <?php echo $report_type === 'booking' ? 'selected' : ''; ?>>Booking Statistics</option>
                                    <option value="flight" <?php echo $report_type === 'flight' ? 'selected' : ''; ?>>Flight Statistics</option>
                                    <option value="revenue" <?php echo $report_type === 'revenue' ? 'selected' : ''; ?>>Revenue Analysis</option>
                                    <option value="user" <?php echo $report_type === 'user' ? 'selected' : ''; ?>>User Registrations</option>
                                </select>
                            </div>
                            <div class="col-md-2 col-6">
                                <label for="date_from" class="form-label">Date From</label>
                                <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $date_from; ?>">
                            </div>
                            <div class="col-md-2 col-6">
                                <label for="date_to" class="form-label">Date To</label>
                                <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $date_to; ?>">
                            </div>
                            <div class="col-md-3 col-6">
                                <label for="airline" class="form-label">Airline</label>
                                <select class="form-select" id="airline" name="airline">
                                    <option value="">All Airlines</option>
                                    <?php foreach ($airlines as $airline_option): ?>
                                        <option value="<?php echo htmlspecialchars($airline_option); ?>" <?php echo $airline === $airline_option ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($airline_option); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">All Statuses</option>
                                    <option value="confirmed" <?php echo $status === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary">Generate Report</button>
                                
                                <div class="btn-group ms-2">
                                    <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                        Quick Date Range
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="#" onclick="setDateRange('today'); return false;">Today</a></li>
                                        <li><a class="dropdown-item" href="#" onclick="setDateRange('yesterday'); return false;">Yesterday</a></li>
                                        <li><a class="dropdown-item" href="#" onclick="setDateRange('this_week'); return false;">This Week</a></li>
                                        <li><a class="dropdown-item" href="#" onclick="setDateRange('last_week'); return false;">Last Week</a></li>
                                        <li><a class="dropdown-item" href="#" onclick="setDateRange('this_month'); return false;">This Month</a></li>
                                        <li><a class="dropdown-item" href="#" onclick="setDateRange('last_month'); return false;">Last Month</a></li>
                                        <li><a class="dropdown-item" href="#" onclick="setDateRange('last_3_months'); return false;">Last 3 Months</a></li>
                                        <li><a class="dropdown-item" href="#" onclick="setDateRange('year_to_date'); return false;">Year to Date</a></li>
                                    </ul>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if ($report_type === 'booking'): ?>
                <!-- Booking Statistics Report -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total Bookings</div>
                                        <div class="h5 mb-0 font-weight-bold"><?php echo $total_bookings; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-calendar fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Total Revenue</div>
                                        <div class="h5 mb-0 font-weight-bold">$<?php echo number_format($total_revenue, 2); ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Confirmed Bookings</div>
                                        <div class="h5 mb-0 font-weight-bold"><?php echo $total_confirmed; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Cancellation Rate</div>
                                        <div class="h5 mb-0 font-weight-bold">
                                            <?php echo $total_bookings > 0 ? round(($total_cancelled / $total_bookings) * 100, 1) : 0; ?>%
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-ban fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Graph -->
                    <div class="col-xl-8 col-lg-7">
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-white py-3">
                                <h6 class="m-0 font-weight-bold">Booking Trends</h6>
                            </div>
                            <div class="card-body">
                                <div class="chart-area">
                                    <canvas id="bookingChart" style="min-height: 300px;"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pie Chart -->
                    <div class="col-xl-4 col-lg-5">
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-white py-3">
                                <h6 class="m-0 font-weight-bold">Booking Status Distribution</h6>
                            </div>
                            <div class="card-body">
                                <div class="chart-pie">
                                    <canvas id="bookingStatusChart" style="min-height: 250px;"></canvas>
                                </div>
                                <div class="mt-4 text-center small">
                                    <span class="me-2"><i class="fas fa-circle text-success"></i> Confirmed (<?php echo $total_confirmed; ?>)</span>
                                    <span class="me-2"><i class="fas fa-circle text-warning"></i> Pending (<?php echo $total_pending; ?>)</span>
                                    <span><i class="fas fa-circle text-danger"></i> Cancelled (<?php echo $total_cancelled; ?>)</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detailed Data Table -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="m-0 font-weight-bold">Daily Booking Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Total Bookings</th>
                                        <th>Confirmed</th>
                                        <th>Pending</th>
                                        <th>Cancelled</th>
                                        <th>Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($booking_data as $row): ?>
                                    <tr>
                                        <td><?php echo date('Y-m-d', strtotime($row['date'])); ?></td>
                                        <td><?php echo $row['count']; ?></td>
                                        <td><?php echo $row['confirmed']; ?></td>
                                        <td><?php echo $row['pending']; ?></td>
                                        <td><?php echo $row['cancelled']; ?></td>
                                        <td>$<?php echo number_format($row['revenue'], 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th>Total</th>
                                        <th><?php echo $total_bookings; ?></th>
                                        <th><?php echo $total_confirmed; ?></th>
                                        <th><?php echo $total_pending; ?></th>
                                        <th><?php echo $total_cancelled; ?></th>
                                        <th>$<?php echo number_format($total_revenue, 2); ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($report_type === 'flight'): ?>
                <!-- Flight Statistics Report -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="m-0 font-weight-bold">Flight Performance</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="flightDataTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Airline</th>
                                        <th>Flight Number</th>
                                        <th>Route</th>
                                        <th>Bookings</th>
                                        <th>Seats</th>
                                        <th>Occupancy Rate</th>
                                        <th>Performance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($flight_data as $row): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['airline']); ?></td>
                                        <td><?php echo htmlspecialchars($row['flight_number']); ?></td>
                                        <td><?php echo htmlspecialchars($row['departure_city'] . ' → ' . $row['arrival_city']); ?></td>
                                        <td><?php echo $row['bookings']; ?></td>
                                        <td><?php echo $row['filled_seats'] . ' / ' . $row['total_seats']; ?></td>
                                        <td><?php echo $row['occupancy_rate']; ?>%</td>
                                        <td>
                                            <div class="progress">
                                                <?php
                                                $rate = floatval($row['occupancy_rate']);
                                                $bg_class = 'bg-danger';
                                                if ($rate >= 80) {
                                                    $bg_class = 'bg-success';
                                                } elseif ($rate >= 60) {
                                                    $bg_class = 'bg-info';
                                                } elseif ($rate >= 40) {
                                                    $bg_class = 'bg-warning';
                                                }
                                                ?>
                                                <div class="progress-bar <?php echo $bg_class; ?>" role="progressbar" style="width: <?php echo $rate; ?>%" aria-valuenow="<?php echo $rate; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <?php endif; ?>

                <?php if ($report_type === 'revenue'): ?>
                <!-- Revenue Analysis Report -->
                <div class="row mb-4">
                    <div class="col-xl-4 col-md-6 mb-4">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Total Revenue</div>
                                        <div class="h5 mb-0 font-weight-bold">$<?php echo number_format($total_rev, 2); ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-4 col-md-6 mb-4">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Total Bookings</div>
                                        <div class="h5 mb-0 font-weight-bold"><?php echo $total_bookings_rev; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-ticket-alt fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-4 col-md-6 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Average Booking Value</div>
                                        <div class="h5 mb-0 font-weight-bold">$<?php echo number_format($avg_booking_value, 2); ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="m-0 font-weight-bold">Revenue Graph</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-area">
                            <canvas id="revenueChart" style="min-height: 300px;"></canvas>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="m-0 font-weight-bold">Daily Revenue Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="revenueTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Revenue</th>
                                        <th>Bookings</th>
                                        <th>Avg. Booking Value</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($revenue_data as $row): ?>
                                    <tr>
                                        <td><?php echo date('Y-m-d', strtotime($row['date'])); ?></td>
                                        <td>$<?php echo number_format($row['total_revenue'], 2); ?></td>
                                        <td><?php echo $row['bookings']; ?></td>
                                        <td>$<?php echo number_format($row['average_booking_value'], 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th>Total</th>
                                        <th>$<?php echo number_format($total_rev, 2); ?></th>
                                        <th><?php echo $total_bookings_rev; ?></th>
                                        <th>$<?php echo number_format($avg_booking_value, 2); ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($report_type === 'user'): ?>
                <!-- User Registration Report -->
                <div class="row mb-4">
                    <div class="col-xl-4 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total Registrations</div>
                                        <div class="h5 mb-0 font-weight-bold"><?php echo $total_registrations; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-users fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-4 col-md-6 mb-4">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Regular Users</div>
                                        <div class="h5 mb-0 font-weight-bold"><?php echo $total_regular_users; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-user fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-4 col-md-6 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Admin Users</div>
                                        <div class="h5 mb-0 font-weight-bold"><?php echo $total_admin_users; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-user-shield fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="m-0 font-weight-bold">User Registration Trends</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-area">
                            <canvas id="userRegistrationChart" style="min-height: 300px;"></canvas>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="m-0 font-weight-bold">Daily Registration Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="userTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Total Registrations</th>
                                        <th>Regular Users</th>
                                        <th>Admin Users</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($user_data as $row): ?>
                                    <tr>
                                        <td><?php echo date('Y-m-d', strtotime($row['date'])); ?></td>
                                        <td><?php echo $row['registrations']; ?></td>
                                        <td><?php echo $row['regular_users']; ?></td>
                                        <td><?php echo $row['admin_users']; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th>Total</th>
                                        <th><?php echo $total_registrations; ?></th>
                                        <th><?php echo $total_regular_users; ?></th>
                                        <th><?php echo $total_admin_users; ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

            </main>
        </div>
    </div>

    <!-- jQuery and DataTables JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#dataTable').DataTable();
            $('#flightDataTable').DataTable();
            $('#revenueTable').DataTable();
            $('#userTable').DataTable();
            
            <?php if ($report_type === 'booking' && !empty($booking_data)): ?>
            // Booking Chart
            const bookingCtx = document.getElementById('bookingChart').getContext('2d');
            const bookingChart = new Chart(bookingCtx, {
                type: 'line',
                data: {
                    labels: [<?php echo implode(', ', array_map(function($item) { return "'" . date('M d', strtotime($item['date'])) . "'"; }, array_reverse($booking_data))); ?>],
                    datasets: [
                        {
                            label: 'Bookings',
                            borderColor: '#4e73df',
                            backgroundColor: 'rgba(78, 115, 223, 0.05)',
                            fill: true,
                            data: [<?php echo implode(', ', array_map(function($item) { return $item['count']; }, array_reverse($booking_data))); ?>],
                            lineTension: 0.3
                        },
                        {
                            label: 'Revenue',
                            borderColor: '#1cc88a',
                            backgroundColor: 'rgba(28, 200, 138, 0.05)',
                            fill: true,
                            data: [<?php echo implode(', ', array_map(function($item) { return $item['revenue']; }, array_reverse($booking_data))); ?>],
                            lineTension: 0.3,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Bookings'
                            }
                        },
                        y1: {
                            position: 'right',
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Revenue ($)'
                            },
                            grid: {
                                drawOnChartArea: false
                            }
                        }
                    }
                }
            });
            
            // Booking Status Pie Chart
            const pieCtx = document.getElementById('bookingStatusChart').getContext('2d');
            const bookingStatusChart = new Chart(pieCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Confirmed', 'Pending', 'Cancelled'],
                    datasets: [{
                        data: [<?php echo $total_confirmed . ', ' . $total_pending . ', ' . $total_cancelled; ?>],
                        backgroundColor: ['#1cc88a', '#f6c23e', '#e74a3b'],
                        hoverBackgroundColor: ['#17a673', '#e3af2b', '#d33b2b'],
                        hoverBorderColor: "rgba(234, 236, 244, 1)",
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    cutout: '60%'
                }
            });
            <?php endif; ?>
            
            <?php if ($report_type === 'revenue' && !empty($revenue_data)): ?>
            // Revenue Chart
            const revenueCtx = document.getElementById('revenueChart').getContext('2d');
            const revenueChart = new Chart(revenueCtx, {
                type: 'bar',
                data: {
                    labels: [<?php echo implode(', ', array_map(function($item) { return "'" . date('M d', strtotime($item['date'])) . "'"; }, array_reverse($revenue_data))); ?>],
                    datasets: [
                        {
                            label: 'Revenue',
                            backgroundColor: '#1cc88a',
                            borderColor: '#1cc88a',
                            data: [<?php echo implode(', ', array_map(function($item) { return $item['total_revenue']; }, array_reverse($revenue_data))); ?>],
                        },
                        {
                            label: 'Bookings',
                            backgroundColor: '#4e73df',
                            borderColor: '#4e73df',
                            data: [<?php echo implode(', ', array_map(function($item) { return $item['bookings']; }, array_reverse($revenue_data))); ?>],
                            type: 'line',
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Revenue ($)'
                            }
                        },
                        y1: {
                            position: 'right',
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Bookings'
                            },
                            grid: {
                                drawOnChartArea: false
                            }
                        }
                    }
                }
            });
            <?php endif; ?>
            
            <?php if ($report_type === 'user' && !empty($user_data)): ?>
            // User Registration Chart
            const userCtx = document.getElementById('userRegistrationChart').getContext('2d');
            const userChart = new Chart(userCtx, {
                type: 'bar',
                data: {
                    labels: [<?php echo implode(', ', array_map(function($item) { return "'" . date('M d', strtotime($item['date'])) . "'"; }, array_reverse($user_data))); ?>],
                    datasets: [
                        {
                            label: 'Regular Users',
                            backgroundColor: '#4e73df',
                            data: [<?php echo implode(', ', array_map(function($item) { return $item['regular_users']; }, array_reverse($user_data))); ?>],
                            stack: 'Stack 0'
                        },
                        {
                            label: 'Admin Users',
                            backgroundColor: '#f6c23e',
                            data: [<?php echo implode(', ', array_map(function($item) { return $item['admin_users']; }, array_reverse($user_data))); ?>],
                            stack: 'Stack 0'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            stacked: true,
                            title: {
                                display: true,
                                text: 'Number of Users'
                            }
                        },
                        x: {
                            stacked: true
                        }
                    }
                }
            });
            <?php endif; ?>
        });
        
        // Function to refresh the report
        function refreshReport() {
            window.location.reload();
        }
        
        // Function to set date range
        function setDateRange(range) {
            const today = new Date();
            let fromDate = new Date();
            let toDate = new Date();
            
            switch (range) {
                case 'today':
                    // From and To are both today
                    break;
                    
                case 'yesterday':
                    fromDate.setDate(fromDate.getDate() - 1);
                    toDate.setDate(toDate.getDate() - 1);
                    break;
                    
                case 'this_week':
                    // First day of this week (Sunday or Monday depending on locale)
                    const day = fromDate.getDay();
                    const diff = fromDate.getDate() - day + (day === 0 ? -6 : 1); // Adjust for Sunday
                    fromDate = new Date(fromDate.setDate(diff));
                    break;
                    
                case 'last_week':
                    // First day of last week
                    const lastDay = fromDate.getDay();
                    const lastDiff = fromDate.getDate() - lastDay + (lastDay === 0 ? -6 : 1) - 7;
                    fromDate = new Date(fromDate.setDate(lastDiff));
                    toDate = new Date(fromDate);
                    toDate.setDate(toDate.getDate() + 6);
                    break;
                    
                case 'this_month':
                    fromDate = new Date(fromDate.getFullYear(), fromDate.getMonth(), 1);
                    break;
                    
                case 'last_month':
                    fromDate = new Date(fromDate.getFullYear(), fromDate.getMonth() - 1, 1);
                    toDate = new Date(toDate.getFullYear(), toDate.getMonth(), 0);
                    break;
                    
                case 'last_3_months':
                    fromDate = new Date(fromDate.getFullYear(), fromDate.getMonth() - 3, 1);
                    break;
                    
                case 'year_to_date':
                    fromDate = new Date(fromDate.getFullYear(), 0, 1);
                    break;
            }
            
            // Format dates as YYYY-MM-DD
            const formatDate = (date) => {
                const year = date.getFullYear();
                const month = (date.getMonth() + 1).toString().padStart(2, '0');
                const day = date.getDate().toString().padStart(2, '0');
                return `${year}-${month}-${day}`;
            };
            
            document.getElementById('date_from').value = formatDate(fromDate);
            document.getElementById('date_to').value = formatDate(toDate);
        }
    </script>
</body>
</html>
