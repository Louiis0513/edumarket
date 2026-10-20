<?php
session_start();
require_once __DIR__ . "/includes/conexion.php";
require_once __DIR__ . "/includes/funciones.php";

$cats = $mysqli->query("SELECT id_categoria, nombre FROM categoria ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);
$catFotos = [
    "Libros" => "img/libros-1.jpg",
    "Uniformes" => "img/uniforme-1.jpg",
    "Maletas" => "img/maleta-1.jpg",
    "Cartucheras" => "img/cartuchera-2.jpg",
    "Útiles" => "img/utiles-1.jpg",
];

$destacados = $mysqli->query(
    "SELECT a.*, c.nombre AS categoria, i.nombre AS institucion
     FROM articulo a
     JOIN categoria c ON c.id_categoria = a.id_categoria
     JOIN institucion i ON i.id_institucion = a.id_institucion
     WHERE a.estado = 'disponible'
     ORDER BY a.id_articulo DESC
     LIMIT 6"
)->fetch_all(MYSQLI_ASSOC);

require __DIR__ . "/includes/header.php";
?>
<main class="contenedor">
  <section class="hero" aria-label="Bienvenida EduMarket">
    <div class="hero-media">
      <img src="img/hero-utiles.jpg" alt="Útiles escolares sobre mesa">
    </div>
    <div class="hero-content">
      <div class="brand-mark">
        <img src="img/logo.png" alt="">
        EduMarket
      </div>
      <h1>Reutiliza, ahorra, comparte</h1>
      <p class="lead">Uniformes, libros, maletas y útiles escolares de segunda mano entre familias de tu colegio.</p>
      <div class="hero-actions">
        <a class="btn btn-secondary btn-lg" href="catalogo.php">Ver catálogo</a>
        <?php if (usuarioLogueado()): ?>
          <a class="btn btn-primary btn-lg" href="publicar.php">Publicar artículo</a>
        <?php else: ?>
          <a class="btn btn-primary btn-lg" href="registro.php">Empezar gratis</a>
        <?php endif; ?>
      </div>
      <div class="chips" aria-label="Categorías">
        <?php foreach ($cats as $c): ?>
          <a class="chip" href="catalogo.php?categoria=<?= (int)$c['id_categoria'] ?>"><?= e($c['nombre']) ?></a>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="section">
    <div class="section-head">
      <h2>Explora por categoría</h2>
      <a href="catalogo.php">Ver todo →</a>
    </div>
    <div class="cat-grid">
      <?php foreach ($cats as $c):
        $foto = $catFotos[$c['nombre']] ?? 'img/hero-utiles.jpg';
      ?>
        <a class="cat-card" href="catalogo.php?categoria=<?= (int)$c['id_categoria'] ?>">
          <img src="<?= e($foto) ?>" alt="<?= e($c['nombre']) ?>">
          <span><?= e($c['nombre']) ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="section">
    <div class="section-head">
      <h2>Cómo funciona</h2>
      <p class="muted">Tres pasos, sin intermediarios</p>
    </div>
    <div class="how-grid">
      <article class="how-step">
        <div class="num">1</div>
        <h3>Publica</h3>
        <p class="muted">Sube el útil que ya no usas: título, colegio, precio o intercambio.</p>
      </article>
      <article class="how-step">
        <div class="num">2</div>
        <h3>Encuentra</h3>
        <p class="muted">Filtra por categoría, colegio o palabra clave en el catálogo.</p>
      </article>
      <article class="how-step">
        <div class="num">3</div>
        <h3>Contacta</h3>
        <p class="muted">Habla directo por WhatsApp y cierra la venta o el trueque.</p>
      </article>
    </div>
  </section>

  <section class="section">
    <div class="section-head">
      <h2>Recién publicados</h2>
      <a href="catalogo.php">Catálogo completo →</a>
    </div>
    <?php if (!$destacados): ?>
      <div class="empty-state">
        <h3>Aún no hay artículos</h3>
        <p>Sé el primero en publicar un útil escolar.</p>
        <a class="btn btn-primary" href="<?= usuarioLogueado() ? 'publicar.php' : 'registro.php' ?>">Publicar</a>
      </div>
    <?php else: ?>
      <div class="grid">
        <?php foreach ($destacados as $a):
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
                <a class="btn btn-ghost" href="articulo.php?id=<?= (int)$a['id_articulo'] ?>">Ver</a>
                <a class="btn btn-wa" target="_blank" rel="noopener"
                   href="<?= e(waLink($a['telefono_contacto'], 'Hola, me interesa en EduMarket: '.$a['titulo'])) ?>">WhatsApp</a>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</main>
<?php require __DIR__ . "/includes/footer.php"; ?>
