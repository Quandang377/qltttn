<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý giấy giới thiệu</title>
   <?php
    require_once $_SERVER['DOCUMENT_ROOT'] . "/qltttn/template/head.php";
    ?>
    <style>
    #page-wrapper {
        padding: 30px;
        min-height: 100vh;
        box-sizing: border-box;
        max-height: 100%;
    }
</style>
</head>
<body>
    <div id="wrapper">
        <?php
    require_once $_SERVER['DOCUMENT_ROOT'] . "/qltttn/template/slidebar_Giaovien.php";
    ?>
        
        <div id="page-wrapper">
            <div class="container-fluid">
                <div class="page-header">
                    <h1>
                        Quản lý giấy giới thiệu
                    </h1>
                </div>
                <div class="row">
                    <div class="btn-group col-md-4" data-toggle="buttons">
                        <label class="btn btn-default active" id="btnapp">
                            <input type="radio" name="status" id="approved" autocomplete="off" checked> Đã duyệt
                        </label>
                        <label class="btn btn-default" id="btnpen">
                            <input type="radio" name="status" id="pending" autocomplete="off"> Chưa duyệt
                        </label>
                    </div>
                    <div class="col-md-4">
                        <input type="text" class="form-control col-md-8" id="name" placeholder="Tìm kiếm theo tên">
                    </div>
                    <div class="col-md-4">
                        <div class="btn btn-success">
                            <i class="fa fa-search"></i>
                        </div>
                    </div>
                </div>
                
                <div id="panel-approved">
                    <div class="col-md-4">
                        <div class="panel panel-default" style="margin-top: 15px;">
                            <div class="panel-heading">
                            Đặng Minh Quân
                            </div>
                            <div class="pannel-body" style="padding: 15px;">
                            Công ty VNG
                            </div>
                        
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="panel panel-default" style="margin-top: 15px;">
                            <div class="panel-heading">
                            Đặng Minh Quân
                            </div>
                            <div class="pannel-body" style="padding: 15px;">
                            Công ty VNG
                            </div>
                        
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="panel panel-default" style="margin-top: 15px;">
                            <div class="panel-heading">
                            Đặng Minh Quân
                            </div>
                            <div class="pannel-body" style="padding: 15px;">
                            Công ty VNG
                            </div>
                        
                        </div>
                    </div>
                    
                <div id="panel-pending" style="display: none;">

                </div>
            </div>
            
        </div>
    </div>

    <!-- Scripts -->
     <script>
    document.getElementById('approved').addEventListener('change', function () {
    document.getElementById('panel-approved').style.display = 'block';
    document.getElementById('panel-pending').style.display = 'none';
    document.getElementById('btnapp').classList.add('active');
    document.getElementById('btnpen').classList.remove('active');
});

document.getElementById('pending').addEventListener('change', function () {
    document.getElementById('panel-approved').style.display = 'none';
    document.getElementById('panel-pending').style.display = 'block';
    document.getElementById('btnpen').classList.add('active');
    document.getElementById('btnapp').classList.remove('active');
});

</script>
    <script src="js/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/metisMenu.min.js"></script>
    <script src="js/startmin.js"></script>
</body>
</html>