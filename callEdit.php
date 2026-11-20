<?php
require __DIR__ . '/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// สร้างตัวแปรรับข้อมูลที่ส่งมาแบบ POST
$id1 = $_POST['id'] ?? '';
$body = json_encode(product_from_post(), JSON_UNESCAPED_UNICODE);

// ---- PATCH: เรียก API แก้ไขสินค้า (API ของเราระบุ id ที่ URL) ----
// ถ้าต้องการแก้ทั้งรายการตามหลัก REST ให้เปลี่ยน 'PATCH' เป็น 'PUT' ได้ทันที
$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => api_url() . '?id=' . urlencode($id1),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'PATCH',
    CURLOPT_POSTFIELDS => $body,
    CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json'
    ),
));
$response = curl_exec($curl);
$status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
$curlError = curl_error($curl);
curl_close($curl);

[$ok, $message] = api_result($response, $status, $curlError);
alert_and_go($message, $ok ? 'index.php' : null);
