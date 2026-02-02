<?php

class DayLog
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findByUserAndDate(int $userId, string $dateYmd): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT *
            FROM day_logs
            WHERE user_id = :user_id AND log_date = :log_date
            LIMIT 1
        ");
        $stmt->execute([
            'user_id' => $userId,
            'log_date' => $dateYmd
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM day_logs WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function findOrCreate(int $userId, string $dateYmd): array
    {
        $existing = $this->findByUserAndDate($userId, $dateYmd);
        if ($existing) return $existing;

        // crea vacío
        $ins = $this->pdo->prepare("
            INSERT INTO day_logs (user_id, log_date, water_ml, protein_g, sleep_hours, energy_level, notes)
            VALUES (:user_id, :log_date, 0, 0, NULL, NULL, '')
        ");
        $ins->execute([
            'user_id' => $userId,
            'log_date' => $dateYmd
        ]);

        $id = (int)$this->pdo->lastInsertId();
        return $this->findById($id) ?: [
            'id' => $id,
            'user_id' => $userId,
            'log_date' => $dateYmd,
            'water_ml' => 0,
            'protein_g' => 0,
            'sleep_hours' => null,
            'energy_level' => null,
            'notes' => ''
        ];
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
