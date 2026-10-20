<?php
session_start();
require_once __DIR__ . "/includes/conexion.php";
require_once __DIR__ . "/includes/funciones.php";
if (usuarioLogueado()) { header("Location: index.php"); exit; }

$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $pass = $_POST["password"] ?? "";
    $stmt = $mysqli->prepare("SELECT id_usuario, nombre, email, password_hash, telefono FROM usuario WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $u = $stmt->get_result()->fetch_assoc();
    if ($u && password_verify($pass, $u["password_hash"])) {
        session_regenerate_id(true);
        $_SESSION["usuario"] = [
            "id" => (int)$u["id_usuario"],
            "nombre" => $u["nombre"],
            "email" => $u["email"],
            "telefono" => $u["telefono"],
        ];
        guardarMensaje("Bienvenido/a, " . $u["nombre"]);
        header("Location: index.php");
        exit;
    }
    $error = "Correo o contraseña incorrectos.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ingresar | EduMarket</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,700;12..96,800&family=Figtree:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/estilos.css">
<link rel="icon" href="img/logo.png" type="image/png">
</head>
<body>
<div class="auth-wrap">
  <div class="auth-card">
    <img src="img/logo.png" alt="EduMarket">
    <h1>Ingresar</h1>
    <p class="muted" style="margin-bottom:1.1rem;">Publica y gestiona tus útiles escolares.</p>
    <?php if ($error): ?><p class="form-error"><?= e($error) ?></p><?php endif; ?>
    <form class="auth-form" method="post">
      <div class="campo">
        <label for="email">Correo</label>
        <input id="email" type="email" name="email" required autocomplete="email"
               value="<?= e($_POST['email'] ?? '') ?>">
      </div>
      <div class="campo">
        <label for="password">Contraseña</label>
        <input id="password" type="password" name="password" required autocomplete="current-password">
      </div>
      <button class="btn btn-primary full" type="submit">Ingresar</button>
    </form>
    <div class="auth-hint">
      Demo: <strong>admin@demo.com</strong> / Admin123!<br>
      o <strong>maria@demo.com</strong> / Admin123!
    </div>
    <p style="margin-top:1rem;">
      <a href="registro.php">Crear cuenta</a> · <a href="index.php">Volver al inicio</a>
    </p>
  </div>
</div>
</body>
</html>
