<?php
session_start();
require_once 'config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Login required']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$review_id = $input['review_id'] ?? 0;
$type = $input['type'] ?? '';
$user_id = $_SESSION['user_id'];

if (!$review_id || !in_array($type, ['like', 'dislike'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

try {
    // Check if user already interacted with this review
    $stmt = $pdo->prepare("SELECT interaction_type FROM review_interactions WHERE review_id = ? AND user_id = ?");
    $stmt->execute([$review_id, $user_id]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        if ($existing['interaction_type'] === $type) {
            // Remove interaction if clicking same type
            $stmt = $pdo->prepare("DELETE FROM review_interactions WHERE review_id = ? AND user_id = ?");
            $stmt->execute([$review_id, $user_id]);
        } else {
            // Update interaction type
            $stmt = $pdo->prepare("UPDATE review_interactions SET interaction_type = ? WHERE review_id = ? AND user_id = ?");
            $stmt->execute([$type, $review_id, $user_id]);
        }
    } else {
        // Insert new interaction
        $stmt = $pdo->prepare("INSERT INTO review_interactions (review_id, user_id, interaction_type) VALUES (?, ?, ?)");
        $stmt->execute([$review_id, $user_id, $type]);
    }
    
    // Update counts in reviews table
    $stmt = $pdo->prepare("
        UPDATE reviews SET 
        likes_count = (SELECT COUNT(*) FROM review_interactions WHERE review_id = ? AND interaction_type = 'like'),
        dislikes_count = (SELECT COUNT(*) FROM review_interactions WHERE review_id = ? AND interaction_type = 'dislike')
        WHERE id = ?
    ");
    $stmt->execute([$review_id, $review_id, $review_id]);
    
    // Get updated counts
    $stmt = $pdo->prepare("SELECT likes_count, dislikes_count FROM reviews WHERE id = ?");
    $stmt->execute([$review_id]);
    $counts = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'likes' => $counts['likes_count'],
        'dislikes' => $counts['dislikes_count']
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>