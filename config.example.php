<?php
// คัดลอกไฟล์นี้เป็น config.php แล้วแก้ค่าให้ตรงกับเครื่องของคุณ
// (config.php ถูกใส่ใน .gitignore จึงไม่ถูกอัปโหลดขึ้น GitHub)
return [
    'db_host'     => 'localhost',
    'db_name'     => 'product_db',
    'db_user'     => 'root',   // XAMPP ค่าเริ่มต้น
    'db_pass'     => '',       // XAMPP ค่าเริ่มต้นคือว่าง
    'db_charset'  => 'utf8mb4',

    // ตั้งเป็น false เฉพาะกรณีเรียก DummyJSON แล้วเจอ error SSL บน XAMPP
    'verify_ssl'  => true,

    // แสดงรายละเอียด error ของฐานข้อมูลใน JSON (ปิดเมื่อใช้งานจริง)
    'debug'       => true,
];
