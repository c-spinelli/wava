<?php
require_once __DIR__ . '/../app/lib/auth.php';
require_once __DIR__ . '/../app/config/db.php';

requireAuth();

$userId = $_SESSION['user_id'];
$errors = [];
$success = false;

// Fecha seleccionada (por defecto hoy)
$selectedDate = $_GET['date'] ?? date('Y-m-d');

// Traer objetivos del usuario (para el siguiente paso: tarjetas)
$stmtUser = $pdo->prepare("
    SELECT goal_water_ml, goal_protein_g, goal_exercise_minutes, goal_sleep_hours
    FROM users WHERE id = :id
");
$stmtUser->execute(['id' => $userId]);
$userGoals = $stmtUser->fetch();
if (!$userGoals) {
    die('Usuario no encontrado');
}

// Buscar day_log del día
$stmtLog = $pdo->prepare("
    SELECT * FROM day_logs
    WHERE user_id = :user_id AND log_date = :log_date
");
$stmtLog->execute(['user_id' => $userId, 'log_date' => $selectedDate]);
$dayLog = $stmtLog->fetch();

// Si no existe, crearlo vacío (esto simplifica el flujo)
if (!$dayLog) {
    $create = $pdo->prepare("
        INSERT INTO day_logs (user_id, log_date, water_ml, protein_g)
        VALUES (:user_id, :log_date, 0, 0)
    ");
    $create->execute(['user_id' => $userId, 'log_date' => $selectedDate]);

    $stmtLog->execute(['user_id' => $userId, 'log_date' => $selectedDate]);
    $dayLog = $stmtLog->fetch();
}

// Guardar cambios del día
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $water = (int)($_POST['water_ml'] ?? 0);
    $protein = (int)($_POST['protein_g'] ?? 0);
    $sleep = $_POST['sleep_hours'] !== '' ? (float)$_POST['sleep_hours'] : null;
    $energy = $_POST['energy_level'] !== '' ? (int)$_POST['energy_level'] : null;
    $notes = trim($_POST['notes'] ?? '');

    if ($water < 0 || $protein < 0) {
        $errors[] = 'Agua y proteína no pueden ser negativas';
    }

    if ($energy !== null && ($energy < 1 || $energy > 10)) {
        $errors[] = 'Energía debe estar entre 1 y 10';
    }

    if (empty($errors)) {
        $update = $pdo->prepare("
            UPDATE day_logs SET
                water_ml = :water,
                protein_g = :protein,
                sleep_hours = :sleep,
                energy_level = :energy,
                notes = :notes
            WHERE id = :id
        ");

        $update->execute([
            'water' => $water,
            'protein' => $protein,
            'sleep' => $sleep,
            'energy' => $energy,
            'notes' => $notes,
            'id' => $dayLog['id']
        ]);

        $success = true;

        // Recargar el log actualizado
        $stmtLog->execute(['user_id' => $userId, 'log_date' => $selectedDate]);
        $dayLog = $stmtLog->fetch();
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Dashboard – Wava</title>
</head>

<body>

    <h1>Dashboard</h1>

    <p>
        <a href="profile.php">Perfil</a> |
        <a href="history.php">Historial</a> |
        <a href="logout.php">Salir</a>
    </p>

    <h2>Día: <?= htmlspecialchars($selectedDate) ?></h2>

    <form method="GET">
        <label>Seleccionar fecha:
            <input type="date" name="date" value="<?= htmlspecialchars($selectedDate) ?>">
        </label>
        <button type="submit">Ir</button>
    </form>

    <?php if ($success): ?>
        <p style="color:green;">Día actualizado</p>
    <?php endif; ?>

    <?php if ($errors): ?>
        <ul style="color:red;">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <h3>Registro del día</h3>

    <form method="POST">
        <label>Agua (ml):
            <input type="number" name="water_ml" value="<?= (int)$dayLog['water_ml'] ?>">
        </label><br><br>

        <label>Proteína (g):
            <input type="number" name="protein_g" value="<?= (int)$dayLog['protein_g'] ?>">
        </label><br><br>

        <label>Sueño (horas):
            <input type="number" step="0.1" name="sleep_hours" value="<?= htmlspecialchars($dayLog['sleep_hours']) ?>">
        </label><br><br>

        <label>Energía (1–10):
            <input type="number" name="energy_level" value="<?= htmlspecialchars($dayLog['energy_level']) ?>">
        </label><br><br>

        <label>Notas:<br>
            <textarea name="notes" rows="4" cols="40"><?= htmlspecialchars($dayLog['notes']) ?></textarea>
        </label><br><br>

        <button type="submit">Guardar día</button>
    </form>

    <p style="margin-top:20px; color:#666;">
        (Próximo paso: tarjetas de progreso + ejercicios múltiples + fetch)
    </p>

</body>

</html>