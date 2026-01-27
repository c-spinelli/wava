<?php
require_once __DIR__ . '/../app/config/db.php';

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
                'password' => password_hash($password, PASSWORD_DEFAULT)
            ]);

            header('Location: login.php');
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $errors[] = 'El email ya está registrado';
            } else {
                $errors[] = 'Error al registrar usuario';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Registro – Wava</title>
</head>

<body>

    <h1>Registro</h1>

    <?php if ($errors): ?>
        <ul style="color:red;">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="POST">
        <label>
            Nombre:<br>
            <input type="text" name="name" value="<?= htmlspecialchars($name) ?>">
        </label><br><br>

        <label>
            Email:<br>
            <input type="email" name="email" value="<?= htmlspecialchars($email) ?>">
        </label><br><br>

        <label>
            Contraseña:<br>
            <input type="password" name="password">
        </label><br><br>

        <button type="submit">Registrarse</button>
    </form>

    <p>
        ¿Ya tenés cuenta? <a href="login.php">Iniciar sesión</a>
    </p>

</body>

</html>