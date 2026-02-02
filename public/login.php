<?php
require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/models/User.php';

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email inválido';
    if ($password === '') $errors[] = 'La contraseña es obligatoria';

    if (!$errors) {
        $userModel = new User($pdo);
        $user = $userModel->findByEmail($email);

        if ($user && $userModel->verifyPassword($user, $password)) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['user_id'] = (int)$user['id'];
            header('Location: dashboard.php');
            exit;
        }

        $errors[] = 'Credenciales incorrectas';
    }
}
?>

<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Iniciar sesión · Wava</title>

    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/layout.css">

    <link rel="stylesheet" href="../assets/css/styles.css">
</head>

<body>

    <header class="topbar">
        <div class="topbar-inner">
            <div class="brand">
                <div class="brand-mark">≋</div>
                <div class="brand-name">Wava</div>
            </div>
        </div>
    </header>

    <main class="auth">
        <div class="auth-inner">
            <section class="auth-left">
                <div class="auth-head">
                    <h1 class="auth-title">Bienvenido de vuelta</h1>
                    <p class="auth-subtitle">Continúa tu viaje hacia un estilo de vida más saludable</p>
                </div>

                <div class="auth-card">
                    <form class="auth-form" method="POST" action="login.php" novalidate>
                        <div class="field">
                            <label for="email">Email</label>
                            <input id="email" name="email" type="email" placeholder="tu@email.com" required>
                        </div>

                        <div class="field">
                            <label for="password">Contraseña</label>
                            <input id="password" name="password" type="password" placeholder="••••••••" required>
                        </div>

                        <button class="btn btn-primary btn-block" type="submit">Iniciar Sesión</button>

                        <p class="auth-bottom">
                            ¿No tenés cuenta?
                            <a href="register.php">Registrate gratis</a>
                        </p>
                    </form>
                </div>
            </section>

            <aside class="auth-right" aria-hidden="true">
                <img src="../assets/img/login.jpg" alt="">
            </aside>
        </div>
    </main>

</body>

</html>