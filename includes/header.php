<?php
$mensaje = obtenerMensaje();
$activo = basename($_SERVER["PHP_SELF"] ?? "");
$usuario = usuarioActual();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>EduMarket · Útiles escolares de segunda mano</title>
<meta name="description" content="Marketplace escolar: compra, vende e intercambia libros, uniformes, maletas y útiles.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,700;12..96,800&family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/estilos.css">
<link rel="icon" href="img/logo.png" type="image/png">
</head>
<body>
<header>
<nav class="nav" aria-label="Principal">
  <a class="nav-brand" href="index.php">
    <img src="img/logo.png" alt="EduMarket">
    <span>EduMarket</span>
  </a>
  <input type="checkbox" id="nav-toggle" class="nav-toggle-input" hidden>
  <label for="nav-toggle" class="nav-toggle" aria-label="Abrir o cerrar menú">
    <span></span><span></span><span></span>
  </label>
  <div class="nav-links">
    <a class="<?= $activo==='index.php'?'active':'' ?>" href="index.php">Inicio</a>
    <a class="<?= $activo==='catalogo.php'?'active':'' ?>" href="catalogo.php">Catálogo</a>
    <?php if ($usuario): ?>
      <a class="nav-cta <?= $activo==='publicar.php'?'active':'' ?>" href="publicar.php">Publicar</a>
      <a class="<?= $activo==='mis_publicaciones.php'?'active':'' ?>" href="mis_publicaciones.php">Mis publicaciones</a>
      <?php if (!empty($usuario["es_admin"])): ?>
        <a class="<?= $activo==='admin.php'?'active':'' ?>" href="admin.php">Admin</a>
      <?php endif; ?>
      <span class="nav-user">Hola, <?= e($usuario["nombre"]) ?></span>
      <a href="logout.php">Salir</a>
    <?php else: ?>
      <a href="login.php">Ingresar</a>
      <a class="nav-cta" href="registro.php">Crear cuenta</a>
    <?php endif; ?>
  </div>
</nav>
</header>
<?php if ($mensaje !== ""): ?><p class="mensaje" role="status"><?= e($mensaje) ?></p><?php endif; ?>
