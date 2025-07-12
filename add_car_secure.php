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

// Get database connection
try {
    $pdo = getDatabase();
} catch (Exception $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("<div style='color: red;'>Database connection failed. Please try again later.</div>");
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
    $currentRate = 'Not set';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Car - Secure Version</title>
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
        
        .form-input {
            width: 100%;
            padding: 0.5rem;
            margin-bottom: 0.75rem;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f9;
            margin: 0;
        }

        .form-wrapper {
            background-color: #fff;
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 600px;
            margin: 2rem auto;
            box-sizing: border-box;
        }

        .form-wrapper input,
        .form-wrapper select,
        .form-wrapper button {
            width: 100%;
            padding: 0.5rem;
            margin-bottom: 0.75rem;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        
        @media (max-width: 480px) {
            .form-wrapper {
                padding: 1rem 0.75rem;
                border-radius: 0;
            }

            h3, h4 {
                font-size: 1.1rem;
            }

            input, button, select {
                font-size: 1rem;
            }
        }
        
        @media (max-width: 480px) {
            .form-wrapper input[type="submit"] {
                width: 100%;
            }
        }
        
        .form-wrapper input[type="submit"],
        .form-wrapper button {
            cursor: pointer;
            background-color: #28a745;
            color: white;
            border: none;
            transition: background 0.3s ease;
        }

        .form-wrapper input[type="submit"]:hover {
            background-color: #218838;
        }

        .form-wrapper input[type="submit"]:hover,
        .form-wrapper button:hover {
            background-color: #218838;
        }
        
        .form-wrapper h3,
        .form-wrapper h4 {
            margin-top: 0;
            margin-bottom: 1rem;
        }
        
        .input-container {
            position: relative;
            display: inline-block;
            margin: 10px;
        }

        .alert {
            display: none;
            position: absolute;
            top: -35px;
            left: 0;
            background-color: #333;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            white-space: nowrap;
            z-index: 1000;
        }

        .alert::after {
            content: '';
            position: absolute;
            top: 100%;
            left: 20px;
            border: 5px solid transparent;
            border-top-color: #333;
        }

        .input-container:hover .alert {
            display: block;
        }

        input[type="date"] {
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 1rem;
            border-radius: 4px;
            border-left: 4px solid #28a745;
            margin: 1rem 0;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 4px;
            border-left: 4px solid #dc3545;
            margin: 1rem 0;
        }
        
        .warning-message {
            background: #fff3cd;
            color: #856404;
            padding: 1rem;
            border-radius: 4px;
            border-left: 4px solid #ffc107;
            margin: 1rem 0;
        }
        
        .rate-info {
            background: #eaf7ff;
            padding: 0.5rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }
    </style>
</head>

<body>
    <div class="page-container">
        <!-- Current Exchange Rate Display -->
        <div class="rate-info">
            📌 Current Exchange Rate: <strong><?= htmlspecialchars($currentRate) ?> LBP/USD</strong>
        </div>

        <!-- Add Car Form -->
        <form method="POST" class="form-wrapper">
            <?php csrf_input_field(); ?>
            <h3 style="margin-top:0;">Add a New Car</h3>
            
            <input type="text" name="chis_nmbr" placeholder="Chassis Number" required />
            
            <select name="brand" required>
                <option value="">Select Brand</option>
                <?php foreach ($brands as $brand): ?>
                    <option value="<?= htmlspecialchars($brand) ?>"><?= htmlspecialchars($brand) ?></option>
                <?php endforeach; ?>
            </select>
            
            <input type="text" name="model" placeholder="Model" />
            
            <input type="number" name="price_selling_dol" placeholder="Selling Price($)" step="0.01" />
            
            <input type="number" name="price_buying_doll" placeholder="Purchase Price ($)" step="0.01" />
            
            <input type="number" name="in_cost" placeholder="Internal Costs" step="0.01" />
            
            <input type="number" name="ex_cost" placeholder="External Costs" step="0.01" />
            
            <input type="text" name="color" placeholder="Color" required />
            
            <input type="date" name="buying_date" data-placeholder="Purchase Date" />
            <div class="alert">Purchase Date</div>

            <input type="date" name="selling_date" data-placeholder="Date of Selling" />
            <div class="alert">Date of Selling</div>

            <select name="source" required>
                <option value="">Select Source</option>
                <?php foreach ($sources as $source): ?>
                    <option value="<?= htmlspecialchars($source) ?>"><?= htmlspecialchars($source) ?></option>
                <?php endforeach; ?>
            </select>
            
            <select name="status" required>
                <option value="">Select Status</option>
                <?php foreach ($statuses as $status): ?>
                    <option value="<?= htmlspecialchars($status) ?>"><?= htmlspecialchars($status) ?></option>
                <?php endforeach; ?>
            </select>
            
            <input type="number" name="mileage" placeholder="Mileage" required min="0" />
            
            <input type="text" name="maintenance" placeholder="Maintenance Notes" required />
            
            <input type="number" name="jamarik" placeholder="Customs Duties ($)" step="0.01" />
            
            <input type="hidden" name="force_submit" id="force_submit" value="0" />
            
            <input type="submit" name="add_car" value="Add Car" />
        </form>

        <!-- Update Exchange Rate Form -->
        <form method="POST" class="form-wrapper">
            <?php csrf_input_field(); ?>
            <h4>Update Exchange Rate</h4>
            <input type="number" name="rate" step="0.01" min="1" required placeholder="New Exchange Rate (LBP/USD)" />
            <input type="submit" name="update_rate" value="Update Rate" />
        </form>
        
        <script>
            window.addEventListener('load', function () {
                const warning = document.querySelector('#profit-warning');
                if (warning) {
                    alert(warning.textContent);
                }
            });
        </script>

        <?php
        // Handle form submissions
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            try {
                // Verify CSRF token
                if (!verify_csrf_token()) {
                    throw new Exception("CSRF token mismatch. Please refresh the page and try again.");
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
                    
                    // Update current rate for display
                    $currentRate = $new_rate;
                    echo "<script>setTimeout(function() { window.location.href = window.location.pathname; }, 2000);</script>";
                }

                // Handle car addition
                if (isset($_POST['add_car'])) {
                    // Get current exchange rate
                    $rateStmt = $pdo->query("SELECT rate FROM exchange_rates ORDER BY rate_date DESC, id DESC LIMIT 1");
                    $rateData = $rateStmt->fetch();
                    $rate = $rateData['rate'] ?? null;

                    if (!$rate || $rate <= 0) {
                        throw new Exception("No valid exchange rate set. Please update it first.");
                    }

                    // Sanitize inputs
                    $chis_nmbr = trim($_POST['chis_nmbr']);
                    $brand = trim($_POST['brand']);
                    $model = trim($_POST['model']) ?: '';
                    $color = trim($_POST['color']) ?: 'Not specified';
                    $source = trim($_POST['source']) ?: 'Not specified';
                    $status = trim($_POST['status']) ?: 'Available';
                    $maintenance = trim($_POST['maintenance']) ?: 'No maintenance notes';
                    $buying_date = trim($_POST['buying_date']) ?: null;
                    $selling_date = trim($_POST['selling_date']) ?: null;

                    // Validate required fields
                    if (empty($chis_nmbr)) {
                        throw new Exception("Chassis number is required.");
                    }
                    if (empty($brand)) {
                        throw new Exception("Brand is required.");
                    }

                    // Handle numeric fields
                    $price_selling_dol = floatval($_POST['price_selling_dol'] ?? 0);
                    $price_buying_doll = floatval($_POST['price_buying_doll'] ?? 0);
                    $in_cost = floatval($_POST['in_cost'] ?? 0);
                    $ex_cost = floatval($_POST['ex_cost'] ?? 0);
                    $mileage = intval($_POST['mileage'] ?? 0);
                    $jamarik = floatval($_POST['jamarik'] ?? 0);

                    // Calculate Lebanese Pound equivalents
                    $price_selling_leb = round($price_selling_dol * $rate);
                    $price_leb_buying = round($price_buying_doll * $rate);
                    $jamarik_leb = round($jamarik * $rate);
                    $in_costLeb = round($in_cost * $rate);
                    $ex_costLeb = round($ex_cost * $rate);

                    // Calculate profit
                    $total_cost = $price_buying_doll + $in_cost + $ex_cost + $jamarik;
                    $profit = $price_selling_dol - $total_cost;
                    $profit_leb = round($profit * $rate);
                    $total_cost_leb = round($total_cost * $rate);

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
                            $mileage, $status, $source, $maintenance
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
                            $chis_nmbr, $price_buying_doll, $price_selling_leb,
                            $price_leb_buying, $price_selling_dol, 
                            $buying_date, $selling_date,
                            $in_cost, $ex_cost, $in_costLeb, $ex_costLeb,
                            $jamarik, $jamarik_leb, $profit, $profit_leb,
                            $total_cost, $total_cost_leb
                        ]);

                        // Commit transaction
                        $pdo->commit();
                        
                        echo "<div class='success-message'>✅ Car and financial data added successfully!<br>
                              <strong>Chassis:</strong> " . htmlspecialchars($chis_nmbr) . "<br>
                              <strong>Brand:</strong> " . htmlspecialchars($brand) . "<br>
                              <strong>Profit:</strong> $" . number_format($profit, 2) . " / " . number_format($profit_leb, 0) . " LBP</div>";
                        
                        // Auto redirect after success
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
                error_log("Form processing error: " . $e->getMessage());
            }
        }
        ?>
    </div>

    <script>
        document.querySelectorAll('input[type="date"]').forEach(input => {
            const container = document.createElement('div');
            container.className = 'input-container';
            
            const alert = document.createElement('div');
            alert.className = 'alert';
            alert.textContent = input.getAttribute('data-placeholder');
            
            input.parentNode.insertBefore(container, input);
            container.appendChild(input);
            container.appendChild(alert);
        });

        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form[method="POST"]');
            if (form && form.querySelector('input[name="add_car"]')) {
                form.addEventListener('submit', function(e) {
                    const chassis = form.querySelector('input[name="chis_nmbr"]').value.trim();
                    const brand = form.querySelector('select[name="brand"]').value;
                    const color = form.querySelector('input[name="color"]').value.trim();
                    const source = form.querySelector('select[name="source"]').value;
                    const status = form.querySelector('select[name="status"]').value;
                    const maintenance = form.querySelector('input[name="maintenance"]').value.trim();
                    
                    if (!chassis) {
                        e.preventDefault();
                        alert('Please enter a chassis number.');
                        return;
                    }
                    
                    if (!brand) {
                        e.preventDefault();
                        alert('Please select a brand.');
                        return;
                    }
                    
                    if (!color) {
                        e.preventDefault();
                        alert('Please enter a color.');
                        return;
                    }
                    
                    if (!source) {
                        e.preventDefault();
                        alert('Please select a source.');
                        return;
                    }
                    
                    if (!status) {
                        e.preventDefault();
                        alert('Please select a status.');
                        return;
                    }
                    
                    if (!maintenance) {
                        e.preventDefault();
                        alert('Please enter maintenance notes.');
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
        });
    </script>
</body>
</html>