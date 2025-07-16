<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';
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
if (!$id)
    die("Không tìm thấy ID đợt thực tập.");
// Lấy thông tin đợt thực tập
$stmt = $conn->prepare("
    SELECT d.ID, d.TenDot, d.BacDaoTao, d.Nam, d.NguoiMoDot, d.NguoiQuanLy, d.ThoiGianBatDau, d.ThoiGianKetThuc, d.TrangThai,
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
        ORDER BY SoLuong DESC
    ");
$stmt->execute(['id' => $id]);
$dsGiaoVien = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    $row = $rowIndex + 1;
    // ===== GVHD VÀ SỐ LƯỢNG SV =====
    $row++; // Dòng trống
    $sheet->setCellValue("A$row", "GVHD VÀ SỐ LƯỢNG SV");
    $sheet->mergeCells("A$row:C$row");
    $sheet->getStyle("A$row")->getFont()->setBold(true);
    $sheet->getStyle("A$row")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $row++;

    // ===== Header GVHD =====
    $sheet->fromArray(['STT', 'Tên GVHD', 'Số SV Hướng Dẫn'], NULL, "A$row");
    $sheet->getStyle("A$row:C$row")->getFont()->setBold(true);
    $sheet->getStyle("A$row:C$row")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $gvStart = $row;
    $row++;

    // ===== Dữ liệu GVHD =====
    $stt = 1;
    foreach ($dsGiaoVien as $gv) {
        $sheet->setCellValue("A$row", $stt++);
        $sheet->setCellValue("B$row", $gv['Ten']);
        $sheet->setCellValue("C$row", $gv['SoLuong']);
        $row++;
    }

    // ===== Kẻ bảng viền GVHD =====
    $sheet->getStyle("A$gvStart:C" . ($row - 1))->applyFromArray([
        'borders' => [
            'allBorders' => ['borderStyle' => Border::BORDER_THIN]
        ],
    ]);

    // ===== Auto size lại cột nếu cần =====
    foreach (range('A', 'F') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
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


// Thống kê số SV hoàn thành và chưa hoàn thành
$stmt = $conn->prepare("
    SELECT 
        SUM(CASE 
                WHEN dt.ID IS NOT NULL 
                     AND dt.Diem_BaoCao IS NOT NULL 
                     AND dt.Diem_ChuyenCan IS NOT NULL 
                     AND dt.Diem_ChuanNghe IS NOT NULL 
                     AND dt.Diem_ThucTe IS NOT NULL
                THEN 1 ELSE 0 
            END) AS DaHoanThanh,
        SUM(CASE 
                WHEN dt.ID IS NULL 
                     OR dt.Diem_BaoCao IS NULL 
                     OR dt.Diem_ChuyenCan IS NULL 
                     OR dt.Diem_ChuanNghe IS NULL 
                     OR dt.Diem_ThucTe IS NULL
                THEN 1 ELSE 0 
            END) AS ChuaHoanThanh
    FROM sinhvien sv
    LEFT JOIN diem_tongket dt ON sv.ID_TaiKhoan = dt.ID_SV AND dt.ID_Dot = sv.ID_Dot
    WHERE sv.ID_Dot = :id
");
$stmt->execute(['id' => $id]);
$tkTrangThai = $stmt->fetch(PDO::FETCH_ASSOC);


// ĐIểm
$stmt = $conn->prepare("
    SELECT 
        (Diem_BaoCao * 0.4 + Diem_ChuyenCan * 0.2 + Diem_ChuanNghe * 0.2 + Diem_ThucTe * 0.2) AS DiemTong
    FROM diem_tongket
    WHERE ID_Dot = :id
        AND Diem_BaoCao IS NOT NULL 
        AND Diem_ChuyenCan IS NOT NULL 
        AND Diem_ChuanNghe IS NOT NULL 
        AND Diem_ThucTe IS NOT NULL
");
$stmt->execute(['id' => $id]);
$diems = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Thống kê khung điểm
$thongKeKhungDiem = [
    '>=9' => 0,
    '7 - <9' => 0,
    '5 - <7' => 0,
    '<5' => 0
];

foreach ($diems as $diem) {
    $diem = floatval($diem);
    if ($diem >= 9) {
        $thongKeKhungDiem['>=9']++;
    } elseif ($diem >= 7) {
        $thongKeKhungDiem['7 - <9']++;
    } elseif ($diem >= 5) {
        $thongKeKhungDiem['5 - <7']++;
    } else {
        $thongKeKhungDiem['<5']++;
    }
}

// Đếm số lượng không đạt (< 5)
$stmt1 = $conn->prepare("
    SELECT COUNT(*) 
    FROM diem_tongket 
    WHERE ID_Dot = :id
        AND Diem_BaoCao IS NOT NULL 
        AND Diem_ChuyenCan IS NOT NULL 
        AND Diem_ChuanNghe IS NOT NULL 
        AND Diem_ThucTe IS NOT NULL
        AND (Diem_BaoCao * 0.4 + Diem_ChuyenCan * 0.2 + Diem_ChuanNghe * 0.2 + Diem_ThucTe * 0.2) < 5
");
$stmt1->execute(['id' => $id]);
$soLuongKhongDat = $stmt1->fetchColumn();

// --- Danh sách SV đăng ký giấy GGT nhưng chưa nhận ---
$stmtGGT = $conn->prepare("
    SELECT sv.Ten, sv.MSSV, sv.Lop
    FROM giaygioithieu g
    JOIN sinhvien sv ON g.IdSinhVien = sv.ID_TaiKhoan
    WHERE g.TrangThai = 2 AND sv.ID_Dot = :id_dot
");
$stmtGGT->execute(['id_dot' => $dot['ID']]);
$danhSachGGT = $stmtGGT->fetchAll(PDO::FETCH_ASSOC);

if (isset($_GET['export_excel']) && $_GET['export_excel'] == 2) {
    ob_clean();
    header_remove();

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle("ThongKeDot");
    $row = 1;

    // ===== TIÊU ĐỀ =====
    $sheet->setCellValue("B$row", "THỐNG KÊ ĐỢT: " . $dot['TenDot']);
    $sheet->mergeCells("B$row:C$row");
    $sheet->getStyle("B$row")->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle("B$row")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $row++;
    // ===== THÔNG TIN ĐỢT =====
    $infoFields = [
        "Bậc đào tạo đào tạo:" => $dot['BacDaoTao'],
        "Năm:" => $dot['Nam'],
        "Người quản lý:" => $dot['TenNguoiQuanLy'],
        "Người mở đợt:" => $dot['TenNguoiMoDot'],
        "Thời gian bắt đầu:" => $dot['ThoiGianBatDau'],
        "Thời gian kết thúc:" => $dot['ThoiGianKetThuc'],
        "Trạng thái:" => match ($dot['TrangThai']) {
            1 => 'Đang chuẩn bị',
            2 => 'Đã bắt đầu',
            4 => 'Đã bắt đầu',
            3 => 'Hoàn tất phân công',
            default => 'Đã kết thúc'
        },
        "Tổng sinh viên:" => $tongSinhVien,
        "Tổng GVHD:" => $tongGVHD,
        "Sinh viên đã hoàn thành:" => $tkTrangThai['DaHoanThanh'],
        "Sinh viên chưa hoàn thành:" => $tkTrangThai['ChuaHoanThanh'],
        "Sinh viên không đạt:" => $soLuongKhongDat,
    ];
    $infoStart = $row;

    foreach ($infoFields as $label => $value) {
        $sheet->setCellValue("B$row", $label);
        $sheet->setCellValue("C$row", $value);
        $row++;
    }
    $infoEnd = $row - 1;
    $sheet->getStyle("B$infoStart:C$infoEnd")->applyFromArray([
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    ]);

    $sheet->setCellValue("F$infoStart", "THỐNG KÊ THEO KHUNG ĐIỂM TỔNG KẾT");
    $sheet->mergeCells("F$infoStart:I$infoStart");
    $sheet->getStyle("F$infoStart")->getFont()->setBold(true);
    $sheet->getStyle("F$infoStart")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $xlRow = $infoStart + 1;

    $sheet->fromArray(['Khung điểm', 'Số lượng'], NULL, "G$xlRow");
    $sheet->getStyle("G$xlRow:H$xlRow")->getFont()->setBold(true);
    $sheet->getStyle("G$xlRow:H$xlRow")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $xlRow++;

    foreach ($thongKeKhungDiem as $khoang => $soLuong) {
        $sheet->setCellValue("G$xlRow", $khoang);
        $sheet->setCellValue("H$xlRow", $soLuong);
        $xlRow++;
    }

    $sheet->getStyle("G" . ($infoStart + 1) . ":H" . ($xlRow - 1))->applyFromArray([
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    ]);
    $xlRow += 1;
    // Ghi tiêu đề bảng dưới phần khung điểm
    $sheet->setCellValue("E$xlRow", "SINH VIÊN ĐĂNG KÝ GIẤY GIỚI THIỆU NHƯNG CHƯA NHẬN");
    $sheet->mergeCells("E$xlRow:J$xlRow");
    $sheet->getStyle("E$xlRow")->getFont()->setBold(true);
    $sheet->getStyle("E$xlRow")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $xlRow++;

    // Ghi header bảng
    $sheet->fromArray(['STT', 'Họ tên', 'MSSV', 'Lớp'], NULL, "F$xlRow");
    $sheet->getStyle("F$xlRow:I$xlRow")->getFont()->setBold(true);
    $sheet->getStyle("F$xlRow:I$xlRow")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $startRow = $xlRow;
    $xlRow++;

    // Ghi dữ liệu
    $stt = 1;
    foreach ($danhSachGGT as $sv) {
        $sheet->setCellValue("F$xlRow", $stt++);
        $sheet->setCellValue("G$xlRow", $sv['Ten']);
        $sheet->setCellValue("H$xlRow", $sv['MSSV']);
        $sheet->setCellValue("I$xlRow", $sv['Lop']);
        $xlRow++;
    }

    // Kẻ khung nếu có dữ liệu
    if ($stt > 1) {
        $sheet->getStyle("F" . ($startRow) . ":I" . ($xlRow - 1))->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

    } else {
        $sheet->setCellValue("F$xlRow", "Không có sinh viên nào.");
        $sheet->mergeCells("F$xlRow:I$xlRow");
        $sheet->getStyle("F$xlRow")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }
    foreach (range('F', 'I') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

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

// Kiểm tra còn sinh viên chưa có GVHD
$stmt = $conn->prepare("SELECT COUNT(*) FROM sinhvien WHERE ID_Dot = :id AND (ID_GVHD IS NULL OR ID_GVHD = '')");
$stmt->execute(['id' => $id]);
$soSVDaCoGVHD = $stmt->fetchColumn();
$phanCongMode = ($soGVChuaCoSV < $tongGVHD && $soSVDaCoGVHD == 0) ? 'phancong_lai' : 'phancong_moi';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'preview_phancong_lai') {
    $id = $_POST['id_dot'] ?? null;
    if (!$id) {
        echo json_encode(['success' => false, 'error' => 'Thiếu ID đợt']);
        exit;
    }

    // Lấy danh sách GV trong đợt
    $stmt = $conn->prepare("SELECT GV.ID_TaiKhoan as id, GV.Ten 
                            FROM dot_giaovien DG 
                            JOIN GiaoVien GV ON DG.ID_GVHD = GV.ID_TaiKhoan 
                            WHERE DG.ID_Dot = :id 
                            ORDER BY GV.Ten");
    $stmt->execute(['id' => $id]);
    $giaoViens = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Lấy tổng số SV CHƯA phân công (đã sửa để kiểm tra cả NULL và chuỗi rỗng)
    $stmt = $conn->prepare("SELECT COUNT(*) FROM SinhVien WHERE ID_Dot = :id AND (ID_GVHD IS NULL OR ID_GVHD = '')");
    $stmt->execute(['id' => $id]);
    $svChuaPhan = (int) $stmt->fetchColumn();

    // Đếm số SV đã được phân cho từng GV
    $phanCong = [];
    $soGVChuaCoSV = 0;

    foreach ($giaoViens as $gv) {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM SinhVien 
                                WHERE ID_Dot = :id AND ID_GVHD = :gv");
        $stmt->execute(['id' => $id, 'gv' => $gv['id']]);
        $soLuong = (int) $stmt->fetchColumn();

        if ($soLuong == 0)
            $soGVChuaCoSV++;

        $phanCong[] = [
            'id' => $gv['id'],
            'ten' => $gv['Ten'],
            'soLuong' => $soLuong
        ];
    }

    // Tính tổng sinh viên
    $tongSinhVien = array_sum(array_column($phanCong, 'soLuong')) + $svChuaPhan;

    echo json_encode([
        'success' => true,
        'phancong' => $phanCong,
        'sv_con_lai' => $svChuaPhan,
        'so_gv_chua_cosv' => $soGVChuaCoSV,
        'tong_sinhvien' => $tongSinhVien
    ], JSON_UNESCAPED_UNICODE);

    exit;
}



// Xác định mode
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

    // Lấy toàn bộ sinh viên trong đợt
    $stmt = $conn->prepare("SELECT ID_TaiKhoan FROM SinhVien WHERE ID_Dot = :id");
    $stmt->execute(['id' => $id]);
    $sinhViens = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (count($giaoViens) == 0 || count($sinhViens) == 0) {
        echo "Không có giáo viên hoặc sinh viên trong đợt!";
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
if (isset($_POST['action']) && $_POST['action'] === 'phancong_lai') {
    $id = $_POST['id_dot'] ?? null;

    if (!$id) {
        echo "Không tìm thấy ID đợt!";
        exit;
    }

    // Xóa phân công cũ
    $stmt = $conn->prepare("UPDATE SinhVien SET ID_GVHD = NULL WHERE ID_Dot = :id");
    $stmt->execute(['id' => $id]);

    // Lấy toàn bộ giáo viên trong đợt
    $stmt = $conn->prepare("SELECT ID_GVHD FROM dot_giaovien WHERE ID_Dot = :id ORDER BY ID_GVHD");
    $stmt->execute(['id' => $id]);
    $giaoViens = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Lấy toàn bộ sinh viên trong đợt
    $stmt = $conn->prepare("SELECT ID_TaiKhoan FROM SinhVien WHERE ID_Dot = :id");
    $stmt->execute(['id' => $id]);
    $sinhViens = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (count($giaoViens) == 0 || count($sinhViens) == 0) {
        echo "Không có giáo viên hoặc sinh viên!";
        exit;
    }

    // Phân công đều lại
    $gvCount = count($giaoViens);
    foreach ($sinhViens as $i => $svId) {
        $gvId = $giaoViens[$i % $gvCount];
        $update = $conn->prepare("UPDATE SinhVien SET ID_GVHD = :gvId WHERE ID_TaiKhoan = :svId");
        $update->execute(['gvId' => $gvId, 'svId' => $svId]);
    }

    echo "OK";
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'auto_phancong_custom') {
    $idDot = $_POST['id_dot'] ?? null;
    $phanCong = $_POST['phan_cong'] ?? [];
    $isReset = $_POST['is_reset'] === 'true';

    if (!$idDot || empty($phanCong)) {
        echo json_encode(['error' => 'Dữ liệu không hợp lệ']);
        exit;
    }

    $conn->beginTransaction();
    try {
        // 1. Lấy tổng số sinh viên trong đợt
        $stmt = $conn->prepare("SELECT COUNT(*) FROM SinhVien WHERE ID_Dot = :id");
        $stmt->execute(['id' => $idDot]);
        $tongSinhVien = $stmt->fetchColumn();

        // 2. Tính tổng phân công mới
        $tongPhanCongMoi = array_sum($phanCong);

        if ($tongPhanCongMoi > $tongSinhVien) {
            throw new Exception("Tổng phân công ($tongPhanCongMoi) vượt quá số sinh viên ($tongSinhVien)");
        }

        // 3. Reset toàn bộ phân công cũ
        $stmt = $conn->prepare("UPDATE SinhVien SET ID_GVHD = NULL WHERE ID_Dot = :id");
        $stmt->execute(['id' => $idDot]);

        // 4. Phân công mới
        $stmt = $conn->prepare("SELECT ID_TaiKhoan FROM SinhVien 
                               WHERE ID_Dot = :id 
                               ORDER BY ID_TaiKhoan");
        $stmt->execute(['id' => $idDot]);
        $sinhViens = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $index = 0;
        $daPhanCong = 0;

        foreach ($phanCong as $idGV => $soLuong) {
            $count = 0;
            while ($count < $soLuong && $index < count($sinhViens)) {
                $svId = $sinhViens[$index++];
                $stmt = $conn->prepare("UPDATE SinhVien SET ID_GVHD = :gv 
                                      WHERE ID_TaiKhoan = :sv");
                $stmt->execute(['gv' => $idGV, 'sv' => $svId]);
                $count++;
                $daPhanCong++;
            }
        }

        $conn->commit();
        echo json_encode([
            'success' => true,
            'da_phancong' => $daPhanCong
        ]);

    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'preview_phancong') {
    $id = $_POST['id_dot'] ?? null;
    if (!$id) {
        echo json_encode(['success' => false, 'error' => 'Thiếu ID đợt']);
        exit;
    }

    // Lấy danh sách GV trong đợt
    $stmt = $conn->prepare("SELECT GV.ID_TaiKhoan as id, GV.Ten 
                            FROM dot_giaovien DG 
                            JOIN GiaoVien GV ON DG.ID_GVHD = GV.ID_TaiKhoan 
                            WHERE DG.ID_Dot = :id 
                            ORDER BY GV.Ten");
    $stmt->execute(['id' => $id]);
    $giaoViens = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Tính tổng số sinh viên trong đợt
    $stmt = $conn->prepare("SELECT COUNT(*) FROM SinhVien WHERE ID_Dot = :id");
    $stmt->execute(['id' => $id]);
    $tongSinhVien = (int) $stmt->fetchColumn();

    // Đếm số lượng SV của từng GV
    $phanCong = [];
    $soGVChuaCoSV = 0;
    foreach ($giaoViens as $gv) {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM SinhVien 
                                WHERE ID_Dot = :id AND ID_GVHD = :gv");
        $stmt->execute(['id' => $id, 'gv' => $gv['id']]);
        $soLuong = (int) $stmt->fetchColumn();

        if ($soLuong == 0)
            $soGVChuaCoSV++;

        $phanCong[] = [
            'id' => $gv['id'],
            'ten' => $gv['Ten'],
            'soLuong' => $soLuong
        ];
    }

    echo json_encode([
        'success' => true,
        'phancong' => $phanCong, // vẫn giữ nguyên soLuong cũ
        'sv_con_lai' => $tongSinhVien,
        'so_gv_chua_cosv' => $soGVChuaCoSV,
        'tong_sinhvien' => $tongSinhVien
    ], JSON_UNESCAPED_UNICODE);

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
                    <button onclick="window.location.href='/datn/pages/canbo/chitietdot?id=<?= $id ?>&export_excel=1'"
                        class="btn btn-success">
                        <i class="fa fa-file-excel-o"></i> Xuất danh sách phân công
                    </button>
                    <button onclick="window.location.href='/datn/pages/canbo/chitietdot?id=<?= $id ?>&export_excel=2'"
                        class="btn btn-success">
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
        #page-wrapper {
            padding: 30px;
            min-height: 100vh;
            box-sizing: border-box;
            max-height: 100%;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }

        .page-header {
            color: #2c3e50;
            font-weight: 700;
            margin-bottom: 30px;
            margin-top: 28px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

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

        #page-wrapper {
            margin-top: 50px;
        }

        .btn {
            margin: 1px;
        }
    </style>
</head>

<body>
    <div id="wrapper">
        <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/slidebar_CanBo.php"; ?>
        <div id="page-wrapper">
            <div class="container-fluid">
                <div class="dot-header panel panel-default" style="padding:24px 18px 10px 18px;">
                    <?php if (!empty($successMessage)): ?>
                        <div id="successAlert" class="alert alert-success">
                            <?= $successMessage ?>
                        </div>
                    <?php endif; ?>
                    <h1 class="panel-title" style="font-size:2rem; font-weight:bold; margin-bottom:18px;">
                        <?= htmlspecialchars($dot['TenDot']) ?>
                    </h1>

                    <div class="row" style="margin-bottom:10px;">
                        <div class="col-sm-4 col-xs-12">
                            <p><b>Bậc đào tạo:</b> <?= htmlspecialchars($dot['BacDaoTao']) ?></p>
                            <p><b>Năm:</b> <?= htmlspecialchars($dot['Nam']) ?></p>
                            <p><b>Người quản lý:</b> <?= htmlspecialchars($dot['TenNguoiQuanLy']) ?></p>
                            <p><b>Người mở đợt:</b> <?= htmlspecialchars($dot['TenNguoiMoDot']) ?></p>

                        </div>
                        <div class="col-sm-4 col-xs-12">
                            <p><b>Thời gian bắt đầu: </b> <?= htmlspecialchars($dot['ThoiGianBatDau']) ?></p>
                            <p><b>Thời gian kết thúc: </b> <?= htmlspecialchars($dot['ThoiGianKetThuc']) ?></p>
                            <p><b>Trạng thái:</b>
                                <?php
                                if ($dot['TrangThai'] == 1)
                                    echo 'Đang chuẩn bị';
                                elseif ($dot['TrangThai'] == 2 || $dot['TrangThai'] == 4)
                                    echo 'Đã bắt đầu';
                                elseif ($dot['TrangThai'] == 3)
                                    echo 'Hoàn tất phân công';
                                elseif ($dot['TrangThai'] == 0)
                                    echo 'Đã kết thúc';
                                else
                                    echo 'Nộp kết quả'
                                        ?>
                                </p>
                            </div>
                            <div class="col-sm-4 col-xs-12">
                                <p><b>Tổng sinh viên:</b> <?= $tongSinhVien ?></p>
                            <div id="infoBox">
                                <p><b>Tổng GVHD:</b> <span id="infoTongGVHD"><?= $tongGVHD ?></span></p>
                                <p><b>Sinh viên chưa có GVHD:</b> <span id="infoSVDaCoGVHD"><?= $soSVDaCoGVHD ?></span>
                                </p>
                                <p><b>GVHD chưa được phân công:</b> <span
                                        id="infoGVChuaCoSV"><?= $soGVChuaCoSV ?></span></p>
                            </div>
                        </div>
                    </div>
                    <div class="dot-actions" style="margin-top:12px;">
                        <button onclick="window.location='/datn/pages/canbo/importexcel?id=<?= $id ?>';"
                            class="btn btn-primary btn-md nut" title="Import danh sách sinh viên" <?= $dot['TrangThai'] != 1 ? 'disabled' : '' ?>>Import sinh
                            viên</button>
                        <button type="button" class="btn btn-primary btn-md nut" id="btnMoModalThemGV"
                            title="Thêm giáo viên cho đợt thực tập" <?= $dot['TrangThai'] != 1 ? 'disabled' : '' ?>>
                            Thêm giáo viên
                        </button>
                        <button type="button" id="btnAutoPhanCong" class="btn btn-success btn-md nut"
                            data-mode="<?= $phanCongMode ?>"
                            title="<?= $phanCongMode == 'phancong_lai' ? 'Phân công tự động lại tất cả sinh viên' : 'Phân công tự động' ?>"
                            <?= $dot['TrangThai'] != 1 ? 'disabled' : '' ?>>
                            <?= $phanCongMode == 'phancong_lai' ? 'Phân công lại' : 'Phân công tự động' ?>
                        </button>
                        <button type="button" class="btn btn-success nut" id="btnHoanTat" <?= ($tongSinhVien < 1 || $tongGVHD < 1) || $dot['TrangThai'] != 1 ? 'disabled' : '' ?>
                            title="Hoàn tất phân công, cho phép đăng ký phiếu giới thiệu, gửi mail cho các giáo viên">
                            Hoàn tất phân công
                        </button>
                        <button onclick="window.location='/datn/pages/canbo/chinhsuadot?id=<?= $id ?>';"
                            class="btn btn-warning btn-md nut" <?= $dot['TrangThai'] == 0 ? 'disabled' : '' ?>>Chỉnh
                            sửa</button>
                        <button
                            onclick="if(confirm('Bạn có chắc muốn xóa đợt này?')) window.location='/datn/pages/canbo/xoadot?id=<?= $id ?>';"
                            class="btn btn-danger btn-md nut" <?= ($tongSinhVien > 0 || $tongGVHD > 0) || $dot['TrangThai'] != 1 ? 'disabled title="Không thể xóa: Đợt đã có sinh viên hoặc giáo viên."' : '' ?>>Xóa
                            đợt</button>

                    </div>
                </div>

                <ul class="nav nav-tabs" id="tabDotThucTap">
                    <li class="active"><a href="#tab-gv" data-toggle="tab" data-tab="gv">Giáo viên hướng dẫn</a>
                    </li>
                    <li><a href="#tab-sv" data-toggle="tab" data-tab="sv">Sinh viên</a></li>
                </ul>
                <div class="tab-content" style="background:#fff; border:1px solid #ddd; border-top:0; padding:18px;">
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
        function capNhatNutPhanCong(idDot) {
            $.post('/datn/pages/canbo/ajax_get_phancong_mode.php', { id_dot: idDot }, function (res) {
                if (!res.success) return;

                const btn = $('#btnAutoPhanCong');
                const mode = res.mode;

                btn.attr('data-mode', mode);
                btn.attr('title', mode === 'phancong_lai'
                    ? 'Phân công lại tất cả sinh viên'
                    : 'Phân công đều các sinh viên chưa được phân công');
                btn.html(mode === 'phancong_lai' ? 'Phân công lại' : 'Phân công tự động');
            }, 'json');
        }
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
                        // Thêm data-current="${sv.ID_GVHD ?? ''}"
                        let select = `<select class="form-control select-gvhd-modal" 
                                data-mssv="${sv.ID_TaiKhoan}" 
                                data-current="${sv.ID_GVHD ?? ''}"
                                ${<?= json_encode($dot['TrangThai']) ?> <= 0 ? 'disabled' : ''}>`;

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
        var trangThaiDot = <?= json_encode($trangThaiDot) ?>; // truyền từ PHP

        $(document).on('change', '.select-gvhd, .select-gvhd-modal', function () {
            var id_sv = $(this).data('mssv');
            var id_gv = $(this).val();
            var id_dot = <?= json_encode($id) ?>;
            var select = this;

            if (id_gv === "") id_gv = null;

            if (trangThaiDot != 1) {
                Swal.fire({
                    title: 'Xác nhận chuyển giáo viên?',
                    text: 'Bạn có chắc muốn chuyển giáo viên hướng dẫn cho sinh viên này?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Chuyển',
                    cancelButtonText: 'Hủy'
                }).then((result) => {
                    if (result.isConfirmed) {
                        thucHienChuyenGV(id_sv, id_gv, id_dot, select);
                    } else {
                        // Nếu hủy thì khôi phục lại lựa chọn ban đầu
                        $(select).val($(select).data('current'));
                    }
                });
            } else {
                thucHienChuyenGV(id_sv, id_gv, id_dot, select);
            }
        });

        function thucHienChuyenGV(id_sv, id_gv, id_dot, select) {
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

                    // Cập nhật số lượng sinh viên của giáo viên cũ
                    if (res.gvhdCu) {
                        var rowCu = $('#table-gv tbody tr').filter(function () {
                            return $(this).find('td').eq(1).data('id') == res.gvhdCu;
                        });
                        if (rowCu.length) {
                            rowCu.find('td').eq(2).text(res.soLuongCu);
                        }
                    }

                    // Cập nhật số lượng sinh viên của giáo viên mới
                    if (res.gvhdMoi) {
                        var rowMoi = $('#table-gv tbody tr').filter(function () {
                            return $(this).find('td').eq(1).data('id') == res.gvhdMoi;
                        });
                        if (rowMoi.length) {
                            rowMoi.find('td').eq(2).text(res.soLuongMoi);
                        }
                    }

                    reloadInfoBox();
                    loadTabGV();
                    capNhatNutPhanCong(id_dot);
                },
                error: function (xhr) {
                    alert("Cập nhật thất bại: " + xhr.responseText);
                }
            });
        }

        $(document).ready(function () {
            $('#btnAutoPhanCong').on('click', function () {
                const mode = $(this).data('mode');

                // --- PHÂN CÔNG LẠI ---
                if (mode === 'phancong_lai') {
                    Swal.fire({
                        title: 'Phân công lại tất cả sinh viên?',
                        text: 'Bạn có muốn phân công lại toàn bộ sinh viên cho giáo viên không?',
                        icon: 'question',
                        showDenyButton: true,
                        showCancelButton: true,
                        confirmButtonText: 'Phân công đều',
                        denyButtonText: 'Tuỳ chỉnh',
                        cancelButtonText: 'Huỷ'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.post(window.location.pathname, {
                                action: 'phancong_lai',
                                id_dot: <?= json_encode($id) ?>
                            }, function (res) {
                                if (res === 'OK') {
                                    Swal.fire('Thành công', 'Phân công lại hoàn tất.', 'success').then(() => location.reload());
                                } else {
                                    Swal.fire('Lỗi', res, 'error');
                                }
                            });
                        } else if (result.isDenied) {
                            $.post(window.location.pathname, {
                                action: 'preview_phancong_lai',
                                id_dot: <?= json_encode($id) ?>
                            }, function (res) {
                                if (!res.success || !res.phancong) {
                                    Swal.fire('Không thể phân công', 'Không còn sinh viên hoặc giáo viên trong đợt.', 'warning');
                                    return;
                                }

                                const tongSinhVien = res.tong_sinhvien;
                                const tongDaGan = res.phancong.reduce((sum, p) => sum + p.soLuong, 0);
                                const conLai = tongSinhVien - tongDaGan;

                                $('#svConLaiCount').text(conLai);
                                $('#gvChuaCoSVCount').text(res.phancong.filter(gv => gv.soLuong === 0).length);

                                // Gắn tổng SV của đợt (để dùng khi submit)
                                $('#formPhanCongCustom').append(`<input type="hidden" id="tongSinhVienTrongDot" value="${tongSinhVien}">`);

                                let html = res.phancong.map((item, i) => `
            <tr>
                <td>${i + 1}</td>
                <td>${item.ten}</td>
                <td>
                    <input type="number" class="form-control soLuongGV" 
                        data-id="${item.id}" 
                        min="0" 
                        value="${item.soLuong}" 
                        data-old="${item.soLuong}">
                </td>
            </tr>
        `).join('');

                                $('#phanCongBody').html(html);
                                $('#phanCongModal').modal('show');

                                // Gắn sự kiện kiểm tra từng input
                                $('.soLuongGV').on('input', function () {
                                    const input = $(this);
                                    const oldVal = parseInt(input.attr('data-old')) || 0;
                                    const valMoi = parseInt(input.val()) || 0;

                                    if (valMoi < 0) {
                                        input.val(oldVal);
                                        return Swal.fire({
                                            icon: 'warning',
                                            title: 'Giá trị không hợp lệ',
                                            text: 'Số lượng không được nhỏ hơn 0.',
                                            timer: 1500,
                                            showConfirmButton: false
                                        });
                                    }

                                    let tong = 0;
                                    let gvChuaCoSV = 0;

                                    $('.soLuongGV').each(function () {
                                        const val = parseInt($(this).val()) || 0;
                                        tong += val;
                                        if (val === 0) gvChuaCoSV++;
                                    });

                                    const conLai = tongSinhVien - tong;

                                    if (conLai >= 0) {
                                        $('#svConLaiCount').text(conLai);
                                        $('#gvChuaCoSVCount').text(gvChuaCoSV);
                                        input.attr('data-old', valMoi);
                                    } else {
                                        input.val(oldVal);
                                        Swal.fire({
                                            icon: 'warning',
                                            title: 'Vượt quá số lượng',
                                            text: 'Không thể phân công quá số sinh viên của đợt.',
                                            timer: 1500,
                                            showConfirmButton: false
                                        });
                                    }
                                });
                            }, 'json');
                        }




                    });

                    // --- PHÂN CÔNG MỚI ---
                } else {
                    Swal.fire({
                        title: 'Phân công sinh viên',
                        text: 'Bạn muốn thực hiện phân công như thế nào?',
                        icon: 'question',
                        showCancelButton: true,
                        showDenyButton: true,
                        confirmButtonText: 'Phân công đều',
                        denyButtonText: 'Tùy chỉnh',
                        cancelButtonText: 'Huỷ',
                        confirmButtonColor: '#28a745',
                        denyButtonColor: '#007bff',
                        cancelButtonColor: '#d33'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.post(window.location.pathname, {
                                action: 'auto_phancong',
                                id_dot: <?= json_encode($id) ?>
                            }, function (res) {
                                if (res.trim() === 'OK') {
                                    Swal.fire('Thành công!', 'Phân công tự động thành công!', 'success')
                                        .then(() => location.reload());

                                } else {
                                    Swal.fire('Lỗi', res, 'error');
                                }
                            });

                        } else if (result.isDenied) {
                            $.post(window.location.pathname, {
                                action: 'preview_phancong',
                                id_dot: <?= json_encode($id) ?>
                            }, function (res) {
                                if (!res.success || !res.phancong) {
                                    Swal.fire('Không thể phân công', 'Không còn sinh viên hoặc giáo viên trong đợt.', 'warning');
                                    return;
                                }

                                const tongSinhVien = res.tong_sinhvien;
                                const tongDaGan = res.phancong.reduce((sum, p) => sum + p.soLuong, 0);
                                const svConLai = tongSinhVien - tongDaGan;

                                // Chia đều sinh viên
                                const soGV = res.phancong.length;
                                const moiMoi = Math.floor((tongSinhVien - res.phancong.reduce((sum, p) => sum + p.soLuong, 0)) / soGV);
                                const du = (tongSinhVien - res.phancong.reduce((sum, p) => sum + p.soLuong, 0)) % soGV;

                                const phanCongData = JSON.parse(JSON.stringify(res.phancong));
                                phanCongData.forEach((gv, i) => {
                                    gv.soLuong += moiMoi + (i < du ? 1 : 0);
                                });

                                // ✅ Tính lại sau chia (không dùng từ DB nữa)
                                const tongSauChia = phanCongData.reduce((sum, gv) => sum + gv.soLuong, 0);
                                const svConLaiSauChia = tongSinhVien - tongSauChia;
                                const gvChuaCoSVPreview = phanCongData.filter(gv => gv.soLuong === 0).length;

                                // ✅ Hiển thị đúng preview
                                $('#svConLaiCount').text(svConLaiSauChia);
                                $('#gvChuaCoSVCount').text(gvChuaCoSVPreview);

                                $('#formPhanCongCustom #tongSinhVienTrongDot').remove();
                                $('#formPhanCongCustom').append(`<input type="hidden" id="tongSinhVienTrongDot" value="${tongSinhVien}">`);

                                // Tạo bảng
                                const html = phanCongData.map((item, i) => `
            <tr>
                <td>${i + 1}</td>
                <td>${item.ten}</td>
                <td>
                    <input type="number" class="form-control soLuongGV" 
                        data-id="${item.id}" 
                        min="0" 
                        value="${item.soLuong}" 
                        data-old="${item.soLuong}">
                </td>
            </tr>
        `).join('');

                                $('#phanCongBody').html(html);
                                $('#phanCongModal').modal('show');

                                // Theo dõi chỉnh sửa
                                $('.soLuongGV').off('input').on('input', function () {
                                    const input = $(this);
                                    const oldVal = parseInt(input.attr('data-old')) || 0;
                                    const valMoi = parseInt(input.val()) || 0;

                                    if (valMoi < 0) {
                                        input.val(oldVal);
                                        return Swal.fire({
                                            icon: 'warning',
                                            title: 'Giá trị không hợp lệ',
                                            text: 'Số lượng không được nhỏ hơn 0.',
                                            timer: 1500,
                                            showConfirmButton: false
                                        });
                                    }

                                    // Tính lại tổng và số GV chưa có SV
                                    let tong = 0;
                                    let gvChuaCoSV = 0;

                                    $('.soLuongGV').each(function () {
                                        const val = parseInt($(this).val()) || 0;
                                        tong += val;
                                        if (val === 0) gvChuaCoSV++;
                                    });

                                    const conLai = tongSinhVien - tong;

                                    if (conLai >= 0) {
                                        $('#svConLaiCount').text(conLai);
                                        $('#gvChuaCoSVCount').text(gvChuaCoSV);
                                        input.attr('data-old', valMoi);
                                    } else {
                                        input.val(oldVal);
                                        Swal.fire({
                                            icon: 'warning',
                                            title: 'Vượt quá số lượng',
                                            text: 'Không thể phân công quá tổng sinh viên.',
                                            timer: 1500,
                                            showConfirmButton: false
                                        });
                                    }
                                });

                            }, 'json').fail(function (jqXHR, textStatus, errorThrown) {
                                console.error('AJAX Error:', textStatus, errorThrown);
                                Swal.fire('Lỗi', 'Không thể tải dữ liệu phân công', 'error');
                            });
                        }




                    });
                }
            });

            // Submit tùy chỉnh
            $('#formPhanCongCustom').on('submit', function (e) {
                e.preventDefault();

                const phanCong = {};
                let tongPhanCongMoi = 0;

                // Lấy thông tin phân công mới từ form
                $('.soLuongGV').each(function () {
                    const id = $(this).data('id');
                    const val = parseInt($(this).val()) || 0;
                    if (val > 0) {
                        phanCong[id] = val;
                        tongPhanCongMoi += val;
                    }
                });

                // Lấy tổng số sinh viên trong đợt
                const tongSinhVien = parseInt($('#tongSinhVienTrongDot').val()) || 0;

                // Kiểm tra cơ bản
                if (tongPhanCongMoi > tongSinhVien) {
                    Swal.fire('Lỗi', 'Tổng phân công không thể vượt quá tổng số sinh viên', 'error');
                    return;
                }

                // Gửi yêu cầu phân công
                $.post(window.location.pathname, {
                    action: 'auto_phancong_custom',
                    id_dot: <?= json_encode($id) ?>,
                    is_reset: true,
                    phan_cong: phanCong
                }, function (res) {
                    if (res.success) {
                        Swal.fire('Thành công', `Đã phân công ${res.da_phancong} sinh viên`, 'success')
                            .then(() => location.reload());
                    } else {
                        Swal.fire('Lỗi', res.error || 'Lỗi khi phân công', 'error');
                    }
                }, 'json');
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
                    reponsive: true,
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
                    reponsive: true,
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
    <div class="modal fade" id="modalDanhSachSV" tabindex="-1" role="dialog" aria-labelledby="modalDanhSachSVLabel">
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
                                <input type="password" class="form-control" id="matKhauGVmoi" name="matKhauGVmoi"
                                    placeholder="Nhập mật khẩu">
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
    <!-- Modal Phân công tuỳ chỉnh -->
    <div class="modal fade" id="phanCongModal" tabindex="-1" role="dialog" aria-labelledby="phanCongLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <form id="formPhanCongCustom" class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="phanCongLabel">Phân công sinh viên tuỳ chỉnh</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Đóng">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p id="phanCongInfo" class="text-info font-weight-bold">
                        Còn lại: <span id="svConLaiCount">0</span> Sinh viên |
                        <span id="gvChuaCoSVCount">0</span> Giáo viên
                    </p>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th>#</th>
                                    <th>Giáo viên</th>
                                    <th>Số lượng sinh viên</th>
                                </tr>
                            </thead>
                            <tbody id="phanCongBody">
                                <!-- Dữ liệu được load bằng JS -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Phân công</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Huỷ</button>
                </div>
            </form>
        </div>
    </div>

</body>

</html>