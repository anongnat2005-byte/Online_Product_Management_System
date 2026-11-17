<?php
declare(strict_types=1);

/*
 * ตัวเชื่อม DummyJSON (API ภายนอก) https://dummyjson.com/docs/products
 *
 *   GET  api/dummyjson.php?action=list&limit=10&skip=0   ดึงรายการสินค้า
 *   GET  api/dummyjson.php?action=search&q=phone         ค้นหาสินค้า
 *   GET  api/dummyjson.php?action=get&id=1               ดึงสินค้าชิ้นเดียว
 *   POST api/dummyjson.php?action=import                 นำเข้าลงฐานข้อมูลของเรา
 *        body (JSON): {"limit": 20, "skip": 0}
 */

require __DIR__ . '/db.php';

const DUMMYJSON_BASE = 'https://dummyjson.com/products';
const DUMMYJSON_FIELDS = 'id,title,description,price,category,stock,brand,thumbnail';

function dummyjson_fetch(string $url, array $config): array
{
    if (!function_exists('curl_init')) {
        send_error('PHP ยังไม่เปิดใช้ extension curl (เปิด extension=curl ใน php.ini)', 500);
    }
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => $config['verify_ssl'] ?? true,
        CURLOPT_SSL_VERIFYHOST => ($config['verify_ssl'] ?? true) ? 2 : 0,
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
    ]);
    $body   = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err    = curl_error($ch);
    curl_close($ch);

    if ($body === false) {
        send_error('เรียก DummyJSON ไม่สำเร็จ', 502, ['detail' => $err]);
    }
    if ($status === 404) {
        send_error('ไม่พบข้อมูลใน DummyJSON', 404);
    }
    if ($status >= 400) {
        send_error("DummyJSON ตอบกลับสถานะ $status", 502);
    }
    $data = json_decode($body, true);
    if (!is_array($data)) {
        send_error('DummyJSON ส่งข้อมูลที่อ่านไม่ได้', 502);
    }
    return $data;
}

// เก็บเฉพาะฟิลด์ที่เราใช้
function simplify(array $p): array
{
    return [
        'dummyjson_id' => $p['id'] ?? null,
        'title'        => $p['title'] ?? '',
        'description'  => $p['description'] ?? null,
        'price'        => (float) ($p['price'] ?? 0),
        'category'     => $p['category'] ?? 'uncategorized',
        'stock'        => (int) ($p['stock'] ?? 0),
        'brand'        => $p['brand'] ?? null,
        'thumbnail'    => $p['thumbnail'] ?? null,
    ];
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';

if ($method === 'GET') {
    switch ($action) {
        case 'list':
            $limit = max(1, min(100, (int) ($_GET['limit'] ?? 10)));
            $skip  = max(0, (int) ($_GET['skip'] ?? 0));
            $url   = DUMMYJSON_BASE . '?' . http_build_query(['limit' => $limit, 'skip' => $skip, 'select' => DUMMYJSON_FIELDS]);
            $data  = dummyjson_fetch($url, $config);
            send_json([
                'data'  => array_map('simplify', $data['products'] ?? []),
                'total' => $data['total'] ?? 0,
            ]);
            break;

        case 'search':
            $q = trim((string) ($_GET['q'] ?? ''));
            if ($q === '') {
                send_error('ต้องระบุคำค้น q', 400);
            }
            $url  = DUMMYJSON_BASE . '/search?' . http_build_query(['q' => $q, 'limit' => 30, 'select' => DUMMYJSON_FIELDS]);
            $data = dummyjson_fetch($url, $config);
            send_json([
                'data'  => array_map('simplify', $data['products'] ?? []),
                'total' => $data['total'] ?? 0,
            ]);
            break;

        case 'get':
            $pid = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
            if ($pid === false || $pid < 1) {
                send_error('id ไม่ถูกต้อง', 400);
            }
            $data = dummyjson_fetch(DUMMYJSON_BASE . '/' . $pid . '?select=' . DUMMYJSON_FIELDS, $config);
            send_json(simplify($data));
            break;

        default:
            send_error('action ไม่ถูกต้อง (list, search, get, import)', 400);
    }
}

if ($method === 'POST' && $action === 'import') {
    $body  = read_json_body();
    $limit = max(1, min(100, (int) ($body['limit'] ?? 20)));
    $skip  = max(0, (int) ($body['skip'] ?? 0));

    $url  = DUMMYJSON_BASE . '?' . http_build_query(['limit' => $limit, 'skip' => $skip, 'select' => DUMMYJSON_FIELDS]);
    $data = dummyjson_fetch($url, $config);

    $pdo      = get_pdo($config);
    $exists   = $pdo->prepare('SELECT COUNT(*) FROM products WHERE title = :title');
    $insert   = $pdo->prepare(
        'INSERT INTO products (title, description, price, category, stock, brand, thumbnail)
         VALUES (:title, :description, :price, :category, :stock, :brand, :thumbnail)'
    );
    $imported = 0;
    $skipped  = 0;

    try {
        foreach ($data['products'] ?? [] as $raw) {
            $p = simplify($raw);
            $exists->execute([':title' => $p['title']]);
            if ((int) $exists->fetchColumn() > 0) {
                $skipped++;   // มีชื่อซ้ำอยู่แล้ว ข้ามเพื่อไม่ให้ข้อมูลซ้ำ
                continue;
            }
            $insert->execute([
                ':title'       => $p['title'],
                ':description' => $p['description'],
                ':price'       => $p['price'],
                ':category'    => $p['category'],
                ':stock'       => $p['stock'],
                ':brand'       => $p['brand'],
                ':thumbnail'   => $p['thumbnail'],
            ]);
            $imported++;
        }
    } catch (PDOException $e) {
        $extra = !empty($config['debug']) ? ['detail' => $e->getMessage()] : [];
        send_error('บันทึกข้อมูลที่นำเข้าไม่สำเร็จ', 500, $extra);
    }

    send_json(['message' => 'นำเข้าข้อมูลจาก DummyJSON สำเร็จ', 'imported' => $imported, 'skipped' => $skipped], 201);
}

header('Allow: GET, POST, OPTIONS');
send_error('ไม่รองรับคำขอนี้', 405);
