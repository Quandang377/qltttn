<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/template/config.php';

// Lấy ID giáo viên từ session
$idGiaoVien = isset($_SESSION['user']['ID_TaiKhoan']) ? $_SESSION['user']['ID_TaiKhoan'] : 0;

if (!$idGiaoVien) {
    die('Bạn chưa đăng nhập!');
}

// Lấy danh sách sinh viên do giáo viên hướng dẫn
$sinhVienList = [];
$stmt = $conn->prepare("
    SELECT sv.ID_TaiKhoan, sv.Ten, sv.MSSV, sv.ID_Dot, dt.TenDot
    FROM sinhvien sv
    JOIN dotthuctap dt ON sv.ID_Dot = dt.ID
    WHERE sv.ID_GVHD = :id_gv
    ORDER BY dt.ID DESC, sv.MSSV ASC
");
$stmt->bindParam(':id_gv', $idGiaoVien, PDO::PARAM_INT);
$stmt->execute();
$sinhVienList = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy tham số lọc
$selectedSinhVien = isset($_GET['sinh_vien']) ? (int)$_GET['sinh_vien'] : 0;
$selectedDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$selectedWeek = isset($_GET['week']) ? $_GET['week'] : date('Y-m-d', strtotime('monday this week'));

// Lấy thống kê tổng quan cho tất cả sinh viên
$thongKeTongQuan = [];
if (!empty($sinhVienList)) {
    $startWeek = date('Y-m-d', strtotime($selectedWeek));
    $endWeek = date('Y-m-d', strtotime($startWeek . ' +6 days'));
    
    foreach ($sinhVienList as $sv) {
        $stmt = $conn->prepare("
            SELECT 
                COUNT(*) as tong_cong_viec,
                SUM(CASE WHEN TienDo = 100 THEN 1 ELSE 0 END) as hoan_thanh,
                AVG(TienDo) as tien_do_trung_binh,
                COUNT(DISTINCT Ngay) as so_ngay_lam_viec
            FROM congviec_baocao 
            WHERE IDSV = :idsv AND Ngay BETWEEN :start_date AND :end_date
        ");
        $stmt->bindParam(':idsv', $sv['ID_TaiKhoan'], PDO::PARAM_INT);
        $stmt->bindParam(':start_date', $startWeek);
        $stmt->bindParam(':end_date', $endWeek);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $thongKeTongQuan[$sv['ID_TaiKhoan']] = [
            'thong_tin' => $sv,
            'tong_cong_viec' => (int)$result['tong_cong_viec'],
            'hoan_thanh' => (int)$result['hoan_thanh'],
            'tien_do_trung_binh' => round($result['tien_do_trung_binh'] ?? 0, 1),
            'so_ngay_lam_viec' => (int)$result['so_ngay_lam_viec'],
            'ty_le_hoan_thanh' => $result['tong_cong_viec'] > 0 ? round(($result['hoan_thanh'] / $result['tong_cong_viec']) * 100, 1) : 0
        ];
    }
}

// Lấy báo cáo tuần của sinh viên được chọn
$baoCaoTuan = [];
$thongKe = ['tong_cong_viec' => 0, 'hoan_thanh' => 0, 'tien_do_trung_binh' => 0];

if ($selectedSinhVien > 0) {
    // Lấy báo cáo theo ngày
    if ($selectedDate) {
        $stmt = $conn->prepare("
            SELECT * FROM congviec_baocao 
            WHERE IDSV = :idsv AND Ngay = :ngay 
            ORDER BY ID DESC
        ");
        $stmt->bindParam(':idsv', $selectedSinhVien, PDO::PARAM_INT);
        $stmt->bindParam(':ngay', $selectedDate);
        $stmt->execute();
        $baoCaoTuan = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Lấy thống kê tuần (7 ngày từ thứ 2)
    $startWeek = date('Y-m-d', strtotime($selectedWeek));
    $endWeek = date('Y-m-d', strtotime($startWeek . ' +6 days'));
    
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as tong_cong_viec,
            SUM(CASE WHEN TienDo = 100 THEN 1 ELSE 0 END) as hoan_thanh,
            AVG(TienDo) as tien_do_trung_binh
        FROM congviec_baocao 
        WHERE IDSV = :idsv AND Ngay BETWEEN :start_date AND :end_date
    ");
    $stmt->bindParam(':idsv', $selectedSinhVien, PDO::PARAM_INT);
    $stmt->bindParam(':start_date', $startWeek);
    $stmt->bindParam(':end_date', $endWeek);
    $stmt->execute();
    $thongKeResult = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($thongKeResult) {
        $thongKe = [
            'tong_cong_viec' => (int)$thongKeResult['tong_cong_viec'],
            'hoan_thanh' => (int)$thongKeResult['hoan_thanh'],
            'tien_do_trung_binh' => round($thongKeResult['tien_do_trung_binh'], 1)
        ];
    }
}

// Lấy báo cáo tuần theo từng ngày để hiển thị trên lịch
$baoCaoTheoNgay = [];
if ($selectedSinhVien > 0) {
    $startWeek = date('Y-m-d', strtotime($selectedWeek));
    $endWeek = date('Y-m-d', strtotime($startWeek . ' +6 days'));
    
    $stmt = $conn->prepare("
        SELECT 
            Ngay,
            COUNT(*) as so_cong_viec,
            SUM(CASE WHEN TienDo = 100 THEN 1 ELSE 0 END) as hoan_thanh,
            AVG(TienDo) as tien_do_trung_binh
        FROM congviec_baocao 
        WHERE IDSV = :idsv AND Ngay BETWEEN :start_date AND :end_date
        GROUP BY Ngay
        ORDER BY Ngay
    ");
    $stmt->bindParam(':idsv', $selectedSinhVien, PDO::PARAM_INT);
    $stmt->bindParam(':start_date', $startWeek);
    $stmt->bindParam(':end_date', $endWeek);
    $stmt->execute();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $baoCaoTheoNgay[$row['Ngay']] = [
            'so_cong_viec' => (int)$row['so_cong_viec'],
            'hoan_thanh' => (int)$row['hoan_thanh'],
            'tien_do_trung_binh' => round($row['tien_do_trung_binh'], 1)
        ];
    }
}

// Tính toán các ngày trong tuần hiện tại
$startOfWeek = date('Y-m-d', strtotime($selectedWeek));
$daysOfWeek = [];
for ($i = 0; $i < 7; $i++) {
    $daysOfWeek[] = date('Y-m-d', strtotime($startOfWeek . " +$i days"));
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý báo cáo tuần</title>
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php"; ?>
    <style>
        body {
            background: linear-gradient(135deg, #e3f0ff 0%, #f8fafc 100%);
            font-family: 'Segoe UI', 'Roboto', Arial, sans-serif;
        }
        
        #page-wrapper {
            padding: 20px;
            min-height: 100vh;
        }
        
        .page-header {
            font-size: 2rem;
            font-weight: 700;
            color: #1e40af;
            text-align: center;
            margin-bottom: 30px;
            text-shadow: 0 2px 4px rgba(30, 64, 175, 0.1);
        }
        
        .panel {
            border-radius: 12px !important;
            border: 1px solid #e2e8f0 !important;
            background: #ffffff;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            margin-bottom: 24px;
        }
        
        .panel-heading {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            border-radius: 12px 12px 0 0;
            padding: 16px 20px;
            border-bottom: 1px solid #e2e8f0;
            color: #1e40af;
            font-weight: 600;
            font-size: 16px;
        }
        
        .panel-body {
            background: #ffffff;
            border-radius: 0 0 12px 12px;
            padding: 20px;
        }
        
        .filter-container {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
            text-align: center;
            border-left: 4px solid #1a73e8;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #1a73e8;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #70757a;
            font-size: 14px;
            font-weight: 500;
        }
        
        .week-calendar {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .week-header {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            background: #f8f9fa;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .day-header {
            padding: 15px 10px;
            text-align: center;
            border-right: 1px solid #e0e0e0;
            font-weight: 600;
            color: #3c4043;
        }
        
        .day-header:last-child {
            border-right: none;
        }
        
        .week-body {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            min-height: 150px;
        }
        
        .day-cell {
            padding: 15px 10px;
            border-right: 1px solid #e0e0e0;
            cursor: pointer;
            transition: background-color 0.2s;
            position: relative;
        }
        
        .day-cell:last-child {
            border-right: none;
        }
        
        .day-cell:hover {
            background-color: #f8f9fa;
        }
        
        .day-cell.selected {
            background-color: #e8f0fe;
            border: 2px solid #1a73e8;
        }
        
        .day-cell.today {
            background-color: #e8f0fe;
        }
        
        .day-date {
            font-size: 14px;
            font-weight: 600;
            color: #3c4043;
            margin-bottom: 8px;
        }
        
        .day-stats {
            font-size: 12px;
            color: #70757a;
        }
        
        .progress-indicator {
            width: 100%;
            height: 4px;
            background-color: #e0e0e0;
            border-radius: 2px;
            margin: 8px 0;
            overflow: hidden;
        }
        
        .progress-bar {
            height: 100%;
            background-color: #1a73e8;
            border-radius: 2px;
            transition: width 0.3s ease;
        }
        
        .task-list-container {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .task-list-header {
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
            font-weight: 600;
            color: #3c4043;
            font-size: 16px;
        }
        
        .task-item {
            padding: 15px 20px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .task-item:last-child {
            border-bottom: none;
        }
        
        .task-content {
            flex: 1;
        }
        
        .task-name {
            font-weight: 600;
            color: #3c4043;
            margin-bottom: 4px;
        }
        
        .task-desc {
            color: #70757a;
            font-size: 14px;
            margin-bottom: 8px;
        }
        
        .task-time {
            color: #70757a;
            font-size: 12px;
        }
        
        .task-progress {
            text-align: center;
            min-width: 80px;
        }
        
        .progress-circle {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 14px;
            margin: 0 auto 5px;
        }
        
        .progress-0-30 { background-color: #e53935; }
        .progress-31-70 { background-color: #fb8c00; }
        .progress-71-99 { background-color: #1e88e5; }
        .progress-100 { background-color: #43a047; }
        
        .form-control {
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 8px 12px;
            font-size: 14px;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        
        .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            outline: none;
        }
        
        .btn {
            border-radius: 8px !important;
            font-weight: 500;
            padding: 8px 16px;
            border: none;
            transition: all 0.2s ease;
            text-decoration: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        
        .text-center { text-align: center !important; }
        .mb-3 { margin-bottom: 1rem !important; }
        .d-flex { display: flex !important; }
        .align-items-center { align-items: center !important; }
        .justify-content-between { justify-content: space-between !important; }
        
        @media (max-width: 768px) {
            #page-wrapper { padding: 15px; }
            .stats-container { grid-template-columns: 1fr; }
            .week-header, .week-body { grid-template-columns: repeat(7, 1fr); font-size: 12px; }
            .day-header, .day-cell { padding: 10px 5px; }
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #70757a;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #bdc1c6;
        }
        
        /* Student Overview Panels */
        .student-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .student-panel {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
        }
        
        .student-panel:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            border-color: #1a73e8;
        }
        
        .student-panel.active {
            border-color: #1a73e8;
            background: #f8f9ff;
        }
        
        .student-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .student-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #1a73e8, #4285f4);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 18px;
            margin-right: 15px;
            flex-shrink: 0;
        }
        
        .student-info h4 {
            margin: 0 0 5px 0;
            color: #3c4043;
            font-size: 16px;
            font-weight: 600;
        }
        
        .student-info p {
            margin: 0;
            color: #70757a;
            font-size: 14px;
        }
        
        .student-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .mini-stat {
            text-align: center;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .mini-stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1a73e8;
            margin-bottom: 2px;
        }
        
        .mini-stat-label {
            font-size: 12px;
            color: #70757a;
            font-weight: 500;
        }
        
        .student-progress-summary {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #f0f0f0;
        }
        
        .progress-summary-text {
            flex: 1;
        }
        
        .progress-summary-label {
            font-size: 12px;
            color: #70757a;
            margin-bottom: 3px;
        }
        
        .progress-summary-value {
            font-size: 14px;
            font-weight: 600;
            color: #3c4043;
        }
        
        .progress-summary-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 12px;
            margin-left: 10px;
        }
        
        .status-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-excellent {
            background: #e6f4ea;
            color: #1e8e3e;
        }
        
        .status-good {
            background: #e8f0fe;
            color: #1a73e8;
        }
        
        .status-average {
            background: #fff4e5;
            color: #e8710a;
        }
        
        .status-poor {
            background: #fce8e6;
            color: #d93025;
        }
        
        .view-mode-toggle {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            justify-content: center;
        }
        
        .mode-btn {
            padding: 8px 16px;
            border: 1px solid #e0e0e0;
            background: #fff;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 14px;
            font-weight: 500;
        }
        
        .mode-btn.active {
            background: #1a73e8;
            color: white;
            border-color: #1a73e8;
        }
        
        .mode-btn:hover:not(.active) {
            background: #f8f9fa;
        }
        
        /* Improvements for many students */
        .student-grid {
            max-height: 80vh;
            overflow-y: auto;
            padding-right: 10px;
        }
        
        .student-grid::-webkit-scrollbar {
            width: 8px;
        }
        
        .student-grid::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        
        .student-grid::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }
        
        .student-grid::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
        
        .student-panel.hidden {
            display: none;
        }
        
        .no-results {
            grid-column: 1 / -1;
            text-align: center;
            padding: 40px 20px;
            color: #70757a;
        }
        
        @media (min-width: 1600px) {
            .student-grid {
                grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            }
        }
        
        @media (max-width: 400px) {
            .student-grid {
                grid-template-columns: 1fr;
            }
            .student-panel {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div id="wrapper">
        <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/slidebar_Giaovien.php"; ?>

        <div id="page-wrapper">
            <div class="container-fluid">
                <h1 class="page-header">
                    <i class="fa fa-calendar"></i> Quản lý báo cáo tuần
                </h1>

                <!-- Bộ lọc -->
                <div class="filter-container">
                    <form method="get" class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center" style="gap: 20px;">
                            <!-- Toggle chế độ xem -->
                            <div class="view-mode-toggle">
                                <button type="button" class="mode-btn <?= $selectedSinhVien == 0 ? 'active' : '' ?>" onclick="viewAllStudents()">
                                    <i class="fa fa-th-large"></i> Tổng quan
                                </button>
                                <button type="button" class="mode-btn <?= $selectedSinhVien > 0 ? 'active' : '' ?>" onclick="viewDetailMode()">
                                    <i class="fa fa-user"></i> Chi tiết
                                </button>
                            </div>
                            
                            <?php if ($selectedSinhVien > 0): ?>
                            <div>
                                <label for="sinh_vien" style="margin-bottom: 5px; font-weight: 600;">Sinh viên:</label>
                                <select name="sinh_vien" id="sinh_vien" class="form-control" style="min-width: 250px;" onchange="this.form.submit()">
                                    <option value="0">-- Chọn sinh viên --</option>
                                    <?php foreach ($sinhVienList as $sv): ?>
                                        <option value="<?= $sv['ID_TaiKhoan'] ?>" <?= $selectedSinhVien == $sv['ID_TaiKhoan'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($sv['MSSV'] . ' - ' . $sv['Ten'] . ' (' . $sv['TenDot'] . ')') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>
                            
                            <div>
                                <label for="week" style="margin-bottom: 5px; font-weight: 600;">Tuần:</label>
                                <input type="week" name="week" id="week" class="form-control" 
                                       value="<?= date('Y-\WW', strtotime($selectedWeek)) ?>" 
                                       onchange="this.form.submit()">
                            </div>
                        </div>
                        
                        <input type="hidden" name="date" value="<?= htmlspecialchars($selectedDate) ?>">
                    </form>
                </div>

                <!-- Search và Filter cho nhiều sinh viên -->
                <?php if ($selectedSinhVien == 0 && count($sinhVienList) > 6): ?>
                <div class="filter-container" style="margin-bottom: 15px;">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center" style="gap: 15px;">
                            <div>
                                <input type="text" id="student-search" class="form-control" placeholder="Tìm kiếm sinh viên..." style="min-width: 250px;">
                            </div>
                            <div>
                                <select id="status-filter" class="form-control">
                                    <option value="">Tất cả trạng thái</option>
                                    <option value="excellent">Xuất sắc</option>
                                    <option value="good">Tốt</option>
                                    <option value="average">Trung bình</option>
                                    <option value="poor">Kém</option>
                                </select>
                            </div>
                        </div>
                        <div class="text-muted">
                            <span id="student-count"><?= count($sinhVienList) ?></span> sinh viên
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($selectedSinhVien == 0): ?>
                    <!-- Trang tổng quan tất cả sinh viên -->
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <i class="fa fa-users"></i> 
                            Tổng quan báo cáo tuần - Tuần <?= date('d/m', strtotime($selectedWeek)) ?> đến <?= date('d/m/Y', strtotime($selectedWeek . ' +6 days')) ?>
                        </div>
                        <div class="panel-body">
                            <?php if (empty($thongKeTongQuan)): ?>
                                <div class="empty-state">
                                    <i class="fa fa-users"></i>
                                    <h4>Không có sinh viên nào</h4>
                                    <p>Hiện tại bạn chưa hướng dẫn sinh viên nào.</p>
                                </div>
                            <?php else: ?>
                                <div class="student-grid">
                                    <?php foreach ($thongKeTongQuan as $idSV => $data): ?>
                                        <?php 
                                        $sv = $data['thong_tin'];
                                        $stats = $data;
                                        
                                        // Xác định trạng thái
                                        $status = 'poor';
                                        $statusText = 'Kém';
                                        if ($stats['ty_le_hoan_thanh'] >= 90) {
                                            $status = 'excellent';
                                            $statusText = 'Xuất sắc';
                                        } elseif ($stats['ty_le_hoan_thanh'] >= 70) {
                                            $status = 'good';
                                            $statusText = 'Tốt';
                                        } elseif ($stats['ty_le_hoan_thanh'] >= 50) {
                                            $status = 'average';
                                            $statusText = 'Trung bình';
                                        }
                                        
                                        // Màu progress circle
                                        $progressClass = 'progress-0-30';
                                        if ($stats['tien_do_trung_binh'] >= 100) $progressClass = 'progress-100';
                                        elseif ($stats['tien_do_trung_binh'] >= 71) $progressClass = 'progress-71-99';
                                        elseif ($stats['tien_do_trung_binh'] >= 31) $progressClass = 'progress-31-70';
                                        ?>
                                        <div class="student-panel" onclick="viewStudentDetail(<?= $idSV ?>)">
                                            <div class="status-badge status-<?= $status ?>"><?= $statusText ?></div>
                                            
                                            <div class="student-header">
                                                <div class="student-avatar">
                                                    <?= strtoupper(substr($sv['Ten'], 0, 1)) ?>
                                                </div>
                                                <div class="student-info">
                                                    <h4><?= htmlspecialchars($sv['Ten']) ?></h4>
                                                    <p><?= htmlspecialchars($sv['MSSV']) ?> - <?= htmlspecialchars($sv['TenDot']) ?></p>
                                                </div>
                                            </div>
                                            
                                            <div class="student-stats">
                                                <div class="mini-stat">
                                                    <div class="mini-stat-value"><?= $stats['tong_cong_viec'] ?></div>
                                                    <div class="mini-stat-label">Công việc</div>
                                                </div>
                                                <div class="mini-stat">
                                                    <div class="mini-stat-value"><?= $stats['hoan_thanh'] ?></div>
                                                    <div class="mini-stat-label">Hoàn thành</div>
                                                </div>
                                                <div class="mini-stat">
                                                    <div class="mini-stat-value"><?= $stats['so_ngay_lam_viec'] ?>/7</div>
                                                    <div class="mini-stat-label">Ngày làm việc</div>
                                                </div>
                                                <div class="mini-stat">
                                                    <div class="mini-stat-value"><?= $stats['ty_le_hoan_thanh'] ?>%</div>
                                                    <div class="mini-stat-label">Tỷ lệ hoàn thành</div>
                                                </div>
                                            </div>
                                            
                                            <div class="student-progress-summary">
                                                <div class="progress-summary-text">
                                                    <div class="progress-summary-label">Tiến độ trung bình</div>
                                                    <div class="progress-summary-value"><?= $stats['tien_do_trung_binh'] ?>% - 
                                                        <?php if ($stats['tien_do_trung_binh'] >= 90): ?>
                                                            Rất tốt
                                                        <?php elseif ($stats['tien_do_trung_binh'] >= 70): ?>
                                                            Tốt
                                                        <?php elseif ($stats['tien_do_trung_binh'] >= 50): ?>
                                                            Trung bình
                                                        <?php else: ?>
                                                            Cần cải thiện
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="progress-summary-circle <?= $progressClass ?>">
                                                    <?= round($stats['tien_do_trung_binh']) ?>%
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <!-- Thống kê tổng -->
                                <div class="stats-container">
                                    <?php 
                                    $tongCongViec = array_sum(array_column($thongKeTongQuan, 'tong_cong_viec'));
                                    $tongHoanThanh = array_sum(array_column($thongKeTongQuan, 'hoan_thanh'));
                                    $tienDoTB = $tongCongViec > 0 ? round(array_sum(array_column($thongKeTongQuan, 'tien_do_trung_binh')) / count($thongKeTongQuan), 1) : 0;
                                    $tyLeHoanThanh = $tongCongViec > 0 ? round(($tongHoanThanh / $tongCongViec) * 100, 1) : 0;
                                    ?>
                                    <div class="stat-card">
                                        <div class="stat-value"><?= count($sinhVienList) ?></div>
                                        <div class="stat-label">Tổng sinh viên</div>
                                    </div>
                                    <div class="stat-card">
                                        <div class="stat-value"><?= $tongCongViec ?></div>
                                        <div class="stat-label">Tổng công việc</div>
                                    </div>
                                    <div class="stat-card">
                                        <div class="stat-value"><?= $tienDoTB ?>%</div>
                                        <div class="stat-label">Tiến độ TB</div>
                                    </div>
                                    <div class="stat-card">
                                        <div class="stat-value"><?= $tyLeHoanThanh ?>%</div>
                                        <div class="stat-label">Tỷ lệ hoàn thành</div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                <?php elseif ($selectedSinhVien > 0): ?>
                    <!-- Chi tiết sinh viên được chọn -->
                    <!-- Thống kê tuần -->
                    <div class="stats-container">
                        <div class="stat-card">
                            <div class="stat-value"><?= $thongKe['tong_cong_viec'] ?></div>
                            <div class="stat-label">Tổng công việc</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?= $thongKe['hoan_thanh'] ?></div>
                            <div class="stat-label">Đã hoàn thành</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?= $thongKe['tien_do_trung_binh'] ?>%</div>
                            <div class="stat-label">Tiến độ trung bình</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value">
                                <?= $thongKe['tong_cong_viec'] > 0 ? round(($thongKe['hoan_thanh'] / $thongKe['tong_cong_viec']) * 100, 1) : 0 ?>%
                            </div>
                            <div class="stat-label">Tỷ lệ hoàn thành</div>
                        </div>
                    </div>

                    <!-- Lịch tuần -->
                    <div class="week-calendar">
                        <div class="week-header">
                            <div class="day-header">Thứ 2</div>
                            <div class="day-header">Thứ 3</div>
                            <div class="day-header">Thứ 4</div>
                            <div class="day-header">Thứ 5</div>
                            <div class="day-header">Thứ 6</div>
                            <div class="day-header">Thứ 7</div>
                            <div class="day-header">Chủ nhật</div>
                        </div>
                        <div class="week-body">
                            <?php foreach ($daysOfWeek as $day): ?>
                                <?php 
                                $isToday = $day === date('Y-m-d');
                                $isSelected = $day === $selectedDate;
                                $dayData = $baoCaoTheoNgay[$day] ?? ['so_cong_viec' => 0, 'hoan_thanh' => 0, 'tien_do_trung_binh' => 0];
                                ?>
                                <div class="day-cell <?= $isToday ? 'today' : '' ?> <?= $isSelected ? 'selected' : '' ?>" 
                                     onclick="selectDate('<?= $day ?>')">
                                    <div class="day-date"><?= date('d/m', strtotime($day)) ?></div>
                                    
                                    <?php if ($dayData['so_cong_viec'] > 0): ?>
                                        <div class="day-stats">
                                            <div><?= $dayData['so_cong_viec'] ?> công việc</div>
                                            <div><?= $dayData['hoan_thanh'] ?>/<?= $dayData['so_cong_viec'] ?> hoàn thành</div>
                                        </div>
                                        <div class="progress-indicator">
                                            <div class="progress-bar" style="width: <?= $dayData['tien_do_trung_binh'] ?>%"></div>
                                        </div>
                                    <?php else: ?>
                                        <div class="day-stats" style="color: #bdc1c6;">Không có công việc</div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Danh sách công việc của ngày được chọn -->
                    <div class="task-list-container">
                        <div class="task-list-header">
                            <i class="fa fa-list"></i> 
                            Công việc ngày <?= date('d/m/Y', strtotime($selectedDate)) ?>
                            <?php if ($selectedDate === date('Y-m-d')): ?>
                                <span style="color: #1a73e8; font-weight: 600;">(Hôm nay)</span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (empty($baoCaoTuan)): ?>
                            <div class="empty-state">
                                <i class="fa fa-calendar-times-o"></i>
                                <h4>Không có công việc nào</h4>
                                <p>Sinh viên chưa báo cáo công việc nào cho ngày này.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($baoCaoTuan as $task): ?>
                                <div class="task-item">
                                    <div class="task-content">
                                        <div class="task-name"><?= htmlspecialchars($task['TenCongViec']) ?></div>
                                        <?php if (!empty($task['MoTa'])): ?>
                                            <div class="task-desc"><?= htmlspecialchars($task['MoTa']) ?></div>
                                        <?php endif; ?>
                                        <div class="task-time">
                                            <i class="fa fa-clock-o"></i> 
                                            Cập nhật: <?= date('H:i d/m/Y', strtotime($task['NgayCapNhat'] ?? $task['Ngay'])) ?>
                                        </div>
                                    </div>
                                    <div class="task-progress">
                                        <?php 
                                        $progress = (int)$task['TienDo'];
                                        $progressClass = 'progress-0-30';
                                        if ($progress >= 100) $progressClass = 'progress-100';
                                        elseif ($progress >= 71) $progressClass = 'progress-71-99';
                                        elseif ($progress >= 31) $progressClass = 'progress-31-70';
                                        ?>
                                        <div class="progress-circle <?= $progressClass ?>">
                                            <?= $progress ?>%
                                        </div>
                                        <div style="font-size: 12px; color: #70757a;">
                                            <?php if ($progress >= 100): ?>
                                                Hoàn thành
                                            <?php elseif ($progress >= 71): ?>
                                                Gần xong
                                            <?php elseif ($progress >= 31): ?>
                                                Đang làm
                                            <?php else: ?>
                                                Mới bắt đầu
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                <?php else: ?>
                    <!-- Trạng thái chưa chọn sinh viên -->
                    <div class="panel panel-default">
                        <div class="panel-body">
                            <div class="empty-state">
                                <i class="fa fa-info-circle"></i>
                                <h3>Chế độ xem</h3>
                                <p>Chọn "Tổng quan" để xem tất cả sinh viên hoặc "Chi tiết" để xem chi tiết từng sinh viên.</p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/footer.php"; ?>
    
    <script>
        function selectDate(date) {
            // Cập nhật URL với ngày được chọn
            const url = new URL(window.location);
            url.searchParams.set('date', date);
            window.location.href = url.toString();
        }
        
        function viewAllStudents() {
            // Chuyển về chế độ tổng quan
            const url = new URL(window.location);
            url.searchParams.delete('sinh_vien');
            url.searchParams.delete('date');
            window.location.href = url.toString();
        }
        
        function viewDetailMode() {
            // Chuyển về chế độ chi tiết - chọn sinh viên đầu tiên nếu có
            const sinhVienSelect = document.getElementById('sinh_vien');
            if (sinhVienSelect && sinhVienSelect.options.length > 1) {
                const url = new URL(window.location);
                url.searchParams.set('sinh_vien', sinhVienSelect.options[1].value);
                window.location.href = url.toString();
            }
        }
        
        function viewStudentDetail(studentId) {
            // Chuyển đến trang chi tiết sinh viên
            const url = new URL(window.location);
            url.searchParams.set('sinh_vien', studentId);
            url.searchParams.set('date', '<?= date('Y-m-d') ?>');
            window.location.href = url.toString();
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            // Thêm hiệu ứng hover cho student panels
            document.querySelectorAll('.student-panel').forEach(panel => {
                panel.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-4px)';
                });
                
                panel.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(-2px)';
                });
            });
            
            // Search và Filter functionality
            const searchInput = document.getElementById('student-search');
            const statusFilter = document.getElementById('status-filter');
            const studentPanels = document.querySelectorAll('.student-panel');
            const studentCount = document.getElementById('student-count');
            
            function filterStudents() {
                const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
                const statusTerm = statusFilter ? statusFilter.value : '';
                let visibleCount = 0;
                
                studentPanels.forEach(panel => {
                    const studentName = panel.querySelector('.student-info h4').textContent.toLowerCase();
                    const studentMSSV = panel.querySelector('.student-info p').textContent.toLowerCase();
                    const studentStatus = panel.querySelector('.status-badge').className.includes('status-' + statusTerm) || statusTerm === '';
                    
                    const matchesSearch = studentName.includes(searchTerm) || studentMSSV.includes(searchTerm);
                    
                    if (matchesSearch && studentStatus) {
                        panel.classList.remove('hidden');
                        visibleCount++;
                    } else {
                        panel.classList.add('hidden');
                    }
                });
                
                // Update count
                if (studentCount) {
                    studentCount.textContent = visibleCount;
                }
                
                // Show no results message
                const studentGrid = document.querySelector('.student-grid');
                let noResults = studentGrid.querySelector('.no-results');
                
                if (visibleCount === 0) {
                    if (!noResults) {
                        noResults = document.createElement('div');
                        noResults.className = 'no-results';
                        noResults.innerHTML = '<i class="fa fa-search"></i><h4>Không tìm thấy sinh viên</h4><p>Thử thay đổi từ khóa tìm kiếm hoặc bộ lọc.</p>';
                        studentGrid.appendChild(noResults);
                    }
                } else {
                    if (noResults) {
                        noResults.remove();
                    }
                }
            }
            
            // Event listeners
            if (searchInput) {
                searchInput.addEventListener('input', filterStudents);
            }
            if (statusFilter) {
                statusFilter.addEventListener('change', filterStudents);
            }
        });
    </script>
</body>
</html>
