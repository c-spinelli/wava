<?php
require_once __DIR__ . '/../app/lib/auth.php';
require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/Models/DayLog.php';


requireAuth();

$userId = (int)($_SESSION['user_id'] ?? 0);
if ($userId <= 0) {
    header("Location: login.php");
    exit;
}

date_default_timezone_set('Europe/Madrid');

// Month selector: ?ym=YYYY-MM (default current month)
$ym = $_GET['ym'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $ym)) {
    $ym = date('Y-m');
}

$monthStart = new DateTimeImmutable($ym . '-01');
$selectedDate = $_GET['date'] ?? null;
if ($selectedDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
    $selectedDate = null;
}

$monthEnd   = $monthStart->modify('last day of this month');

$todayDate = date('Y-m-d');

$selectedDate = $_GET['date'] ?? null;
if ($selectedDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
    $selectedDate = null;
}
if ($selectedDate && $selectedDate > $todayDate) {
    $selectedDate = null; // bloquea futuro
}

$success = false;
$errors = [];
$dayLog = null;
$workouts = [];
$totalExerciseMinutes = 0;

if ($selectedDate) {
    $dayLogModel = new DayLog($pdo);

    // Traer o crear day_log 
    $dayLog = $dayLogModel->findOrCreate($userId, $selectedDate);

    // guardar cambios del día (POST)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_day') {
        $water = (int)($_POST['water_ml'] ?? 0);
        $protein = (int)($_POST['protein_g'] ?? 0);
        $sleep = ($_POST['sleep_hours'] ?? '') !== '' ? (float)$_POST['sleep_hours'] : null;
        $energy = ($_POST['energy_level'] ?? '') !== '' ? (int)$_POST['energy_level'] : null;
        $notes = trim($_POST['notes'] ?? '');

        if ($water < 0 || $protein < 0) $errors[] = 'Agua y proteína no pueden ser negativas';
        if ($energy !== null && ($energy < 1 || $energy > 10)) $errors[] = 'Energía debe estar entre 1 y 10';

        if (!$errors) {
            $dayLogModel->update((int)$dayLog['id'], [
                'water_ml' => $water,
                'protein_g' => $protein,
                'sleep_hours' => $sleep,
                'energy_level' => $energy,
                'notes' => $notes
            ]);

            $success = true;

            // recargar 
            $dayLog = $dayLogModel->findById((int)$dayLog['id']) ?? $dayLog;
        }
    }

    // workouts del día 
    $stmtWorkouts = $pdo->prepare("
        SELECT id, workout_type, minutes, notes
        FROM workouts
        WHERE day_log_id = :day_log_id
        ORDER BY id DESC
    ");
    $stmtWorkouts->execute(['day_log_id' => (int)$dayLog['id']]);
    $workouts = $stmtWorkouts->fetchAll(PDO::FETCH_ASSOC);

    // total minutos (se queda igual)
    $sum = $pdo->prepare("SELECT COALESCE(SUM(minutes),0) FROM workouts WHERE day_log_id = :id");
    $sum->execute(['id' => (int)$dayLog['id']]);
    $totalExerciseMinutes = (int)$sum->fetchColumn();
}

$prevYm = $monthStart->modify('-1 month')->format('Y-m');
$nextYm = $monthStart->modify('+1 month')->format('Y-m');

$fmt = new IntlDateFormatter(
    'es_ES',
    IntlDateFormatter::LONG,
    IntlDateFormatter::NONE,
    'Europe/Madrid',
    IntlDateFormatter::GREGORIAN,
    'MMMM yyyy'
);

$monthLabel = ucfirst($fmt->format($monthStart));


// Fetch user goals (fallbacks just in case)
$goalsStmt = $pdo->prepare("
  SELECT goal_water_ml, goal_protein_g, goal_exercise_minutes, goal_sleep_hours
  FROM users
  WHERE id = :uid
  LIMIT 1
");
$goalsStmt->execute(['uid' => $userId]);
$userGoals = $goalsStmt->fetch(PDO::FETCH_ASSOC) ?: [];


$goalWater    = (int)($userGoals['goal_water_ml'] ?? 2000);
$goalProtein  = (int)($userGoals['goal_protein_g'] ?? 100);
$goalExercise = (int)($userGoals['goal_exercise_minutes'] ?? 30);
$goalSleep    = (float)($userGoals['goal_sleep_hours'] ?? 8);

// Load logs for the month + total workout minutes per day_log
$stmt = $pdo->prepare("
  SELECT
    d.log_date,
    COALESCE(d.water_ml, 0)   AS water_ml,
    COALESCE(d.protein_g, 0)  AS protein_g,
    COALESCE(d.sleep_hours, 0) AS sleep_hours,
    COALESCE(w.total_minutes, 0) AS exercise_minutes
  FROM day_logs d
  LEFT JOIN (
    SELECT day_log_id, COALESCE(SUM(minutes), 0) AS total_minutes
    FROM workouts
    GROUP BY day_log_id
  ) w ON w.day_log_id = d.id
  WHERE d.user_id = :uid
    AND d.log_date BETWEEN :start AND :end
");
$stmt->execute([
    'uid'   => $userId,
    'start' => $monthStart->format('Y-m-d'),
    'end'   => $monthEnd->format('Y-m-d'),
]);

$byDate = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $byDate[$row['log_date']] = $row;
}

// Build calendar grid (Mon-Sun), 6 rows
// Find Monday of the week containing the 1st of month
$firstDow = (int)$monthStart->format('N'); // 1=Mon..7=Sun
$gridStart = $monthStart->modify('-' . ($firstDow - 1) . ' days'); // Monday
$gridDays = [];
for ($i = 0; $i < 42; $i++) {
    $gridDays[] = $gridStart->modify("+$i days");
}

function pct($value, $goal)
{
    if ($goal <= 0) return 0;
    return (int)floor(($value / $goal) * 100);
}

function dayClass($row, $goals)
{
    // returns one of: day--none, day--water, day--protein, day--exercise, day--sleep, day--perfect
    if (!$row) return 'day--none';

    $waterOk   = $row['water_ml'] >= $goals['water'];
    $proteinOk = $row['protein_g'] >= $goals['protein'];
    $exOk      = $row['exercise_minutes'] >= $goals['exercise'];
    $sleepOk   = $row['sleep_hours'] >= $goals['sleep'];

    if ($waterOk && $proteinOk && $exOk && $sleepOk) return 'day--perfect';

    // prioridad visual (podés cambiarla fácil)
    if ($waterOk)   return 'day--water';
    if ($proteinOk) return 'day--protein';
    if ($exOk)      return 'day--exercise';
    if ($sleepOk)   return 'day--sleep';

    // tuvo registro pero no cumplió metas -> lo marcamos igual como "registered"
    return 'day--registered';
}

$goals = [
    'water' => $goalWater,
    'protein' => $goalProtein,
    'exercise' => $goalExercise,
    'sleep' => $goalSleep
];

$currentMonthStart = new DateTimeImmutable(date('Y-m-01'));
$canGoNext = ($monthStart < $currentMonthStart);

?>
<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Historial - Wava</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <script src="../assets/js/app.js" defer></script>
</head>

<body>
    <!-- Topbar -->
    <header class="topbar">
        <div class="topbar-inner">
            <div class="brand">
                <div class="brand-mark">≋</div>
                <div class="brand-name">Wava</div>
            </div>

            <nav class="nav">
                <a class="nav-link" href="dashboard.php">Dashboard</a>
                <a class="nav-link active" href="history.php">Historial</a>
                <a class="nav-link" href="profile.php">Perfil</a>
            </nav>

            <div class="topbar-actions">
                <div class="avatar" aria-label="Usuario"></div>
            </div>
        </div>
    </header>


    <main class="container history-page">
        <div class="history-head">
            <div>
                <h1 class="page-title">HISTORIAL</h1>
                <p class="page-subtitle">Revisá y editá tu progreso anterior</p>
            </div>

            <div class="month-nav">
                <a class="month-btn" href="history.php?ym=<?= htmlspecialchars($prevYm) ?>">‹</a>

                <div class="month-pill"><?= htmlspecialchars($monthLabel) ?></div>

                <?php if ($canGoNext): ?>
                    <a class="month-btn" href="history.php?ym=<?= htmlspecialchars($nextYm) ?>" aria-label="Mes siguiente">›</a>
                <?php else: ?>
                    <span class="month-btn" style="opacity:.35; pointer-events:none;">›</span>
                <?php endif; ?>
            </div>

        </div>

        <div class="history-grid">

            <div class="history-left">
                <section class="calendar card">
                    <div class="calendar-weekdays">
                        <div>LUN</div>
                        <div>MAR</div>
                        <div>MIÉ</div>
                        <div>JUE</div>
                        <div>VIE</div>
                        <div>SÁB</div>
                        <div>DOM</div>
                    </div>

                    <div class="calendar-cells">
                        <?php foreach ($gridDays as $d):
                            $dateStr = $d->format('Y-m-d');
                            $inMonth = ($d->format('Y-m') === $monthStart->format('Y-m'));
                            $row = $byDate[$dateStr] ?? null;

                            // disable: fuera del mes o en el futuro
                            $isFuture = ($dateStr > $todayDate);
                            $isDisabled = (!$inMonth) || $isFuture;

                            $href = $isDisabled
                                ? "#"
                                : ("history.php?ym=" . $monthStart->format('Y-m') . "&date=" . $dateStr);

                            $disabledAttrs = $isDisabled ? ' tabindex="-1" aria-disabled="true"' : '';
                            $disabledCls   = $isDisabled ? ' is-disabled' : '';

                            // nunca mostrar datos para días futuros
                            if ($isFuture) {
                                $row = null;
                            }

                            // si existe row pero todo es 0, tratamos como vacío
                            if ($row) {
                                $allZero =
                                    ((int)$row['water_ml'] === 0) &&
                                    ((int)$row['protein_g'] === 0) &&
                                    ((int)$row['exercise_minutes'] === 0) &&
                                    ((float)$row['sleep_hours'] === 0.0);

                                if ($allZero) $row = null;
                            }

                            $cls = dayClass($row, $goals);
                            $extra    = $inMonth ? '' : ' is-out';
                            $todayCls = ($dateStr === $todayDate) ? ' is-today' : '';

                            // Display snippets
                            $waterPct = $row ? pct((int)$row['water_ml'], $goalWater) : 0;
                            $exMin    = $row ? (int)$row['exercise_minutes'] : 0;
                            $protein  = $row ? (int)$row['protein_g'] : 0;
                            $sleep    = $row ? (float)$row['sleep_hours'] : 0.0;
                        ?>
                            <a class="day <?= $cls ?><?= $extra ?><?= $todayCls ?><?= $disabledCls ?>"
                                href="<?= htmlspecialchars($href) ?>" <?= $disabledAttrs ?>
                                data-date="<?= htmlspecialchars($dateStr) ?>">

                                <div class="day-num"><?= (int)$d->format('j') ?></div>

                                <?php if ($row): ?>
                                    <div class="day-metrics">
                                        <div class="m"><span class="i">💧</span><span><?= $waterPct ?>%</span></div>
                                        <?php if ($protein > 0): ?>
                                            <div class="m"><span class="i">🥗</span><span><?= $protein ?>g</span></div>
                                        <?php endif; ?>
                                        <?php if ($exMin > 0): ?>
                                            <div class="m"><span class="i">🏋️</span><span><?= $exMin ?>m</span></div>
                                        <?php endif; ?>
                                        <?php if ($sleep > 0): ?>
                                            <div class="m"><span class="i">🌙</span><span><?= rtrim(rtrim(number_format($sleep, 1), '0'), '.') ?>h</span></div>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <?php if ($dateStr === $todayDate): ?>
                                        <div class="day-empty">Hoy</div>
                                    <?php else: ?>
                                        <div class="day-empty">—</div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>

                <?php if ($selectedDate && $dayLog): ?>
                    <div class="card history-editor2" id="history-editor">
                        <div class="history-editor2__head">
                            <div>
                                <div class="history-editor2__title">Editar día</div>
                                <div class="history-editor2__date"><?= htmlspecialchars($selectedDate) ?></div>
                            </div>

                            <a class="history-editor2__close"
                                href="history.php?ym=<?= htmlspecialchars($monthStart->format('Y-m')) ?>"
                                aria-label="Cerrar">✕</a>
                        </div>

                        <?php if ($success): ?>
                            <div class="notice success" style="margin-top:10px;">Día actualizado</div>
                        <?php endif; ?>

                        <?php if ($errors): ?>
                            <div class="notice error" style="margin-top:10px;">
                                <?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <form class="form" method="POST" style="margin-top:12px;">
                            <input type="hidden" name="action" value="save_day">

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
                                        <input class="control" type="number" step="0.1" name="sleep_hours"
                                            value="<?= htmlspecialchars((string)($dayLog['sleep_hours'] ?? '')) ?>">
                                        <span class="trail purple">🌙</span>
                                    </div>
                                </label>

                                <label class="field">
                                    <span>Nivel de energía (1–10)</span>
                                    <div class="range">
                                        <span class="range-label">Bajo</span>
                                        <input class="range-input" type="range" name="energy_level" min="1" max="10"
                                            value="<?= (int)($dayLog['energy_level'] ?? 5) ?>"
                                            oninput="document.getElementById('energyValHistory').textContent=this.value">
                                        <span class="range-label">Alto</span>
                                    </div>
                                    <div class="range-value"><span id="energyValHistory"><?= (int)($dayLog['energy_level'] ?? 5) ?></span></div>
                                </label>
                            </div>

                            <label class="field full" style="margin-top:10px;">
                                <span>Notas del día</span>
                                <textarea class="control" name="notes" rows="3"
                                    placeholder="¿Cómo te sientes hoy?"><?= htmlspecialchars((string)($dayLog['notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                            </label>

                            <button class="primary" type="submit" style="margin-top:10px;">Guardar Cambios</button>
                        </form>

                        <div style="margin-top:14px;">
                            <div class="panel-head" style="padding:0; margin:0 0 8px;">
                                <h2 style="font-size:14px; margin:0;">Ejercicios</h2>
                                <button class="pill" type="button" id="toggleWorkoutForm">+ Añadir</button>
                            </div>

                            <form id="workout-form" class="workout-form is-collapsed">
                                <input type="hidden" name="day_log_id" value="<?= (int)$dayLog['id'] ?>">
                                <input type="hidden" id="selected-date" value="<?= htmlspecialchars($selectedDate) ?>">

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
                                <?php
                                $workoutIcons = [
                                    'running'  => '🏃',
                                    'strength' => '🏋️',
                                    'boxing' => '🥊',
                                    'yoga' => '🧘',
                                    'cycling' => '🚴',
                                    'walking' => '🚶'
                                ];
                                $workoutLabels = [
                                    'running'  => 'Running',
                                    'strength' => 'Strength',
                                    'boxing' => 'Boxing',
                                    'yoga' => 'Yoga',
                                    'cycling' => 'Cycling',
                                    'walking' => 'Walking'
                                ];
                                ?>
                                <?php foreach ($workouts as $w): ?>
                                    <?php
                                    $type = $w['workout_type'];
                                    $icon = $workoutIcons[$type] ?? '🎯';
                                    $title = $workoutLabels[$type] ?? ucfirst($type);
                                    ?>
                                    <div class="workout-card type-<?= htmlspecialchars($type) ?>">
                                        <div class="workout-icon"><?= $icon ?></div>
                                        <div class="workout-meta">
                                            <div class="workout-title"><?= htmlspecialchars($title) ?></div>
                                            <div class="workout-sub">
                                                <?= (int)$w['minutes'] ?> minutos<?= !empty($w['notes']) ? ' · ' . htmlspecialchars($w['notes']) : '' ?>
                                            </div>
                                        </div>
                                        <button class="trash" type="button" data-workout-id="<?= (int)$w['id'] ?>" aria-label="Eliminar">🗑️</button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <aside class="side">
                <div class="card legend">
                    <h3>Leyenda</h3>
                    <div class="legend-row"><span class="sw sw-water"></span><span>Hidratación completa</span></div>
                    <div class="legend-row"><span class="sw sw-protein"></span><span>Proteína óptima</span></div>
                    <div class="legend-row"><span class="sw sw-exercise"></span><span>Ejercicio destacado</span></div>
                    <div class="legend-row"><span class="sw sw-perfect"></span><span>Día perfecto</span></div>
                    <div class="legend-row"><span class="sw sw-none"></span><span>Sin registro</span></div>
                </div>
            </aside>

        </div>

    </main>
</body>

</html>