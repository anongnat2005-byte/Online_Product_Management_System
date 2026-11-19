<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>เพิ่มข้อมูลสินค้า</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container my-4">
    <h2>เพิ่มข้อมูลสินค้า</h2>
    <!-- ส่งข้อมูลแบบ POST ไปที่ callInsert.php ซึ่งจะเรียก API ต่อ -->
    <form action="callInsert.php" method="post">
        <div class="form-group">
            <label for="title">ชื่อสินค้า:</label>
            <input type="text" class="form-control" id="title" name="title" maxlength="255"
                   placeholder="Enter product name" required>
        </div>
        <div class="form-group">
            <label for="category">หมวดหมู่:</label>
            <input type="text" class="form-control" id="category" name="category" maxlength="100"
                   placeholder="เช่น beauty, smartphones, accessories" required>
        </div>
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="price">ราคา:</label>
                <input type="number" class="form-control" id="price" name="price" min="0" step="0.01"
                       placeholder="Enter price" required>
            </div>
            <div class="form-group col-md-6">
                <label for="stock">จำนวนสินค้า:</label>
                <input type="number" class="form-control" id="stock" name="stock" min="0" step="1"
                       placeholder="Enter stock" required>
            </div>
        </div>
        <div class="form-group">
            <label for="brand">แบรนด์:</label>
            <input type="text" class="form-control" id="brand" name="brand" maxlength="100"
                   placeholder="Enter brand">
        </div>
        <div class="form-group">
            <label for="description">รายละเอียด:</label>
            <textarea class="form-control" id="description" name="description" rows="3"
                      placeholder="Enter description"></textarea>
        </div>
        <div class="form-group">
            <label for="thumbnail">URL รูปภาพ:</label>
            <input type="url" class="form-control" id="thumbnail" name="thumbnail" maxlength="500"
                   placeholder="https://...">
        </div>
        <button type="submit" class="btn btn-primary">Submit</button>
        <input type="reset" class="btn btn-secondary" value="Reset">
        <a href="index.php" class="btn btn-link">กลับหน้ารายการ</a>
    </form>
</div>
</body>
</html>
