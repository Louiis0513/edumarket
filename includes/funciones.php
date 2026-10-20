<?php
function e($texto) {
    return htmlspecialchars((string)$texto, ENT_QUOTES, "UTF-8");
}
function usuarioLogueado() { return isset($_SESSION["usuario"]); }
function usuarioActual() { return $_SESSION["usuario"] ?? null; }
function requiereUsuario() {
    if (!usuarioLogueado()) {
        guardarMensaje("Inicia sesión para continuar.");
        header("Location: login.php");
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
    ];
    return $map[$fallbackCat] ?? "img/hero-utiles.jpg";
}
function etiquetaTipo($tipo) {
    return $tipo === "intercambio" ? "Intercambio" : "Venta";
}
