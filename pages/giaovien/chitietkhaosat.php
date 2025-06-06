<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Tạo khảo sát</title>
    <?php
    require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php";
    ?>
</head>
<body>
    <div id="wrapper">
    <?php
    require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/slidebar_GiaoVien.php";
    ?>
        <div id="page-wrapper">
            <div class="container-fluid">
                <div class="page-header">
                    <h1>
                        Khảo Sát Thực Tập
                    </h1>
                </div>
        <div id="containerKhaoSat" class="mt-3">
            <div id="listKhaoSat" class="row">
        </div>
        <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    Danh sách người phản hồi
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Người phản hồi</th>
                                    <th>Thời gian phản hồi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr style="cursor: pointer;" onclick="hienPhanHoi('Đặng Minh Quân')">
                                <td>1</td>
                                <td>Đặng Minh Quân</td>
                                <td>12:30 1/1/2025</td>
                                </tr>
                                <tr style="cursor: pointer;" onclick="hienPhanHoi('Đặng Minh Quân')">
                                <td>1</td>
                                <td>Đặng Minh Quân</td>
                                <td>12:30 1/1/2025</td>
                                </tr>
                                <tr style="cursor: pointer;" onclick="hienPhanHoi('Đặng Minh Quân')">
                                <td>2</td>
                                <td>Đặng Minh Quân</td>
                                <td>12:30 1/1/2025</td>
                                </tr>
                                <tr style="cursor: pointer;" onclick="hienPhanHoi('Đặng Minh Quân')">
                                <td>3</td>
                                <td>Đặng Minh Quân</td>
                                <td>12:30 1/1/2025</td>
                                </tr>
                                <tr style="cursor: pointer;" onclick="hienPhanHoi('Đặng Minh Quân')">
                                <td>4</td>
                                <td>Đặng Minh Quân</td>
                                <td>12:30 1/1/2025</td>
                                </tr>
                                <tr style="cursor: pointer;" onclick="hienPhanHoi('Đặng Minh Quân')">
                                <td>5</td>
                                <td>Đặng Minh Quân</td>
                                <td>12:30 1/1/2025</td>
                                </tr>
                                <tr style="cursor: pointer;" onclick="hienPhanHoi('Đặng Minh Quân')">
                                <td>6</td>
                                <td>Đặng Minh Quân</td>
                                <td>12:30 1/1/2025</td>
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
<div class="modal fade" id="modalPhanHoi" tabindex="-1" role="dialog" aria-labelledby="modalPhanHoiLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Đóng"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="modalPhanHoiLabel">Đặng Minh Quân</h4>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label>Câu hỏi số 1</label>
          <input type="text" class="form-control" value="Câu trả lời" readonly>
        </div>
        <div class="form-group">
          <label>Câu hỏi số 2</label>
          <input type="text" class="form-control" value="Câu trả lời" readonly>
        </div>
        <div class="form-group">
          <label>Câu hỏi số 3</label>
          <input type="text" class="form-control" value="Câu trả lời" readonly>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-dismiss="modal">Đóng</button>
      </div>
    </div>
  </div>
</div>
</div>  
<script>
function hienPhanHoi(ten) {
    document.getElementById('modalPhanHoiLabel').textContent = ten;
    $('#modalPhanHoi').modal('show');
}
</script>
</div

