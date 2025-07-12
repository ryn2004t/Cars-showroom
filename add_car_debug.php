<?php
// Security and Configuration
define('DB_ACCESS_ALLOWED', true);
require_once 'connect.php';
require_once 'csrf.php';

// Initialize session and security
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Get database connection
try {
    $pdo = getDatabase();
    echo "<div style='color: green;'>✅ Database connection successful!</div>";
} catch (Exception $e) {
    die("<div style='color: red;'>❌ Database connection failed: " . $e->getMessage() . "</div>");
}

// Debug: Check table structures
echo "<h3>🔍 Database Debug Information</h3>";

try {
    // Check cars table structure
    echo "<h4>Cars Table Structure:</h4>";
    $stmt = $pdo->query("DESCRIBE cars");
    $carsColumns = $stmt->fetchAll();
    echo "<pre>";
    foreach ($carsColumns as $column) {
        echo $column['Field'] . " - " . $column['Type'] . " - " . $column['Null'] . " - " . $column['Key'] . "\n";
    }
    echo "</pre>";

    // Check car_financials table structure
    echo "<h4>Car_Financials Table Structure:</h4>";
    $stmt = $pdo->query("DESCRIBE car_financials");
    $financialsColumns = $stmt->fetchAll();
    echo "<pre>";
    foreach ($financialsColumns as $column) {
        echo $column['Field'] . " - " . $column['Type'] . " - " . $column['Null'] . " - " . $column['Key'] . "\n";
    }
    echo "</pre>";

    // Check exchange_rates table
    echo "<h4>Exchange_Rates Table Structure:</h4>";
    $stmt = $pdo->query("DESCRIBE exchange_rates");
    $ratesColumns = $stmt->fetchAll();
    echo "<pre>";
    foreach ($ratesColumns as $column) {
        echo $column['Field'] . " - " . $column['Type'] . " - " . $column['Null'] . " - " . $column['Key'] . "\n";
    }
    echo "</pre>";

} catch (Exception $e) {
    echo "<div style='color: red;'>❌ Error checking table structure: " . $e->getMessage() . "</div>";
}

// Test form processing
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['test_insert'])) {
    echo "<h3>🧪 Test Insert Results</h3>";
    
    try {
        // Simple test data
        $test_data = [
            'chis_nmbr' => 'TEST123456',
            'brand' => 'Toyota',
            'model' => 'Camry',
            'color' => 'Blue',
            'mileage' => 50000,
            'status' => 'Available',
            'source' => 'Qatar',
            'maintenance' => 'Test maintenance'
        ];

        // Try to insert into cars table
        echo "<div style='background: #f0f0f0; padding: 10px; margin: 10px 0;'>";
        echo "<strong>Attempting to insert into cars table...</strong><br>";
        
        $stmt = $pdo->prepare("
            INSERT INTO cars (chis_nmbr, brand, model, color, mileage, status, source, maintenance)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $test_data['chis_nmbr'],
            $test_data['brand'],
            $test_data['model'],
            $test_data['color'],
            $test_data['mileage'],
            $test_data['status'],
            $test_data['source'],
            $test_data['maintenance']
        ]);
        
        if ($result) {
            echo "<span style='color: green;'>✅ Cars table insert successful!</span><br>";
            
            // Now try financials table with minimal data
            echo "<strong>Attempting to insert into car_financials table...</strong><br>";
            
            // First, let's see what columns actually exist
            $stmt = $pdo->query("SHOW COLUMNS FROM car_financials");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            echo "Available columns: " . implode(', ', $columns) . "<br>";
            
            // Create a basic insert based on available columns
            $financial_data = [
                'chis_nmbr' => $test_data['chis_nmbr'],
                'price_buying_doll' => 10000,
                'price_selling_dol' => 12000,
                'profit' => 2000
            ];
            
            // Build dynamic query based on available columns
            $available_financial_columns = [];
            $values = [];
            $placeholders = [];
            
            foreach ($financial_data as $column => $value) {
                if (in_array($column, $columns)) {
                    $available_financial_columns[] = $column;
                    $values[] = $value;
                    $placeholders[] = '?';
                }
            }
            
            if (!empty($available_financial_columns)) {
                $sql = "INSERT INTO car_financials (" . implode(', ', $available_financial_columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
                echo "SQL: " . $sql . "<br>";
                echo "Values: " . implode(', ', $values) . "<br>";
                
                $stmt2 = $pdo->prepare($sql);
                $result2 = $stmt2->execute($values);
                
                if ($result2) {
                    echo "<span style='color: green;'>✅ Car_financials table insert successful!</span><br>";
                } else {
                    echo "<span style='color: red;'>❌ Car_financials insert failed</span><br>";
                    print_r($stmt2->errorInfo());
                }
            } else {
                echo "<span style='color: red;'>❌ No matching columns found for financial data</span><br>";
            }
            
            // Clean up test data
            $pdo->prepare("DELETE FROM car_financials WHERE chis_nmbr = ?")->execute([$test_data['chis_nmbr']]);
            $pdo->prepare("DELETE FROM cars WHERE chis_nmbr = ?")->execute([$test_data['chis_nmbr']]);
            echo "<span style='color: blue;'>🧹 Test data cleaned up</span><br>";
            
        } else {
            echo "<span style='color: red;'>❌ Cars table insert failed</span><br>";
            print_r($stmt->errorInfo());
        }
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div style='color: red;'>❌ Test insert error: " . $e->getMessage() . "</div>";
        echo "<div style='color: red;'>Stack trace: " . $e->getTraceAsString() . "</div>";
    }
}

// Test current exchange rate
try {
    $rateStmt = $pdo->query("SELECT rate FROM exchange_rates ORDER BY rate_date DESC, id DESC LIMIT 1");
    $rateData = $rateStmt->fetch();
    $currentRate = $rateData['rate'] ?? 'Not set';
    echo "<div style='color: blue;'>📊 Current exchange rate: " . htmlspecialchars($currentRate) . "</div>";
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ Error getting exchange rate: " . $e->getMessage() . "</div>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-form { background: #f9f9f9; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .back-link { display: inline-block; margin-top: 20px; padding: 10px 20px; background: #007cba; color: white; text-decoration: none; border-radius: 4px; }
    </style>
</head>
<body>
    <h1>🔧 Database Debug Tool</h1>
    
    <div class="test-form">
        <h3>Test Database Insert</h3>
        <p>This will test if we can insert data into your database tables.</p>
        <form method="POST">
            <?php csrf_input_field(); ?>
            <button type="submit" name="test_insert">Run Test Insert</button>
        </form>
    </div>

    <div class="test-form">
        <h3>Expected vs Actual Table Structure</h3>
        <p><strong>Expected cars table columns:</strong></p>
        <ul>
            <li>chis_nmbr (varchar/text)</li>
            <li>brand (varchar/text)</li>
            <li>model (varchar/text)</li>
            <li>color (varchar/text)</li>
            <li>mileage (int/bigint)</li>
            <li>status (varchar/text)</li>
            <li>source (varchar/text)</li>
            <li>maintenance (text)</li>
        </ul>
        
        <p><strong>Expected car_financials table columns:</strong></p>
        <ul>
            <li>chis_nmbr (varchar/text)</li>
            <li>price_buying_doll (decimal/float)</li>
            <li>price_selling_dol (decimal/float)</li>
            <li>price_selling_leb (decimal/float)</li>
            <li>price_leb_buying (decimal/float)</li>
            <li>buying_date (date)</li>
            <li>selling_date (date)</li>
            <li>in_cost (decimal/float)</li>
            <li>ex_cost (decimal/float)</li>
            <li>in_costLeb (decimal/float)</li>
            <li>ex_costLeb (decimal/float)</li>
            <li>jamarik (decimal/float)</li>
            <li>jamarik_leb (decimal/float)</li>
            <li>profit (decimal/float)</li>
            <li>profit_leb (decimal/float)</li>
            <li>total_cost (decimal/float) - might be missing</li>
            <li>total_cost_leb (decimal/float) - might be missing</li>
        </ul>
    </div>

    <a href="add_car.php" class="back-link">← Back to Add Car Form</a>
    <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
</body>
</html>