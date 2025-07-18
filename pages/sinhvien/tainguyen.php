<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <?php
    require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php";
    require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";

    ?>
    <meta charset="UTF-8">
    <title>Tài nguyên</title>

    <style>
    #page-wrapper {
        padding: 30px;
        min-height: 100vh;
        box-sizing: border-box;
        max-height: 100%;
        overflow-y: auto;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    
    .main-content {
        background: white;
        border-radius: 15px;
        padding: 30px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        margin-bottom: 30px;
    }
    
    .page-header {
        color: #2c3e50;
        font-weight: 700;
        margin-bottom: 30px;
        text-align: center;
        position: relative;
    }
    
    .page-header::after {
        content: '';
        position: absolute;
        bottom: -10px;
        left: 50%;
        transform: translateX(-50%);
        width: 80px;
        height: 3px;
        background: linear-gradient(45deg, #667eea, #764ba2);
        border-radius: 2px;
    }
    
    .filter-section {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 30px;
        border: 1px solid #e9ecef;
    }
    
    .filter-section label {
        display: block;
        margin-bottom: 10px;
        font-weight: 600;
        color: #495057;
    }
    
    .filter-section .form-control {
        border-radius: 8px;
        border: 2px solid #e9ecef;
        padding: 12px 15px;
        font-size: 14px;
        transition: all 0.3s ease;
        background: white;
        height: auto;
        line-height: 1.5;
    }
    
    .filter-section .form-control:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        outline: none;
    }
    
    .stats-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 20px;
        border-radius: 10px;
        text-align: center;
        margin-bottom: 20px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .stats-card h3 {
        margin: 0;
        font-size: 2.5em;
        font-weight: 700;
    }
    
    .stats-card p {
        margin: 5px 0 0 0;
        opacity: 0.9;
    }
    
    .resource-panel {
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        height: 100%;
        border: none;
        cursor: pointer;
        color: #333;
        position: relative;
        overflow: hidden;
    }
    
    .resource-panel::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(45deg, rgba(255,255,255,0.1), transparent);
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    .resource-panel:hover::before {
        opacity: 1;
    }
    
    .resource-panel:hover {
        transform: translateY(-8px) scale(1.02);
        box-shadow: 0 15px 30px rgba(0,0,0,0.15);
    }
    
    /* Màu sắc hiện đại cho từng loại file */
    .resource-panel.pdf {
        background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
        color: white;
    }
    .resource-panel.word {
        background: linear-gradient(135deg, #4834d4 0%, #686de0 100%);
        color: white;
    }
    .resource-panel.excel {
        background: linear-gradient(135deg, #00d2d3 0%, #54a0ff 100%);
        color: white;
    }
    .resource-panel.powerpoint {
        background: linear-gradient(135deg, #ff9ff3 0%, #f368e0 100%);
        color: white;
    }
    .resource-panel.video {
        background: linear-gradient(135deg, #7bed9f 0%, #70a1ff 100%);
        color: white;
    }
    .resource-panel.archive {
        background: linear-gradient(135deg, #a4b0be 0%, #747d8c 100%);
        color: white;
    }
    .resource-panel.image {
        background: linear-gradient(135deg, #ffa502 0%, #ff6348 100%);
        color: white;
    }
    .resource-panel.code {
        background: linear-gradient(135deg, #2ed573 0%, #1e90ff 100%);
        color: white;
    }
    .resource-panel.default {
        background: linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%);
        color: white;
    }
    
    .resource-panel .resource-title {
        font-size: 16px;
        font-weight: 600;
        margin-bottom: 8px;
        line-height: 1.3;
    }
    
    .resource-panel .resource-author {
        font-size: 13px;
        font-weight: 500;
        opacity: 0.9;
        margin-bottom: 5px;
    }
    
    .resource-panel .file-info {
        font-size: 11px;
        opacity: 0.8;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .resource-panel .file-info i {
        font-size: 10px;
    }
    
    .resource-icon {
        margin-right: 15px !important;
        opacity: 0.9;
    }
    
    /* Styles cho panel đợt */
    .dot-panel {
        border: none;
        border-radius: 15px;
        box-shadow: 0 8px 25px rgba(0,0,0,0.08);
        margin-bottom: 30px;
        overflow: hidden;
        transition: all 0.3s ease;
    }
    
    .dot-panel:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 35px rgba(0,0,0,0.12);
    }
    
    .dot-panel.latest {
        border: 2px solid #667eea;
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.2);
    }
    
    .panel-heading {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 15px 15px 0 0;
        padding: 20px 25px;
        border: none;
        position: relative;
        min-height: 60px;
    }
    
    .panel-heading.latest::after {
        content: 'MỚI NHẤT';
        position: absolute;
        top: 15px;
        right: 20px;
        background: rgba(255,255,255,0.3);
        color: white;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.5px;
        z-index: 10;
    }
    
    .panel-heading h4 {
        margin: 0;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-right: 100px; /* Tạo khoảng trống cho badge "MỚI NHẤT" */
    }
    
    .panel-heading .dot-info {
        display: flex;
        align-items: center;
        gap: 10px;
        flex: 1;
    }
    
    .panel-heading .badge {
        background: rgba(255,255,255,0.2);
        color: white;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
        white-space: nowrap;
    }
    
    .panel-body {
        padding: 25px;
        background: white;
    }
    
    .row {
        margin-bottom: 20px;
    }
    
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #6c757d;
    }
    
    .empty-state i {
        font-size: 4em;
        margin-bottom: 20px;
        opacity: 0.5;
    }
    
    .empty-state h3 {
        margin-bottom: 10px;
        color: #495057;
    }
    
    .loading-spinner {
        display: none;
        text-align: center;
        padding: 40px;
    }
    
    .loading-spinner .spinner {
        width: 40px;
        height: 40px;
        border: 4px solid #f3f3f3;
        border-top: 4px solid #667eea;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    .fade-in {
        animation: fadeIn 0.5s ease-in;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    /* Responsive design */
    @media (max-width: 768px) {
        .panel-heading h4 {
            padding-right: 120px;
        }
        
        .panel-heading.latest::after {
            top: 10px;
            right: 10px;
            font-size: 9px;
            padding: 4px 8px;
        }
        
        .stats-card {
            margin-bottom: 15px;
        }
        
        .filter-section {
            margin-bottom: 20px;
        }
        
        .dot-info span {
            font-size: 14px;
        }
    }
    
    @media (max-width: 480px) {
        .panel-heading h4 {
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
            padding-right: 60px;
        }
        
        .panel-heading .badge {
            align-self: flex-start;
        }
        
        .panel-heading.latest::after {
            top: 8px;
            right: 8px;
            font-size: 8px;
            padding: 3px 6px;
        }
    }
    
    /* Styles cho modal xem file */
    #modalXemFile .modal-dialog {
        max-width: 95% !important;
        width: 95% !important;
        margin: 30px auto;
    }
    
    #modalXemFile .modal-content {
        border-radius: 12px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
        border: none;
    }
    
    #modalXemFile .modal-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 12px 12px 0 0;
        padding: 20px 25px;
        border-bottom: none;
    }
    
    #modalXemFile .modal-title {
        font-weight: 600;
        font-size: 18px;
    }
    
    #modalXemFile .modal-body {
        padding: 25px;
        background: #f8f9fa;
        min-height: 500px;
    }
    
    #modalXemFile .modal-footer {
        border-top: 1px solid #dee2e6;
        padding: 15px 25px;
        background: white;
        border-radius: 0 0 12px 12px;
    }
    
    /* Responsive cho modal xem file */
    @media (max-width: 768px) {
        #modalXemFile .modal-dialog {
            max-width: 98% !important;
            width: 98% !important;
            margin: 10px auto;
        }
        
        #modalXemFile .modal-body {
            padding: 15px;
        }
        
        .office-viewer-container .viewer-tabs {
            flex-direction: column;
        }
        
        .office-viewer-container .viewer-tab {
            margin-right: 0;
            margin-bottom: 2px;
            border-radius: 8px;
        }
        
        .download-info-panel {
            flex-direction: column;
            text-align: center;
            gap: 20px;
        }
    }
    
    /* Styles cho modal */
    .download-info {
        display: flex;
        align-items: center;
        gap: 20px;
        margin-bottom: 20px;
    }
    
    .file-preview {
        text-align: center;
        color: #6c757d;
        min-width: 80px;
    }
    
    .file-details h5 {
        margin: 0 0 10px 0;
        font-weight: 600;
        color: #2c3e50;
    }
    
    .file-meta {
        color: #6c757d;
        font-size: 14px;
        margin-bottom: 10px;
    }
    
    .file-author {
        color: #495057;
        font-size: 14px;
        margin: 0;
    }
    
    .download-actions {
        border-top: 1px solid #e9ecef;
        padding-top: 20px;
        text-align: center;
    }
    
    .action-buttons {
        display: flex;
        gap: 10px;
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .btn-download {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        color: white;
        padding: 10px 20px;
        border-radius: 6px;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    
    .btn-download:hover {
        background: linear-gradient(135deg, #5a67d8 0%, #6b5b95 100%);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }
    
    .btn-preview {
        background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
        border: none;
        color: white;
        padding: 10px 20px;
        border-radius: 6px;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    
    .btn-preview:hover {
        background: linear-gradient(135deg, #38a169 0%, #2f855a 100%);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(72, 187, 120, 0.3);
    }
    
    .modal-content {
        border-radius: 10px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }
    
    .modal-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 10px 10px 0 0;
    }
    
    .modal-header .close {
        color: white;
        opacity: 0.8;
    }
    
    .modal-header .close:hover {
        opacity: 1;
    }
    
    #previewContent {
        max-height: 500px;
        overflow-y: auto;
        text-align: center;
    }
    
    #previewContent img {
        max-width: 100%;
        height: auto;
        border-radius: 8px;
    }
    
    #previewContent iframe {
        width: 100%;
        height: 500px;
        border: none;
        border-radius: 8px;
    }
    </style>
</head>

<body>
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/slidebar_Sinhvien.php"; ?>

    <div id="wrapper">
        <div id="page-wrapper">
            <div class="main-content">
                <div class="row">
                    <div class="col-md-12">
                        <h1 class="page-header">
                            <i class="fa fa-cloud-download" style="margin-right: 10px;"></i>
                            Tài nguyên học tập
                        </h1>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-3">
                        <div class="stats-card">
                            <h3 id="totalResources">0</h3>
                            <p><i class="fa fa-file"></i> Tổng tài nguyên</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <h3 id="totalDots">0</h3>
                            <p><i class="fa fa-calendar"></i> Tổng đợt</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="filter-section">
                            <label for="dotFilter">
                                <i class="fa fa-filter"></i> Lọc theo đợt thực tập:
                            </label>
                            <select id="dotFilter" class="form-control">
                                <option value="">Tất cả các đợt</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="loading-spinner" id="loadingSpinner">
                    <div class="spinner"></div>
                    <p>Đang tải tài nguyên...</p>
                </div>
                
                <div id="resourcesContainer">
                
                <?php
                try {
                    // Truy vấn danh sách tài nguyên theo từng đợt
                    $selectedDot = $_GET['id_dot'] ?? null;
$resources = [];

if ($selectedDot) {
    // 1. Tài nguyên thuộc đợt đã chọn
    $sql = "
        SELECT f.*, sv.Ten AS TenSinhVien, sv.Lop, sv.MSSV,
               dt.TenDot, dt.Nam, dt.ID as DotID, dt.ThoiGianBatDau
        FROM file f
        LEFT JOIN sinhvien sv ON f.ID_SV = sv.ID_TaiKhoan
        JOIN tainguyen_dot td ON f.ID = td.ID_File
        JOIN dotthuctap dt ON td.ID_Dot = dt.ID
        WHERE f.TrangThai = 1
          AND f.Loai = 'Tainguyen'
          AND td.ID_Dot = :idDot
    ";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['idDot' => $selectedDot]);
    $resourcesDot = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Tài nguyên không thuộc đợt nào
    $sqlChung = "
        SELECT f.*, sv.Ten AS TenSinhVien, sv.Lop, sv.MSSV,
               NULL AS TenDot, NULL AS Nam, NULL AS DotID, NULL AS ThoiGianBatDau
        FROM file f
        LEFT JOIN sinhvien sv ON f.ID_SV = sv.ID_TaiKhoan
        WHERE f.TrangThai = 1
          AND f.Loai = 'Tainguyen'
          AND NOT EXISTS (
              SELECT 1 FROM tainguyen_dot td WHERE td.ID_File = f.ID
          )
    ";
    $stmtChung = $conn->prepare($sqlChung);
    $stmtChung->execute();
    $resourcesChung = $stmtChung->fetchAll(PDO::FETCH_ASSOC);

    // Gộp lại
    $resources = array_merge($resourcesDot, $resourcesChung);
} else {
    // Không chọn đợt => lấy hết
    $sql = "
        SELECT f.*, sv.Ten AS TenSinhVien, sv.Lop, sv.MSSV,
               dt.TenDot, dt.Nam, dt.ID as DotID, dt.ThoiGianBatDau
        FROM file f
        LEFT JOIN sinhvien sv ON f.ID_SV = sv.ID_TaiKhoan
        LEFT JOIN tainguyen_dot td ON f.ID = td.ID_File
        LEFT JOIN dotthuctap dt ON td.ID_Dot = dt.ID
        WHERE f.TrangThai = 1
          AND f.Loai = 'Tainguyen'
        ORDER BY dt.ThoiGianBatDau DESC, f.NgayNop DESC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);
}


                    if (count($resources) > 0) {
                        // Nhóm tài nguyên theo đợt
                        $resourcesByDot = [];
                        $latestDotDate = null;
                        $latestDotId = null;
                        
                        foreach ($resources as $resource) {
                            $dotKey = $resource['DotID'] . '_' . $resource['TenDot'];
                            if (!isset($resourcesByDot[$dotKey])) {
                                $resourcesByDot[$dotKey] = [
                                    'dotInfo' => [
                                        'TenDot' => $resource['TenDot'],
                                        'Nam' => $resource['Nam'],
                                        'ID' => $resource['DotID'],
                                        'ThoiGianBatDau' => $resource['ThoiGianBatDau']
                                    ],
                                    'resources' => []
                                ];
                                
                                // Tìm đợt mới nhất
                                if ($latestDotDate === null || $resource['ThoiGianBatDau'] > $latestDotDate) {
                                    $latestDotDate = $resource['ThoiGianBatDau'];
                                    $latestDotId = $resource['DotID'];
                                }
                            }
                            $resourcesByDot[$dotKey]['resources'][] = $resource;
                        }
                        
                        $totalResources = count($resources);
                        $totalDots = count($resourcesByDot);
                        
                        // Hiển thị từng đợt
                        foreach ($resourcesByDot as $dotData) {
                            $dotInfo = $dotData['dotInfo'];
                            $dotResources = $dotData['resources'];
                            $isLatest = ($dotInfo['ID'] == $latestDotId);
                            
                            $panelClass = $isLatest ? 'dot-panel latest fade-in' : 'dot-panel fade-in';
                            $headingClass = $isLatest ? 'panel-heading latest' : 'panel-heading';
                            
                            echo '<div class="' . $panelClass . '" data-dot-id="' . $dotInfo['ID'] . '">';
                            echo '<div class="' . $headingClass . '">';
                            echo '<h4>';
                            echo '<div class="dot-info">';
                            echo '<i class="fa fa-calendar-check-o"></i>';
                            echo '<span>Đợt: ' . htmlspecialchars($dotInfo['TenDot']) . ' - Năm ' . htmlspecialchars($dotInfo['Nam']) . '</span>';
                            echo '</div>';
                            echo '<div class="badge">';
                            echo '<i class="fa fa-files-o"></i> ' . count($dotResources) . ' tài nguyên';
                            echo '</div>';
                            echo '</h4>';
                            echo '</div>';
                            echo '<div class="panel-body">';
                            
                            echo '<div class="row">';
                            $counter = 0;
                            
                            foreach ($dotResources as $resource) {
                                if ($counter > 0 && $counter % 4 == 0) {
                                    echo '</div><div class="row">';
                                }
                                
                                $fileExtension = pathinfo($resource['DIR'], PATHINFO_EXTENSION);
                                $icon = getFileIcon($fileExtension);
                                $panelClass = getPanelClass($fileExtension);
                                $uploadDate = date('d/m/Y', strtotime($resource['NgayNop']));
                                
                                echo '<div class="col-md-3" style="margin-bottom: 20px;">';
                                // Debug: hiển thị thông tin file path
                                $originalDir = $resource['DIR'];
                                
                                // Escape data for JavaScript and ensure correct path format
                                $fileDir = $resource['DIR'];
                                
                                // Remove 'datn/' prefix if exists and ensure proper web path
                                $fileDir = str_replace('\\', '/', $fileDir);
                                if (strpos($fileDir, 'datn/') === 0) {
                                    $fileDir = substr($fileDir, 5); // Remove 'datn/' prefix
                                }
                                
                                // Ensure the path starts with 'datn/' for web access
                                if (strpos($fileDir, 'datn/') !== 0) {
                                    $fileDir = 'datn/' . ltrim($fileDir, '/');
                                }
                                
                                // Debug: thêm comment HTML để debug
                                echo '<!-- DEBUG: Original DIR: ' . htmlspecialchars($originalDir) . ' -->';
                                echo '<!-- DEBUG: Processed DIR: ' . htmlspecialchars($fileDir) . ' -->';
                                echo '<!-- DEBUG: File extension: ' . htmlspecialchars($fileExtension) . ' -->';
                                
                                $jsDir = str_replace("'", "\\'", $fileDir);
                                $jsDisplayName = str_replace("'", "\\'", $resource['TenHienThi']); // Tên hiển thị
                                $jsFileName = str_replace("'", "\\'", $resource['TenFile']); // Tên file thực tế để tải
                                $jsAuthor = str_replace("'", "\\'", $resource['TenSinhVien']);
                                
                                echo '<div class="resource-panel ' . $panelClass . '" onclick="showDownloadModal(\'' . $jsDir . '\', \'' . $jsDisplayName . '\', \'' . $jsFileName . '\', \'' . $fileExtension . '\', \'' . $uploadDate . '\', \'' . $jsAuthor . '\')">';
                                echo '<div style="display: flex; align-items: center;">';
                                echo '<i class="fa ' . $icon . ' fa-fw fa-2x resource-icon"></i>';
                                echo '<div style="flex: 1;">';
                                echo '<div class="resource-title">' . htmlspecialchars($resource['TenHienThi']) . '</div>';
                                
                                // Hiển thị tên sinh viên nếu có
                                if (!empty($resource['TenSinhVien'])) {
                                    echo '<div class="resource-author">';
                                    echo '<i class="fa fa-user"></i> ' . htmlspecialchars($resource['TenSinhVien']);
                                    echo '</div>';
                                } else {
                                    echo '<div class="resource-author">';
                                    echo '<i class="fa fa-globe"></i> Tài nguyên chung';
                                    echo '</div>';
                                }
                                
                                echo '<div class="file-info">';
                                echo '<i class="fa fa-calendar"></i> ' . $uploadDate;
                                echo '</div>';
                                echo '</div></div></div></div>';
                                
                                $counter++;
                            }
                            
                            echo '</div>'; // Đóng row cuối cùng
                            echo '</div>'; // Đóng panel-body
                            echo '</div>'; // Đóng panel
                        }
                        
                        // Tạo JavaScript để populate dropdown filter và hiển thị thống kê
                        echo '<script>';
                        echo 'document.getElementById("totalResources").textContent = "' . $totalResources . '";';
                        echo 'document.getElementById("totalDots").textContent = "' . $totalDots . '";';
                        echo 'var dotOptions = [];';
                        echo 'var latestDotId = "' . $latestDotId . '";';
                        
                        foreach ($resourcesByDot as $dotData) {
                            $dotInfo = $dotData['dotInfo'];
                            $isLatest = ($dotInfo['ID'] == $latestDotId);
                            $label = htmlspecialchars($dotInfo['TenDot']) . ' - Năm ' . htmlspecialchars($dotInfo['Nam']);
                            if ($isLatest) {
                                $label .= ' (Mới nhất)';
                            }
                            echo 'dotOptions.push({id: "' . $dotInfo['ID'] . '", text: "' . $label . '", isLatest: ' . ($isLatest ? 'true' : 'false') . '});';
                        }
                        
                        echo 'var dotFilter = document.getElementById("dotFilter");';
                        echo 'dotOptions.forEach(function(option) {';
                        echo '    var optionElement = document.createElement("option");';
                        echo '    optionElement.value = option.id;';
                        echo '    optionElement.textContent = option.text;';
                        echo '    if (option.isLatest) {';
                        echo '        optionElement.selected = true;';
                        echo '    }';
                        echo '    dotFilter.appendChild(optionElement);';
                        echo '});';
                        
                        // Tự động hiển thị đợt mới nhất
                        echo 'setTimeout(function() {';
                        echo '    var allPanels = document.querySelectorAll(".dot-panel");';
                        echo '    allPanels.forEach(function(panel) {';
                        echo '        if (panel.getAttribute("data-dot-id") === latestDotId) {';
                        echo '            panel.style.display = "block";';
                        echo '        } else {';
                        echo '            panel.style.display = "none";';
                        echo '        }';
                        echo '    });';
                        echo '}, 100);';
                        
                        echo '</script>';
                    } else {
                        echo '<div class="empty-state">';
                        echo '<i class="fa fa-inbox"></i>';
                        echo '<h3>Chưa có tài nguyên nào</h3>';
                        echo '<p>Hiện tại chưa có tài nguyên học tập nào được tải lên.</p>';
                        echo '</div>';
                    }
                } catch (PDOException $e) {
                    echo '<div class="alert alert-danger">Lỗi khi tải tài nguyên: ' . $e->getMessage() . '</div>';
                }

                // Hàm xác định icon dựa trên loại file
                function getFileIcon($fileType)
                {
                    $fileType = strtolower($fileType);
                    switch ($fileType) {
                        case 'pdf':
                            return 'fa-file-pdf-o';
                        case 'doc':
                        case 'docx':
                            return 'fa-file-word-o';
                        case 'xls':
                        case 'xlsx':
                            return 'fa-file-excel-o';
                        case 'ppt':
                        case 'pptx':
                            return 'fa-file-powerpoint-o';
                        case 'mp4':
                        case 'avi':
                        case 'mov':
                            return 'fa-file-video-o';
                        case 'zip':
                        case 'rar':
                            return 'fa-file-archive-o';
                        case 'jpg':
                        case 'png':
                        case 'gif':
                            return 'fa-file-image-o';
                        case 'php':
                        case 'html':
                        case 'js':
                        case 'css':
                            return 'fa-file-code-o';
                        default:
                            return 'fa-file-o';
                    }
                }

                // Hàm xác định class màu cho panel dựa trên loại file
                function getPanelClass($fileType)
                {
                    $fileType = strtolower($fileType);
                    switch ($fileType) {
                        case 'pdf':
                            return 'pdf';
                        case 'doc':
                        case 'docx':
                            return 'word';
                        case 'xls':
                        case 'xlsx':
                            return 'excel';
                        case 'ppt':
                        case 'pptx':
                            return 'powerpoint';
                        case 'mp4':
                        case 'avi':
                        case 'mov':
                            return 'video';
                        case 'zip':
                        case 'rar':
                            return 'archive';
                        case 'jpg':
                        case 'png':
                        case 'gif':
                            return 'image';
                        case 'php':
                        case 'html':
                        case 'js':
                        case 'css':
                            return 'code';
                        default:
                            return 'default';
                    }
                }
                ?>
                </div> <!-- Đóng resourcesContainer -->
            </div> <!-- Đóng main-content -->
        </div>
    </div>
    
    <!-- Modal tải file xuống -->
    <div class="modal fade" id="downloadModal" tabindex="-1" role="dialog" aria-labelledby="downloadModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" id="downloadModalLabel">
                        <i class="fa fa-download"></i> Tải xuống tài nguyên
                    </h4>
                </div>
                <div class="modal-body">
                    <div class="download-info">
                        <div class="file-preview">
                            <i id="modalFileIcon" class="fa fa-file fa-4x"></i>
                        </div>
                        <div class="file-details">
                            <h5 id="modalFileName">Tên file</h5>
                            <p class="file-meta">
                                <span id="modalFileType">Loại file</span> • 
                                <span id="modalUploadDate">Ngày tải lên</span>
                            </p>
                            <p class="file-author">
                                <i class="fa fa-user"></i> <span id="modalAuthor">Tác giả</span>
                            </p>
                        </div>
                    </div>
                    
                    <div class="download-actions">
                        <p class="text-muted">Bạn có muốn tải file này về máy không?</p>
                        
                        <div class="action-buttons">
                            <button type="button" class="btn btn-primary btn-download" onclick="downloadFile()">
                                <i class="fa fa-download"></i> Tải xuống
                            </button>
                            <button type="button" class="btn btn-success btn-preview" onclick="previewFile()">
                                <i class="fa fa-eye"></i> Xem trước
                            </button>
                            <button type="button" class="btn btn-info btn-online" onclick="viewOnline()">
                                <i class="fa fa-globe"></i> Xem online
                            </button>
                            <button type="button" class="btn btn-warning btn-debug" onclick="debugFile()">
                                <i class="fa fa-bug"></i> Debug
                            </button>
                            <button type="button" class="btn btn-default" data-dismiss="modal">
                                <i class="fa fa-times"></i> Hủy
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal xem trước file -->
    <div class="modal fade" id="previewModal" tabindex="-1" role="dialog" aria-labelledby="previewModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" id="previewModalLabel">
                        <i class="fa fa-eye"></i> Xem trước tài nguyên
                    </h4>
                </div>
                <div class="modal-body">
                    <div id="previewContent"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" onclick="downloadFileFromPreview()">
                        <i class="fa fa-download"></i> Tải xuống
                    </button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        <i class="fa fa-times"></i> Đóng
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal xem file online -->
    <div class="modal fade" id="modalXemFile" tabindex="-1" role="dialog" aria-labelledby="modalXemFileLabel">
        <div class="modal-dialog modal-lg" role="document" style="max-width: 90%; width: 90%;">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" id="modalXemFileLabel">
                        <i class="fa fa-file-o"></i> Xem file online
                    </h4>
                </div>
                <div class="modal-body" style="padding: 20px;">
                    <div id="xemFileBody"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        <i class="fa fa-times"></i> Đóng
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <?php
    require $_SERVER['DOCUMENT_ROOT'] . "/datn/template/footer.php"
        ?>
    <script>
        // Biến global để lưu thông tin file hiện tại
        var currentFile = {
            url: '',
            displayName: '',  // Tên hiển thị
            fileName: '',     // Tên file thực tế
            type: '',
            date: '',
            author: ''
        };
        
        // Hàm hiển thị modal tải xuống
        function showDownloadModal(fileUrl, displayName, fileName, fileType, uploadDate, author) {
            try {
                // Sanitize inputs
                fileUrl = fileUrl || '';
                displayName = displayName || 'Unknown file';
                fileName = fileName || displayName || 'Unknown file';
                fileType = fileType || '';
                uploadDate = uploadDate || '';
                author = author || 'Tài nguyên chung';
                
                // Debug: log thông tin file
                console.log('=== FILE DEBUG INFO ===');
                console.log('Original File URL:', fileUrl);
                console.log('Display Name:', displayName);
                console.log('File Name:', fileName);
                console.log('File Type:', fileType);
                console.log('Upload Date:', uploadDate);
                console.log('Author:', author);
                
                // Ensure the URL is properly formatted
                if (fileUrl && !fileUrl.startsWith('http') && !fileUrl.startsWith('/')) {
                    fileUrl = '/' + fileUrl;
                }
                
                console.log('Processed File URL:', fileUrl);
                
                currentFile.url = fileUrl;
                currentFile.displayName = displayName;
                currentFile.fileName = fileName;
                currentFile.type = fileType;
                currentFile.date = uploadDate;
                currentFile.author = author;
                
                // Cập nhật nội dung modal (hiển thị tên hiển thị)
                var modalFileName = document.getElementById('modalFileName');
                var modalFileType = document.getElementById('modalFileType');
                var modalUploadDate = document.getElementById('modalUploadDate');
                var modalAuthor = document.getElementById('modalAuthor');
                var modalFileIcon = document.getElementById('modalFileIcon');
                
                if (modalFileName) modalFileName.textContent = displayName;
                if (modalFileType) modalFileType.textContent = getFileTypeName(fileType);
                if (modalUploadDate) modalUploadDate.textContent = uploadDate;
                if (modalAuthor) modalAuthor.textContent = author;
                
                // Cập nhật icon
                var iconClass = getFileIconClass(fileType);
                if (modalFileIcon) modalFileIcon.className = 'fa ' + iconClass + ' fa-4x';
                
                // Hiển thị modal
                $('#downloadModal').modal('show');
            } catch (error) {
                console.error('Error in showDownloadModal:', error);
                showNotification('error', 'Có lỗi xảy ra khi mở modal tải xuống.');
            }
        }
        
        // Hàm tải file xuống
        function downloadFile() {
            try {
                if (!currentFile.url) {
                    showNotification('error', 'Không tìm thấy đường dẫn file.');
                    return;
                }
                
                // Chuẩn hóa đường dẫn file
                var fileUrl = currentFile.url.replace(/\\/g, '/');
                
                // Tạo URL download thông qua script download.php với tên file tùy chỉnh
                var downloadUrl = '/datn/download.php?file=' + encodeURIComponent(fileUrl) + '&name=' + encodeURIComponent(currentFile.fileName);
                
                // Hiển thị thông báo đang tải (với tên hiển thị)
                showNotification('info', 'Đang chuẩn bị tải file: ' + currentFile.displayName);
                
                // Tạo link tải xuống (sử dụng tên file thực tế)
                var link = document.createElement('a');
                link.href = downloadUrl;
                link.download = currentFile.fileName; // Sử dụng tên file thực tế
                link.target = '_blank';
                link.style.display = 'none';
                
                // Xử lý lỗi tải file
                link.onerror = function() {
                    showNotification('error', 'Có lỗi xảy ra khi tải file. Vui lòng thử lại.');
                };
                
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
                $('#downloadModal').modal('hide');
                
                // Hiển thị thông báo thành công sau 1 giây (với tên file thực tế)
                setTimeout(function() {
                    showNotification('success', 'File đã được tải xuống: ' + currentFile.fileName);
                }, 1000);
                
            } catch (error) {
                console.error('Error in downloadFile:', error);
                console.error('Current file URL:', currentFile.url);
                console.error('Current file name:', currentFile.fileName);
                showNotification('error', 'Có lỗi xảy ra khi tải file xuống. Vui lòng thử lại.');
            }
        }
        
        // Hàm xem trước file
        function previewFile() {
            try {
                if (!currentFile.url) {
                    showNotification('error', 'Không tìm thấy đường dẫn file.');
                    return;
                }
                
                $('#downloadModal').modal('hide');
                
                setTimeout(function() {
                    var previewContent = document.getElementById('previewContent');
                    if (!previewContent) {
                        showNotification('error', 'Không tìm thấy phần tử preview.');
                        return;
                    }
                    
                    var ext = currentFile.type.toLowerCase();
                    var downloadUrl = '/datn/download.php?file=' + encodeURIComponent(currentFile.url);
                    var previewUrl = '/datn/download.php?file=' + encodeURIComponent(currentFile.url) + '&preview=1';
                    
                    var html = '';
                    
                    if (['pdf'].includes(ext)) {
                        html = '<iframe src="' + previewUrl + '" style="width:100%;height:500px;border:none;border-radius:8px;"></iframe>';
                    } else                    if (['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'].includes(ext)) {
                        html = '<img src="' + previewUrl + '" style="max-width:100%;height:auto;border-radius:8px;" alt="' + currentFile.displayName + '" onerror="this.style.display=\'none\'; this.nextSibling.style.display=\'block\';">';
                        html += '<div style="display:none;" class="alert alert-warning">Không thể tải ảnh.</div>';
                    } else if (['mp4', 'webm', 'ogg', 'avi', 'mov', 'wmv'].includes(ext)) {
                        html = '<video src="' + previewUrl + '" controls style="max-width:100%;max-height:400px;border-radius:8px;"></video>';
                    } else if (['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'].includes(ext)) {
                        // Sử dụng hàm mới để xem file Office
                        $('#previewModal').modal('hide');
                        setTimeout(function() {
                            viewOfficeFile(currentFile.url, currentFile.displayName);
                        }, 300);
                        return; // Kết thúc function vì đã xử lý riêng
                    } else if (ext === 'txt') {
                        fetch(previewUrl)
                            .then(res => res.text())
                            .then(text => {
                                previewContent.innerHTML = '<pre style="text-align:left;background:#f8f9fa;padding:20px;border-radius:8px;max-height:500px;overflow-y:auto;white-space:pre-wrap;">' + text + '</pre>';
                            })
                            .catch(err => {
                                previewContent.innerHTML = '<div class="alert alert-warning">Không thể tải nội dung file.</div>';
                            });
                        
                        $('#previewModal').modal('show');
                        return;
                    } else {
                        html = '<div class="alert alert-info"><i class="fa fa-info-circle"></i> Không thể xem trước loại file này.<br><br><button class="btn btn-primary" onclick="downloadFileFromPreview()"><i class="fa fa-download"></i> Tải xuống để xem</button></div>';
                    }
                    
                    previewContent.innerHTML = html;
                    $('#previewModal').modal('show');
                }, 300);
            } catch (error) {
                console.error('Error in previewFile:', error);
                showNotification('error', 'Có lỗi xảy ra khi xem trước file.');
            }
        }
        
        // Hàm tải file từ modal xem trước
        function downloadFileFromPreview() {
            downloadFile();
            $('#previewModal').modal('hide');
        }
        
        // Hàm lấy tên loại file
        function getFileTypeName(fileType) {
            var type = fileType.toLowerCase();
            switch (type) {
                case 'pdf': return 'PDF Document';
                case 'doc': case 'docx': return 'Word Document';
                case 'xls': case 'xlsx': return 'Excel Spreadsheet';
                case 'ppt': case 'pptx': return 'PowerPoint Presentation';
                case 'mp4': case 'avi': case 'mov': return 'Video File';
                case 'zip': case 'rar': return 'Archive File';
                case 'jpg': case 'jpeg': case 'png': case 'gif': return 'Image File';
                case 'txt': return 'Text File';
                case 'php': case 'html': case 'js': case 'css': return 'Code File';
                default: return 'File';
            }
        }
        
        // Hàm xem file Word/Excel/PowerPoint với nhiều phương thức
        function viewOfficeFile(url, displayName) {
            try {
                // Chuẩn hóa đường dẫn
                url = url.replace(/\\/g, '/');
                var downloadUrl = '/datn/download.php?file=' + encodeURIComponent(url);
                var previewUrl = '/datn/download.php?file=' + encodeURIComponent(url) + '&preview=1';
                
                // Tạo URL tuyệt đối cho Google Docs Viewer
                var absoluteUrl = window.location.protocol + '//' + window.location.host + previewUrl;
                
                var html = `
                    <div class="office-viewer-container">
                        <div class="viewer-tabs">
                            <button class="viewer-tab active" onclick="switchViewer('google', this)">
                                <i class="fa fa-google"></i> Google Docs
                            </button>
                            <button class="viewer-tab" onclick="switchViewer('office', this)">
                                <i class="fa fa-microsoft"></i> Office Online
                            </button>
                            <button class="viewer-tab" onclick="switchViewer('download', this)">
                                <i class="fa fa-download"></i> Tải xuống
                            </button>
                        </div>
                        
                        <div id="google-viewer" class="viewer-content active">
                            <iframe src="https://docs.google.com/gview?url=${encodeURIComponent(absoluteUrl)}&embedded=true" 
                                style="width:100%;height:550px;border:none;border-radius:8px;" 
                                frameborder="0"
                                onload="handleViewerLoad(this)"
                                onerror="handleViewerError(this)">
                            </iframe>
                            <div class="viewer-loading">
                                <i class="fa fa-spinner fa-spin"></i> Đang tải...
                            </div>
                        </div>
                        
                        <div id="office-viewer" class="viewer-content">
                            <iframe src="https://view.officeapps.live.com/op/embed.aspx?src=${encodeURIComponent(absoluteUrl)}" 
                                style="width:100%;height:550px;border:none;border-radius:8px;" 
                                frameborder="0"
                                onload="handleViewerLoad(this)"
                                onerror="handleViewerError(this)">
                            </iframe>
                            <div class="viewer-loading">
                                <i class="fa fa-spinner fa-spin"></i> Đang tải...
                            </div>
                        </div>
                        
                        <div id="download-viewer" class="viewer-content">
                            <div class="download-info-panel">
                                <div class="download-icon">
                                    <i class="fa fa-file-word-o fa-4x"></i>
                                </div>
                                <div class="download-details">
                                    <h4>${displayName}</h4>
                                    <p>Không thể xem trước file này trực tuyến. Hãy tải xuống để xem nội dung.</p>
                                    <a href="${downloadUrl}" class="btn btn-primary btn-lg" target="_blank">
                                        <i class="fa fa-download"></i> Tải xuống ngay
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <style>
                    .office-viewer-container {
                        position: relative;
                    }
                    .viewer-tabs {
                        display: flex;
                        border-bottom: 2px solid #e9ecef;
                        margin-bottom: 15px;
                    }
                    .viewer-tab {
                        flex: 1;
                        padding: 12px 20px;
                        border: none;
                        background: #f8f9fa;
                        color: #495057;
                        cursor: pointer;
                        transition: all 0.3s ease;
                        border-radius: 8px 8px 0 0;
                        margin-right: 2px;
                        font-weight: 500;
                    }
                    .viewer-tab:hover {
                        background: #e9ecef;
                        color: #007bff;
                    }
                    .viewer-tab.active {
                        background: #007bff;
                        color: white;
                    }
                    .viewer-content {
                        display: none;
                        position: relative;
                    }
                    .viewer-content.active {
                        display: block;
                    }
                    .viewer-loading {
                        position: absolute;
                        top: 50%;
                        left: 50%;
                        transform: translate(-50%, -50%);
                        color: #6c757d;
                        font-size: 16px;
                        display: block;
                    }
                    .download-info-panel {
                        display: flex;
                        align-items: center;
                        gap: 30px;
                        padding: 40px;
                        background: #f8f9fa;
                        border-radius: 8px;
                        text-align: center;
                        justify-content: center;
                    }
                    .download-icon {
                        color: #007bff;
                    }
                    .download-details h4 {
                        margin-bottom: 15px;
                        color: #495057;
                    }
                    .download-details p {
                        margin-bottom: 20px;
                        color: #6c757d;
                    }
                    </style>
                `;
                
                var xemFileBody = document.getElementById('xemFileBody');
                if (xemFileBody) {
                    xemFileBody.innerHTML = html;
                }
                
                var modalXemFile = document.getElementById('modalXemFile');
                if (modalXemFile) {
                    $(modalXemFile).modal('show');
                }
            } catch (error) {
                console.error('Error in viewOfficeFile:', error);
                showNotification('error', 'Có lỗi xảy ra khi xem file Office.');
            }
        }
        
        // Hàm chuyển đổi giữa các viewer
        function switchViewer(type, button) {
            try {
                // Cập nhật tab active
                var tabs = document.querySelectorAll('.viewer-tab');
                tabs.forEach(tab => tab.classList.remove('active'));
                button.classList.add('active');
                
                // Cập nhật viewer content
                var contents = document.querySelectorAll('.viewer-content');
                contents.forEach(content => content.classList.remove('active'));
                
                var targetContent = document.getElementById(type + '-viewer');
                if (targetContent) {
                    targetContent.classList.add('active');
                }
            } catch (error) {
                console.error('Error in switchViewer:', error);
            }
        }
        
        // Hàm xử lý khi iframe load thành công
        function handleViewerLoad(iframe) {
            try {
                var loading = iframe.nextElementSibling;
                if (loading && loading.classList.contains('viewer-loading')) {
                    loading.style.display = 'none';
                }
            } catch (error) {
                console.error('Error in handleViewerLoad:', error);
            }
        }
        
        // Hàm xử lý khi iframe load thất bại
        function handleViewerError(iframe) {
            try {
                var loading = iframe.nextElementSibling;
                if (loading && loading.classList.contains('viewer-loading')) {
                    loading.innerHTML = '<i class="fa fa-exclamation-triangle"></i> Không thể tải nội dung';
                    loading.style.color = '#dc3545';
                }
            } catch (error) {
                console.error('Error in handleViewerError:', error);
            }
        }
        
        // Hàm xem file Word/Excel/PowerPoint với nhiều phương thức
        function getFileIconClass(fileType) {
            var type = fileType.toLowerCase();
            switch (type) {
                case 'pdf': return 'fa-file-pdf-o';
                case 'doc': case 'docx': return 'fa-file-word-o';
                case 'xls': case 'xlsx': return 'fa-file-excel-o';
                case 'ppt': case 'pptx': return 'fa-file-powerpoint-o';
                case 'mp4': case 'avi': case 'mov': return 'fa-file-video-o';
                case 'zip': case 'rar': return 'fa-file-archive-o';
                case 'jpg': case 'jpeg': case 'png': case 'gif': return 'fa-file-image-o';
                case 'php': case 'html': case 'js': case 'css': return 'fa-file-code-o';
                default: return 'fa-file-o';
            }
        }
        
        // Hàm hiển thị thông báo
        function showNotification(type, message) {
            try {
                var alertType = 'info';
                var iconClass = 'fa-info-circle';
                
                if (type === 'success') {
                    alertType = 'success';
                    iconClass = 'fa-check';
                } else if (type === 'error' || type === 'danger') {
                    alertType = 'danger';
                    iconClass = 'fa-exclamation-triangle';
                } else if (type === 'warning') {
                    alertType = 'warning';
                    iconClass = 'fa-warning';
                }
                
                var notification = $('<div class="alert alert-' + alertType + ' alert-dismissible" style="position:fixed;top:20px;right:20px;z-index:9999;min-width:300px;max-width:400px;">' +
                    '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
                    '<span aria-hidden="true">&times;</span>' +
                    '</button>' +
                    '<i class="fa ' + iconClass + '"></i> ' + message +
                    '</div>');
                
                $('body').append(notification);
                
                setTimeout(function() {
                    notification.fadeOut(function() {
                        notification.remove();
                    });
                }, 4000);
            } catch (error) {
                console.error('Error in showNotification:', error);
                alert(message); // Fallback notification
            }
        }
        
        function xemFileOnline(url) {
            try {
                // Chuẩn hóa đường dẫn
                url = url.replace(/\\/g, '/');
                
                var downloadUrl = '/datn/download.php?file=' + encodeURIComponent(url);
                var previewUrl = '/datn/download.php?file=' + encodeURIComponent(url) + '&preview=1';

                const ext = url.split('.').pop().toLowerCase();
                let html = '';

                if (['pdf'].includes(ext)) {
                    html = `<iframe src="${previewUrl}" style="width:100%;height:600px;border:none;border-radius:8px;"></iframe>`;
                } else if (['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'].includes(ext)) {
                    html = `<img src="${previewUrl}" style="max-width:100%;max-height:600px;border-radius:8px;" alt="Preview">`;
                } else if (['mp4', 'webm', 'ogg', 'avi', 'mov', 'wmv'].includes(ext)) {
                    html = `<video src="${previewUrl}" controls style="max-width:100%;max-height:600px;border-radius:8px;"></video>`;
                } else if (['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'].includes(ext)) {
                    // Sử dụng hàm mới để xem file Office
                    var displayName = url.split('/').pop();
                    viewOfficeFile(url, displayName);
                    return; // Kết thúc function vì đã xử lý riêng
                } else if (url.startsWith('http')) {
                    html = `<iframe src="${url}" style="width:100%;height:600px;border:none;border-radius:8px;"></iframe>`;
                } else if (ext === 'txt') {
                    fetch(previewUrl).then(res => res.text()).then(text => {
                        var xemFileBody = document.getElementById('xemFileBody');
                        if (xemFileBody) {
                            xemFileBody.innerHTML = '<pre style="text-align:left;background:#f9f9f9;padding:20px;border-radius:8px;white-space:pre-wrap;">' + text + '</pre>';
                        }
                    }).catch(err => {
                        var xemFileBody = document.getElementById('xemFileBody');
                        if (xemFileBody) {
                            xemFileBody.innerHTML = '<div class="alert alert-warning">Không thể tải nội dung file.</div>';
                        }
                    });
                    
                    var modalXemFile = document.getElementById('modalXemFile');
                    if (modalXemFile) {
                        $(modalXemFile).modal('show');
                    }
                    return;
                } else {
                    html = `<div class="alert alert-info">
                        <i class="fa fa-info-circle"></i> Không thể xem trực tuyến. 
                        <a href="${downloadUrl}" target="_blank" class="btn btn-primary btn-sm">
                            <i class="fa fa-download"></i> Tải xuống để xem
                        </a>
                    </div>`;
                }

                var xemFileBody = document.getElementById('xemFileBody');
                if (xemFileBody) {
                    xemFileBody.innerHTML = html;
                }
                
                var modalXemFile = document.getElementById('modalXemFile');
                if (modalXemFile) {
                    $(modalXemFile).modal('show');
                }
            } catch (error) {
                console.error('Error in xemFileOnline:', error);
                showNotification('error', 'Có lỗi xảy ra khi xem file online.');
            }
        }
        
        // Xử lý filter theo đợt với hiệu ứng
        function handleDotFilter() {
            try {
                var dotFilterElement = document.getElementById('dotFilter');
                if (!dotFilterElement) return;
                
                var selectedDot = dotFilterElement.value;
                var allPanels = document.querySelectorAll('.dot-panel');
                var loadingSpinner = document.getElementById('loadingSpinner');
                
                // Hiển thị loading
                if (loadingSpinner) {
                    loadingSpinner.style.display = 'block';
                }
                
                // Ẩn tất cả panels
                allPanels.forEach(function(panel) {
                    panel.style.display = 'none';
                });
                
                // Hiển thị sau 300ms để tạo hiệu ứng loading
                setTimeout(function() {
                    if (loadingSpinner) {
                        loadingSpinner.style.display = 'none';
                    }
                    
                    allPanels.forEach(function(panel) {
                        if (selectedDot === '' || panel.getAttribute('data-dot-id') === selectedDot) {
                            panel.style.display = 'block';
                            panel.classList.add('fade-in');
                        } else {
                            panel.style.display = 'none';
                            panel.classList.remove('fade-in');
                        }
                    });
                }, 300);
            } catch (error) {
                console.error('Error in handleDotFilter:', error);
            }
        }
  
        
        // Thêm tooltip cho các resource panels và xử lý events
        document.addEventListener('DOMContentLoaded', function() {
            try {
                // Xử lý hover effects cho resource panels
                var resourcePanels = document.querySelectorAll('.resource-panel');
                resourcePanels.forEach(function(panel) {
                    panel.addEventListener('mouseenter', function() {
                        this.style.transform = 'translateY(-8px) scale(1.02)';
                    });
                    
                    panel.addEventListener('mouseleave', function() {
                        this.style.transform = 'translateY(0) scale(1)';
                    });
                });
                
                // Xử lý filter dropdown
                var dotFilter = document.getElementById('dotFilter');
                if (dotFilter) {
                    dotFilter.addEventListener('change', function() {
                        handleDotFilter();
                        
                        // Smooth scroll to top
                        setTimeout(function() {
                            if (window.scrollTo) {
                                window.scrollTo({
                                    top: 0,
                                    behavior: 'smooth'
                                });
                            } else {
                                window.scrollTo(0, 0);
                            }
                        }, 350);
                    });
                }
                
                // Kiểm tra và hiển thị thông báo nếu không có tài nguyên
                var hasResources = document.querySelectorAll('.resource-panel').length > 0;
                if (!hasResources) {
                    console.log('No resources found');
                }
                
            } catch (error) {
                console.error('Error in DOMContentLoaded:', error);
            }
        });
        
        // Hàm debug file path
        function debugFile() {
            try {
                if (!currentFile.url) {
                    showNotification('error', 'Không tìm thấy đường dẫn file.');
                    return;
                }
                
                var fileUrl = currentFile.url.replace(/\\/g, '/');
                var debugUrl = '/datn/download.php?file=' + encodeURIComponent(fileUrl) + '&name=' + encodeURIComponent(currentFile.fileName) + '&debug=1';
                
                // Mở tab mới để xem thông tin debug
                window.open(debugUrl, '_blank');
                
            } catch (error) {
                console.error('Error in debugFile:', error);
                showNotification('error', 'Có lỗi xảy ra khi debug file.');
            }
        }
    </script>
</body>

</html>