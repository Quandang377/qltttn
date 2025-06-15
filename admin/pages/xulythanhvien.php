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
    } elseif ($vaiTro == 'Giáo viên') {
        $stmt = $conn->prepare("INSERT INTO GiaoVien (ID_TaiKhoan, Ten, Email, TrangThai) VALUES (?, ?, ?, ?)");
        $stmt->execute([$idTaiKhoan, $hoTen, $taiKhoan, $trangThai]);
    } elseif ($vaiTro == 'Cán bộ Khoa/Bộ môn') {
        $stmt = $conn->prepare("INSERT INTO CanBoKhoa (ID_TaiKhoan, Ten, Email, TrangThai) VALUES (?, ?, ?, ?)");
        $stmt->execute([$idTaiKhoan, $hoTen, $taiKhoan, $trangThai]);
    }else
    {
        $stmt = $conn->prepare("INSERT INTO admin (ID_TaiKhoan, Ten, Email, TrangThai) VALUES (?, ?, ?, ?)");
        $stmt->execute([$idTaiKhoan, $hoTen, $taiKhoan, $trangThai]);
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
        $stmt = $conn->prepare("UPDATE SinhVien SET Ten = ?, Lop = ?, Email = ?, MSSV = ? WHERE ID_TaiKhoan = ?");
        $stmt->execute([$hoTen, $lop, $taiKhoan,$mssv, $idTaiKhoan]);
    } elseif ($vaiTro == 'Giáo viên') {
        $stmt = $conn->prepare("UPDATE GiaoVien SET Ten = ?, Email = ? WHERE ID_TaiKhoan = ?");
        $stmt->execute([$hoTen, $taiKhoan, $idTaiKhoan]);
    } elseif ($vaiTro == 'Cán bộ Khoa/Bộ môn') {
        $stmt = $conn->prepare("UPDATE CanBoKhoa SET Ten = ?, Email = ? WHERE ID_TaiKhoan = ?");
        $stmt->execute([$hoTen, $taiKhoan, $idTaiKhoan]);
    }
}

header("Location: quanlythanhvien");
exit;
?>
