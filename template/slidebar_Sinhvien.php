<<<<<<< HEAD
<nav class="navbar navbar-inverse navbar-fixed-top" role="navigation">
            <div class="navbar-header">
                <a class="navbar-brand" href="index.html">Cao đẳng kỹ thuật Cao Thắng</a>
            </div>
            <aside class="sidebar navbar-default" role="navigation">
                <div class="sidebar-nav navbar-collapse">
                    <ul class="nav" id="side-menu">
                        <li class="sidebar-search">
                            <div class="input-group custom-search-form">
                                <input type="text" class="form-control" placeholder="Search...">
                                <span class="input-group-btn">
                                    <button class="btn btn-primary" type="button">
                                        <i class="fa fa-search"></i>
                                    </button>
                                </span>
                            </div>
                        </li>
                        <li>
                            <a href="pages/sinhvien/trangchu" class="active"><i class="fa fa-dashboard fa-fw"></i> Trang chủ</a>
                        </li>
                        <li>
                            <a href="pages/sinhvien/dangkygiaygioithieu" class="active"><i class="fa fa-dashboard fa-fw"></i> Đăng ký giấy giới thiệu</a>
                        </li>
                        <li>
                            <a href="pages/sinhvien/baocaotuan" class="active"><i class="fa fa-dashboard fa-fw"></i> Báo cáo tuần</a>
                        </li>
                        <li>
                            <a href="pages/sinhvien/tainguyen" class="active"><i class="fa fa-dashboard fa-fw"></i> Tài nguyên</a>
                        </li>
                         <li>
                            <a href="pages/sinhvien/khaosat" class="active"><i class="fa fa-dashboard fa-fw"></i> Khảo sát</a>
                        </li>
                        <li>
                            <a href="pages/sinhvien/thongtincanhan" class="active"><i class="fa fa-dashboard fa-fw"></i> Thông tin cá nhân</a>
                        </li>
                    </ul>
                </div>
            </aside>
        </nav>
=======
<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";

$idTaiKhoan = $_SESSION['user']['ID_TaiKhoan'] ?? null;
$currentPage = basename($_SERVER['PHP_SELF']);
$currentPath = $_SERVER['REQUEST_URI'];
$tenDot = '';
if ($idTaiKhoan) {
    $stmt = $conn->prepare("SELECT dt.TenDot 
        FROM SinhVien sv 
        LEFT JOIN DotThucTap dt ON sv.ID_Dot = dt.ID 
        WHERE sv.ID_TaiKhoan = ?");
    $stmt->execute([$idTaiKhoan]);
    $tenDot = $stmt->fetchColumn();
}

$coThongBaoMoi = false;
if ($idTaiKhoan) {
    $stmt = $conn->prepare("SELECT ID_Dot FROM SinhVien WHERE ID_TaiKhoan = ?");
    $stmt->execute([$idTaiKhoan]);
    $idDot = $stmt->fetchColumn();

    $stmt = $conn->prepare("
        SELECT COUNT(*) FROM THONGBAO tb
        WHERE tb.TRANGTHAI = 1 AND tb.ID_Dot = ? AND tb.ID NOT IN (
            SELECT ID_ThongBao FROM ThongBao_Xem WHERE ID_TaiKhoan = ?
        )
    ");
    $stmt->execute([$idDot, $idTaiKhoan]);
    $coThongBaoMoi = $stmt->fetchColumn() > 0;
}
$stmt = $conn->prepare("
    SELECT COUNT(*) FROM KhaoSat ks
    WHERE ks.TrangThai = 1
    AND (
        ks.NguoiNhan IN ('Tất cả', ?) 
        OR (
            ks.NguoiNhan = 'Sinh viên thuộc hướng dẫn'
            AND EXISTS (
                SELECT 1 FROM SinhVien sv
                WHERE sv.ID_TaiKhoan = ? AND sv.ID_GVHD = ks.NguoiTao
            )
        )
    )
    AND ks.ID NOT IN (
        SELECT ID_KhaoSat FROM PhanHoiKhaoSat WHERE ID_TaiKhoan = ?
    )
");
$stmt->execute([$vaiTro, $idTaiKhoan, $idTaiKhoan]);
$coKhaoSatMoi = $stmt->fetchColumn() > 0;

$stmt = $conn->query("SELECT * FROM cauhinh");
$cauhinh = [];
foreach ($stmt as $row) {
    $cauhinh[$row['Ten']] = $row['GiaTri'];
}

?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    .topbar {
        background: linear-gradient(to right, <?= $cauhinh['mau_sac_giaodien'] ?? '#2563eb' ?>);
        padding: 10px 20px;
        color: white;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
    }

    .topbar-left {
        font-weight: bold;
        font-size: 16px;
    }

    .topbar-center {
        flex: 1;
        text-align: center;
        font-weight: bold;
        font-size: 18px;
    }

    .topbar-right {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .search-box {
        background: white;
        border-radius: 20px;
        padding: 5px 15px;
        display: flex;
        align-items: center;
        margin: 10px auto;
        max-width: 400px;
        width: 100%;
    }

    .search-box input {
        border: none;
        outline: none;
        flex: 1;
    }

    .nav-bar {
        background: white;
        display: flex;
        justify-content: center;
        gap: 25px;
        padding: 12px 0;
        border-bottom: 1px solid #e5e7eb;
    }

    .nav-bar a {
        color: #333;
        font-weight: 500;
        text-decoration: none;
        padding: 8px 14px;
        border-radius: 4px;
        transition: all 0.2s ease;
    }

    .nav-bar a:hover {
        background-color: #f0f0f0;
        /* nền xám khi hover */
        color: #000;
    }

    .nav-bar a.active {
        background-color:
            <?= $cauhinh['mau_sac_giaodien'] ?? '#2563eb' ?>
        ;
        /* xanh khi active */
        color: white;
    }

    #page-wrapper {
        margin-left: 0 !important;
        padding: 0 15px;
    }

    .navbar-nav>li>a:hover {
        background-color: #f0f0f0 !important;
        /* nền xám nhạt */
    }

    /* Khi menu đang active */
    .navbar-nav>li.active>a {
        background-color: #007BFF !important;
        /* nền xanh */
        color: white !important;
        /* chữ trắng */
    }
</style>

<!-- Top Bar -->
<div class="topbar">
    <div class="topbar-left">
        <?= $tenDot ? 'Đợt: ' . htmlspecialchars($tenDot) : 'Chưa có đợt thực tập' ?>
    </div>
    <div class="topbar-center">

        <a style="color:rgb(255, 255, 255);" title="Đến trang web của Khoa">
            <?= $cauhinh['ten_trang'] ?? 'Hệ thống quản lý thực tập' ?></a>
    </div>
    <div class="topbar-right">

        <a href="pages/sinhvien/thongbaomoi" title="Thông báo mới" style="color: #ffffff;">
            <i class="fa fa-bell"></i>
            <?php if ($coThongBaoMoi): ?><span style="color:red;">●</span><?php endif; ?>
        </a>
        <a href="pages/sinhvien/thongtincanhan" title="Tài khoản" style="color: #ffffff;"><i class="fa fa-user"></i></a>
        <a href="/datn/logout" title="Đăng xuất" style="color: #ffffff;"><i
                class="fa-solid fa-arrow-right-from-bracket"></i></a>
        <a href="<?= $cauhinh['website_khoa'] ?? 'https://cntt.caothang.edu.vn/' ?>" title="Khoa Công Nghệ Thông Tin">
            <img src="<?= htmlspecialchars($cauhinh['logo'] ?? '/datn/uploads/Images/logo.jpg') ?>" alt="Logo"
                style="height: 30px; margin-left: 10px; vertical-align: middle;">
        </a>
    </div>
</div>

<!-- Navigation Menu -->
<div class="nav-bar">
    <a href="pages/sinhvien/trangchu" class="<?= strpos($currentPath, 'trangchu') !== false ? 'active' : '' ?>">Trang
        chủ</a>
    <a href="pages/sinhvien/dangkygiaygioithieu"
        class="<?= strpos($currentPath, 'dangkygiaygioithieu') !== false ? 'active' : '' ?>">Giấy giới thiệu</a>
    <a href="pages/sinhvien/baocaotuan" class="<?= strpos($currentPath, 'baocaotuan') !== false ? 'active' : '' ?>">Báo
        cáo tuần</a>
    <a href="pages/sinhvien/tainguyen" class="<?= strpos($currentPath, 'tainguyen') !== false ? 'active' : '' ?>">Tài
        nguyên</a>
    <a href="pages/sinhvien/nopketqua" class="<?= strpos($currentPath, 'nopketqua') !== false ? 'active' : '' ?>">Nộp
        kết quả</a>
    <a href="pages/sinhvien/khaosat" class="<?= strpos($currentPath, 'khaosat') !== false ? 'active' : '' ?>">
        Khảo sát
        <?php if (!empty($coKhaoSatMoi)): ?>
            <span style="color:red;font-size:16px;vertical-align:middle;">●</span>
        <?php endif; ?>
    </a>
    <form method="get" action="pages/sinhvien/timkiem.php" class="form-inline text-right" style="margin-right: -50px;">
        <input type="text" name="q" class="form-control" placeholder="Tìm kiếm thông tin..." style="width: 300px;"
            required>
        <button type="submit" class="btn btn-primary">Tìm kiếm</button>
    </form>
</div>
>>>>>>> e2493fb9eccefe43493535a26cd6705d9a037692
