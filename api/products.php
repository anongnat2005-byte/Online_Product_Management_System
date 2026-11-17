<?php
declare(strict_types=1);

/*
 * REST API จัดการสินค้า
 *
 *   GET    api/products.php                    รายการสินค้า (?q=คำค้น &category= &page= &limit= &sort= &order=)
 *   GET    api/products.php?id=1               รายละเอียดสินค้า
 *   GET    api/products.php?categories=1       รายชื่อหมวดหมู่
 *   POST   api/products.php                    เพิ่มสินค้าใหม่ (JSON body)
 *   PUT    api/products.php?id=1               แก้ไขข้อมูลทั้งรายการ (ต้องส่งครบ)
 *   PATCH  api/products.php?id=1               แก้ไขบางส่วน เช่น price หรือ stock
 *   DELETE api/products.php?id=1               ลบสินค้า
 */

require __DIR__ . '/db.php';

$pdo    = get_pdo($config);
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : null;

if (isset($_GET['id']) && ($id === false || $id < 1)) {
    send_error('id ต้องเป็นจำนวนเต็มบวก', 400);
}

// ---------- ตรวจสอบและทำความสะอาดข้อมูลสินค้า ----------
// $partial = true สำหรับ PATCH (ส่งเฉพาะบางฟิลด์ได้)
function validate_product(array $in, bool $partial): array
{
    $errors = [];
    $clean  = [];

    $required = ['title', 'price', 'category', 'stock'];
    if (!$partial) {
        foreach ($required as $f) {
            if (!array_key_exists($f, $in)) {
                $errors[] = "ต้องมีฟิลด์ $f";
            }
        }
    }

    if (array_key_exists('title', $in)) {
        $v = is_string($in['title']) ? trim($in['title']) : '';
        if ($v === '' || mb_strlen($v) > 255) {
            $errors[] = 'title ต้องไม่ว่างและยาวไม่เกิน 255 ตัวอักษร';
        } else {
            $clean['title'] = $v;
        }
    }

    if (array_key_exists('category', $in)) {
        $v = is_string($in['category']) ? trim($in['category']) : '';
        if ($v === '' || mb_strlen($v) > 100) {
            $errors[] = 'category ต้องไม่ว่างและยาวไม่เกิน 100 ตัวอักษร';
        } else {
            $clean['category'] = $v;
        }
    }

    if (array_key_exists('price', $in)) {
        if (!is_numeric($in['price']) || (float) $in['price'] < 0 || (float) $in['price'] > 99999999.99) {
            $errors[] = 'price ต้องเป็นตัวเลขตั้งแต่ 0 ขึ้นไป';
        } else {
            $clean['price'] = round((float) $in['price'], 2);
        }
    }

    if (array_key_exists('stock', $in)) {
        $v = filter_var($in['stock'], FILTER_VALIDATE_INT);
        if ($v === false || $v < 0) {
            $errors[] = 'stock ต้องเป็นจำนวนเต็มตั้งแต่ 0 ขึ้นไป';
        } else {
            $clean['stock'] = $v;
        }
    }

    foreach (['description' => 65000, 'brand' => 100, 'thumbnail' => 500] as $f => $max) {
        if (array_key_exists($f, $in)) {
            if ($in[$f] === null || $in[$f] === '') {
                $clean[$f] = null;
            } elseif (!is_string($in[$f]) || mb_strlen($in[$f]) > $max) {
                $errors[] = "$f ต้องเป็นข้อความและยาวไม่เกิน $max ตัวอักษร";
            } else {
                $clean[$f] = trim($in[$f]);
            }
        }
    }

    if ($errors) {
        send_error('ข้อมูลไม่ถูกต้อง', 422, ['details' => $errors]);
    }
    return $clean;
}

function find_product(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    return $row ? format_product($row) : null;
}

try {
    switch ($method) {

        // ===== GET: ดูรายการ / ค้นหา / ดูรายละเอียด =====
        case 'GET':
            if (isset($_GET['categories'])) {
                $rows = $pdo->query('SELECT DISTINCT category FROM products ORDER BY category')->fetchAll(PDO::FETCH_COLUMN);
                send_json(['data' => $rows]);
            }

            if ($id !== null) {
                $product = find_product($pdo, $id);
                if (!$product) {
                    send_error('ไม่พบสินค้า', 404);
                }
                send_json($product);
            }

            $where  = [];
            $params = [];

            $q = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
            if ($q !== '') {
                $where[]      = '(title LIKE :q1 OR description LIKE :q2 OR brand LIKE :q3)';
                $params[':q1'] = $params[':q2'] = $params[':q3'] = '%' . $q . '%';
            }

            $category = isset($_GET['category']) ? trim((string) $_GET['category']) : '';
            if ($category !== '') {
                $where[]            = 'category = :category';
                $params[':category'] = $category;
            }

            $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

            $sortable = ['id', 'title', 'price', 'category', 'stock', 'created_at'];
            $sort     = in_array($_GET['sort'] ?? '', $sortable, true) ? $_GET['sort'] : 'id';
            $order    = strtolower((string) ($_GET['order'] ?? 'asc')) === 'desc' ? 'DESC' : 'ASC';

            $limit  = max(1, min(100, (int) ($_GET['limit'] ?? 20)));
            $page   = max(1, (int) ($_GET['page'] ?? 1));
            $offset = ($page - 1) * $limit;

            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM products $whereSql");
            $countStmt->execute($params);
            $total = (int) $countStmt->fetchColumn();

            $stmt = $pdo->prepare("SELECT * FROM products $whereSql ORDER BY $sort $order LIMIT :limit OFFSET :offset");
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            send_json([
                'data'       => array_map('format_product', $stmt->fetchAll()),
                'total'      => $total,
                'page'       => $page,
                'limit'      => $limit,
                'total_pages' => (int) ceil($total / $limit),
            ]);
            break;

        // ===== POST: เพิ่มสินค้าใหม่ =====
        case 'POST':
            $data = validate_product(read_json_body(), false);
            $stmt = $pdo->prepare(
                'INSERT INTO products (title, description, price, category, stock, brand, thumbnail)
                 VALUES (:title, :description, :price, :category, :stock, :brand, :thumbnail)'
            );
            $stmt->execute([
                ':title'       => $data['title'],
                ':description' => $data['description'] ?? null,
                ':price'       => $data['price'],
                ':category'    => $data['category'],
                ':stock'       => $data['stock'],
                ':brand'       => $data['brand'] ?? null,
                ':thumbnail'   => $data['thumbnail'] ?? null,
            ]);
            $newId = (int) $pdo->lastInsertId();
            header('Location: products.php?id=' . $newId);
            send_json(['message' => 'เพิ่มสินค้าสำเร็จ', 'data' => find_product($pdo, $newId)], 201);
            break;

        // ===== PUT: แก้ไขทั้งรายการ =====
        case 'PUT':
            if ($id === null) {
                send_error('ต้องระบุ id (เช่น ?id=1)', 400);
            }
            if (!find_product($pdo, $id)) {
                send_error('ไม่พบสินค้า', 404);
            }
            $data = validate_product(read_json_body(), false);
            $stmt = $pdo->prepare(
                'UPDATE products
                 SET title = :title, description = :description, price = :price,
                     category = :category, stock = :stock, brand = :brand, thumbnail = :thumbnail
                 WHERE id = :id'
            );
            $stmt->execute([
                ':title'       => $data['title'],
                ':description' => $data['description'] ?? null,
                ':price'       => $data['price'],
                ':category'    => $data['category'],
                ':stock'       => $data['stock'],
                ':brand'       => $data['brand'] ?? null,
                ':thumbnail'   => $data['thumbnail'] ?? null,
                ':id'          => $id,
            ]);
            send_json(['message' => 'แก้ไขสินค้าสำเร็จ', 'data' => find_product($pdo, $id)]);
            break;

        // ===== PATCH: แก้ไขบางส่วน =====
        case 'PATCH':
            if ($id === null) {
                send_error('ต้องระบุ id (เช่น ?id=1)', 400);
            }
            if (!find_product($pdo, $id)) {
                send_error('ไม่พบสินค้า', 404);
            }
            $data = validate_product(read_json_body(), true);
            if (!$data) {
                send_error('ไม่มีฟิลด์ที่ต้องการแก้ไข', 400);
            }
            $sets   = [];
            $params = [':id' => $id];
            foreach ($data as $field => $value) {
                // $field มาจาก validate_product ซึ่งจำกัดชื่อฟิลด์ไว้แล้ว จึงปลอดภัยต่อ SQL injection
                $sets[]            = "$field = :$field";
                $params[":$field"] = $value;
            }
            $stmt = $pdo->prepare('UPDATE products SET ' . implode(', ', $sets) . ' WHERE id = :id');
            $stmt->execute($params);
            send_json(['message' => 'แก้ไขข้อมูลบางส่วนสำเร็จ', 'data' => find_product($pdo, $id)]);
            break;

        // ===== DELETE: ลบสินค้า =====
        case 'DELETE':
            if ($id === null) {
                send_error('ต้องระบุ id (เช่น ?id=1)', 400);
            }
            if (!find_product($pdo, $id)) {
                send_error('ไม่พบสินค้า', 404);
            }
            $stmt = $pdo->prepare('DELETE FROM products WHERE id = :id');
            $stmt->execute([':id' => $id]);
            send_json(['message' => 'ลบสินค้าสำเร็จ', 'id' => $id]);
            break;

        default:
            header('Allow: GET, POST, PUT, PATCH, DELETE, OPTIONS');
            send_error('ไม่รองรับ HTTP method นี้', 405);
    }
} catch (PDOException $e) {
    $extra = !empty($config['debug']) ? ['detail' => $e->getMessage()] : [];
    send_error('เกิดข้อผิดพลาดของฐานข้อมูล', 500, $extra);
}
