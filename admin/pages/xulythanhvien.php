<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/template/config.php';

$cheDo = $_POST['che_do'];
$taiKhoan = $_POST['tai_khoan'];
$matKhau = $_POST['matkhau'] ?? null;
$vaiTro = $_POST['vai_tro'];
$hoTen = $_POST['ten'];
$idTaiKhoan = $_POST['id_tai_khoan'] ?? substr(uniqid(), -5);
$trangThai = 1;

if ($cheDo === 'them') {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM TaiKhoan WHERE TaiKhoan = ?");
    $stmt->execute([$taiKhoan]);
    $soLuong = $stmt->fetchColumn();

    if ($soLuong > 0) {
        header("Location: quanlythanhvien?error=duplicated");
        exit;
    }
    $matKhauMaHoa = password_hash($matKhau, PASSWORD_BCRYPT);

    $stmt = $conn->prepare("INSERT INTO TaiKhoan (TaiKhoan, MatKhau, VaiTro, TrangThai) VALUES ( ?, ?, ?, ?)");
    $stmt->execute([$taiKhoan, $matKhauMaHoa, $vaiTro, $trangThai]);
    $idTaiKhoan = $conn->lastInsertId();
    if ($vaiTro == 'Sinh viên') {
        $mssv = $_POST['mssv'];
        $lop = $_POST['lop'];
        $idDot = $_POST['dot'];

        $stmt = $conn->prepare("INSERT INTO SinhVien (ID_TaiKhoan, ID_Dot, Ten, Lop, XepLoai, MSSV, ID_GVHD, TrangThai)
                                VALUES (?, ?, ?, ?, NULL, ?, NULL, ?)");
        $stmt->execute([$idTaiKhoan, $idDot, $hoTen, $lop, $mssv, $trangThai]);
        header("Location: /datn/admin/pages/quanlythanhvien?msg=added");
        exit;
    } elseif ($vaiTro == 'Giáo viên') {
        $stmt = $conn->prepare("INSERT INTO GiaoVien (ID_TaiKhoan, Ten, TrangThai) VALUES (?, ?, ?)");
        $stmt->execute([$idTaiKhoan, $hoTen, $trangThai]);
        header("Location: /datn/admin/pages/quanlythanhvien?msg=added");
        exit;
    } elseif ($vaiTro == 'Cán bộ Khoa/Bộ môn') {
        $stmt = $conn->prepare("INSERT INTO CanBoKhoa (ID_TaiKhoan, Ten, TrangThai) VALUES (?, ?, ?)");
        $stmt->execute([$idTaiKhoan, $hoTen, $trangThai]);
        header("Location: /datn/admin/pages/quanlythanhvien?msg=added");
        exit;
    }else
    {
        $stmt = $conn->prepare("INSERT INTO admin (ID_TaiKhoan, Ten, TrangThai) VALUES (?,?, ?)");
        $stmt->execute([$idTaiKhoan, $hoTen, $trangThai]);
        header("Location: /datn/admin/pages/quanlythanhvien?msg=added");
        exit;
    }
} elseif ($cheDo === 'sua') {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM TaiKhoan WHERE TaiKhoan = ? AND ID_TaiKhoan != ?");
    $stmt->execute([$taiKhoan, $idTaiKhoan]);
    $soLuong = $stmt->fetchColumn();

    if ($soLuong > 0) {
        header("Location: quanlythanhvien?error=duplicated");
        exit;
    }
    if (!empty($matKhau)) {
        $matKhauMaHoa = password_hash($matKhau, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("UPDATE TaiKhoan SET TaiKhoan = ?, MatKhau = ?, VaiTro = ? WHERE ID_TaiKhoan = ?");
        $stmt->execute([$taiKhoan, $matKhauMaHoa, $vaiTro, $idTaiKhoan]);
    } else {
        $stmt = $conn->prepare("UPDATE TaiKhoan SET TaiKhoan = ?, VaiTro = ? WHERE ID_TaiKhoan = ?");
        $stmt->execute([$taiKhoan, $vaiTro, $idTaiKhoan]);
    }

    if ($vaiTro == 'Sinh viên') {
        $mssv = $_POST['mssv'];
        $lop = $_POST['lop'];
        $stmt = $conn->prepare("UPDATE SinhVien SET Ten = ?, Lop = ? , MSSV = ? WHERE ID_TaiKhoan = ?");
        $stmt->execute([$hoTen, $lop,$mssv, $idTaiKhoan]);
        header("Location: /datn/admin/pages/quanlythanhvien?msg=edited");
        exit;
    } elseif ($vaiTro == 'Giáo viên') {
        $stmt = $conn->prepare("UPDATE GiaoVien SET Ten = ? WHERE ID_TaiKhoan = ?");
        $stmt->execute([$hoTen, $idTaiKhoan]);
        header("Location: /datn/admin/pages/quanlythanhvien?msg=edited");
        exit;
    } elseif ($vaiTro == 'Cán bộ Khoa/Bộ môn') {
        $stmt = $conn->prepare("UPDATE CanBoKhoa SET Ten = ? WHERE ID_TaiKhoan = ?");
        $stmt->execute([$hoTen, $idTaiKhoan]);
        header("Location: /datn/admin/pages/quanlythanhvien?msg=edited");
        exit;
    }elseif ($vaiTro == 'Admin') {
        $stmt = $conn->prepare("UPDATE admin SET Ten = ? WHERE ID_TaiKhoan = ?");
        $stmt->execute([$hoTen, $idTaiKhoan]);
        header("Location: /datn/admin/pages/quanlythanhvien?msg=edited");
        exit;
    }
} elseif ($cheDo == 'mo_khoa' && isset($_POST['id_tai_khoan'])) {
    $id = $_POST['id_tai_khoan'];

    // Cập nhật trạng thái trong bảng TaiKhoan
    $stmt = $conn->prepare("UPDATE TaiKhoan SET TrangThai = 1 WHERE ID_TaiKhoan = ?");
    $stmt->execute([$id]);

    // Lấy vai trò tài khoản
    $stmtRole = $conn->prepare("SELECT VaiTro FROM TaiKhoan WHERE ID_TaiKhoan = ?");
    $stmtRole->execute([$id]);
    $vaiTro = $stmtRole->fetchColumn();

    // Mở khóa theo vai trò
    if ($vaiTro == 'Sinh viên') {
        $sql = $conn->prepare("UPDATE SinhVien SET TrangThai = 1 WHERE ID_TaiKhoan = ?");
        $sql->execute([$id]);
    } elseif ($vaiTro == 'Giáo viên') {
        $sql = $conn->prepare("UPDATE GiaoVien SET TrangThai = 1 WHERE ID_TaiKhoan = ?");
        $sql->execute([$id]);
    } elseif ($vaiTro == 'Cán bộ Khoa/Bộ môn') {
        $sql = $conn->prepare("UPDATE CanBoKhoa SET TrangThai = 1 WHERE ID_TaiKhoan = ?");
        $sql->execute([$id]);
    } elseif ($vaiTro == 'Admin') {
        $sql = $conn->prepare("UPDATE Admin SET TrangThai = 1 WHERE ID_TaiKhoan = ?");
        $sql->execute([$id]);
    }
    header("Location: /datn/admin/pages/quanlythanhvien?msg=unlocked");
    exit;
}

header("Location: quanlythanhvien");
exit;
?>
