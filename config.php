<?php
// ============================================================
// QuickHire - Konfiguráció (config.php)
// Ezt a fájlt MINDEN API végpont betölti.
// ============================================================

// --- Adatbázis beállítások (XAMPP alapértelmezés) ---
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "quickhire";

// Session indítása
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CORS fejlécek (hogy a frontend elérhesse az API-t)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// OPTIONS preflight kérés kezelése
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Adatbázis kapcsolat
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    http_response_code(500);
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode(["success" => false, "message" => "Adatbazis kapcsolat sikertelen."]);
    exit;
}

$conn->set_charset("utf8mb4");

// ============================================================
// Segédfüggvények
// ============================================================

/**
 * JSON válasz küldése és script leállítása.
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Ellenőrzi, hogy a felhasználó be van-e jelentkezve.
 */
function requireLogin() {
    if (empty($_SESSION['user_id']) || empty($_SESSION['user_role'])) {
        jsonResponse(["success" => false, "message" => "Jelentkezz be a folytatáshoz."], 401);
    }
}

/**
 * Ellenőrzi, hogy munkáltató van-e bejelentkezve.
 */
function requireEmployer() {
    requireLogin();
    if ($_SESSION['user_role'] !== 'employer') {
        jsonResponse(["success" => false, "message" => "Csak munkáltatók férhetnek hozzá."], 403);
    }
}

/**
 * Ellenőrzi, hogy munkavállaló van-e bejelentkezve.
 */
function requireEmployee() {
    requireLogin();
    if ($_SESSION['user_role'] !== 'employee') {
        jsonResponse(["success" => false, "message" => "Csak munkavállalók férhetnek hozzá."], 403);
    }
}

/**
 * Ellenőrzi, hogy admin van-e bejelentkezve.
 */
function requireAdmin() {
    requireLogin();
    if ($_SESSION['user_role'] !== 'admin') {
        jsonResponse(["success" => false, "message" => "Csak adminisztrátorok férhetnek hozzá."], 403);
    }
}

/**
 * JSON adatot olvas a kérés body-jából.
 */
function getJsonInput() {
    $raw = file_get_contents("php://input");
    $input = json_decode($raw, true);
    if (!is_array($input)) {
        jsonResponse(["success" => false, "message" => "Hibás vagy hiányzó JSON adat."], 400);
    }
    return $input;
}
