<?php
/**
 * Car Management Dashboard
 * Professional car inventory and financial tracking system
 * 
 * @author Your Name
 * @version 3.0
 * @since 2024
 */

// Security and Configuration
define('DB_ACCESS_ALLOWED', true);
require_once 'connect2.php';
require_once 'csrf.php';

// Initialize session and security
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// TEMPORARY: Set to true to disable CSRF for testing (REMOVE in production)
define('DISABLE_CSRF_FOR_TESTING', false);

// Enhanced Security Headers
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' cdn.jsdelivr.net cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' cdn.jsdelivr.net fonts.googleapis.com cdnjs.cloudflare.com; font-src 'self' cdn.jsdelivr.net fonts.gstatic.com cdnjs.cloudflare.com; img-src 'self' data: https:;");
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
header("Permissions-Policy: geolocation=(), microphone=(), camera=()");

// Input Sanitization Function
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Database Connection with enhanced error handling
try {
    $conn = getDatabase();
    if (!$conn) {
        throw new Exception("Database connection failed");
    }
} catch (Exception $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("
    <div class='error-container'>
        <div class='error-icon'>⚠️</div>
        <h1>Service Temporarily Unavailable</h1>
        <p>We're experiencing technical difficulties. Please try again later.</p>
    </div>
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; margin: 0; padding: 0; }
        .error-container { max-width: 500px; margin: 100px auto; padding: 40px; background: white; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); text-align: center; }
        .error-icon { font-size: 48px; margin-bottom: 20px; }
        h1 { color: #e53e3e; margin-bottom: 10px; }
        p { color: #718096; }
    </style>
    ");
}

class CarDashboard {
    private $conn;
    private $allowedSortColumns = ['brand', 'model', 'color', 'status', 'buying_date', 'selling_date', 'profit'];
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    public function getMonthlyProfitData($months = 6) {
        $monthlyData = [];
        $currentMonth = date('Y-m');
        
        try {
            for ($i = $months - 1; $i >= 0; $i--) {
                $month = date('Y-m', strtotime("-$i months", strtotime($currentMonth)));
                $stmt = $this->conn->prepare("
                    SELECT COALESCE(SUM(profit), 0) as profit 
                    FROM car_financials 
                    WHERE DATE_FORMAT(selling_date, '%Y-%m') = ? AND selling_date IS NOT NULL
                ");
                $stmt->execute([$month]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $monthlyData[$month] = (float) ($result['profit'] ?? 0);
            }
            return $monthlyData;
        } catch (PDOException $e) {
            error_log("Monthly profit data error: " . $e->getMessage());
            for ($i = $months - 1; $i >= 0; $i--) {
                $month = date('Y-m', strtotime("-$i months", strtotime($currentMonth)));
                $monthlyData[$month] = 0;
            }
            return $monthlyData;
        }
    }
    
    public function getProfitStats() {
        $defaultStats = ['month' => 0, 'year' => 0, 'total' => 0];
        $monthStart = date('Y-m-01');
        $yearStart = date('Y-01-01');
        $today = date('Y-m-d');
        
        try {
            // Monthly profit
            $stmtMonth = $this->conn->prepare("
                SELECT COALESCE(SUM(profit), 0) as month_profit 
                FROM car_financials 
                WHERE selling_date BETWEEN ? AND ? AND selling_date IS NOT NULL
            ");
            $stmtMonth->execute([$monthStart, $today]);
            $monthProfit = $stmtMonth->fetch(PDO::FETCH_ASSOC)['month_profit'] ?? 0;
            
            // Yearly profit
            $stmtYear = $this->conn->prepare("
                SELECT COALESCE(SUM(profit), 0) as year_profit 
                FROM car_financials 
                WHERE selling_date BETWEEN ? AND ? AND selling_date IS NOT NULL
            ");
            $stmtYear->execute([$yearStart, $today]);
            $yearProfit = $stmtYear->fetch(PDO::FETCH_ASSOC)['year_profit'] ?? 0;
            
            // Total profit
            $stmtTotal = $this->conn->prepare("
                SELECT COALESCE(SUM(profit), 0) as total_profit 
                FROM car_financials 
                WHERE selling_date IS NOT NULL
            ");
            $stmtTotal->execute();
            $totalProfit = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total_profit'] ?? 0;
            
            return [
                'month' => (float) $monthProfit,
                'year' => (float) $yearProfit,
                'total' => (float) $totalProfit
            ];
        } catch (PDOException $e) {
            error_log("Profit stats error: " . $e->getMessage());
            return $defaultStats;
        }
    }
    
    public function getTotalCars() {
        try {
            $stmt = $this->conn->query("SELECT COUNT(*) as total_cars FROM cars");
            return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total_cars'] ?? 0);
        } catch (PDOException $e) {
            error_log("Total cars error: " . $e->getMessage());
            return 0;
        }
    }
    
    public function getTopBrands($limit = 5) {
        try {
            $stmt = $this->conn->prepare("
                SELECT brand, COUNT(*) as sold_count
                FROM cars c
                JOIN car_financials f ON c.chis_nmbr = f.chis_nmbr
                WHERE f.selling_date IS NOT NULL
                GROUP BY brand
                ORDER BY sold_count DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            while (count($results) < $limit) {
                $results[] = ['brand' => 'N/A', 'sold_count' => 0];
            }
            
            return $results;
        } catch (PDOException $e) {
            error_log("Top brands error: " . $e->getMessage());
            return array_map(function($i) {
                return ['brand' => 'Brand ' . ($i+1), 'sold_count' => 0];
            }, range(0, 4));
        }
    }
    
    public function calculateProfitBetweenDates($startDate, $endDate) {
        try {
            // Validate dates
            if (!$this->validateDate($startDate) || !$this->validateDate($endDate)) {
                throw new InvalidArgumentException("Invalid date format");
            }
            
            $stmt = $this->conn->prepare("
                SELECT 
                    COALESCE(SUM(profit), 0) AS total_profit, 
                    COALESCE(SUM(profit_leb), 0) AS total_profit_leb,
                    COUNT(*) AS cars_sold
                FROM car_financials 
                WHERE selling_date BETWEEN ? AND ? AND selling_date IS NOT NULL
            ");
            $stmt->execute([$startDate, $endDate]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Profit calculation error: " . $e->getMessage());
            return ['total_profit' => 0, 'total_profit_leb' => 0, 'cars_sold' => 0];
        }
    }
    
    private function validateDate($date, $format = 'Y-m-d') {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
    
    public function getSummaryByStatus($status = null) {
        try {
            $query = "
                SELECT 
                    c.status,
                    COUNT(*) as car_count,
                    COALESCE(SUM(f.price_selling_dol), 0) as total_selling_price_usd,
                    COALESCE(SUM(f.price_selling_leb), 0) as total_selling_price_lbp,
                    COALESCE(SUM(f.price_buying_doll), 0) as total_purchase_price_usd,
                    COALESCE(SUM(f.price_leb_buying), 0) as total_purchase_price_lbp,
                    COALESCE(SUM(f.in_cost), 0) as total_in_cost_usd,
                    COALESCE(SUM(f.in_costLeb), 0) as total_in_cost_lbp,
                    COALESCE(SUM(f.ex_cost), 0) as total_ex_cost_usd,
                    COALESCE(SUM(f.ex_costLeb), 0) as total_ex_cost_lbp,
                    COALESCE(SUM(f.total_cost), 0) as total_total_cost_usd,
                    COALESCE(SUM(f.total_cost_leb), 0) as total_total_cost_lbp,
                    COALESCE(SUM(f.jamarik), 0) as total_customs_usd,
                    COALESCE(SUM(f.jamarik_leb), 0) as total_customs_lbp,
                    COALESCE(SUM(f.profit), 0) as total_profit_usd,
                    COALESCE(SUM(f.profit_leb), 0) as total_profit_lbp
                FROM cars c
                LEFT JOIN car_financials f ON c.chis_nmbr = f.chis_nmbr
            ";
            
            if ($status && in_array($status, ['Sold Out', 'Available'])) {
                $query .= " WHERE c.status = ?";
                $stmt = $this->conn->prepare($query . " GROUP BY c.status ORDER BY c.status");
                $stmt->execute([$status]);
            } else {
                $stmt = $this->conn->prepare($query . " GROUP BY c.status ORDER BY c.status");
                $stmt->execute();
            }
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Summary by status error: " . $e->getMessage());
            return [];
        }
    }
    
    public function getCustomStatuses() {
        try {
            $stmt = $this->conn->query("SELECT DISTINCT status FROM cars WHERE status IS NOT NULL AND status != '' ORDER BY status");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            error_log("Custom statuses error: " . $e->getMessage());
            return [];
        }
    }
    
    public function searchCars($filters = []) {
        $conditions = [];
        $params = [];
        
        // Sanitize all inputs
        $filters = array_map('sanitize_input', $filters);
        
        $fieldMapping = [
            'brand' => 'c.brand',
            'color' => 'c.color',
            'model' => 'c.model',
            'buying_date' => 'f.buying_date',
            'selling_date' => 'f.selling_date',
            'source' => 'c.source',
            'status' => 'c.status'
        ];
        
        foreach ($fieldMapping as $field => $column) {
            if (!empty($filters[$field])) {
                $conditions[] = "$column = ?";
                $params[] = $filters[$field];
            }
        }
        
        // Special handling for chassis number
        if (!empty($filters['chis_nmbr'])) {
            $conditions[] = "(c.chis_nmbr = ? OR RIGHT(c.chis_nmbr, 6) = ?)";
            $params[] = $filters['chis_nmbr'];
            $params[] = $filters['chis_nmbr'];
        }
        
        // Numeric filters with validation
        if (!empty($filters['min_profit']) && is_numeric($filters['min_profit'])) {
            $conditions[] = "f.profit >= ?";
            $params[] = (float) $filters['min_profit'];
        }
        
        if (!empty($filters['max_price_doll']) && is_numeric($filters['max_price_doll'])) {
            $conditions[] = "f.price_selling_dol <= ?";
            $params[] = (float) $filters['max_price_doll'];
        }
        
        if (!empty($filters['max_mileage']) && is_numeric($filters['max_mileage'])) {
            $conditions[] = "c.mileage <= ?";
            $params[] = (int) $filters['max_mileage'];
        }
        
        $query = "
            SELECT 
                c.chis_nmbr, c.brand, c.model, c.color, c.mileage, c.status, c.source, c.maintenance,
                f.price_selling_leb, f.price_selling_dol, f.price_buying_doll, f.price_leb_buying, 
                f.buying_date, f.selling_date, f.in_cost, f.ex_cost, f.in_costLeb, f.ex_costLeb, 
                f.total_cost, f.total_cost_leb, f.jamarik, f.jamarik_leb, f.profit, f.profit_leb
            FROM cars c
            LEFT JOIN car_financials f ON c.chis_nmbr = f.chis_nmbr
        ";
        
        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }
        
        $query .= " ORDER BY c.brand, c.model";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Search cars error: " . $e->getMessage());
            return [];
        }
    }
}

// Initialize Dashboard
$dashboard = new CarDashboard($conn);

// Handle form submissions with enhanced security
$searchResults = null;
$profitMessage = '';
$summaryData = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        // Debug CSRF token (remove in production)
        error_log("CSRF Debug - Session token: " . ($_SESSION['csrf_token'] ?? 'NOT SET'));
        error_log("CSRF Debug - POST token: " . ($_POST['csrf_token'] ?? 'NOT SET'));
        
        // Verify CSRF token (unless disabled for testing)
        if (!DISABLE_CSRF_FOR_TESTING && !verify_csrf_token()) {
            // More detailed CSRF error for debugging
            $csrfError = "CSRF validation failed. ";
            if (!isset($_SESSION['csrf_token'])) {
                $csrfError .= "No session token found. ";
            }
            if (!isset($_POST['csrf_token'])) {
                $csrfError .= "No POST token found. ";
            }
            if (isset($_SESSION['csrf_token']) && isset($_POST['csrf_token'])) {
                $csrfError .= "Tokens don't match. ";
            }
            throw new Exception($csrfError . "Please refresh the page and try again.");
        }
        
        // Rate limiting (basic implementation)
        if (!isset($_SESSION['last_request']) || (time() - $_SESSION['last_request']) > 1) {
            $_SESSION['last_request'] = time();
        } else {
            throw new Exception("Too many requests. Please wait a moment.");
        }
        
        if (isset($_POST['profit_summary'])) {
            $start = sanitize_input($_POST['start_date'] ?? '');
            $end = sanitize_input($_POST['end_date'] ?? '');
            
            if (!$start || !$end) {
                $profitMessage = "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle'></i> Please select both start and end dates.</div>";
            } else {
                $result = $dashboard->calculateProfitBetweenDates($start, $end);
                $usd = number_format($result['total_profit'], 2);
                $lbp = number_format($result['total_profit_leb'], 0);
                $carsSold = $result['cars_sold'];
                
                $profitMessage = "<div class='alert alert-success'>
                    <i class='fas fa-check-circle'></i> 
                    <strong>Profit Summary:</strong> From <strong>" . htmlspecialchars($start) . "</strong> to <strong>" . htmlspecialchars($end) . "</strong><br>
                    <strong>Total Profit:</strong> $" . $usd . " / " . $lbp . " LBP<br>
                    <strong>Cars Sold:</strong> " . $carsSold . " vehicles
                </div>";
            }
        }
        
        if (isset($_POST['show_all']) || isset($_POST['search'])) {
            $searchResults = $dashboard->searchCars($_POST);
            $summaryData = $dashboard->getSummaryByStatus();
        }
        
    } catch (Exception $e) {
        error_log("Form processing error: " . $e->getMessage());
        $profitMessage = "<div class='alert alert-danger'><i class='fas fa-exclamation-circle'></i> " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

// Get dashboard data with fallbacks
$monthlyData = $dashboard->getMonthlyProfitData();
$profitStats = $dashboard->getProfitStats();
$totalCars = $dashboard->getTotalCars();
$topBrands = $dashboard->getTopBrands();
$customStatuses = $dashboard->getCustomStatuses();

// Predefined options
$predefinedBrands = ['Mercedes', 'Audi', 'Renault', 'Toyota', 'BMW', 'Ford', 'Hyundai', 'Kia', 'Mazda', 'Volkswagen'];
$predefinedStatuses = ['Sold Out', 'Available', 'Reserved', 'In Transit', 'Maintenance'];
$predefinedSources = ['Qatar', 'Emirates', 'Africa', 'Europe', 'Local'];

// Merge custom statuses with predefined ones
$allStatuses = array_unique(array_merge($predefinedStatuses, $customStatuses));

// Ensure we have data for charts
if (empty($monthlyData)) {
    $currentMonth = date('Y-m');
    for ($i = 5; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months", strtotime($currentMonth)));
        $monthlyData[$month] = 0;
    }
}

if (empty($topBrands)) {
    $topBrands = [
        ['brand' => 'Toyota', 'sold_count' => 0],
        ['brand' => 'Honda', 'sold_count' => 0],
        ['brand' => 'Nissan', 'sold_count' => 0],
        ['brand' => 'BMW', 'sold_count' => 0],
        ['brand' => 'Mercedes', 'sold_count' => 0]
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Car Management Dashboard - Modern</title>
    <link rel="icon" href="hala.ico">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- CSS Libraries -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        :root {
            /* Color Palette */
            --primary: #6366f1;
            --primary-light: #8b5cf6;
            --primary-dark: #4338ca;
            --secondary: #10b981;
            --success: #059669;
            --danger: #ef4444;
            --warning: #f59e0b;
            --info: #3b82f6;
            --light: #f8fafc;
            --dark: #1e293b;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            
            /* Typography */
            --font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            --font-size-xs: 0.75rem;
            --font-size-sm: 0.875rem;
            --font-size-base: 1rem;
            --font-size-lg: 1.125rem;
            --font-size-xl: 1.25rem;
            --font-size-2xl: 1.5rem;
            --font-size-3xl: 1.875rem;
            --font-size-4xl: 2.25rem;
            
            /* Spacing */
            --spacing-xs: 0.25rem;
            --spacing-sm: 0.5rem;
            --spacing-md: 1rem;
            --spacing-lg: 1.5rem;
            --spacing-xl: 2rem;
            --spacing-2xl: 3rem;
            
            /* Borders */
            --border-radius-sm: 0.375rem;
            --border-radius-md: 0.5rem;
            --border-radius-lg: 0.75rem;
            --border-radius-xl: 1rem;
            --border-radius-2xl: 1.5rem;
            
            /* Shadows */
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --shadow-2xl: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            
            /* Transitions */
            --transition-fast: 150ms cubic-bezier(0.4, 0, 0.2, 1);
            --transition-normal: 250ms cubic-bezier(0.4, 0, 0.2, 1);
            --transition-slow: 350ms cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        * {
            box-sizing: border-box;
        }
        
        body {
            font-family: var(--font-family);
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: var(--gray-800);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }
        
        .main-container {
            background: var(--gray-50);
            min-height: 100vh;
            padding: var(--spacing-lg);
        }
        
        .container-fluid {
            max-width: 1920px;
            margin: 0 auto;
        }
        
        /* Enhanced Header */
        .dashboard-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            border-radius: var(--border-radius-2xl);
            box-shadow: var(--shadow-xl);
            padding: var(--spacing-2xl);
            margin-bottom: var(--spacing-xl);
            position: relative;
            overflow: hidden;
        }
        
        .dashboard-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: float 6s ease-in-out infinite;
        }
        
        .dashboard-header h1 {
            font-weight: 800;
            font-size: clamp(2rem, 5vw, 3rem);
            margin-bottom: var(--spacing-sm);
            position: relative;
            z-index: 1;
        }
        
        .dashboard-header .subtitle {
            font-size: var(--font-size-lg);
            opacity: 0.95;
            font-weight: 400;
            position: relative;
            z-index: 1;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }
        
        /* Modern Cards */
        .card {
            background: white;
            border: none;
            border-radius: var(--border-radius-xl);
            box-shadow: var(--shadow-sm);
            transition: all var(--transition-normal);
            overflow: hidden;
            margin-bottom: var(--spacing-xl);
        }
        
        .card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-2px);
        }
        
        .card-body {
            padding: var(--spacing-xl);
        }
        
        .card-title {
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: var(--spacing-lg);
            font-size: var(--font-size-xl);
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
        }
        
        /* Stat Cards */
        .stat-card {
            background: linear-gradient(135deg, white, var(--gray-50));
            border: 1px solid var(--gray-200);
            border-radius: var(--border-radius-xl);
            padding: var(--spacing-xl);
            transition: all var(--transition-normal);
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--primary-light));
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-xl);
            border-color: var(--primary);
        }
        
        .stat-value {
            font-size: clamp(1.5rem, 4vw, 2.5rem);
            font-weight: 800;
            color: var(--primary);
            line-height: 1.2;
            margin-bottom: var(--spacing-xs);
        }
        
        .stat-label {
            font-size: var(--font-size-sm);
            color: var(--gray-600);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stat-icon {
            position: absolute;
            top: var(--spacing-lg);
            right: var(--spacing-lg);
            width: 3rem;
            height: 3rem;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border-radius: var(--border-radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.25rem;
            opacity: 0.8;
        }
        
        /* Navigation Cards */
        .nav-card {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border: none;
            border-radius: var(--border-radius-xl);
            color: white;
            text-decoration: none;
            transition: all var(--transition-normal);
            overflow: hidden;
            position: relative;
        }
        
        .nav-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left var(--transition-slow);
        }
        
        .nav-card:hover::before {
            left: 100%;
        }
        
        .nav-card:hover {
            transform: translateY(-6px);
            box-shadow: var(--shadow-2xl);
            color: white;
        }
        
        .nav-card.add { background: linear-gradient(135deg, var(--secondary), #34d399); }
        .nav-card.update { background: linear-gradient(135deg, var(--warning), #fbbf24); }
        .nav-card.delete { background: linear-gradient(135deg, var(--danger), #f87171); }
        .nav-card.clients { background: linear-gradient(135deg, var(--info), #60a5fa); }
        
        .nav-card .card-body {
            padding: var(--spacing-xl);
            text-align: center;
            position: relative;
            z-index: 1;
        }
        
        .nav-card .card-title {
            font-size: var(--font-size-lg);
            font-weight: 600;
            margin-bottom: var(--spacing-sm);
            color: white;
        }
        
        .nav-card .card-text {
            font-size: var(--font-size-sm);
            opacity: 0.9;
            color: white;
        }
        
        /* Form Controls */
        .form-control, .form-select {
            border: 2px solid var(--gray-200);
            border-radius: var(--border-radius-md);
            padding: 0.75rem 1rem;
            font-size: var(--font-size-sm);
            transition: all var(--transition-fast);
            background: white;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(99, 102, 241, 0.1);
            outline: none;
        }
        
        .form-label {
            font-weight: 500;
            color: var(--gray-700);
            margin-bottom: var(--spacing-sm);
            font-size: var(--font-size-sm);
        }
        
        /* Buttons */
        .btn {
            border-radius: var(--border-radius-md);
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            font-size: var(--font-size-sm);
            border: none;
            transition: all var(--transition-fast);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: var(--spacing-sm);
            text-decoration: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
            color: white;
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, var(--gray-600), var(--gray-700));
            color: white;
        }
        
        .btn-secondary:hover {
            background: linear-gradient(135deg, var(--gray-700), var(--gray-800));
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
            color: white;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, var(--danger), #ef4444);
            color: white;
        }
        
        .btn-danger:hover {
            background: linear-gradient(135deg, #dc2626, var(--danger));
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
            color: white;
        }
        
        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: var(--font-size-xs);
        }
        
        /* Table Styles */
        .table-container {
            background: white;
            border-radius: var(--border-radius-xl);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            border: 1px solid var(--gray-200);
        }
        
        .table-responsive {
            max-height: 70vh;
            overflow-y: auto;
        }
        
        .table {
            margin-bottom: 0;
            font-size: var(--font-size-sm);
        }
        
        .table th {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            border: none;
            padding: 1rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: var(--font-size-xs);
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .table td {
            padding: 0.75rem 1rem;
            border-top: 1px solid var(--gray-200);
            vertical-align: middle;
        }
        
        .table tbody tr:hover {
            background-color: var(--gray-50);
        }
        
        .table tbody tr:nth-of-type(even) {
            background-color: rgba(99, 102, 241, 0.02);
        }
        
        /* Chart Containers */
        .chart-container {
            position: relative;
            height: 350px;
            width: 100%;
        }
        
        /* Alert Styles */
        .alert {
            border-radius: var(--border-radius-md);
            padding: 1rem 1.25rem;
            margin-bottom: var(--spacing-lg);
            border: none;
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
        }
        
        .alert-success {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #065f46;
        }
        
        .alert-warning {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            color: #92400e;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #991b1b;
        }
        
        .alert-info {
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            color: #1e40af;
        }
        
        /* Dropdown Enhancement */
        .dropdown-container {
            position: relative;
        }
        
        .dropdown-actions {
            display: flex;
            gap: var(--spacing-sm);
            align-items: center;
            margin-top: var(--spacing-sm);
        }
        
        .dropdown-item-list {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--border-radius-md);
            max-height: 200px;
            overflow-y: auto;
            margin-top: var(--spacing-sm);
        }
        
        .dropdown-item-custom {
            padding: var(--spacing-sm) var(--spacing-md);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--gray-100);
        }
        
        .dropdown-item-custom:last-child {
            border-bottom: none;
        }
        
        .dropdown-item-custom:hover {
            background: var(--gray-50);
        }
        
        .delete-item-btn {
            background: var(--danger);
            color: white;
            border: none;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all var(--transition-fast);
        }
        
        .delete-item-btn:hover {
            background: #dc2626;
            transform: scale(1.1);
        }
        
        /* Loading States */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid var(--gray-200);
            border-radius: 50%;
            border-top-color: var(--primary);
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Error Container */
        .error-container {
            max-width: 500px;
            margin: 2rem auto;
            padding: var(--spacing-2xl);
            background: white;
            border-radius: var(--border-radius-2xl);
            box-shadow: var(--shadow-xl);
            text-align: center;
        }
        
        .error-icon {
            font-size: 3rem;
            margin-bottom: var(--spacing-lg);
            color: var(--danger);
        }
        
        /* Responsive Design */
        @media (max-width: 1199.98px) {
            .main-container {
                padding: var(--spacing-md);
            }
            
            .dashboard-header {
                padding: var(--spacing-xl);
            }
            
            .card-body {
                padding: var(--spacing-lg);
            }
        }
        
        @media (max-width: 991.98px) {
            .chart-container {
                height: 300px;
            }
            
            .table-responsive {
                max-height: 60vh;
            }
            
            .nav-card .card-body {
                padding: var(--spacing-lg);
            }
        }
        
        @media (max-width: 767.98px) {
            .main-container {
                padding: var(--spacing-sm);
            }
            
            .dashboard-header {
                padding: var(--spacing-lg);
                border-radius: var(--border-radius-lg);
            }
            
            .card-body {
                padding: var(--spacing-md);
            }
            
            .stat-card {
                padding: var(--spacing-lg);
            }
            
            .table th, .table td {
                padding: 0.5rem;
                font-size: var(--font-size-xs);
            }
            
            .chart-container {
                height: 250px;
            }
        }
        
        @media (max-width: 575.98px) {
            .dashboard-header h1 {
                font-size: var(--font-size-3xl);
            }
            
            .nav-card .card-title {
                font-size: var(--font-size-base);
            }
            
            .stat-value {
                font-size: var(--font-size-xl);
            }
            
            .btn {
                padding: 0.5rem 1rem;
                font-size: var(--font-size-xs);
            }
        }
        
        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .animate-fade-in {
            animation: fadeInUp 0.6s ease-out forwards;
        }
        
        /* Stagger animations */
        .animate-stagger-1 { animation-delay: 0.1s; }
        .animate-stagger-2 { animation-delay: 0.2s; }
        .animate-stagger-3 { animation-delay: 0.3s; }
        .animate-stagger-4 { animation-delay: 0.4s; }
        
        /* Scrollbar Styling */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: var(--gray-100);
            border-radius: var(--border-radius-md);
        }
        
        ::-webkit-scrollbar-thumb {
            background: var(--gray-300);
            border-radius: var(--border-radius-md);
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: var(--gray-400);
        }
        
        /* Focus States */
        .btn:focus,
        .form-control:focus,
        .form-select:focus {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
        }
        
        /* Print Styles */
        @media print {
            .nav-card,
            .dashboard-header,
            .btn,
            .form-control,
            .form-select {
                display: none !important;
            }
            
            .card {
                box-shadow: none;
                border: 1px solid var(--gray-300);
            }
            
            .table th {
                background: var(--gray-100) !important;
                color: var(--gray-900) !important;
            }
        }
    </style>
</head>

<body>
    <div class="main-container">
        <div class="container-fluid">
            <!-- Header -->
            <div class="dashboard-header animate-fade-in">
                <h1><i class="fas fa-car"></i> Car Management Dashboard</h1>
                <p class="subtitle">Professional inventory and financial tracking system</p>
                <?php if (DISABLE_CSRF_FOR_TESTING): ?>
                    <div class="alert alert-warning mt-3">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>TESTING MODE:</strong> CSRF protection is disabled. Remember to enable it in production!
                    </div>
                <?php endif; ?>
            </div>

            <!-- Navigation Cards -->
            <div class="row g-4 mb-4">
                <div class="col-12 col-sm-6 col-lg-3">
                    <a href="add_car.php" class="nav-card add d-block animate-fade-in animate-stagger-1">
                        <div class="card-body">
                            <h3 class="card-title"><i class="fas fa-plus-circle"></i> Add Car</h3>
                            <p class="card-text">Add new vehicle to inventory</p>
                        </div>
                    </a>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <a href="update2.php" class="nav-card update d-block animate-fade-in animate-stagger-2">
                        <div class="card-body">
                            <h3 class="card-title"><i class="fas fa-edit"></i> Update Car</h3>
                            <p class="card-text">Modify existing vehicle details</p>
                        </div>
                    </a>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <a href="delete_car.php" class="nav-card delete d-block animate-fade-in animate-stagger-3">
                        <div class="card-body">
                            <h3 class="card-title"><i class="fas fa-trash-alt"></i> Delete Car</h3>
                            <p class="card-text">Remove vehicle from inventory</p>
                        </div>
                    </a>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <a href="clients.php" class="nav-card clients d-block animate-fade-in animate-stagger-4">
                        <div class="card-body">
                            <h3 class="card-title"><i class="fas fa-users"></i> Clients Manager</h3>
                            <p class="card-text">Manage customer information</p>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row g-4 mb-4">
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="stat-card animate-fade-in animate-stagger-1">
                        <div class="stat-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="stat-value">$<?= number_format($profitStats['month'], 0) ?></div>
                        <div class="stat-label">Monthly Profit</div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="stat-card animate-fade-in animate-stagger-2">
                        <div class="stat-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-value">$<?= number_format($profitStats['year'], 0) ?></div>
                        <div class="stat-label">Yearly Profit</div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="stat-card animate-fade-in animate-stagger-3">
                        <div class="stat-icon">
                            <i class="fas fa-car"></i>
                        </div>
                        <div class="stat-value"><?= $totalCars ?></div>
                        <div class="stat-label">Total Cars</div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="stat-card animate-fade-in animate-stagger-4">
                        <div class="stat-icon">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="stat-value">$<?= number_format($profitStats['total'], 0) ?></div>
                        <div class="stat-label">Total Profit</div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="row g-4 mb-4">
                <div class="col-12 col-lg-6">
                    <div class="card animate-fade-in">
                        <div class="card-body">
                            <h3 class="card-title">
                                <i class="fas fa-chart-line"></i>
                                Monthly Profit Trend
                            </h3>
                            <div class="chart-container">
                                <canvas id="profitChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="card animate-fade-in">
                        <div class="card-body">
                            <h3 class="card-title">
                                <i class="fas fa-trophy"></i>
                                Top Selling Brands
                            </h3>
                            <div class="chart-container">
                                <canvas id="brandsChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search Form -->
            <div class="card animate-fade-in">
                <div class="card-body">
                    <h3 class="card-title">
                        <i class="fas fa-search"></i>
                        Search & Filter Cars
                    </h3>
                    <form method="POST" id="searchForm">
                        <?php csrf_input_field(); ?>
                        
                        <div class="row g-3">
                            <div class="col-12 col-md-6 col-lg-3">
                                <label for="brand" class="form-label">Brand</label>
                                <select class="form-select" id="brand" name="brand">
                                    <option value="">All Brands</option>
                                    <?php foreach ($predefinedBrands as $brand): ?>
                                        <option value="<?= htmlspecialchars($brand) ?>"><?= htmlspecialchars($brand) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 col-md-6 col-lg-3">
                                <label for="model" class="form-label">Model</label>
                                <input type="text" class="form-control" id="model" name="model" placeholder="Camry, X5, etc.">
                            </div>
                            <div class="col-12 col-md-6 col-lg-3">
                                <label for="color" class="form-label">Color</label>
                                <input type="text" class="form-control" id="color" name="color" placeholder="Red, Black, etc.">
                            </div>
                            <div class="col-12 col-md-6 col-lg-3">
                                <label for="chis_nmbr" class="form-label">Chassis Number</label>
                                <input type="text" class="form-control" id="chis_nmbr" name="chis_nmbr" placeholder="Full or last 6 digits">
                            </div>
                            <div class="col-12 col-md-6 col-lg-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">All Status</option>
                                    <?php foreach ($allStatuses as $status): ?>
                                        <option value="<?= htmlspecialchars($status) ?>"><?= htmlspecialchars($status) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 col-md-6 col-lg-3">
                                <label for="source" class="form-label">Source</label>
                                <select class="form-select" id="source" name="source">
                                    <option value="">All Sources</option>
                                    <?php foreach ($predefinedSources as $source): ?>
                                        <option value="<?= htmlspecialchars($source) ?>"><?= htmlspecialchars($source) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 col-md-6 col-lg-3">
                                <label for="buying_date" class="form-label">Buying Date</label>
                                <input type="date" class="form-control" id="buying_date" name="buying_date">
                            </div>
                            <div class="col-12 col-md-6 col-lg-3">
                                <label for="selling_date" class="form-label">Selling Date</label>
                                <input type="date" class="form-control" id="selling_date" name="selling_date">
                            </div>
                            <div class="col-12 col-md-6 col-lg-3">
                                <label for="min_profit" class="form-label">Min Profit ($)</label>
                                <input type="number" class="form-control" id="min_profit" name="min_profit" placeholder="Minimum Profit">
                            </div>
                            <div class="col-12 col-md-6 col-lg-3">
                                <label for="max_price_doll" class="form-label">Max Price ($)</label>
                                <input type="number" class="form-control" id="max_price_doll" name="max_price_doll" placeholder="Maximum selling price">
                            </div>
                            <div class="col-12 col-md-6 col-lg-3">
                                <label for="max_mileage" class="form-label">Max Mileage</label>
                                <input type="number" class="form-control" id="max_mileage" name="max_mileage" placeholder="Maximum mileage">
                            </div>
                        </div>

                        <!-- Custom Dropdown Management -->
                        <div class="row g-3 mt-4">
                            <div class="col-12">
                                <h5 class="mb-3">
                                    <i class="fas fa-cog"></i>
                                    Manage Dropdown Options
                                </h5>
                            </div>
                            
                            <!-- Status Management -->
                            <div class="col-12 col-md-6 col-lg-4">
                                <div class="dropdown-container">
                                    <label class="form-label">Status Management</label>
                                    <div class="dropdown-actions">
                                        <input type="text" class="form-control form-control-sm custom-status-input" placeholder="New Status">
                                        <button type="button" class="btn btn-primary btn-sm add-status-btn">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm delete-status-btn">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                    <div class="status-feedback"></div>
                                    <div class="dropdown-item-list mt-2" id="statusItemList"></div>
                                </div>
                            </div>
                            
                            <!-- Brand Management -->
                            <div class="col-12 col-md-6 col-lg-4">
                                <div class="dropdown-container">
                                    <label class="form-label">Brand Management</label>
                                    <div class="dropdown-actions">
                                        <input type="text" class="form-control form-control-sm custom-brand-input" placeholder="New Brand">
                                        <button type="button" class="btn btn-primary btn-sm add-brand-btn">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm delete-brand-btn">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                    <div class="brand-feedback"></div>
                                    <div class="dropdown-item-list mt-2" id="brandItemList"></div>
                                </div>
                            </div>
                            
                            <!-- Source Management -->
                            <div class="col-12 col-md-6 col-lg-4">
                                <div class="dropdown-container">
                                    <label class="form-label">Source Management</label>
                                    <div class="dropdown-actions">
                                        <input type="text" class="form-control form-control-sm custom-source-input" placeholder="New Source">
                                        <button type="button" class="btn btn-primary btn-sm add-source-btn">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm delete-source-btn">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                    <div class="source-feedback"></div>
                                    <div class="dropdown-item-list mt-2" id="sourceItemList"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-12 col-md-6 col-lg-3">
                                <button type="submit" name="search" class="btn btn-primary w-100">
                                    <i class="fas fa-search"></i> Search Cars
                                </button>
                            </div>
                            <div class="col-12 col-md-6 col-lg-3">
                                <button type="submit" name="show_all" class="btn btn-secondary w-100">
                                    <i class="fas fa-list"></i> Show All Cars
                                </button>
                            </div>
                            <div class="col-12 col-md-6 col-lg-3">
                                <button type="button" class="btn btn-secondary w-100" onclick="resetForm()">
                                    <i class="fas fa-redo"></i> Reset Form
                                </button>
                            </div>
                        </div>
                    </form>
                    
                    <!-- Profit Summary Form -->
                    <div class="mt-5 pt-4 border-top">
                        <form method="POST" id="profitForm">
                            <?php csrf_input_field(); ?>
                            <h5 class="mb-3">
                                <i class="fas fa-calculator"></i>
                                Profit Summary Between Dates
                            </h5>
                            <div class="row g-3">
                                <div class="col-12 col-md-4">
                                    <label for="start_date" class="form-label">Start Date</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" required>
                                </div>
                                <div class="col-12 col-md-4">
                                    <label for="end_date" class="form-label">End Date</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" required>
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label">&nbsp;</label>
                                    <button type="submit" name="profit_summary" class="btn btn-primary w-100">
                                        <i class="fas fa-calculator"></i> Calculate Profit
                                    </button>
                                </div>
                            </div>
                        </form>
                        
                        <?php if ($profitMessage): ?>
                            <div class="mt-3">
                                <?= $profitMessage ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Summary Table -->
            <?php if (!empty($summaryData)): ?>
                <div class="card animate-fade-in">
                    <div class="card-body">
                        <h3 class="card-title">
                            <i class="fas fa-chart-pie"></i>
                            Financial Summary by Status
                        </h3>
                        <div class="table-container">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th rowspan="2">Status</th>
                                            <th rowspan="2">Count</th>
                                            <th colspan="2">Selling Price</th>
                                            <th colspan="2">Purchase Price</th>
                                            <th colspan="2">In Cost</th>
                                            <th colspan="2">Ex Cost</th>
                                            <th>Custom Duties</th>
                                            <th colspan="2">Total Cost</th>
                                            <th colspan="2">Profit</th>
                                        </tr>
                                        <tr>
                                            <th>USD</th>
                                            <th>LBP</th>
                                            <th>USD</th>
                                            <th>LBP</th>
                                            <th>USD</th>
                                            <th>LBP</th>
                                            <th>USD</th>
                                            <th>LBP</th>
                                            <th>LBP</th>
                                            <th>USD</th>
                                            <th>LBP</th>
                                            <th>USD</th>
                                            <th>LBP</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($summaryData as $row): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($row['status'] ?? 'N/A') ?></strong></td>
                                            <td><?= htmlspecialchars($row['car_count'] ?? 0) ?></td>
                                            <td>$<?= number_format($row['total_selling_price_usd'] ?? 0, 2) ?></td>
                                            <td><?= number_format($row['total_selling_price_lbp'] ?? 0, 0) ?></td>
                                            <td>$<?= number_format($row['total_purchase_price_usd'] ?? 0, 2) ?></td>
                                            <td><?= number_format($row['total_purchase_price_lbp'] ?? 0, 0) ?></td>
                                            <td>$<?= number_format($row['total_in_cost_usd'] ?? 0, 2) ?></td>
                                            <td><?= number_format($row['total_in_cost_lbp'] ?? 0, 0) ?></td>
                                            <td>$<?= number_format($row['total_ex_cost_usd'] ?? 0, 2) ?></td>
                                            <td><?= number_format($row['total_ex_cost_lbp'] ?? 0, 0) ?></td>
                                            <td><?= number_format($row['total_customs_lbp'] ?? 0, 0) ?></td>
                                            <td>$<?= number_format($row['total_total_cost_usd'] ?? 0, 2) ?></td>
                                            <td><?= number_format($row['total_total_cost_lbp'] ?? 0, 0) ?></td>
                                            <td>$<?= number_format($row['total_profit_usd'] ?? 0, 2) ?></td>
                                            <td><?= number_format($row['total_profit_lbp'] ?? 0, 0) ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Search Results -->
            <?php if (!empty($searchResults)): ?>
                <div class="card animate-fade-in">
                    <div class="card-body">
                        <h3 class="card-title">
                            <i class="fas fa-search"></i>
                            Search Results (<?= count($searchResults) ?> cars found)
                        </h3>
                        <div class="table-container">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Chassis</th>
                                            <th>Brand</th>
                                            <th>Model</th>
                                            <th>Color</th>
                                            <th>Status</th>
                                            <th>Buying Date</th>
                                            <th>Selling Date</th>
                                            <th>Sell Price ($)</th>
                                            <th>Sell Price (LBP)</th>
                                            <th>Purchase ($)</th>
                                            <th>Purchase (LBP)</th>
                                            <th>In Cost ($)</th>
                                            <th>In Cost (LBP)</th>
                                            <th>Ex Cost ($)</th>
                                            <th>Ex Cost (LBP)</th>
                                            <th>Customs (LBP)</th>
                                            <th>Total Cost ($)</th>
                                            <th>Total Cost (LBP)</th>
                                            <th>Profit ($)</th>
                                            <th>Profit (LBP)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($searchResults as $car): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars(substr($car['chis_nmbr'], -6)) ?></strong></td>
                                            <td><?= htmlspecialchars($car['brand'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($car['model'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($car['color'] ?? 'N/A') ?></td>
                                            <td><span class="badge bg-primary"><?= htmlspecialchars($car['status'] ?? 'N/A') ?></span></td>
                                            <td><?= htmlspecialchars($car['buying_date'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($car['selling_date'] ?? 'N/A') ?></td>
                                            <td>$<?= number_format($car['price_selling_dol'] ?? 0, 2) ?></td>
                                            <td><?= number_format($car['price_selling_leb'] ?? 0, 0) ?></td>
                                            <td>$<?= number_format($car['price_buying_doll'] ?? 0, 2) ?></td>
                                            <td><?= number_format($car['price_leb_buying'] ?? 0, 0) ?></td>
                                            <td>$<?= number_format($car['in_cost'] ?? 0, 2) ?></td>
                                            <td><?= number_format($car['in_costLeb'] ?? 0, 0) ?></td>
                                            <td>$<?= number_format($car['ex_cost'] ?? 0, 2) ?></td>
                                            <td><?= number_format($car['ex_costLeb'] ?? 0, 0) ?></td>
                                            <td><?= number_format($car['jamarik_leb'] ?? 0, 0) ?></td>
                                            <td>$<?= number_format($car['total_cost'] ?? 0, 2) ?></td>
                                            <td><?= number_format($car['total_cost_leb'] ?? 0, 0) ?></td>
                                            <td>$<?= number_format($car['profit'] ?? 0, 2) ?></td>
                                            <td><?= number_format($car['profit_leb'] ?? 0, 0) ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Enhanced Dashboard JavaScript
        class DashboardManager {
            constructor() {
                this.dropdowns = {
                    status: {
                        input: document.querySelector('.custom-status-input'),
                        addBtn: document.querySelector('.add-status-btn'),
                        deleteBtn: document.querySelector('.delete-status-btn'),
                        feedback: document.querySelector('.status-feedback'),
                        itemList: document.getElementById('statusItemList'),
                        storageKey: 'customStatuses',
                        defaultOptions: <?= json_encode($predefinedStatuses) ?>,
                        selectId: 'status'
                    },
                    brand: {
                        input: document.querySelector('.custom-brand-input'),
                        addBtn: document.querySelector('.add-brand-btn'),
                        deleteBtn: document.querySelector('.delete-brand-btn'),
                        feedback: document.querySelector('.brand-feedback'),
                        itemList: document.getElementById('brandItemList'),
                        storageKey: 'customBrands',
                        defaultOptions: <?= json_encode($predefinedBrands) ?>,
                        selectId: 'brand'
                    },
                    source: {
                        input: document.querySelector('.custom-source-input'),
                        addBtn: document.querySelector('.add-source-btn'),
                        deleteBtn: document.querySelector('.delete-source-btn'),
                        feedback: document.querySelector('.source-feedback'),
                        itemList: document.getElementById('sourceItemList'),
                        storageKey: 'customSources',
                        defaultOptions: <?= json_encode($predefinedSources) ?>,
                        selectId: 'source'
                    }
                };
                
                this.init();
            }
            
            init() {
                this.initCharts();
                this.initDropdowns();
                this.initFormValidation();
                this.setDefaultDates();
                this.initAnimations();
            }
            
            initCharts() {
                // Profit Chart
                const profitCtx = document.getElementById('profitChart').getContext('2d');
                new Chart(profitCtx, {
                    type: 'line',
                    data: {
                        labels: <?= json_encode(array_keys($monthlyData)) ?>,
                        datasets: [{
                            label: 'Monthly Profit ($)',
                            data: <?= json_encode(array_values($monthlyData)) ?>,
                            backgroundColor: 'rgba(99, 102, 241, 0.1)',
                            borderColor: 'rgba(99, 102, 241, 1)',
                            borderWidth: 3,
                            tension: 0.4,
                            fill: true,
                            pointBackgroundColor: 'rgba(99, 102, 241, 1)',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                titleColor: '#fff',
                                bodyColor: '#fff',
                                borderColor: 'rgba(99, 102, 241, 1)',
                                borderWidth: 1,
                                callbacks: {
                                    label: function(context) {
                                        return 'Profit: $' + context.raw.toLocaleString();
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.1)'
                                },
                                ticks: {
                                    callback: function(value) {
                                        return '$' + value.toLocaleString();
                                    }
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                }
                            }
                        }
                    }
                });

                // Brands Chart
                const brandsCtx = document.getElementById('brandsChart').getContext('2d');
                new Chart(brandsCtx, {
                    type: 'doughnut',
                    data: {
                        labels: <?= json_encode(array_column($topBrands, 'brand')) ?>,
                        datasets: [{
                            data: <?= json_encode(array_column($topBrands, 'sold_count')) ?>,
                            backgroundColor: [
                                'rgba(99, 102, 241, 0.8)',
                                'rgba(16, 185, 129, 0.8)',
                                'rgba(245, 158, 11, 0.8)',
                                'rgba(239, 68, 68, 0.8)',
                                'rgba(139, 92, 246, 0.8)'
                            ],
                            borderColor: [
                                'rgba(99, 102, 241, 1)',
                                'rgba(16, 185, 129, 1)',
                                'rgba(245, 158, 11, 1)',
                                'rgba(239, 68, 68, 1)',
                                'rgba(139, 92, 246, 1)'
                            ],
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 20,
                                    usePointStyle: true
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                titleColor: '#fff',
                                bodyColor: '#fff',
                                callbacks: {
                                    label: function(context) {
                                        return context.label + ': ' + context.raw + ' cars';
                                    }
                                }
                            }
                        }
                    }
                });
            }
            
            initDropdowns() {
                Object.keys(this.dropdowns).forEach(key => {
                    const dropdown = this.dropdowns[key];
                    this.loadItems(dropdown);
                    this.bindEvents(dropdown);
                });
            }
            
            loadItems(dropdown) {
                const savedItems = JSON.parse(localStorage.getItem(dropdown.storageKey)) || [];
                const selectElement = document.getElementById(dropdown.selectId);
                
                if (!selectElement) return;
                
                // Clear existing custom options
                this.clearCustomOptions(selectElement);
                
                // Add saved items
                savedItems.forEach(item => {
                    if (!this.optionExists(selectElement, item)) {
                        const option = new Option(item, item);
                        selectElement.add(option);
                    }
                });
                
                // Update item list display
                this.updateItemList(dropdown, savedItems);
            }
            
            clearCustomOptions(selectElement) {
                const options = Array.from(selectElement.options);
                options.forEach(option => {
                    if (option.value !== '' && !this.isDefaultOption(option.value, selectElement.id)) {
                        option.remove();
                    }
                });
            }
            
            isDefaultOption(value, selectId) {
                const defaultOptions = {
                    'status': <?= json_encode($predefinedStatuses) ?>,
                    'brand': <?= json_encode($predefinedBrands) ?>,
                    'source': <?= json_encode($predefinedSources) ?>
                };
                
                return defaultOptions[selectId] && defaultOptions[selectId].includes(value);
            }
            
            optionExists(selectElement, value) {
                return Array.from(selectElement.options).some(opt => opt.value === value);
            }
            
            updateItemList(dropdown, items) {
                if (!dropdown.itemList) return;
                
                dropdown.itemList.innerHTML = '';
                
                items.forEach(item => {
                    const itemDiv = document.createElement('div');
                    itemDiv.className = 'dropdown-item-custom';
                    itemDiv.innerHTML = `
                        <span>${this.escapeHtml(item)}</span>
                        <button type="button" class="delete-item-btn" onclick="dashboardManager.deleteItem('${dropdown.storageKey}', '${this.escapeHtml(item)}')">
                            <i class="fas fa-times"></i>
                        </button>
                    `;
                    dropdown.itemList.appendChild(itemDiv);
                });
            }
            
            bindEvents(dropdown) {
                if (dropdown.addBtn) {
                    dropdown.addBtn.addEventListener('click', () => this.addItem(dropdown));
                }
                
                if (dropdown.deleteBtn) {
                    dropdown.deleteBtn.addEventListener('click', () => this.deleteSelectedItem(dropdown));
                }
                
                if (dropdown.input) {
                    dropdown.input.addEventListener('keypress', (e) => {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            this.addItem(dropdown);
                        }
                    });
                }
            }
            
            addItem(dropdown) {
                const newItem = dropdown.input.value.trim();
                
                if (!newItem) {
                    this.showFeedback(dropdown.feedback, 'Please enter a value', 'warning');
                    return;
                }
                
                if (newItem.length > 50) {
                    this.showFeedback(dropdown.feedback, 'Value too long (max 50 characters)', 'warning');
                    return;
                }
                
                const selectElement = document.getElementById(dropdown.selectId);
                
                if (this.optionExists(selectElement, newItem)) {
                    this.showFeedback(dropdown.feedback, 'This item already exists!', 'warning');
                    return;
                }
                
                // Add to localStorage
                const currentItems = JSON.parse(localStorage.getItem(dropdown.storageKey)) || [];
                currentItems.push(newItem);
                localStorage.setItem(dropdown.storageKey, JSON.stringify(currentItems));
                
                // Add to dropdown
                const option = new Option(newItem, newItem);
                selectElement.add(option);
                
                // Update item list
                this.updateItemList(dropdown, currentItems);
                
                // Clear input
                dropdown.input.value = '';
                
                // Show success
                this.showFeedback(dropdown.feedback, `"${newItem}" added successfully!`, 'success');
            }
            
            deleteSelectedItem(dropdown) {
                const selectElement = document.getElementById(dropdown.selectId);
                const selectedValue = selectElement.value;
                
                if (!selectedValue) {
                    this.showFeedback(dropdown.feedback, 'Please select an item to delete', 'warning');
                    return;
                }
                
                if (this.isDefaultOption(selectedValue, dropdown.selectId)) {
                    this.showFeedback(dropdown.feedback, 'Cannot delete default options', 'warning');
                    return;
                }
                
                this.deleteItem(dropdown.storageKey, selectedValue);
            }
            
            deleteItem(storageKey, itemValue) {
                const dropdown = Object.values(this.dropdowns).find(d => d.storageKey === storageKey);
                if (!dropdown) return;
                
                // Remove from localStorage
                const currentItems = JSON.parse(localStorage.getItem(storageKey)) || [];
                const updatedItems = currentItems.filter(item => item !== itemValue);
                localStorage.setItem(storageKey, JSON.stringify(updatedItems));
                
                // Remove from dropdown
                const selectElement = document.getElementById(dropdown.selectId);
                const options = Array.from(selectElement.options);
                const optionToRemove = options.find(opt => opt.value === itemValue);
                if (optionToRemove) {
                    selectElement.remove(optionToRemove.index);
                }
                
                // Update item list
                this.updateItemList(dropdown, updatedItems);
                
                // Show success
                this.showFeedback(dropdown.feedback, `"${itemValue}" deleted successfully!`, 'success');
            }
            
            showFeedback(element, message, type) {
                if (!element) return;
                
                const alertClass = `alert-${type}`;
                element.innerHTML = `
                    <div class="alert ${alertClass} alert-dismissible fade show mt-2">
                        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : 'exclamation-circle'}"></i>
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `;
                
                // Auto-dismiss after 3 seconds
                setTimeout(() => {
                    const alert = element.querySelector('.alert');
                    if (alert) {
                        alert.classList.remove('show');
                        setTimeout(() => element.innerHTML = '', 150);
                    }
                }, 3000);
            }
            
            initFormValidation() {
                const forms = document.querySelectorAll('form');
                forms.forEach(form => {
                    form.addEventListener('submit', (e) => {
                        const submitBtn = form.querySelector('button[type="submit"]');
                        if (submitBtn) {
                            submitBtn.disabled = true;
                            submitBtn.innerHTML = '<span class="loading"></span> Processing...';
                            
                            // Re-enable after 3 seconds to prevent permanent disable
                            setTimeout(() => {
                                submitBtn.disabled = false;
                                submitBtn.innerHTML = submitBtn.innerHTML.replace('<span class="loading"></span> Processing...', submitBtn.textContent);
                            }, 3000);
                        }
                    });
                });
            }
            
            setDefaultDates() {
                const today = new Date().toISOString().split('T')[0];
                const firstOfMonth = new Date();
                firstOfMonth.setDate(1);
                const firstOfMonthStr = firstOfMonth.toISOString().split('T')[0];
                
                const startDate = document.getElementById('start_date');
                const endDate = document.getElementById('end_date');
                
                if (startDate && !startDate.value) {
                    startDate.value = firstOfMonthStr;
                }
                
                if (endDate && !endDate.value) {
                    endDate.value = today;
                }
            }
            
            initAnimations() {
                // Trigger animations on scroll
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('animate-fade-in');
                        }
                    });
                });
                
                document.querySelectorAll('.card').forEach(card => {
                    observer.observe(card);
                });
            }
            
            escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
        }
        
        // Global functions
        function resetForm() {
            document.getElementById('searchForm').reset();
            document.querySelectorAll('.form-control, .form-select').forEach(input => {
                input.value = '';
            });
        }
        
        function exportTable(tableId) {
            const table = document.getElementById(tableId);
            if (!table) return;
            
            let csv = '';
            const rows = table.querySelectorAll('tr');
            
            rows.forEach(row => {
                const cells = row.querySelectorAll('th, td');
                const rowData = Array.from(cells).map(cell => 
                    '"' + cell.textContent.replace(/"/g, '""') + '"'
                ).join(',');
                csv += rowData + '\n';
            });
            
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `car_data_${new Date().toISOString().split('T')[0]}.csv`;
            a.click();
            window.URL.revokeObjectURL(url);
        }
        
        // Initialize dashboard
        let dashboardManager;
        document.addEventListener('DOMContentLoaded', () => {
            dashboardManager = new DashboardManager();
        });
        
        // Service Worker for offline capability
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').catch(err => {
                    console.log('ServiceWorker registration failed: ', err);
                });
            });
        }
    </script>
</body>
</html>