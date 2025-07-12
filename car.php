<?php
/**
 * Car Management Dashboard
 * Professional car inventory and financial tracking system
 * 
 * @author Your Name
 * @version 2.0
 * @since 2024
 */

// Security and Configuration
define('DB_ACCESS_ALLOWED', true);
require_once 'connect2.php';
require_once 'csrf.php';

// Initialize session and security
session_start();
ini_set('display_errors', 1);
//error_reporting(E_ALL);

// Security Headers
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' cdn.jsdelivr.net; font-src 'self' cdn.jsdelivr.net");
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("Referrer-Policy: strict-origin-when-cross-origin");

// Database Connection
try {
    $conn = getDatabase();
} catch (Exception $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("<div class='error-container'><h1>Database Connection Failed</h1><p>Please try again later.</p></div>");
}

class CarDashboard {
    private $conn;
    
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
                    WHERE DATE_FORMAT(selling_date, '%Y-%m') = ?
                ");
                $stmt->execute([$month]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $monthlyData[$month] = (float) ($result['profit'] ?? 0);
            }
            return $monthlyData;
        } catch (PDOException $e) {
            error_log("Monthly profit data error: " . $e->getMessage());
            // Return dummy data if query fails
            for ($i = $months - 1; $i >= 0; $i--) {
                $month = date('Y-m', strtotime("-$i months", strtotime($currentMonth)));
                $monthlyData[$month] = 0;
            }
            return $monthlyData;
        }
    }
    
    public function getProfitStats() {
        $defaultStats = ['month' => 0, 'year' => 0];
        $monthStart = date('Y-m-01');
        $yearStart = date('Y-01-01');
        $today = date('Y-m-d');
        
        try {
            $stmtMonth = $this->conn->prepare("
                SELECT COALESCE(SUM(profit), 0) as month_profit 
                FROM car_financials 
                WHERE selling_date BETWEEN ? AND ?
            ");
            $stmtMonth->execute([$monthStart, $today]);
            $monthProfit = $stmtMonth->fetch(PDO::FETCH_ASSOC)['month_profit'] ?? 0;
            
            $stmtYear = $this->conn->prepare("
                SELECT COALESCE(SUM(profit), 0) as year_profit 
                FROM car_financials 
                WHERE selling_date BETWEEN ? AND ?
            ");
            $stmtYear->execute([$yearStart, $today]);
            $yearProfit = $stmtYear->fetch(PDO::FETCH_ASSOC)['year_profit'] ?? 0;
            
            return [
                'month' => (float) $monthProfit,
                'year' => (float) $yearProfit
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
            
            // Ensure we always return the requested number of items
            while (count($results) < $limit) {
                $results[] = ['brand' => 'N/A', 'sold_count' => 0];
            }
            
            return $results;
        } catch (PDOException $e) {
            error_log("Top brands error: " . $e->getMessage());
            // Return dummy data if query fails
            return array_map(function($i) {
                return ['brand' => 'Brand ' . ($i+1), 'sold_count' => 0];
            }, range(0, 4));
        }
    }
    
    public function calculateProfitBetweenDates($startDate, $endDate) {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    COALESCE(SUM(profit), 0) AS total_profit, 
                    COALESCE(SUM(profit_leb), 0) AS total_profit_leb
                FROM car_financials 
                WHERE selling_date BETWEEN ? AND ?
            ");
            $stmt->execute([$startDate, $endDate]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Profit calculation error: " . $e->getMessage());
            return ['total_profit' => 0, 'total_profit_leb' => 0];
        }
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
            
            if ($status) {
                $query .= " WHERE c.status = ?";
                $stmt = $this->conn->prepare($query . " GROUP BY c.status");
                $stmt->execute([$status]);
            } else {
                $stmt = $this->conn->prepare($query . " GROUP BY c.status");
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


    
    public function addCustomStatus($status) {
        // This would typically be handled by your add/update forms
        // For now, we'll just return success since the status will be added when a car is created/updated
        return true;
    }
    
    public function searchCars($filters = []) {
        $conditions = [];
        $params = [];
        
        // Build conditions dynamically
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
        
        // Numeric filters
        if (!empty($filters['min_profit'])) {
            $conditions[] = "f.profit >= ?";
            $params[] = $filters['min_profit'];
        }
        
        if (!empty($filters['max_price_doll'])) {
            $conditions[] = "f.price_selling_dol <= ?";
            $params[] = $filters['max_price_doll'];
        }
        
        if (!empty($filters['max_mileage'])) {
            $conditions[] = "c.mileage <= ?";
            $params[] = $filters['max_mileage'];
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

// Handle form submissions
$searchResults = null;
$profitMessage = '';
$summaryData = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        verify_csrf_token();
        
        // Handle custom status addition
        if (isset($_POST['add_custom_status']) && !empty($_POST['custom_status_input'])) {
            $customStatus = trim($_POST['custom_status_input']);
            $dashboard->addCustomStatus($customStatus);
            // Status will be available in the dropdown on next page load
        }
        
        if (isset($_POST['profit_summary'])) {
            $start = $_POST['start_date'] ?? '';
            $end = $_POST['end_date'] ?? '';
            
            if (!$start || !$end) {
                $profitMessage = "<div class='alert alert-warning'>⚠️ Please select both start and end dates.</div>";
            } else {
                $result = $dashboard->calculateProfitBetweenDates($start, $end);
                $usd = number_format($result['total_profit'], 2);
                $lbp = number_format($result['total_profit_leb'], 0);
                
                $profitMessage = "<div class='alert alert-success'>
                    ✅ Total Profit from <strong>" . htmlspecialchars($start) . "</strong> to <strong>" . htmlspecialchars($end) . "</strong>: 
                    <strong>$" . $usd . "</strong> / <strong>" . $lbp . " LBP</strong>
                </div>";
            }
        }
        
        if (isset($_POST['show_all']) || isset($_POST['search'])) {
            $searchResults = $dashboard->searchCars($_POST);
            $summaryData = $dashboard->getSummaryByStatus();
        }
        
    } catch (Exception $e) {
        error_log("Form processing error: " . $e->getMessage());
        $profitMessage = "<div class='alert alert-danger'>An error occurred. Please try again.</div>";
    }
}

// Get dashboard data with fallbacks
$monthlyData = $dashboard->getMonthlyProfitData();
$profitStats = $dashboard->getProfitStats();
$totalCars = $dashboard->getTotalCars();
$topBrands = $dashboard->getTopBrands();
$customStatuses = $dashboard->getCustomStatuses();
//$customSources = $dashboard->getCustomSources();


// Predefined options
$predefinedBrands = ['Mercedes', 'Audi', 'Renault', 'Toyota', 'BMW'];
$predefinedStatuses = ['Sold Out', 'Available'];
$predefinedSources = ['Qatar', 'Africa'];

// Merge custom statuses with predefined ones
$allStatuses = array_unique(array_merge($predefinedStatuses, $customStatuses));
//$allSources = array_unique(array_merge($predefinedSources, $customSources));



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
    <title>Car Management Dashboard</title>
    <link rel="icon" href="hala.ico">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
    :root {
        --primary: #4361ee;
        --primary-light: #4895ef;
        --secondary: #3f37c9;
        --success: #4cc9f0;
        --danger: #f72585;
        --warning: #f8961e;
        --info: #577590;
        --light: #f8f9fa;
        --dark: #212529;
        --text: #2b2d42;
        --border: #dee2e6;
        --shadow-sm: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24);
        --shadow-md: 0 4px 6px rgba(0,0,0,0.1), 0 1px 3px rgba(0,0,0,0.08);
        --shadow-lg: 0 10px 15px rgba(0,0,0,0.1), 0 4px 6px rgba(0,0,0,0.05);
        --radius-sm: 0.25rem;
        --radius-md: 0.5rem;
        --radius-lg: 1rem;
        --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    }
    
    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }
    
    body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        background-color: #f8fafc;
        color: var(--text);
        line-height: 1.6;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }
    
    .container-fluid {
        padding: 1.5rem;
        max-width: 1920px;
        margin: 0 auto;
    }
    
    /* Header Styles */
    .dashboard-header {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        border-radius: var(--radius-md);
        box-shadow: var(--shadow-md);
        padding: 2rem 1rem;
        margin-bottom: 1.5rem;
        position: relative;
        overflow: hidden;
    }
    
    .dashboard-header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 100%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 70%);
        transform: rotate(30deg);
    }
    
    .dashboard-header h1 {
        font-weight: 800;
        font-size: clamp(1.75rem, 4vw, 2.5rem);
        margin-bottom: 0.5rem;
        position: relative;
    }
    
    .dashboard-header p {
        font-weight: 400;
        opacity: 0.9;
        font-size: 1.1rem;
    }
    
    /* Card Styles */
    .card {
        border: none;
        border-radius: var(--radius-md);
        box-shadow: var(--shadow-sm);
        transition: var(--transition);
        background-color: white;
        margin-bottom: 1.5rem;
        overflow: hidden;
    }
    
    .card:hover {
        box-shadow: var(--shadow-md);
        transform: translateY(-2px);
    }
    
    .card-body {
        padding: 1.5rem;
    }
    
    .card-title {
        font-weight: 600;
        color: var(--dark);
        margin-bottom: 1rem;
        font-size: 1.25rem;
    }
    
    /* Stat Cards */
    .stat-card {
        border-left: 4px solid var(--primary);
        transition: var(--transition);
        height: 100%;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-md);
    }
    
    .stat-value {
        font-size: clamp(1.5rem, 3vw, 2rem);
        font-weight: 700;
        color: var(--primary);
        line-height: 1.2;
    }
    
    /* Navigation Cards */
    .nav-card {
        border: none;
        color: white;
        transition: var(--transition);
        height: 100%;
        border-radius: var(--radius-md);
        overflow: hidden;
    }
    
    .nav-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-lg);
    }
    
    .nav-card .card-body {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
    }
    
    .nav-card.add { background: linear-gradient(135deg, var(--success), #3a86ff); }
    .nav-card.update { background: linear-gradient(135deg, var(--warning), #f3722c); }
    .nav-card.delete { background: linear-gradient(135deg, var(--danger), #b5179e); }
    .nav-card.clients { background: linear-gradient(135deg, var(--info), #1d4e89); }
    
    /* Form Styles */
    .form-control, .form-select {
        border: 1px solid #e2e8f0;
        border-radius: var(--radius-sm);
        padding: 0.5rem 0.75rem;
        transition: var(--transition);
    }
    
    .form-control:focus, .form-select:focus {
        border-color: var(--primary-light);
        box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.15);
    }
    
    .input-group .btn {
        border-radius: 0 var(--radius-sm) var(--radius-sm) 0;
    }
    
    /* Button Styles */
    .btn {
        border-radius: var(--radius-sm);
        padding: 0.5rem 1.25rem;
        font-weight: 500;
        transition: var(--transition);
    }
    
    .btn-primary {
        background-color: var(--primary);
        border-color: var(--primary);
    }
    
    .btn-primary:hover {
        background-color: var(--secondary);
        border-color: var(--secondary);
        transform: translateY(-1px);
        box-shadow: var(--shadow-sm);
    }
    
    .btn-secondary {
        background-color: #64748b;
        border-color: #64748b;
    }
    
    /* Table Styles */
    .table-responsive {
        border-radius: var(--radius-md);
        overflow: hidden;
        box-shadow: var(--shadow-sm);
        margin-bottom: 0;
    }
    
    .table {
        margin-bottom: 0;
        min-width: 100%;
    }
    
    .table th {
        background-color: var(--primary);
        color: white;
        position: sticky;
        top: 0;
        font-weight: 600;
        padding: 1rem;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
    }
    
    .table td {
        padding: 0.75rem 1rem;
        vertical-align: middle;
        border-top: 1px solid #f1f5f9;
    }
    
    .table tr:hover td {
        background-color: #f8fafc;
    }
    
    /* Summary Table */
    .summary-table {
        background: white;
        border-radius: var(--radius-md);
        overflow: hidden;
    }
    
    .summary-table th {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        text-align: center;
        padding: 1rem;
    }
    
    .summary-table td {
        text-align: center;
        font-weight: 500;
        padding: 0.75rem;
    }
    
    .summary-table .status-cell {
        font-weight: 700;
        text-transform: uppercase;
        color: var(--primary);
    }
    
    /* Chart Containers */
    .chart-container {
        position: relative;
        height: 300px;
        min-height: 300px;
        width: 100%;
    }
    
    /* Alert Styles */
    .alert {
        border-radius: var(--radius-sm);
        padding: 1rem;
        margin-bottom: 1rem;
    }
    
    /* Responsive Adjustments */
    @media (max-width: 1199.98px) {
        .container-fluid {
            padding: 1rem;
        }
        
        .card-body {
            padding: 1.25rem;
        }
    }
    
    @media (max-width: 991.98px) {
        .table-responsive-lg {
            font-size: 0.85rem;
        }
        
        .table th, .table td {
            padding: 0.6rem;
        }
        
        .chart-container {
            height: 250px;
        }
    }
    
    @media (max-width: 767.98px) {
        .dashboard-header {
            padding: 1.5rem 1rem;
        }
        
        .stat-card .card-body {
            padding: 1rem;
        }
        
        .table-responsive-lg {
            font-size: 0.8rem;
        }
        
        .table th {
            font-size: 0.7rem;
            padding: 0.5rem;
        }
        
        .table td {
            padding: 0.4rem;
        }
        
        .form-control, .form-select, .btn {
            padding: 0.5rem;
        }
    }
    
    @media (max-width: 575.98px) {
        .container-fluid {
            padding: 0.75rem;
        }
        
        .dashboard-header {
            border-radius: 0;
            margin-left: -0.75rem;
            margin-right: -0.75rem;
        }
        
        .row.g-3 {
            margin-left: -0.5rem;
            margin-right: -0.5rem;
        }
        
        .row.g-3 > [class^="col-"] {
            padding-left: 0.5rem;
            padding-right: 0.5rem;
        }
        
        .card-body {
            padding: 1rem;
        }
        
        .chart-container {
            height: 220px;
        }
    }
    
    /* Modern Scrollbar */
    ::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }
    
    ::-webkit-scrollbar-track {
        background: #f1f5f9;
    }
    
    ::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }
    
    ::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
    
    /* Animation */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .card, .stat-card, .nav-card {
        animation: fadeIn 0.5s ease-out forwards;
    }
    
    /* Delay animations for a staggered effect */
    .stat-card:nth-child(1) { animation-delay: 0.1s; }
    .stat-card:nth-child(2) { animation-delay: 0.2s; }
    .stat-card:nth-child(3) { animation-delay: 0.3s; }
    .stat-card:nth-child(4) { animation-delay: 0.4s; }
    
    .nav-card:nth-child(1) { animation-delay: 0.1s; }
    .nav-card:nth-child(2) { animation-delay: 0.2s; }
    .nav-card:nth-child(3) { animation-delay: 0.3s; }
    .nav-card:nth-child(4) { animation-delay: 0.4s; }






    <style>
    /* ... (keep your existing styles) ... */

    /* Enhanced Table Container Styles */
    .table-container {
        position: relative;
        max-height: 60vh;
        overflow: auto;
        border-radius: var(--radius-md);
        box-shadow: var(--shadow-sm);
        margin-bottom: 1.5rem;
    }

    /* Table Wrapper for Horizontal Scrolling */
    .table-wrapper {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        position: relative;
    }

    /* Sticky Headers for Both Tables */
    .table-responsive-lg thead th {
        position: sticky;
        top: 0;
        z-index: 10;
        background-color: var(--primary);
        color: white;
    }

    .summary-table thead th {
        position: sticky;
        top: 0;
        z-index: 10;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
    }

    /* Ensure tables take full width */
    .table-responsive-lg table,
    .summary-table {
        min-width: 100%;
        width: max-content;
    }

    /* Better scrolling experience */
    .table-responsive-lg tbody {
        display: block;
        overflow-y: auto;
        max-height: 50vh;
    }

    /* Responsive adjustments */
    @media (max-width: 991.98px) {
        .table-container {
            max-height: 50vh;
        }
    }

    @media (max-width: 767.98px) {
        .table-container {
            max-height: 40vh;
        }
    }
</style>
</style>
</head>

<body>
    <div class="container-fluid py-3">
        <!-- Header -->
        <div class="dashboard-header p-4 mb-4 text-center">
            <h1 class="display-5 fw-bold">🚗 Car Management Dashboard</h1>
            <p class="lead mb-0">Professional inventory and financial tracking system</p>
        </div>

        <!-- Navigation Cards -->
        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-md-3">
                <a href="add_car.php" class="card nav-card add text-decoration-none h-100">
                    <div class="card-body text-center py-4">
                        <h3 class="card-title mb-2">➕ Add Car</h3>
                        <p class="card-text mb-0">Add new vehicle to inventory</p>
                    </div>
                </a>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <a href="update2.php" class="card nav-card update text-decoration-none h-100">
                    <div class="card-body text-center py-4">
                        <h3 class="card-title mb-2">✏️ Update Car</h3>
                        <p class="card-text mb-0">Modify existing vehicle details</p>
                    </div>
                </a>
            </div>
          <!--  <div class="col-12 col-sm-6 col-md-3">
                <a href="delete_car.php" class="card nav-card delete text-decoration-none h-100">
                    <div class="card-body text-center py-4">
                        <h3 class="card-title mb-2">🗑️ Delete Car</h3>
                        <p class="card-text mb-0">Remove vehicle from inventory</p>
                    </div>
                </a>
            </div> -->
            <div class="col-12 col-sm-6 col-md-3">
                <a href="clients.php" class="card nav-card clients text-decoration-none h-100">
                    <div class="card-body text-center py-4">
                        <h3 class="card-title mb-2">👤 Clients Manager</h3>
                        <p class="card-text mb-0">Manage customer information</p>
                    </div>
                </a>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-md-3">
                <div class="card stat-card h-100">
                    <div class="card-body text-center">
                        <div class="stat-value text-primary">$<?= number_format($profitStats['month'], 0) ?></div>
                        <div class="text-muted">📅 Monthly Profit</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="card stat-card h-100">
                    <div class="card-body text-center">
                        <div class="stat-value text-primary">$<?= number_format($profitStats['year'], 0) ?></div>
                        <div class="text-muted">📆 Yearly Profit</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="card stat-card h-100">
                    <div class="card-body text-center">
                        <div class="stat-value text-primary"><?= $totalCars ?></div>
                        <div class="text-muted">🚗 Total Cars</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="card stat-card h-100">
                    <div class="card-body text-center">
                        <div class="stat-value text-primary"><?= count($topBrands) ?></div>
                        <div class="text-muted">🏆 Active Brands</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row g-3 mb-4">
            <div class="col-12 col-lg-6">
                <div class="card h-100">
                    <div class="card-body">
                        <h3 class="card-title">📈 Monthly Profit Trend</h3>
                        <div class="chart-container">
                            <canvas id="profitChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-6">
                <div class="card h-100">
                    <div class="card-body">
                        <h3 class="card-title">🔝 Top Selling Brands</h3>
                        <div class="chart-container">
                            <canvas id="brandsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search Form -->
        <div class="card mb-4">
            <div class="card-body">
                <h3 class="card-title mb-3">🔍 Search & Filter Cars</h3>
                <form method="POST">
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
    <option value="Sold Out">Sold Out</option>
    <option value="Available">Available</option>
    <!-- Custom statuses will be added here by JavaScript -->
</select>
                        </div>

 <div class="col-12 col-md-6 col-lg-3">
                            <label for="source" class="form-label">Source</label>
                            <select class="form-select" id="source" name="source">
    <option value="">All Sources</option>
    <option value="Qatar">Qatar</option>
    <option value="Emirates">Emirates</option>
    <!-- Custom statuses will be added here by JavaScript -->
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
                      <!-- Status Dropdown -->
<!-- HTML for all three input groups -->
<div class="row g-3 mb-4">
    <!-- Status Input -->
    <div class="col-12 col-md-6 col-lg-4">
        <label class="form-label">Add Status</label>
        <div class="input-group">
            <input type="text" class="form-control custom-status-input" placeholder="New Status">
            <button type="button" class="btn btn-primary add-status-btn">Add</button>
        </div>
        <div class="status-feedback mt-2"></div>
    </div>
    
    <!-- Brand Input -->
    <div class="col-12 col-md-6 col-lg-4">
        <label class="form-label">Add Brand</label>
        <div class="input-group">
            <input type="text" class="form-control custom-brand-input" placeholder="New Brand">
            <button type="button" class="btn btn-primary add-brand-btn">Add</button>
        </div>
        <div class="brand-feedback mt-2"></div>
    </div>
    
    <!-- Source Input -->
    <div class="col-12 col-md-6 col-lg-4">
        <label class="form-label">Add Source</label>
        <div class="input-group">
            <input type="text" class="form-control custom-source-input" placeholder="New Source">
            <button type="button" class="btn btn-primary add-source-btn">Add</button>
        </div>
        <div class="source-feedback mt-2"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize dropdowns data
    const dropdowns = {
        status: {
            input: document.querySelector('.custom-status-input'),
            button: document.querySelector('.add-status-btn'),
            feedback: document.querySelector('.status-feedback'),
            storageKey: 'customStatuses',
            defaultOptions: ['Sold Out', 'Available'],
            selectId: 'status'
        },
        brand: {
            input: document.querySelector('.custom-brand-input'),
            button: document.querySelector('.add-brand-btn'),
            feedback: document.querySelector('.brand-feedback'),
            storageKey: 'customBrands',
            defaultOptions: ['Mercedes', 'Audi', 'Renault', 'Toyota', 'BMW'],
            selectId: 'brand'
        },
        source: {
            input: document.querySelector('.custom-source-input'),
            button: document.querySelector('.add-source-btn'),
            feedback: document.querySelector('.source-feedback'),
            storageKey: 'customSources',
            defaultOptions: [],
            selectId: 'source'
        }
    };

    // Initialize all dropdowns
    Object.keys(dropdowns).forEach(key => {
        const dropdown = dropdowns[key];
        
        // Load saved items
        loadItems(dropdown);
        
        // Add click event to button
        dropdown.button.addEventListener('click', () => addItem(dropdown));
        
        // Add Enter key support
        dropdown.input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                addItem(dropdown);
            }
        });
    });

    // Load items from localStorage
    function loadItems(dropdown) {
        const savedItems = JSON.parse(localStorage.getItem(dropdown.storageKey)) || [];
        const selectElement = document.getElementById(dropdown.selectId);
        
        if (!selectElement) {
            console.error(`Dropdown with ID ${dropdown.selectId} not found`);
            return;
        }
        
        // Clear existing options (keep first option if it's "All" or similar)
        while (selectElement.options.length > 1) {
            selectElement.remove(1);
        }
        
        // Combine default and saved items
        const allItems = [...new Set([...dropdown.defaultOptions, ...savedItems])];
        
        // Add items to dropdown
        allItems.forEach(item => {
            if (!Array.from(selectElement.options).some(opt => opt.value === item)) {
                const option = new Option(item, item);
                selectElement.add(option);
            }
        });
    }

    // Add new item
    function addItem(dropdown) {
        const newItem = dropdown.input.value.trim();
        const selectElement = document.getElementById(dropdown.selectId);
        
        if (!newItem) {
            showFeedback(dropdown.feedback, 'Please enter a value', 'danger');
            return;
        }
        
        // Check if already exists
        if (selectElement && Array.from(selectElement.options).some(opt => opt.value === newItem)) {
            showFeedback(dropdown.feedback, 'This item already exists!', 'warning');
            return;
        }
        
        // Get current items from storage
        const currentItems = JSON.parse(localStorage.getItem(dropdown.storageKey)) || [];
        
        // Add new item
        currentItems.push(newItem);
        localStorage.setItem(dropdown.storageKey, JSON.stringify(currentItems));
        
        // Add to dropdown
        if (selectElement) {
            const option = new Option(newItem, newItem);
            selectElement.add(option);
        }
        
        // Clear input
        dropdown.input.value = '';
        
        // Show success message
        showFeedback(dropdown.feedback, `"${newItem}" added successfully!`, 'success');
        
        // Reload items to update the display
        loadItems(dropdown);
    }

    // Delete item (called from HTML onclick)
    window.deleteItem = function(storageKey, itemValue) {
        const dropdown = Object.values(dropdowns).find(d => d.storageKey === storageKey);
        if (!dropdown) return;
        
        const selectElement = document.getElementById(dropdown.selectId);
        
        // Remove from localStorage
        const currentItems = JSON.parse(localStorage.getItem(storageKey)) || [];
        const updatedItems = currentItems.filter(item => item !== itemValue);
        localStorage.setItem(storageKey, JSON.stringify(updatedItems));
        
        // Remove from dropdown
        if (selectElement) {
            const options = Array.from(selectElement.options);
            const optionToRemove = options.find(opt => opt.value === itemValue);
            if (optionToRemove) {
                selectElement.remove(optionToRemove.index);
            }
        }
        
        // Show success message
        showFeedback(dropdown.feedback, `"${itemValue}" deleted successfully!`, 'success');
    };

    // Show feedback message
    function showFeedback(element, message, type) {
        element.innerHTML = `
            <div class="alert alert-${type} alert-dismissible fade show">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
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
});
</script>

<style>
    /* Style for dropdown container */
    .dropdown-container {
        position: relative;
        display: inline-block;
        width: 100%;
    }
    
    /* Style for dropdown items with delete buttons */
    .dropdown-item-with-delete {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 12px;
    }
    
    .delete-item-btn {
        color: white;
        background-color: #dc3545;
        border: none;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        font-size: 12px;
        line-height: 1;
        padding: 0;
        margin-left: 8px;
    }
    
    .delete-item-btn:hover {
        background-color: #bb2d3b;
    }
</style>



</body>
</html>
                    <div class="row mt-3">
                        <div class="col-12 col-md-6">
                            <button type="submit" name="search" class="btn btn-primary w-100">
                                <i class="bi bi-search"></i> Search Cars
                            </button>
                        </div>
                        <div class="col-12 col-md-6">
                            <button type="submit" name="show_all" class="btn btn-secondary w-100">
                                <i class="bi bi-list-ul"></i> Show All Cars
                            </button>
                        </div>
                    </div>
                </form>
                
                <!-- Profit Summary Form -->
                <form method="POST" class="mt-4">
                    <?php csrf_input_field(); ?>
                    <h5 class="mb-3">💰 Profit Summary Between Dates</h5>
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" required>
                        </div>
                        <div class="col-12">
                            <button type="submit" name="profit_summary" class="btn btn-primary">
                                Calculate Profit
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

        <!-- Summary Table -->
        <?php if (!empty($summaryData)): ?>
      <div class="card mb-4">
    <div class="card-body">
        <h3 class="card-title mb-3">📊 Financial Summary by Status</h3>
        <div class="table-container">
            <div class="table-wrapper">
                <table class="table table-bordered summary-table">
                        <thead>
                            <tr>
                                <th rowspan="2">Status</th>
                                <th rowspan="2">Count</th>
                                <th colspan="2">Selling Price</th>
                                <th colspan="2">Purchase Price</th>
                                <th colspan="2">In Cost</th>
                                <th colspan="2">Ex Cost</th>
                                <th >Custom Duties</th>
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
                                <td class="status-cell"><?= htmlspecialchars($row['status'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($row['car_count'] ?? 0) ?></td>
                                <td>$<?= number_format($row['total_selling_price_usd'] ?? 0, 2) ?></td>
                                <td><?= number_format($row['total_selling_price_lbp'] ?? 0, 0) ?> LBP</td>
                                <td>$<?= number_format($row['total_purchase_price_usd'] ?? 0, 2) ?></td>
                                <td><?= number_format($row['total_purchase_price_lbp'] ?? 0, 0) ?> LBP</td>
                                <td>$<?= number_format($row['total_in_cost_usd'] ?? 0, 2) ?></td>
                                <td><?= number_format($row['total_in_cost_lbp'] ?? 0, 0) ?> LBP</td>
                                <td>$<?= number_format($row['total_ex_cost_usd'] ?? 0, 2) ?></td>
                                <td><?= number_format($row['total_ex_cost_lbp'] ?? 0, 0) ?> LBP</td>
                             
                                <td><?= number_format($row['total_customs_lbp'] ?? 0, 0) ?> LBP</td>
                                <td>$<?= number_format($row['total_total_cost_usd'] ?? 0, 2) ?></td>
                                <td><?= number_format($row['total_total_cost_lbp'] ?? 0, 0) ?> LBP</td>
                                <td>$<?= number_format($row['total_profit_usd'] ?? 0, 2) ?></td>
                                <td><?= number_format($row['total_profit_lbp'] ?? 0, 0) ?> LBP</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    <</table>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

        <!-- Search Results -->
        <?php if (!empty($searchResults)): ?>
       <div class="card">
    <div class="card-body">
        <h3 class="card-title mb-3">🔎 Search Results (<?= count($searchResults) ?> cars found)</h3>
        <div class="table-container">
            <div class="table-wrapper">
                <table class="table table-striped table-hover">
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
                               <!-- <th>Customs ($)</th> -->
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
                                <td><?= htmlspecialchars(substr($car['chis_nmbr'], -6)) ?></td>
                                <td><?= htmlspecialchars($car['brand'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($car['model'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($car['color'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($car['status'] ?? 'N/A') ?></td>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Profit Chart
        const profitCtx = document.getElementById('profitChart').getContext('2d');
        const profitChart = new Chart(profitCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_keys($monthlyData)) ?>,
                datasets: [{
                    label: 'Monthly Profit ($)',
                    data: <?= json_encode(array_values($monthlyData)) ?>,
                    backgroundColor: 'rgba(0, 120, 215, 0.2)',
                    borderColor: 'rgba(0, 120, 215, 1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return '$' + context.raw.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });

        // Brands Chart
        const brandsCtx = document.getElementById('brandsChart').getContext('2d');
        const brandsChart = new Chart(brandsCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($topBrands, 'brand')) ?>,
                datasets: [{
                    label: 'Cars Sold',
                    data: <?= json_encode(array_column($topBrands, 'sold_count')) ?>,
                    backgroundColor: [
                        'rgba(40, 167, 69, 0.7)',
                        'rgba(220, 53, 69, 0.7)',
                        'rgba(255, 193, 7, 0.7)',
                        'rgba(23, 162, 184, 0.7)',
                        'rgba(108, 117, 125, 0.7)'
                    ],
                    borderColor: [
                        'rgba(40, 167, 69, 1)',
                        'rgba(220, 53, 69, 1)',
                        'rgba(255, 193, 7, 1)',
                        'rgba(23, 162, 184, 1)',
                        'rgba(108, 117, 125, 1)'
                    ],
                    borderWidth: 1
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
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });

        // Auto-focus on search input
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.querySelector('input[name="model"]');
            if (searchInput) {
                searchInput.focus();
            }
            
            // Set default dates for profit summary
            const today = new Date().toISOString().split('T')[0];
            const firstOfMonth = new Date();
            firstOfMonth.setDate(1);
            const firstOfMonthStr = firstOfMonth.toISOString().split('T')[0];
            
            document.getElementById('start_date').value = firstOfMonthStr;
            document.getElementById('end_date').value = today;
        });
    </script>
</body>
</html> 
