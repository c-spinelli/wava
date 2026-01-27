<?php
require_once __DIR__ . '/../app/lib/auth.php';
require_once __DIR__ . '/../app/config/db.php';

requireAuth();

$userId = $_SESSION['user_id'];
$errors = [];
$success = false;

// Traer datos actuales
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute(['id' => $userId]);
$user = $stmt->fetch();

if (!$user) {
    die('Usuario no encontrado');
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $age = $_POST['age'] !== '' ? (int)$_POST['age'] : null;
    $height = $_POST['height_cm'] !== '' ? (int)$_POST['height_cm'] : null;
    $weight = $_POST['weight_kg'] !== '' ? (float)$_POST['weight_kg'] : null;

    $goal_water = (int)$_POST['goal_water_ml'];
    $goal_protein = (int)$_POST['goal_protein_g'];
    $goal_exercise = (int)$_POST['goal_exercise_minutes'];
    $goal_sleep = (float)$_POST['goal_sleep_hours'];

    if ($goal_water <= 0 || $goal_protein <= 0 || $goal_exercise <= 0 || $goal_sleep <= 0) {
        $errors[] = 'Todos los objetivos deben ser mayores a 0';
    }

    if (empty($errors)) {
        $update = $pdo->prepare("
            UPDATE users SET
                age = :age,
                height_cm = :height,
                weight_kg = :weight,
                goal_water_ml = :goal_water,
                goal_protein_g = :goal_protein,
                goal_exercise_minutes = :goal_exercise,
                goal_sleep_hours = :goal_sleep
            WHERE id = :id
        ");

        $update->execute([
            'age' => $age,
            'height' => $height,
            'weight' => $weight,
            'goal_water' => $goal_water,
            'goal_protein' => $goal_protein,
            'goal_exercise' => $goal_exercise,
            'goal_sleep' => $goal_sleep,
            'id' => $userId
        ]);

        $success = true;

        // Recargar datos
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch();
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Perfil – Wava</title>
</head>

<body>

    <h1>Perfil y objetivos</h1>

    <?php if ($success): ?>
        <p style="color:green;">Perfil actualizado correctamente</p>
    <?php endif; ?>

    <?php if ($errors): ?>
        <ul style="color:red;">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="POST">

        <h3>Datos personales</h3>

        <label>Edad:
            <input type="number" name="age" value="<?= htmlspecialchars($user['age']) ?>">
        </label><br><br>

        <label>Altura (cm):
            <input type="number" name="height_cm" value="<?= htmlspecialchars($user['height_cm']) ?>">
        </label><br><br>

        <label>Peso (kg):
            <input type="number" step="0.1" name="weight_kg" value="<?= htmlspecialchars($user['weight_kg']) ?>">
        </label><br><br>

        <h3>Objetivos diarios</h3>

        <label>Agua (ml):
            <input type="number" name="goal_water_ml" value="<?= $user['goal_water_ml'] ?>">
        </label><br><br>

        <label>Proteína (g):
            <input type="number" name="goal_protein_g" value="<?= $user['goal_protein_g'] ?>">
        </label><br><br>

        <label>Ejercicio (min):
            <input type="number" name="goal_exercise_minutes" value="<?= $user['goal_exercise_minutes'] ?>">
        </label><br><br>

        <label>Sueño (horas):
            <input type="number" step="0.1" name="goal_sleep_hours" value="<?= $user['goal_sleep_hours'] ?>">
        </label><br><br>

        <button type="submit">Guardar cambios</button>
    </form>

    <p><a href="dashboard.php">Volver al dashboard</a></p>

</body>

</html>