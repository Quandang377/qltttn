<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";
$isLoggedIn = isset($_SESSION['user']); // 👈 chuyển lên đây
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
if ($idTaiKhoan) {
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
}
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

        <a href="<?= $isLoggedIn ? 'pages/sinhvien/thongbaomoi' : '#' ?>" class="<?= $isLoggedIn ? '' : 'guest-link' ?>"
            title="Thông báo mới" style="color: #ffffff;">
            <i class="fa fa-bell"></i><?php if ($coThongBaoMoi): ?><span style="color:red;">●</span><?php endif; ?>
        </a>
        <a href="<?= $isLoggedIn ? 'pages/sinhvien/thongtincanhan' : '#' ?>"
            class="<?= $isLoggedIn ? '' : 'guest-link' ?>" title="Tài khoản" style="color: #ffffff;">
            <i class="fa fa-user"></i>
        </a>

        <?php if ($isLoggedIn): ?>
            <!-- Nếu đã đăng nhập, hiển thị nút Đăng xuất -->
            <a href="/datn/logout" title="Đăng xuất" style="color: #ffffff;">
                <i class="fa-solid fa-arrow-right-from-bracket"></i>
            </a>
        <?php else: ?>
            <!-- Nếu chưa đăng nhập, hiển thị nút Đăng nhập -->
            <a href="/datn/login" title="Đăng nhập" style="color: #ffffff;">
                <i class="fa-solid fa-right-to-bracket"></i>
            </a>
        <?php endif; ?>
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
    <a href="pages/sinhvien/trangchu" class="<?= strpos($currentPath, 'trangchu') !== false ? 'active' : '' ?>">Trang
        chủ</a>

    <a href="<?= $isLoggedIn ? 'pages/sinhvien/dangkygiaygioithieu' : '#' ?>"
        class="<?= strpos($currentPath, 'dangkygiaygioithieu') !== false ? 'active' : '' ?> <?= !$isLoggedIn ? 'guest-link' : '' ?>">
        Giấy giới thiệu
    </a>

    <a href="<?= $isLoggedIn ? 'pages/sinhvien/baocaotuan' : '#' ?>"
        class="<?= strpos($currentPath, 'baocaotuan') !== false ? 'active' : '' ?> <?= !$isLoggedIn ? 'guest-link' : '' ?>">
        Báo cáo tuần
    </a>

    <a href="pages/sinhvien/tainguyen" class="<?= strpos($currentPath, 'tainguyen') !== false ? 'active' : '' ?>">Tài
        nguyên</a>

    <a href="<?= $isLoggedIn ? 'pages/sinhvien/nopketqua' : '#' ?>"
        class="<?= strpos($currentPath, 'nopketqua') !== false ? 'active' : '' ?> <?= !$isLoggedIn ? 'guest-link' : '' ?>">
        Nộp kết quả
    </a>

    <a href="<?= $isLoggedIn ? 'pages/sinhvien/khaosat' : '#' ?>"
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
    function submitSearchForm() {
        const keyword = document.getElementById('searchInput').value.trim();
        if (keyword) {
            window.location.href = '/datn/pages/sinhvien/timkiem?q=' + encodeURIComponent(keyword);
        }
        return false;
    }

    document.addEventListener('DOMContentLoaded', () => {
        const guestLinks = document.querySelectorAll('.guest-link');

        guestLinks.forEach(link => {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                Swal.fire({
                    title: 'Yêu cầu đăng nhập',
                    text: 'Bạn cần đăng nhập để sử dụng chức năng này.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Đăng nhập',
                    cancelButtonText: 'Hủy'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = '/datn/login';
                    }
                });
            });
        });
    });
</script>