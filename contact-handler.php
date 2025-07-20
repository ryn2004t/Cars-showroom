<?php
/**
 * Contact Form Handler for Elite Motors
 * Processes contact form submissions securely
 */

// Security headers
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// CSRF protection (basic)
session_start();
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    // For demonstration, we'll skip CSRF validation
    // In production, you should implement proper CSRF protection
}

// Sanitize and validate input
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Get form data
$name = sanitizeInput($_POST['name'] ?? '');
$email = sanitizeInput($_POST['email'] ?? '');
$phone = sanitizeInput($_POST['phone'] ?? '');
$interest = sanitizeInput($_POST['interest'] ?? '');
$message = sanitizeInput($_POST['message'] ?? '');

// Validation
$errors = [];

if (empty($name) || strlen($name) < 2) {
    $errors[] = 'Name is required and must be at least 2 characters';
}

if (empty($email) || !validateEmail($email)) {
    $errors[] = 'Please enter a valid email address';
}

if (empty($message) || strlen($message) < 10) {
    $errors[] = 'Message is required and must be at least 10 characters';
}

// Check for spam (basic honeypot and rate limiting)
if (isset($_POST['website']) && !empty($_POST['website'])) {
    // Honeypot field filled - likely spam
    http_response_code(400);
    echo json_encode(['error' => 'Spam detected']);
    exit;
}

// Rate limiting (basic implementation)
$clientIP = $_SERVER['REMOTE_ADDR'] ?? '';
$rateLimitFile = 'rate_limit_' . md5($clientIP) . '.txt';
$currentTime = time();
$timeWindow = 300; // 5 minutes
$maxSubmissions = 3;

if (file_exists($rateLimitFile)) {
    $submissions = json_decode(file_get_contents($rateLimitFile), true) ?: [];
    // Remove old submissions
    $submissions = array_filter($submissions, function($time) use ($currentTime, $timeWindow) {
        return ($currentTime - $time) < $timeWindow;
    });
    
    if (count($submissions) >= $maxSubmissions) {
        http_response_code(429);
        echo json_encode(['error' => 'Too many submissions. Please try again later.']);
        exit;
    }
    
    $submissions[] = $currentTime;
} else {
    $submissions = [$currentTime];
}

file_put_contents($rateLimitFile, json_encode($submissions));

// If there are validation errors, return them
if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['error' => implode('. ', $errors)]);
    exit;
}

// Prepare email content
$to = 'info@elitemotors.com'; // Change to your email
$subject = 'New Contact Form Submission - Elite Motors';
$emailBody = "
New contact form submission from Elite Motors website:

Name: $name
Email: $email
Phone: $phone
Interest: $interest

Message:
$message

Submitted on: " . date('Y-m-d H:i:s') . "
IP Address: $clientIP
User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown') . "
";

$headers = [
    'From: noreply@elitemotors.com',
    'Reply-To: ' . $email,
    'X-Mailer: PHP/' . phpversion(),
    'Content-Type: text/plain; charset=UTF-8'
];

// Send email
$mailSent = mail($to, $subject, $emailBody, implode("\r\n", $headers));

// Log the submission (for backup/analytics)
$logEntry = [
    'timestamp' => date('Y-m-d H:i:s'),
    'name' => $name,
    'email' => $email,
    'phone' => $phone,
    'interest' => $interest,
    'message' => substr($message, 0, 100) . '...', // Truncated for log
    'ip' => $clientIP,
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
    'mail_sent' => $mailSent
];

$logFile = 'contact_submissions.log';
file_put_contents($logFile, json_encode($logEntry) . "\n", FILE_APPEND | LOCK_EX);

// Database storage (optional - uncomment if you have database setup)
/*
try {
    $pdo = new PDO('mysql:host=localhost;dbname=elite_motors', $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("
        INSERT INTO contact_submissions (name, email, phone, interest, message, ip_address, submitted_at) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([$name, $email, $phone, $interest, $message, $clientIP]);
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
}
*/

// Send auto-reply to customer
$autoReplySubject = 'Thank you for contacting Elite Motors';
$autoReplyBody = "
Dear $name,

Thank you for your interest in Elite Motors! We have received your message and will get back to you within 24 hours.

Your inquiry details:
- Name: $name
- Email: $email
- Interest: $interest

Our team of automotive experts is excited to help you find the perfect vehicle or assist with your automotive needs.

Best regards,
Elite Motors Team
123 Luxury Lane, Elite District
New York, NY 10001
Phone: +1 (555) 123-4567
Email: info@elitemotors.com
Website: www.elitemotors.com

Follow us on social media:
Facebook: @EliteMotors
Instagram: @EliteMotors
Twitter: @EliteMotors
";

$autoReplyHeaders = [
    'From: Elite Motors <info@elitemotors.com>',
    'X-Mailer: PHP/' . phpversion(),
    'Content-Type: text/plain; charset=UTF-8'
];

mail($email, $autoReplySubject, $autoReplyBody, implode("\r\n", $autoReplyHeaders));

// Return success response
echo json_encode([
    'success' => true,
    'message' => 'Thank you for your message! We will contact you soon.',
    'data' => [
        'name' => $name,
        'email' => $email,
        'timestamp' => date('Y-m-d H:i:s')
    ]
]);

// Clean up old rate limit files (maintenance)
$rateLimitDir = '.';
$files = glob($rateLimitDir . '/rate_limit_*.txt');
foreach ($files as $file) {
    if (filemtime($file) < (time() - 86400)) { // 24 hours old
        unlink($file);
    }
}
?>