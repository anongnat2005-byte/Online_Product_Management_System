<?php
// ตัวช่วยที่ทุกหน้าใช้ร่วมกัน

// ที่อยู่ของ API คำนวณจากหน้าปัจจุบัน จึงใช้ได้ทุกชื่อโฟลเดอร์/พอร์ต
// เช่น http://localhost/Online_Product_Management_System/api/products.php
function api_url(string $file = 'products.php'): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
    return $scheme . '://' . $_SERVER['HTTP_HOST'] . $dir . '/api/' . $file;
}

// ป้องกัน XSS ก่อนแสดงข้อมูลในหน้าเว็บ
function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// แสดง popup alert แล้วไปหน้าอื่น ($target = null คือย้อนกลับหน้าเดิม)
function alert_and_go(string $message, ?string $target = 'index.php'): void
{
    $flags = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
    $msg = json_encode($message, $flags);
    $go = $target === null
        ? 'history.back();'
        : 'window.location.href = ' . json_encode($target, $flags) . ';';
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="th"><head><meta charset="UTF-8"><title>กำลังดำเนินการ</title></head><body>';
    echo "<script>alert($msg); $go</script>";
    echo '</body></html>';
    exit;
}

// แปลงผลตอบกลับจาก API เป็น [สำเร็จหรือไม่, ข้อความ]
function api_result($response, int $status, string $curlError = ''): array
{
    if ($response === false) {
        return [false, 'เชื่อมต่อ API ไม่ได้: ' . $curlError];
    }
    $json = json_decode($response, true);
    $ok = $status >= 200 && $status < 300;
    $msg = $json['message'] ?? $json['error'] ?? ('HTTP ' . $status);
    if (!$ok && !empty($json['details'])) {
        $msg .= "\n- " . implode("\n- ", $json['details']);
    }
    return [$ok, $msg . " (HTTP $status)"];
}

// อ่านค่าจากฟอร์ม (POST) เป็นข้อมูลสินค้า ส่งค่าดิบไปให้ API ตรวจสอบเอง
function product_from_post(): array
{
    return [
        'title'       => trim($_POST['title'] ?? ''),
        'category'    => trim($_POST['category'] ?? ''),
        'price'       => $_POST['price'] ?? '',
        'stock'       => $_POST['stock'] ?? '',
        'brand'       => trim($_POST['brand'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'thumbnail'   => trim($_POST['thumbnail'] ?? ''),
    ];
}
