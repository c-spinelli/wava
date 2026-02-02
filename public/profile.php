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

function h($v)
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function v_or_empty($v)
{
    return $v === null ? '' : (string)$v;
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $age = ($_POST['age'] ?? '') !== '' ? (int)$_POST['age'] : null;
    $height = ($_POST['height_cm'] ?? '') !== '' ? (int)$_POST['height_cm'] : null;
    $weight = ($_POST['weight_kg'] ?? '') !== '' ? (float)$_POST['weight_kg'] : null;

    $goal_water = (int)($_POST['goal_water_ml'] ?? 0);
    $goal_protein = (int)($_POST['goal_protein_g'] ?? 0);
    $goal_exercise = (int)($_POST['goal_exercise_minutes'] ?? 0);
    $goal_sleep = (float)($_POST['goal_sleep_hours'] ?? 0);

    if ($goal_water <= 0 || $goal_protein <= 0 || $goal_exercise <= 0 || $goal_sleep <= 0) {
        $errors[] = 'Todos los objetivos deben ser mayores a 0.';
    }

    if ($age !== null && $age <= 0) $errors[] = 'La edad debe ser mayor a 0.';
    if ($height !== null && $height <= 0) $errors[] = 'La altura debe ser mayor a 0.';
    if ($weight !== null && $weight <= 0) $errors[] = 'El peso debe ser mayor a 0.';

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

$createdAt = $user['created_at'] ?? null;
$memberSince = '';
if ($createdAt) {
    try {
        $dt = new DateTime($createdAt);
        $memberSince = $dt->format('M Y'); // "Jan 2026"
    } catch (Exception $e) {
        $memberSince = '';
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Perfil – Wava</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/layout.css">
    <link rel="stylesheet" href="../assets/css/components.css">

    <link rel="stylesheet" href="../assets/css/perfil.css">

    <link rel="stylesheet" href="../assets/css/styles.css">
    <script src="../assets/js/app.js" defer></script>
</head>

<body class="app page-profile">
    <header class="topbar">
        <div class="topbar-inner">
            <div class="brand">
                <div class="brand-mark">≋</div>
                <div class="brand-name">Wava</div>
            </div>

            <nav class="nav">
                <a class="nav-link" href="dashboard.php">Dashboard</a>
                <a class="nav-link" href="history.php">Historial</a>
                <a class="nav-link active" href="profile.php">Perfil</a>
            </nav>

            <div class="topbar-actions">
            </div>
        </div>
    </header>

    <main class="container">
        <div class="hero hero-split">
            <div>
                <h1>MI PERFIL</h1>
                <p class="subtitle">Configura tu información personal y objetivos diarios</p>
            </div>

            <div class="hero-actions">
                <button class="btn btn-ghost btn-sm" type="button" id="editToggleBtn">✏️ Editar</button>
                <button class="btn btn-primary btn-sm" type="button" id="saveBtn" disabled>Guardar cambios</button>
            </div>
        </div>


        <?php if ($success): ?>
            <div class="notice success">Perfil actualizado correctamente.</div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="notice error">
                <?php foreach ($errors as $e): ?>
                    <div><?= h($e) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <section class="profile-grid">
            <aside class="profile-side">
                <div class="panel profile-card">
                    <div class="profile-avatar" aria-hidden="true">
                        <span class="profile-avatar-emoji">👤</span>
                    </div>

                    <div class="profile-name"><?= h($user['name'] ?? 'Usuario') ?></div>
                    <div class="profile-email"><?= h($user['email'] ?? '') ?></div>

                    <div class="profile-info-card">
                        <div class="profile-info-icon">📅</div>
                        <div class="profile-info-text">
                            <div class="profile-info-label">Miembro desde</div>
                            <div class="profile-info-value"><?= h($memberSince ?: '—') ?></div>
                        </div>
                    </div>
                </div>

                <div class="panel">
                    <h2>Configuración</h2>
                    <div class="settings-list">
                        <button class="settings-item" type="button" disabled>
                            <span class="settings-ico">🔒</span>
                            <span>Cambiar contraseña</span>
                            <span class="settings-right">›</span>
                        </button>

                        <button class="settings-item" type="button" disabled>
                            <span class="settings-ico">🔔</span>
                            <span>Notificaciones</span>
                            <span class="settings-right">›</span>
                        </button>

                        <button class="settings-item" type="button" disabled>
                            <span class="settings-ico">🛡️</span>
                            <span>Privacidad</span>
                            <span class="settings-right">›</span>
                        </button>

                        <a class="settings-item danger" href="logout.php">
                            <span class="settings-ico">↩</span>
                            <span>Cerrar sesión</span>
                            <span class="settings-right">›</span>
                        </a>
                    </div>
                </div>
            </aside>

            <div class="profile-main">
                <form method="POST" class="profile-form">
                    <section class="panel">
                        <div class="panel-head">
                            <h2>Información Personal</h2>
                        </div>

                        <div class="form-grid">
                            <div class="field">
                                <label class="label">Nombre</label>
                                <input class="control" type="text" value="<?= h($user['name'] ?? '') ?>" disabled>
                            </div>

                            <div class="field">
                                <label class="label">Email</label>
                                <input class="control" type="email" value="<?= h($user['email'] ?? '') ?>" disabled>
                            </div>

                            <div class="field">
                                <label class="label">Edad</label>
                                <div class="control-wrap">
                                    <input class="control js-editable" type="number" name="age" min="1" max="120"
                                        value="<?= h(v_or_empty($user['age'])) ?>" disabled>
                                    <div class="suffix">años</div>
                                </div>
                            </div>

                            <div class="field">
                                <label class="label">Altura</label>
                                <div class="control-wrap">
                                    <input class="control js-editable" type="number" name="height_cm" min="50" max="250"
                                        value="<?= h(v_or_empty($user['height_cm'])) ?>" disabled>
                                    <div class="suffix">cm</div>
                                </div>
                            </div>

                            <div class="field">
                                <label class="label">Peso</label>
                                <div class="control-wrap">
                                    <input class="control js-editable" type="number" step="0.1" name="weight_kg" min="20" max="400"
                                        value="<?= h(v_or_empty($user['weight_kg'])) ?>" disabled>
                                    <div class="suffix">kg</div>
                                </div>
                            </div>
                        </div>
                    </section>


                    <section class="panel">
                        <div class="panel-head">
                            <h2>Objetivos Diarios</h2>
                        </div>

                        <div class="goals">
                            <div class="goal-card goal-water">
                                <div class="goal-top">
                                    <div class="goal-left">
                                        <div class="goal-icon">💧</div>
                                        <div>
                                            <div class="goal-title">Agua</div>
                                            <div class="goal-sub">Hidratación diaria</div>
                                        </div>
                                    </div>
                                    <div class="goal-right">
                                        <div class="goal-value" data-range-value-for="goal_water_ml"><?= (int)$user['goal_water_ml'] ?></div>
                                        <div class="goal-unit">ml/día</div>
                                    </div>
                                </div>

                                <input
                                    class="range-input js-editable"
                                    type="range"
                                    name="goal_water_ml"
                                    min="1000" max="5000" step="50"
                                    value="<?= (int)$user['goal_water_ml'] ?>"
                                    data-range-value-id="goal_water_ml"
                                    disabled>
                                <div class="goal-minmax">
                                    <span>1000 ml</span>
                                    <span>5000 ml</span>
                                </div>
                            </div>

                            <div class="goal-card goal-protein">
                                <div class="goal-top">
                                    <div class="goal-left">
                                        <div class="goal-icon">🥗</div>
                                        <div>
                                            <div class="goal-title">Proteína</div>
                                            <div class="goal-sub">Ingesta diaria</div>
                                        </div>
                                    </div>
                                    <div class="goal-right">
                                        <div class="goal-value" data-range-value-for="goal_protein_g"><?= (int)$user['goal_protein_g'] ?></div>
                                        <div class="goal-unit">g/día</div>
                                    </div>
                                </div>

                                <input
                                    class="range-input js-editable"
                                    type="range"
                                    name="goal_protein_g"
                                    min="50" max="300" step="5"
                                    value="<?= (int)$user['goal_protein_g'] ?>"
                                    data-range-value-id="goal_protein_g"
                                    disabled>
                                <div class="goal-minmax">
                                    <span>50 g</span>
                                    <span>300 g</span>
                                </div>
                            </div>

                            <div class="goal-card goal-exercise">
                                <div class="goal-top">
                                    <div class="goal-left">
                                        <div class="goal-icon">🏋️</div>
                                        <div>
                                            <div class="goal-title">Ejercicio</div>
                                            <div class="goal-sub">Actividad física</div>
                                        </div>
                                    </div>
                                    <div class="goal-right">
                                        <div class="goal-value" data-range-value-for="goal_exercise_minutes"><?= (int)$user['goal_exercise_minutes'] ?></div>
                                        <div class="goal-unit">min/día</div>
                                    </div>
                                </div>

                                <input
                                    class="range-input js-editable"
                                    type="range"
                                    name="goal_exercise_minutes"
                                    min="15" max="180" step="5"
                                    value="<?= (int)$user['goal_exercise_minutes'] ?>"
                                    data-range-value-id="goal_exercise_minutes"
                                    disabled>
                                <div class="goal-minmax">
                                    <span>15 min</span>
                                    <span>180 min</span>
                                </div>
                            </div>

                            <div class="goal-card goal-sleep">
                                <div class="goal-top">
                                    <div class="goal-left">
                                        <div class="goal-icon">🌙</div>
                                        <div>
                                            <div class="goal-title">Sueño</div>
                                            <div class="goal-sub">Descanso nocturno</div>
                                        </div>
                                    </div>
                                    <div class="goal-right">
                                        <div class="goal-value" data-range-value-for="goal_sleep_hours"><?= rtrim(rtrim(number_format((float)$user['goal_sleep_hours'], 1, '.', ''), '0'), '.') ?></div>
                                        <div class="goal-unit">hrs/día</div>
                                    </div>
                                </div>

                                <input
                                    class="range-input js-editable"
                                    type="range"
                                    name="goal_sleep_hours"
                                    min="4" max="12" step="0.5"
                                    value="<?= (float)$user['goal_sleep_hours'] ?>"
                                    data-range-value-id="goal_sleep_hours"
                                    data-range-decimals="1"
                                    disabled>
                                <div class="goal-minmax">
                                    <span>4 hrs</span>
                                    <span>12 hrs</span>
                                </div>
                            </div>
                        </div>
                    </section>
                </form>
            </div>
        </section>
    </main>
</body>

</html>