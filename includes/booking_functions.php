<?php
/**
 * Booking Management Functions
 * 
 * A collection of functions to handle booking operations throughout the application.
 */

/**
 * Get a single booking by ID with related flight and user information
 * 
 * @param int $booking_id The booking ID
 * @return array|null The booking data or null if not found
 */
function getBooking($booking_id) {
    global $conn;
    
    if (!isset($conn)) {
        return null;
    }
    
    try {
        $stmt = $conn->prepare("SELECT b.*, 
                              f.flight_number, f.airline, f.departure_city, f.arrival_city,
                              f.departure_time, f.arrival_time, f.status as flight_status,
                              u.first_name, u.last_name, u.email, u.phone
                              FROM bookings b 
                              JOIN flights f ON b.flight_id = f.flight_id 
                              JOIN users u ON b.user_id = u.user_id 
                              WHERE b.booking_id = ?");
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return null;
        }
        
        $booking = $result->fetch_assoc();
        
        // Get tickets associated with this booking
        $stmt = $conn->prepare("SELECT * FROM tickets WHERE booking_id = ?");
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $tickets_result = $stmt->get_result();
        
        $tickets = [];
        while ($ticket = $tickets_result->fetch_assoc()) {
            $tickets[] = $ticket;
        }
        
        // Add tickets to booking data
        $booking['tickets'] = $tickets;
        
        // Get payment information if available
        try {
            $stmt = $conn->prepare("SELECT * FROM payments WHERE booking_id = ?");
            $stmt->bind_param("i", $booking_id);
            $stmt->execute();
            $payment_result = $stmt->get_result();
            
            if ($payment_result->num_rows > 0) {
                $booking['payment'] = $payment_result->fetch_assoc();
            }
        } catch (Exception $e) {
            // Ignore if payments table doesn't exist
        }
        
        return $booking;
    } catch (Exception $e) {
        // Log error
        error_log("Error getting booking: " . $e->getMessage());
        return null;
    }
}

/**
 * Get a list of bookings with filtering options
 * 
 * @param array $filters Associative array of filters (status, date_from, date_to, user_id, flight_id, search)
 * @param int $limit Maximum number of bookings to return (0 = no limit)
 * @param int $offset Starting offset for pagination
 * @param string $sort_by Field to sort by
 * @param string $sort_dir Sort direction (ASC or DESC)
 * @return array Array of bookings
 */
function getBookings($filters = [], $limit = 0, $offset = 0, $sort_by = 'booking_date', $sort_dir = 'DESC') {
    global $conn;
    
    if (!isset($conn)) {
        return [];
    }
    
    $query = "SELECT b.*, 
              f.flight_number, f.airline, f.departure_city, f.arrival_city,
              f.departure_time, f.arrival_time, f.status as flight_status,
              u.first_name, u.last_name, u.email
              FROM bookings b 
              JOIN flights f ON b.flight_id = f.flight_id 
              JOIN users u ON b.user_id = u.user_id 
              WHERE 1=1";
              
    $params = [];
    $types = "";
    
    // Apply filters
    if (isset($filters['status']) && !empty($filters['status'])) {
        $query .= " AND b.booking_status = ?";
        $params[] = $filters['status'];
        $types .= "s";
    }
    
    if (isset($filters['date_from']) && !empty($filters['date_from'])) {
        $query .= " AND DATE(b.booking_date) >= ?";
        $params[] = $filters['date_from'];
        $types .= "s";
    }
    
    if (isset($filters['date_to']) && !empty($filters['date_to'])) {
        $query .= " AND DATE(b.booking_date) <= ?";
        $params[] = $filters['date_to'];
        $types .= "s";
    }
    
    if (isset($filters['user_id']) && !empty($filters['user_id'])) {
        $query .= " AND b.user_id = ?";
        $params[] = $filters['user_id'];
        $types .= "i";
    }
    
    if (isset($filters['flight_id']) && !empty($filters['flight_id'])) {
        $query .= " AND b.flight_id = ?";
        $params[] = $filters['flight_id'];
        $types .= "i";
    }
    
    if (isset($filters['airline']) && !empty($filters['airline'])) {
        $query .= " AND f.airline = ?";
        $params[] = $filters['airline'];
        $types .= "s";
    }
    
    if (isset($filters['search']) && !empty($filters['search'])) {
        $query .= " AND (b.booking_id LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? 
                  OR u.email LIKE ? OR f.flight_number LIKE ? OR f.departure_city LIKE ? 
                  OR f.arrival_city LIKE ?)";
        $search_term = "%" . $filters['search'] . "%";
        $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term, 
                              $search_term, $search_term, $search_term]);
        $types .= "sssssss";
    }
    
    // Add sorting
    $allowed_sort_fields = ['booking_id', 'booking_date', 'booking_status', 'payment_status', 
                           'total_amount', 'departure_time', 'arrival_time'];
                           
    if (in_array($sort_by, $allowed_sort_fields)) {
        $sort_field = ($sort_by == 'departure_time' || $sort_by == 'arrival_time') 
            ? "f.$sort_by" : "b.$sort_by";
        $sort_direction = ($sort_dir == 'ASC') ? 'ASC' : 'DESC';
        $query .= " ORDER BY $sort_field $sort_direction";
    } else {
        $query .= " ORDER BY b.booking_date DESC";
    }
    
    // Add limit
    if ($limit > 0) {
        $query .= " LIMIT ?, ?";
        $params[] = $offset;
        $params[] = $limit;
        $types .= "ii";
    }
    
    try {
        $stmt = $conn->prepare($query);
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $bookings = [];
        while ($booking = $result->fetch_assoc()) {
            $bookings[] = $booking;
        }
        
        return $bookings;
    } catch (Exception $e) {
        // Log error
        error_log("Error getting bookings: " . $e->getMessage());
        return [];
    }
}

/**
 * Count bookings based on filters
 * 
 * @param array $filters Associative array of filters
 * @return int Number of bookings matching filters
 */
function countBookings($filters = []) {
    global $conn;
    
    if (!isset($conn)) {
        return 0;
    }
    
    $query = "SELECT COUNT(*) as count 
              FROM bookings b 
              JOIN flights f ON b.flight_id = f.flight_id 
              JOIN users u ON b.user_id = u.user_id 
              WHERE 1=1";
              
    $params = [];
    $types = "";
    
    // Apply the same filters as getBookings
    if (isset($filters['status']) && !empty($filters['status'])) {
        $query .= " AND b.booking_status = ?";
        $params[] = $filters['status'];
        $types .= "s";
    }
    
    // ... (same filter logic as getBookings)
    if (isset($filters['date_from']) && !empty($filters['date_from'])) {
        $query .= " AND DATE(b.booking_date) >= ?";
        $params[] = $filters['date_from'];
        $types .= "s";
    }
    
    if (isset($filters['date_to']) && !empty($filters['date_to'])) {
        $query .= " AND DATE(b.booking_date) <= ?";
        $params[] = $filters['date_to'];
        $types .= "s";
    }
    
    if (isset($filters['user_id']) && !empty($filters['user_id'])) {
        $query .= " AND b.user_id = ?";
        $params[] = $filters['user_id'];
        $types .= "i";
    }
    
    if (isset($filters['flight_id']) && !empty($filters['flight_id'])) {
        $query .= " AND b.flight_id = ?";
        $params[] = $filters['flight_id'];
        $types .= "i";
    }
    
    if (isset($filters['airline']) && !empty($filters['airline'])) {
        $query .= " AND f.airline = ?";
        $params[] = $filters['airline'];
        $types .= "s";
    }
    
    if (isset($filters['search']) && !empty($filters['search'])) {
        $query .= " AND (b.booking_id LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? 
                  OR u.email LIKE ? OR f.flight_number LIKE ? OR f.departure_city LIKE ? 
                  OR f.arrival_city LIKE ?)";
        $search_term = "%" . $filters['search'] . "%";
        $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term, 
                              $search_term, $search_term, $search_term]);
        $types .= "sssssss";
    }
    
    try {
        $stmt = $conn->prepare($query);
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc()['count'];
    } catch (Exception $e) {
        // Log error
        error_log("Error counting bookings: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get booking statistics for the dashboard
 * 
 * @return array Booking statistics
 */
function getBookingStatistics() {
    global $conn;
    
    if (!isset($conn)) {
        return [
            'total' => 0,
            'confirmed' => 0,
            'pending' => 0,
            'cancelled' => 0,
            'completed' => 0,
            'today' => 0,
            'revenue' => 0
        ];
    }
    
    $stats = [];
    
    try {
        // Total bookings
        $result = $conn->query("SELECT COUNT(*) as count FROM bookings");
        $stats['total'] = $result->fetch_assoc()['count'];
        
        // Confirmed bookings
        $result = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE booking_status = 'confirmed'");
        $stats['confirmed'] = $result->fetch_assoc()['count'];
        
        // Pending bookings
        $result = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE booking_status = 'pending'");
        $stats['pending'] = $result->fetch_assoc()['count'];
        
        // Cancelled bookings
        $result = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE booking_status = 'cancelled'");
        $stats['cancelled'] = $result->fetch_assoc()['count'];
        
        // Completed bookings
        $result = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE booking_status = 'completed'");
        $stats['completed'] = $result->fetch_assoc()['count'];
        
        // Today's bookings
        $result = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE DATE(booking_date) = CURDATE()");
        $stats['today'] = $result->fetch_assoc()['count'];
        
        // Total revenue
        $result = $conn->query("SELECT SUM(total_amount) as total FROM bookings WHERE booking_status != 'cancelled'");
        $row = $result->fetch_assoc();
        $stats['revenue'] = $row['total'] ? $row['total'] : 0;
        
        return $stats;
    } catch (Exception $e) {
        // Log error
        error_log("Error getting booking statistics: " . $e->getMessage());
        return [
            'total' => 0,
            'confirmed' => 0,
            'pending' => 0,
            'cancelled' => 0,
            'completed' => 0,
            'today' => 0,
            'revenue' => 0
        ];
    }
}

/**
 * Update booking status
 * 
 * @param int $booking_id Booking ID
 * @param string $status New booking status
 * @param string $payment_status New payment status (optional)
 * @param string $notes Admin notes (optional)
 * @return bool Success/failure
 */
function updateBookingStatus($booking_id, $status, $payment_status = null, $notes = '') {
    try {
        // Get database connection
        global $conn;
        
        // Check if the admin_notes column exists
        $column_check = $conn->query("SHOW COLUMNS FROM bookings LIKE 'admin_notes'");
        $admin_notes_exists = ($column_check->num_rows > 0);
        
        if ($payment_status) {
            if ($admin_notes_exists) {
                $stmt = $conn->prepare("UPDATE bookings SET booking_status = ?, payment_status = ?, 
                                      admin_notes = CONCAT(IFNULL(admin_notes, ''), '\n', ?) 
                                      WHERE booking_id = ?");
                $stmt->bind_param("sssi", $status, $payment_status, $notes, $booking_id);
            } else {
                // If admin_notes column doesn't exist, skip updating it
                $stmt = $conn->prepare("UPDATE bookings SET booking_status = ?, payment_status = ? 
                                      WHERE booking_id = ?");
                $stmt->bind_param("ssi", $status, $payment_status, $booking_id);
            }
        } else {
            if ($admin_notes_exists) {
                $stmt = $conn->prepare("UPDATE bookings SET booking_status = ?, 
                                      admin_notes = CONCAT(IFNULL(admin_notes, ''), '\n', ?) 
                                      WHERE booking_id = ?");
                $stmt->bind_param("ssi", $status, $notes, $booking_id);
            } else {
                // If admin_notes column doesn't exist, skip updating it
                $stmt = $conn->prepare("UPDATE bookings SET booking_status = ? WHERE booking_id = ?");
                $stmt->bind_param("si", $status, $booking_id);
            }
        }
        
        $stmt->execute();
        return true;
        
    } catch (Exception $e) {
        error_log("Error updating booking status: " . $e->getMessage());
        return false;
    }
}

/**
 * Cancel a booking
 * 
 * @param int $booking_id Booking ID
 * @param string $reason Cancellation reason
 * @param float $refund_amount Refund amount (0 for no refund)
 * @return bool Success/failure
 */
function cancelBooking($booking_id, $reason = '', $refund_amount = 0) {
    global $conn;
    
    if (!isset($conn)) {
        return false;
    }
    
    try {
        $conn->begin_transaction();
        
        // Update booking status
        $stmt = $conn->prepare("UPDATE bookings SET booking_status = 'cancelled', 
                              admin_notes = CONCAT(IFNULL(admin_notes, ''), '\n', ?) 
                              WHERE booking_id = ?");
        $cancel_note = "Cancelled: $reason";
        $stmt->bind_param("si", $cancel_note, $booking_id);
        $stmt->execute();
        
        // Update tickets status
        $stmt = $conn->prepare("UPDATE tickets SET status = 'cancelled' WHERE booking_id = ?");
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        
        // Update payment status if refund is provided
        if ($refund_amount > 0) {
            $stmt = $conn->prepare("UPDATE bookings SET payment_status = 'refunded' WHERE booking_id = ?");
            $stmt->bind_param("i", $booking_id);
            $stmt->execute();
            
            // Try to add refund record if table exists
            try {
                $stmt = $conn->prepare("INSERT INTO refunds (booking_id, amount, reason, processed_by, created_at) 
                                     VALUES (?, ?, ?, ?, NOW())");
                $admin_id = $_SESSION['user_id'] ?? 0;
                $stmt->bind_param("idsi", $booking_id, $refund_amount, $reason, $admin_id);
                $stmt->execute();
            } catch (Exception $e) {
                // Ignore if refunds table doesn't exist
            }
        }
        
        // Update flight available seats
        $stmt = $conn->prepare("SELECT flight_id, passengers FROM bookings WHERE booking_id = ?");
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $booking = $result->fetch_assoc();
            
            $stmt = $conn->prepare("UPDATE flights SET available_seats = available_seats + ? 
                                  WHERE flight_id = ?");
            $stmt->bind_param("ii", $booking['passengers'], $booking['flight_id']);
            $stmt->execute();
        }
        
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error cancelling booking: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete a booking and all related records
 * 
 * @param int $booking_id Booking ID
 * @return bool Success/failure
 */
function deleteBooking($booking_id) {
    global $conn;
    
    if (!isset($conn)) {
        return false;
    }
    
    try {
        $conn->begin_transaction();
        
        // Delete tickets
        $stmt = $conn->prepare("DELETE FROM tickets WHERE booking_id = ?");
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        
        // Delete payments
        $stmt = $conn->prepare("DELETE FROM payments WHERE booking_id = ?");
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        
        // Try to delete refunds if table exists
        try {
            $stmt = $conn->prepare("DELETE FROM refunds WHERE booking_id = ?");
            $stmt->bind_param("i", $booking_id);
            $stmt->execute();
        } catch (Exception $e) {
            // Ignore if table doesn't exist
        }
        
        // Update flight available seats before deleting booking
        $stmt = $conn->prepare("SELECT flight_id, passengers, booking_status FROM bookings WHERE booking_id = ?");
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $booking = $result->fetch_assoc();
            
            // Only update seats if booking was not cancelled
            if ($booking['booking_status'] !== 'cancelled') {
                $stmt = $conn->prepare("UPDATE flights SET available_seats = available_seats + ? 
                                      WHERE flight_id = ?");
                $stmt->bind_param("ii", $booking['passengers'], $booking['flight_id']);
                $stmt->execute();
            }
        }
        
        // Delete booking
        $stmt = $conn->prepare("DELETE FROM bookings WHERE booking_id = ?");
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error deleting booking: " . $e->getMessage());
        return false;
    }
}

/**
 * Generate booking reports by type
 * 
 * @param string $report_type Type of report (daily, monthly, airline, route, status)
 * @param string $date_from Start date
 * @param string $date_to End date
 * @return array Report data
 */
function generateBookingReport($report_type, $date_from, $date_to) {
    global $conn;
    
    if (!isset($conn)) {
        return [];
    }
    
    $query = "";
    $params = [];
    $types = "";
    
    // Start and end date are always needed
    $params[] = $date_from . ' 00:00:00';
    $params[] = $date_to . ' 23:59:59';
    $types .= "ss";
    
    switch ($report_type) {
        case 'daily':
            $query = "SELECT 
                    DATE(b.booking_date) as date, 
                    COUNT(*) as count,
                    SUM(b.total_amount) as revenue,
                    COUNT(CASE WHEN b.booking_status = 'confirmed' THEN 1 END) as confirmed,
                    COUNT(CASE WHEN b.booking_status = 'cancelled' THEN 1 END) as cancelled,
                    COUNT(CASE WHEN b.booking_status = 'pending' THEN 1 END) as pending
                    FROM bookings b
                    WHERE b.booking_date BETWEEN ? AND ?
                    GROUP BY DATE(b.booking_date) ORDER BY date DESC";
            break;
            
        case 'monthly':
            $query = "SELECT 
                    YEAR(b.booking_date) as year,
                    MONTH(b.booking_date) as month,
                    COUNT(*) as count,
                    SUM(b.total_amount) as revenue,
                    COUNT(CASE WHEN b.booking_status = 'confirmed' THEN 1 END) as confirmed,
                    COUNT(CASE WHEN b.booking_status = 'cancelled' THEN 1 END) as cancelled,
                    COUNT(CASE WHEN b.booking_status = 'pending' THEN 1 END) as pending
                    FROM bookings b
                    WHERE b.booking_date BETWEEN ? AND ?
                    GROUP BY YEAR(b.booking_date), MONTH(b.booking_date) 
                    ORDER BY year DESC, month DESC";
            break;
            
        case 'airline':
            $query = "SELECT 
                    f.airline,
                    COUNT(*) as count,
                    SUM(b.total_amount) as revenue,
                    COUNT(CASE WHEN b.booking_status = 'confirmed' THEN 1 END) as confirmed,
                    COUNT(CASE WHEN b.booking_status = 'cancelled' THEN 1 END) as cancelled,
                    COUNT(CASE WHEN b.booking_status = 'pending' THEN 1 END) as pending
                    FROM bookings b
                    JOIN flights f ON b.flight_id = f.flight_id
                    WHERE b.booking_date BETWEEN ? AND ?
                    GROUP BY f.airline ORDER BY count DESC";
            break;
            
        case 'route':
            $query = "SELECT 
                    f.departure_city,
                    f.arrival_city,
                    COUNT(*) as count,
                    SUM(b.total_amount) as revenue,
                    AVG(b.total_amount / b.passengers) as avg_ticket_price
                    FROM bookings b
                    JOIN flights f ON b.flight_id = f.flight_id
                    WHERE b.booking_date BETWEEN ? AND ?
                    GROUP BY f.departure_city, f.arrival_city 
                    ORDER BY count DESC";
            break;
            
        case 'status':
            $query = "SELECT 
                    b.booking_status,
                    COUNT(*) as count,
                    SUM(b.total_amount) as revenue,
                    AVG(b.total_amount) as average_value
                    FROM bookings b
                    WHERE b.booking_date BETWEEN ? AND ?
                    GROUP BY b.booking_status ORDER BY count DESC";
            break;
            
        default:
            return [];
    }
    
    try {
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $report_data = [];
        while ($row = $result->fetch_assoc()) {
            $report_data[] = $row;
        }
        
        return $report_data;
    } catch (Exception $e) {
        error_log("Error generating booking report: " . $e->getMessage());
        return [];
    }
}

/**
 * Get a user's booking history
 * 
 * @param int $user_id User ID
 * @param int $limit Maximum number of bookings to return (0 = no limit)
 * @param int $offset Starting offset for pagination
 * @return array Array of bookings
 */
function getUserBookings($user_id, $limit = 0, $offset = 0) {
    global $conn;
    
    if (!isset($conn)) {
        return [];
    }
    
    $query = "SELECT b.*, f.flight_number, f.airline, f.departure_city, f.arrival_city, 
              f.departure_time, f.arrival_time, f.status as flight_status 
              FROM bookings b 
              JOIN flights f ON b.flight_id = f.flight_id 
              WHERE b.user_id = ? 
              ORDER BY b.booking_date DESC";
    
    $params = [$user_id];
    $types = "i";
    
    // Add limit
    if ($limit > 0) {
        $query .= " LIMIT ?, ?";
        $params[] = $offset;
        $params[] = $limit;
        $types .= "ii";
    }
    
    try {
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $bookings = [];
        while ($booking = $result->fetch_assoc()) {
            $bookings[] = $booking;
        }
        
        return $bookings;
    } catch (Exception $e) {
        error_log("Error getting user bookings: " . $e->getMessage());
        return [];
    }
}

/**
 * Get booking stats for a specific user
 * 
 * @param int $user_id User ID
 * @return array Booking statistics for the user
 */
function getUserBookingStats($user_id) {
    global $conn;
    
    if (!isset($conn)) {
        return [
            'total' => 0,
            'completed' => 0,
            'upcoming' => 0,
            'cancelled' => 0
        ];
    }
    
    try {
        $stmt = $conn->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN b.booking_status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN b.booking_status != 'cancelled' AND b.booking_status != 'completed' 
                         AND f.departure_time > NOW() THEN 1 ELSE 0 END) as upcoming,
                SUM(CASE WHEN b.booking_status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
            FROM bookings b
            LEFT JOIN flights f ON b.flight_id = f.flight_id
            WHERE b.user_id = ?
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return [
            'total' => 0,
            'completed' => 0,
            'upcoming' => 0,
            'cancelled' => 0
        ];
    } catch (Exception $e) {
        error_log("Error getting user booking stats: " . $e->getMessage());
        return [
            'total' => 0,
            'completed' => 0,
            'upcoming' => 0,
            'cancelled' => 0
        ];
    }
}

/**
 * Get tickets for a booking
 * 
 * @param int $booking_id Booking ID
 * @return array Array of tickets
 */
function getBookingTickets($booking_id) {
    global $conn;
    
    if (!isset($conn)) {
        return [];
    }
    
    try {
        $stmt = $conn->prepare("SELECT * FROM tickets WHERE booking_id = ?");
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $tickets = [];
        while ($ticket = $result->fetch_assoc()) {
            $tickets[] = $ticket;
        }
        
        return $tickets;
    } catch (Exception $e) {
        error_log("Error getting booking tickets: " . $e->getMessage());
        return [];
    }
}

/**
 * Format booking status for display
 * 
 * @param string $status Booking status
 * @return string HTML with appropriate badge
 */
function formatBookingStatus($status) {
    switch ($status) {
        case 'confirmed':
            return '<span class="badge bg-success">Confirmed</span>';
        case 'pending':
            return '<span class="badge bg-warning text-dark">Pending</span>';
        case 'cancelled':
            return '<span class="badge bg-danger">Cancelled</span>';
        case 'completed':
            return '<span class="badge bg-info">Completed</span>';
        default:
            return '<span class="badge bg-secondary">'. ucfirst($status) .'</span>';
    }
}

/**
 * Format payment status for display
 * 
 * @param string $status Payment status
 * @return string HTML with appropriate badge
 */
function formatPaymentStatus($status) {
    switch ($status) {
        case 'completed':
            return '<span class="badge bg-success">Completed</span>';
        case 'pending':
            return '<span class="badge bg-warning text-dark">Pending</span>';
        case 'failed':
            return '<span class="badge bg-danger">Failed</span>';
        case 'refunded':
            return '<span class="badge bg-info">Refunded</span>';
        default:
            return '<span class="badge bg-secondary">'. ucfirst($status) .'</span>';
    }
}

/**
 * Check if booking is eligible for cancellation
 * 
 * @param array $booking Booking data
 * @return bool True if eligible, false otherwise
 */
function canCancelBooking($booking) {
    // Can't cancel if already cancelled or completed
    if ($booking['booking_status'] === 'cancelled' || $booking['booking_status'] === 'completed') {
        return false;
    }
    
    // Check time until departure (if at least 24 hours)
    $departure_time = strtotime($booking['departure_time']);
    $current_time = time();
    $hours_difference = ($departure_time - $current_time) / (60 * 60);
    
    // Get cancellation policy from settings if available
    $min_hours = 24; // Default to 24 hours
    
    // Try to get from settings table if it exists
    global $conn;
    if (isset($conn)) {
        try {
            $result = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'min_hours_before_departure'");
            if ($result && $result->num_rows > 0) {
                $min_hours = intval($result->fetch_assoc()['setting_value']);
            }
        } catch (Exception $e) {
            // Silently fail and use default
        }
    }
    
    return $hours_difference >= $min_hours;
}
