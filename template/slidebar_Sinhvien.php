<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";
$idTaiKhoan = $_SESSION['user']['ID_TaiKhoan'] ?? null;
$tenDot = '';
if ($idTaiKhoan) {
    $stmt = $conn->prepare("SELECT dt.TenDot 
        FROM SinhVien sv 
        LEFT JOIN DotThucTap dt ON sv.ID_Dot = dt.ID 
        WHERE sv.ID_TaiKhoan = ?");
    $stmt->execute([$idTaiKhoan]);
    $tenDot = $stmt->fetchColumn();
}
?>
<nav class="navbar navbar-inverse navbar-fixed-top" role="navigation">
    <div class="navbar-header">
        <a class="navbar-brand" style="color:rgb(255, 255, 255); font-weight: bold; text-shadow: 0 1px 6px #b6f5c2;">
            <?= $tenDot ? htmlspecialchars($tenDot) : 'Chưa có đợt thực tập' ?>
        </a>
    </div>
    <aside class="sidebar navbar-default" role="navigation">
        <div class="sidebar-nav navbar-collapse">
            <ul class="nav" id="side-menu">
                <li>
                    <a href="pages/sinhvien/trangchu">
                        <i class="fa fa-home fa-fw"></i> Trang chủ
                    </a>
                </li>
                <li>
                    <a href="pages/sinhvien/dangkygiaygioithieu">
                        <i class="fa fa-file-text-o fa-fw"></i> Đăng ký giấy giới thiệu
                    </a>
                </li>
                <li>
                    <a href="pages/sinhvien/baocaotuan">
                        <i class="fa fa-calendar-check-o fa-fw"></i> Báo cáo tuần
                    </a>
                </li>
                <li>
                    <a href="pages/sinhvien/tainguyen">
                        <i class="fa fa-folder-open-o fa-fw"></i> Tài nguyên
                    </a>
                </li>
                <li>
                    <a href="pages/sinhvien/xemdanhsachcongty">
                        <i class="fa fa-building-o fa-fw"></i> Danh sách công ty
                    </a>
                </li>
                <li>
                    <a href="pages/sinhvien/khaosat">
                        <i class="fa fa-list-alt fa-fw"></i> Khảo sát
                    </a>
                </li>
                <li>
                    <a href="pages/sinhvien/thongtincanhan">
                        <i class="fa fa-user fa-fw"></i> Thông tin cá nhân
                    </a>
                </li>
                <li>
                    <a href="/datn/logout"><i class="fa fa-sign-out"></i> Đăng xuất</a>
                </li>
            </ul>
        </div>
    </aside>
</nav>