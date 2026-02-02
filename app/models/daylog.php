<?php

class DayLog
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findOrCreate(int $userId, string $dateYmd): int
    {
        // Buscar
        $stmt = $this->pdo->prepare("
            SELECT id FROM day_logs
            WHERE user_id = :user_id AND log_date = :log_date
            LIMIT 1
        ");
        $stmt->execute(['user_id' => $userId, 'log_date' => $dateYmd]);
        $id = (int)($stmt->fetchColumn() ?? 0);

        if ($id > 0) return $id;

        // Crear
        $ins = $this->pdo->prepare("
            INSERT INTO day_logs (user_id, log_date)
            VALUES (:user_id, :log_date)
        ");
        $ins->execute(['user_id' => $userId, 'log_date' => $dateYmd]);

        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $dayLogId, array $data): void
    {
        $allowed = ['water_ml', 'protein_g', 'sleep_hours', 'energy_level', 'notes'];
        $set = [];
        $params = ['id' => $dayLogId];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $set[] = "{$field} = :{$field}";
                $params[$field] = $data[$field];
            }
        }

        if (!$set) return;

        $sql = "UPDATE day_logs SET " . implode(', ', $set) . " WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
    }
}
