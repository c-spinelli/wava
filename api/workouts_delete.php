<?php
require_once __DIR__ . '/../app/lib/auth.php';
require_once __DIR__ . '/../app/config/db.php';

requireAuth();

header('Content-Type: application/json');

$userId = (int)($_SESSION['user_id'] ?? 0);
$workoutId = (int)($_POST['workout_id'] ?? 0);

if ($userId <= 0 || $workoutId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos inválidos']);
    exit;
}

// 1) Encontrar el day_log_id del workout SOLO si pertenece al usuario logueado
$stmt = $pdo->prepare("
    SELECT w.day_log_id
    FROM workouts w
    INNER JOIN day_logs d ON d.id = w.day_log_id
    WHERE w.id = :workout_id AND d.user_id = :user_id
");
$stmt->execute([
    'workout_id' => $workoutId,
    'user_id' => $userId
]);

$dayLogId = (int)($stmt->fetchColumn() ?? 0);

if ($dayLogId <= 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Workout no encontrado']);
    exit;
}

// 2) Eliminar
$del = $pdo->prepare("DELETE FROM workouts WHERE id = :id AND day_log_id = :day_log_id");
$del->execute([
    'id' => $workoutId,
    'day_log_id' => $dayLogId
]);

// 3) Recalcular total del día para actualizar KPI sin reload
$sum = $pdo->prepare("
    SELECT COALESCE(SUM(minutes), 0) AS total_minutes
    FROM workouts
    WHERE day_log_id = :day_log_id
");
$sum->execute(['day_log_id' => $dayLogId]);
$totalExerciseMinutes = (int)$sum->fetchColumn();

echo json_encode([
    'ok' => true,
    'deleted_id' => $workoutId,
    'day_log_id' => $dayLogId,
    'total_exercise_minutes' => $totalExerciseMinutes
]);
