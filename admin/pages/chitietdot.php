<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/vendor/autoload.php';
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$id = $_POST['id_dot'] ?? $_GET['id'] ?? null;
if (!$id) die("Không tìm thấy ID đợt thực tập.");
// Lấy thông tin đợt thực tập
   $stmt = $conn->prepare("
    SELECT d.ID, d.TenDot, d.Loai, d.Nam, d.NguoiMoDot, d.NguoiQuanLy, d.ThoiGianBatDau, d.ThoiGianKetThuc, d.TrangThai,
        COALESCE(cb1.Ten, ad1.Ten) AS TenNguoiMoDot,
        COALESCE(cb2.Ten, ad2.Ten) AS TenNguoiQuanLy
    FROM DOTTHUCTAP d
    LEFT JOIN CanBoKhoa cb1 ON d.NguoiMoDot = cb1.ID_TaiKhoan
    LEFT JOIN Admin ad1 ON d.NguoiMoDot = ad1.ID_TaiKhoan
    LEFT JOIN CanBoKhoa cb2 ON d.NguoiQuanLy = cb2.ID_TaiKhoan
    LEFT JOIN Admin ad2 ON d.NguoiQuanLy = ad2.ID_TaiKhoan
    WHERE d.ID = :id
");
$stmt->execute(['id' => $id]);
$dot = $stmt->fetch();

if (!$dot) {
    die("Không tìm thấy đợt thực tập.");
}
// Nếu xuất Excel
if (isset($_GET['export_excel']) && $_GET['export_excel'] == 1) {
    ob_clean(); // Dọn sạch output buffer   
    header_remove();
    // Lấy dữ liệu sinh viên - GVHD
    $stmt = $conn->prepare("
    SELECT 
        SV.MSSV, SV.Ten AS TenSV, SV.NgaySinh, SV.Lop, GV.Ten AS TenGVHD
    FROM sinhvien SV
    LEFT JOIN giaovien GV ON SV.ID_GVHD = GV.ID_TaiKhoan
    WHERE SV.ID_Dot = :id
    ORDER BY SUBSTRING_INDEX(SV.Ten, ' ', -1) ASC
    ");
    $stmt->execute(['id' => $id]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Chuẩn bị dữ liệu (giả định biến $data chứa danh sách SV, $dot là đợt)
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// ======= Tiêu đề chính =======
$sheet->mergeCells('A1:G1');
$sheet->setCellValue('A1', 'DANH SÁCH GV HƯỚNG DẪN THỰC TẬP TỐT NGHIỆP ' . ($dot['TenDot'] ?? ''));
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// ======= Header bảng =======
$headers = ['STT', 'Mã SV', 'Họ Tên', 'Ngày Sinh', 'Lớp', 'Họ Tên GVHD'];
$sheet->fromArray($headers, NULL, 'A3');
$sheet->getStyle('A3:G3')->getFont()->setBold(true);
$sheet->getStyle('A3:G3');
$sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(3, 3);


// ======= Nội dung dữ liệu =======
$rowIndex = 4;
$stt = 1;
foreach ($data as $row) {
    // Giả sử bạn đã tách "Họ" và "Tên" riêng từ $row['TenSV'] trước đó
    $sheet->setCellValue('A' . $rowIndex, $stt++);
    $sheet->setCellValueExplicit('B' . $rowIndex, $row['MSSV'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
    $sheet->setCellValue('C' . $rowIndex, $row['TenSV']);
    $sheet->setCellValue('D' . $rowIndex, date('d/m/Y', strtotime($row['NgaySinh'])));
    $sheet->setCellValue('E' . $rowIndex, $row['Lop']);
    $sheet->setCellValue('F' . $rowIndex, $row['TenGVHD']);
    $rowIndex++;
}

// ======= Kẻ khung viền bảng =======
$styleArray = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['argb' => '000000'],
        ],
    ],
];
$sheet->getStyle('A3:G' . ($rowIndex - 1))->applyFromArray($styleArray);

// ======= Căn chỉnh và tự động co dãn cột =======
foreach (range('A', 'G') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
    $sheet->getStyle($col . '3:' . $col . ($rowIndex - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
}
$sheet->getStyle('C4:C' . ($rowIndex - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT); // Tên SV
$sheet->getStyle('F4:F' . ($rowIndex - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT); // Tên GVHD

// Ẩn cột thừa nếu có (G trở đi)
foreach (range('G', 'Z') as $col) {
    $sheet->getColumnDimension($col)->setVisible(false);
}
// Căn chỉnh lề trang
$sheet->getPageMargins()->setTop(0.5)->setBottom(0.5)->setLeft(0.3)->setRight(0.3);
$sheet->getPageSetup()->setHorizontalCentered(true);
// ======= Xuất file Excel =======
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="DS_GVHD_TTTN_' . ($dot['TenDot'] ?? 'dot') . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
}

$stmt = $conn->prepare("SELECT COUNT(*) FROM sinhvien WHERE ID_Dot = :id");
$stmt->execute(['id' => $id]);
$tongSinhVien = $stmt->fetchColumn();

// Tổng số giáo viên trong đợt (dựa vào dot_giaovien)
$stmt = $conn->prepare("SELECT COUNT(*) FROM dot_giaovien WHERE ID_Dot = :id");
$stmt->execute(['id' => $id]);
$tongGVHD = $stmt->fetchColumn();

    // Lấy giáo viên thuộc đợt từ bảng dot_giaovien
    $stmt = $conn->prepare("
        SELECT GV.ID_TaiKhoan, GV.Ten, COUNT(SV.ID_TaiKhoan) AS SoLuong
        FROM dot_giaovien DG
        INNER JOIN giaovien GV ON DG.ID_GVHD = GV.ID_TaiKhoan
        LEFT JOIN sinhvien SV ON GV.ID_TaiKhoan = SV.ID_GVHD AND SV.ID_Dot = DG.ID_Dot
        WHERE DG.ID_Dot = :id
        GROUP BY GV.ID_TaiKhoan, GV.Ten
        ORDER BY SoLuong
    ");
$stmt->execute(['id' => $id]);
$dsGiaoVien = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Thống kê số SV hoàn thành và chưa hoàn thành
$stmt = $conn->prepare("SELECT 
    SUM(CASE WHEN T.TrangThai = 1 THEN 1 ELSE 0 END) AS DaHoanThanh,
    SUM(CASE WHEN T.TrangThai != 1 OR T.TrangThai IS NULL THEN 1 ELSE 0 END) AS ChuaHoanThanh
FROM sinhvien SV
LEFT JOIN tongket T ON SV.ID_TaiKhoan = T.IDSV
WHERE SV.ID_Dot = :id");
$stmt->execute(['id' => $id]);
$tkTrangThai = $stmt->fetch(PDO::FETCH_ASSOC);

// Thống kê xếp loại
$stmt = $conn->prepare("SELECT SV.XepLoai, COUNT(*) AS SoLuong
FROM sinhvien SV
WHERE SV.ID_Dot = :id AND SV.XepLoai IS NOT NULL AND SV.XepLoai <> ''
GROUP BY SV.XepLoai");
$stmt->execute(['id' => $id]);
$tkXepLoai = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (isset($_GET['export_excel']) && $_GET['export_excel'] == 2) {
    ob_clean();
    header_remove();

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle("ThongKeDot");

    ob_clean();
header_remove();

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle("ThongKeDot");
$row = 1;

// ===== TIÊU ĐỀ =====
$sheet->setCellValue("A$row", "THỐNG KÊ ĐỢT: " . $dot['TenDot']);
$sheet->mergeCells("A$row:B$row");
$sheet->getStyle("A$row")->getFont()->setBold(true)->setSize(14);
$sheet->getStyle("A$row")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Tiêu đề Xếp loại
$sheet->setCellValue("D$row", "THỐNG KÊ XẾP LOẠI SINH VIÊN");
$sheet->mergeCells("D$row:F$row");
$sheet->getStyle("D$row")->getFont()->setBold(true);
$sheet->getStyle("D$row")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$row++;

// ===== THÔNG TIN ĐỢT =====
$infoFields = [
    "Loại:" => $dot['Loai'],
    "Năm:" => $dot['Nam'],
    "Người quản lý:" => $dot['TenNguoiQuanLy'],
    "Người mở đợt:" => $dot['TenNguoiMoDot'],
    "Thời gian bắt đầu:" => $dot['ThoiGianBatDau'],
    "Thời gian kết thúc:" => $dot['ThoiGianKetThuc'],
    "Trạng thái:" => match($dot['TrangThai']) {
        1 => 'Đang chuẩn bị',
        2 => 'Đã bắt đầu',
        3 => 'Hoàn tất phân công',
        default => 'Đã kết thúc'
    },
    "Tổng sinh viên:" => $tongSinhVien,
    "Sinh viên đã hoàn thành:" => $tkTrangThai['DaHoanThanh'],
    "Sinh viên chưa hoàn thành:" => $tkTrangThai['ChuaHoanThanh'],
    "Tổng GVHD:" => $tongGVHD,
];
$infoStart = $row;

foreach ($infoFields as $label => $value) {
    $sheet->setCellValue("A$row", $label);
    $sheet->setCellValue("B$row", $value);
    $row++;
}
$infoEnd = $row - 1;
$sheet->getStyle("A$infoStart:B$infoEnd")->applyFromArray([
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
]);

// ===== XẾP LOẠI =====
$sheet->fromArray(['STT', 'Xếp loại', 'Số lượng'], NULL, "D" . ($infoStart));
$sheet->getStyle("D$infoStart:F$infoStart")->getFont()->setBold(true);
$sheet->getStyle("D$infoStart:F$infoStart")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$xlRow = $infoStart + 1;
$stt = 1;
foreach ($tkXepLoai as $xl) {
    $sheet->setCellValue("D$xlRow", $stt++);
    $sheet->setCellValue("E$xlRow", $xl['XepLoai']);
    $sheet->setCellValue("F$xlRow", $xl['SoLuong']);
    $xlRow++;
}
$sheet->getStyle("D$infoStart:F" . ($xlRow - 1))->applyFromArray([
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
]);

$row = max($row, $xlRow) + 2;

// ===== TIÊU ĐỀ GVHD =====
$sheet->setCellValue("A$row", "GVHD VÀ SỐ LƯỢNG SV");
$sheet->mergeCells("A$row:C$row");
$sheet->getStyle("A$row")->getFont()->setBold(true);
$sheet->getStyle("A$row")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$row++;

// ===== HEADER GVHD =====
$sheet->fromArray(['STT', 'Tên GVHD', 'Số SV Hướng Dẫn'], NULL, "A$row");
$sheet->getStyle("A$row:C$row")->getFont()->setBold(true);
$sheet->getStyle("A$row:C$row")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$gvStart = $row;
$row++;

$stt = 1;
foreach ($dsGiaoVien as $gv) {
    $sheet->setCellValue("A$row", $stt++);
    $sheet->setCellValue("B$row", $gv['Ten']);
    $sheet->setCellValue("C$row", $gv['SoLuong']);
    $row++;
}
$sheet->getStyle("A$gvStart:C" . ($row - 1))->applyFromArray([
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
]);

// ===== Auto size columns =====
foreach (range('A', 'F') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// ===== Xuất file =====
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="ThongKe_Dot_' . $dot['TenDot'] . '.xlsx"');
header('Cache-Control: max-age=0');
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;

}

$successMessage = "";
if (isset($_SESSION['success'])) {
    $successMessage = $_SESSION['success'];
    unset($_SESSION['success']);
}

$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$trangThaiDot = $dot['TrangThai'];

// AJAX: Trả về danh sách sinh viên của 1 giáo viên
if (isset($_GET['ajax']) && $_GET['ajax'] == 1 && isset($_GET['id_gv'])) {
    $idGV = (int) $_GET['id_gv'];
    $idDot = (int) ($_GET['id'] ?? 0);
    $stmt = $conn->prepare("
        SELECT ID_TaiKhoan, MSSV, Ten, Lop, ID_GVHD
        FROM sinhvien
        WHERE ID_GVHD = :id_gv AND ID_Dot = :id_dot
        ORDER BY Lop, Ten
    ");
    $stmt->execute(['id_gv' => $idGV, 'id_dot' => $idDot]);
    $ds = $stmt->fetchAll(PDO::FETCH_ASSOC);
    header('Content-Type: application/json');
    echo json_encode(['ds' => $ds]);
    exit;
}
// Số sinh viên đã có giáo viên hướng dẫn
$stmt = $conn->prepare("SELECT COUNT(*) FROM sinhvien WHERE ID_Dot = :id AND ID_GVHD IS NULL OR ID_GVHD = ''");
$stmt->execute(['id' => $id]);
$soSVDaCoGVHD = $stmt->fetchColumn();

// Số giáo viên trong đợt chưa có sinh viên hướng dẫn
$stmt = $conn->prepare("
    SELECT COUNT(*) FROM dot_giaovien DG
    LEFT JOIN sinhvien SV ON DG.ID_GVHD = SV.ID_GVHD AND SV.ID_Dot = DG.ID_Dot
    WHERE DG.ID_Dot = :id AND SV.ID_TaiKhoan IS NULL
");
$stmt->execute(['id' => $id]);
$soGVChuaCoSV = $stmt->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'auto_phancong') {
    $id = $_POST['id_dot'] ?? null;

    if (!$id) {
        echo "Không tìm thấy ID đợt!";
        exit;
    }
    // Lấy toàn bộ giáo viên trong đợt
    $stmt = $conn->prepare("SELECT ID_GVHD FROM dot_giaovien WHERE ID_Dot = :id ORDER BY ID_GVHD");
    $stmt->execute(['id' => $id]);
    $giaoViens = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Lấy sinh viên chưa có GVHD
    $stmt = $conn->prepare("SELECT ID_TaiKhoan FROM SinhVien WHERE ID_Dot = :id AND (ID_GVHD IS NULL OR ID_GVHD = '')");
    $stmt->execute(['id' => $id]);
    $sinhViens = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (count($giaoViens) == 0 || count($sinhViens) == 0) {
        echo "Không có giáo viên hoặc sinh viên chưa được phân công!";
        exit;
    }

    // Phân công đều
    $gvCount = count($giaoViens);
    foreach ($sinhViens as $i => $svId) {
        $gvId = $giaoViens[$i % $gvCount];
        $update = $conn->prepare("UPDATE SinhVien SET ID_GVHD = :gvId WHERE ID_TaiKhoan = :svId");
        $update->execute(['gvId' => $gvId, 'svId' => $svId]);
    }
    echo "OK";
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'hoan_tat') {
    $id = $_POST['id_dot'] ?? null;

    if (!$id) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'ERROR', 'message' => 'Không tìm thấy ID đợt!']);
        exit;
    }

    // Kiểm tra còn sinh viên chưa có GVHD
    $stmt = $conn->prepare("SELECT COUNT(*) FROM sinhvien WHERE ID_Dot = :id AND (ID_GVHD IS NULL OR ID_GVHD = '')");
    $stmt->execute(['id' => $id]);
    $soSVDaCoGVHD = $stmt->fetchColumn();

    // Kiểm tra còn giáo viên chưa có sinh viên
    $stmt = $conn->prepare("
        SELECT COUNT(*) FROM dot_giaovien DG
        LEFT JOIN sinhvien SV ON DG.ID_GVHD = SV.ID_GVHD AND SV.ID_Dot = DG.ID_Dot
        WHERE DG.ID_Dot = :id AND SV.ID_TaiKhoan IS NULL
    ");
    $stmt->execute(['id' => $id]);
    $soGVChuaCoSV = $stmt->fetchColumn();

    if ($soSVDaCoGVHD > 0) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'ERROR', 'message' => 'Vẫn còn sinh viên chưa được phân công giáo viên hướng dẫn!']);
        exit;
    }
    if ($soGVChuaCoSV > 0) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'ERROR', 'message' => 'Vẫn còn giáo viên chưa được phân công sinh viên nào!']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE DOTTHUCTAP SET TrangThai = 3 WHERE ID = :id");
    $stmt->execute(['id' => $id]);

    require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/vendor/autoload.php';


    // Lấy danh sách giáo viên và email
    $stmt = $conn->prepare("
        SELECT GV.Ten, TK.TaiKhoan AS Email
        FROM dot_giaovien DG
        JOIN giaovien GV ON DG.ID_GVHD = GV.ID_TaiKhoan
        JOIN taikhoan TK ON GV.ID_TaiKhoan = TK.ID_TaiKhoan
        WHERE DG.ID_Dot = :id
    ");
    $stmt->execute(['id' => $id]);
    $giaoVienList = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($giaoVienList as $gv) {
        $mail = new PHPMailer(true);
    try {
        // Cấu hình SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'thanhkiet.101023@gmail.com';
        $mail->Password = 'qxla zadq xqoq zsat'; // Mật khẩu ứng dụng
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;
        $mail->CharSet = 'UTF-8';
        // Người gửi và người nhận
        $mail->setFrom('thanhkiet.101023@gmail.com', name: 'Khoa Công Nghệ Thông Tin');
        $mail->addAddress($gv['Email'], $gv['Ten']);

        // Nội dung
        $mail->isHTML(true);
        $mail->Subject = 'Thông báo hoàn tất phân công hướng dẫn thực tập';
        $mail->Body = '
        <html>
        <body style="font-family: Arial, sans-serif; font-size:14px; line-height:1.6;">
            <p style="color:#000000;">Chào thầy/cô <strong style="color:#000000;">' . htmlspecialchars($gv['Ten']) . '</strong>,</p>
            <p style="color:#000000;">Đợt thực tập <strong style="color:#000000;">' . htmlspecialchars($dot['TenDot']) . '</strong> đã được phân công hoàn tất.</p>
            <p style="color:#000000;">Thầy/cô có thể đăng nhập hệ thống để xem danh sách sinh viên mình hướng dẫn.</p>
            <br>
            <p style="color:#000000;">Trân trọng,<br><strong style="color:#000000;">Khoa Công Nghệ Thông Tin</strong></p>
        </body>
        </html>';

        $mail->send();
        file_put_contents('mail_debug.log', "Đã gửi tới: " . $gv['Email'] . PHP_EOL, FILE_APPEND);
    } catch (Exception $e) {
        error_log("Gửi mail thất bại tới {$gv['Email']}: {$mail->ErrorInfo}");
    }
    }

    echo json_encode(['status' => 'OK', 'message' => 'Đã hoàn tất phân công và gửi thông báo đến giáo viên.']);
    header('Content-Type: application/json');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'them_gv_dot') {
    $idDot = (int) $_POST['id_dot'];
    $gvCoSan = $_POST['gvCoSan'] ?? [];
    $tenGVmoi = trim($_POST['tenGVmoi'] ?? '');
    $taiKhoanGVmoi = trim($_POST['taiKhoanGVmoi'] ?? '');
    $matKhauGVmoi = $_POST['matKhauGVmoi'] ?? '';
    $added = 0;

    try {
        $conn->beginTransaction();

        // Thêm các giáo viên đã có
        foreach ($gvCoSan as $idGV) {
            $idGV = (int) $idGV;
            $stmt = $conn->prepare("SELECT 1 FROM giaovien WHERE ID_TaiKhoan = ?");
            $stmt->execute([$idGV]);
            if (!$stmt->fetch()) {
                throw new PDOException("Giáo viên ID $idGV không tồn tại");
            }

            $stmt = $conn->prepare("INSERT IGNORE INTO dot_giaovien (ID_Dot, ID_GVHD) VALUES (?, ?)");
            $stmt->execute([$idDot, $idGV]);
            $added += $stmt->rowCount();
        }

        // Thêm giáo viên mới nếu có
        if ($tenGVmoi && $taiKhoanGVmoi && $matKhauGVmoi) {
            // Kiểm tra tài khoản tồn tại
            $stmt = $conn->prepare("SELECT COUNT(*) FROM TaiKhoan WHERE TaiKhoan = ?");
            $stmt->execute([$taiKhoanGVmoi]);
            if ($stmt->fetchColumn() > 0) {
                throw new PDOException("Tài khoản giáo viên đã tồn tại");
            }

            // Thêm tài khoản
            $matKhauMaHoa = password_hash($matKhauGVmoi, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("INSERT INTO TaiKhoan (TaiKhoan, MatKhau, VaiTro, TrangThai) VALUES (?, ?, 'Giáo viên', 1)");
            $stmt->execute([$taiKhoanGVmoi, $matKhauMaHoa]);
            $idGVmoi = $conn->lastInsertId();

            // Thêm giáo viên
            $stmt = $conn->prepare("INSERT INTO GiaoVien (ID_TaiKhoan, Ten, TrangThai) VALUES (?, ?, 1)");
            $stmt->execute([$idGVmoi, $tenGVmoi]);

            // Thêm vào đợt
            $stmt = $conn->prepare("INSERT INTO dot_giaovien (ID_Dot, ID_GVHD) VALUES (?, ?)");
            $stmt->execute([$idDot, $idGVmoi]);
            $added++;
        }

        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => $added > 0 ? "Đã thêm $added giáo viên vào đợt" : "Không có giáo viên nào được thêm"
        ]);
    } catch (PDOException $e) {
        $conn->rollBack();
        error_log('Lỗi thêm GV: ' . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi hệ thống: ' . $e->getMessage()
        ]);
    }
    exit;
}

if (isset($_GET['ajax']) && $_GET['ajax'] === 'info' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    // Tổng GVHD
    $stmt = $conn->prepare("SELECT COUNT(*) FROM dot_giaovien WHERE ID_Dot = :id");
    $stmt->execute(['id' => $id]);
    $tongGVHD = $stmt->fetchColumn();

    // Sinh viên chưa có GVHD
    $stmt = $conn->prepare("SELECT COUNT(*) FROM sinhvien WHERE ID_Dot = :id AND (ID_GVHD IS NULL OR ID_GVHD = '')");
    $stmt->execute(['id' => $id]);
    $soSVDaCoGVHD = $stmt->fetchColumn();

    // GVHD chưa được phân công
    $stmt = $conn->prepare("
        SELECT COUNT(*) FROM dot_giaovien DG
        LEFT JOIN sinhvien SV ON DG.ID_GVHD = SV.ID_GVHD AND SV.ID_Dot = DG.ID_Dot
        WHERE DG.ID_Dot = :id AND SV.ID_TaiKhoan IS NULL
    ");
    $stmt->execute(['id' => $id]);
    $soGVChuaCoSV = $stmt->fetchColumn();

    header('Content-Type: application/json');
    echo json_encode([
        'tongGVHD' => $tongGVHD,
        'soSVDaCoGVHD' => $soSVDaCoGVHD,
        'soGVChuaCoSV' => $soGVChuaCoSV
    ]);
    exit;
}
// AJAX: Trả về nội dung tab giáo viên
if (isset($_GET['ajax_tab']) && $_GET['ajax_tab'] == 'gv') {
    ?>
    <div class="panel panel-default">
    <div class="panel-heading">
        <div class="row" style="margin-bottom: 15px;">
            <div class="col-md-4">
                Danh sách giáo viên hướng dẫn
            </div>
            <div class="col-md-8" style="text-align: right;">
                <button onclick="window.location.href='admin/pages/chitietdot?id=<?= $id ?>&export_excel=1'" class="btn btn-success">
                    <i class="fa fa-file-excel-o"></i> Xuất danh sách phân công
                </button>
                <button onclick="window.location.href='admin/pages/chitietdot?id=<?= $id ?>&export_excel=2'" class="btn btn-success">
                    <i class="fa fa-file-excel-o"></i> Xuất thống kê
                </button>
            </div>
    </div>
</div>
    <div class="panel-body">
        <div class="table-responsive">
            <table id="table-gv" class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Họ tên giáo viên</th>
                        <th style="text-align: center;">Số sinh viên được phân công</th>
                        <th style="text-align: right;">Xem danh sách sinh viên</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dsGiaoVien as $idx => $gv): ?>
                        <tr>
                            <td><?= $idx + 1 ?></td>
                            <td data-id="<?= $gv['ID_TaiKhoan'] ?>"><?= htmlspecialchars($gv['Ten']) ?></td>
                            <td style="text-align: center;"><?= $gv['SoLuong'] ?></td>
                            <td style="text-align: right;">
                                <button type="button" class="btn btn-info btn-xs"
                                    onclick="xemSinhVienGV(<?= $gv['ID_TaiKhoan'] ?>, '<?= htmlspecialchars(addslashes($gv['Ten'])) ?>')">
                                    Xem sinh viên
                                </button>
                                <button type="button" class="btn btn-danger btn-xs btn-xoa-gv-dot"
                                    data-id="<?= $gv['ID_TaiKhoan'] ?>" <?= $gv['SoLuong'] > 0 ? 'disabled title="Không thể xóa: Giáo viên đã có sinh viên!"' : 'title="Xóa giáo viên khỏi đợt"' ?>>
                                    <i class="fa fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php
exit;
}

// AJAX: Trả về nội dung tab sinh viên
if (isset($_GET['ajax_tab']) && $_GET['ajax_tab'] == 'sv') {
    // Lấy toàn bộ sinh viên của đợt
    $stmt = $conn->prepare("SELECT ID_TaiKhoan, MSSV, Ten, Lop, ID_GVHD FROM sinhvien WHERE ID_Dot = :id ORDER BY ID_GVHD");
    $stmt->execute(['id' => $id]);
    $dsSinhVienAll = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Lấy danh sách giáo viên trong đợt từ bảng trung gian dot_giaovien
    $stmt = $conn->prepare("
        SELECT GV.ID_TaiKhoan, GV.Ten
        FROM dot_giaovien DG
        INNER JOIN giaovien GV ON DG.ID_GVHD = GV.ID_TaiKhoan
        WHERE DG.ID_Dot = :id
        ORDER BY GV.Ten
    ");
$stmt->execute(['id' => $id]);
$giaoVienTrongDot = $stmt->fetchAll(PDO::FETCH_ASSOC);


?>
<div class="panel panel-default">
    <div class="panel-heading">
        <b>Danh sách sinh viên</b>
        <div class="pull-right" style="min-width:220px;">
            <select id="filterGVHD" class="form-control input-sm">
                <option value="all">-- Tất cả --</option>
                <option value="">-- Chưa có giáo viên hướng dẫn --</option>
            </select>
        </div>
    </div>
    <div class="panel-body">
        <table class="table table-striped" id="table-dssv">
            <thead>
                <tr>
                    <th>STT</th>
                    <th>MSSV</th>
                    <th>Họ tên</th>
                    <th>Lớp</th>
                    <th>Giáo viên hướng dẫn</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($dsSinhVienAll as $idx => $sv): ?>
                    <tr>
                        <td><?= $idx + 1 ?></td>
                        <td><?= htmlspecialchars($sv['MSSV']) ?></td>
                        <td><?= htmlspecialchars($sv['Ten']) ?></td>
                        <td><?= htmlspecialchars($sv['Lop']) ?></td>
                        <td data-id="<?= $sv['ID_GVHD'] ?>">
                            <select class="form-control select-gvhd" data-mssv="<?= $sv['ID_TaiKhoan'] ?>"
                                <?= $dot['TrangThai'] != 1 ? 'disabled' : '' ?>>
                                <option value="">-- Chưa có --</option>
                                <?php foreach ($giaoVienTrongDot as $gv): ?>
                                    <option value="<?= $gv['ID_TaiKhoan'] ?>" <?= ($sv['ID_GVHD'] == $gv['ID_TaiKhoan']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($gv['Ten']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php exit;
}

// Xử lý chuyển giáo viên hướng dẫn (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'chuyen_gv') {
    $id_sv = (int) $_POST['mssv'];
    $gvhdMoi = $_POST['gvhdMoi'];
    $idDot = (int) $_POST['id_dot'];

    // Lấy ID_GVHD cũ
    $stmt = $conn->prepare("SELECT ID_GVHD FROM SinhVien WHERE ID_TaiKhoan = :id_sv AND ID_Dot = :id_dot");
    $stmt->execute(['id_sv' => $id_sv, 'id_dot' => $idDot]);
    $gvhdCu = $stmt->fetchColumn();

    // Cập nhật
    if ($gvhdMoi === "" || $gvhdMoi === null) {
        $stmt = $conn->prepare("UPDATE SinhVien SET ID_GVHD = NULL WHERE ID_TaiKhoan = :id_sv AND ID_Dot = :id_dot");
        $stmt->execute(['id_sv' => $id_sv, 'id_dot' => $idDot]);
    } else {
        $gvhdMoi = (int) $gvhdMoi;
        $stmt = $conn->prepare("UPDATE SinhVien SET ID_GVHD = :gvhdMoi WHERE ID_TaiKhoan = :id_sv AND ID_Dot = :id_dot");
        $stmt->execute(['gvhdMoi' => $gvhdMoi, 'id_sv' => $id_sv, 'id_dot' => $idDot]);
    }

    // Đếm lại số lượng sinh viên của GV cũ và mới
    $stmt = $conn->prepare("SELECT COUNT(*) FROM SinhVien WHERE ID_GVHD = :id_gv AND ID_Dot = :id_dot");
    $stmt->execute(['id_gv' => $gvhdCu, 'id_dot' => $idDot]);
    $soLuongCu = $stmt->fetchColumn();

    $soLuongMoi = null;
    if ($gvhdMoi) {
        $stmt->execute(['id_gv' => $gvhdMoi, 'id_dot' => $idDot]);
        $soLuongMoi = $stmt->fetchColumn();
    }

    echo json_encode([
        'success' => true,
        'gvhdCu' => $gvhdCu,
        'soLuongCu' => $soLuongCu,
        'gvhdMoi' => $gvhdMoi,
        'soLuongMoi' => $soLuongMoi
    ]);
    exit;
}

$stmt = $conn->prepare("
        SELECT GV.ID_TaiKhoan, GV.Ten
        FROM dot_giaovien DG
        INNER JOIN giaovien GV ON DG.ID_GVHD = GV.ID_TaiKhoan
        WHERE DG.ID_Dot = :id
        ORDER BY GV.Ten
    ");
$stmt->execute(['id' => $id]);
$allGiaoVien = $stmt->fetchAll(PDO::FETCH_ASSOC);


// xóa giáo viên khỏi đợt
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'xoa_gv_dot') {
    $idDot = (int) $_POST['id_dot'];
    $idGV = (int) $_POST['id_gv'];
    // Kiểm tra giáo viên này đã có sinh viên chưa
    $stmt = $conn->prepare("SELECT COUNT(*) FROM sinhvien WHERE ID_Dot = :id_dot AND ID_GVHD = :id_gv");
    $stmt->execute(['id_dot' => $idDot, 'id_gv' => $idGV]);
    $soLuong = $stmt->fetchColumn();
    if ($soLuong > 0) {
        echo json_encode(['success' => false, 'message' => 'Không thể xóa: Giáo viên đã có sinh viên!']);
        exit;
    }
    // Xóa khỏi dot_giaovien
    $stmt = $conn->prepare("DELETE FROM dot_giaovien WHERE ID_Dot = :id_dot AND ID_GVHD = :id_gv");
    $stmt->execute(['id_dot' => $idDot, 'id_gv' => $idGV]);
    echo json_encode(['success' => true]);
    exit;
}
?>
    <!DOCTYPE html>
    <html lang="vi">

    <head>
        <meta charset="UTF-8">
        <title><?= htmlspecialchars($dot['TenDot']) ?></title>
        <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php"; ?>
        <style>
            @media (max-width: 900px) {
                .dot-info-row {
                    flex-direction: column;
                    gap: 0;
                }

                .dot-header {
                    padding: 18px 8px 8px 8px;
                }
            }

            td.success {
                background: rgb(189, 255, 231) !important;
                transition: background 0.5s;
            }
        </style>
    </head>

    <body>
        <div id="wrapper">
            <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/admin/template/slidebar.php"; ?>
            <div id="page-wrapper">
                <div class="container-fluid">
                    <div class="dot-header panel panel-default" style="padding:24px 18px 10px 18px;">
                        <h1 class="panel-title" style="font-size:2rem; font-weight:bold; margin-bottom:18px;">
                            <?= htmlspecialchars($dot['TenDot']) ?>
                        </h1>
                        <div class="row" style="margin-bottom:10px;">
                            <div class="col-sm-4 col-xs-12">
                                <p><b>Loại:</b> <?= htmlspecialchars($dot['Loai']) ?></p>
                                <p><b>Năm:</b> <?= htmlspecialchars($dot['Nam']) ?></p>
                                <p><b>Người quản lý:</b> <?= htmlspecialchars($dot['TenNguoiQuanLy']) ?></p>
                                <p><b>Người mở đợt:</b> <?= htmlspecialchars($dot['TenNguoiMoDot']) ?></p>

                            </div>
                            <div class="col-sm-4 col-xs-12">
                                <p><b>Thời gian bắt đầu:</b> <?= htmlspecialchars($dot['ThoiGianBatDau']) ?></p>
                                <p><b>Thời gian kết thúc:</b> <?= htmlspecialchars($dot['ThoiGianKetThuc']) ?></p>
                                <p><b>Trạng thái:</b>
                                    <?php
                                    if ($dot['TrangThai'] == 1)
                                        echo 'Đang chuẩn bị';
                                    elseif ($dot['TrangThai'] == 2)
                                        echo 'Đã bắt đầu';
                                    elseif ($dot['TrangThai'] == 3)
                                        echo 'Hoàn tất phân công';
                                    else
                                        echo 'Đã kết thúc';
                                    ?>
                                </p>
                            </div>
                            <div class="col-sm-4 col-xs-12">
                                <p><b>Tổng sinh viên:</b> <?= $tongSinhVien ?></p>
                                <div id="infoBox">
                                    <p><b>Tổng GVHD:</b> <span id="infoTongGVHD"><?= $tongGVHD ?></span></p>
                                    <p><b>Sinh viên chưa có GVHD:</b> <span
                                            id="infoSVDaCoGVHD"><?= $soSVDaCoGVHD ?></span>
                                    </p>
                                    <p><b>GVHD chưa được phân công:</b> <span
                                            id="infoGVChuaCoSV"><?= $soGVChuaCoSV ?></span></p>
                                </div>
                            </div>
                        </div>
                        <div class="dot-actions" style="margin-top:12px;">
                            <button onclick="window.location='admin/pages/importexcel?id=<?= $id ?>';"
                                class="btn btn-primary btn-md" title="Import danh sách sinh viên"<?= $dot['TrangThai'] != 1 ? 'disabled' : '' ?>>Import sinh
                                viên</button>
                            <button type="button" class="btn btn-primary btn-md" id="btnMoModalThemGV" title="Thêm giáo viên cho đợt thực tập"
                                <?= $dot['TrangThai'] != 1 ? 'disabled' : '' ?>>
                                Thêm giáo viên
                            </button>
                            <button type="button" id="btnAutoPhanCong" class="btn btn-success btn-md" title="Phân công đều các sinh viên còn lại"
                                <?= $dot['TrangThai'] != 1 ? 'disabled' : '' ?>>
                                Phân công tự động
                            </button>
                            <button type="button" class="btn btn-success" id="btnHoanTat" <?= ($tongSinhVien < 1 || $tongGVHD < 1) || $dot['TrangThai'] != 1 ? 'disabled' : '' ?>
                                title="Hoàn tất phân công, cho phép đăng ký phiếu giới thiệu, gửi mail cho các giáo viên">
                                Hoàn tất phân công
                            </button>
                            <button onclick="window.location='admin/pages/chinhsuadot?id=<?= $id ?>';"
                                class="btn btn-warning btn-md" <?= $dot['TrangThai'] == 0 ? 'disabled' : '' ?>>Chỉnh
                                sửa</button>
                            <button
                                onclick="if(confirm('Bạn có chắc muốn xóa đợt này?')) window.location='admin/pages/xoadot?id=<?= $id ?>';"
                                class="btn btn-danger btn-md" <?= ($tongSinhVien > 0 || $tongGVHD > 0)||$dot['TrangThai'] !=1 ? 'disabled title="Không thể xóa: Đợt đã có sinh viên hoặc giáo viên."' : '' ?>>Xóa đợt</button>

                        </div>
                    </div>
                    <?php if (!empty($successMessage)): ?>
                        <div id="successAlert" class="alert alert-success">
                            <?= $successMessage ?>
                        </div>
                    <?php endif; ?>
                    <ul class="nav nav-tabs" id="tabDotThucTap">
                        <li class="active"><a href="#tab-gv" data-toggle="tab" data-tab="gv">Giáo viên hướng dẫn</a>
                        </li>
                        <li><a href="#tab-sv" data-toggle="tab" data-tab="sv">Sinh viên</a></li>
                    </ul>
                    <div class="tab-content"
                        style="background:#fff; border:1px solid #ddd; border-top:0; padding:18px;">
                        <div class="tab-pane fade in active" id="tab-gv">
                            <div id="tabGVContent">
                                <!-- Nội dung giáo viên sẽ được load AJAX -->
                            </div>
                        </div>
                        <div class="tab-pane fade" id="tab-sv">
                            <div id="tabSVContent">
                                <!-- Nội dung sinh viên sẽ được load AJAX -->
                            </div>
                        </div>
                    </div>
                </div>
                </div>
                </div>
                <?php require $_SERVER['DOCUMENT_ROOT'] . "/datn/template/footer.php" ?>
                <script>
                    var allGiaoVien = <?= json_encode($allGiaoVien) ?>;
                    function xemSinhVienGV(idGV, tenGV) {
                        $.get(window.location.pathname, {
                            ajax: 1,
                            id: <?= json_encode($id) ?>,
                            id_gv: idGV
                        }, function (res) {
                            let html = `<h4>Giáo viên: ${tenGV}</h4>`;
                            if (res.ds && res.ds.length > 0) {
                                html += `<table class="table table-striped"><thead>
                            <tr>
                                <th>STT</th>
                                <th>MSSV</th>
                                <th>Họ tên</th>
                                <th>Lớp</th>
                                <th>Chuyển GVHD</th>
                            </tr></thead><tbody>`;
                                res.ds.forEach(function (sv, idx) {
                                    let select = `<select class="form-control select-gvhd-modal" data-mssv="${sv.ID_TaiKhoan}" <?= $dot['TrangThai'] != 1 ? 'disabled' : '' ?>>`;
                                    select += `<option value="">-- Phân công sau --</option>`;
                                    allGiaoVien.forEach(function (gv) {
                                        select += `<option value="${gv.ID_TaiKhoan}" ${sv.ID_GVHD == gv.ID_TaiKhoan ? 'selected' : ''}>${gv.Ten}</option>`;
                                    });
                                    select += `</select>`;
                                    html += `<tr>
                                <td>${idx + 1}</td>
                                <td>${sv.MSSV}</td>
                                <td>${sv.Ten}</td>
                                <td>${sv.Lop}</td>
                                <td>${select}</td>
                            </tr>`;
                                });
                                html += `</tbody></table>`;
                            } else {
                                html += `<div class="alert alert-warning">Chưa có sinh viên nào được phân công cho giáo viên này.</div>`;
                            }
                            $('#modalDanhSachSVBody').html(html);
                            $('#modalDanhSachSV').modal('show');
                        }, 'json');
                    }
                    // Xử lý chuyển giáo viên hướng dẫn (cả ngoài bảng và trong modal)
                    $(document).on('change', '.select-gvhd, .select-gvhd-modal', function () {
                        var id_sv = $(this).data('mssv');
                        var id_gv = $(this).val();
                        var id_dot = <?= json_encode($id) ?>;
                        var select = this;
                        if (id_gv === "") id_gv = null;
                        $.ajax({
                            url: window.location.href,
                            method: 'POST',
                            dataType: 'json',
                            data: {
                                action: 'chuyen_gv',
                                mssv: id_sv,
                                gvhdMoi: id_gv,
                                id_dot: id_dot
                            },
                            success: function (res) {
                                $(select).closest('td').addClass('success');
                                setTimeout(function () {
                                    $(select).closest('td').removeClass('success');
                                }, 1000);

                                // Cập nhật số lượng sinh viên hướng dẫn ở bảng table-gv
                                if (res.gvhdCu) {
                                    var rowCu = $('#table-gv tbody tr').filter(function () {
                                        return $(this).find('td').eq(1).data('id') == res.gvhdCu;
                                    });
                                    if (rowCu.length) {
                                        rowCu.find('td').eq(2).text(res.soLuongCu);
                                    }
                                }
                                if (res.gvhdMoi) {
                                    var rowMoi = $('#table-gv tbody tr').filter(function () {
                                        return $(this).find('td').eq(1).data('id') == res.gvhdMoi;
                                    });
                                    if (rowMoi.length) {
                                        rowMoi.find('td').eq(2).text(res.soLuongMoi);
                                    }
                                }
                                // Gọi cập nhật info ngay sau khi chuyển GVHD thành công
                                reloadInfoBox();
                                loadTabGV();
                            },
                            error: function (xhr) {
                                alert("Cập nhật thất bại: " + xhr.responseText);
                            }
                        });
                    });
                    $('#btnAutoPhanCong').on('click', function () {
                        Swal.fire({
                            title: 'Xác nhận phân công tự động?',
                            text: 'Xác nhận phân công đều các sinh viên còn lại cho các giáo viên?',
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonText: 'Xác nhận',
                            cancelButtonText: 'Huỷ',
                            confirmButtonColor: '#3085d6',
                            cancelButtonColor: '#d33'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                $.post(window.location.pathname, {
                                    action: 'auto_phancong',
                                    id_dot: <?= json_encode($id) ?>
                                }, function (res) {
                                    console.log('Kết quả AJAX:', res); // Thêm dòng này để debug
                                    if (res.trim() === 'OK') {
                                        Swal.fire('Thành công!', 'Phân công tự động thành công!', 'success').then(() => {
                                            reloadInfoBox();
                                            location.reload();
                                        });
                                    } else {
                                        Swal.fire('Lỗi', res, 'error');
                                    }
                                });
                            }
                        });
                    });
                    $('#btnHoanTat').on('click', function () {
                    Swal.fire({
                        title: 'Xác nhận hoàn tất?',
                        text: 'Sau khi hoàn tất, bạn sẽ không thể phân công lại!',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Xác nhận',
                        cancelButtonText: 'Huỷ'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Hiển thị loading
                            Swal.fire({
                                title: 'Đang gửi mail cho các giáo viên...',
                                html: 'Vui lòng chờ trong giây lát...',
                                allowOutsideClick: false,
                                didOpen: () => {
                                    Swal.showLoading();

                                    // Gửi request sau khi loading đã hiển thị
                                    $.post(window.location.pathname, {
                                        action: 'hoan_tat',
                                        id_dot: <?= json_encode($id) ?>
                                    }, function (res) {
                                        Swal.close(); // Tắt loading khi có phản hồi

                                        if (res && res.status === 'OK') {
                                            Swal.fire('Thành công!', 'Đã hoàn tất phân công!', 'success').then(() => {
                                                location.reload();
                                            });
                                        } else {
                                            Swal.fire('Lỗi', res && res.message ? res.message : 'Có lỗi xảy ra!', 'error');
                                        }
                                    }, 'json');
                                }
                            });
                        }
                    });
                });


                    // AJAX load tab
                    function loadTabGV() {
                        $('#tabGVContent').html('<div class="text-center"><i class="fa fa-spinner fa-spin"></i> Đang tải...</div>');
                        $.get(window.location.pathname, { ajax_tab: 'gv', id: <?= json_encode($id) ?> }, function (html) {
                            $('#tabGVContent').html(html);
                            $('#table-gv').DataTable({
                                pageLength: 10,
                                language: {
                                    url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/vi.json"
                                }
                            });
                        });
                    }
                    function loadTabSV() {
                        $('#tabSVContent').html('<div class="text-center"><i class="fa fa-spinner fa-spin"></i> Đang tải...</div>');
                        $.get(window.location.pathname, { ajax_tab: 'sv', id: <?= json_encode($id) ?> }, function (html) {
                            $('#tabSVContent').html(html);
                            var table = $('#table-dssv').DataTable({
                                pageLength: 15,
                                language: {
                                    url: "/datn/assets/datatables/vi.json"
                                }
                            });

                            // Custom filter theo ID giáo viên hướng dẫn
                            $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
                                var selectedId = $('#filterGVHD').val();
                                if (settings.nTable.id !== 'table-dssv') return true;
                                if (selectedId === 'all') return true; // Hiển thị tất cả
                                var td = table.row(dataIndex).node();
                                var gvId = $($(td).find('td[data-id]')).data('id');
                                if (!selectedId) {
                                    // Lọc sinh viên chưa có GVHD
                                    return !gvId;
                                }
                                return gvId == selectedId;
                            });

                            $('#filterGVHD').on('change', function () {
                                table.draw();
                            });

                            // Mặc định chọn "Tất cả"
                            $('#filterGVHD').val('all');
                            table.draw();
                        });
                    }

                    // Khi chuyển tab
                    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                        var tab = $(e.target).data('tab');
                        if (tab === 'gv') loadTabGV();
                        if (tab === 'sv') loadTabSV();
                    });

                    // Tải tab đầu tiên khi vào trang
                    $(function () { loadTabGV(); });
                    $(document).ready(function () {

                        $('#btnMoModalThemGV').on('click', function () {
                            // Reset form mỗi lần mở
                            $('#formThemGV')[0].reset();
                            $('#modalThemGV').modal('show');
                        });
                        $('#formThemGV').on('submit', function (e) {
                            e.preventDefault();

                            var idDot = <?= json_encode($id) ?>;
                            var gvCoSan = $('#chonGVCoSan').val() || [];
                            var tenGVmoi = $('#tenGVmoi').val().trim();
                            var taiKhoanGVmoi = $('#taiKhoanGVmoi').val().trim();
                            var matKhauGVmoi = $('#matKhauGVmoi').val();

                            if (gvCoSan.length === 0 && (!tenGVmoi || !taiKhoanGVmoi || !matKhauGVmoi)) {
                                Swal.fire('Vui lòng chọn giáo viên hoặc nhập thông tin giáo viên mới!', '', 'warning');
                                return;
                            }

                            // Kiểm tra tài khoản phải là email nếu nhập giáo viên mới
                            if (tenGVmoi && taiKhoanGVmoi && matKhauGVmoi) {
                                var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                                if (!emailPattern.test(taiKhoanGVmoi)) {
                                    Swal.fire('Tài khoản phải là một địa chỉ email hợp lệ!', '', 'warning');
                                    return;
                                }
                            }
                            $.ajax({
                                url: window.location.pathname,
                                method: 'POST',
                                dataType: 'json',
                                data: {
                                    action: 'them_gv_dot',
                                    id_dot: idDot,
                                    gvCoSan: gvCoSan,
                                    tenGVmoi: tenGVmoi,
                                    taiKhoanGVmoi: taiKhoanGVmoi,
                                    matKhauGVmoi: matKhauGVmoi
                                },
                                success: function (res) {
                                    if (res.success) {
                                        Swal.fire('Thành công!', res.message, 'success').then(() => {
                                            reloadInfoBox();
                                            location.reload();
                                        });
                                    } else {
                                        Swal.fire('Lỗi', res.message, 'error');
                                    }
                                },
                                error: function () {
                                    Swal.fire('Lỗi', 'Không thể thêm giáo viên.', 'error');
                                }
                            });
                        });
                        $('#btnShowFormGVmoi').on('click', function () {
                            $('#formGVmoiBox').slideDown(200);
                            $('#chonGVCoSan').attr('size', 6); // thu nhỏ lại khi hiện form
                            $(this).hide();
                        });
                        $('#btnHideFormGVmoi').on('click', function () {
                            $('#formGVmoiBox').slideUp(200);
                            $('#chonGVCoSan').attr('size', 20); // kéo dài lại khi ẩn form
                            $('#btnShowFormGVmoi').show();
                        });
                        // Khi mở modal thì reset về trạng thái ẩn form
                        $('#modalThemGV').on('show.bs.modal', function () {
                            $('#formGVmoiBox').hide();
                            $('#btnShowFormGVmoi').show();
                            $('#chonGVCoSan').attr('size', 20);
                        });
                    });
                    function reloadInfoBox() {
                        $.get(window.location.pathname, { ajax: 'info', id: <?= json_encode($id) ?> }, function (res) {
                            $('#infoTongGVHD').text(res.tongGVHD);
                            $('#infoSVDaCoGVHD').text(res.soSVDaCoGVHD);
                            $('#infoGVChuaCoSV').text(res.soGVChuaCoSV);
                        }, 'json');
                    }
                    // Xử lý xóa giáo viên khỏi đợt
                    $(document).on('click', '.btn-xoa-gv-dot', function () {
                        var idGV = $(this).data('id');
                        var idDot = <?= json_encode($id) ?>;
                        var btn = this;
                        Swal.fire({
                            title: 'Xác nhận xóa?',
                            text: 'Bạn chắc chắn muốn xóa giáo viên này khỏi đợt? (Chỉ xóa nếu chưa có sinh viên được phân công)',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Xóa',
                            cancelButtonText: 'Hủy'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                $.post(window.location.pathname, {
                                    action: 'xoa_gv_dot',
                                    id_dot: idDot,
                                    id_gv: idGV
                                }, function (res) {
                                    if (res.success) {
                                        Swal.fire('Đã xóa!', '', 'success');
                                        loadTabGV();
                                        reloadInfoBox();
                                        location.reload();
                                    } else {
                                        Swal.fire('Lỗi', res.message, 'error');
                                    }
                                }, 'json');
                            }
                        });
                    });

                    // Alert tự ẩn
                    window.addEventListener('DOMContentLoaded', () => {
                        const alertBox = document.getElementById('successAlert');
                        if (alertBox) {
                            setTimeout(() => {
                                alertBox.style.transition = 'opacity 0.5s ease';
                                alertBox.style.opacity = '0';
                                setTimeout(() => alertBox.remove(), 500);
                            }, 2000);
                        }
                    });
                </script>
            </div>
            <!-- Modal xem sinh viên của giáo viên -->
            <div class="modal fade" id="modalDanhSachSV" tabindex="-1" role="dialog"
                aria-labelledby="modalDanhSachSVLabel">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title" id="modalDanhSachSVLabel">Danh sách sinh viên</h4>
                        </div>
                        <div class="modal-body" id="modalDanhSachSVBody">
                            <!-- Nội dung sẽ được JS đổ vào -->
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Đóng</button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Modal Thêm Giáo Viên vào Đợt -->
            <div class="modal fade" id="modalThemGV" tabindex="-1" role="dialog" aria-labelledby="modalThemGVLabel">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <form id="formThemGV">
                            <div class="modal-header">
                                <h4 class="modal-title" id="modalThemGVLabel">Thêm giáo viên vào đợt thực tập</h4>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="id_dot" value="<?= htmlspecialchars($id) ?>">
                                <div class="form-group">
                                    <label for="chonGVCoSan"><b>Chọn giáo viên cho đợt (giữ Ctrl hoặc Shift để chọn
                                            nhiều):</b></label>
                                    <select multiple class="form-control" id="chonGVCoSan" name="gvCoSan[]" size="20"
                                        style="min: height 250;;">
                                        <?php
                                        // Lấy danh sách giáo viên chưa có trong đợt này
                                        $stmt = $conn->prepare("
                                        SELECT GV.ID_TaiKhoan, GV.Ten
                                        FROM GiaoVien GV
                                        WHERE GV.TrangThai = 1
                                        AND GV.ID_TaiKhoan NOT IN (
                                            SELECT ID_GVHD FROM dot_giaovien WHERE ID_Dot = :id_dot
                                        )
                                        ORDER BY GV.Ten
                                    ");
                                        $stmt->execute(['id_dot' => $id]);
                                        $giaoVienChuaCoTrongDot = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                        if (count($giaoVienChuaCoTrongDot) === 0): ?>
                                            <option disabled>Đã hết giáo viên để thêm</option>
                                        <?php else:
                                            foreach ($giaoVienChuaCoTrongDot as $gv): ?>
                                                <option value="<?= $gv['ID_TaiKhoan'] ?>"><?= htmlspecialchars($gv['Ten']) ?>
                                                </option>
                                            <?php endforeach;
                                        endif; ?>
                                    </select>
                                </div>
                                <div class="text-center" style="margin: 10px 0;">
                                    <button type="button" class="btn btn-link" id="btnShowFormGVmoi">
                                        <i class="fa fa-plus"></i> Thêm giáo viên mới
                                    </button>
                                </div>
                                <div id="formGVmoiBox" style="display:none; margin-top:10px;">
                                    <hr>
                                    <div class="form-group">
                                        <label>Họ tên giáo viên mới</label>
                                        <input type="text" class="form-control" id="tenGVmoi" name="tenGVmoi"
                                            placeholder="Nhập tên giáo viên mới">
                                    </div>
                                    <div class="form-group">
                                        <label>Tài khoản (email)</label>
                                        <input type="text" class="form-control" id="taiKhoanGVmoi" name="taiKhoanGVmoi"
                                            placeholder="Nhập email">
                                    </div>
                                    <div class="form-group">
                                        <label>Mật khẩu</label>
                                        <input type="password" class="form-control" id="matKhauGVmoi"
                                            name="matKhauGVmoi" placeholder="Nhập mật khẩu">
                                    </div>
                                    <div class="text-center">
                                        <button type="button" class="btn btn-link" id="btnHideFormGVmoi">
                                            <i class="fa fa-chevron-up"></i> Ẩn nhập giáo viên mới
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-default" data-dismiss="modal">Đóng</button>
                                <button type="submit" class="btn btn-primary">Thêm vào đợt</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

    </body>

    </html>