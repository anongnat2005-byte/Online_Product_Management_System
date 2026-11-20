<?php
require __DIR__ . '/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// ---- POST: เรียกตัวเชื่อม DummyJSON ให้ดึงสินค้า 20 รายการแล้วบันทึกลงฐานข้อมูลของเรา ----
$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => api_url('dummyjson.php') . '?action=import',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 60,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => json_encode(['limit' => 20, 'skip' => 0]),
    CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json'
    ),
));
$response = curl_exec($curl);
$status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
$curlError = curl_error($curl);
curl_close($curl);

[$ok, $message] = api_result($response, $status, $curlError);
if ($ok) {
    $json = json_decode($response, true);
    $message .= "\nนำเข้า " . ($json['imported'] ?? 0) . ' รายการ, ข้ามที่ซ้ำ ' . ($json['skipped'] ?? 0) . ' รายการ';
}
alert_and_go($message, 'index.php');
