<?php
require_once 'config/database.php';

$type = $_GET['type'] ?? '';

if ($type === 'restaurant') {
    $stmt = $pdo->prepare("SELECT DISTINCT city FROM restaurants WHERE status = 'approved' ORDER BY city");
} elseif ($type === 'hotel') {
    $stmt = $pdo->prepare("SELECT DISTINCT city FROM hotels WHERE status = 'approved' ORDER BY city");
} else {
    echo json_encode([]);
    exit;
}

$stmt->execute();
$cities = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($cities);
?>