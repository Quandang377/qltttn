<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";
$isLoggedIn = isset($_SESSION['user']);
$idTaiKhoan = $_SESSION['user']['ID_TaiKhoan'] ?? null;
$currentPage = basename($_SERVER['PHP_SELF']);
$currentPath = $_SERVER['REQUEST_URI'];

$coThongBaoMoi = false;
$coKhaoSatMoi = false;
$tenDot = '';
$vaiTro = isset($_SESSION['VaiTro']); // Gán cứng cho giáo viên, nếu dùng nhiều vai trò thì lấy từ session


    // Kiểm tra thông báo mới
    // Lấy danh sách các ID_Dot mà giáo viên được phân công
    $stmt = $conn->prepare("SELECT ID_Dot FROM dot_giaovien WHERE ID_GVHD = ?");
    $stmt->execute([$idTaiKhoan]);
    $dsDot = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($dsDot)) {
        // Tạo chuỗi ID placeholders cho câu lệnh IN
        $placeholders = implode(',', array_fill(0, count($dsDot), '?'));
        $params = array_merge($dsDot, [$idTaiKhoan]); // thêm ID_TaiKhoan vào cuối

        $sql = "
            SELECT COUNT(*) FROM thongbao tb
            WHERE tb.TrangThai = 1 
            AND tb.ID_Dot IN ($placeholders)
            AND tb.ID NOT IN (
                SELECT ID_ThongBao FROM thongbao_xem WHERE ID_TaiKhoan = ?
            )
        ";
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $coThongBaoMoi = $stmt->fetchColumn() > 0;
    }

    // Kiểm tra khảo sát mới
   $stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM khaosat ks
    WHERE ks.TrangThai = 1
    AND (
        (
            ks.NguoiNhan = 'Tất cả'
            AND EXISTS (
                SELECT 1 FROM dot_giaovien dg
                WHERE dg.ID_GVHD = ?
                AND dg.ID_Dot = ks.ID_Dot
            )
        )
        OR (
            ks.NguoiNhan = 'Giáo viên'
            AND EXISTS (
                SELECT 1 FROM dot_giaovien dg
                WHERE dg.ID_GVHD = ?
                AND dg.ID_Dot = ks.ID_Dot
            )
        )
    )
    AND ks.ID NOT IN (
        SELECT ID_KhaoSat FROM phanhoikhaosat WHERE ID_TaiKhoan = ?
    )
");
$stmt->execute([$idTaiKhoan, $idTaiKhoan, $idTaiKhoan]);
$coKhaoSatMoi = $stmt->fetchColumn() > 0;


// Lấy cấu hình hệ thống
$stmt = $conn->query("SELECT * FROM cauhinh");
$cauhinh = [];
foreach ($stmt as $row) {
    $cauhinh[$row['Ten']] = $row['GiaTri'];
}
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    .topbar {
        background: linear-gradient(to right,
                <?= $cauhinh['mau_sac_giaodien'] ?? '#2563eb' ?>
            );
        padding: 15px 15px;
        color: white;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        z-index: 1000;
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
        margin-top: 8px;
        padding: 12px 0;
        border-bottom: 1px solid #e5e7eb;
        position: fixed;
        top: 50px; /* chiều cao của .topbar */
        left: 0;
        width: 100%;
        z-index: 999;
    }

    .nav-bar a {
        color: #333;
        font-weight: 500;
        text-decoration: none;
        height: 36px;
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

    .menu-toggle {
        display: none;
        background: none;
        border: none;
        font-size: 22px;
        color: #333;
        padding: 10px 15px;
        cursor: pointer;
        margin-left: 15px;
    }

    @media (max-width: 768px) {
        .nav-bar {
            flex-direction: column;
            align-items: flex-start;
            padding: 10px 15px;
            display: none;
        }

        .nav-bar.active {
            display: flex;
        }

        .nav-bar a,
        .nav-bar form {
            margin: 5px 0;
            width: 100%;
        }

        .menu-toggle {
            display: inline-block;
        }

        .topbar-center {
            display: none;
        }
    }

    /* Thêm padding-top cho phần nội dung bên dưới để tránh bị che khuất */
    body {
        padding-top: 102px; /* 50px topbar + 52px nav-bar (điều chỉnh nếu cần) */
    }
</style>

<!-- Top Bar -->
<div class="topbar">
    
    <div class="topbar-center">

        <a style="color:rgb(255, 255, 255);" title="Đến trang web của Khoa">
            <?= $cauhinh['ten_trang'] ?? 'Hệ thống quản lý thực tập' ?></a>
    </div>
    <div class="topbar-right">

        <a href="<?= $isLoggedIn ? 'pages/giaovien/thongbaomoi' : '#' ?>" class="<?= $isLoggedIn ? '' : 'guest-link' ?>"
            title="Thông báo mới" style="color: #ffffff;">
            <i class="fa fa-bell"></i><?php if ($coThongBaoMoi): ?><span style="color:red;">●</span><?php endif; ?>
        </a>
        <a href="<?= $isLoggedIn ? 'pages/giaovien/thongtincanhan' : '#' ?>"
            class="<?= $isLoggedIn ? '' : 'guest-link' ?>" title="Tài khoản" style="color: #ffffff;">
            <i class="fa fa-user"></i>
        </a>

            <!-- Nếu đã đăng nhập, hiển thị nút Đăng xuất -->
            <a href="/datn/logout" title="Đăng xuất" style="color: #ffffff;">
                <i class="fa-solid fa-arrow-right-from-bracket"></i>
            </a>
        
        <a href="<?= $cauhinh['website_khoa'] ?? 'https://cntt.caothang.edu.vn/' ?>" title="Khoa Công Nghệ Thông Tin">
            <img src="<?= htmlspecialchars($cauhinh['logo'] ?? '/datn/uploads/Images/logo.jpg') ?>" alt="Logo"
                style="height: 30px; margin-left: 10px; vertical-align: middle;">
        </a>
    </div>
</div>
<button class="menu-toggle" onclick="toggleMenu()">
    <i class="fa fa-bars"></i>
</button>

<!-- Navigation Menu -->
<div class="nav-bar">
    <a href="pages/giaovien/trangchu" class="<?= strpos($currentPath, 'trangchu') !== false ? 'active' : '' ?>">Trang
        chủ</a>

    <a href="<?= $isLoggedIn ? 'pages/giaovien/quanlybaocaotuan' : '#' ?>"
        class="<?= strpos($currentPath, 'quanlybaocaotuan') !== false ? 'active' : '' ?> <?= !$isLoggedIn ? 'guest-link' : '' ?>">
        Báo cáo tuần
    </a>

    <a href="pages/giaovien/xembaocaotongket" class="<?= strpos($currentPath, 'xembaocaotongket') !== false ? 'active' : '' ?>">Tổng kết</a>

    <a href="<?= $isLoggedIn ? 'pages/giaovien/khaosat' : '#' ?>"
        class="<?= strpos($currentPath, 'khaosat') !== false ? 'active' : '' ?> <?= !$isLoggedIn ? 'guest-link' : '' ?>">
        Khảo sát
        <?php if (!empty($coKhaoSatMoi)): ?>
            <span style="color:red;font-size:16px;vertical-align:middle;">●</span>
        <?php endif; ?>
    </a>
    <form id="menuSearchForm" class="form-inline text-right" style="margin-right: -50px;"
        onsubmit="return submitSearchForm();">
        <input type="text" name="q" id="searchInput" class="form-control" placeholder="Tìm kiếm thông tin..."
            style="width: 300px;" required> 
        <button type="submit" class="btn btn-primary">Tìm kiếm</button>
    </form>
</div>
<script>
    function toggleMenu() {
        const navBar = document.querySelector('.nav-bar');
        navBar.classList.toggle('active');
    }
    
</script>
