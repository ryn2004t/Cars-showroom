<?php
/**
 * CSRF Debug Page
 * Use this to test CSRF token functionality
 */

session_start();
require_once 'csrf.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSRF Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .debug-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 8px; }
        .token { background: #f0f0f0; padding: 10px; font-family: monospace; word-break: break-all; }
        .success { color: green; }
        .error { color: red; }
        .form-group { margin: 10px 0; }
        button { padding: 10px 20px; background: #007cba; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #005a87; }
    </style>
</head>
<body>
    <h1>CSRF Token Debug Page</h1>
    
    <div class="debug-section">
        <h2>Current Session Information</h2>
        <p><strong>Session ID:</strong> <?= session_id() ?></p>
        <p><strong>Session Status:</strong> <?= session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive' ?></p>
        <p><strong>Current CSRF Token:</strong></p>
        <div class="token"><?= generate_csrf_token() ?></div>
    </div>
    
    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
        <div class="debug-section">
            <h2>POST Request Results</h2>
            <p><strong>Session Token:</strong> <?= $_SESSION['csrf_token'] ?? 'NOT SET' ?></p>
            <p><strong>POST Token:</strong> <?= $_POST['csrf_token'] ?? 'NOT SET' ?></p>
            <p><strong>Tokens Match:</strong> 
                <?php if (verify_csrf_token()): ?>
                    <span class="success">✓ YES</span>
                <?php else: ?>
                    <span class="error">✗ NO</span>
                <?php endif; ?>
            </p>
        </div>
    <?php endif; ?>
    
    <div class="debug-section">
        <h2>Test Form</h2>
        <form method="POST">
            <?php csrf_input_field(); ?>
            <div class="form-group">
                <button type="submit">Test CSRF Token</button>
            </div>
        </form>
    </div>
    
    <div class="debug-section">
        <h2>Actions</h2>
        <a href="?action=refresh" onclick="return confirm('This will refresh your CSRF token. Continue?')">
            <button type="button">Refresh Token</button>
        </a>
        <a href="?action=destroy" onclick="return confirm('This will destroy your session. Continue?')">
            <button type="button">Destroy Session</button>
        </a>
    </div>
    
    <?php
    // Handle actions
    if (isset($_GET['action'])) {
        switch ($_GET['action']) {
            case 'refresh':
                refresh_csrf_token();
                echo '<script>alert("Token refreshed!"); window.location.href = "csrf_debug.php";</script>';
                break;
            case 'destroy':
                session_destroy();
                echo '<script>alert("Session destroyed!"); window.location.href = "csrf_debug.php";</script>';
                break;
        }
    }
    ?>
    
    <div class="debug-section">
        <h2>Instructions</h2>
        <ol>
            <li>Use the "Test CSRF Token" button to verify that CSRF tokens are working</li>
            <li>If tokens don't match, try refreshing the token</li>
            <li>If still not working, try destroying the session and starting fresh</li>
            <li>Once this page works, your main dashboard should work too</li>
        </ol>
    </div>
    
    <p><a href="dashboard.php">← Back to Dashboard</a></p>
</body>
</html>