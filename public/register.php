<?php
require_once __DIR__ . '/../app/config/db.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Si ya está logueado, que no vea registro
if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$errors = [];
$name = $email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($name === '') {
        $errors[] = 'El nombre es obligatorio';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email inválido';
    }

    if (strlen($password) < 6) {
        $errors[] = 'La contraseña debe tener al menos 6 caracteres';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare(
            "INSERT INTO users (name, email, password_hash)
             VALUES (:name, :email, :password)"
        );

        try {
            $stmt->execute([
                'name' => $name,
                'email' => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT),
            ]);

            // AUTO LOGIN
            $_SESSION['user_id'] = (int)$pdo->lastInsertId();

            // Ir al dashboard directamente
            header('Location: dashboard.php');
            exit;
        } catch (PDOException $e) {
            // 23000 = constraint violation (ej: UNIQUE email)
            if ($e->getCode() == 23000) {
                $errors[] = 'El email ya está registrado';
            } else {
                $errors[] = 'Error al registrar usuario';
            }
        }
    }
}
?>
<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Registro · Wava</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>

<body>

    <!-- Header simple (primera pasada). Después lo unificamos en toda la app -->
    <header class="topbar">
        <div class="topbar-inner">
            <a class="brand" href="index.php" style="text-decoration:none; color:inherit;">
                <div class="brand-mark">≋</div>
                <div class="brand-name">Wava</div>
            </a>

            <div class="topbar-actions">
                <a class="btn btn-ghost" href="login.php">Iniciar sesión</a>
                <a class="btn btn-primary" href="register.php">Crear cuenta</a>
            </div>
        </div>
    </header>

    <main class="auth">
        <div class="auth-inner">
            <section class="auth-left">
                <div class="auth-head">
                    <h1 class="auth-title">Crear tu cuenta</h1>
                    <p class="auth-subtitle">Empezá a registrar tus hábitos en menos de 1 minuto.</p>
                </div>

                <div class="auth-card">

                    <?php if ($errors): ?>
                        <div class="auth-alert" role="alert">
                            <ul>
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form class="auth-form" method="POST" action="register.php" novalidate>
                        <div class="field">
                            <label for="name">Nombre</label>
                            <input id="name" name="name" type="text" placeholder="Tu nombre" value="<?= htmlspecialchars($name) ?>" required>
                        </div>

                        <div class="field">
                            <label for="email">Email</label>
                            <input id="email" name="email" type="email" placeholder="tu@email.com" value="<?= htmlspecialchars($email) ?>" required>
                        </div>

                        <div class="field">
                            <label for="password">Contraseña</label>
                            <input id="password" name="password" type="password" placeholder="Mínimo 6 caracteres" required>
                        </div>

                        <button class="btn btn-primary btn-block" type="submit">Registrarse</button>

                        <p class="auth-bottom">
                            ¿Ya tenés cuenta?
                            <a href="login.php">Iniciar sesión</a>
                        </p>
                    </form>
                </div>
            </section>

            <aside class="auth-right" aria-hidden="true">
                <img src="../assets/img/register.jpg" alt="">
            </aside>
        </div>
    </main>

</body>

</html>