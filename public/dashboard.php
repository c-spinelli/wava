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
// Total minutos de ejercicio del día
$stmtWorkoutSum = $pdo->prepare("
    SELECT SUM(minutes) as total_minutes
    FROM workouts
    WHERE day_log_id = :day_log_id
");
$stmtWorkoutSum->execute(['day_log_id' => $dayLog['id']]);
$workoutSum = $stmtWorkoutSum->fetch();

$totalExerciseMinutes = (int)($workoutSum['total_minutes'] ?? 0);

function progressPercent($current, $goal)
{
    if ($goal <= 0) return 0;
    return round(($current / $goal) * 100);
}


$progressWater = progressPercent($dayLog['water_ml'], $userGoals['goal_water_ml']);
$progressProtein = progressPercent($dayLog['protein_g'], $userGoals['goal_protein_g']);
$progressExercise = progressPercent($totalExerciseMinutes, $userGoals['goal_exercise_minutes']);
$progressSleep = $dayLog['sleep_hours'] !== null
    ? progressPercent($dayLog['sleep_hours'], $userGoals['goal_sleep_hours'])
    : null;



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

// Listar workouts del día
$stmtWorkouts = $pdo->prepare("
    SELECT id, workout_type, minutes, notes
    FROM workouts
    WHERE day_log_id = :day_log_id
    ORDER BY id DESC
");
$stmtWorkouts->execute(['day_log_id' => $dayLog['id']]);
$workouts = $stmtWorkouts->fetchAll();

// Añadir workout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_workout') {
    $type = trim($_POST['workout_type'] ?? '');
    $minutes = (int)($_POST['minutes'] ?? 0);
    $notesW = trim($_POST['workout_notes'] ?? '');

    if ($type === '') $errors[] = 'Tipo de ejercicio es obligatorio';
    if ($minutes <= 0) $errors[] = 'Minutos debe ser mayor a 0';

    if (empty($errors)) {
        $ins = $pdo->prepare("
            INSERT INTO workouts (day_log_id, workout_type, minutes, notes)
            VALUES (:day_log_id, :type, :minutes, :notes)
        ");
        $ins->execute([
            'day_log_id' => $dayLog['id'],
            'type' => $type,
            'minutes' => $minutes,
            'notes' => $notesW
        ]);

        // Redirect para evitar reenvío del form
        header('Location: dashboard.php?date=' . urlencode($selectedDate));
        exit;
    }
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

// Eliminar workout
if (isset($_GET['delete_workout_id'])) {
    $deleteId = (int)$_GET['delete_workout_id'];

    $del = $pdo->prepare("
        DELETE FROM workouts
        WHERE id = :id AND day_log_id = :day_log_id
    ");
    $del->execute([
        'id' => $deleteId,
        'day_log_id' => $dayLog['id']
    ]);

    header('Location: dashboard.php?date=' . urlencode($selectedDate));
    exit;
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
    <h2>Progreso del día</h2>

    <ul>
        <li>
            Agua: <?= $dayLog['water_ml'] ?> / <?= $userGoals['goal_water_ml'] ?> ml
            (<?= $progressWater ?>%)
        </li>
        <li>
            Proteína: <?= $dayLog['protein_g'] ?> / <?= $userGoals['goal_protein_g'] ?> g
            (<?= $progressProtein ?>%)
        </li>
        <li>
            Ejercicio: <?= $totalExerciseMinutes ?> / <?= $userGoals['goal_exercise_minutes'] ?> min
            (<?= $progressExercise ?>%)
        </li>
        <li>
            Sueño:
            <?php if ($progressSleep !== null): ?>
                <?= $dayLog['sleep_hours'] ?> / <?= $userGoals['goal_sleep_hours'] ?> h
                (<?= $progressSleep ?>%)
            <?php else: ?>
                no registrado
            <?php endif; ?>
        </li>
    </ul>


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

    <h3>Ejercicios del día</h3>

    <form method="POST">
        <input type="hidden" name="action" value="add_workout">

        <label>Tipo:
            <select name="workout_type">
                <option value="">-- seleccionar --</option>
                <option value="running">Running</option>
                <option value="strength">Fuerza</option>
                <option value="yoga">Yoga</option>
                <option value="cycling">Spinning</option>
                <option value="boxing">Boxing</option>
                <option value="walking">Caminata</option>
            </select>
        </label>

        <label>Minutos:
            <input type="number" name="minutes" min="1">
        </label>

        <label>Nota:
            <input type="text" name="workout_notes">
        </label>

        <button type="submit">Añadir ejercicio</button>
    </form>

    <?php if (empty($workouts)): ?>
        <p>No hay ejercicios registrados para este día.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($workouts as $w): ?>
                <li>
                    <?= htmlspecialchars($w['workout_type']) ?> —
                    <?= (int)$w['minutes'] ?> min
                    <?php if (!empty($w['notes'])): ?>
                        (<?= htmlspecialchars($w['notes']) ?>)
                    <?php endif; ?>
                    <a href="dashboard.php?date=<?= urlencode($selectedDate) ?>&delete_workout_id=<?= (int)$w['id'] ?>"
                        onclick="return confirm('¿Eliminar este ejercicio?');">
                        Eliminar
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>


</body>

</html>