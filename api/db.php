<?php
declare(strict_types=1);

// ---------- ตั้งค่า header สำหรับ API (JSON + CORS) ----------
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ---------- ฟังก์ชันช่วย ----------
function send_json($data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
    exit;
}

function send_error(string $message, int $status = 400, array $extra = []): void
{
    send_json(array_merge(['error' => $message], $extra), $status);
}

function read_json_body(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return [];
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        send_error('รูปแบบ JSON ไม่ถูกต้อง', 400);
    }
    return $data;
}

// ---------- โหลดค่าตั้งค่า ----------
$configFile = __DIR__ . '/../config.php';
if (!file_exists($configFile)) {
    send_error('ไม่พบไฟล์ config.php ให้คัดลอกจาก config.example.php', 500);
}
$config = require $configFile;

// ---------- เชื่อมต่อฐานข้อมูล ----------
function get_pdo(array $config): PDO
{
    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        $config['db_host'],
        $config['db_name'],
        $config['db_charset']
    );
    try {
        return new PDO($dsn, $config['db_user'], $config['db_pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        $extra = !empty($config['debug']) ? ['detail' => $e->getMessage()] : [];
        send_error('เชื่อมต่อฐานข้อมูลไม่ได้ (เปิด MySQL ใน XAMPP และสร้าง product_db แล้วหรือยัง?)', 500, $extra);
    }
}

// แปลงแถวจากฐานข้อมูลให้ type ถูกต้องก่อนส่งออกเป็น JSON
function format_product(array $row): array
{
    $row['id']    = (int) $row['id'];
    $row['price'] = (float) $row['price'];
    $row['stock'] = (int) $row['stock'];
    return $row;
}
