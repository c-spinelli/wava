<?php
require_once __DIR__ . '/../app/lib/auth.php';
require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/Models/Workout.php';

requireAuth();
header('Content-Type: application/json');

$userId = (int)($_SESSION['user_id'] ?? 0);
$workoutId = (int)($_POST['workout_id'] ?? 0);

if ($userId <= 0 || $workoutId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos inválidos']);
    exit;
}

try {
    $workoutModel = new Workout($pdo);
    $result = $workoutModel->deleteForUser($workoutId, $userId);
    echo json_encode($result);
} catch (RuntimeException $e) {
    http_response_code(404);
    echo json_encode(['error' => $e->getMessage()]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al eliminar workout']);
}
