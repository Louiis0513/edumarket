<?php
session_start();
require_once __DIR__ . "/includes/conexion.php";
require_once __DIR__ . "/includes/funciones.php";
if (usuarioLogueado()) { header("Location: index.php"); exit; }

$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = trim($_POST["nombre"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $telefono = trim($_POST["telefono"] ?? "");
    $pass = $_POST["password"] ?? "";
    $pass2 = $_POST["password2"] ?? "";

    if ($nombre === "" || $email === "" || $pass === "") {
        $error = "Completa los campos obligatorios.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Correo inválido.";
    } elseif (strlen($pass) < 6) {
        $error = "Mínimo 6 caracteres en la contraseña.";
    } elseif ($pass !== $pass2) {
        $error = "Las contraseñas no coinciden.";
    } else {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        try {
            $stmt = $mysqli->prepare("INSERT INTO usuario (email, nombre, password_hash, telefono) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $email, $nombre, $hash, $telefono);
            $stmt->execute();
            $_SESSION["usuario"] = [
                "id" => (int)$mysqli->insert_id,
                "nombre" => $nombre,
                "email" => $email,
                "telefono" => $telefono,
                "es_admin" => 0,
            ];
            guardarMensaje("Cuenta creada. ¡Ya puedes publicar!");
            header("Location: index.php");
            exit;
        } catch (mysqli_sql_exception $e) {
            $error = "Ese correo ya está registrado.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Registro | EduMarket</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,700;12..96,800&family=Figtree:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/estilos.css">
<link rel="icon" href="img/logo.png" type="image/png">
</head>
<body>
<div class="auth-wrap">
  <div class="auth-card">
    <img src="img/logo.png" alt="EduMarket">
    <h1>Crear cuenta</h1>
    <p class="muted" style="margin-bottom:1.1rem;">Únete al marketplace escolar.</p>
    <?php if ($error): ?><p class="form-error"><?= e($error) ?></p><?php endif; ?>
    <form class="auth-form" method="post">
      <div class="campo">
        <label for="nombre">Nombre</label>
        <input id="nombre" name="nombre" required value="<?= e($_POST['nombre'] ?? '') ?>">
      </div>
      <div class="campo">
        <label for="email">Correo</label>
        <input id="email" type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>">
      </div>
      <div class="campo">
        <label for="telefono">Teléfono WhatsApp</label>
        <input id="telefono" name="telefono" placeholder="3001234567" value="<?= e($_POST['telefono'] ?? '') ?>">
      </div>
      <div class="campo">
        <label for="password">Contraseña</label>
        <input id="password" type="password" name="password" required minlength="6">
      </div>
      <div class="campo">
        <label for="password2">Confirmar</label>
        <input id="password2" type="password" name="password2" required minlength="6">
      </div>
      <button class="btn btn-primary full" type="submit">Registrarme</button>
    </form>
    <p style="margin-top:1rem;"><a href="login.php">Ya tengo cuenta</a> · <a href="index.php">Inicio</a></p>
  </div>
</div>
</body>
</html>
