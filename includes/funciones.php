<?php
function e($texto) {
    return htmlspecialchars((string)$texto, ENT_QUOTES, "UTF-8");
}
function usuarioLogueado() { return isset($_SESSION["usuario"]); }
function usuarioActual() { return $_SESSION["usuario"] ?? null; }
function esAdmin() {
    return usuarioLogueado() && !empty($_SESSION["usuario"]["es_admin"]);
}
function requiereUsuario() {
    if (!usuarioLogueado()) {
        guardarMensaje("Inicia sesión para continuar.");
        header("Location: login.php");
        exit;
    }
}
function requiereAdmin() {
    requiereUsuario();
    if (!esAdmin()) {
        guardarMensaje("No tienes permiso de administrador.");
        header("Location: index.php");
        exit;
    }
}
function guardarMensaje($texto) { $_SESSION["mensaje"] = $texto; }
function obtenerMensaje() {
    $m = $_SESSION["mensaje"] ?? "";
    unset($_SESSION["mensaje"]);
    return $m;
}
function formatoPesos($n) { return "$" . number_format((float)$n, 0, ",", "."); }
function waLink($telefono, $texto = "") {
    $tel = preg_replace("/\D+/", "", (string)$telefono);
    if ($tel !== "" && strpos($tel, "57") !== 0) $tel = "57" . $tel;
    $url = "https://wa.me/" . $tel;
    if ($texto !== "") $url .= "?text=" . rawurlencode($texto);
    return $url;
}
/** URL de foto del artículo (ruta local o absoluta). */
function fotoArticulo($fotoUrl, $fallbackCat = "") {
    $foto = trim((string)$fotoUrl);
    if ($foto !== "") {
        if (preg_match('#^https?://#i', $foto)) return $foto;
        return $foto;
    }
    $map = [
        "Libros" => "img/libros-1.jpg",
        "Uniformes" => "img/uniforme-1.jpg",
        "Maletas" => "img/maleta-1.jpg",
        "Cartucheras" => "img/cartuchera-2.jpg",
        "Útiles" => "img/utiles-1.jpg",
        "Utiles" => "img/utiles-1.jpg",
        "Cuadernos" => "img/cuadernos-1.jpg",
        "Calzado" => "img/uniforme-1.jpg",
        "Calculadoras" => "img/utiles-1.jpg",
        "Deportes" => "img/sudadera-1.jpg",
        "Arte y manualidades" => "img/utiles-2.jpg",
        "Tecnología" => "img/hero-utiles.jpg",
        "Instrumentos musicales" => "img/hero-utiles.jpg",
        "Accesorios" => "img/cartuchera-1.jpg",
        "Otros" => "img/hero-utiles.jpg",
    ];
    return $map[$fallbackCat] ?? "img/hero-utiles.jpg";
}
function etiquetaTipo($tipo) {
    return $tipo === "intercambio" ? "Intercambio" : "Venta";
}

/**
 * Sube una foto a uploads/. Devuelve ruta relativa o null.
 * $error se llena si el archivo es inválido.
 */
function subirFotoArticulo(array $file, &$error = null) {
    if (($file["error"] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($file["error"] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        $error = "No se pudo subir la foto. Intenta de nuevo.";
        return false;
    }
    if (($file["size"] ?? 0) > 3 * 1024 * 1024) {
        $error = "La foto no puede superar 3 MB.";
        return false;
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file["tmp_name"]);
    $exts = [
        "image/jpeg" => "jpg",
        "image/png" => "png",
        "image/webp" => "webp",
        "image/gif" => "gif",
    ];
    if (!isset($exts[$mime])) {
        $error = "Formato de foto no permitido. Usa JPG, PNG, WEBP o GIF.";
        return false;
    }
    $dir = __DIR__ . "/../uploads";
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $nombre = "art_" . date("Ymd_His") . "_" . bin2hex(random_bytes(4)) . "." . $exts[$mime];
    $destino = $dir . "/" . $nombre;
    if (!move_uploaded_file($file["tmp_name"], $destino)) {
        $error = "Error al guardar la foto en el servidor.";
        return false;
    }
    return "uploads/" . $nombre;
}

/**
 * Busca colegio por nombre (sin importar mayúsculas) o lo crea.
 * Evita duplicados. Devuelve id_institucion o 0.
 */
function obtenerOCrearInstitucion(mysqli $mysqli, $nombre) {
    $nombre = trim(preg_replace('/\s+/u', ' ', (string)$nombre));
    if ($nombre === "" || mb_strlen($nombre) < 3) {
        return 0;
    }
    if (mb_strlen($nombre) > 150) {
        $nombre = mb_substr($nombre, 0, 150);
    }
    $st = $mysqli->prepare("SELECT id_institucion FROM institucion WHERE LOWER(nombre) = LOWER(?) LIMIT 1");
    $st->bind_param("s", $nombre);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    if ($row) {
        return (int)$row["id_institucion"];
    }
    try {
        $ins = $mysqli->prepare("INSERT INTO institucion (nombre) VALUES (?)");
        $ins->bind_param("s", $nombre);
        $ins->execute();
        return (int)$mysqli->insert_id;
    } catch (mysqli_sql_exception $e) {
        // Carrera o duplicado por UNIQUE: devolver el existente
        $st2 = $mysqli->prepare("SELECT id_institucion FROM institucion WHERE LOWER(nombre) = LOWER(?) LIMIT 1");
        $st2->bind_param("s", $nombre);
        $st2->execute();
        $row2 = $st2->get_result()->fetch_assoc();
        return $row2 ? (int)$row2["id_institucion"] : 0;
    }
}

/**
 * Resuelve id de colegio desde select o campo "nuevo".
 */
function resolverInstitucion(mysqli $mysqli, $idSelect, $nombreNuevo) {
    $idSelect = (int)$idSelect;
    $nombreNuevo = trim((string)$nombreNuevo);
    if ($nombreNuevo !== "") {
        return obtenerOCrearInstitucion($mysqli, $nombreNuevo);
    }
    if ($idSelect > 0) {
        $st = $mysqli->prepare("SELECT id_institucion FROM institucion WHERE id_institucion=? LIMIT 1");
        $st->bind_param("i", $idSelect);
        $st->execute();
        if ($st->get_result()->fetch_assoc()) {
            return $idSelect;
        }
    }
    return 0;
}

/**
 * Datos de paginación. $porPagina por defecto 8.
 * Devuelve [page, porPagina, offset, totalPaginas].
 */
function calcularPaginacion($total, $porPagina = 8) {
    $porPagina = max(1, (int)$porPagina);
    $total = max(0, (int)$total);
    $totalPaginas = max(1, (int)ceil($total / $porPagina));
    $page = max(1, (int)($_GET["page"] ?? 1));
    if ($page > $totalPaginas) $page = $totalPaginas;
    $offset = ($page - 1) * $porPagina;
    return [
        "page" => $page,
        "por_pagina" => $porPagina,
        "offset" => $offset,
        "total_paginas" => $totalPaginas,
        "total" => $total,
    ];
}

/**
 * HTML de paginación. $extraQuery es array de query params a conservar.
 */
function renderPaginacion(array $pag, array $extraQuery = []) {
    if ($pag["total_paginas"] <= 1) return "";
    $page = $pag["page"];
    $totalPaginas = $pag["total_paginas"];
    unset($extraQuery["page"]);
    $base = "?" . http_build_query($extraQuery);
    $base = $base === "?" ? "?page=" : $base . "&page=";
    $html = '<nav class="paginacion" aria-label="Paginación">';
    if ($page > 1) {
        $html .= '<a class="btn btn-ghost" href="' . e($base . ($page - 1)) . '">← Anterior</a>';
    } else {
        $html .= '<span class="btn btn-ghost disabled">← Anterior</span>';
    }
    $html .= '<span class="paginacion-info">Página ' . (int)$page . ' de ' . (int)$totalPaginas . '</span>';
    if ($page < $totalPaginas) {
        $html .= '<a class="btn btn-ghost" href="' . e($base . ($page + 1)) . '">Siguiente →</a>';
    } else {
        $html .= '<span class="btn btn-ghost disabled">Siguiente →</span>';
    }
    $html .= '</nav>';
    return $html;
}
