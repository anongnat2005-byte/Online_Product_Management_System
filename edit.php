<?php
require __DIR__ . '/helpers.php';

// รับ id ที่ส่งมากับ URL
$id1 = $_GET['id'] ?? '';
if (!ctype_digit((string) $id1) || (int) $id1 < 1) {
    alert_and_go('รหัสสินค้าไม่ถูกต้อง');
}

// ---- GET: ดึงข้อมูลสินค้าชิ้นเดียวจาก API มาแสดงในฟอร์ม ----
$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => api_url() . '?id=' . urlencode($id1),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'GET',
));
$response = curl_exec($curl);
$status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
$curlError = curl_error($curl);
curl_close($curl);

$data = json_decode((string) $response, true);
if ($response === false || $status !== 200 || !is_array($data)) {
    [, $message] = api_result($response, $status, $curlError);
    alert_and_go('โหลดข้อมูลสินค้าไม่สำเร็จ: ' . $message);
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>แก้ไขข้อมูลสินค้า</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container my-4">
    <h2>แก้ไขข้อมูลสินค้า</h2>
    <!-- ส่งข้อมูลแบบ POST ไปที่ callEdit.php ซึ่งจะเรียก API ต่อ -->
    <form action="callEdit.php" method="post">
        <div class="form-group">
            <label for="id">ID:</label>
            <input type="text" class="form-control" id="id" name="id" value="<?= h($data['id']) ?>" readonly>
        </div>
        <div class="form-group">
            <label for="title">ชื่อสินค้า:</label>
            <input type="text" class="form-control" id="title" name="title" maxlength="255"
                   value="<?= h($data['title']) ?>" required>
        </div>
        <div class="form-group">
            <label for="category">หมวดหมู่:</label>
            <input type="text" class="form-control" id="category" name="category" maxlength="100"
                   value="<?= h($data['category']) ?>" required>
        </div>
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="price">ราคา:</label>
                <input type="number" class="form-control" id="price" name="price" min="0" step="0.01"
                       value="<?= h($data['price']) ?>" required>
            </div>
            <div class="form-group col-md-6">
                <label for="stock">จำนวนสินค้า:</label>
                <input type="number" class="form-control" id="stock" name="stock" min="0" step="1"
                       value="<?= h($data['stock']) ?>" required>
            </div>
        </div>
        <div class="form-group">
            <label for="brand">แบรนด์:</label>
            <input type="text" class="form-control" id="brand" name="brand" maxlength="100"
                   value="<?= h($data['brand'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="description">รายละเอียด:</label>
            <textarea class="form-control" id="description" name="description" rows="3"><?= h($data['description'] ?? '') ?></textarea>
        </div>
        <div class="form-group">
            <label for="thumbnail">URL รูปภาพ:</label>
            <input type="url" class="form-control" id="thumbnail" name="thumbnail" maxlength="500"
                   value="<?= h($data['thumbnail'] ?? '') ?>">
        </div>
        <button type="submit" class="btn btn-primary">Submit</button>
        <input type="reset" class="btn btn-secondary" value="Reset">
        <a href="index.php" class="btn btn-link">กลับหน้ารายการ</a>
    </form>
</div>
</body>
</html>
