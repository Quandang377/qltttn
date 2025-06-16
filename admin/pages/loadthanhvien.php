<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/template/config.php';

$vaiTro = $_GET['vaiTro'] ?? '';

switch ($vaiTro) {
    case 'Sinh viên':
        $sql = "SELECT ID_TaiKhoan, MSSV, ID_Dot, Lop, Ten as HoTen, TrangThai FROM SinhVien INNER JOIN TaiKhoan ON SinhVien.ID_TaiKhoan = TaiKhoan.ID_TaiKhoan";
        break;
    case 'Giáo viên':
        $sql = "SELECT ID_TaiKhoan, '' as MSSV, '' as ID_Dot, '' as Lop, Ten as HoTen, TrangThai FROM GiaoVien INNER JOIN TaiKhoan ON GiaoVien.ID_TaiKhoan = TaiKhoan.ID_TaiKhoan";
        break;
    case 'Cán bộ Khoa/Bộ môn':
        $sql = "SELECT ID_TaiKhoan, '' as MSSV, '' as ID_Dot, '' as Lop, Ten as HoTen, TrangThai FROM CanBoKhoa INNER JOIN TaiKhoan ON CanBoKhoa.ID_TaiKhoan = TaiKhoan.ID_TaiKhoan";
        break;
    case 'Admin':
        $sql = "SELECT ID_TaiKhoan, '' as MSSV, '' as ID_Dot, '' as Lop, Ten as HoTen, TrangThai FROM Admin INNER JOIN TaiKhoan ON Admin.ID_TaiKhoan = TaiKhoan.ID_TaiKhoan";
        break;
    case 'Tất cả':
    default:
        $sql = "SELECT ID_TaiKhoan, MSSV, ID_Dot, Lop, Ten as HoTen, TrangThai FROM SinhVien
                UNION
                SELECT ID_TaiKhoan, '', '', '', Ten, TrangThai FROM GiaoVien
                UNION
                SELECT ID_TaiKhoan, '', '', '', Ten, TrangThai FROM CanBoKhoa
                UNION
                SELECT ID_TaiKhoan, '', '', '', Ten, TrangThai FROM Admin";
}

$stmt = $conn->prepare($sql);
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($data);
exit;
