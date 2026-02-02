<?php
require_once __DIR__ . '/../app/lib/auth.php';
require_once __DIR__ . '/../app/config/db.php';

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

// Insertar workout
$stmt = $pdo->prepare("
    INSERT INTO workouts (day_log_id, workout_type, minutes, notes)
    VALUES (:day_log_id, :type, :minutes, :notes)
");
$stmt->execute([
    'day_log_id' => $dayLogId,
    'type' => $type,
    'minutes' => $minutes,
    'notes' => $notes
]);

$workoutId = $pdo->lastInsertId();

// Calcular total de minutos de ejercicio del día (para actualizar KPI sin recargar)
$sumStmt = $pdo->prepare("
    SELECT COALESCE(SUM(minutes), 0) AS total_minutes
    FROM workouts
    WHERE day_log_id = :day_log_id
");
$sumStmt->execute(['day_log_id' => $dayLogId]);
$totalExerciseMinutes = (int)$sumStmt->fetchColumn();

// Devolver el workout creado
echo json_encode([
    'id' => (int)$workoutId,
    'workout_type' => $type,
    'minutes' => $minutes,
    'notes' => $notes,
    'total_exercise_minutes' => $totalExerciseMinutes
]);
