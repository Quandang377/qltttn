<!-- Navbar -->
<nav class="navbar navbar-inverse navbar-fixed-top" role="navigation">
    <div class="container-fluid">
        <div class="navbar-header">
            <!-- Nút toggle -->
            <button type="button" class="navbar-toggle" id="sidebarToggle">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="#"><strong>HỆ THỐNG QUẢN TRỊ</strong></a>
        </div>
    </div>
</nav>

<!-- Sidebar -->
<aside class="navbar-default sidebar" role="navigation">
    <div class="sidebar-nav" id="sidebar-collapse">
        <ul class="nav" id="side-menu">
            <li><a href="admin/pages/trangchu"><i class="fa fa-home fa-fw"></i> Trang Chủ</a></li>
            <li><a href="admin/pages/modotthuctap"><i class="fa fa-briefcase fa-fw"></i> Quản lý đợt thực tập</a></li>
            <li><a href="admin/pages/quanlythongbao"><i class="fa fa-bell fa-fw"></i> Quản lý thông báo</a></li>
            <li><a href="admin/pages/quanlygiaygioithieu"><i class="fa fa-file fa-fw"></i> Quản lý giấy giới thiệu</a></li>
            <li><a href="admin/pages/quanlytainguyen"><i class="fa fa-folder fa-fw"></i> Quản lý tài nguyên</a></li>
            <li><a href="admin/pages/khaosat"><i class="fa fa-table fa-fw"></i> Khảo sát</a></li>
            <li><a href="admin/pages/quanlythanhvien"><i class="fa fa-user fa-fw"></i> Quản lý thành viên</a></li>
            <li><a href="admin/pages/quanlycongty"><i class="fa fa-building fa-fw"></i> Quản lý công ty</a></li>
            <li><a href="admin/pages/cauhinh"><i class="fa fa-cog fa-fw"></i> Cấu hình</a></li>
            <li><a href="admin/pages/thongtincanhan"><i class="fa fa-info fa-fw"></i> Thông tin cá nhân</a></li>
            <li><a href="/datn/logout"><i class="fa fa-sign-out fa-fw"></i> Đăng xuất</a></li>
        </ul>
    </div>
</aside>


<!-- /.sidebar -->
 <style>
body {
        font-family: 'Segoe UI', sans-serif;
    }

.navbar {
    background-color: #2c3e50;
    border: none;
}

.navbar-brand {
    color: #fff !important;
    font-weight: bold;
    font-size: 18px;
}

.sidebar {
    background-color:rgb(255, 255, 255);
    color: #ecf0f1;
}

.sidebar-header {
    padding: 20px 15px;
    font-size: 16px;
    font-weight: bold;
    background-color: #2c3e50;
    color: #ecf0f1;
    border-bottom: 1px solid #1a252f;
}

.sidebar-nav .nav > li > a {
    color:#2c3e50;
    padding: 12px 20px;
    display: block;
    transition: background 0.3s ease;
}

.sidebar-nav .nav > li > a:hover,
.sidebar-nav .nav > li.active > a {
    background-color: #2c3e50;
    color: #ecf0f1;
    text-decoration: none;
}

.sidebar-nav .nav > li > a i {
    margin-right: 8px;
}

.sidebar.collapsed {
    transform: translateX(-100%);
    visibility: hidden;
    pointer-events: none;
}
#page-wrapper {
    min-height: 100vh;
    padding-bottom: 60px; /* Để footer không bị che */
}
@media (min-width: 769px) {
    .sidebar {
        width: 220px;
        position: fixed;
        top: 0;
        left: 0;
        bottom: 0;
        overflow-y: auto;
        border-right: 1px solid #1a252f;
        transition: transform 0.3s ease, visibility 0.3s ease;
        z-index: 1000;
    }

    #page-wrapper {
        margin-left: 220px;
        transition: margin-left 0.3s ease;
    }
    .navbar-toggle {
    display: block !important;
    float: left;
    margin: 10px 15px;
    background-color: transparent;
    border: none;
    color: white;
}
    .sidebar.collapsed ~ #page-wrapper {
        margin-left: 0;
    }
}

@media (max-width: 768px) {
    .sidebar {
        width: 100%;
        position: static;
        margin-top: 50px;
    }

    .navbar-toggle {
        display: block;
    }

    #page-wrapper {
        margin-left: 0;
        padding: 15px;
    }
}

 </style>
 <script>
    document.addEventListener('DOMContentLoaded', function () {
        const toggleBtn = document.querySelector('.navbar-toggle');
        const sidebar = document.querySelector('.sidebar');

        toggleBtn.addEventListener('click', function () {
            sidebar.classList.toggle('collapsed');
        });
    });
 </script>