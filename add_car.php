<?php
// Security and Configuration
define('DB_ACCESS_ALLOWED', true);
require_once 'connect.php';
require_once 'csrf.php';

// Initialize session and security
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Enhanced Security Headers
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; font-src 'self';");
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("Referrer-Policy: strict-origin-when-cross-origin");

// Input Sanitization Function
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Get database connection
try {
    $pdo = getDatabase();
} catch (Exception $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("<div class='error-message'>Database connection failed. Please try again later.</div>");
}

// Predefined dropdown options
$brands = ['Mercedes', 'Audi', 'Renault', 'Toyota', 'BMW', 'Ford', 'Hyundai', 'Kia', 'Mazda', 'Volkswagen', 'Honda', 'Nissan', 'Chevrolet', 'Peugeot', 'Citroën'];
$statuses = ['Available', 'Sold Out', 'Reserved', 'In Transit', 'Maintenance'];
$sources = ['Qatar', 'Emirates', 'Africa', 'Europe', 'Local', 'Auction', 'Trade-in'];

// Get current exchange rate
try {
    $rateStmt = $pdo->query("SELECT rate FROM exchange_rates ORDER BY rate_date DESC, id DESC LIMIT 1");
    $rateData = $rateStmt->fetch();
    $currentRate = $rateData['rate'] ?? 'Not set';
} catch (Exception $e) {
    error_log("Failed to get exchange rate: " . $e->getMessage());
    $currentRate = 'Error loading rate';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Car - Car Management System</title>
    <link rel="icon" href="hala.ico" />
    <style>
        .page-container {
            padding: 1rem;
            max-width: 100%;
            box-sizing: border-box;
        }
        
        @media (min-width: 768px) {
            .page-container {
                max-width: 720px;
                margin: auto;
            }
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            min-height: 100vh;
            color: #333;
        }
        
        .form-wrapper {
            background-color: #fff;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            max-width: 600px;
            margin: 2rem auto;
            box-sizing: border-box;
        }
        
        .form-wrapper h3 {
            color: #4a5568;
            text-align: center;
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .form-wrapper input,
        .form-wrapper select {
            width: 100%;
            padding: 0.75rem;
            margin-bottom: 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 1rem;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }
        
        .form-wrapper input:focus,
        .form-wrapper select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-wrapper input[type="submit"] {
            cursor: pointer;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            font-weight: 600;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .form-wrapper input[type="submit"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        .rate-info {
            background: linear-gradient(135deg, #eaf7ff 0%, #f0f8ff 100%);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #667eea;
        }
        
        .rate-info strong {
            color: #4a5568;
        }
        
        .input-container {
            position: relative;
            display: block;
            margin-bottom: 1rem;
        }
        
        .alert {
            display: none;
            position: absolute;
            top: -40px;
            left: 0;
            background-color: #4a5568;
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 0.875rem;
            white-space: nowrap;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .alert::after {
            content: '';
            position: absolute;
            top: 100%;
            left: 20px;
            border: 6px solid transparent;
            border-top-color: #4a5568;
        }
        
        .input-container:hover .alert {
            display: block;
        }
        
        .success-message {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            padding: 1rem;
            border-radius: 8px;
            border-left: 4px solid #28a745;
            margin: 1rem 0;
        }
        
        .error-message {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            padding: 1rem;
            border-radius: 8px;
            border-left: 4px solid #dc3545;
            margin: 1rem 0;
        }
        
        .warning-message {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            color: #856404;
            padding: 1rem;
            border-radius: 8px;
            border-left: 4px solid #ffc107;
            margin: 1rem 0;
        }
        
        @media (max-width: 480px) {
            .form-wrapper {
                padding: 1.5rem;
                margin: 1rem;
                border-radius: 8px;
            }
            
            .form-wrapper h3 {
                font-size: 1.25rem;
            }
            
            .form-wrapper input,
            .form-wrapper select {
                padding: 0.625rem;
                font-size: 0.9rem;
            }
        }
        
        .navigation-link {
            display: inline-block;
            margin-top: 1rem;
            padding: 0.5rem 1rem;
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            transition: transform 0.2s ease;
        }
        
        .navigation-link:hover {
            transform: translateY(-1px);
            text-decoration: none;
            color: white;
        }
    </style>
</head>
<body>
    <div class="page-container">
        <!-- Current Exchange Rate Display -->
        <div class="rate-info">
            <span style="font-size: 1.2rem;">📌</span>
            <strong>Current Exchange Rate: <?= htmlspecialchars($currentRate) ?> LBP/USD</strong>
        </div>

        <!-- Add Car Form -->
        <form method="POST" class="form-wrapper">
            <?php csrf_input_field(); ?>
            <h3>🚗 Add a New Car</h3>
            
            <input type="text" name="chis_nmbr" placeholder="Chassis Number" required />
            
            <select name="brand" required>
                <option value="">Select Brand</option>
                <?php foreach ($brands as $brand): ?>
                    <option value="<?= htmlspecialchars($brand) ?>"><?= htmlspecialchars($brand) ?></option>
                <?php endforeach; ?>
            </select>
            
            <input type="text" name="model" placeholder="Model" />
            
            <input type="number" name="price_selling_dol" placeholder="Selling Price ($)" step="0.01" />
            
            <input type="number" name="price_buying_doll" placeholder="Purchase Price ($)" step="0.01" />
            
            <input type="number" name="in_cost" placeholder="Internal Costs ($)" step="0.01" />
            
            <input type="number" name="ex_cost" placeholder="External Costs ($)" step="0.01" />
            
            <input type="text" name="color" placeholder="Color (required)" required />
            
            <div class="input-container">
                <input type="date" name="buying_date" data-placeholder="Purchase Date (optional)" />
                <div class="alert">Purchase Date (optional)</div>
            </div>
            
            <div class="input-container">
                <input type="date" name="selling_date" data-placeholder="Date of Selling (optional)" />
                <div class="alert">Date of Selling (optional)</div>
            </div>
            
            <select name="source" required>
                <option value="">Select Source (required)</option>
                <?php foreach ($sources as $source): ?>
                    <option value="<?= htmlspecialchars($source) ?>"><?= htmlspecialchars($source) ?></option>
                <?php endforeach; ?>
            </select>
            
            <select name="status" required>
                <option value="">Select Status (required)</option>
                <?php foreach ($statuses as $status): ?>
                    <option value="<?= htmlspecialchars($status) ?>"><?= htmlspecialchars($status) ?></option>
                <?php endforeach; ?>
            </select>
            
            <input type="number" name="mileage" placeholder="Mileage (required)" required min="0" />
            
            <input type="text" name="maintenance" placeholder="Maintenance Notes (required)" required />
            
            <input type="number" name="jamarik" placeholder="Customs Duties ($)" step="0.01" />
            
            <input type="submit" name="add_car" value="Add Car" />
        </form>

        <!-- Update Exchange Rate Form -->
        <form method="POST" class="form-wrapper">
            <?php csrf_input_field(); ?>
            <h3>💱 Update Exchange Rate</h3>
            <input type="number" name="rate" step="0.01" min="1" required placeholder="New Exchange Rate (LBP/USD)" />
            <input type="submit" name="update_rate" value="Update Rate" />
        </form>

        <!-- Navigation -->
        <div style="text-align: center;">
            <a href="dashboard.php" class="navigation-link">← Back to Dashboard</a>
        </div>
    </div>

    <?php
    // Handle form submissions
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        try {
            // Verify CSRF token
            if (!verify_csrf_token()) {
                throw new Exception("Invalid CSRF token. Please refresh the page and try again.");
            }

            // Handle exchange rate update
            if (isset($_POST['update_rate'])) {
                $new_rate = filter_var($_POST['rate'], FILTER_VALIDATE_FLOAT);
                
                if ($new_rate === false || $new_rate <= 0) {
                    throw new Exception("Invalid exchange rate. Please enter a positive number.");
                }
                
                $stmt = $pdo->prepare("INSERT INTO exchange_rates (rate, rate_date) VALUES (?, NOW())");
                $stmt->execute([$new_rate]);
                
                echo "<div class='success-message'>✅ Exchange rate updated successfully to " . htmlspecialchars($new_rate) . " LBP/USD!</div>";
                
                // Refresh the current rate display
                $currentRate = $new_rate;
                echo "<script>
                    setTimeout(function() {
                        window.location.href = window.location.pathname;
                    }, 2000);
                </script>";
            }

            // Handle car addition
            if (isset($_POST['add_car'])) {
                // Get current exchange rate
                $rateStmt = $pdo->query("SELECT rate FROM exchange_rates ORDER BY rate_date DESC, id DESC LIMIT 1");
                $rateData = $rateStmt->fetch();
                $rate = $rateData['rate'] ?? null;

                if (!$rate || $rate <= 0) {
                    throw new Exception("No valid exchange rate set. Please update the exchange rate first.");
                }

                // Sanitize and validate inputs
                $chis_nmbr = sanitize_input($_POST['chis_nmbr']);
                $brand = sanitize_input($_POST['brand']);
                $model = sanitize_input($_POST['model']) ?: '';  // Default to empty string
                $color = sanitize_input($_POST['color']) ?: 'Not specified';  // Default value
                $source = sanitize_input($_POST['source']) ?: 'Not specified';  // Default value
                $status = sanitize_input($_POST['status']) ?: 'Available';  // Default value
                $maintenance = sanitize_input($_POST['maintenance']) ?: 'No maintenance notes';  // Default value
                $buying_date = sanitize_input($_POST['buying_date']);
                $selling_date = sanitize_input($_POST['selling_date']);

                // Validate required fields (based on NOT NULL constraints)
                if (empty($chis_nmbr)) {
                    throw new Exception("Chassis number is required.");
                }
                if (empty($brand)) {
                    throw new Exception("Brand is required.");
                }

                // Validate numeric fields
                $numeric_fields = ['price_selling_dol', 'price_buying_doll', 'in_cost', 'ex_cost', 'mileage', 'jamarik'];
                $validated_numbers = [];

                foreach ($numeric_fields as $field) {
                    $input_value = $_POST[$field] ?? '';
                    
                    // Handle empty values for optional fields
                    if ($input_value === '' || $input_value === null) {
                        if ($field === 'mileage') {
                            // Mileage is NOT NULL, so default to 0
                            $validated_numbers[$field] = 0;
                        } else {
                            // Other fields can be 0
                            $validated_numbers[$field] = 0;
                        }
                    } else {
                        $value = filter_var($input_value, FILTER_VALIDATE_FLOAT);
                        if ($value === false) {
                            throw new Exception("Invalid value for " . ucfirst(str_replace('_', ' ', $field)) . ". Please enter a valid number.");
                        }
                        $validated_numbers[$field] = $value;
                    }
                }

                // Calculate Lebanese Pound equivalents
                $price_selling_leb = round($validated_numbers['price_selling_dol'] * $rate);
                $price_leb_buying = round($validated_numbers['price_buying_doll'] * $rate);
                $jamarik_leb = round($validated_numbers['jamarik'] * $rate);
                $in_costLeb = round($validated_numbers['in_cost'] * $rate);
                $ex_costLeb = round($validated_numbers['ex_cost'] * $rate);

                // Calculate profit
                $total_cost_usd = $validated_numbers['price_buying_doll'] + $validated_numbers['in_cost'] + $validated_numbers['ex_cost'] + $validated_numbers['jamarik'];
                $profit = $validated_numbers['price_selling_dol'] - $total_cost_usd;
                $profit_leb = round($profit * $rate);
                $total_cost_leb = round($total_cost_usd * $rate);

                // Warning for negative profit
                if ($profit < 0) {
                    echo "<div id='profit-warning' class='warning-message'>⚠️ Warning: Profit is negative ($" . number_format($profit, 2) . ").</div>";
                }

                // Check if chassis number already exists
                $checkStmt = $pdo->prepare("SELECT chis_nmbr FROM cars WHERE chis_nmbr = ?");
                $checkStmt->execute([$chis_nmbr]);
                if ($checkStmt->fetch()) {
                    throw new Exception("A car with chassis number '" . htmlspecialchars($chis_nmbr) . "' already exists.");
                }

                // Begin transaction
                $pdo->beginTransaction();

                try {
                    // Insert into cars table
                    $stmt1 = $pdo->prepare("
                        INSERT INTO cars (chis_nmbr, brand, model, color, mileage, status, source, maintenance)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt1->execute([
                        $chis_nmbr, $brand, $model, $color, 
                        $validated_numbers['mileage'], $status, $source, $maintenance
                    ]);

                    // Insert into car_financials table
                    $stmt2 = $pdo->prepare("
                        INSERT INTO car_financials (
                            chis_nmbr, price_buying_doll, price_selling_leb, price_leb_buying, 
                            price_selling_dol, buying_date, selling_date, in_cost, ex_cost, 
                            in_costLeb, ex_costLeb, jamarik, jamarik_leb, profit, profit_leb, 
                            total_cost, total_cost_leb
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt2->execute([
                        $chis_nmbr, $validated_numbers['price_buying_doll'], $price_selling_leb,
                        $price_leb_buying, $validated_numbers['price_selling_dol'], 
                        $buying_date ?: null, $selling_date ?: null,
                        $validated_numbers['in_cost'], $validated_numbers['ex_cost'],
                        $in_costLeb, $ex_costLeb, $validated_numbers['jamarik'],
                        $jamarik_leb, $profit, $profit_leb, $total_cost_usd, $total_cost_leb
                    ]);

                    // Commit transaction
                    $pdo->commit();
                    
                    echo "<div class='success-message'>✅ Car and financial data added successfully!<br>
                          <strong>Chassis:</strong> " . htmlspecialchars($chis_nmbr) . "<br>
                          <strong>Brand:</strong> " . htmlspecialchars($brand) . "<br>
                          <strong>Profit:</strong> $" . number_format($profit, 2) . " / " . number_format($profit_leb, 0) . " LBP</div>";
                    
                    // Clear form by redirecting after a delay
                    echo "<script>
                        setTimeout(function() {
                            if (confirm('Car added successfully! Would you like to add another car?')) {
                                window.location.href = window.location.pathname;
                            } else {
                                window.location.href = 'dashboard.php';
                            }
                        }, 3000);
                    </script>";

                } catch (Exception $e) {
                    // Rollback transaction on error
                    $pdo->rollback();
                    throw new Exception("Failed to add car: " . $e->getMessage());
                }
            }

        } catch (Exception $e) {
            echo "<div class='error-message'>❌ " . htmlspecialchars($e->getMessage()) . "</div>";
            error_log("Add car error: " . $e->getMessage());
        }
    }
    ?>

    <script>
        // Enhanced form functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Show profit warning if exists
            const warning = document.querySelector('#profit-warning');
            if (warning) {
                // Show warning for 5 seconds
                setTimeout(function() {
                    warning.style.display = 'none';
                }, 5000);
            }

            // Add date placeholder functionality
            document.querySelectorAll('input[type="date"]').forEach(input => {
                if (!input.parentElement.classList.contains('input-container')) {
                    const container = document.createElement('div');
                    container.className = 'input-container';
                    
                    const alert = document.createElement('div');
                    alert.className = 'alert';
                    alert.textContent = input.getAttribute('data-placeholder');
                    
                    input.parentNode.insertBefore(container, input);
                    container.appendChild(input);
                    container.appendChild(alert);
                }
            });

            // Form validation
            const form = document.querySelector('form[method="POST"]');
            if (form && form.querySelector('input[name="add_car"]')) {
                form.addEventListener('submit', function(e) {
                    const chassis = form.querySelector('input[name="chis_nmbr"]').value.trim();
                    const brand = form.querySelector('select[name="brand"]').value;
                    const color = form.querySelector('input[name="color"]').value.trim();
                    const source = form.querySelector('select[name="source"]').value;
                    const status = form.querySelector('select[name="status"]').value;
                    const mileage = form.querySelector('input[name="mileage"]').value.trim();
                    const maintenance = form.querySelector('input[name="maintenance"]').value.trim();
                    
                    // Validate required fields
                    if (!chassis) {
                        e.preventDefault();
                        alert('Please enter a chassis number.');
                        form.querySelector('input[name="chis_nmbr"]').focus();
                        return;
                    }
                    
                    if (!brand) {
                        e.preventDefault();
                        alert('Please select a brand.');
                        form.querySelector('select[name="brand"]').focus();
                        return;
                    }
                    
                    if (!color) {
                        e.preventDefault();
                        alert('Please enter a color.');
                        form.querySelector('input[name="color"]').focus();
                        return;
                    }
                    
                    if (!source) {
                        e.preventDefault();
                        alert('Please select a source.');
                        form.querySelector('select[name="source"]').focus();
                        return;
                    }
                    
                    if (!status) {
                        e.preventDefault();
                        alert('Please select a status.');
                        form.querySelector('select[name="status"]').focus();
                        return;
                    }
                    
                    if (!maintenance) {
                        e.preventDefault();
                        alert('Please enter maintenance notes.');
                        form.querySelector('input[name="maintenance"]').focus();
                        return;
                    }

                    // Disable submit button to prevent double submission
                    const submitBtn = form.querySelector('input[type="submit"]');
                    submitBtn.disabled = true;
                    submitBtn.value = 'Adding Car...';
                    
                    // Re-enable after 5 seconds as safety measure
                    setTimeout(function() {
                        submitBtn.disabled = false;
                        submitBtn.value = 'Add Car';
                    }, 5000);
                });
            }

            // Auto-calculate total when values change
            const numericInputs = ['price_buying_doll', 'in_cost', 'ex_cost', 'jamarik'];
            numericInputs.forEach(fieldName => {
                const input = document.querySelector(`input[name="${fieldName}"]`);
                if (input) {
                    input.addEventListener('input', function() {
                        calculateTotals();
                    });
                }
            });

            function calculateTotals() {
                const buyingPrice = parseFloat(document.querySelector('input[name="price_buying_doll"]').value) || 0;
                const inCost = parseFloat(document.querySelector('input[name="in_cost"]').value) || 0;
                const exCost = parseFloat(document.querySelector('input[name="ex_cost"]').value) || 0;
                const jamarik = parseFloat(document.querySelector('input[name="jamarik"]').value) || 0;
                const sellingPrice = parseFloat(document.querySelector('input[name="price_selling_dol"]').value) || 0;
                
                const totalCost = buyingPrice + inCost + exCost + jamarik;
                const profit = sellingPrice - totalCost;
                
                // You can display this info in a summary if needed
                console.log('Total Cost: $' + totalCost.toFixed(2) + ', Profit: $' + profit.toFixed(2));
            }
        });
    </script>
</body>
</html>