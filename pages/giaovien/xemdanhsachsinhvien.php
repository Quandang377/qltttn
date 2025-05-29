<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Danh sách sinh viên</title>
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
</style>
</head>
<body>
    <div id="wrapper">
        <?php
    require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/slidebar_Giaovien.php";
    ?>
        <div id="page-wrapper">
            <div class="container-fluid">
                <h1 class="page-header">Danh sách sinh viên</h1>
                    </div>
                <br>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <div class="row">
                            <div class="col-md-4">
                                Danh sách sinh viên thuộc quản lí 
                            </div>
                            <div class="col-md-4">
                                <input type="text" class="form-control" id="MSSV" placeholder="Tìm kiếm theo MSSV">
                            </div>
                            <div class="col-md-4">
                                <div class="btn btn-success">
                                    <i class="fa fa-search"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="panel-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Họ tên</th>
                                        <th>Lớp</th>
                                        <th>Nghành</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>1</td>
                                        <td>Đặng Minh Quân</td>
                                        <td>CĐTH21B</td>
                                        <td>Công nghệ thông tin</td>
                                    </tr>
                                    <tr>
                                        <td>1</td>
                                        <td>Đặng Minh Quân</td>
                                        <td>CĐTH21B</td>
                                        <td>Công nghệ thông tin</td>
                                    </tr>
                                    <tr>
                                        <td>1</td>
                                        <td>Đặng Minh Quân</td>
                                        <td>CĐTH21B</td>
                                        <td>Công nghệ thông tin</td>
                                    </tr><tr>
                                        <td>1</td>
                                        <td>Đặng Minh Quân</td>
                                        <td>CĐTH21B</td>
                                        <td>Công nghệ thông tin</td>
                                    </tr>
                                    <tr>
                                        <td>1</td>
                                        <td>Đặng Minh Quân</td>
                                        <td>CĐTH21B</td>
                                        <td>Công nghệ thông tin</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
</script>
</body>
</html>