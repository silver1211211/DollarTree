<?php
/**
 * submit_review.php
 * AJAX endpoint — logged-in user submits a private review.
 * Accepts: display_name, rating, review_text
 * Returns JSON. visibility = 'private' (only visible to submitting user).
 */
ob_start();

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');
ob_clean();

function rv_json($arr) { echo json_encode($arr); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST')
    rv_json(['success'=>false, 'error'=>'Invalid request method.']);

if (empty($_SESSION['user_id']))
    rv_json(['success'=>false, 'error'=>'You must be logged in to post a review.']);

$user_id = (int)$_SESSION['user_id'];

/* Display name — user-provided title/name */
$display_name = trim($_POST['display_name'] ?? '');
if (mb_strlen($display_name) < 2)
    rv_json(['success'=>false, 'error'=>'Please enter your name or title (at least 2 characters).']);
if (mb_strlen($display_name) > 60)
    rv_json(['success'=>false, 'error'=>'Name cannot exceed 60 characters.']);
$display_name = htmlspecialchars($display_name, ENT_QUOTES, 'UTF-8');

/* Rating */
$rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
if ($rating < 1 || $rating > 5)
    rv_json(['success'=>false, 'error'=>'Please select a valid star rating (1–5).']);

/* Review text */
$text = trim($_POST['review_text'] ?? '');
if (mb_strlen($text) < 10)
    rv_json(['success'=>false, 'error'=>'Review must be at least 10 characters.']);
if (mb_strlen($text) > 1000)
    rv_json(['success'=>false, 'error'=>'Review cannot exceed 1000 characters.']);
$text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

global $pdo;

try {
    $stmt = $pdo->prepare("
        INSERT INTO reviews
            (user_id, username, review_text, rating, reviews_count, verified, visibility, created_at)
        VALUES
            (?, ?, ?, ?, 1, 1, 'private', NOW())
    ");
    $stmt->execute([$user_id, $display_name, $text, $rating]);
} catch (PDOException $e) {
    rv_json(['success'=>false, 'error'=>'Database error. Please try again.']);
}

rv_json(['success'=>true, 'message'=>'Review added successfully.']);