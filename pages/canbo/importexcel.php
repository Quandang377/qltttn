<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";

$id = $_POST['id_dot'] ?? $_GET['id'] ?? null;
if (!$id)
    die("Không tìm thấy ID đợt thực tập.");

// Lấy thông tin đợt được chọn
$stmt = $conn->prepare("SELECT ID, TenDot, Nam, BacDaoTao FROM dotthuctap WHERE ID = ? AND TrangThai = 1");
$stmt->execute([$id]);
$dot_info = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$dot_info) {
    die("Không tìm thấy đợt thực tập hoặc đợt đã bị khóa.");
}

$dot_duoc_chon = $id;


// Xử lý upload file excel và import sinh viên
$import_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_excel'])) {
    if (isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] === UPLOAD_ERR_OK) {
        $fileName = $_FILES['excel_file']['name'];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowed = ['xls', 'xlsx'];
        if (in_array($ext, $allowed)) {
            require $_SERVER['DOCUMENT_ROOT'] . '/datn/vendor/autoload.php'; // Đảm bảo đã cài PhpSpreadsheet
            $inputFileName = $_FILES['excel_file']['tmp_name'];
            try {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($inputFileName);
                $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

                // Lấy tên lớp từ dòng 2 (Lớp học phần)
                $lop_hoc_phan = '';
                
                // Debug: Log toàn bộ dòng 2
                if (isset($sheetData[2])) {
                    error_log("Debug - Dòng 2 content: " . print_r($sheetData[2], true));
                    
                    // Kiểm tra cột D (ô D2) - nơi chứa tên lớp như "CDN QTM 22A-Thực tập tốt nghiệp"
                    if (isset($sheetData[2]['D']) && !empty(trim($sheetData[2]['D']))) {
                        $class_info = trim($sheetData[2]['D']);
                        error_log("Debug - Found class in D2: " . $class_info);
                        
                        // Tách lấy phần trước dấu "-" để có tên lớp
                        $lop_parts = explode('-', $class_info, 2);
                        $lop_hoc_phan = trim($lop_parts[0]);
                        error_log("Debug - Extracted class: " . $lop_hoc_phan);
                    }
                    
                    // Nếu chưa tìm thấy trong cột D, thử tìm trong các cột khác của dòng 2
                    if (empty($lop_hoc_phan)) {
                        foreach ($sheetData[2] as $col => $cell) {
                            $cell = trim($cell ?? '');
                            if (!empty($cell) && (strpos($cell, 'CDN') !== false || strpos($cell, 'QTM') !== false || strpos($cell, '-Thực tập') !== false)) {
                                error_log("Debug - Found class in column $col: " . $cell);
                                $lop_parts = explode('-', $cell, 2);
                                $lop_hoc_phan = trim($lop_parts[0]);
                                error_log("Debug - Extracted class: " . $lop_hoc_phan);
                                break;
                            }
                        }
                    }
                }
                
                // Nếu vẫn không tìm thấy lớp trong dòng 2, thử tìm trong các dòng khác
                if (empty($lop_hoc_phan)) {
                    error_log("Debug - Class not found in row 2, searching other rows...");
                    for ($search_row = 1; $search_row <= 5; $search_row++) {
                        if (isset($sheetData[$search_row])) {
                            foreach ($sheetData[$search_row] as $cell) {
                                $cell = trim($cell ?? '');
                                if (!empty($cell) && (strpos($cell, 'CDN') !== false || strpos($cell, 'QTM') !== false || strpos($cell, '-Thực tập') !== false)) {
                                    error_log("Debug - Found class in row $search_row: " . $cell);
                                    $lop_parts = explode('-', $cell, 2);
                                    $lop_hoc_phan = trim($lop_parts[0]);
                                    if (!empty($lop_hoc_phan)) {
                                        error_log("Debug - Final extracted class: " . $lop_hoc_phan);
                                        break 2;
                                    }
                                }
                            }
                        }
                    }
                }
                
                // Fallback: nếu vẫn không tìm thấy, dùng giá trị mặc định
                if (empty($lop_hoc_phan)) {
                    $lop_hoc_phan = 'Chưa xác định';
                    error_log("Debug - Using default class name: " . $lop_hoc_phan);
                }

                $success = 0;
                $failed = 0;
                $duplicates = 0;
                $errors = [];
                
                // Fix AUTO_INCREMENT issue - reset to proper value
                try {
                    $stmt_fix = $conn->prepare("SELECT MAX(ID) as max_id FROM sinhvien");
                    $stmt_fix->execute();
                    $max_id = $stmt_fix->fetchColumn();
                    $next_id = ($max_id ? $max_id + 1 : 1);
                    $conn->exec("ALTER TABLE sinhvien AUTO_INCREMENT = $next_id");
                } catch (Exception $e) {
                    // Ignore if can't fix AUTO_INCREMENT
                }
                
                foreach ($sheetData as $i => $row) {
                    // Bỏ qua dòng đầu tiên và tìm dòng bắt đầu dữ liệu thực tế
                    if ($i <= 3) continue; // Bỏ qua dòng 1-3
                    
                    // Kiểm tra xem có phải dòng tiêu đề cột không (chứa "STT", "Mã SV", "Họ", "Tên")
                    $is_header_row = false;
                    foreach ($row as $cell) {
                        $cell_lower = strtolower(trim($cell ?? ''));
                        if ($cell_lower == 'stt' || $cell_lower == 'mã sv' || $cell_lower == 'họ' || $cell_lower == 'tên' || $cell_lower == 'ngày sinh') {
                            $is_header_row = true;
                            break;
                        }
                    }
                    
                    if ($is_header_row) {
                        error_log("Debug - Skipping header row $i");
                        continue;
                    }

                    $mssv = trim($row['B'] ?? '');
                    $ho = trim($row['C'] ?? '');
                    $ten = trim($row['D'] ?? '');
                    $ngay_sinh = trim($row['E'] ?? ''); // Ngày sinh giờ ở cột E
                    $hoten = trim($ho . ' ' . $ten);

                    // Debug: Log raw data
                    error_log("Row $i: MSSV='$mssv', Ho='$ho', Ten='$ten', NgaySinh='$ngay_sinh'");

                    // Bỏ qua dòng trống hoặc dòng không có dữ liệu hợp lệ
                    if (empty($mssv) && empty($ho) && empty($ten)) {
                        error_log("Debug - Skipping empty row $i");
                        continue;
                    }

                    if (!$mssv || !$ho || !$ten) {
                        $failed++;
                        if (!$mssv) $errors[] = "Dòng $i: Thiếu mã sinh viên";
                        if (!$ho) $errors[] = "Dòng $i: Thiếu họ";
                        if (!$ten) $errors[] = "Dòng $i: Thiếu tên";
                        continue;
                    }

                    // Kiểm tra trùng MSSV trong cùng đợt (chỉ kiểm tra trong đợt hiện tại)
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM sinhvien WHERE MSSV = ? AND ID_Dot = ?");
                    $stmt->execute([$mssv, $dot_duoc_chon]);
                    if ($stmt->fetchColumn() > 0) {
                        $duplicates++;
                        continue;
                    }

                    // Kiểm tra tài khoản đã có chưa (kiểm tra trong toàn hệ thống)
                    $email = $mssv . '@caothang.edu.vn';
                    $stmt = $conn->prepare("SELECT ID_TaiKhoan FROM taikhoan WHERE TaiKhoan = ?");
                    $stmt->execute([$email]);
                    $rowTK = $stmt->fetch(PDO::FETCH_ASSOC);

                    try {
                        if ($rowTK) {
                            // Tài khoản đã tồn tại, sử dụng lại ID tài khoản
                            $id_taikhoan = $rowTK['ID_TaiKhoan'];
                        } else {
                            // Tạo tài khoản mới cho sinh viên
                            $matkhau_hash = password_hash($mssv, PASSWORD_DEFAULT);
                            $stmt = $conn->prepare("INSERT INTO taikhoan (TaiKhoan, MatKhau, VaiTro, TrangThai) VALUES (?, ?, 'Sinh viên', 1)");
                            $stmt->execute([$email, $matkhau_hash]);
                            $id_taikhoan = $conn->lastInsertId();
                        }

                        // Xử lý ngày sinh - chuyển đổi format nếu cần
                        $ngay_sinh_formatted = null;
                        if (!empty($ngay_sinh)) {
                            // Nếu ngày sinh là số Excel date
                            if (is_numeric($ngay_sinh)) {
                                try {
                                    $ngay_sinh_formatted = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($ngay_sinh)->format('Y-m-d');
                                } catch (Exception $e) {
                                    // Nếu lỗi khi convert Excel date, thử parse như string
                                    $date = date_create($ngay_sinh);
                                    if ($date) {
                                        $ngay_sinh_formatted = $date->format('Y-m-d');
                                    }
                                }
                            } else {
                                // Nếu ngày sinh là string, thử parse nhiều format khác nhau
                                $formats = ['d/m/Y', 'm/d/Y', 'Y-m-d', 'd-m-Y', 'm-d-Y'];
                                foreach ($formats as $format) {
                                    $date = DateTime::createFromFormat($format, $ngay_sinh);
                                    if ($date && $date->format($format) === $ngay_sinh) {
                                        $ngay_sinh_formatted = $date->format('Y-m-d');
                                        break;
                                    }
                                }
                                
                                // Nếu vẫn không parse được, thử strtotime
                                if (!$ngay_sinh_formatted) {
                                    $timestamp = strtotime($ngay_sinh);
                                    if ($timestamp !== false) {
                                        $ngay_sinh_formatted = date('Y-m-d', $timestamp);
                                    }
                                }
                            }
                        }
                        
                        // Debug: Log thông tin xử lý ngày sinh
                        if (empty($ngay_sinh)) {
                            // Không log lỗi cho ngày sinh trống, chỉ để NULL
                        } elseif (!$ngay_sinh_formatted) {
                            $errors[] = "Sinh viên $mssv ($hoten): Không thể parse ngày sinh '$ngay_sinh'";
                        }

                        
                        // Thêm sinh viên vào đợt mới (có thể dùng tài khoản cũ)
                        // Debug: Log dữ liệu trước khi insert
                        error_log("Debug - Insert data: ID_TaiKhoan=$id_taikhoan, ID_Dot=$dot_duoc_chon, Ten='$hoten', MSSV='$mssv', Lop='$lop_hoc_phan', NgaySinh='$ngay_sinh_formatted'");
                        
                        // Thực hiện insert với retry nếu có lỗi PRIMARY KEY
                        $insert_success = false;
                        $retry_count = 0;
                        $max_retries = 3;
                        
                        while (!$insert_success && $retry_count < $max_retries) {
                            try {
                                $stmt2 = $conn->prepare("INSERT INTO sinhvien (ID_TaiKhoan, ID_Dot, Ten, MSSV, Lop, NgaySinh, TrangThai) VALUES (?, ?, ?, ?, ?, ?, 1)");
                                $stmt2->execute([$id_taikhoan, $dot_duoc_chon, $hoten, $mssv, $lop_hoc_phan, $ngay_sinh_formatted]);
                                $insert_success = true;
                                $success++;
                            } catch (PDOException $e) {
                                if ($e->getCode() == 23000 && strpos($e->getMessage(), 'Duplicate entry') !== false && strpos($e->getMessage(), 'PRIMARY') !== false) {
                                    // Lỗi trùng PRIMARY KEY, thử fix AUTO_INCREMENT và retry
                                    $retry_count++;
                                    try {
                                        $stmt_fix = $conn->prepare("SELECT MAX(ID) as max_id FROM sinhvien");
                                        $stmt_fix->execute();
                                        $max_id = $stmt_fix->fetchColumn();
                                        $next_id = ($max_id ? $max_id + 1 : 1);
                                        $conn->exec("ALTER TABLE sinhvien AUTO_INCREMENT = $next_id");
                                    } catch (Exception $fix_e) {
                                        // Ignore fix error
                                    }
                                    
                                    if ($retry_count >= $max_retries) {
                                        throw $e; // Re-throw if max retries reached
                                    }
                                } else {
                                    throw $e; // Re-throw other errors immediately
                                }
                            }
                        }
                    } catch (Exception $e) {
                        $failed++;
                        $error_msg = $e->getMessage();
                        // Làm cho thông báo lỗi dễ hiểu hơn
                        if (strpos($error_msg, 'Duplicate entry') !== false && strpos($error_msg, 'PRIMARY') !== false) {
                            $errors[] = "Lỗi ID trùng lặp cho sinh viên $mssv ($hoten) - Vui lòng thử lại";
                        } else {
                            $errors[] = "Lỗi khi thêm sinh viên $mssv ($hoten): " . $error_msg;
                        }
                    }
                }

                // Tạo thông báo kết quả import
                $total_processed = $success + $failed + $duplicates;
                if ($success > 0) {
                    $import_message = '<div class="alert alert-success">
                        <h4><i class="fa fa-check-circle"></i> Import thành công!</h4>
                        <p><strong>Tổng số dòng xử lý:</strong> ' . $total_processed . '</p>
                        <p><strong>Thành công:</strong> ' . $success . ' sinh viên</p>';
                    
                    if ($duplicates > 0) {
                        $import_message .= '<p><strong>Trùng lặp trong đợt này (bỏ qua):</strong> ' . $duplicates . ' sinh viên</p>';
                    }
                    
                    if ($failed > 0) {
                        $import_message .= '<p><strong>Thất bại:</strong> ' . $failed . ' sinh viên</p>';
                    }
                    
                    if (!empty($lop_hoc_phan)) {
                        $import_message .= '<p><strong>Lớp học phần:</strong> ' . htmlspecialchars($lop_hoc_phan) . '</p>';
                    }
                    
                    $import_message .= '</div>';
                } else {
                    $import_message = '<div class="alert alert-warning">
                        <h4><i class="fa fa-exclamation-triangle"></i> Không có sinh viên nào được import!</h4>
                        <p><strong>Tổng số dòng xử lý:</strong> ' . $total_processed . '</p>';
                    
                    if ($duplicates > 0) {
                        $import_message .= '<p><strong>Trùng lặp trong đợt này (bỏ qua):</strong> ' . $duplicates . ' sinh viên</p>';
                    }
                    
                    if ($failed > 0) {
                        $import_message .= '<p><strong>Dữ liệu không hợp lệ:</strong> ' . $failed . ' dòng</p>';
                    }
                    
                    $import_message .= '<p>Vui lòng kiểm tra lại định dạng file Excel và thử lại.</p></div>';
                }

                // Hiển thị chi tiết lỗi nếu có
                if (!empty($errors)) {
                    $import_message .= '<div class="alert alert-danger">
                        <h4><i class="fa fa-times-circle"></i> Chi tiết lỗi:</h4>
                        <ul>';
                    foreach (array_slice($errors, 0, 10) as $error) { // Chỉ hiển thị 10 lỗi đầu tiên
                        $import_message .= '<li>' . htmlspecialchars($error) . '</li>';
                    }
                    if (count($errors) > 10) {
                        $import_message .= '<li>... và ' . (count($errors) - 10) . ' lỗi khác</li>';
                    }
                    $import_message .= '</ul></div>';
                }

                // Không redirect nữa, để hiển thị thông báo
                // header("Location: " . $_SERVER['REQUEST_URI']);
                // exit;
            } catch (Exception $e) {
                $import_message = '<div class="alert alert-danger">
                    <h4><i class="fa fa-times-circle"></i> Lỗi đọc file Excel!</h4>
                    <p>Chi tiết lỗi: ' . htmlspecialchars($e->getMessage()) . '</p>
                    <p>Vui lòng kiểm tra lại file Excel và thử lại.</p>
                </div>';
            }
        } else {
            $import_message = '<div class="alert alert-danger">
                <h4><i class="fa fa-times-circle"></i> File không hợp lệ!</h4>
                <p>Chỉ chấp nhận file Excel (.xls, .xlsx)!</p>
            </div>';
        }
    } else {
        $import_message = '<div class="alert alert-danger">
            <h4><i class="fa fa-times-circle"></i> Lỗi tải file!</h4>
            <p>Vui lòng chọn file Excel hợp lệ!</p>
        </div>';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Import excel</title>
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php";

    ?>

    <style>
        #page-wrapper {
            padding: 30px;
            min-height: 100vh;
            box-sizing: border-box;
            max-height: 100%;
        }

        .panel-primary > .panel-heading {
            background-color: #337ab7;
            border-color: #337ab7;
        }

        .help-block {
            color: #737373;
            font-style: italic;
        }

        .btn-lg {
            margin: 0 10px;
        }

        .alert-info {
            background-color: #d9edf7;
            border-color: #bce8f1;
            color: #31708f;
        }

        .form-control:focus {
            border-color: #337ab7;
            box-shadow: 0 0 0 0.2rem rgba(51, 122, 183, 0.25);
        }
    </style>
</head>

<body>

    <div id="wrapper">
        <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/slidebar_CanBo.php"; ?>
        <div id="page-wrapper">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12">
                        <h1 class="page-header">
                            <i class="fa fa-upload"></i> IMPORT EXCEL
                        </h1>
                    </div>
                </div>
                
                <!-- Thông tin đợt thực tập -->
                <div class="row">
                    <div class="col-lg-12">
                        <div class="alert alert-info">
                            <h4><i class="fa fa-info-circle"></i> Thông tin đợt thực tập</h4>
                            <p class="mb-0">
                                <strong>Tên đợt:</strong> <?php echo htmlspecialchars($dot_info['TenDot']); ?> <br>
                                <strong>Năm:</strong> <?php echo htmlspecialchars($dot_info['Nam']); ?> <br>
                                <strong>Ngành:</strong> <?php echo htmlspecialchars($dot_info['BacDaoTao']); ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Hiển thị thông báo import -->
                <?php if ($import_message): ?>
                    <div class="row">
                        <div class="col-lg-12">
                            <?php echo $import_message; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Form upload Excel -->
                <div class="row">
                    <div class="col-lg-8 col-lg-offset-2">
                        <div class="panel panel-primary">
                            <div class="panel-heading">
                                <h3 class="panel-title">
                                    <i class="fa fa-file-excel-o"></i> Tải lên file Excel danh sách sinh viên
                                </h3>
                            </div>
                            <div class="panel-body">
                                <form method="post" enctype="multipart/form-data" id="uploadForm">
                                    <div class="form-group">
                                        <label for="excel_file">Chọn file Excel (.xls, .xlsx):</label>
                                        <input type="file" name="excel_file" id="excel_file" class="form-control" 
                                               required accept=".xls,.xlsx">
                                        <small class="help-block">
                                            <i class="fa fa-info-circle"></i> 
                                            File Excel phải có cấu trúc: Mã SV (cột B), Họ (cột C), Tên (cột D), Ngày sinh (cột E).
                                            Tên lớp sẽ được lấy từ dòng "Lớp học phần:" trong file.
                                        </small>
                                    </div>
                                    <div class="form-group text-center">
                                        <button type="submit" name="upload_excel" class="btn btn-primary btn-lg">
                                            <i class="fa fa-upload"></i> Tải lên và Import
                                        </button>
                                        <a href="javascript:history.back()" class="btn btn-default btn-lg">
                                            <i class="fa fa-arrow-left"></i> Quay lại
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Hướng dẫn sử dụng -->
                <div class="row">
                    <div class="col-lg-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h3 class="panel-title">
                                    <i class="fa fa-question-circle"></i> Hướng dẫn sử dụng
                                </h3>
                            </div>
                            <div class="panel-body">
                                <ol>
                                    <li>Chuẩn bị file Excel với định dạng đúng</li>
                                    <li>Dòng 2 phải chứa thông tin "Lớp học phần: [Tên lớp]-Thực tập tốt nghiệp"</li>
                                    <li>Bảng dữ liệu bắt đầu từ dòng có tiêu đề cột</li>
                                    <li>Các cột dữ liệu:
                                        <ul>
                                            <li><strong>Cột B:</strong> Mã sinh viên</li>
                                            <li><strong>Cột C:</strong> Họ</li>
                                            <li><strong>Cột D:</strong> Tên</li>
                                            <li><strong>Cột E:</strong> Ngày sinh (dd/mm/yyyy)</li>
                                        </ul>
                                    </li>
                                    <li>Nhấn "Tải lên và Import" để import dữ liệu</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
    require $_SERVER['DOCUMENT_ROOT'] . "/datn/template/footer.php"
        ?>

</body>

</html>