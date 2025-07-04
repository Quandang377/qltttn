<nav class="navbar navbar-inverse navbar-fixed-top" role="navigation">
    <div class="container-fluid">
        <div class="navbar-header">
            <!-- Nút toggle -->
            <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#sidebar-collapse">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>

            <a class="navbar-brand" href="#">Cao đẳng kỹ thuật Cao Thắng</a>
        </div>
    </div>
</nav>

<!-- Sidebar -->
<aside class="navbar-default sidebar" role="navigation">
    <div class="sidebar-nav navbar-collapse collapse" id="sidebar-collapse">
        <ul class="nav" id="side-menu">
            <li><a href="admin/pages/trangchu"><i class="fa fa-home fa-fw"></i> Trang Chủ</a></li>
            <li><a href="admin/pages/modotthuctap"><i class="fa fa-briefcase fa-fw"></i> Quản lí đợt thực tập</a></li>
            <li><a href="admin/pages/quanlythongbao"><i class="fa fa-bell fa-fw"></i> Quản lí thông báo</a></li>
            <li><a href="admin/pages/quanlytainguyen"><i class="fa fa-folder fa-fw"></i> Quản lí tài nguyên</a></li>
            <li><a href="admin/pages/khaosat"><i class="fa fa-table fa-fw"></i> Khảo sát</a></li>
            <li><a href="admin/pages/quanlythanhvien"><i class="fa fa-user fa-fw"></i> Quản lí thành viên</a></li>
            <li><a href="admin/pages/cauhinh"><i class="fa fa-cog fa-fw"></i> Cấu hình</a></li>
            <li><a href="admin/pages/thongtincanhan"><i class="fa fa-info fa-fw"></i> Thông tin cá nhân</a></li>
            <li><a href="/datn/logout"><i class="fa fa-sign-out fa-fw"></i> Đăng xuất</a></li>
        </ul>
    </div>
</aside>

<!-- /.sidebar -->
 <style>
    #page-wrapper {
    padding-top: 50px;
}

/* Sidebar desktop */
@media (min-width: 769px) {
    .sidebar {
        width: 220px;
        position: fixed;
        top: 0px;
        left: 0;
        bottom: 0;
        background-color: #f8f8f8;
        overflow-y: auto;
        border-right: 1px solid #ddd;
    }

    #page-wrapper {
        margin-left: 220px;
        padding: 20px;
    }
}

/* Sidebar mobile */
@media (max-width: 768px) {
    .sidebar {
        width: 100%;
        position: static;
        background-color: #222;
        margin-top: 50px;

    }

    .sidebar-nav .nav > li > a {
        color: #fff;
        padding: 10px 15px;
        display: block;
        border-bottom: 1px solid #333;
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