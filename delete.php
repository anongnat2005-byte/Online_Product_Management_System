<?php
require __DIR__ . '/helpers.php';

// สร้างตัวแปรรับ id ที่ส่งมาลบข้อมูล
$id1 = $_GET['id'] ?? '';

// ---- DELETE: เรียก API ลบสินค้า ----
// หมายเหตุ: ลิงก์ลบในหน้า index.php มีหน้าต่างยืนยัน (confirm) ก่อนเสมอ
$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => api_url() . '?id=' . urlencode($id1),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'DELETE',
    CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json'
    ),
));
$response = curl_exec($curl);
$status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
$curlError = curl_error($curl);
curl_close($curl);

[$ok, $message] = api_result($response, $status, $curlError);
alert_and_go($message, 'index.php');
