<?php
session_start();
require_once __DIR__ . "/includes/conexion.php";
require_once __DIR__ . "/includes/funciones.php";
requiereAdmin();

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $accion = $_POST["accion"] ?? "";

    if ($accion === "eliminar_articulo") {
        $id = (int)($_POST["id_articulo"] ?? 0);
        $st = $mysqli->prepare("DELETE FROM articulo WHERE id_articulo=?");
        $st->bind_param("i", $id);
        $st->execute();
        guardarMensaje("Publicación #{$id} eliminada.");
        header("Location: admin.php");
        exit;
    }

    if ($accion === "cambiar_estado") {
        $id = (int)($_POST["id_articulo"] ?? 0);
        $estado = ($_POST["estado"] ?? "") === "entregado" ? "entregado" : "disponible";
        $st = $mysqli->prepare("UPDATE articulo SET estado=? WHERE id_articulo=?");
        $st->bind_param("si", $estado, $id);
        $st->execute();
        guardarMensaje("Estado actualizado.");
        header("Location: admin.php?" . http_build_query(array_filter([
            "q" => trim($_GET["q"] ?? "") ?: null,
            "estado" => trim($_GET["estado"] ?? "") ?: null,
            "page" => (int)($_GET["page"] ?? 1) ?: null,
        ])));
        exit;
    }

    if ($accion === "crear_colegio") {
        $nombre = trim($_POST["nombre_colegio"] ?? "");
        $id = obtenerOCrearInstitucion($mysqli, $nombre);
        if ($id <= 0) {
            $error = "Nombre de colegio inválido (mín. 3 caracteres).";
        } else {
            guardarMensaje("Colegio guardado (sin duplicados).");
            header("Location: admin.php#colegios");
            exit;
        }
    }

    if ($accion === "eliminar_colegio") {
        $id = (int)($_POST["id_institucion"] ?? 0);
        $cnt = $mysqli->prepare("SELECT COUNT(*) AS c FROM articulo WHERE id_institucion=?");
        $cnt->bind_param("i", $id);
        $cnt->execute();
        $n = (int)$cnt->get_result()->fetch_assoc()["c"];
        if ($n > 0) {
            $error = "No se puede eliminar: hay {$n} publicación(es) ligadas a ese colegio.";
        } else {
            $st = $mysqli->prepare("DELETE FROM institucion WHERE id_institucion=?");
            $st->bind_param("i", $id);
            $st->execute();
            guardarMensaje("Colegio eliminado.");
            header("Location: admin.php#colegios");
            exit;
        }
    }
}

$q = trim($_GET["q"] ?? "");
$estadoFiltro = trim($_GET["estado"] ?? "");

$where = " WHERE 1=1";
$types = "";
$params = [];
if ($q !== "") {
    $where .= " AND (a.titulo LIKE ? OR u.nombre LIKE ? OR u.email LIKE ?)";
    $types .= "sss";
    $like = "%{$q}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}
if ($estadoFiltro === "disponible" || $estadoFiltro === "entregado") {
    $where .= " AND a.estado=?";
    $types .= "s";
    $params[] = $estadoFiltro;
}

$countSql = "SELECT COUNT(*) AS total
 FROM articulo a
 JOIN usuario u ON u.id_usuario = a.id_usuario" . $where;
if ($types) {
    $stc = $mysqli->prepare($countSql);
    $stc->bind_param($types, ...$params);
    $stc->execute();
    $totalArts = (int)$stc->get_result()->fetch_assoc()["total"];
} else {
    $totalArts = (int)$mysqli->query($countSql)->fetch_assoc()["total"];
}

$pag = calcularPaginacion($totalArts, 10);
$queryKeep = array_filter([
    "q" => $q !== "" ? $q : null,
    "estado" => $estadoFiltro !== "" ? $estadoFiltro : null,
]);

$sql = "SELECT a.*, c.nombre AS categoria, i.nombre AS institucion,
        u.nombre AS autor, u.email AS autor_email
 FROM articulo a
 JOIN categoria c ON c.id_categoria = a.id_categoria
 JOIN institucion i ON i.id_institucion = a.id_institucion
 JOIN usuario u ON u.id_usuario = a.id_usuario"
 . $where
 . " ORDER BY a.id_articulo DESC LIMIT ? OFFSET ?";
$typesPage = $types . "ii";
$paramsPage = array_merge($params, [$pag["por_pagina"], $pag["offset"]]);
$st = $mysqli->prepare($sql);
$st->bind_param($typesPage, ...$paramsPage);
$st->execute();
$arts = $st->get_result()->fetch_all(MYSQLI_ASSOC);

$stats = [
    "articulos" => (int)$mysqli->query("SELECT COUNT(*) c FROM articulo")->fetch_assoc()["c"],
    "disponibles" => (int)$mysqli->query("SELECT COUNT(*) c FROM articulo WHERE estado='disponible'")->fetch_assoc()["c"],
    "usuarios" => (int)$mysqli->query("SELECT COUNT(*) c FROM usuario")->fetch_assoc()["c"],
    "colegios" => (int)$mysqli->query("SELECT COUNT(*) c FROM institucion")->fetch_assoc()["c"],
];
$insts = $mysqli->query(
    "SELECT i.*, (SELECT COUNT(*) FROM articulo a WHERE a.id_institucion=i.id_institucion) AS pubs
     FROM institucion i ORDER BY i.nombre"
)->fetch_all(MYSQLI_ASSOC);
$usuarios = $mysqli->query(
    "SELECT u.id_usuario, u.nombre, u.email, u.es_admin,
            (SELECT COUNT(*) FROM articulo a WHERE a.id_usuario=u.id_usuario) AS pubs
     FROM usuario u ORDER BY u.es_admin DESC, u.nombre"
)->fetch_all(MYSQLI_ASSOC);

require __DIR__ . "/includes/header.php";
?>
<main class="contenedor">
  <div class="page-head">
    <div>
      <h1>Panel administrador</h1>
      <p>Control de publicaciones, colegios y usuarios.</p>
    </div>
  </div>

  <?php if ($error): ?><p class="form-error"><?= e($error) ?></p><?php endif; ?>

  <div class="admin-stats">
    <div class="stat-card"><strong><?= $stats["articulos"] ?></strong><span>Publicaciones</span></div>
    <div class="stat-card"><strong><?= $stats["disponibles"] ?></strong><span>Disponibles</span></div>
    <div class="stat-card"><strong><?= $stats["usuarios"] ?></strong><span>Usuarios</span></div>
    <div class="stat-card"><strong><?= $stats["colegios"] ?></strong><span>Colegios</span></div>
  </div>

  <section class="section">
    <div class="section-head">
      <h2>Todas las publicaciones</h2>
    </div>
    <form class="panel filters admin-filters" method="get">
      <div>
        <label for="q">Buscar</label>
        <input id="q" name="q" placeholder="Título, autor o correo…" value="<?= e($q) ?>">
      </div>
      <div>
        <label for="estado">Estado</label>
        <select id="estado" name="estado">
          <option value="">Todos</option>
          <option value="disponible" <?= $estadoFiltro==='disponible'?'selected':'' ?>>Disponible</option>
          <option value="entregado" <?= $estadoFiltro==='entregado'?'selected':'' ?>>Entregado</option>
        </select>
      </div>
      <div class="filters-actions">
        <button class="btn btn-primary" type="submit">Filtrar</button>
        <a class="btn btn-ghost" href="admin.php">Limpiar</a>
      </div>
    </form>

    <?php if (!$arts): ?>
      <div class="empty-state"><h3>Sin publicaciones</h3></div>
    <?php else: ?>
      <div class="pub-grid">
        <?php foreach ($arts as $a):
          $foto = fotoArticulo($a["foto_url"], $a["categoria"]);
        ?>
          <article class="pub-item admin-pub">
            <div class="pub-thumb">
              <img src="<?= e($foto) ?>" alt="" loading="lazy">
            </div>
            <div>
              <h3>#<?= (int)$a["id_articulo"] ?> · <?= e($a["titulo"]) ?></h3>
              <p class="muted">
                <span class="badge <?= $a['tipo']==='intercambio'?'intercambio':'' ?>"><?= e(etiquetaTipo($a['tipo'])) ?></span>
                <?php if ($a["estado"] === "entregado"): ?>
                  <span class="badge entregado">Entregado</span>
                <?php else: ?>
                  <span class="badge">Disponible</span>
                <?php endif; ?>
                · <?= e($a["categoria"]) ?> · <?= e($a["institucion"]) ?>
              </p>
              <p class="muted">Por <?= e($a["autor"]) ?> (<?= e($a["autor_email"]) ?>)</p>
            </div>
            <div class="pub-actions">
              <a class="btn btn-ghost" href="articulo.php?id=<?= (int)$a['id_articulo'] ?>">Ver</a>
              <form method="post">
                <input type="hidden" name="accion" value="cambiar_estado">
                <input type="hidden" name="id_articulo" value="<?= (int)$a['id_articulo'] ?>">
                <input type="hidden" name="estado" value="<?= $a['estado']==='disponible'?'entregado':'disponible' ?>">
                <button class="btn btn-ghost" type="submit">
                  <?= $a['estado']==='disponible' ? 'Marcar entregado' : 'Marcar disponible' ?>
                </button>
              </form>
              <form method="post" onsubmit="return confirm('¿Eliminar esta publicación de forma permanente?');">
                <input type="hidden" name="accion" value="eliminar_articulo">
                <input type="hidden" name="id_articulo" value="<?= (int)$a['id_articulo'] ?>">
                <button class="btn btn-danger" type="submit">Eliminar</button>
              </form>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
      <?= renderPaginacion($pag, $queryKeep) ?>
    <?php endif; ?>
  </section>

  <section class="section" id="colegios">
    <div class="section-head">
      <h2>Colegios</h2>
    </div>
    <div class="panel form-panel" style="margin-bottom:1rem;">
      <form method="post" class="admin-inline-form">
        <input type="hidden" name="accion" value="crear_colegio">
        <div class="campo" style="flex:1;margin-bottom:0;">
          <label for="nombre_colegio">Agregar colegio</label>
          <input id="nombre_colegio" name="nombre_colegio" required maxlength="150" placeholder="Nombre del colegio">
        </div>
        <button class="btn btn-primary" type="submit" style="align-self:end;">Guardar</button>
      </form>
      <p class="muted" style="margin-top:.5rem;font-size:.82rem;">Si el nombre ya existe, no se duplica.</p>
    </div>
    <div class="table-wrap panel">
      <table>
        <thead>
          <tr><th>Nombre</th><th>Publicaciones</th><th></th></tr>
        </thead>
        <tbody>
          <?php foreach ($insts as $i): ?>
            <tr>
              <td><?= e($i["nombre"]) ?></td>
              <td><?= (int)$i["pubs"] ?></td>
              <td>
                <?php if ((int)$i["pubs"] === 0): ?>
                  <form method="post" onsubmit="return confirm('¿Eliminar colegio?');" style="display:inline;">
                    <input type="hidden" name="accion" value="eliminar_colegio">
                    <input type="hidden" name="id_institucion" value="<?= (int)$i['id_institucion'] ?>">
                    <button class="btn btn-danger" type="submit">Eliminar</button>
                  </form>
                <?php else: ?>
                  <span class="muted">En uso</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

  <section class="section" id="usuarios">
    <div class="section-head">
      <h2>Usuarios</h2>
    </div>
    <div class="table-wrap panel">
      <table>
        <thead>
          <tr><th>Nombre</th><th>Correo</th><th>Rol</th><th>Pubs</th></tr>
        </thead>
        <tbody>
          <?php foreach ($usuarios as $u): ?>
            <tr>
              <td><?= e($u["nombre"]) ?></td>
              <td><?= e($u["email"]) ?></td>
              <td><?= !empty($u["es_admin"]) ? "Admin" : "Usuario" ?></td>
              <td><?= (int)$u["pubs"] ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
</main>
<?php require __DIR__ . "/includes/footer.php"; ?>
