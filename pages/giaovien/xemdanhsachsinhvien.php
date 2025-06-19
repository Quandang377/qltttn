<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";
$id_gvhd = '6'; // Đặt mặc định là 6
$id_dot = '71'; // Đợt thực tập mặc định là 71

// Lấy danh sách sinh viên thuộc giáo viên này và đợt thực tập này
$stmt = $conn->prepare("
    SELECT sv.ID_TaiKhoan, sv.Ten, sv.MSSV, sv.Lop
    FROM SinhVien sv
    WHERE sv.ID_GVHD = ? AND sv.ID_Dot = ?
");
$stmt->execute([$id_gvhd, $id_dot]);
$sinhviens = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Danh sách sinh viên</title>
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php"; ?>
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
        <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/slidebar_Giaovien.php"; ?>
        <div id="page-wrapper">
            <div class="container-fluid">
                <h1 class="page-header">Danh sách sinh viên</h1>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        Danh sách sinh viên thuộc quản lí
                    </div>
                    <div class="panel-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered" id="table-dssv">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>MSSV</th>
                                        <th>Họ tên</th>
                                        <th>Lớp</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $stt = 1; foreach ($sinhviens as $sv): ?>
                                    <tr>
                                        <td><?php echo $stt++; ?></td>
                                        <td><?php echo htmlspecialchars($sv['MSSV']); ?></td>
                                        <td><?php echo htmlspecialchars($sv['Ten']); ?></td>
                                        <td><?php echo htmlspecialchars($sv['Lop']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
        <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/footer.php"; ?>
    <script>
    $(document).ready(function () {
        $('#table-dssv').DataTable({
            responsive: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/vi.json'
            }
        });
    });
    </script>
</body>
</html>