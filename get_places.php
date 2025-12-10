<?php
require_once 'config/database.php';

$type = $_GET['type'] ?? '';
$city = $_GET['city'] ?? '';

if (!$type || !$city) {
    echo json_encode([]);
    exit;
}

if ($type === 'restaurant') {
    $stmt = $pdo->prepare("SELECT id, name, cuisine_type, city FROM restaurants WHERE city = ? AND status = 'approved' ORDER BY name");
} elseif ($type === 'hotel') {
    $stmt = $pdo->prepare("SELECT id, name, star_rating, city FROM hotels WHERE city = ? AND status = 'approved' ORDER BY name");
} else {
    echo json_encode([]);
    exit;
}

$stmt->execute([$city]);
$places = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($places);
?>