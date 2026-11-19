<?php
require __DIR__ . '/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: create.php');
    exit;
}

// สร้างตัวแปรรับข้อมูลที่ส่งมาแบบ POST (อยู่ใน product_from_post() ของ helpers.php)
// แล้วแปลงเป็น JSON ด้วย json_encode (ปลอดภัยกว่าต่อสตริงเอง เพราะไม่พังเมื่อมีเครื่องหมาย " ในข้อมูล)
$body = json_encode(product_from_post(), JSON_UNESCAPED_UNICODE);

// ---- POST: เรียก API เพิ่มสินค้า ----
$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => api_url(),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => $body,
    CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json'
    ),
));
$response = curl_exec($curl);
$status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
$curlError = curl_error($curl);
curl_close($curl);

// สำเร็จ -> popup แล้วไปหน้า index.php / ไม่สำเร็จ -> popup แล้วย้อนกลับไปแก้ฟอร์ม
[$ok, $message] = api_result($response, $status, $curlError);
alert_and_go($message, $ok ? 'index.php' : null);
