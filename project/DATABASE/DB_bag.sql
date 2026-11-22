create database project;
use project;

create table Customers (
    customers_id varchar(50) PRIMARY KEY,
    customers_firstname  varchar(50),
    customers_lastname  varchar(50),
    gender varchar(50),
    customers_member varchar(10) NOT NULL,
    phonenumber varchar(15),
    email varchar(50),
    address varchar(200),
    postal_code varchar(10),
    password VARCHAR(255) NOT NULL
);
insert into Customers (customers_id,customers_firstname,customers_lastname,gender,customers_member,phonenumber,email,address,postal_code)values
('C001', 'Chaiya', 'Bunmak', 'Male', 'no', '081-1112233', 'chaiya@gmail.com', '101/2 Moo 4, Khon Kaen City, Khon Kaen', '40000'),
('C002', 'Siriwan', 'Chatree',  'Female', 'no', '082-2223344', 'siriwan@gmail.com', '50/5 Soi Ladprao 71, Bangkok', '10310'),
('C003', 'Thanawat', 'Phrommarach',  'Male', 'yes', '083-3334455', 'thanawat@gmail.com', '202/3 Chiang Rai City, Chiang Rai', '57000'),
('C004', 'Natcha', 'Intarachai',  'Female', 'yes', '084-4445566', 'natcha@gmail.com', '58/9 Moo 7, Udon Thani City, Udon Thani', '41000'),
('C005', 'Jirapat', 'Suthiphong',  'Male', 'no', '085-5556677', 'jirapat@gmail.com', '40/1 Sukhumvit 11, Bangkok', '10110'),
('C006', 'Patcharee', 'Punsiri',  'Female', 'no', '086-6667788', 'patcharee@gmail.com', '3/2 Moo 1, Hua Hin, Prachuap Khiri Khan', '77110'),
('C007', 'Somyot', 'Charoenrat',  'Male', 'yes', '087-7778899', 'somyot@gmail.com', '75 Moo 5, Surat Thani City, Surat Thani', '84000'),
('C008', 'Manat', 'Sangthong',  'Male', 'yes', '088-8889900', 'manat@gmail.com', '99/9 Moo 8, Nonthaburi City, Nonthaburi', '11000'),
('C009', 'Nantida', 'Wongkham',  'Female', 'yes', '089-9990011', 'nantida@gmail.com', '123/45 Chiang Mai City, Chiang Mai', '50000'),
('C010', 'Somporn', 'Prasertsak',  'Male', 'no', '080-0001122', 'somporn@gmail.com', '10/20 Moo 6, Ubon Ratchathani City, Ubon Ratchathani', '34000'); 

select * from Customers ;
describe  Customers;

create table Employees (
    employees_id varchar(50) PRIMARY KEY,
    employees_firstname  varchar(50),
    employees_lastname varchar(50),
    gender varchar(50),
    department varchar(50) NOT NULL,
    phonenumber varchar(15),
    salary int
);
insert into Employees (employees_id, employees_firstname, employees_lastname, gender, department, phonenumber, salary) values
('E001', 'Kittipong', 'Namsomti', 'Male', 'Sales Representation', '091-2345678', 22000),
('E002', 'Sawidtee', 'Panit', 'Female', 'Sales Representation', '092-3456789', 25000),
('E003', 'Chonthicha', 'Damrong', 'Female', 'Admin', '093-4567890', 22000),
('E004', 'Surachai', 'Thongkham', 'Male', 'Sales Representation', '094-5678901', 25000);

select * from Employees ;
describe Employees ;

create table Products (
    product_id varchar(50) PRIMARY KEY,
    brand varchar(50),
    details varchar(50),
    daily_rental_price int
);
insert into Products (product_id,brand,details,daily_rental_price)values
('PD0001', 'Chanel', '22 Bag Medium in Black','4100'),
('PD0002', 'Chanel', 'Classic 10 Black Silver Hardwear','3900'),
('PD0003', 'Celine', 'Ava Triomphe','2300'),
('PD0004', 'Celine', 'Pico Belt ​Bag','1900'),
('PD0005', 'DIOR', 'LADY D JOY SMALL','3300'),
('PD0006', 'Lady Dior', 'Medium Shiny Calf in Black','2800'),
('PD0007', 'Goyard', 'Structure Mini Saigon in White','3550'),
('PD0008', 'Hermes', 'Kelly 28Gris T GHW','5200'),
('PD0009', 'Prada', 'Alma mini','1500'),
('PD0010', 'YSL', 'Medium College in CROC','1900');

select * from Products;
describe Products;

create table Rentals (
    rental_id varchar(50) PRIMARY KEY,
    customers_id varchar(50),
    product_id varchar(50),
    employees_id varchar(50),
    track_rentals varchar(50),
    rental_date date,
    rental_days int,
    deposit_fee int,
    rental_status ENUM('Pending', 'Complete', 'Cancel') DEFAULT 'Pending',
    FOREIGN KEY (customers_id) REFERENCES Customers(customers_id),
    FOREIGN KEY (product_id) REFERENCES Products(product_id),
    FOREIGN KEY (employees_id) REFERENCES Employees(employees_id)
);
insert into Rentals (rental_id,customers_id,product_id,employees_id,track_rentals,rental_date,rental_days,deposit_fee)values
('Ren1111','C001','PD0001','E001','EF123456789TH', '2025-06-05',1,'900'),
('Ren1112','C002','PD0010','E002','EA321456789TH', '2025-06-11',3,'900'),
('Ren1113','C003','PD0003','E003','EE431256789TH', '2025-07-05',1,'0'),
('Ren1114','C004','PD0004','E004','AF789561235TH', '2025-07-19',3,'0'),
('Ren1115','C005','PD0009','E003','FA123785689TH', '2025-08-12',1,'900'),
('Ren1116','C006','PD0006','E001','FF124411789TH', '2025-09-29',3,'900'),
('Ren1117','C007','PD0007','E002','EF123451169TH', '2025-10-25',1,'0'),
('Ren1118','C008','PD0008','E002','FE112556789TH', '2025-11-16',3,'0'),
('Ren1119','C009','PD0002','E004','TA897456789TH', '2025-12-17',1,'0'),
('Ren1120','C010','PD0005','E001','TH985256789TH', '2026-01-02',3,'900');

select * from Rentals;
describe Rentals;

select p.daily_rental_price , r.rental_days,r.deposit_fee,(p.daily_rental_price *  r.rental_days) + r.deposit_fee 
as total_rental_price
from  Products p
join Rentals r on p.product_id = r.product_id;


create table Returnss(
    return_id varchar(50) PRIMARY KEY,
    rental_id varchar(50),
    return_date date,
    is_damaged varchar(10) NOT NULL,
    refund_deposit int,
    FOREIGN KEY (rental_id) REFERENCES Rentals(rental_id)
);
insert into Returnss(return_id,rental_id,return_date,is_damaged,refund_deposit) values
('Ret0001','Ren1111','2025-06-06','no','900'),
('Ret0002','Ren1112','2025-06-14','no','900'),
('Ret0003','Ren1113','2025-07-06','no','0'),
('Ret0004','Ren1114','2025-07-22','no','0'),
('Ret0005','Ren1115','2025-08-13','no','900'),
('Ret0006','Ren1116','2025-10-01','yes','0'),
('Ret0007','Ren1117','2025-10-26','no','0'),
('Ret0008','Ren1118','2025-11-19','no','0'),
('Ret0009','Ren1119','2025-12-18','no','0'),
('Ret0010','Ren1120','2026-01-05','no','900');

select * from Returnss;
describe Returnss;

create table Stores ( 
postal_code varchar(10) PRIMARY KEY, 
name_store varchar(100) NOT NULL, 
owner_firstname varchar(100) NOT NULL,
owner_lastname varchar(100) NOT NULL, 
email varchar(100), 
address varchar(100),
phone varchar(20)
 );
 
insert Stores (postal_code,name_store, owner_firstname, owner_lastname, email, address, phone) values
('73000','sumsumbag_shop', 'Somjai', 'Jaidee','sumsum_bag_shop@gmail.com','6, Rajamankha Nai Rd.,Amphoe Muang, Nakhon Pathom','081-2345678');

select * from Stores ;
describe Stores ;

create table track_Return ( 
track_return varchar(50) PRIMARY KEY NOT NULL, 
rental_id varchar(50) NOT NULL, 
postal_code varchar(10) NOT NULL,
returnl_status ENUM('Pending', 'Complete', 'Cancel') DEFAULT 'Pending',
FOREIGN KEY (rental_id) REFERENCES Rentals(rental_id),
FOREIGN KEY (postal_code) REFERENCES Stores(postal_code)
 );
 
insert track_Return (track_return,rental_id,postal_code) values
('TH123456789TH','Ren1111','73000'),
('TH987654321TH','Ren1112','73000'),
('KEX12345678901','Ren1113','73000'),
('KEX98765432102','Ren1114','73000'),
('FLASH123456789','Ren1115','73000'),
('FLASH987654321','Ren1116','73000'),
('J&TTH123456789','Ren1117','73000'),
('J&TTH987654321','Ren1118','73000'),
('SHOPEE123456789','Ren1119','73000'),
('BEST123456789TH','Ren1120','73000');

select * from track_Return ;
describe track_Return;

show tables;

describe Customers;

describe Customers  Employees ;