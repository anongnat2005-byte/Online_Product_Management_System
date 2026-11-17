# Online Product Management System

ระบบจัดการสินค้าออนไลน์ สร้างด้วย PHP + MySQL/MariaDB พร้อม REST API ครบ 5 HTTP Methods
และเชื่อมต่อ API ภายนอก [DummyJSON](https://dummyjson.com/docs/products)

| รายการ | รายละเอียด |
| --- | --- |
| ภาษา | PHP, HTML, CSS, JavaScript |
| เซิร์ฟเวอร์ | XAMPP (Apache) |
| ฐานข้อมูล | MySQL / MariaDB |
| รูปแบบข้อมูล | JSON |
| API ภายนอก | DummyJSON |
| ทดสอบ API | Postman |

## โครงสร้างโปรเจกต์

```
product-management/
├── api/
│   ├── db.php           # เชื่อมต่อฐานข้อมูล + ฟังก์ชันช่วย
│   ├── products.php     # REST API (GET, POST, PUT, PATCH, DELETE)
│   └── dummyjson.php    # ตัวเชื่อม DummyJSON
├── assets/
│   ├── app.js
│   └── style.css
├── config.example.php   # ตัวอย่างไฟล์ตั้งค่า
├── database.sql         # สร้างฐานข้อมูล + ตาราง + ข้อมูลตัวอย่าง
└── index.html           # หน้าเว็บจัดการสินค้า
```

## วิธีติดตั้ง

1. วางโฟลเดอร์โปรเจกต์ไว้ใน `C:\xampp\htdocs\product-management`
2. เปิด **XAMPP Control Panel** แล้วกด Start ที่ **Apache** และ **MySQL**
3. เปิด <http://localhost/phpmyadmin/> > แท็บ **Import** > เลือกไฟล์ `database.sql` > **Import**
   (จะได้ฐานข้อมูล `product_db` และตาราง `products`)
4. คัดลอก `config.example.php` เป็น `config.php` แล้วแก้ค่าให้ตรงกับเครื่อง
   (XAMPP ค่าเริ่มต้นคือ user `root` ไม่มีรหัสผ่าน)
5. เปิดเว็บที่ <http://localhost/product-management/>

> ถ้าดึงข้อมูลจาก DummyJSON แล้วเจอ error SSL บน XAMPP ให้ตั้ง `'verify_ssl' => false` ใน `config.php`
> (ใช้เฉพาะตอนพัฒนาในเครื่องตัวเอง)

## REST API

Base URL: `http://localhost/product-management/api/products.php`

| Method | URL | หน้าที่ | Status |
| --- | --- | --- | --- |
| GET | `products.php` | รายการสินค้า (รองรับ `q`, `category`, `page`, `limit`, `sort`, `order`) | 200 |
| GET | `products.php?id=1` | รายละเอียดสินค้า | 200 / 404 |
| GET | `products.php?categories=1` | รายชื่อหมวดหมู่ | 200 |
| POST | `products.php` | เพิ่มสินค้าใหม่ | 201 / 422 |
| PUT | `products.php?id=1` | แก้ไขทั้งรายการ (ต้องส่ง title, price, category, stock) | 200 / 404 / 422 |
| PATCH | `products.php?id=1` | แก้ไขบางส่วน เช่น price หรือ stock | 200 / 404 / 422 |
| DELETE | `products.php?id=1` | ลบสินค้า | 200 / 404 |

### ตัวอย่างการทดสอบใน Postman

**POST** เพิ่มสินค้า (Body > raw > JSON)

```json
{
  "title": "Keyboard",
  "price": 35.5,
  "category": "accessories",
  "stock": 40,
  "brand": "Keychron"
}
```

**PUT** `?id=1` แก้ไขทั้งรายการ

```json
{
  "title": "Keyboard Pro",
  "price": 49.99,
  "category": "accessories",
  "stock": 30
}
```

**PATCH** `?id=1` แก้เฉพาะราคาและจำนวน

```json
{ "price": 29.99, "stock": 55 }
```

**GET ค้นหา** `products.php?q=phone&category=smartphones&page=1&limit=10`

**DELETE** `products.php?id=1`

## เชื่อมต่อ DummyJSON

| Method | URL | หน้าที่ |
| --- | --- | --- |
| GET | `dummyjson.php?action=list&limit=10&skip=0` | ดึงรายการสินค้าจาก DummyJSON |
| GET | `dummyjson.php?action=search&q=phone` | ค้นหาสินค้าใน DummyJSON |
| GET | `dummyjson.php?action=get&id=1` | ดึงสินค้าชิ้นเดียว |
| POST | `dummyjson.php?action=import` | นำเข้าลงฐานข้อมูลของเรา Body: `{"limit": 20, "skip": 0}` (ข้ามชื่อที่ซ้ำ) |

ข้อมูลที่เพิ่ม แก้ไข และลบ จะถูกบันทึกลงฐานข้อมูล `product_db` จริง ไม่ใช่แค่จำลองผลลัพธ์จาก DummyJSON

## ความปลอดภัย

- ใช้ PDO + prepared statements ป้องกัน SQL injection
- ตรวจสอบข้อมูลฝั่งเซิร์ฟเวอร์ (ชนิดข้อมูล ความยาว ค่าติดลบ)
- `config.php` ไม่ถูกอัปโหลดขึ้น Git (อยู่ใน `.gitignore`)
