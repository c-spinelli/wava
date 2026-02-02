<?php

class User
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    public function create(string $name, string $email, string $plainPassword): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO users (name, email, password_hash)
             VALUES (:name, :email, :password_hash)"
        );

        $stmt->execute([
            'name' => $name,
            'email' => $email,
            'password_hash' => password_hash($plainPassword, PASSWORD_DEFAULT),
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function verifyPassword(array $userRow, string $plainPassword): bool
    {
        $hash = $userRow['password_hash'] ?? '';
        return $hash !== '' && password_verify($plainPassword, $hash);
    }

    public function updateProfileAndGoals(int $userId, array $data): void
    {
        $allowed = [
            'name',
            'age',
            'height_cm',
            'weight_kg',
            'lifestyle',
            'goal_water_ml',
            'goal_protein_g',
            'goal_exercise_minutes',
            'goal_sleep_hours'
        ];

        $set = [];
        $params = ['id' => $userId];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $set[] = "{$field} = :{$field}";
                $params[$field] = $data[$field];
            }
        }

        if (!$set) return;

        $sql = "UPDATE users SET " . implode(', ', $set) . " WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
    }
}
