<?php
session_start();
require_once __DIR__ . "/includes/conexion.php";
require_once __DIR__ . "/includes/funciones.php";
requiereUsuario();

$uid = (int)$_SESSION["usuario"]["id"];

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["accion"] ?? "") === "eliminar") {
    $id = (int)($_POST["id_articulo"] ?? 0);
    $st = $mysqli->prepare("DELETE FROM articulo WHERE id_articulo=? AND id_usuario=?");
    $st->bind_param("ii", $id, $uid);
    $st->execute();
    guardarMensaje("Publicación eliminada.");
    header("Location: mis_publicaciones.php");
    exit;
}

$stc = $mysqli->prepare("SELECT COUNT(*) AS total FROM articulo WHERE id_usuario=?");
$stc->bind_param("i", $uid);
$stc->execute();
$total = (int)$stc->get_result()->fetch_assoc()["total"];
$pag = calcularPaginacion($total, 8);

$st = $mysqli->prepare(
    "SELECT a.*, c.nombre AS categoria
     FROM articulo a
     JOIN categoria c ON c.id_categoria = a.id_categoria
     WHERE a.id_usuario = ?
     ORDER BY a.id_articulo DESC
     LIMIT ? OFFSET ?"
);
$lim = $pag["por_pagina"];
$off = $pag["offset"];
$st->bind_param("iii", $uid, $lim, $off);
$st->execute();
$arts = $st->get_result()->fetch_all(MYSQLI_ASSOC);

require __DIR__ . "/includes/header.php";
?>
<main class="contenedor">
  <div class="page-head">
    <div>
      <h1>Mis publicaciones</h1>
      <p>Edita, marca como entregado o elimina tus anuncios.</p>
    </div>
    <a class="btn btn-primary" href="publicar.php">＋ Nuevo artículo</a>
  </div>

  <?php if (!$arts && $total === 0): ?>
    <div class="empty-state">
      <h3>Aún no tienes publicaciones</h3>
      <p>Publica un útil escolar y empieza a recibir mensajes por WhatsApp.</p>
      <a class="btn btn-primary" href="publicar.php">Publicar ahora</a>
    </div>
  <?php else: ?>
    <div class="results-meta">
      <span><?= $total ?> publicación<?= $total === 1 ? '' : 'es' ?></span>
    </div>
    <div class="pub-grid">
      <?php foreach ($arts as $a):
        $foto = fotoArticulo($a["foto_url"], $a["categoria"]);
      ?>
        <article class="pub-item">
          <div class="pub-thumb">
            <img src="<?= e($foto) ?>" alt="" loading="lazy">
          </div>
          <div>
            <h3><?= e($a["titulo"]) ?></h3>
            <p class="muted">
              <span class="badge <?= $a['tipo']==='intercambio'?'intercambio':'' ?>"><?= e(etiquetaTipo($a['tipo'])) ?></span>
              <?php if ($a["estado"] === "entregado"): ?>
                <span class="badge entregado">Entregado</span>
              <?php endif; ?>
              · <?= e($a["categoria"]) ?>
              · <?= $a["tipo"] === "intercambio" ? "Intercambio" : formatoPesos($a["precio"]) ?>
            </p>
          </div>
          <div class="pub-actions">
            <?php if ($a["estado"] === "disponible"): ?>
              <a class="btn btn-ghost" href="articulo.php?id=<?= (int)$a['id_articulo'] ?>">Ver</a>
            <?php endif; ?>
            <a class="btn btn-ghost" href="publicar.php?id=<?= (int)$a['id_articulo'] ?>">Editar</a>
            <form method="post" onsubmit="return confirm('¿Eliminar esta publicación?');">
              <input type="hidden" name="accion" value="eliminar">
              <input type="hidden" name="id_articulo" value="<?= (int)$a['id_articulo'] ?>">
              <button class="btn btn-danger" type="submit">Eliminar</button>
            </form>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
    <?= renderPaginacion($pag) ?>
  <?php endif; ?>
</main>
<?php require __DIR__ . "/includes/footer.php"; ?>
