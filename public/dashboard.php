<?php
require_once __DIR__ . '/../app/lib/auth.php';
require_once __DIR__ . '/../app/config/db.php';

requireAuth();

$userId = $_SESSION['user_id'];
$errors = [];
$success = false;

$selectedDate = $_GET['date'] ?? date('Y-m-d');
$today = date('Y-m-d');
if ($selectedDate > $today) {
    $selectedDate = $today;
}


$stmtUser = $pdo->prepare("
    SELECT goal_water_ml, goal_protein_g, goal_exercise_minutes, goal_sleep_hours
    FROM users WHERE id = :id
");
$stmtUser->execute(['id' => $userId]);
$userGoals = $stmtUser->fetch();
if (!$userGoals) {
    die('Usuario no encontrado');
}

$stmtLog = $pdo->prepare("
    SELECT * FROM day_logs
    WHERE user_id = :user_id AND log_date = :log_date
");
$stmtLog->execute(['user_id' => $userId, 'log_date' => $selectedDate]);
$dayLog = $stmtLog->fetch();

if (!$dayLog) {
    $create = $pdo->prepare("
    INSERT INTO day_logs (user_id, log_date, water_ml, protein_g, sleep_hours, energy_level, notes)
    VALUES (:user_id, :log_date, 0, 0, NULL, NULL, '')    
    ");
    $create->execute(['user_id' => $userId, 'log_date' => $selectedDate]);

    $stmtLog->execute(['user_id' => $userId, 'log_date' => $selectedDate]);
    $dayLog = $stmtLog->fetch();
}

$stmtWorkoutSum = $pdo->prepare("
    SELECT SUM(minutes) as total_minutes
    FROM workouts
    WHERE day_log_id = :day_log_id
");
$stmtWorkoutSum->execute(['day_log_id' => $dayLog['id']]);
$workoutSum = $stmtWorkoutSum->fetch();

$totalExerciseMinutes = (int)($workoutSum['total_minutes'] ?? 0);

$progressWater = progressPercent($dayLog['water_ml'], $userGoals['goal_water_ml']);
$progressProtein = progressPercent($dayLog['protein_g'], $userGoals['goal_protein_g']);
$progressExercise = progressPercent($totalExerciseMinutes, $userGoals['goal_exercise_minutes']);
$progressSleep = $dayLog['sleep_hours'] !== null
    ? progressPercent($dayLog['sleep_hours'], $userGoals['goal_sleep_hours'])
    : null;

$stmtWorkouts = $pdo->prepare("
    SELECT id, workout_type, minutes, notes
    FROM workouts
    WHERE day_log_id = :day_log_id
    ORDER BY id DESC
");
$stmtWorkouts->execute(['day_log_id' => $dayLog['id']]);
$workouts = $stmtWorkouts->fetchAll();

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
        UPDATE day_logs
        SET water_ml=:water_ml, protein_g=:protein_g, sleep_hours=:sleep_hours,
            energy_level=:energy_level, notes=:notes
        WHERE id=:id
        
        ");

        $update->execute([
            'water_ml' => $water,
            'protein_g' => $protein,
            'sleep_hours' => $sleep,
            'energy_level' => $energy,
            'notes' => $notes,
            'id' => $dayLog['id']
        ]);


        $success = true;

        $stmtLog->execute(['user_id' => $userId, 'log_date' => $selectedDate]);
        $dayLog = $stmtLog->fetch();
    }
}

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

$stmtWorkoutSum = $pdo->prepare("
  SELECT COALESCE(SUM(minutes),0) as total_minutes
  FROM workouts
  WHERE day_log_id = :day_log_id
");
$stmtWorkoutSum->execute(['day_log_id' => $dayLog['id']]);
$totalExerciseMinutes = (int)$stmtWorkoutSum->fetch()['total_minutes'];

function progressPercent($current, $goal)
{
    $goal = (float)$goal;
    if ($goal <= 0) return 0;
    return (int)round(((float)$current / $goal) * 100);
}

$progressWater   = progressPercent((int)$dayLog['water_ml'], (int)$userGoals['goal_water_ml']);
$progressProtein = progressPercent((int)$dayLog['protein_g'], (int)$userGoals['goal_protein_g']);
$progressExercise = progressPercent($totalExerciseMinutes, (int)$userGoals['goal_exercise_minutes']);

$progressSleep = ($dayLog['sleep_hours'] !== null)
    ? progressPercent((float)$dayLog['sleep_hours'], (int)$userGoals['goal_sleep_hours'])
    : null;

$notesSafe = htmlspecialchars((string)($dayLog['notes'] ?? ''), ENT_QUOTES, 'UTF-8');


?>



<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Dashboard – Wava</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/layout.css">
    <link rel="stylesheet" href="../assets/css/components.css">

    <link rel="stylesheet" href="../assets/css/dashboard.css">

    <link rel="stylesheet" href="../assets/css/styles.css">
    <script src="../assets/js/app.js" defer></script>
</head>

<body class="page-dashboard">
    <header class="topbar">
        <div class="topbar-inner">
            <div class="brand">
                <div class="brand-mark">≋</div>
                <div class="brand-name">Wava</div>
            </div>

            <nav class="nav">
                <a class="nav-link active" href="dashboard.php">Dashboard</a>
                <a class="nav-link" href="history.php">Historial</a>
                <a class="nav-link" href="profile.php">Perfil</a>
            </nav>

            <div class="topbar-actions">
            </div>
        </div>
    </header>

    <main class="container">
        <div class="hero">
            <div>
                <h1>TRACK YOUR DAY</h1>
                <p class="subtitle">Registra tus hábitos y alcanza tus objetivos</p>
            </div>

            <form class="date-nav" method="GET">
                <button class="date-btn" type="button" onclick="moveDate(-1)" aria-label="Día anterior">‹</button>
                <label class="date-pill">
                    <span class="cal">📅</span>
                    <input class="date-input" type="date" name="date" value="<?= htmlspecialchars($selectedDate) ?>" max="<?= date('Y-m-d') ?>">
                </label>
                <?php $isToday = ($selectedDate >= date('Y-m-d')); ?>
                <button class="date-btn" type="button"
                    onclick="moveDate(1)"
                    aria-label="Día siguiente"
                    <?= $isToday ? 'disabled' : '' ?>>›</button>

            </form>
        </div>

        <?php
        function ringPct($pct)
        {
            return max(0, min(100, (int)$pct));
        }
        $ringWater = ringPct($progressWater);
        $ringProtein = ringPct($progressProtein);
        $ringExercise = ringPct($progressExercise);
        $ringSleep = $progressSleep !== null ? ringPct($progressSleep) : 0;
        ?>

        <section class="kpis">
            <article class="kpi">
                <div class="kpi-head">
                    <div class="kpi-icon water">💧</div>
                    <div class="kpi-title">AGUA</div>
                </div>

                <div class="ring" style="--p: <?= $ringWater ?>; --c: var(--blue);">
                    <div class="ring-inner">
                        <div class="ring-main"><?= (int)$dayLog['water_ml'] ?></div>
                        <div class="ring-sub">/ <?= (int)$userGoals['goal_water_ml'] ?> ml</div>
                    </div>
                </div>

                <div class="kpi-pct blue"><?= (int)$progressWater ?>%</div>
            </article>

            <article class="kpi">
                <div class="kpi-head">
                    <div class="kpi-icon protein">🥗</div>
                    <div class="kpi-title">PROTEÍNA</div>
                </div>

                <div class="ring" style="--p: <?= $ringProtein ?>; --c: var(--green);">
                    <div class="ring-inner">
                        <div class="ring-main"><?= (int)$dayLog['protein_g'] ?></div>
                        <div class="ring-sub">/ <?= (int)$userGoals['goal_protein_g'] ?> g</div>
                    </div>
                </div>

                <div class="kpi-pct green"><?= (int)$progressProtein ?>%</div>
            </article>

            <article class="kpi">
                <div class="kpi-head">
                    <div class="kpi-icon exercise">🏋️</div>
                    <div class="kpi-title">EJERCICIO</div>
                </div>

                <div
                    id="kpi-exercise-ring"
                    class="ring"
                    data-goal="<?= (int)$userGoals['goal_exercise_minutes'] ?>"
                    style="--p: <?= $ringExercise ?>; --c: var(--orange);">
                    <div class="ring-inner">
                        <div id="kpi-exercise-value" class="ring-main"><?= (int)$totalExerciseMinutes ?></div>
                        <div class="ring-sub">/ <?= (int)$userGoals['goal_exercise_minutes'] ?> min</div>
                    </div>
                </div>

                <div id="kpi-exercise-percent" class="kpi-pct orange"><?= (int)$progressExercise ?>%</div>

            </article>

            <article class="kpi">
                <div class="kpi-head">
                    <div class="kpi-icon sleep">🌙</div>
                    <div class="kpi-title">SUEÑO</div>
                </div>

                <div class="ring" style="--p: <?= $ringSleep ?>; --c: var(--purple);">
                    <div class="ring-inner">
                        <div class="ring-main">
                            <?= $dayLog['sleep_hours'] !== null ? htmlspecialchars($dayLog['sleep_hours']) : '—' ?>
                        </div>
                        <div class="ring-sub">/ <?= (int)$userGoals['goal_sleep_hours'] ?> hrs</div>
                    </div>
                </div>

                <div class="kpi-pct purple">
                    <?= $progressSleep !== null ? ((int)$progressSleep . '%') : '—' ?>
                </div>
            </article>
        </section>

        <?php if ($success): ?>
            <div class="notice success">Día actualizado</div>
        <?php endif; ?>

        <?php if ($errors): ?>
            <div class="notice error">
                <?php foreach ($errors as $error): ?>
                    <div><?= htmlspecialchars($error) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <section class="layout">
            <article class="panel">
                <h2>Registro del Día</h2>

                <form class="form" method="POST">
                    <div class="form-grid">
                        <label class="field">
                            <span>Agua (ml)</span>
                            <div class="control-wrap">
                                <input class="control has-trail" type="number" name="water_ml" value="<?= (int)$dayLog['water_ml'] ?>">
                                <span class="trail blue">💧</span>
                            </div>
                        </label>

                        <label class="field">
                            <span>Proteína (g)</span>
                            <div class="control-wrap">
                                <input class="control has-trail" type="number" name="protein_g" value="<?= (int)$dayLog['protein_g'] ?>">
                                <span class="trail green">🥗</span>
                            </div>
                        </label>


                        <label class="field">
                            <span>Horas de sueño</span>
                            <div class="control-wrap">
                                <input class="control has-trail" type="number" step="0.1" name="sleep_hours" value="<?= htmlspecialchars((string)($dayLog['sleep_hours'] ?? '')) ?>">
                                <span class="trail purple">🌙</span>
                            </div>
                        </label>

                        <label class="field">
                            <span>Nivel de energía (1–10)</span>
                            <div class="range">
                                <span class="range-label">Bajo</span>
                                <input class="range-input" type="range" name="energy_level" min="1" max="10"
                                    value="<?= (int)($dayLog['energy_level'] ?? 5) ?>"
                                    oninput="document.getElementById('energyVal').textContent=this.value">
                                <span class="range-label">Alto</span>
                            </div>
                            <div class="range-value"> <span id="energyVal"><?= (int)($dayLog['energy_level'] ?? 5) ?></span> </div>
                        </label>

                    </div>

                    <label class="field full">
                        <span>Notas del día</span>
                        <textarea class="control" name="notes" rows="4" placeholder="¿Cómo te sientes hoy?"><?= $notesSafe ?></textarea>
                    </label>

                    <button class="primary" type="submit">Guardar Cambios</button>
                </form>
            </article>

            <aside class="side">
                <div class="tips">
                    <div class="tips-head">
                        <span class="tips-badge">💧</span>
                        <div>
                            <div class="tips-title">Tip del día</div>
                            <div class="tips-sub">Pequeños hábitos, gran diferencia</div>
                        </div>
                    </div>

                    <div class="tip-card tip-water">
                        <div class="tip-emoji">💧</div>
                        <div class="tip-body">
                            <div class="tip-label">Hidratación</div>
                            <div class="tip-text">Beber agua antes de cada comida puede ayudarte a alcanzar tu objetivo diario más fácilmente.</div>
                        </div>
                    </div>

                    <div class="tip-card tip-sleep">
                        <div class="tip-emoji">🌙</div>
                        <div class="tip-body">
                            <div class="tip-label">Descanso</div>
                            <div class="tip-text">Dormir al menos 7–8 horas mejora la recuperación y el rendimiento.</div>
                        </div>
                    </div>
                </div>
            </aside>


        </section>

        <section class="panel">
            <div class="panel-head">
                <h2>Ejercicios de Hoy</h2>

                <button class="pill" type="button" id="toggleWorkoutForm"
                    aria-controls="workout-form" aria-expanded="false">
                    + Añadir Ejercicio
                </button>

            </div>

            <form id="workout-form" class="workout-form is-collapsed">

                <input type="hidden" name="day_log_id" value="<?= (int)$dayLog['id'] ?>">

                <div class="form-grid">
                    <label class="field">
                        <span>Tipo</span>
                        <select class="control" name="workout_type" required>
                            <option value="">-- seleccionar --</option>
                            <option value="running">Running</option>
                            <option value="strength">Fuerza</option>
                            <option value="yoga">Yoga</option>
                            <option value="cycling">Spinning</option>
                            <option value="boxing">Boxing</option>
                            <option value="walking">Caminata</option>
                        </select>
                    </label>

                    <label class="field">
                        <span>Minutos</span>
                        <input class="control" type="number" name="minutes" min="1" required>
                    </label>
                </div>

                <label class="field">
                    <span>Nota (opcional)</span>
                    <input class="control" type="text" name="workout_notes" placeholder="Ej: sprints">
                </label>

                <div class="actions">
                    <button class="primary" type="submit">Añadir</button>
                </div>
            </form>

            <div class="workout-list" id="workout-list">
                <?php foreach ($workouts as $w): ?>

                    <?php
                    $workoutIcons = [
                        'running'  => '🏃',
                        'strength' => '🏋️',
                        'boxing'   => '🥊',
                        'yoga'     => '🧘',
                        'cycling'  => '🚴',
                        'walking'  => '🚶'
                    ];

                    $workoutLabels = [
                        'running'  => 'Running',
                        'strength' => 'Strength',
                        'boxing'   => 'Boxing',
                        'yoga'     => 'Yoga',
                        'cycling'  => 'Cycling',
                        'walking'  => 'Walking'
                    ];

                    $type  = $w['workout_type'];
                    $icon  = $workoutIcons[$type] ?? '🎯';
                    $title = $workoutLabels[$type] ?? ucfirst($type);
                    ?>

                    <div class="workout-card type-<?= htmlspecialchars($type) ?>">
                        <div class="workout-icon"><?= $icon ?></div>

                        <div class="workout-meta">
                            <div class="workout-title"><?= htmlspecialchars($title) ?></div>
                            <div class="workout-sub">
                                <?= (int)$w['minutes'] ?> minutos
                                <?= !empty($w['notes']) ? ' · ' . htmlspecialchars($w['notes']) : '' ?>
                            </div>
                        </div>

                        <button class="trash" type="button" data-workout-id="<?= (int)$w['id'] ?>" aria-label="Eliminar">🗑️</button>

                    </div>

                <?php endforeach; ?>
            </div>

        </section>

    </main>

    <script>
        function moveDate(delta) {
            const input = document.querySelector('input[name="date"]');
            if (!input || !input.value) return;

            const today = new Date();
            today.setHours(0, 0, 0, 0);

            const d = new Date(input.value + "T00:00:00");
            d.setDate(d.getDate() + delta);

            if (d > today) return;

            const yyyy = d.getFullYear();
            const mm = String(d.getMonth() + 1).padStart(2, '0');
            const dd = String(d.getDate()).padStart(2, '0');

            input.value = `${yyyy}-${mm}-${dd}`;


            const form = input.closest('form');
            if (form) form.submit();
        }
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const btn = document.getElementById('toggleWorkoutForm');
            const wf = document.getElementById('workout-form');
            const list = document.getElementById('workout-list');
            if (!btn || !wf) return;

            // ------- helpers -------
            const closeForm = () => {
                wf.classList.add('is-collapsed');
                wf.style.display = 'none';
                btn.textContent = '+ Añadir Ejercicio';
                btn.classList.remove('is-danger');
                btn.setAttribute('aria-expanded', 'false');
            };

            const openForm = () => {
                wf.classList.remove('is-collapsed');
                wf.style.display = 'block';
                btn.textContent = '✕ Cerrar';
                btn.classList.add('is-danger');
                btn.setAttribute('aria-expanded', 'true');
            };

            const toggleForm = () => {
                const isOpen = !wf.classList.contains('is-collapsed');
                isOpen ? closeForm() : openForm();
            };

            closeForm();

            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                toggleForm();
            }, true);

            let pendingAutoClose = false;

            wf.addEventListener('submit', () => {
                pendingAutoClose = true;
            }, true);

            if (list) {
                const obs = new MutationObserver((mutations) => {
                    if (!pendingAutoClose) return;

                    const changed = mutations.some(m => m.type === 'childList' || m.type === 'subtree');
                    if (!changed) return;

                    pendingAutoClose = false;

                    wf.reset();

                    closeForm();
                });

                obs.observe(list, {
                    childList: true,
                    subtree: true
                });
            }
        });
    </script>


</body>

</html>