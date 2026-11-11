<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database Configuration
if (!defined('DB_HOST')) define('DB_HOST', getenv('PGHOST') ?: '');
if (!defined('DB_NAME')) define('DB_NAME', getenv('PGDATABASE') ?: '');
if (!defined('DB_USER')) define('DB_USER', getenv('PGUSER') ?: '');
if (!defined('DB_PASS')) define('DB_PASS', getenv('PGPASSWORD') ?: '');

require_once __DIR__ . '/../includes/db.php';
// Authentication Check
function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }
}

// Sanitize Input
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Format Currency
function formatCurrency($amount) {
    return '₱' . number_format($amount, 2);
}

// Format Date
function formatDate($date) {
    return date('M j, Y', strtotime($date));
}

// Calculate Time Ago
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff/60) . ' minutes ago';
    if ($diff < 86400) return floor($diff/3600) . ' hours ago';
    if ($diff < 604800) return floor($diff/86400) . ' days ago';
    if ($diff < 2592000) return floor($diff/604800) . ' weeks ago';
    if ($diff < 31536000) return floor($diff/2592000) . ' months ago';
    return floor($diff/31536000) . ' years ago';
}

// Get Status Badge
function getStatusBadge($status, $payment_method = null) {
    $badges = [
        'completed' => 'badge-success',
        'verified' => 'badge-success',
        'active' => 'badge-primary',
        'pending' => 'payment_method' === 'GCash' ? 'badge-warning' : 'badge-secondary',
        'cancelled' => 'badge-error'
    ];
    return $badges[$status] ?? 'badge-secondary';
}

// Get Status Text
function getStatusText($status, $payment_method = null) {
    $texts = [
        'completed' => 'Completed',
        'verified' => 'Verified',
        'active' => 'Active',
        'pending' => $payment_method === 'GCash' ? 'Awaiting GCash Proof' : 'Pending',
        'cancelled' => 'Cancelled'
    ];
    return $texts[$status] ?? ucfirst($status);
}
?>