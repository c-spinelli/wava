<?php
require_once __DIR__ . '/../app/lib/auth.php';
require_once __DIR__ . '/../app/config/db.php';

requireAuth();

$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("
  SELECT log_date
  FROM day_logs
  WHERE user_id = :user_id
  ORDER BY log_date DESC
");
$stmt->execute(['user_id' => $userId]);
$days = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Historial – Wava</title>
</head>

<body>

    <h1>Historial</h1>

    <p><a href="dashboard.php">Volver al dashboard</a></p>

    <?php if (empty($days)): ?>
        <p>No hay días registrados todavía.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($days as $d): ?>
                <li>
                    <a href="dashboard.php?date=<?= htmlspecialchars($d['log_date']) ?>">
                        <?= htmlspecialchars($d['log_date']) ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

</body>

</html>