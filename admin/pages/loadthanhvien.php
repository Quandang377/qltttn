<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';

require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";
header('Content-Type: application/json');

$vaiTro = $_GET['vaiTro'] ?? '';

switch ($vaiTro) {
    case 'Giáo viên':
        $stmt = $conn->query("SELECT gv.ID_TaiKhoan, gv.Ten, gv.TrangThai, '' AS MSSV FROM GiaoVien gv");
        break;
    case 'Sinh viên':
        $stmt = $conn->query("SELECT sv.ID_TaiKhoan, sv.Ten, sv.TrangThai, sv.MSSV FROM SinhVien sv");
        break;
    case 'Cán bộ Khoa/Bộ môn':
        $stmt = $conn->query("SELECT cb.ID_TaiKhoan, cb.Ten, cb.TrangThai, '' AS MSSV FROM CanBoKhoa cb");
        break;
    case 'Admin':
        $stmt = $conn->prepare("SELECT tk.ID_TaiKhoan, tk.TaiKhoan AS Ten, tk.TrangThai, '' AS MSSV FROM TaiKhoan tk WHERE VaiTro = ?");
        $stmt->execute(['Admin']);
        break;
    default:
        echo json_encode([]);
        exit;
}

$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($data);