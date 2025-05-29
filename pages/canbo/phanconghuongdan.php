<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Phân công hướng dẫn</title>
    <link rel="stylesheet" href=".../access/css/styles/style_phanconghuongdan.css"> 
    <?php
    require_once $_SERVER['DOCUMENT_ROOT'] . "/qltttn/template/head.php";
    ?>
</head>
<body>
    <div id="wrapper">
        
    <?php
    require_once $_SERVER['DOCUMENT_ROOT'] . "/qltttn/template/slidebar_CanBo.php";
    ?>
<div id="page-wrapper">
    <div class="container-fluid">
        <div class="row mt-5">
            <div class="col-lg-12">
                <h1 class="page-header">CĐTH21K1</h1>
            </div>
            <!-- /.col-lg-12 -->
            <div class="form-container">
                <form id="intershipForm" method="post">
                    <div class="row mb-3">
                    <div class="col-md-4 col-md-offset-4">
                        <div class="form-group">
                            <label >Chọn Giáo Viên</label>
                            <select id="nguoiQL" name="nguoiQl"class="form-control">
                            <option value="Lữ Cao Tiến">Lữ Cao Tiến</option>
                            <option value="Lữ Cao Tiến">Lữ Cao Tiến</option>
                            </select>
                        </div>
                        </div>
                        
                    </div>
                
            </div>
        </div>
        <div id="containerDotThucTap" class="mt-5">
            <h2>Chọn sinh viên</h2>
            <div id="listDotThucTap" class="row">
        </div>
        <div class="row">
                        <div class="col-lg-12">
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                </div>
                                <div class="panel-body">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th></th>
                                                    <th>Họ Tên</th>
                                                    <th>Lớp</th>
                                                    <th>Ngành</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <tr onclick="toggleCheckbox(this)">
                                                        <td><input type="checkbox" name="chon[]" value="1"></td>
                                                        <td>Đặng Minh Quân</td>
                                                        <td>2021</td>
                                                        <td>Công nghệ thông tin</td>
                                                    </tr>
                                                </tr>
                                               <tr>
                                                    <tr onclick="toggleCheckbox(this)">
                                                        <td><input type="checkbox" name="chon[]" value="1"></td>
                                                        <td>Đặng Minh Quân</td>
                                                        <td>2021</td>
                                                        <td>Công nghệ thông tin</td>
                                                    </tr>
                                                </tr>
                                                <tr>
                                                    <tr onclick="toggleCheckbox(this)">
                                                        <td><input type="checkbox" name="chon[]" value="1"></td>
                                                        <td>Đặng Minh Quân</td>
                                                        <td>2021</td>
                                                        <td>Công nghệ thông tin</td>
                                                    </tr>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <!-- /.table-responsive -->
                                </div>
                                <!-- /.panel-body -->
                            </div>
                            <!-- /.panel -->
                        </div>
                        <!-- /.col-lg-6 -->
                    </div>
                                
    <div class="row">
  <div class=" col-md-offset text-center">
    <button  type="button" class=" btn btn-primary btn-lg mt-3">Xác nhận</button>
  </div>
</div>

</form>
</div>
    </div>
<script>
  function toggleCheckbox(row) {
    if (event.target.type === 'checkbox') return;
    const checkbox = row.querySelector('input[type="checkbox"]');
    checkbox.checked = !checkbox.checked;
  }
</script>