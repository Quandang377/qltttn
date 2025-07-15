<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/template/config.php';


$idSinhVien = isset($_SESSION['user']['ID_TaiKhoan']) ? $_SESSION['user']['ID_TaiKhoan'] : 0;

// Lấy ID_Dot của sinh viên
$dotSql = "SELECT ID_Dot FROM sinhvien WHERE ID_TaiKhoan = :id";
$dotStmt = $conn->prepare($dotSql);
$dotStmt->bindParam(':id', $idSinhVien, PDO::PARAM_INT);
$dotStmt->execute();
$dotRow = $dotStmt->fetch(PDO::FETCH_ASSOC);
$idDot = $dotRow ? $dotRow['ID_Dot'] : 0;

// Lấy thông tin đợt thực tập và kiểm tra trạng thái
$dotThucTapInfo = null;
$isDotActive = false;
if ($idDot > 0) {
    $dotInfoSql = "SELECT TenDot, ThoiGianBatDau, ThoiGianKetThuc, TrangThai FROM DotThucTap WHERE ID = :id";
    $dotInfoStmt = $conn->prepare($dotInfoSql);
    $dotInfoStmt->bindParam(':id', $idDot, PDO::PARAM_INT);
    $dotInfoStmt->execute();
    $dotThucTapInfo = $dotInfoStmt->fetch(PDO::FETCH_ASSOC);
    $isDotActive = $dotThucTapInfo && ($dotThucTapInfo['TrangThai'] == 2 || $dotThucTapInfo['TrangThai'] >= 4);
}

// Fetch tasks for the entire month for calendar display
$calendarTasks = [];
if ($idSinhVien && $isDotActive) {
    // Get the first and last day of the month based on calendarDate
    $calendarMonth = isset($_GET['month']) ? intval($_GET['month']) : date('n');
    $calendarYear = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
    
    $firstDay = date('Y-m-d', strtotime("$calendarYear-$calendarMonth-01"));
    $lastDay = date('Y-m-t', strtotime("$calendarYear-$calendarMonth-01"));
    
    // Query to get task counts and details for each day
    $monthTasksStmt = $conn->prepare("SELECT cb.Ngay, 
                                      COUNT(*) as task_count, 
                                      SUM(CASE WHEN TienDo = 100 THEN 1 ELSE 0 END) as completed_count,
                                      GROUP_CONCAT(SUBSTRING(TenCongViec, 1, 15) SEPARATOR '||') as task_names,
                                      GROUP_CONCAT(TienDo SEPARATOR '||') as task_progress
                                      FROM congviec_baocao cb
                                      WHERE IDSV = :idsv AND Ngay BETWEEN :firstDay AND :lastDay 
                                      GROUP BY Ngay");
    $monthTasksStmt->bindParam(':idsv', $idSinhVien, PDO::PARAM_INT);
    $monthTasksStmt->bindParam(':firstDay', $firstDay);
    $monthTasksStmt->bindParam(':lastDay', $lastDay);
    $monthTasksStmt->execute();
    
    while ($row = $monthTasksStmt->fetch(PDO::FETCH_ASSOC)) {
        $taskNames = explode('||', $row['task_names']);
        $taskProgress = explode('||', $row['task_progress']);
        
        $tasks = [];
        for ($i = 0; $i < count($taskNames); $i++) {
            $tasks[] = [
                'name' => $taskNames[$i],
                'progress' => isset($taskProgress[$i]) ? (int)$taskProgress[$i] : 0
            ];
        }
        
        $calendarTasks[$row['Ngay']] = [
            'total' => $row['task_count'],
            'completed' => $row['completed_count'],
            'tasks' => $tasks
        ];
    }
}

// Xử lý thêm công việc bằng PHP thuần
$addTaskMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['add_task_submit']) || (isset($_POST['edit_task_action']) && $_POST['edit_task_action'] === 'edit'))) {
    if (!$isDotActive) {
        $addTaskMsg = '<div class="alert alert-danger">Đợt của bạn đã kết thúc hoặc chưa bắt đầu. Không thể thêm công việc!</div>';
    } else {
    // Lấy dữ liệu từ form
    $taskName = trim($_POST['task_name'] ?? '');
    $taskDesc = trim($_POST['task_description'] ?? '');
    $taskStart = $_POST['task_start_time'] ?? '';
    $taskEnd = $_POST['task_end_time'] ?? '';
    $taskProgress = intval($_POST['task_progress'] ?? 0);
    $taskDate = $_POST['task_date'] ?? '';

    // Chỉ cho phép thêm cho ngày hiện tại
    $today = date('Y-m-d');
    if ($taskDate !== $today) {
        $addTaskMsg = '<div class="alert alert-danger">Chỉ được thêm công việc cho ngày hôm nay!</div>';
    } elseif (!$taskName) {
        $addTaskMsg = '<div class="alert alert-danger">Vui lòng nhập đầy đủ thông tin!</div>';
    } else {
        $isEdit = isset($_POST['edit_task_action']) && $_POST['edit_task_action'] === 'edit';
        if ($isEdit) {
            $editTaskId = intval($_POST['edit_task_id']);
            if ($editTaskId > 0) {
                $sql = "UPDATE congviec_baocao SET TenCongViec = :ten, MoTa = :mota, TienDo = :tiendo WHERE ID = :id AND IDSV = :idsv";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':ten', $taskName);
                $stmt->bindParam(':mota', $taskDesc);
                $stmt->bindParam(':tiendo', $taskProgress, PDO::PARAM_INT);
                $stmt->bindParam(':id', $editTaskId, PDO::PARAM_INT);
                $stmt->bindParam(':idsv', $idSinhVien, PDO::PARAM_INT);
                if ($stmt->execute()) {
                    $addTaskMsg = '<div class="alert alert-success">Cập nhật công việc thành công!</div>';
                    echo "<script>location.href=location.href;</script>";
                    exit;
                } else {
                    $addTaskMsg = '<div class="alert alert-danger">Không thể cập nhật công việc!</div>';
                }
            }
        } else {
            $sql = "INSERT INTO congviec_baocao (IDSV, Ngay, TenCongViec, MoTa, TienDo, ID_Dot)
                    VALUES (:idsv, :ngay, :ten, :mota, :tiendo, :idDot)";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':idsv', $idSinhVien, PDO::PARAM_INT);
            $stmt->bindParam(':ngay', $taskDate);
            $stmt->bindParam(':ten', $taskName);
            $stmt->bindParam(':mota', $taskDesc);
            $stmt->bindParam(':tiendo', $taskProgress, PDO::PARAM_INT);
            $stmt->bindParam(':idDot', $idDot, PDO::PARAM_INT);

            if ($stmt->execute()) {
                $addTaskMsg = '<div class="alert alert-success">Thêm công việc thành công!</div>';
                // Reload lại trang để cập nhật danh sách công việc
                echo "<script>location.href=location.href;</script>";
                exit;
            } else {
                $addTaskMsg = '<div class="alert alert-danger">Không thể lưu công việc!</div>';
            }
        }
    }
}
}

$selectedDate = $_GET['date'] ?? date('Y-m-d');
$taskList = [];

// Phân trang cho task list
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$tasksPerPage = 3;
$totalTasks = 0;
$totalPages = 1;

if ($idSinhVien && $selectedDate && $isDotActive) {
    // Đếm tổng số task
    $stmtCount = $conn->prepare("SELECT COUNT(*) FROM congviec_baocao WHERE IDSV = :idsv AND Ngay = :ngay");
    $stmtCount->bindParam(':idsv', $idSinhVien, PDO::PARAM_INT);
    $stmtCount->bindParam(':ngay', $selectedDate);
    $stmtCount->execute();
    $totalTasks = (int)$stmtCount->fetchColumn();
    $totalPages = max(1, ceil($totalTasks / $tasksPerPage));

    // Lấy task theo trang
    $offset = ($page - 1) * $tasksPerPage;
    $stmt = $conn->prepare("SELECT * FROM congviec_baocao WHERE IDSV = :idsv AND Ngay = :ngay ORDER BY ID DESC LIMIT :limit OFFSET :offset");
    $stmt->bindParam(':idsv', $idSinhVien, PDO::PARAM_INT);
    $stmt->bindParam(':ngay', $selectedDate);
    $stmt->bindValue(':limit', $tasksPerPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $taskList = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Xử lý cập nhật tiến độ hoặc sửa task
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_task_id'])) {
    if (!$isDotActive) {
        // Không làm gì cả nếu đợt không active
    } else {
    $taskId = intval($_POST['update_task_id']);
    $action = $_POST['update_task_action'] ?? '';
    if ($taskId > 0) {
        if ($action === 'progress') {
            // Cập nhật tiến độ
            $progress = intval($_POST['update_task_progress'] ?? 0);
            $stmt = $conn->prepare("UPDATE congviec_baocao SET TienDo = :progress WHERE ID = :id AND IDSV = :idsv");
            $stmt->bindParam(':progress', $progress, PDO::PARAM_INT);
            $stmt->bindParam(':id', $taskId, PDO::PARAM_INT);
            $stmt->bindParam(':idsv', $idSinhVien, PDO::PARAM_INT);
            $stmt->execute();
        } elseif ($action === 'edit') {
            // Sửa tên và mô tả
            $name = trim($_POST['update_task_name'] ?? '');
            $desc = trim($_POST['update_task_desc'] ?? '');
            $progress = intval($_POST['update_task_progress'] ?? 0);
            $stmt = $conn->prepare("UPDATE congviec_baocao SET TenCongViec = :name, MoTa = :desc, TienDo = :progress WHERE ID = :id AND IDSV = :idsv");
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':desc', $desc);
            $stmt->bindParam(':progress', $progress, PDO::PARAM_INT);
            $stmt->bindParam(':id', $taskId, PDO::PARAM_INT);
            $stmt->bindParam(':idsv', $idSinhVien, PDO::PARAM_INT);
            $stmt->execute();
        }
        // Reload lại trang để cập nhật
        echo "<script>location.href=location.href;</script>";
        exit;
    }
}
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Báo cáo tuần <?php echo $idSinhVien?></title>
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php"; ?>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #e3f0ff 0%, #f8fafc 100%);
            font-family: 'Segoe UI', 'Roboto', Arial, sans-serif;
        }
        #page-wrapper {
            padding: 20px;
            min-height: 100vh;
        }
        .calendar-container {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }
        .calendar-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 20px;
            background: #fff;
            border-bottom: 1px solid #e0e0e0;
        }
        .calendar-title {
            font-size: 18px;
            font-weight: 500;
            color: #3c4043;
        }
        .current-month-year {
            font-size: 16px;
            color: #5f6368;
        }
        .controls-panel {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 15px 20px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .controls-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .controls-right {
            display: flex;
            align-items: center;
        }
        .date-display {
            font-size: 16px;
            font-weight: 500;
            color: #3c4043;
            margin-right: 20px;
        }
        .nav-controls {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .calendar-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .btn-today {
            background-color: transparent;
            border: 1px solid #dadce0;
            border-radius: 4px;
            color: #1a73e8;
            font-size: 14px;
            font-weight: 500;
            height: 36px;
            padding: 0 16px;
            cursor: pointer;
        }
        .btn-nav {
            border: none;
            background: transparent;
            color: #5f6368;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .btn-nav:hover {
            background-color: #f1f3f4;
        }
        .week-view-header {
            display: grid;
            grid-template-columns: 70px repeat(7, 1fr);
            background-color: #f8f9fa;
            border-bottom: 1px solid #e0e0e0;
        }
        .day-header {
            padding: 8px;
            text-align: center;
            border-right: 1px solid #e0e0e0;
        }
        .day-name {
            font-size: 10px;
            font-weight: 500;
            text-transform: uppercase;
            color: #70757a;
            margin-bottom: 2px;
        }
        .day-date {
            font-size: 22px;
            font-weight: 400;
            color: #3c4043;
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            border-radius: 50%;
            cursor: pointer;
        }
        .day-date:hover {
            background-color: #e8eaed;
        }
        .current-day .day-date {
            background-color: #1a73e8;
            color: #fff;
        }
        .week-view-body {
            display: grid;
            grid-template-columns: 70px repeat(7, 1fr);
            height: 600px;
            overflow-y: auto;
        }
        .time-column {
            display: flex;
            flex-direction: column;
            border-right: 1px solid #e0e0e0;
        }
        .time-slot {
            height: 48px;
            padding: 0 8px;
            text-align: right;
            color: #70757a;
            font-size: 10px;
            border-bottom: 1px solid #e0e0e0;
            position: relative;
        }
        .time-label {
            position: absolute;
            top: -7px;
            right: 8px;
            font-size: 10px;
        }
        .day-column {
            display: flex;
            flex-direction: column;
            border-right: 1px solid #e0e0e0;
        }
        .day-slot {
            height: 48px;
            border-bottom: 1px solid #e0e0e0;
            padding: 2px;
            position: relative;
        }
        .task-item {
            position: absolute;
            left: 2px;
            right: 2px;
            padding: 6px 8px;
            font-size: 12px;
            border-radius: 4px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            background-color: #1a73e8;
            color: white;
            cursor: pointer;
        }
        .task-item.selected {
            box-shadow: 0 0 0 2px #fff, 0 0 0 4px #1a73e8;
            z-index: 100;
        }
        .summary-container {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        .task-list-container {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border: 1px solid #e0e0e0;
            margin-bottom: 20px;
            max-height: 400px;
            overflow-y: auto;
        }
        .task-list-header {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
            font-weight: 500;
            position: sticky;
            top: 0;
            background: #fff;
            z-index: 10;
        }
        .task-list {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        .task-list-item {
            padding: 12px 15px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: background-color 0.2s;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .task-list-item:last-child {
            border-bottom: none;
        }
        .task-list-item:hover {
            background-color: #f8f9fa;
        }
        .task-list-item.selected {
            background-color: #e8f0fe;
        }
        .task-list-name {
            font-weight: 500;
            margin-bottom: 4px;
        }
        .task-list-time {
            font-size: 12px;
            color: #70757a;
        }
        .task-list-progress {
            display: inline-block;
            width: 40px;
            height: 40px;
            line-height: 40px;
            text-align: center;
            border-radius: 50%;
            color: #fff;
            font-weight: 500;
            font-size: 12px;
        }
        .progress-panel {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
            display: none;
            border: 1px solid #e0e0e0;
        }
        .progress-panel.active {
            display: block;
        }
        .progress-panel-header {
            border-bottom: 1px solid #e0e0e0;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .progress-panel-title {
            font-size: 18px;
            font-weight: 500;
            color: #3c4043;
            margin-bottom: 5px;
        }
        .progress-panel-time {
            font-size: 14px;
            color: #70757a;
        }
       
        .progress-panel-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        .progress-panel-value {
            text-align: center;
            font-size: 32px;
            font-weight: 500;
            color: #1a73e8;
            margin: 10px 0;
        }
        .progress-panel-label {
            margin-bottom: 10px;
            font-size: 14px;
            color: #70757a;
            font-weight: 500;
        }
        .week-stats {
            padding: 5px 0;
        }
        .week-date {
            font-size: 14px;
            color: #70757a;
            text-align: center;
            font-weight: 500;
            padding-bottom: 8px;
            border-bottom: 1px dashed #e0e0e0;
        }
        .task-stats-item {
            display: flex;
            flex-direction: column;
            margin: 12px 0;
            padding: 10px;
            border-radius: 8px;
            background-color: #f8f9fa;
            border-left: 3px solid #1a73e8;
        }
        .task-stats-name {
            font-weight: 500;
            font-size: 14px;
            margin-bottom: 6px;
            color: #3c4043;
        }
        .task-stats-progress {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .task-stats-percentage {
            font-weight: 600;
            font-size: 16px;
            color: #1a73e8;
        }
        .task-stats-duration {
            font-size: 12px;
            color: #70757a;
            margin-top: 4px;
        }
        .task-stats-item[data-progress="low"] {
            border-left-color: #e53935;
        }
        .task-stats-item[data-progress="medium"] {
            border-left-color: #fb8c00;
        }
        .task-stats-item[data-progress="high"] {
            border-left-color: #43a047;
        }
        /* Add these styles to your existing CSS */
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 2px;
            background-color: #f1f3f4;
            padding: 2px;
            border-radius: 8px;
        }
        
        .calendar-header {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            text-align: center;
            font-weight: 500;
            margin-bottom: 5px;
        }
        
        .weekday-header {
            padding: 10px;
            color: #70757a;
            font-size: 12px;
        }
        
        .date-cell {
            background-color: white;
            border-radius: 4px;
            min-height: 100px;
            padding: 5px;
            position: relative;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .date-cell:hover {
            background-color: #f8f9fa;
        }
        
        .date-cell.selected {
            background-color: #e8f0fe;
            border: 2px solid #1a73e8;
        }
        
        .date-cell.today {
            background-color: #e8f0fe;
        }
        
        .date-cell.other-month {
            background-color: #f8f9fa;
            color: #bdc1c6;
        }
        
        .date-number {
            font-size: 14px;
            font-weight: 500;
            position: absolute;
            top: 5px;
            right: 5px;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
        
        .today .date-number {
            background-color: #1a73e8;
            color: white;
        }
        
        .task-indicator {
            height: 4px;
            margin: 2px 0;
            border-radius: 2px;
            background-color: #1a73e8;
        }
        
        .task-count {
            font-size: 12px;
            color: #70757a;
            margin-top: 24px;
            padding-left: 5px;
        }
        
        .selected-date-container {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .selected-date-header {
            font-size: 16px;
            font-weight: 500;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e0e0e0;
        }
        /* Add to the existing <style> section */
.task-badges {
    margin-top: 28px;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 3px;
}

.task-badge {
    width: 90%;
    padding: 2px 4px;
    border-radius: 3px;
    font-size: 10px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    background-color: #e8f0fe;
    color: #1a73e8;
    border-left: 3px solid #1a73e8;
}

.task-badge.completed {
    background-color: #e6f4ea;
    color: #1e8e3e;
    border-left: 3px solid #1e8e3e;
}

.task-badge-summary {
    margin-top: 2px;
    font-size: 11px;
    padding: 3px 6px;
    border-radius: 4px;
    background-color: #e8f0fe;
    color: #1a73e8;
    text-align: center;
    width: 90%;
    border: 1px solid #c6dafc;
}

.has-tasks {
    position: relative;
}

.has-tasks::after {
    content: '';
    position: absolute;
    bottom: 5px;
    left: 5px;
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background-color: #1a73e8;
}

/* Add to your existing CSS */
.modal-task-container {
    max-height: 300px;
    margin-bottom: 0;
    box-shadow: none;
    border: 1px solid #e0e0e0;
}

#modal-progress-panel {
    border: 1px solid #e0e0e0;
    display: none;
}

#modal-progress-panel.active {
    display: block;
}

.task-badge, .task-badge-summary {
    cursor: pointer;
}

/* Make modal scrollable on small screens */
@media (max-height: 700px) {
    .modal-dialog {
        max-height: 90vh;
        overflow-y: auto;
    }
}

.task-badge.low-progress {
    background-color: #fdecea;
    color: #e53935;
    border-left: 3px solidrgb(73, 0, 243);
}
.task-badge.mid-progress {
    background-color: #fff4e5;
    color: #ff9800;
    border-left: 3px solid #ff9800;
}
.task-badge.completed {
    background-color: #e6f4ea;
    color: #1e8e3e;
    border-left: 3px solid #1e8e3e;
}
    </style>
</head>
<body>
    <div id="wrapper">
        <?php
            require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";
            require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/slidebar_Sinhvien.php";
            
            // Lấy ID sinh viên từ session hoặc gán tạm
            $idSinhVien = isset($_SESSION['userId']) ? $_SESSION['userId'] : 1;
        ?>
        <div id="page-wrapper">
            <div class="container-fluid">
                <h1 class="page-header text-primary">Báo Cáo Tuần</h1>
                <?php if (!empty($addTaskMsg)) echo $addTaskMsg; ?>
                
                <?php if (!$isDotActive): ?>
                    <div class="alert alert-warning">
                        <i class="fa fa-exclamation-triangle"></i> 
                        <strong>Thông báo:</strong> Đợt của bạn đã kết thúc hoặc chưa bắt đầu. Bạn không thể thêm hoặc chỉnh sửa công việc.
                        <?php if ($dotThucTapInfo): ?>
                            <br><small>Đợt thực tập: <?php echo htmlspecialchars($dotThucTapInfo['TenDot']); ?> 
                            (<?php echo date('d/m/Y', strtotime($dotThucTapInfo['ThoiGianBatDau'])); ?> - 
                            <?php echo date('d/m/Y', strtotime($dotThucTapInfo['ThoiGianKetThuc'])); ?>)</small>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <div class="row">
                    <!-- Left Column: Calendar -->
                    <div class="col-lg-9">
                        <!-- Controls Panel -->
                        <div class="controls-panel">
                            <div class="controls-left">
                                <div class="date-display">
                                    <span id="current-month-year">Tháng 7, 2025</span>
                                </div>
                                <div class="nav-controls">
                                    <button class="btn-today" id="today-btn">Hôm nay</button>
                                    <button class="btn-nav" id="prev-month-btn">
                                        <i class="material-icons">chevron_left</i>
                                    </button>
                                    <button class="btn-nav" id="next-month-btn">
                                        <i class="material-icons">chevron_right</i>
                                    </button>
                                </div>
                            </div>
                            <div class="controls-right">
                                <button class="btn btn-primary" id="add-task-btn">
                                    <i class="fa fa-plus"></i> Thêm công việc
                                </button>
                            </div>
                        </div>

                        <!-- Calendar Container - Calendar grid sẽ được sinh bằng JS -->
                        <div class="calendar-container">
                            <div class="calendar-header" id="calendar-header"></div>
                            <div class="calendar-grid" id="calendar-grid"></div>
                        </div>
                    </div>
                        
                        <div class="row">
                        <div style="display: flex; gap: 18px;flex-direction: column;">
                            <div class="task-list-container">
                            <div class="task-list-header">
                                Danh sách công việc của ngày
                            </div>
                            <ul class="task-list" id="task-list">
                                <?php if (empty($taskList)): ?>
                                    <li class="task-list-item text-muted">Không có công việc nào</li>
                                <?php else: ?>
                                    <?php foreach ($taskList as $task): ?>
                                        <li class="task-list-item"
                                            data-id="<?php echo (int)$task['ID']; ?>"
                                            data-name="<?php echo htmlspecialchars($task['TenCongViec']); ?>"
                                            data-desc="<?php echo htmlspecialchars($task['MoTa']); ?>"
                                            data-progress="<?php echo (int)$task['TienDo']; ?>">
                                            <div>
                                                <div class="task-list-name"><?php echo htmlspecialchars($task['TenCongViec']); ?></div>
                                                <div class="task-list-time"><?php echo htmlspecialchars($task['MoTa']); ?></div>
                                            </div>
                                            <div class="task-list-progress" style="background:#1a73e8"><?php echo (int)$task['TienDo']; ?>%</div>
                                        </li>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </ul>
                            <?php if ($totalPages > 1): ?>
                                <nav aria-label="Task pagination" style="text-align:center;margin:10px 0;">
                                    <ul class="pagination justify-content-center" style="margin-bottom:0;">
                                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                            <li class="page-item<?php if ($i == $page) echo ' active'; ?>">
                                                <a class="page-link task-page-link" href="#" data-page="<?php echo $i; ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endfor; ?>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        </div>

                        <div class="progress-panel" id="progress-panel">
                            <div class="progress-panel-header">
                                <div class="progress-panel-title" id="progress-task-name">Tên công việc</div>
                            </div>
                            <div class="progress-panel-description" id="progress-task-description">
                            </div>
                            <div>
                                <div class="progress-panel-value" id="progress-value-display">0%</div>
                                <div class="progress-panel-slider">
                                    <input type="range" min="0" max="100" value="0" class="slider" id="progress-slider">
                                </div>
                                <div class="progress-panel-buttons">
                                    <button class="btn btn-sm btn-outline-secondary" id="btn-edit-task">
                                        <i class="fa fa-edit"></i> Sửa
                                    </button>
                                    <button class="btn btn-sm btn-primary" id="btn-update-progress">
                                        <i class="fa fa-save"></i> Cập nhật
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Task Modal -->
    <div class="modal fade" id="task-modal" tabindex="-1" role="dialog" aria-labelledby="task-modal-label" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="post" id="task-form">
                    <div class="modal-header">
                        <h5 class="modal-title" id="task-modal-label">Thêm công việc mới</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="task_date" id="task-date-input" value="<?php echo date('Y-m-d'); ?>">
                        <input type="hidden" name="edit_task_id" id="edit-task-id" value="">
                        <input type="hidden" name="edit_task_action" id="edit-task-action" value="">
                        <div class="form-group">
                            <label for="task-name">Tên công việc</label>
                            <input type="text" class="form-control" id="task-name" name="task_name" required>
                        </div>
                        <div class="form-group">
                            <label for="task-description">Mô tả công việc</label>
                            <textarea class="form-control" id="task-description" name="task_description" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="task-progress">Tiến độ (%)</label>
                            <input type="range" class="form-control-range" id="task-progress" name="task_progress" min="0" max="100" value="0">
                            <div class="d-flex justify-content-between">
                                <span id="progress-value">0%</span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                        <button type="submit" class="btn btn-primary" name="add_task_submit">Lưu</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <form id="change-date-form" method="get" style="display:none;">
        <input type="hidden" name="date" id="change-date-input" value="<?php echo htmlspecialchars($selectedDate); ?>">
    </form>
    <form id="update-task-form" method="post" style="display:none;">
        <input type="hidden" name="update_task_id" id="update-task-id">
        <input type="hidden" name="update_task_name" id="update-task-name">
        <input type="hidden" name="update_task_desc" id="update-task-desc">
        <input type="hidden" name="update_task_progress" id="update-task-progress">
        <input type="hidden" name="update_task_action" id="update-task-action" value="">
    </form>
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/footer.php"; ?>
    
    <script>
        // Pass PHP tasks data to JavaScript
        const calendarTasksData = <?php echo json_encode($calendarTasks); ?>;
        const isDotActive = <?php echo $isDotActive ? 'true' : 'false'; ?>;
    </script>
    <script>
        // Update calendar headers with correct days and dates
        function updateCalendarHeader() {
            // Clear existing headers except the first one (time column)
            while (weekHeader.children.length > 1) {
                weekHeader.removeChild(weekHeader.lastChild);
            }
            
            // Update month/year display
            document.getElementById('current-month-year').textContent = 
                startOfWeek.toLocaleDateString('vi-VN', { month: 'long', year: 'numeric' });
            
            // Create day headers
            for (let i = 0; i < 7; i++) {
                const date = new Date(startOfWeek);
                date.setDate(startOfWeek.getDate() + i);
                
                const dayHeader = document.createElement('div');
                dayHeader.className = 'day-header';
                
                // Check if this is the current day
                const isToday = date.toDateString() === new Date().toDateString();
                if (isToday) {
                    dayHeader.classList.add('current-day');
                }
                
                const dayName = document.createElement('div');
                dayName.className = 'day-name';
                dayName.textContent = date.toLocaleDateString('vi-VN', { weekday: 'short' });
                
                const dayDate = document.createElement('div');
                dayDate.className = 'day-date';
                dayDate.textContent = date.getDate();
                
                dayHeader.appendChild(dayName);
                dayHeader.appendChild(dayDate);
                weekHeader.appendChild(dayHeader);
            }
            
            // Update stats week range
            const endOfWeek = new Date(startOfWeek);
            endOfWeek.setDate(startOfWeek.getDate() + 6);
            document.getElementById('stats-week-range').textContent = 
                `${startOfWeek.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit' })} - 
                ${endOfWeek.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' })}`;
        }
        
        // Initialize when document is ready
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize calendar
            
            // Event listeners
            document.getElementById('add-task-btn').addEventListener('click', () => openTaskModal(null, new Date()));
            
            // Update progress value display when slider changes
            document.getElementById('task-progress').addEventListener('input', function() {
                document.getElementById('progress-value').textContent = `${this.value}%`;
            });
            
            // Click outside to close progress panel
            document.addEventListener('click', function(e) {
                if (!e.target.closest('#progress-panel') && 
                    !e.target.closest('.task-list-item')) {
                    document.getElementById('progress-panel').classList.remove('active');
                    document.querySelectorAll('.task-list-item').forEach(item => {
                        item.classList.remove('selected');
                    });
                }
            });
        });
        
        // Biến lưu ngày hiện tại và ngày được chọn
        let calendarDate = new Date();
        let selectedDate = new Date();

        // Hiển thị lịch tháng
        function renderCalendar() {
            const month = calendarDate.getMonth();
            const year = calendarDate.getFullYear();

            // Cập nhật tiêu đề tháng/năm
            document.getElementById('current-month-year').textContent =
                `Tháng ${month + 1}, ${year}`;

            // Header thứ trong tuần
            const weekdays = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'];
            const header = document.getElementById('calendar-header');
            header.innerHTML = '';
            weekdays.forEach(day => {
                const div = document.createElement('div');
                div.className = 'weekday-header';
                div.textContent = day;
                header.appendChild(div);
            });

            // Tính toán ngày đầu/thứ đầu/tháng trước/sau
            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);
            const startDayOfWeek = firstDay.getDay();
            const daysInMonth = lastDay.getDate();

            // Ngày cuối tháng trước để lấp đầu tuần
            const prevMonthLastDay = new Date(year, month, 0).getDate();

            // Số ô cần hiển thị (6 hàng x 7 cột = 42)
            const totalCells = 42;

            const grid = document.getElementById('calendar-grid');
            grid.innerHTML = '';

            let dayNum = 1;
            let nextMonthDay = 1;

            for (let i = 0; i < totalCells; i++) {
                const cell = document.createElement('div');
                cell.className = 'date-cell';

                let cellDate;
                // Ô đầu tháng trước
                if (i < startDayOfWeek) {
                    cell.classList.add('other-month');
                    cell.textContent = prevMonthLastDay - startDayOfWeek + i + 1;
                    cellDate = new Date(year, month - 1, prevMonthLastDay - startDayOfWeek + i + 1);
                }
                // Ô trong tháng này
                else if (dayNum <= daysInMonth) {
                    const dateNumberSpan = document.createElement('span');
                    dateNumberSpan.className = 'date-number';
                    dateNumberSpan.textContent = dayNum;
                    cell.appendChild(dateNumberSpan);
                    
                    cellDate = new Date(year, month, dayNum);

                    // Format date for checking tasks
                    const formattedDate = `${year}-${String(month + 1).padStart(2, '0')}-${String(dayNum).padStart(2, '0')}`;
                    
                    // Check if there are tasks for this date and add badges
                    if (calendarTasksData && calendarTasksData[formattedDate]) {
                        const taskData = calendarTasksData[formattedDate];
                        const tasks = taskData.tasks || [];

                        cell.classList.add('has-tasks');

                        const badgesContainer = document.createElement('div');
                        badgesContainer.className = 'task-badges';

                        if (tasks.length <= 5) {
                            tasks.forEach(task => {
                                const badge = document.createElement('div');
                                let progressClass = 'task-badge';

                                if (task.progress >= 100) {
                                    progressClass += ' completed';
                                } else if (task.progress >= 50) {
                                    progressClass += ' mid-progress';
                                } else {
                                    progressClass += ' low-progress';
                                }

                                badge.className = progressClass;
                                badge.textContent = task.name;
                                badge.title = `${task.name} (${task.progress}%)`;
                                badgesContainer.appendChild(badge);
                            });
                        } else {
                            const firstFour = tasks.slice(0, 4);
                            firstFour.forEach(task => {
                                const badge = document.createElement('div');
                                let progressClass = 'task-badge';

                                if (task.progress >= 100) {
                                    progressClass += ' completed';
                                } else if (task.progress >= 50) {
                                    progressClass += ' mid-progress';
                                } else {
                                    progressClass += ' low-progress';
                                }

                                badge.className = progressClass;
                                badge.textContent = task.name;
                                badge.title = `${task.name} (${task.progress}%)`;
                                badgesContainer.appendChild(badge);
                            });
                            const remainingCount = tasks.length - 4;
                            const extraBadge = document.createElement('div');
                            extraBadge.className = 'task-badge';
                            extraBadge.textContent = `+${remainingCount} công việc khác`;
                            extraBadge.title = `Có ${remainingCount} công việc khác`;
                            badgesContainer.appendChild(extraBadge);
                        }
                        
                        cell.appendChild(badgesContainer);
                    }

                    // Đánh dấu hôm nay
                    const today = new Date();
                    if (
                        cellDate.getDate() === today.getDate() &&
                        cellDate.getMonth() === today.getMonth() &&
                        cellDate.getFullYear() === today.getFullYear()
                    ) {
                        cell.classList.add('today');
                    }
                    // Đánh dấu ngày đang chọn
                    if (
                        cellDate.getDate() === selectedDate.getDate() &&
                        cellDate.getMonth() === selectedDate.getMonth() &&
                        cellDate.getFullYear() === selectedDate.getFullYear()
                    ) {
                        cell.classList.add('selected');
                    }

                    // Sự kiện chọn ngày
                    cell.addEventListener('click', function() {
                        // Format date for form submission
                        const yyyy = cellDate.getFullYear();
                        const mm = String(cellDate.getMonth() + 1).padStart(2, '0');
                        const dd = String(cellDate.getDate()).padStart(2, '0');
                        document.getElementById('change-date-input').value = `${yyyy}-${mm}-${dd}`;
                        document.getElementById('change-date-form').submit();
                    });

                    dayNum++;
                }
                // Ô tháng sau
                else {
                    cell.classList.add('other-month');
                    cell.textContent = nextMonthDay++;
                    cellDate = new Date(year, month + 1, nextMonthDay - 1);
                }

                grid.appendChild(cell);
            }
            updateAddTaskButton();
        }

        document.addEventListener('DOMContentLoaded', function() {
            renderCalendar();

            document.getElementById('prev-month-btn').addEventListener('click', function() {
                calendarDate.setMonth(calendarDate.getMonth() - 1);
                renderCalendar();
                // Nếu ngày chọn không thuộc tháng mới, chọn ngày 1
                if (
                    selectedDate.getMonth() !== calendarDate.getMonth() ||
                    selectedDate.getFullYear() !== calendarDate.getFullYear()
                ) {
                    selectedDate = new Date(calendarDate.getFullYear(), calendarDate.getMonth(), 1);
                    updateSelectedDateHeader();
                }
            });

            document.getElementById('next-month-btn').addEventListener('click', function() {
                calendarDate.setMonth(calendarDate.getMonth() + 1);
                renderCalendar();
                if (
                    selectedDate.getMonth() !== calendarDate.getMonth() ||
                    selectedDate.getFullYear() !== calendarDate.getFullYear()
                ) {
                    selectedDate = new Date(calendarDate.getFullYear(), calendarDate.getMonth(), 1);
                    updateSelectedDateHeader();
                }
            });

            document.getElementById('today-btn').addEventListener('click', function() {
                calendarDate = new Date();
                selectedDate = new Date();
                renderCalendar();
                updateSelectedDateHeader();
            });

            document.getElementById('add-task-btn').addEventListener('click', function() {
                openTaskModal(null, selectedDate);
            });

        });

        // Hàm kiểm tra 2 ngày có cùng ngày/tháng/năm không
        function isToday(date) {
            const now = new Date();
            return date.getDate() === now.getDate() &&
                date.getMonth() === now.getMonth() &&
                date.getFullYear() === now.getFullYear();
        }

        // Cập nhật trạng thái nút "Thêm công việc"
        function updateAddTaskButton() {
            const btn = document.getElementById('add-task-btn');
            if (!isDotActive) {
                btn.disabled = true;
                btn.title = "Đợt của bạn đã kết thúc hoặc chưa bắt đầu";
                btn.style.opacity = "0.6";
            } else if (!isToday(selectedDate)) {
                btn.disabled = true;
                btn.title = "Chỉ được thêm công việc cho ngày hôm nay";
                btn.style.opacity = "0.6";
            } else {
                btn.disabled = false;
                btn.title = "";
                btn.style.opacity = "1";
            }
        }

        // Sửa lại hàm renderCalendar để gọi updateAddTaskButton khi chọn ngày
        function renderCalendar() {
            const month = calendarDate.getMonth();
            const year = calendarDate.getFullYear();

            // Cập nhật tiêu đề tháng/năm
            document.getElementById('current-month-year').textContent =
                `Tháng ${month + 1}, ${year}`;

            // Header thứ trong tuần
            const weekdays = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'];
            const header = document.getElementById('calendar-header');
            header.innerHTML = '';
            weekdays.forEach(day => {
                const div = document.createElement('div');
                div.className = 'weekday-header';
                div.textContent = day;
                header.appendChild(div);
            });

            // Tính toán ngày đầu/thứ đầu/tháng trước/sau
            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);
            const startDayOfWeek = firstDay.getDay();
            const daysInMonth = lastDay.getDate();

            // Ngày cuối tháng trước để lấp đầu tuần
            const prevMonthLastDay = new Date(year, month, 0).getDate();

            // Số ô cần hiển thị (6 hàng x 7 cột = 42)
            const totalCells = 42;

            const grid = document.getElementById('calendar-grid');
            grid.innerHTML = '';

            let dayNum = 1;
            let nextMonthDay = 1;

            for (let i = 0; i < totalCells; i++) {
                const cell = document.createElement('div');
                cell.className = 'date-cell';

                let cellDate;
                // Ô đầu tháng trước
                if (i < startDayOfWeek) {
                    cell.classList.add('other-month');
                    cell.textContent = prevMonthLastDay - startDayOfWeek + i + 1;
                    cellDate = new Date(year, month - 1, prevMonthLastDay - startDayOfWeek + i + 1);
                }
                // Ô trong tháng này
                else if (dayNum <= daysInMonth) {
                    const dateNumberSpan = document.createElement('span');
                    dateNumberSpan.className = 'date-number';
                    dateNumberSpan.textContent = dayNum;
                    cell.appendChild(dateNumberSpan);
                    
                    cellDate = new Date(year, month, dayNum);

                    // Format date for checking tasks
                    const formattedDate = `${year}-${String(month + 1).padStart(2, '0')}-${String(dayNum).padStart(2, '0')}`;
                    
                    // Check if there are tasks for this date and add badges
                    if (calendarTasksData && calendarTasksData[formattedDate]) {
                        const taskData = calendarTasksData[formattedDate];
                        const tasks = taskData.tasks || [];

                        cell.classList.add('has-tasks');

                        const badgesContainer = document.createElement('div');
                        badgesContainer.className = 'task-badges';

                        if (tasks.length <= 5) {
                            tasks.forEach(task => {
                                const badge = document.createElement('div');
                                let progressClass = 'task-badge';

                                if (task.progress >= 100) {
                                    progressClass += ' completed';
                                } else if (task.progress >= 50) {
                                    progressClass += ' mid-progress';
                                } else {
                                    progressClass += ' low-progress';
                                }

                                badge.className = progressClass;
                                badge.textContent = task.name;
                                badge.title = `${task.name} (${task.progress}%)`;
                                badgesContainer.appendChild(badge);
                            });
                        } else {
                            const firstFour = tasks.slice(0, 4);
                            firstFour.forEach(task => {
                                const badge = document.createElement('div');
                                let progressClass = 'task-badge';

                                if (task.progress >= 100) {
                                    progressClass += ' completed';
                                } else if (task.progress >= 50) {
                                    progressClass += ' mid-progress';
                                } else {
                                    progressClass += ' low-progress';
                                }

                                badge.className = progressClass;
                                badge.textContent = task.name;
                                badge.title = `${task.name} (${task.progress}%)`;
                                badgesContainer.appendChild(badge);
                            });
                            const remainingCount = tasks.length - 4;
                            const extraBadge = document.createElement('div');
                            extraBadge.className = 'task-badge';
                            extraBadge.textContent = `+${remainingCount} công việc khác`;
                            extraBadge.title = `Có ${remainingCount} công việc khác`;
                            badgesContainer.appendChild(extraBadge);
                        }
                        
                        cell.appendChild(badgesContainer);
                    }

                    // Đánh dấu hôm nay
                    const today = new Date();
                    if (
                        cellDate.getDate() === today.getDate() &&
                        cellDate.getMonth() === today.getMonth() &&
                        cellDate.getFullYear() === today.getFullYear()
                    ) {
                        cell.classList.add('today');
                    }
                    // Đánh dấu ngày đang chọn
                    if (
                        cellDate.getDate() === selectedDate.getDate() &&
                        cellDate.getMonth() === selectedDate.getMonth() &&
                        cellDate.getFullYear() === selectedDate.getFullYear()
                    ) {
                        cell.classList.add('selected');
                    }

                    // Sự kiện chọn ngày
                    cell.addEventListener('click', function() {
                        // Format date for form submission
                        const yyyy = cellDate.getFullYear();
                        const mm = String(cellDate.getMonth() + 1).padStart(2, '0');
                        const dd = String(cellDate.getDate()).padStart(2, '0');
                        document.getElementById('change-date-input').value = `${yyyy}-${mm}-${dd}`;
                        document.getElementById('change-date-form').submit();
                    });

                    dayNum++;
                }
                // Ô tháng sau
                else {
                    cell.classList.add('other-month');
                    cell.textContent = nextMonthDay++;
                    cellDate = new Date(year, month + 1, nextMonthDay - 1);
                }

                grid.appendChild(cell);
            }
            updateAddTaskButton();
        }

        // Khi click nút "Thêm công việc", chỉ mở modal nếu là hôm nay và đợt active
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('add-task-btn').addEventListener('click', function() {
                if (!isDotActive) {
                    alert('Đợt của bạn đã kết thúc hoặc chưa bắt đầu. Không thể thêm công việc!');
                    return;
                }
                if (isToday(selectedDate)) {
                    // Reset form và set ngày cho input ẩn
                    document.getElementById('task-form').reset();
                    document.getElementById('progress-value').textContent = '0%';
                    // Sửa: format ngày yyyy-mm-dd đúng múi giờ
                    const yyyy = selectedDate.getFullYear();
                    const mm = String(selectedDate.getMonth() + 1).padStart(2, '0');
                    const dd = String(selectedDate.getDate()).padStart(2, '0');
                    document.getElementById('task-date-input').value = `${yyyy}-${mm}-${dd}`;
                    $('#task-modal').modal('show');
                }
            });
        });

        // Trong openTaskModal, kiểm tra lại ngày và trạng thái đợt
        function openTaskModal(taskId, date) {
            if (!isDotActive) {
                alert('Đợt của bạn đã kết thúc hoặc chưa bắt đầu. Không thể thêm công việc!');
                return;
            }
            if (!isToday(date)) {
                alert('Chỉ được thêm công việc cho ngày hôm nay!');
                return;
            }
            // Reset form và set ngày cho input ẩn
            document.getElementById('task-form').reset();
            document.getElementById('progress-value').textContent = '0%';
            // Sửa: format ngày yyyy-mm-dd đúng múi giờ
            const yyyy = date.getFullYear();
            const mm = String(date.getMonth() + 1).padStart(2, '0');
            const dd = String(date.getDate()).padStart(2, '0');
            document.getElementById('task-date-input').value = `${yyyy}-${mm}-${dd}`;
            $('#task-modal').modal('show');
        }

        function bindTaskListEvents() {
    let selectedTaskId = null;
    document.querySelectorAll('.task-list-item').forEach(function(item) {
        if (!item.classList.contains('text-muted')) {
            item.addEventListener('click', function(e) {
                document.querySelectorAll('.task-list-item').forEach(i => i.classList.remove('selected'));
                item.classList.add('selected');
                document.getElementById('progress-panel').classList.add('active');
                document.getElementById('progress-task-name').textContent = item.dataset.name;
                document.getElementById('progress-task-description').textContent = item.dataset.desc;
                document.getElementById('progress-value-display').textContent = item.dataset.progress + '%';
                document.getElementById('progress-slider').value = item.dataset.progress;
                // Lưu lại ID task đang chọn vào biến toàn cục
                window.selectedTaskId = item.dataset.id;
            });
        }
    });
}
    // Cập nhật % khi kéo slider trong panel
    document.getElementById('progress-slider').addEventListener('input', function() {
        document.getElementById('progress-value_display').textContent = this.value + '%';
    });
    // Khi nhấn "Cập nhật tiến độ"
    document.getElementById('btn-update-progress').addEventListener('click', function() {
        if (!isDotActive) {
            alert('Đợt của bạn đã kết thúc hoặc chưa bắt đầu. Không thể cập nhật tiến độ!');
            return;
        }
        if (window.selectedTaskId) {
            document.getElementById('update-task-id').value = window.selectedTaskId;
            document.getElementById('update-task-progress').value = document.getElementById('progress-slider').value;
            document.getElementById('update-task-action').value = 'progress';
            document.getElementById('update-task-form').submit();
        }
    });
        document.addEventListener('DOMContentLoaded', function() {
    bindTaskListEvents();
});

// Khi đóng modal, reset trạng thái về thêm mới
$('#task-modal').on('hidden.bs.modal', function () {
    document.getElementById('edit-task-id').value = '';
    document.getElementById('edit-task-action').value = '';
    document.getElementById('task-modal-label').textContent = 'Thêm công việc mới';
    document.getElementById('task-form').reset();
    document.getElementById('progress-value').textContent = '0%';
});

document.addEventListener('DOMContentLoaded', function() {
    // Phân trang task-list
    document.querySelectorAll('.task-page-link').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const page = this.getAttribute('data-page');
            // Giữ lại ngày đang chọn khi chuyển trang
            const date = document.getElementById('change-date-input').value;
            // Reload với page mới
            let url = new URL(window.location.href);
            url.searchParams.set('date', date);
            url.searchParams.set('page', page);
            window.location.href = url.toString();
        });
    });
});

document.addEventListener('DOMContentLoaded', function() {
    const dateString = document.getElementById('change-date-input').value; 
    const chosenDate = new Date(dateString);

    if (chosenDate.toDateString() !== new Date().toDateString()) {
        document.getElementById('btn-edit-task').style.display = 'none';
    } else {
        document.getElementById('btn-edit-task').addEventListener('click', function() {
            if (window.selectedTaskId) {
                const item = document.querySelector(`.task-list-item[data-id='${window.selectedTaskId}']`);
                if (item) {
                    document.getElementById('edit-task-id').value = window.selectedTaskId;
                    document.getElementById('edit-task-action').value = 'edit';
                    document.getElementById('task-modal-label').textContent = 'Sửa công việc';
                    document.getElementById('task-name').value = item.dataset.name;
                    document.getElementById('task-description').value = item.dataset.desc;
                    document.getElementById('task-progress').value = item.dataset.progress;
                    document.getElementById('progress-value').textContent = item.dataset.progress + '%';
                    $('#task-modal').modal('show');
                }
            }
        });
    }
});
    </script>
</body>
</html>
