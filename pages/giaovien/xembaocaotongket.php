<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";

// Tạm thời lấy id tài khoản giáo viên là 6
$id_gvhd = 6;
$id_dot = 71;
$errorMsg = '';

// Đóng/mở cho phép nộp báo cáo tổng kết
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['luu_trangthai_tongket'])) {
    $trangthai_tongket = isset($_POST['trangthai_tongket']) ? 1 : 0;
    $stmt = $conn->prepare("SELECT ID FROM Baocaotongket WHERE ID_TaiKhoan = ?");
    $stmt->execute([$id_gvhd]);
    if ($stmt->fetch()) {
        $stmt = $conn->prepare("UPDATE Baocaotongket SET TrangThai = ?, NgayCapNhat = NOW() WHERE ID_TaiKhoan = ?");
        $stmt->execute([$trangthai_tongket, $id_gvhd]);
    } else {
        $stmt = $conn->prepare("INSERT INTO Baocaotongket (ID_TaiKhoan, TrangThai) VALUES (?, ?)");
        $stmt->execute([$id_gvhd, $trangthai_tongket]);
    }
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

// Lấy trạng thái cho phép nộp báo cáo tổng kết
$stmt = $conn->prepare("SELECT TrangThai FROM Baocaotongket WHERE ID_TaiKhoan = ?");
$stmt->execute([$id_gvhd]);
$trangthai_tongket = $stmt->fetchColumn();
if ($trangthai_tongket === false) $trangthai_tongket = 0;

// Lấy danh sách sinh viên thuộc giáo viên này và cùng đợt
$stmt = $conn->prepare("
    SELECT sv.ID_TaiKhoan, sv.Ten, sv.MSSV
    FROM SinhVien sv
    WHERE sv.ID_GVHD = ? AND sv.ID_Dot = ?
");
$stmt->execute([$id_gvhd, $id_dot]);
$sinhviens = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy trạng thái, đường dẫn báo cáo tổng kết, ngày nộp và số lần nộp/xóa/sửa của sinh viên
$baocao_tongket = [];
foreach ($sinhviens as $sv) {
    // Lấy file báo cáo mới nhất
    $stmt2 = $conn->prepare("SELECT TenFile, Dir, NgayNop FROM file WHERE ID_SV = ? AND Loai = 'Baocao' AND TrangThai = 1 ORDER BY ID DESC LIMIT 1");
    $stmt2->execute([$sv['ID_TaiKhoan']]);
    $row = $stmt2->fetch(PDO::FETCH_ASSOC);

    // Đếm tổng số file báo cáo đã nộp (bao gồm cả đã xóa/sửa)
    $stmt3 = $conn->prepare("SELECT COUNT(*) FROM file WHERE ID_SV = ? AND Loai = 'Baocao'");
    $stmt3->execute([$sv['ID_TaiKhoan']]);
    $solan = $stmt3->fetchColumn();

    if ($row) {
        $baocao_tongket[$sv['ID_TaiKhoan']] = [
            'TenFile' => $row['TenFile'],
            'Dir' => $row['Dir'],
            'NgayNop' => $row['NgayNop'],
            'SoLan' => $solan
        ];
    } else {
        $baocao_tongket[$sv['ID_TaiKhoan']] = [
            'TenFile' => null,
            'Dir' => null,
            'NgayNop' => null,
            'SoLan' => $solan
        ];
    }
}

// Xử lý tải xuống tất cả báo cáo thành file zip
if (isset($_GET['download_all']) && $_GET['download_all'] == 1) {
    $zip = new ZipArchive();
    $zipName = 'baocao_tongket_' . date('Ymd_His') . '.zip';
    $zipPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $zipName;
    if ($zip->open($zipPath, ZipArchive::CREATE) === TRUE) {
        foreach ($baocao_tongket as $sv_id => $bc) {
            if ($bc && file_exists($bc['Dir'])) {
                // Đặt tên file trong zip là đúng tên file gốc (không thêm MSSV)
                $zip->addFile($bc['Dir'], $bc['TenFile']);
            }
        }
        $zip->close();
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zipName . '"');
        header('Content-Length: ' . filesize($zipPath));
        readfile($zipPath);
        unlink($zipPath);
        exit;
    } else {
        $errorMsg = "Không thể tạo file zip!";
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Báo cáo tổng kết sinh viên</title>
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
                <h1 class="page-header">Báo cáo tổng kết sinh viên</h1>
                <?php if ($errorMsg): ?>
                    <div class="alert alert-danger"><?php echo $errorMsg; ?></div>
                <?php endif; ?>
                <!-- Form đóng/mở cho phép nộp báo cáo tổng kết -->
                <form method="post" class="mb-3" id="form-trangthai-tongket">
                    <div class="panel panel-default" style="max-width: 350px; margin-bottom: 20px;">
                        <div class="panel-heading"><strong>Đóng/mở nộp báo cáo tổng kết</strong></div>
                        <div class="panel-body" style="display: flex; align-items: center; justify-content: center; padding: 18px 0;">
                            <span style="margin-right: 12px;">Trạng thái:</span>
                            <input type="checkbox" name="trangthai_tongket" value="1" id="toggle-trangthai"
                                <?php if ($trangthai_tongket) echo 'checked'; ?>
                                data-toggle="toggle" data-on="Mở" data-off="Đóng"
                                data-onstyle="success" data-offstyle="danger"
                            >
                            <input type="hidden" name="luu_trangthai_tongket" value="1">
                        </div>
                    </div>
                </form>
                <!-- Nút tải xuống tất cả -->
                <form method="get" style="margin-bottom: 15px;">
                    <button type="submit" name="download_all" value="1" class="btn btn-success">
                        <i class="fa fa-download"></i> Tải xuống tất cả báo cáo (ZIP)
                    </button>
                </form>
                <!-- Bảng sinh viên và trạng thái báo cáo tổng kết -->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        Danh sách sinh viên thuộc quản lí
                    </div>
                    <div class="panel-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered" id="table-dsbaocao">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>MSSV</th>
                                        <th>Họ tên</th>
                                        <th>Trạng thái báo cáo tổng kết</th>
                                        <th>Ngày giờ tải lên</th>
                                        <th>Số lần nộp/xóa/sửa</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $stt = 1; foreach ($sinhviens as $sv): ?>
                                        <tr>
                                            <td><?php echo $stt++; ?></td>
                                            <td><?php echo htmlspecialchars($sv['MSSV']); ?></td>
                                            <td><?php echo htmlspecialchars($sv['Ten']); ?></td>
                                            <td>
                                                <?php if ($baocao_tongket[$sv['ID_TaiKhoan']]['TenFile']): ?>
                                                    <span class="text-success">Đã nộp</span>
                                                <?php else: ?>
                                                    <span class="text-muted">Chưa nộp</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php
                                                $ngayNop = $baocao_tongket[$sv['ID_TaiKhoan']]['NgayNop'];
                                                echo $ngayNop ? date('d/m/Y H:i', strtotime($ngayNop)) : '-';
                                                ?>
                                            </td>
                                            <td>
                                                <?php echo $baocao_tongket[$sv['ID_TaiKhoan']]['SoLan']; ?>
                                            </td>
                                            <td>
                                                <?php if ($baocao_tongket[$sv['ID_TaiKhoan']]['TenFile']): ?>
                                                    <a href="/datn/download.php?file=<?php echo urlencode(basename($baocao_tongket[$sv['ID_TaiKhoan']]['Dir'])); ?>"
                                                       class="btn btn-success btn-xs" title="Tải xuống báo cáo">
                                                        <i class="fa fa-download"></i> Tải xuống
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
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

    <link href="https://cdn.jsdelivr.net/npm/bootstrap4-toggle@3.6.1/css/bootstrap4-toggle.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap4-toggle@3.6.1/js/bootstrap4-toggle.min.js"></script>
    <script>
    $(function() {
        $('#toggle-trangthai').bootstrapToggle();
        $('#toggle-trangthai').change(function() {
            $('#form-trangthai-tongket').submit();
        });
    });
    $(document).ready(function () {
        $('#table-dsbaocao').DataTable({
            responsive: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/vi.json'
            }
        });
    });
    </script>
</body>
</html>
