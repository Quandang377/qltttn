<<<<<<< HEAD
CREATE DATABASE IF NOT EXISTS ThucTapDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE ThucTapDB;

=======

CREATE DATABASE IF NOT EXISTS ThucTapDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ThucTapDB;

CREATE TABLE TaiKhoan (
    ID_TaiKhoan VARCHAR(5) PRIMARY KEY,
    TaiKhoan VARCHAR(20),
    MatKhau VARCHAR(16),
    VaiTro NVARCHAR(30),
    TrangThai TINYINT
);

CREATE TABLE CanBoKhoa (
    ID_TaiKhoan VARCHAR(5) PRIMARY KEY,
    Ten NVARCHAR(50),
    TrangThai TINYINT,
    FOREIGN KEY (ID_TaiKhoan) REFERENCES TaiKhoan(ID_TaiKhoan)
);

CREATE TABLE GiaoVien (
    ID_TaiKhoan VARCHAR(5) PRIMARY KEY,
    Ten NVARCHAR(50),
    Sdt VARCHAR(12),
    Email VARCHAR(250),
    TrangThai TINYINT,
    FOREIGN KEY (ID_TaiKhoan) REFERENCES TaiKhoan(ID_TaiKhoan)
);

>>>>>>> 4fd8ce05db2488642b901eba16148a94e291076e
CREATE TABLE DotThucTap (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    TenDot VARCHAR(50),
    Nam VARCHAR(5),
    Loai VARCHAR(25),
    Nganh TEXT,
    NguoiQuanLy VARCHAR(255),
    ThoiGianBatDau DATE,
    ThoiGianKetThuc DATE,
    TenNguoiMoDot VARCHAR(255),
    TrangThai TINYINT
);

<<<<<<< HEAD
=======
CREATE TABLE SinhVien (
    ID_TaiKhoan VARCHAR(5) PRIMARY KEY,
    ID_Dot INT,
    Ten NVARCHAR(50),
    Lop NVARCHAR(50),
    Diem INT,
    XepLoai NVARCHAR(50),
    MSSV VARCHAR(12),
    ID_GVHD VARCHAR(5),
    TrangThai TINYINT,
    FOREIGN KEY (ID_TaiKhoan) REFERENCES TaiKhoan(ID_TaiKhoan),
    FOREIGN KEY (ID_Dot) REFERENCES DotThucTap(ID),
    FOREIGN KEY (ID_GVHD) REFERENCES GiaoVien(ID_TaiKhoan)
);

>>>>>>> 4fd8ce05db2488642b901eba16148a94e291076e
CREATE TABLE TaiNguyenThucTap (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    Ten VARCHAR(255),
    DuongDan VARCHAR(255),
    NguoiDang VARCHAR(255),
    TrangThai TINYINT
);

CREATE TABLE CongTy (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    MaSoThue VARCHAR(250),
    TenCty VARCHAR(250),
    LinhVuc VARCHAR(250),
    Sdt VARCHAR(50),
    Email VARCHAR(250),
    DiaChi TEXT,
    MoTa TEXT,
    TrangThai TINYINT
);

CREATE TABLE GiayGioiThieu (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    TenCty VARCHAR(250),
    MaSoThue VARCHAR(250),
    LinhVuc VARCHAR(250),
    Sdt VARCHAR(50),
    Email VARCHAR(250),
    DiaChi TEXT,
    MoTa TEXT,
    IdSinhVien VARCHAR(5),
    TrangThai TINYINT,
    FOREIGN KEY (IdSinhVien) REFERENCES SinhVien(ID_TaiKhoan)
);

CREATE TABLE BaoCao (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    IDSV VARCHAR(5),
    IdGVHD VARCHAR(5),
    Tuan VARCHAR(10),
    CongviecThucHien TEXT,
    DanhGia TEXT,
    TrangThai TINYINT,
    FOREIGN KEY (IDSV) REFERENCES SinhVien(ID_TaiKhoan),
    FOREIGN KEY (IdGVHD) REFERENCES GiaoVien(ID_TaiKhoan)
);

CREATE TABLE ThongBao (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    TieuDe NVARCHAR(250),
    NoiDung TEXT,
    NgayDang DATETIME,
    ID_TaiKhoan VARCHAR(5),
    TrangThai TINYINT,
    FOREIGN KEY (ID_TaiKhoan) REFERENCES TaiKhoan(ID_TaiKhoan)
);
<<<<<<< HEAD

=======
>>>>>>> 4fd8ce05db2488642b901eba16148a94e291076e
CREATE TABLE TEPDINHKEM (
    ID INT PRIMARY KEY AUTO_INCREMENT,
    TENFILE VARCHAR(255),
    ID_THONGBAO INT,
    FOREIGN KEY (ID_THONGBAO) REFERENCES THONGBAO(ID)
);
<<<<<<< HEAD

CREATE TABLE TaiKhoan (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    TaiKhoan VARCHAR(20),
    MatKhau VARCHAR(16),
    VaiTro NVARCHAR(30),
    TrangThai TINYINT
);

CREATE TABLE CanBoKhoa (
    ID_TaiKhoan INT AUTO_INCREMENT PRIMARY KEY,
    Ten NVARCHAR(50),
    Email VARCHAR(250),
    TrangThai TINYINT,
    FOREIGN KEY (ID_TaiKhoan) REFERENCES TaiKhoan(ID_TaiKhoan)
);

CREATE TABLE GiaoVien (
    ID_TaiKhoan INT AUTO_INCREMENT PRIMARY KEY,
    Ten NVARCHAR(50),
    Email VARCHAR(250),
    TrangThai TINYINT,
    FOREIGN KEY (ID_TaiKhoan) REFERENCES TaiKhoan(ID_TaiKhoan)
);

CREATE TABLE SinhVien (
    ID_TaiKhoan INT AUTO_INCREMENT PRIMARY KEY,
    ID_Dot INT,
    Ten NVARCHAR(50),
    Lop NVARCHAR(50),
    XepLoai NVARCHAR(50),
    MSSV VARCHAR(12),
    ID_GVHD VARCHAR(5),
    TrangThai TINYINT,
    FOREIGN KEY (ID_TaiKhoan) REFERENCES TaiKhoan(ID_TaiKhoan),
    FOREIGN KEY (ID_Dot) REFERENCES DotThucTap(ID),
    FOREIGN KEY (ID_GVHD) REFERENCES GiaoVien(ID_TaiKhoan)
);

=======
>>>>>>> 4fd8ce05db2488642b901eba16148a94e291076e
CREATE TABLE KhaoSat (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    TieuDe NVARCHAR(255),
    MoTa TEXT,
<<<<<<< HEAD
    NguoiNhan VARCHAR(50),
=======
>>>>>>> 4fd8ce05db2488642b901eba16148a94e291076e
    NguoiTao VARCHAR(5),
    ThoiGianTao DATETIME,
    TrangThai TINYINT,
    FOREIGN KEY (NguoiTao) REFERENCES TaiKhoan(ID_TaiKhoan)
);

CREATE TABLE CauHoiKhaoSat (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    ID_KhaoSat INT,
    NoiDung TEXT,
<<<<<<< HEAD
=======
    LoaiCauHoi VARCHAR(20),
>>>>>>> 4fd8ce05db2488642b901eba16148a94e291076e
    TrangThai TINYINT,
    FOREIGN KEY (ID_KhaoSat) REFERENCES KhaoSat(ID)
);

CREATE TABLE PhanHoiKhaoSat (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    ID_KhaoSat INT,
    ID_TaiKhoan VARCHAR(5),
    ThoiGianTraLoi DATETIME,
<<<<<<< HEAD
    TrangThai TINYINT DEFAULT 1,
    FOREIGN KEY (ID_KhaoSat) REFERENCES KhaoSat(ID),
    FOREIGN KEY (ID_TaiKhoan) REFERENCES TaiKhoan(ID_TaiKhoan),
    UNIQUE (ID_KhaoSat, ID_TaiKhoan)
=======
    TrangThai TINYINT,
    FOREIGN KEY (ID_KhaoSat) REFERENCES KhaoSat(ID),
    FOREIGN KEY (ID_TaiKhoan) REFERENCES TaiKhoan(ID_TaiKhoan)
>>>>>>> 4fd8ce05db2488642b901eba16148a94e291076e
);

CREATE TABLE CauTraLoi (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    ID_PhanHoi INT,
    ID_CauHoi INT,
    TraLoi TEXT,
    TrangThai TINYINT,
    FOREIGN KEY (ID_PhanHoi) REFERENCES PhanHoiKhaoSat(ID),
    FOREIGN KEY (ID_CauHoi) REFERENCES CauHoiKhaoSat(ID)
);

CREATE TABLE File (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    TenFile VARCHAR(30),
    File BLOB,
    ID_SV VARCHAR(5),
    ID_GVHD VARCHAR(5),
    TrangThai TINYINT,
    FOREIGN KEY (ID_SV) REFERENCES SinhVien(ID_TaiKhoan),
    FOREIGN KEY (ID_GVHD) REFERENCES GiaoVien(ID_TaiKhoan)
);

CREATE TABLE TongKet (
    ID INT AUTO_INCREMENT PRIMARY KEY,
<<<<<<< HEAD
    ID_SV VARCHAR(5),
=======
    IDSV VARCHAR(5),
>>>>>>> 4fd8ce05db2488642b901eba16148a94e291076e
    ID_GVHD VARCHAR(5),
    Diem FLOAT,
    DanhGia TEXT,
    TrangThai TINYINT,
<<<<<<< HEAD
    FOREIGN KEY (ID_SV) REFERENCES SinhVien(ID_TaiKhoan),
    FOREIGN KEY (ID_GVHD) REFERENCES GiaoVien(ID_TaiKhoan)
);
CREATE TABLE TuanBaoCao (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    ID_GVHD VARCHAR(250) NOT NULL,
    Tuan INT NOT NULL,             
    TrangThai BOOLEAN NOT NULL,
    FOREIGN KEY (ID_GVHD) REFERENCES GiaoVien(ID_TaiKhoan)
);
=======
    FOREIGN KEY (IDSV) REFERENCES SinhVien(ID_TaiKhoan),
    FOREIGN KEY (ID_GVHD) REFERENCES GiaoVien(ID_TaiKhoan)
);
>>>>>>> 4fd8ce05db2488642b901eba16148a94e291076e
