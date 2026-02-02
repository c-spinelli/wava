<?php
require_once __DIR__ . '/../app/lib/auth.php';
require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/models/workout.php';

requireAuth();
header('Content-Type: application/json');

$dayLogId = (int)($_POST['day_log_id'] ?? 0);
$type = trim($_POST['workout_type'] ?? '');
$minutes = (int)($_POST['minutes'] ?? 0);
$notes = trim($_POST['workout_notes'] ?? '');

if ($dayLogId <= 0 || $type === '' || $minutes <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos inválidos']);
    exit;
}

try {
    $workoutModel = new Workout($pdo);
    $created = $workoutModel->create($dayLogId, $type, $minutes, $notes);
    echo json_encode($created);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al crear workout']);
}
