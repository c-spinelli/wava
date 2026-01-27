<?php
require_once __DIR__ . '/../app/config/db.php';

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email inválido';
    }

    if ($password === '') {
        $errors[] = 'La contraseña es obligatoria';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare(
            "SELECT id, password_hash FROM users WHERE email = :email"
        );
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            session_start();
            $_SESSION['user_id'] = $user['id'];

            header('Location: dashboard.php');
            exit;
        } else {
            $errors[] = 'Credenciales incorrectas';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Login – Wava</title>
</head>

<body>

    <h1>Login</h1>

    <?php if ($errors): ?>
        <ul style="color:red;">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="POST">
        <label>
            Email:<br>
            <input type="email" name="email" value="<?= htmlspecialchars($email) ?>">
        </label><br><br>

        <label>
            Contraseña:<br>
            <input type="password" name="password">
        </label><br><br>

        <button type="submit">Ingresar</button>
    </form>

    <p>
        ¿No tenés cuenta? <a href="register.php">Registrarse</a>
    </p>

</body>

</html>