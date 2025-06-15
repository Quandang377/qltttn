<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/template/config.php';

$cheDo = $_POST['che_do'];
$taiKhoan = $_POST['tai_khoan'];
$matKhau = $_POST['matkhau'] ?? null;
$vaiTro = $_POST['vai_tro'];
$hoTen = $_POST['ten'];
$email = $_POST['email'] ?? null;
$idTaiKhoan = $_POST['id_tai_khoan'] ?? substr(uniqid(), -5);
$trangThai = 1;

if ($cheDo === 'them') {
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
    } elseif ($vaiTro == 'Giáo viên') {
        $stmt = $conn->prepare("INSERT INTO GiaoVien (ID_TaiKhoan, Ten, Email, TrangThai) VALUES (?, ?, ?, ?)");
        $stmt->execute([$idTaiKhoan, $hoTen, $email, $trangThai]);
    } elseif ($vaiTro == 'Cán bộ Khoa/Bộ môn') {
        $stmt = $conn->prepare("INSERT INTO CanBoKhoa (ID_TaiKhoan, Ten, Email, TrangThai) VALUES (?, ?, ?, ?)");
        $stmt->execute([$idTaiKhoan, $hoTen, $email, $trangThai]);
    }
} elseif ($cheDo === 'sua') {
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
        $stmt = $conn->prepare("UPDATE SinhVien SET Ten = ?, Lop = ?, MSSV = ? WHERE ID_TaiKhoan = ?");
        $stmt->execute([$hoTen, $lop, $mssv, $idTaiKhoan]);
    } elseif ($vaiTro == 'Giáo viên') {
        $stmt = $conn->prepare("UPDATE GiaoVien SET Ten = ?, Email = ? WHERE ID_TaiKhoan = ?");
        $stmt->execute([$hoTen, $email, $idTaiKhoan]);
    } elseif ($vaiTro == 'Cán bộ Khoa/Bộ môn') {
        $stmt = $conn->prepare("UPDATE CanBoKhoa SET Ten = ?, Email = ? WHERE ID_TaiKhoan = ?");
        $stmt->execute([$hoTen, $email, $idTaiKhoan]);
    }
}

header("Location: quanlythanhvien");
exit;
?>
