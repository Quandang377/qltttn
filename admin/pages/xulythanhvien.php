<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/template/config.php';

$cheDo = $_POST['che_do'];
$taikhoan = $_POST['tai_khoan'];
$matKhau = $_POST['matkhau'] ?? null;
$vaiTro = $_POST['vai_tro'];
$hoTen = $_POST['ten'];
$idtaikhoan = $_POST['id_tai_khoan'] ?? substr(uniqid(), -5);
$trangThai = 1;

if ($cheDo === 'them') {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM taikhoan WHERE taikhoan = ?");
    $stmt->execute([$taikhoan]);
    $soLuong = $stmt->fetchColumn();

    if ($soLuong > 0) {
        header("Location: quanlythanhvien?error=duplicated");
        exit;
    }
    $matKhauMaHoa = password_hash($matKhau, PASSWORD_BCRYPT);

    $stmt = $conn->prepare("INSERT INTO taikhoan (taikhoan, MatKhau, VaiTro, TrangThai) VALUES ( ?, ?, ?, ?)");
    $stmt->execute([$taikhoan, $matKhauMaHoa, $vaiTro, $trangThai]);
    $idtaikhoan = $conn->lastInsertId();
    if ($vaiTro == 'Sinh viên') {
        $mssv = $_POST['mssv'];
        $lop = $_POST['lop'];
        $idDot = $_POST['dot'];

        $stmt = $conn->prepare("INSERT INTO sinhvien (ID_taikhoan, ID_Dot, Ten, Lop, XepLoai, MSSV, ID_GVHD, TrangThai)
                                VALUES (?, ?, ?, ?, NULL, ?, NULL, ?)");
        $stmt->execute([$idtaikhoan, $idDot, $hoTen, $lop, $mssv, $trangThai]);
        header("Location: /datn/admin/pages/quanlythanhvien?msg=added");
        exit;
    } elseif ($vaiTro == 'Giáo viên') {
        $stmt = $conn->prepare("INSERT INTO giaovien (ID_taikhoan, Ten, TrangThai) VALUES (?, ?, ?)");
        $stmt->execute([$idtaikhoan, $hoTen, $trangThai]);
        header("Location: /datn/admin/pages/quanlythanhvien?msg=added");
        exit;
    } elseif ($vaiTro == 'Cán bộ Khoa/Bộ môn') {
        $stmt = $conn->prepare("INSERT INTO canbokhoa (ID_taikhoan, Ten, TrangThai) VALUES (?, ?, ?)");
        $stmt->execute([$idtaikhoan, $hoTen, $trangThai]);
        header("Location: /datn/admin/pages/quanlythanhvien?msg=added");
        exit;
    }else
    {
        $stmt = $conn->prepare("INSERT INTO admin (ID_taikhoan, Ten, TrangThai) VALUES (?,?, ?)");
        $stmt->execute([$idtaikhoan, $hoTen, $trangThai]);
        header("Location: /datn/admin/pages/quanlythanhvien?msg=added");
        exit;
    }
} elseif ($cheDo === 'sua') {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM taikhoan WHERE taikhoan = ? AND ID_taikhoan != ?");
    $stmt->execute([$taikhoan, $idtaikhoan]);
    $soLuong = $stmt->fetchColumn();

    if ($soLuong > 0) {
        header("Location: quanlythanhvien?error=duplicated");
        exit;
    }
    if (!empty($matKhau)) {
        $matKhauMaHoa = password_hash($matKhau, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("UPDATE taikhoan SET taikhoan = ?, MatKhau = ?, VaiTro = ? WHERE ID_taikhoan = ?");
        $stmt->execute([$taikhoan, $matKhauMaHoa, $vaiTro, $idtaikhoan]);
    } else {
        $stmt = $conn->prepare("UPDATE taikhoan SET taikhoan = ?, VaiTro = ? WHERE ID_taikhoan = ?");
        $stmt->execute([$taikhoan, $vaiTro, $idtaikhoan]);
    }

    if ($vaiTro == 'Sinh viên') {
        $mssv = $_POST['mssv'];
        $lop = $_POST['lop'];
        $stmt = $conn->prepare("UPDATE sinhvien SET Ten = ?, Lop = ? , MSSV = ? WHERE ID_taikhoan = ?");
        $stmt->execute([$hoTen, $lop,$mssv, $idtaikhoan]);
        header("Location: /datn/admin/pages/quanlythanhvien?msg=edited");
        exit;
    } elseif ($vaiTro == 'Giáo viên') {
        $stmt = $conn->prepare("UPDATE giaovien SET Ten = ? WHERE ID_taikhoan = ?");
        $stmt->execute([$hoTen, $idtaikhoan]);
        header("Location: /datn/admin/pages/quanlythanhvien?msg=edited");
        exit;
    } elseif ($vaiTro == 'Cán bộ Khoa/Bộ môn') {
        $stmt = $conn->prepare("UPDATE canbokhoa SET Ten = ? WHERE ID_taikhoan = ?");
        $stmt->execute([$hoTen, $idtaikhoan]);
        header("Location: /datn/admin/pages/quanlythanhvien?msg=edited");
        exit;
    }elseif ($vaiTro == 'admin') {
        $stmt = $conn->prepare("UPDATE admin SET Ten = ? WHERE ID_taikhoan = ?");
        $stmt->execute([$hoTen, $idtaikhoan]);
        header("Location: /datn/admin/pages/quanlythanhvien?msg=edited");
        exit;
    }
} elseif ($cheDo == 'mo_khoa' && isset($_POST['id_tai_khoan'])) {
    $id = $_POST['id_tai_khoan'];

    // Cập nhật trạng thái trong bảng taikhoan
    $stmt = $conn->prepare("UPDATE taikhoan SET TrangThai = 1 WHERE ID_taikhoan = ?");
    $stmt->execute([$id]);

    // Lấy vai trò tài khoản
    $stmtRole = $conn->prepare("SELECT VaiTro FROM taikhoan WHERE ID_taikhoan = ?");
    $stmtRole->execute([$id]);
    $vaiTro = $stmtRole->fetchColumn();

    // Mở khóa theo vai trò
    if ($vaiTro == 'Sinh viên') {
        $sql = $conn->prepare("UPDATE sinhvien SET TrangThai = 1 WHERE ID_taikhoan = ?");
        $sql->execute([$id]);
    } elseif ($vaiTro == 'Giáo viên') {
        $sql = $conn->prepare("UPDATE giaovien SET TrangThai = 1 WHERE ID_taikhoan = ?");
        $sql->execute([$id]);
    } elseif ($vaiTro == 'Cán bộ Khoa/Bộ môn') {
        $sql = $conn->prepare("UPDATE canbokhoa SET TrangThai = 1 WHERE ID_taikhoan = ?");
        $sql->execute([$id]);
    } elseif ($vaiTro == 'admin') {
        $sql = $conn->prepare("UPDATE admin SET TrangThai = 1 WHERE ID_taikhoan = ?");
        $sql->execute([$id]);
    }
    header("Location: /datn/admin/pages/quanlythanhvien?msg=unlocked");
    exit;
}

header("Location: quanlythanhvien");
exit;
?>
