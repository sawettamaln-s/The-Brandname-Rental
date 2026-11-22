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
DROP TABLE IF EXISTS membership_requests, track_return, returnss, rentals, stores, products, employees, admins, customers;
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
    daily_rental_price INT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO products (product_id, brand, details, daily_rental_price) VALUES
('PD0001', 'Chanel',    '22 Bag Medium in Black',           4100),
('PD0002', 'Chanel',    'Classic 10 Black Silver Hardwear', 3900),
('PD0003', 'Celine',    'Ava Triomphe',                     2300),
('PD0004', 'Celine',    'Pico Belt Bag',                    1900),
('PD0005', 'DIOR',      'LADY D JOY SMALL',                 3300),
('PD0006', 'Lady Dior', 'Medium Shiny Calf in Black',       2800),
('PD0007', 'Goyard',    'Structure Mini Saigon in White',   3550),
('PD0008', 'Hermes',    'Kelly 28Gris T GHW',               5200),
('PD0009', 'Prada',     'Alma mini',                        1500),
('PD0010', 'YSL',       'Medium College in CROC',           1900);

-- การเช่า: หนึ่งแถวต่อกระเป๋าหนึ่งใบ
-- ค่ามัดจำ: สมาชิก 0 บาท, ไม่เป็นสมาชิก 900 บาทต่อใบ
CREATE TABLE rentals (
    rental_id     VARCHAR(50) PRIMARY KEY,
    customers_id  VARCHAR(50),
    product_id    VARCHAR(50),
    employees_id  VARCHAR(50),
    track_rentals VARCHAR(50),
    rental_date   DATE,
    rental_days   INT,
    deposit_fee   INT,
    rental_status ENUM('Pending', 'Complete', 'Cancel') DEFAULT 'Pending',
    FOREIGN KEY (customers_id) REFERENCES customers(customers_id),
    FOREIGN KEY (product_id)   REFERENCES products(product_id),
    FOREIGN KEY (employees_id) REFERENCES employees(employees_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO rentals (rental_id, customers_id, product_id, employees_id, track_rentals, rental_date, rental_days, deposit_fee, rental_status) VALUES
('Ren1111', 'C001', 'PD0001', 'E001', 'EF123456789TH', '2025-06-05', 1, 900, 'Complete'),
('Ren1112', 'C002', 'PD0010', 'E002', 'EA321456789TH', '2025-06-11', 3, 900, 'Complete'),
('Ren1113', 'C003', 'PD0003', 'E003', 'EE431256789TH', '2025-07-05', 1,   0, 'Complete'),
('Ren1114', 'C004', 'PD0004', 'E004', 'AF789561235TH', '2025-07-19', 3,   0, 'Complete'),
('Ren1115', 'C005', 'PD0009', 'E003', 'FA123785689TH', '2025-08-12', 1, 900, 'Complete'),
('Ren1116', 'C006', 'PD0006', 'E001', 'FF124411789TH', '2025-09-29', 3, 900, 'Complete'),
('Ren1117', 'C007', 'PD0007', 'E002', 'EF123451169TH', '2025-10-25', 1,   0, 'Complete'),
('Ren1118', 'C008', 'PD0008', 'E002', 'FE112556789TH', '2025-11-16', 3,   0, 'Complete'),
('Ren1119', 'C009', 'PD0002', 'E004', 'TA897456789TH', '2025-12-17', 1,   0, 'Complete'),
('Ren1120', 'C010', 'PD0005', 'E001', 'TH985256789TH', '2026-01-02', 3, 900, 'Complete');

-- ราคารวมของแต่ละรายการเช่า (ค่าเช่ารายวัน x จำนวนวัน + ค่ามัดจำ)
-- SELECT r.rental_id, (p.daily_rental_price * r.rental_days) + r.deposit_fee AS total_rental_price
-- FROM products p JOIN rentals r ON p.product_id = r.product_id;

-- วันที่ร้านได้รับของคืน (บันทึกเมื่อแอดมินตรวจสภาพแล้ว)
CREATE TABLE returnss (
    return_id   VARCHAR(50) PRIMARY KEY,
    rental_id   VARCHAR(50),
    return_date DATE,
    FOREIGN KEY (rental_id) REFERENCES rentals(rental_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO returnss (return_id, rental_id, return_date) VALUES
('Ret0001', 'Ren1111', '2025-06-06'),
('Ret0002', 'Ren1112', '2025-06-14'),
('Ret0003', 'Ren1113', '2025-07-06'),
('Ret0004', 'Ren1114', '2025-07-22'),
('Ret0005', 'Ren1115', '2025-08-13'),
('Ret0006', 'Ren1116', '2025-10-01'),
('Ret0007', 'Ren1117', '2025-10-26'),
('Ret0008', 'Ren1118', '2025-11-19'),
('Ret0009', 'Ren1119', '2025-12-18'),
('Ret0010', 'Ren1120', '2026-01-05');

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

-- เลขพัสดุที่ลูกค้าส่งของคืน
-- Complete = ของสภาพดี คืนมัดจำครึ่งหนึ่ง, Cancel = ของชำรุด ร้านยึดมัดจำทั้งหมด
CREATE TABLE track_return (
    track_return   VARCHAR(50) PRIMARY KEY NOT NULL,
    rental_id      VARCHAR(50) NOT NULL,
    postal_code    VARCHAR(10) NOT NULL,
    returnl_status ENUM('Pending', 'Complete', 'Cancel') DEFAULT 'Pending',
    FOREIGN KEY (rental_id)   REFERENCES rentals(rental_id),
    FOREIGN KEY (postal_code) REFERENCES stores(postal_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO track_return (track_return, rental_id, postal_code, returnl_status) VALUES
('TH123456789TH',   'Ren1111', '73000', 'Complete'),
('TH987654321TH',   'Ren1112', '73000', 'Complete'),
('KEX12345678901',  'Ren1113', '73000', 'Complete'),
('KEX98765432102',  'Ren1114', '73000', 'Complete'),
('FLASH123456789',  'Ren1115', '73000', 'Complete'),
('FLASH987654321',  'Ren1116', '73000', 'Cancel'),
('J&TTH123456789',  'Ren1117', '73000', 'Complete'),
('J&TTH987654321',  'Ren1118', '73000', 'Complete'),
('SHOPEE123456789', 'Ren1119', '73000', 'Complete'),
('BEST123456789TH', 'Ren1120', '73000', 'Complete');

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
