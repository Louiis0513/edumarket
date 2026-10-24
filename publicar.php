<?php
session_start();
require_once __DIR__ . "/includes/conexion.php";
require_once __DIR__ . "/includes/funciones.php";
requiereUsuario();

$idEdit = (int)($_GET["id"] ?? 0);
$art = null;
$uid = (int)$_SESSION["usuario"]["id"];

if ($idEdit > 0) {
    $st = $mysqli->prepare("SELECT * FROM articulo WHERE id_articulo=? AND id_usuario=?");
    $st->bind_param("ii", $idEdit, $uid);
    $st->execute();
    $art = $st->get_result()->fetch_assoc();
    if (!$art) {
        guardarMensaje("No puedes editar ese artículo.");
        header("Location: mis_publicaciones.php");
        exit;
    }
}

$cats = $mysqli->query("SELECT id_categoria,nombre FROM categoria ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);
$insts = $mysqli->query("SELECT id_institucion,nombre FROM institucion ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $titulo = trim($_POST["titulo"] ?? "");
    $descripcion = trim($_POST["descripcion"] ?? "");
    $tipo = ($_POST["tipo"] ?? "") === "intercambio" ? "intercambio" : "venta";
    $precio = $tipo === "intercambio" ? 0 : (float)($_POST["precio"] ?? 0);
    $telefono = trim($_POST["telefono_contacto"] ?? "");
    $id_categoria = (int)($_POST["id_categoria"] ?? 0);
    $estado = ($_POST["estado"] ?? "disponible") === "entregado" ? "entregado" : "disponible";
    $colegioModo = ($_POST["colegio_modo"] ?? "lista") === "nuevo" ? "nuevo" : "lista";
    $id_institucion = 0;
    if ($colegioModo === "nuevo") {
        $id_institucion = resolverInstitucion($mysqli, 0, $_POST["colegio_nuevo"] ?? "");
    } else {
        $id_institucion = resolverInstitucion($mysqli, (int)($_POST["id_institucion"] ?? 0), "");
    }

    $foto = $art["foto_url"] ?? "";
    $uploadError = null;
    $subida = subirFotoArticulo($_FILES["foto"] ?? [], $uploadError);
    if ($subida === false) {
        $error = $uploadError;
    } elseif ($subida !== null) {
        $foto = $subida;
    }

    if ($error === "") {
        if ($titulo === "" || $telefono === "" || $id_categoria <= 0 || $id_institucion <= 0) {
            $error = "Completa título, teléfono, categoría y colegio.";
            if ($colegioModo === "nuevo" && trim($_POST["colegio_nuevo"] ?? "") === "") {
                $error = "Escribe el nombre del colegio nuevo o elige uno de la lista.";
            }
        } elseif ($tipo === "venta" && $precio <= 0) {
            $error = "El precio debe ser mayor a 0 en venta.";
        } else {
            if ($idEdit > 0) {
                $st = $mysqli->prepare("UPDATE articulo SET titulo=?,descripcion=?,precio=?,tipo=?,estado=?,telefono_contacto=?,foto_url=?,id_categoria=?,id_institucion=? WHERE id_articulo=? AND id_usuario=?");
                $st->bind_param("ssdssssiiii", $titulo, $descripcion, $precio, $tipo, $estado, $telefono, $foto, $id_categoria, $id_institucion, $idEdit, $uid);
                $st->execute();
                guardarMensaje("Artículo actualizado.");
            } else {
                $estado = "disponible";
                $st = $mysqli->prepare("INSERT INTO articulo (titulo,descripcion,precio,tipo,estado,telefono_contacto,foto_url,id_categoria,id_institucion,id_usuario) VALUES (?,?,?,?,?,?,?,?,?,?)");
                $st->bind_param("ssdssssiii", $titulo, $descripcion, $precio, $tipo, $estado, $telefono, $foto, $id_categoria, $id_institucion, $uid);
                $st->execute();
                guardarMensaje("Artículo publicado. ¡Listo para el catálogo!");
            }
            // Recargar lista de colegios por si se creó uno nuevo
            header("Location: mis_publicaciones.php");
            exit;
        }
    }
}

$v = function ($k, $d = "") use ($art) {
    if ($_SERVER["REQUEST_METHOD"] === "POST") return $_POST[$k] ?? $d;
    return $art[$k] ?? $d;
};
$colegioModoActual = ($_SERVER["REQUEST_METHOD"] === "POST")
    ? ((($_POST["colegio_modo"] ?? "") === "nuevo") ? "nuevo" : "lista")
    : "lista";

require __DIR__ . "/includes/header.php";
?>
<main class="contenedor">
  <div class="page-head">
    <div>
      <h1><?= $idEdit ? "Editar" : "Publicar" ?> artículo</h1>
      <p>Describe el útil con claridad: el WhatsApp es la vía de contacto.</p>
    </div>
    <a class="btn btn-ghost" href="mis_publicaciones.php">Mis publicaciones</a>
  </div>

  <div class="panel form-panel">
    <?php if ($error): ?><p class="form-error"><?= e($error) ?></p><?php endif; ?>
    <form method="post" enctype="multipart/form-data">
      <div class="campo">
        <label for="titulo">Título</label>
        <input id="titulo" name="titulo" required maxlength="200" placeholder="Ej. Maleta azul con ruedas"
               value="<?= e($v('titulo')) ?>">
      </div>
      <div class="campo">
        <label for="descripcion">Descripción</label>
        <textarea id="descripcion" name="descripcion" placeholder="Estado, talla, grado, lo que incluye…"><?= e($v('descripcion')) ?></textarea>
      </div>
      <div class="form-row">
        <div class="campo">
          <label for="tipo">Tipo</label>
          <select name="tipo" id="tipo">
            <option value="venta" <?= $v('tipo','venta')==='venta'?'selected':'' ?>>Venta</option>
            <option value="intercambio" <?= $v('tipo')==='intercambio'?'selected':'' ?>>Intercambio</option>
          </select>
        </div>
        <div class="campo">
          <label for="precio">Precio (COP)</label>
          <input id="precio" type="number" step="100" min="0" name="precio" value="<?= e($v('precio','0')) ?>">
        </div>
      </div>
      <?php if ($idEdit): ?>
      <div class="campo">
        <label for="estado">Estado</label>
        <select id="estado" name="estado">
          <option value="disponible" <?= $v('estado')==='disponible'?'selected':'' ?>>Disponible</option>
          <option value="entregado" <?= $v('estado')==='entregado'?'selected':'' ?>>Entregado</option>
        </select>
      </div>
      <?php endif; ?>
      <div class="campo">
        <label for="telefono_contacto">Teléfono WhatsApp</label>
        <input id="telefono_contacto" name="telefono_contacto" required placeholder="3001234567"
               value="<?= e($v('telefono_contacto', $_SESSION['usuario']['telefono'] ?? '')) ?>">
      </div>

      <div class="campo">
        <label for="foto">Foto del artículo</label>
        <div class="upload-box">
          <input id="foto" type="file" name="foto" accept="image/jpeg,image/png,image/webp,image/gif">
          <p class="muted" style="margin-top:.45rem;font-size:.82rem;">JPG, PNG, WEBP o GIF · máx. 3 MB</p>
          <?php
            $fotoActual = trim((string)$v('foto_url', $art['foto_url'] ?? ''));
            if ($fotoActual !== ""):
          ?>
            <div class="upload-preview">
              <img src="<?= e($fotoActual) ?>" alt="Vista previa">
              <span class="muted">Foto actual<?= $idEdit ? ' (sube otra para reemplazarla)' : '' ?></span>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="campo">
        <label for="id_categoria">Categoría</label>
        <select id="id_categoria" name="id_categoria" required>
          <option value="">Seleccionar</option>
          <?php foreach ($cats as $c): ?>
            <option value="<?= (int)$c['id_categoria'] ?>" <?= (int)$v('id_categoria')===(int)$c['id_categoria']?'selected':'' ?>><?= e($c['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <fieldset class="colegio-fieldset">
        <legend>Colegio</legend>
        <div class="colegio-tabs" role="tablist">
          <label class="colegio-tab">
            <input type="radio" name="colegio_modo" value="lista" <?= $colegioModoActual==='lista'?'checked':'' ?>>
            Elegir existente
          </label>
          <label class="colegio-tab">
            <input type="radio" name="colegio_modo" value="nuevo" <?= $colegioModoActual==='nuevo'?'checked':'' ?>>
            Crear nuevo
          </label>
        </div>
        <div class="campo" id="bloque-lista" style="<?= $colegioModoActual==='nuevo'?'display:none':'' ?>">
          <label for="id_institucion">Colegios registrados</label>
          <select id="id_institucion" name="id_institucion">
            <option value="">Seleccionar colegio</option>
            <?php foreach ($insts as $i): ?>
              <option value="<?= (int)$i['id_institucion'] ?>" <?= (int)$v('id_institucion')===(int)$i['id_institucion']?'selected':'' ?>><?= e($i['nombre']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="campo" id="bloque-nuevo" style="<?= $colegioModoActual==='lista'?'display:none':'' ?>">
          <label for="colegio_nuevo">Nombre del colegio</label>
          <input id="colegio_nuevo" name="colegio_nuevo" maxlength="150"
                 placeholder="Ej. Colegio Nuevo Horizonte"
                 value="<?= e($_POST['colegio_nuevo'] ?? '') ?>">
          <p class="muted" style="margin-top:.35rem;font-size:.82rem;">Si el nombre ya existe, se reutiliza (sin duplicar).</p>
        </div>
      </fieldset>

      <div class="acciones acciones-form">
        <button class="btn btn-primary" type="submit"><?= $idEdit ? "Guardar cambios" : "Publicar ahora" ?></button>
        <a class="btn btn-ghost" href="mis_publicaciones.php">Cancelar</a>
      </div>
    </form>
  </div>
</main>
<script>
const tipo = document.getElementById('tipo');
const precio = document.getElementById('precio');
function syncTipo() {
  if (tipo.value === 'intercambio') {
    precio.value = '0';
    precio.readOnly = true;
    precio.style.opacity = '.6';
  } else {
    precio.readOnly = false;
    precio.style.opacity = '1';
  }
}
tipo.addEventListener('change', syncTipo);
syncTipo();

const bloqueLista = document.getElementById('bloque-lista');
const bloqueNuevo = document.getElementById('bloque-nuevo');
document.querySelectorAll('input[name="colegio_modo"]').forEach(function (r) {
  r.addEventListener('change', function () {
    const esNuevo = this.value === 'nuevo';
    bloqueLista.style.display = esNuevo ? 'none' : '';
    bloqueNuevo.style.display = esNuevo ? '' : 'none';
  });
});
</script>
<?php require __DIR__ . "/includes/footer.php"; ?>
