<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng ký giấy giới thiệu</title>
    <?php
        require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php";
    ?>
    <style>
        #page-wrapper {
            padding: 30px;
            min-height: 100vh;
            box-sizing: border-box;
            max-height: 100%;
        }
        tr.selected {
            background-color: #007bff !important;
            color: white;
        }
    </style>
</head>
<body>
    <div id="wrapper">  
        <?php
            require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/slidebar_Sinhvien.php";
        ?>
    
        <div id="page-wrapper">
            <div class="container-fluid">
                <h1 class="page-header">IMPORT EXCEL</h1>
                <h3 class="page-header">Chọn đợt để import</h3>
                
                <div class="row" style="margin-top: 15px;"></div>
                <br>

                <div class="panel panel-default">
                    <div class="panel-heading">
                        Danh sách phiếu giới thiệu thực tập
                    </div>
                    <div class="panel-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered" id="table-dot">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Tên đợt</th>
                                        <th>Năm</th>
                                        <th>Nghành</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>1</td>
                                        <td>CDTH21k1</td>
                                        <td>2025</td>
                                        <td>Công nghệ thông tin</td>
                                    </tr>
                                    <tr>
                                        <td>2</td>
                                        <td>CDTH21K2</td>
                                        <td>2025</td>
                                        <td>Công nghệ thông tin</td>
                                    </tr>
                                    <tr>
                                        <td>3</td>
                                        <td>CDTH21k1</td>
                                        <td>2025</td>
                                        <td>Công nghệ thông tin</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-md-8 col-md-offset-2 text-center">
                        <button type="button" class="btn btn-primary" style="margin-top: 10px;" id="btn-dot">Chọn</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php
        require $_SERVER['DOCUMENT_ROOT'] . "/datn/template/footer.php";
        require $_SERVER['DOCUMENT_ROOT'] . "/datn/template/datatable_init.php";
        initDataTableJS('table-dot',btnID:'btn-dot'); 
    ?>
</body>
</html>
