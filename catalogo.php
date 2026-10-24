<?php
session_start();
require_once __DIR__ . "/includes/conexion.php";
require_once __DIR__ . "/includes/funciones.php";

$idCat = (int)($_GET["categoria"] ?? 0);
$idInst = (int)($_GET["institucion"] ?? 0);
$tipoFiltro = trim($_GET["tipo"] ?? "");
$q = trim($_GET["q"] ?? "");

$cats = $mysqli->query("SELECT id_categoria, nombre FROM categoria ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);
$insts = $mysqli->query("SELECT id_institucion, nombre FROM institucion ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);

$where = " WHERE a.estado = 'disponible'";
$types = "";
$params = [];

if ($idCat > 0) { $where .= " AND a.id_categoria=?"; $types .= "i"; $params[] = $idCat; }
if ($idInst > 0) { $where .= " AND a.id_institucion=?"; $types .= "i"; $params[] = $idInst; }
if ($tipoFiltro === "venta" || $tipoFiltro === "intercambio") {
    $where .= " AND a.tipo=?"; $types .= "s"; $params[] = $tipoFiltro;
}
if ($q !== "") {
    $where .= " AND (a.titulo LIKE ? OR a.descripcion LIKE ?)";
    $types .= "ss";
    $like = "%{$q}%";
    $params[] = $like;
    $params[] = $like;
}

$countSql = "SELECT COUNT(*) AS total FROM articulo a" . $where;
if ($types) {
    $stCount = $mysqli->prepare($countSql);
    $stCount->bind_param($types, ...$params);
    $stCount->execute();
    $total = (int)$stCount->get_result()->fetch_assoc()["total"];
} else {
    $total = (int)$mysqli->query($countSql)->fetch_assoc()["total"];
}

$pag = calcularPaginacion($total, 8);
$queryKeep = [
    "categoria" => $idCat ?: null,
    "institucion" => $idInst ?: null,
    "tipo" => $tipoFiltro !== "" ? $tipoFiltro : null,
    "q" => $q !== "" ? $q : null,
];
$queryKeep = array_filter($queryKeep, fn($v) => $v !== null && $v !== "");

$sql = "SELECT a.*, c.nombre AS categoria, i.nombre AS institucion
 FROM articulo a
 JOIN categoria c ON c.id_categoria = a.id_categoria
 JOIN institucion i ON i.id_institucion = a.id_institucion"
 . $where
 . " ORDER BY a.id_articulo DESC LIMIT ? OFFSET ?";

$typesPage = $types . "ii";
$paramsPage = array_merge($params, [$pag["por_pagina"], $pag["offset"]]);
$st = $mysqli->prepare($sql);
$st->bind_param($typesPage, ...$paramsPage);
$st->execute();
$arts = $st->get_result()->fetch_all(MYSQLI_ASSOC);

require __DIR__ . "/includes/header.php";
?>
<main class="contenedor">
  <div class="page-head">
    <div>
      <h1>Catálogo</h1>
      <p>Filtra por colegio, categoría o tipo y contacta por WhatsApp.</p>
    </div>
    <?php if (usuarioLogueado()): ?>
      <a class="btn btn-primary" href="publicar.php">＋ Publicar</a>
    <?php else: ?>
      <a class="btn btn-ghost" href="login.php">Ingresa para publicar</a>
    <?php endif; ?>
  </div>

  <form class="panel filters" method="get" action="catalogo.php">
    <div>
      <label for="q">Buscar</label>
      <input id="q" name="q" placeholder="Ej. maleta, matemáticas…" value="<?= e($q) ?>">
    </div>
    <div>
      <label for="categoria">Categoría</label>
      <select id="categoria" name="categoria">
        <option value="0">Todas</option>
        <?php foreach ($cats as $c): ?>
          <option value="<?= (int)$c['id_categoria'] ?>" <?= $idCat === (int)$c['id_categoria'] ? 'selected' : '' ?>><?= e($c['nombre']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label for="institucion">Colegio</label>
      <select id="institucion" name="institucion">
        <option value="0">Todos</option>
        <?php foreach ($insts as $i): ?>
          <option value="<?= (int)$i['id_institucion'] ?>" <?= $idInst === (int)$i['id_institucion'] ? 'selected' : '' ?>><?= e($i['nombre']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label for="tipo">Tipo</label>
      <select id="tipo" name="tipo">
        <option value="">Todos</option>
        <option value="venta" <?= $tipoFiltro === 'venta' ? 'selected' : '' ?>>Venta</option>
        <option value="intercambio" <?= $tipoFiltro === 'intercambio' ? 'selected' : '' ?>>Intercambio</option>
      </select>
    </div>
    <div class="filters-actions">
      <button class="btn btn-primary" type="submit">Filtrar</button>
      <a class="btn btn-ghost" href="catalogo.php">Limpiar</a>
    </div>
  </form>

  <div class="results-meta">
    <span><?= $total ?> resultado<?= $total === 1 ? '' : 's' ?></span>
  </div>

  <?php if (!$arts): ?>
    <div class="empty-state">
      <h3>No hay artículos con esos filtros</h3>
      <p>Prueba otra categoría o limpia la búsqueda.</p>
      <a class="btn btn-primary" href="catalogo.php">Ver todo el catálogo</a>
    </div>
  <?php else: ?>
    <div class="grid">
      <?php foreach ($arts as $a):
        $foto = fotoArticulo($a['foto_url'], $a['categoria']);
      ?>
        <article class="card">
          <a class="card-media" href="articulo.php?id=<?= (int)$a['id_articulo'] ?>">
            <img src="<?= e($foto) ?>" alt="<?= e($a['titulo']) ?>" loading="lazy">
            <span class="badge <?= $a['tipo']==='intercambio'?'intercambio':'' ?>"><?= e(etiquetaTipo($a['tipo'])) ?></span>
          </a>
          <div class="card-body">
            <h3><a href="articulo.php?id=<?= (int)$a['id_articulo'] ?>"><?= e($a['titulo']) ?></a></h3>
            <p class="muted"><?= e($a['institucion']) ?> · <?= e($a['categoria']) ?></p>
            <p class="card-price <?= $a['tipo']==='intercambio'?'swap':'' ?>">
              <?= $a['tipo']==='intercambio' ? 'Intercambio' : formatoPesos($a['precio']) ?>
            </p>
            <div class="acciones">
              <a class="btn btn-ghost" href="articulo.php?id=<?= (int)$a['id_articulo'] ?>">Ver detalle</a>
              <a class="btn btn-wa" target="_blank" rel="noopener"
                 href="<?= e(waLink($a['telefono_contacto'], 'Hola, me interesa en EduMarket: '.$a['titulo'])) ?>">WhatsApp</a>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
    <?= renderPaginacion($pag, $queryKeep) ?>
  <?php endif; ?>
</main>
<?php require __DIR__ . "/includes/footer.php"; ?>
