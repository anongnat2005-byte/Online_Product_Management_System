<?php
require __DIR__ . '/helpers.php';

$q = trim($_GET['q'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$limit = 10;

// ---- GET: ดึงรายการสินค้า (ค้นหาด้วย q ได้) จาก REST API ด้วย cURL ----
$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => api_url() . '?' . http_build_query(['q' => $q, 'page' => $page, 'limit' => $limit]),
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

$result = json_decode((string) $response, true);
$data = $result['data'] ?? [];
$total = $result['total'] ?? 0;
$totalPages = $result['total_pages'] ?? 1;

$error = '';
if ($response === false) {
    $error = 'เชื่อมต่อ API ไม่ได้: ' . $curlError;
} elseif ($status >= 400) {
    $error = $result['error'] ?? "API ตอบกลับสถานะ $status";
}

$i = ($page - 1) * $limit + 1;
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ข้อมูลสินค้า</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <style>
        thead th { background: #00a86b; color: #fff; white-space: nowrap; }
        .stock-low { color: #dc3545; font-weight: 600; }
        .thumb { width: 40px; height: 40px; object-fit: cover; border-radius: 6px; background: #eee; }
    </style>
</head>
<body>
<div class="container my-4">
    <h1>ข้อมูลสินค้า</h1>

    <div class="d-flex flex-wrap align-items-center my-3">
        <a href="create.php" class="btn btn-primary mr-2 mb-2">+ เพิ่มสินค้า</a>
        <form action="import.php" method="post" class="mr-auto mb-2"
              onsubmit="return confirm('นำเข้าสินค้า 20 รายการแรกจาก DummyJSON ลงฐานข้อมูล?');">
            <button type="submit" class="btn btn-outline-secondary">นำเข้าจาก DummyJSON</button>
        </form>
        <form method="get" class="form-inline mb-2">
            <input type="search" name="q" value="<?= h($q) ?>" class="form-control mr-2"
                   placeholder="ค้นหาชื่อ รายละเอียด หรือแบรนด์">
            <button type="submit" class="btn btn-outline-primary">ค้นหา</button>
        </form>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= h($error) ?></div>
    <?php endif; ?>

    <p class="text-muted">พบสินค้า <?= (int) $total ?> รายการ</p>

    <div class="table-responsive">
        <table class="table table-striped table-bordered">
            <thead>
            <tr>
                <th>ลำดับ</th>
                <th>รหัสสินค้า</th>
                <th>ชื่อสินค้า</th>
                <th>หมวดหมู่</th>
                <th class="text-right">ราคา</th>
                <th class="text-right">จำนวน</th>
                <th>จัดการ</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$data): ?>
                <tr><td colspan="7" class="text-center text-muted">ไม่พบสินค้า</td></tr>
            <?php endif; ?>
            <?php foreach ($data as $row) { ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= h($row['id']) ?></td>
                    <td>
                        <?php if (!empty($row['thumbnail'])): ?>
                            <img class="thumb mr-2" src="<?= h($row['thumbnail']) ?>" alt="">
                        <?php endif; ?>
                        <?= h($row['title']) ?>
                        <?php if (!empty($row['brand'])): ?>
                            <small class="text-muted">(<?= h($row['brand']) ?>)</small>
                        <?php endif; ?>
                    </td>
                    <td><?= h($row['category']) ?></td>
                    <td class="text-right"><?= number_format((float) $row['price'], 2) ?></td>
                    <td class="text-right <?= $row['stock'] <= 5 ? 'stock-low' : '' ?>"><?= (int) $row['stock'] ?></td>
                    <td class="text-nowrap">
                        <a href="edit.php?id=<?= h($row['id']) ?>" class="btn btn-sm btn-outline-primary">แก้ไข</a>
                        <a href="delete.php?id=<?= h($row['id']) ?>" class="btn btn-sm btn-outline-danger"
                           onclick="return confirm('คุณต้องการลบข้อมูลสินค้านี้ใช่หรือไม่?');">ลบ</a>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
        <nav>
            <ul class="pagination justify-content-center">
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="?<?= h(http_build_query(['q' => $q, 'page' => $page - 1])) ?>">ก่อนหน้า</a>
                </li>
                <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
                    <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?<?= h(http_build_query(['q' => $q, 'page' => $p])) ?>"><?= $p ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="?<?= h(http_build_query(['q' => $q, 'page' => $page + 1])) ?>">ถัดไป</a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
</div>
</body>
</html>
