-- ============================================================
-- ฐานข้อมูลร้านเช่ากระเป๋า (THE BRANDNAME RENTAL)
-- วิธีใช้: สร้างฐานข้อมูลเปล่า (utf8mb4) แล้ว import ไฟล์นี้
-- ชื่อตารางเป็นตัวพิมพ์เล็กทั้งหมด เพื่อให้ใช้ได้ทั้งบน Windows และ Linux
--
-- บัญชีทดลอง
--   ลูกค้า: chaiya@gmail.com (และลูกค้าตัวอย่างทุกคน) รหัสผ่าน demo1234
--   แอดมิน: admin รหัสผ่าน Admin@1234
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS membership_requests, track_return, returnss, rentals, orders, stores, products, employees, admins, customers;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE customers (
    customers_id        VARCHAR(50)  PRIMARY KEY,
    customers_firstname VARCHAR(50),
    customers_lastname  VARCHAR(50),
    gender              VARCHAR(50),
    customers_member    VARCHAR(10)  NOT NULL DEFAULT 'no',   -- yes = เป็นสมาชิก, no = ไม่เป็นสมาชิก
    member_until        DATE         NULL,                    -- วันหมดอายุสมาชิก (สมาชิก 1 ปี)
    phonenumber         VARCHAR(15),
    email               VARCHAR(100) NOT NULL UNIQUE,
    address             VARCHAR(200),
    postal_code         VARCHAR(10),
    password            VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ลูกค้าตัวอย่าง: รหัสผ่าน demo1234 ทุกบัญชี
INSERT INTO customers (customers_id, customers_firstname, customers_lastname, gender, customers_member, member_until, phonenumber, email, address, postal_code, password) VALUES
('C001', 'Chaiya',    'Bunmak',      'Male',   'no',  NULL,         '081-1112233', 'chaiya@gmail.com',    '101/2 Moo 4, Khon Kaen City, Khon Kaen',               '40000', '$2y$10$R7Cuh.6HQqNg5tjkHXgex.su4mO01OrFhcuvh1KS6rtZ.Qmj.UaVa'),
('C002', 'Siriwan',   'Chatree',     'Female', 'no',  NULL,         '082-2223344', 'siriwan@gmail.com',   '50/5 Soi Ladprao 71, Bangkok',                          '10310', '$2y$10$R7Cuh.6HQqNg5tjkHXgex.su4mO01OrFhcuvh1KS6rtZ.Qmj.UaVa'),
('C003', 'Thanawat',  'Phrommarach', 'Male',   'yes', '2027-12-31', '083-3334455', 'thanawat@gmail.com',  '202/3 Chiang Rai City, Chiang Rai',                     '57000', '$2y$10$R7Cuh.6HQqNg5tjkHXgex.su4mO01OrFhcuvh1KS6rtZ.Qmj.UaVa'),
('C004', 'Natcha',    'Intarachai',  'Female', 'yes', '2027-12-31', '084-4445566', 'natcha@gmail.com',    '58/9 Moo 7, Udon Thani City, Udon Thani',               '41000', '$2y$10$R7Cuh.6HQqNg5tjkHXgex.su4mO01OrFhcuvh1KS6rtZ.Qmj.UaVa'),
('C005', 'Jirapat',   'Suthiphong',  'Male',   'no',  NULL,         '085-5556677', 'jirapat@gmail.com',   '40/1 Sukhumvit 11, Bangkok',                            '10110', '$2y$10$R7Cuh.6HQqNg5tjkHXgex.su4mO01OrFhcuvh1KS6rtZ.Qmj.UaVa'),
('C006', 'Patcharee', 'Punsiri',     'Female', 'no',  NULL,         '086-6667788', 'patcharee@gmail.com', '3/2 Moo 1, Hua Hin, Prachuap Khiri Khan',               '77110', '$2y$10$R7Cuh.6HQqNg5tjkHXgex.su4mO01OrFhcuvh1KS6rtZ.Qmj.UaVa'),
('C007', 'Somyot',    'Charoenrat',  'Male',   'yes', '2027-12-31', '087-7778899', 'somyot@gmail.com',    '75 Moo 5, Surat Thani City, Surat Thani',               '84000', '$2y$10$R7Cuh.6HQqNg5tjkHXgex.su4mO01OrFhcuvh1KS6rtZ.Qmj.UaVa'),
('C008', 'Manat',     'Sangthong',   'Male',   'yes', '2027-12-31', '088-8889900', 'manat@gmail.com',     '99/9 Moo 8, Nonthaburi City, Nonthaburi',               '11000', '$2y$10$R7Cuh.6HQqNg5tjkHXgex.su4mO01OrFhcuvh1KS6rtZ.Qmj.UaVa'),
('C009', 'Nantida',   'Wongkham',    'Female', 'yes', '2027-12-31', '089-9990011', 'nantida@gmail.com',   '123/45 Chiang Mai City, Chiang Mai',                    '50000', '$2y$10$R7Cuh.6HQqNg5tjkHXgex.su4mO01OrFhcuvh1KS6rtZ.Qmj.UaVa'),
('C010', 'Somporn',   'Prasertsak',  'Male',   'no',  NULL,         '080-0001122', 'somporn@gmail.com',   '10/20 Moo 6, Ubon Ratchathani City, Ubon Ratchathani', '34000', '$2y$10$R7Cuh.6HQqNg5tjkHXgex.su4mO01OrFhcuvh1KS6rtZ.Qmj.UaVa');

-- ผู้ดูแลระบบ: รหัสผ่านเก็บแบบ hash (บัญชีทดลอง admin / Admin@1234)
CREATE TABLE admins (
    admin_id  INT AUTO_INCREMENT PRIMARY KEY,
    username  VARCHAR(50)  NOT NULL UNIQUE,
    password  VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO admins (username, password) VALUES
('admin', '$2y$10$qnDXElMItl3SphTGaZmw.esvHTLIaXijMe2w5zyc4wcpnxWWCBzNG');

CREATE TABLE employees (
    employees_id        VARCHAR(50) PRIMARY KEY,
    employees_firstname VARCHAR(50),
    employees_lastname  VARCHAR(50),
    gender              VARCHAR(50),
    department          VARCHAR(50) NOT NULL,
    phonenumber         VARCHAR(15),
    salary              INT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO employees (employees_id, employees_firstname, employees_lastname, gender, department, phonenumber, salary) VALUES
('E001', 'Kittipong',  'Namsomti',  'Male',   'Sales Representation', '091-2345678', 22000),
('E002', 'Sawidtee',   'Panit',     'Female', 'Sales Representation', '092-3456789', 25000),
('E003', 'Chonthicha', 'Damrong',   'Female', 'Admin',                '093-4567890', 22000),
('E004', 'Surachai',   'Thongkham', 'Male',   'Sales Representation', '094-5678901', 25000);

CREATE TABLE products (
    product_id         VARCHAR(50) PRIMARY KEY,
    brand              VARCHAR(50),
    details            VARCHAR(100),
    daily_rental_price INT,
    image              VARCHAR(100) NULL   -- ชื่อไฟล์รูปใน assets/images/products/ (ไม่มีรูปจะแสดงรูปตัวแทน)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO products (product_id, brand, details, daily_rental_price, image) VALUES
('PD0001', 'Chanel',    '22 Bag Medium in Black',           4100, 'PD0001.png'),
('PD0002', 'Chanel',    'Classic 10 Black Silver Hardwear', 3900, 'PD0002.png'),
('PD0003', 'Celine',    'Ava Triomphe',                     2300, 'PD0003.png'),
('PD0004', 'Celine',    'Pico Belt Bag',                    1900, 'PD0004.png'),
('PD0005', 'DIOR',      'LADY D JOY SMALL',                 3300, 'PD0005.png'),
('PD0006', 'Lady Dior', 'Medium Shiny Calf in Black',       2800, 'PD0006.png'),
('PD0007', 'Goyard',    'Structure Mini Saigon in White',   3550, 'PD0007.png'),
('PD0008', 'Hermes',    'Kelly 28Gris T GHW',               5200, 'PD0008.png'),
('PD0009', 'Prada',     'Alma mini',                        1500, 'PD0009.png'),
('PD0010', 'YSL',       'Medium College in CROC',           1900, 'PD0010.png');

-- คำสั่งเช่า: การชำระเงินหนึ่งครั้ง (ตะกร้าหนึ่งครั้ง) มีกระเป๋าได้หลายใบในตาราง rentals
-- ลูกค้าแนบสลิปตอนสั่ง ร้านตรวจสลิปแล้วอนุมัติหรือปฏิเสธทั้งคำสั่ง
CREATE TABLE orders (
    order_id     VARCHAR(50)  PRIMARY KEY,
    customers_id VARCHAR(50)  NOT NULL,
    slip_file    VARCHAR(100) NULL,       -- ชื่อไฟล์สลิปใน uploads/slips (ข้อมูลตัวอย่างไม่มีไฟล์สลิป)
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_at  DATETIME     NULL,       -- เวลาที่ร้านตรวจสลิป
    cancelled_by ENUM('shop', 'customer') NULL,  -- ใครยกเลิก: ร้านปฏิเสธสลิป หรือลูกค้ายกเลิกเองก่อนร้านตรวจ
    FOREIGN KEY (customers_id) REFERENCES customers(customers_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO orders (order_id, customers_id, slip_file, created_at, reviewed_at) VALUES
('ORD0001', 'C001', NULL, '2025-06-03 10:12:00', '2025-06-03 13:00:00'),
('ORD0002', 'C002', NULL, '2025-06-09 19:40:00', '2025-06-10 09:30:00'),
('ORD0003', 'C003', NULL, '2025-07-02 08:05:00', '2025-07-02 11:20:00'),
('ORD0004', 'C004', NULL, '2025-07-16 21:15:00', '2025-07-17 10:00:00'),
('ORD0005', 'C005', NULL, '2025-08-09 12:30:00', '2025-08-09 15:45:00'),
('ORD0006', 'C006', NULL, '2025-09-26 18:00:00', '2025-09-27 09:10:00'),
('ORD0007', 'C007', NULL, '2025-10-22 14:25:00', '2025-10-22 16:00:00'),
('ORD0008', 'C008', NULL, '2025-11-13 09:50:00', '2025-11-13 13:30:00'),
('ORD0009', 'C009', NULL, '2025-12-14 20:10:00', '2025-12-15 09:00:00'),
('ORD0010', 'C010', NULL, '2025-12-30 11:00:00', '2025-12-30 14:20:00'),
-- คำสั่งที่ยังไม่จบ วันที่อิงจากวันที่นำเข้าข้อมูล เว็บเดโมจึงมีตัวอย่างทุกสถานะหลังรีเซ็ต
('ORD0011', 'C001', NULL, NOW() - INTERVAL 5 DAY, NOW() - INTERVAL 5 DAY),
('ORD0012', 'C005', NULL, NOW() - INTERVAL 9 DAY, NOW() - INTERVAL 9 DAY),
('ORD0013', 'C003', NULL, NOW() - INTERVAL 1 DAY, NOW() - INTERVAL 1 DAY),
('ORD0014', 'C002', NULL, NOW() - INTERVAL 10 DAY, NOW() - INTERVAL 10 DAY);

-- การเช่า: หนึ่งแถวต่อกระเป๋าหนึ่งใบ
-- ค่ามัดจำ: สมาชิก 0 บาท, ไม่เป็นสมาชิก 900 บาทต่อใบ
-- daily_price คือราคาต่อวัน ณ วันที่สั่ง ยอดของคำสั่งเก่าจึงไม่เปลี่ยนเมื่อร้านแก้ราคาสินค้า
-- due_date (ส่งคืนภายในวันที่) = วันเริ่มเช่า + จำนวนวัน คำนวณโดยฐานข้อมูล
-- สถานะ: Pending รอตรวจสลิป → Accepted รับเรื่องแล้ว → Preparing กำลังดำเนินการ (เตรียมของ)
--        → Shipped จัดส่งแล้ว (หน้าเว็บแสดง "อยู่ระหว่างเช่า" เมื่อถึงวันเริ่มเช่า)
--        → Returning กำลังส่งคืน → Received ร้านได้รับของ รอตรวจสภาพ
--        → Completed เสร็จสมบูรณ์ คืนมัดจำเต็ม / Deducted หักมัดจำ (ยอดและเหตุผลอยู่ใน returnss)
--        Cancelled = ร้านปฏิเสธสลิป หรือลูกค้ายกเลิกเองระหว่างรอตรวจสลิป (ดู orders.cancelled_by)
-- employees_id (พนักงานที่จัดส่ง), track_rentals (เลขพัสดุ) และ shipped_date บันทึกตอนร้านจัดส่ง
CREATE TABLE rentals (
    rental_id     VARCHAR(50) PRIMARY KEY,
    order_id      VARCHAR(50) NOT NULL,
    product_id    VARCHAR(50) NOT NULL,
    employees_id  VARCHAR(50) NULL,
    track_rentals VARCHAR(50) NULL,
    rental_date   DATE        NOT NULL,
    rental_days   INT         NOT NULL,
    due_date      DATE AS (rental_date + INTERVAL rental_days DAY) STORED,
    daily_price   INT         NOT NULL,
    deposit_fee   INT         NOT NULL,
    shipped_date  DATE        NULL,
    rental_status ENUM('Pending', 'Accepted', 'Preparing', 'Shipped', 'Returning', 'Received', 'Completed', 'Deducted', 'Cancelled') NOT NULL DEFAULT 'Pending',
    INDEX idx_rentals_product_status (product_id, rental_status),
    FOREIGN KEY (order_id)     REFERENCES orders(order_id),
    FOREIGN KEY (product_id)   REFERENCES products(product_id),
    FOREIGN KEY (employees_id) REFERENCES employees(employees_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO rentals (rental_id, order_id, product_id, employees_id, track_rentals, rental_date, rental_days, daily_price, deposit_fee, shipped_date, rental_status) VALUES
('Ren1111', 'ORD0001', 'PD0001', 'E001', 'EF123456789TH', '2025-06-05', 1, 4100, 900, '2025-06-04', 'Completed'),
('Ren1112', 'ORD0002', 'PD0010', 'E002', 'EA321456789TH', '2025-06-11', 3, 1900, 900, '2025-06-10', 'Completed'),
('Ren1113', 'ORD0003', 'PD0003', 'E003', 'EE431256789TH', '2025-07-05', 1, 2300,   0, '2025-07-03', 'Completed'),
('Ren1114', 'ORD0004', 'PD0004', 'E004', 'AF789561235TH', '2025-07-19', 3, 1900,   0, '2025-07-17', 'Completed'),
('Ren1115', 'ORD0005', 'PD0009', 'E003', 'FA123785689TH', '2025-08-12', 1, 1500, 900, '2025-08-10', 'Completed'),
('Ren1116', 'ORD0006', 'PD0006', 'E001', 'FF124411789TH', '2025-09-29', 3, 2800, 900, '2025-09-27', 'Deducted'),
('Ren1117', 'ORD0007', 'PD0007', 'E002', 'EF123451169TH', '2025-10-25', 1, 3550,   0, '2025-10-23', 'Completed'),
('Ren1118', 'ORD0008', 'PD0008', 'E002', 'FE112556789TH', '2025-11-16', 3, 5200,   0, '2025-11-14', 'Completed'),
('Ren1119', 'ORD0009', 'PD0002', 'E004', 'TA897456789TH', '2025-12-17', 1, 3900,   0, '2025-12-15', 'Completed'),
('Ren1120', 'ORD0010', 'PD0005', 'E001', 'TH985256789TH', '2026-01-02', 3, 3300, 900, '2025-12-31', 'Completed'),
-- อยู่ระหว่างเช่า ครบกำหนดคืนพรุ่งนี้: ลูกค้าทดลอง chaiya เห็นแจ้งเตือนและแจ้งส่งคืนได้
('Ren1121', 'ORD0011', 'PD0003', 'E001', 'EF555666777TH', CURDATE() - INTERVAL 2 DAY, 3, 2300, 900, CURDATE() - INTERVAL 3 DAY, 'Shipped'),
-- เกินกำหนดคืน: แสดงในหน้าแอดมิน
('Ren1122', 'ORD0012', 'PD0001', 'E002', 'EA888999000TH', CURDATE() - INTERVAL 6 DAY, 2, 4100, 900, CURDATE() - INTERVAL 7 DAY, 'Shipped'),
-- รับเรื่องแล้ว: แอดมินทดลองเริ่มเตรียมของและจัดส่งได้
('Ren1123', 'ORD0013', 'PD0008', NULL,   NULL,            CURDATE() + INTERVAL 3 DAY, 2, 5200,   0, NULL, 'Accepted'),
-- ร้านได้รับของคืนแล้ว รอตรวจสภาพ: แอดมินทดลองคืนมัดจำหรือหักมัดจำได้
('Ren1124', 'ORD0014', 'PD0009', 'E004', 'FA246813579TH', CURDATE() - INTERVAL 7 DAY, 3, 1500, 900, CURDATE() - INTERVAL 8 DAY, 'Received');

-- การคืนของที่ร้านได้รับแล้ว: return_date คือวันที่ร้านได้รับของ (บันทึกเมื่อแอดมินกด "ได้รับของแล้ว")
-- deduction คือค่าเสียหายหลังตรวจสภาพ (0 = คืนมัดจำเต็ม) หักจากมัดจำก่อน ส่วนที่เกินมัดจำลูกค้าต้องชำระเพิ่ม
-- (เช่น สมาชิกที่ไม่มีมัดจำ) และ deduction_note คือเหตุผล
CREATE TABLE returnss (
    return_id      VARCHAR(50)  PRIMARY KEY,
    rental_id      VARCHAR(50)  NOT NULL UNIQUE,
    return_date    DATE         NOT NULL,
    deduction      INT          NOT NULL DEFAULT 0,
    deduction_note VARCHAR(255) NULL,
    FOREIGN KEY (rental_id) REFERENCES rentals(rental_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO returnss (return_id, rental_id, return_date, deduction, deduction_note) VALUES
('Ret0001', 'Ren1111', '2025-06-06', 0, NULL),
('Ret0002', 'Ren1112', '2025-06-14', 0, NULL),
('Ret0003', 'Ren1113', '2025-07-06', 0, NULL),
('Ret0004', 'Ren1114', '2025-07-22', 0, NULL),
('Ret0005', 'Ren1115', '2025-08-13', 0, NULL),
('Ret0006', 'Ren1116', '2025-10-01', 900, 'สายกระเป๋าขาดและหนังถลอกที่มุมกระเป๋า'),
('Ret0007', 'Ren1117', '2025-10-26', 0, NULL),
('Ret0008', 'Ren1118', '2025-11-19', 0, NULL),
('Ret0009', 'Ren1119', '2025-12-18', 0, NULL),
('Ret0010', 'Ren1120', '2026-01-05', 0, NULL),
('Ret0011', 'Ren1124', CURDATE() - INTERVAL 1 DAY, 0, NULL);

CREATE TABLE stores (
    postal_code     VARCHAR(10)  PRIMARY KEY,
    name_store      VARCHAR(100) NOT NULL,
    owner_firstname VARCHAR(100) NOT NULL,
    owner_lastname  VARCHAR(100) NOT NULL,
    email           VARCHAR(100),
    address         VARCHAR(100),
    phone           VARCHAR(20)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO stores (postal_code, name_store, owner_firstname, owner_lastname, email, address, phone) VALUES
('73000', 'sumsumbag_shop', 'Somjai', 'Jaidee', 'sumsum_bag_shop@gmail.com', '6, Rajamankha Nai Rd.,Amphoe Muang, Nakhon Pathom', '081-2345678');

-- เลขพัสดุที่ลูกค้าส่งของคืน (หนึ่งรายการเช่าแจ้งคืนได้ครั้งเดียว)
-- ผลการตรวจสภาพเก็บใน rentals.rental_status (Completed/Deducted) และยอดที่หักเก็บใน returnss
CREATE TABLE track_return (
    track_return   VARCHAR(50) PRIMARY KEY NOT NULL,
    rental_id      VARCHAR(50) NOT NULL UNIQUE,
    postal_code    VARCHAR(10) NOT NULL,
    FOREIGN KEY (rental_id)   REFERENCES rentals(rental_id),
    FOREIGN KEY (postal_code) REFERENCES stores(postal_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO track_return (track_return, rental_id, postal_code) VALUES
('TH123456789TH',   'Ren1111', '73000'),
('TH987654321TH',   'Ren1112', '73000'),
('KEX12345678901',  'Ren1113', '73000'),
('KEX98765432102',  'Ren1114', '73000'),
('FLASH123456789',  'Ren1115', '73000'),
('FLASH987654321',  'Ren1116', '73000'),
('J&TTH123456789',  'Ren1117', '73000'),
('J&TTH987654321',  'Ren1118', '73000'),
('SHOPEE123456789', 'Ren1119', '73000'),
('BEST123456789TH', 'Ren1120', '73000'),
('FLASH24681357', 'Ren1124', '73000');

-- คำขอสมัครสมาชิก: ลูกค้าแนบสลิป แอดมินตรวจแล้วอนุมัติหรือปฏิเสธ
CREATE TABLE membership_requests (
    request_id   INT AUTO_INCREMENT PRIMARY KEY,
    customers_id VARCHAR(50)  NOT NULL,
    slip_file    VARCHAR(100) NOT NULL,
    status       ENUM('Pending', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending',
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_at  DATETIME     NULL,
    FOREIGN KEY (customers_id) REFERENCES customers(customers_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
