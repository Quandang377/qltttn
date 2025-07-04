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
            <li>
                <a href="pages/giaovien/trangchu" ><i class="fa fa-home fa-fw"></i> Trang chủ</a>
            </li>
            <li>
                <a href="pages/giaovien/baocaotuan" ><i class="fa fa-file fa-fw"></i>
                    Xem báo cáo tuần</a>
            </li>
            <li>
                <a href="pages/giaovien/xemdanhsachsinhvien" ><i class="fa fa-user fa-fw"></i>
                    Danh sách sinh viên</a>
            </li>
            <li>
                <a href="pages/giaovien/khaosat" ><i class="fa fa-table fa-fw"></i> Khảo sát</a>
            </li>
            <li>
                <a href="pages/giaovien/xembaocaotongket" ><i class="fa fa-dashboard fa-fw"></i> Xem báo cáo tổng kết</a>
            </li>
            <li>
                <a href="pages/giaovien/thongtincanhan">
                    <i class="fa fa-user fa-fw"></i> Thông tin cá nhân
                </a>
            </li>
            <li><a href="/datn/logout"><i class="fa fa-sign-out"></i> Đăng xuất</a></li>
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

        .sidebar-nav .nav>li>a {
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