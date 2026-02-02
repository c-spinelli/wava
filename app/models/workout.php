<?php

class Workout
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(int $dayLogId, string $type, int $minutes, string $notes = ''): array
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO workouts (day_log_id, workout_type, minutes, notes)
            VALUES (:day_log_id, :type, :minutes, :notes)
        ");

        $stmt->execute([
            'day_log_id' => $dayLogId,
            'type' => $type,
            'minutes' => $minutes,
            'notes' => $notes
        ]);

        $workoutId = (int)$this->pdo->lastInsertId();
        $total = $this->getTotalMinutesForDayLog($dayLogId);

        return [
            'id' => $workoutId,
            'workout_type' => $type,
            'minutes' => $minutes,
            'notes' => $notes,
            'total_exercise_minutes' => $total
        ];
    }

    public function deleteForUser(int $workoutId, int $userId): array
    {
        // Obtener el day_log_id solo si pertenece al usuario
        $stmt = $this->pdo->prepare("
            SELECT w.day_log_id
            FROM workouts w
            INNER JOIN day_logs d ON d.id = w.day_log_id
            WHERE w.id = :workout_id AND d.user_id = :user_id
            LIMIT 1
        ");

        $stmt->execute([
            'workout_id' => $workoutId,
            'user_id' => $userId
        ]);

        $dayLogId = (int)($stmt->fetchColumn() ?? 0);

        if ($dayLogId <= 0) {
            throw new RuntimeException('Workout no encontrado');
        }

        $del = $this->pdo->prepare("DELETE FROM workouts WHERE id = :id AND day_log_id = :day_log_id");
        $del->execute([
            'id' => $workoutId,
            'day_log_id' => $dayLogId
        ]);

        $total = $this->getTotalMinutesForDayLog($dayLogId);

        return [
            'ok' => true,
            'deleted_id' => $workoutId,
            'day_log_id' => $dayLogId,
            'total_exercise_minutes' => $total
        ];
    }

    public function getTotalMinutesForDayLog(int $dayLogId): int
    {
        $sum = $this->pdo->prepare("
            SELECT COALESCE(SUM(minutes), 0)
            FROM workouts
            WHERE day_log_id = :day_log_id
        ");

        $sum->execute(['day_log_id' => $dayLogId]);
        return (int)$sum->fetchColumn();
    }
}
