<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/slidebar_Canbo.php";
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";

// Lấy danh sách đợt có trạng thái = 1
$stmt = $conn->prepare("SELECT ID, TenDot, Nam, Loai FROM dotthuctap WHERE TrangThai = 1");
$stmt->execute();
$dotthuctap = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Xử lý chọn đợt
$dot_duoc_chon = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['chon_dot'])) {
    $dot_duoc_chon = $_POST['dot_id'] ?? null;
}

// Xử lý upload file excel và import sinh viên
$import_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_excel'])) {
    $dot_duoc_chon = $_POST['dot_id_hidden'] ?? null;
    if (!$dot_duoc_chon) {
        $import_message = '<div class="alert alert-danger">Vui lòng chọn đợt trước khi import!</div>';
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    } elseif (isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] === UPLOAD_ERR_OK) {
        $fileName = $_FILES['excel_file']['name'];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowed = ['xls', 'xlsx'];
        if (in_array($ext, $allowed)) {
            require $_SERVER['DOCUMENT_ROOT'] . '/datn/vendor/autoload.php'; // Đảm bảo đã cài PhpSpreadsheet
            $inputFileName = $_FILES['excel_file']['tmp_name'];
            try {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($inputFileName);
                $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

                $success = 0;
                foreach ($sheetData as $i => $row) {
                    if ($i == 1) continue; 

                    $mssv = trim($row['B']);
                    $ho = trim($row['C']);
                    $ten = trim($row['D']);
                    $hoten = trim($ho . ' ' . $ten);

                    if (!$mssv || !$ho || !$ten) continue;

                    // Kiểm tra trùng MSSV trong cùng đợt
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM sinhvien WHERE MSSV = ? AND ID_Dot = ?");
                    $stmt->execute([$mssv, $dot_duoc_chon]);
                    if ($stmt->fetchColumn() > 0) continue;

                    // Kiểm tra tài khoản đã có chưa
                    $email = $mssv . '@caothang.edu.vn';
                    $stmt = $conn->prepare("SELECT ID_TaiKhoan FROM taikhoan WHERE TaiKhoan = ?");
                    $stmt->execute([$email]);
                    $rowTK = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($rowTK) {   
                        $id_taikhoan = $rowTK['ID_TaiKhoan'];
                    } else {
                        $matkhau_hash = password_hash($mssv, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("INSERT INTO taikhoan (TaiKhoan, MatKhau, VaiTro, TrangThai) VALUES (?, ?, 'Sinh viên', 1)");
                        $stmt->execute([$email, $matkhau_hash]);
                        $id_taikhoan = $conn->lastInsertId();
                    }

                    $stmt2 = $conn->prepare("INSERT INTO sinhvien (ID_TaiKhoan, ID_Dot, Ten, MSSV, TrangThai) VALUES (?, ?, ?, ?, 1)");
                    $stmt2->execute([$id_taikhoan, $dot_duoc_chon, $hoten, $mssv]);
                    $success++;
                }

                // Thành công hoặc không thành công đều reload lại trang
                header("Location: " . $_SERVER['REQUEST_URI']);
                exit;
            } catch (Exception $e) {
                $import_message = '<div class="alert alert-danger">Lỗi đọc file excel!</div>';
                header("Location: " . $_SERVER['REQUEST_URI']);
                exit;
            }
        } else {
            $import_message = '<div class="alert alert-danger">Chỉ chấp nhận file Excel (.xls, .xlsx)!</div>';
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        }
    } else {
        $import_message = '<div class="alert alert-danger">Vui lòng chọn file excel hợp lệ!</div>';
        header("Location: " . $_SERVER['REQUEST_URI']); 
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Import excel</title>
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php"; ?>
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
        <div id="page-wrapper">
            <div class="container-fluid">
                <h1 class="page-header">IMPORT EXCEL</h1>
                <h3 class="page-header">Chọn đợt để import</h3>
                <div class="row" style="margin-top: 15px;"></div>
                <br>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        Danh sách đợt thực tập đang mở
                    </div>
                    <div class="panel-body">
                        <div class="table-responsive">
                            <form method="post" id="form-chon-dot">
                                <table class="table table-striped table-bordered" id="table-dot">
                                    <thead>
                                        <tr>
                                            <th></th>
                                            <th>#</th>
                                            <th>Tên đợt</th>
                                            <th>Năm</th>
                                            <th>Ngành</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($dotthuctap as $i => $dot): ?>
                                        <tr>
                                            <td>
                                                <input type="radio" name="dot_id" value="<?php echo $dot['ID']; ?>" <?php if ($dot_duoc_chon == $dot['ID']) echo 'checked'; ?>>
                                            </td>
                                            <td><?php echo $i + 1; ?></td>
                                            <td><?php echo htmlspecialchars($dot['TenDot']); ?></td>
                                            <td><?php echo htmlspecialchars($dot['Nam']); ?></td>
                                            <td><?php echo htmlspecialchars($dot['Loai']); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <div class="col-md-8 col-md-offset-2 text-center">
                                    <button type="submit" class="btn btn-primary" style="margin-top: 10px;" name="chon_dot" id="btn-dot">Chọn đợt</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php if (!$dot_duoc_chon): ?>
                    <div class="alert alert-warning text-center" style="margin-top: 20px;">
                        Vui lòng chọn đợt thực tập để import dữ liệu!
                    </div>
                <?php else: ?>
                    <div class="alert alert-success text-center" style="margin-top: 20px;">
                        Đã chọn đợt thực tập: 
                        <strong>
                            <?php
                                $dot = array_filter($dotthuctap, fn($d) => $d['ID'] == $dot_duoc_chon);
                                $dot = array_values($dot)[0] ?? null;
                                echo $dot ? htmlspecialchars($dot['TenDot'] . ' (' . $dot['Nam'] . ')') : '';
                            ?>
                        </strong>
                    </div>
                    <?php echo $import_message; ?>
                    <div class="text-center" style="margin: 20px 0;">
                        <button type="button" class="btn btn-success" data-toggle="modal" data-target="#uploadModal">
                            <i class="fa fa-upload"></i> Tải lên file Excel
                        </button>
                    </div>
                <?php endif; ?>
                <div class="modal fade" id="uploadModal" tabindex="-1" role="dialog" aria-labelledby="uploadModalLabel">
                  <div class="modal-dialog" role="document">
                    <form method="post" enctype="multipart/form-data">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h4 class="modal-title" id="uploadModalLabel">Tải lên file Excel</h4>
                          <button type="button" class="close" data-dismiss="modal" aria-label="Đóng"><span aria-hidden="true">&times;</span></button>
                        </div>
                        <div class="modal-body">
                          <input type="file" name="excel_file" class="form-control" required accept=".xls,.xlsx">
                          <input type="hidden" name="dot_id_hidden" value="<?php echo htmlspecialchars($dot_duoc_chon); ?>">
                        </div>
                        <div class="modal-footer">
                          <button type="submit" name="upload_excel" class="btn btn-success">Tải lên</button>
                          <button type="button" class="btn btn-default" data-dismiss="modal">Hủy</button>
                        </div>
                      </div>
                    </form>
                  </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
