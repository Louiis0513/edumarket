<?php
session_start();
require_once __DIR__ . "/includes/conexion.php";
require_once __DIR__ . "/includes/funciones.php";

$id = (int)($_GET["id"] ?? 0);
$st = $mysqli->prepare(
    "SELECT a.*, c.nombre AS categoria, i.nombre AS institucion, u.nombre AS vendedor
     FROM articulo a
     JOIN categoria c ON c.id_categoria = a.id_categoria
     JOIN institucion i ON i.id_institucion = a.id_institucion
     JOIN usuario u ON u.id_usuario = a.id_usuario
     WHERE a.id_articulo = ? AND a.estado = 'disponible'
     LIMIT 1"
);
$st->bind_param("i", $id);
$st->execute();
$a = $st->get_result()->fetch_assoc();

if (!$a) {
    guardarMensaje("Artículo no disponible.");
    header("Location: catalogo.php");
    exit;
}

$foto = fotoArticulo($a["foto_url"], $a["categoria"]);
$waTexto = "Hola, vi tu publicación en EduMarket: «{$a['titulo']}». ¿Sigue disponible?";

require __DIR__ . "/includes/header.php";
?>
<main class="contenedor">
  <a class="back-link" href="catalogo.php">← Volver al catálogo</a>

  <div class="detail">
    <div class="detail-media">
      <img src="<?= e($foto) ?>" alt="<?= e($a['titulo']) ?>">
    </div>
    <div class="detail-body panel">
      <span class="badge <?= $a['tipo']==='intercambio'?'intercambio':'' ?>"><?= e(etiquetaTipo($a['tipo'])) ?></span>
      <h1><?= e($a["titulo"]) ?></h1>
      <p class="detail-price">
        <?= $a["tipo"] === "intercambio" ? "Intercambio" : formatoPesos($a["precio"]) ?>
      </p>
      <p><?= nl2br(e($a["descripcion"] ?? "")) ?></p>

      <div class="detail-meta">
        <p class="muted">Categoría: <strong><?= e($a["categoria"]) ?></strong></p>
        <p class="muted">Colegio: <strong><?= e($a["institucion"]) ?></strong></p>
        <p class="muted">Publicado por: <strong><?= e($a["vendedor"]) ?></strong></p>
      </div>

      <div class="wa-block">
        <p>Coordina entrega o trueque directamente con el vendedor.</p>
        <a class="btn btn-wa btn-lg full" target="_blank" rel="noopener"
           href="<?= e(waLink($a['telefono_contacto'], $waTexto)) ?>">
          Contactar por WhatsApp
        </a>
      </div>
    </div>
  </div>
</main>
<?php require __DIR__ . "/includes/footer.php"; ?>
