<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";

?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Quản lý thành viên</title>
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php"; ?>
</head>

<body>
    <div id="wrapper">
        <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/admin/template/slidebar.php"; ?>
        <div id="page-wrapper">
            <div class="container-fluid">
                <div class="row mt-5">
                    <div class="col-lg-12">
                        <h1 class="page-header">Quản Lý Thành Viên</h1>
                    </div>
                </div>
                <div class="row">
                    <form method="post" id="FormQuanLy">
                        <div class="row">
                            <div class="form-group col-sm-2">
                                <select name="loc" class="form-control" required>
                                    <option value="Cán bộ Khoa/Bộ môn">Cán bộ Khoa/Bộ môn</option>
                                    <option value="Giáo viên">Giáo viên</option>
                                    <option value="Sinh viên">Sinh viên</option>
                                    <option value="Admin">Admin</option>
                                </select>
                            </div>
                            <div class="form-group col-sm-1">
                                <button class="btn btn-primary">Thêm</button>
                            </div>
                            <div class="form-group col-sm-1">
                                <button class="btn btn-warning">Chỉnh sửa</button>
                            </div>
                            <div class="form-group col-sm-1">
                                <button class="btn btn-secondary">Xóa</button>
                            </div>
                        </div>
                        <div class="row">
                            <h3>Danh sách thành viên</h3>
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                </div>
                                <div class="panel-body">
                                    <div class="table-responsive">
                                        <table class="table" id="tableThanhVien">
                                            <thead>
                                                <tr>
                                                    <th></th>
                                                    <th>MSSV</th>
                                                    <th>Họ Tên</th>
                                                    <th>Trạng Thái</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="text-center">
                                    <button type="submit" class="btn btn-primary btn-lg mt-3">Xác nhận</button>
                                    <a href="/datn/pages/canbo/chitietdot?id=<?= urlencode($id) ?>"
                                        class="btn btn-default btn-lg">Thoát</a>
                                </div>
                            </div>
                    </form>
                </div>
            </div>
</body>

</html>

<script>
    $(document).ready(function () {
        var table = $('#tableThanhVien').DataTable({
            responsive: true,
            pageLength: 20,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/vi.json'
            }
        });

    });
    
</script>
