<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Tài nguyên</title>
    <?php
    require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php";
    require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";
    ?>
    <style>
    #page-wrapper {
        padding: 30px;
        min-height: 100vh;
        box-sizing: border-box;
        max-height: 100%;
        overflow-y: auto;
    }
    .resource-panel {
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
        height: 100%;
        border: none;
        cursor: pointer;
        color: #333; /* Màu chữ mặc định */
    }
    .resource-panel:hover {
        transform: translateY(-5px);
        box-shadow: 0 6px 12px rgba(0,0,0,0.15);
    }
    
    /* Màu sắc cho từng loại file */
    .resource-panel.pdf {
        background: linear-gradient(135deg, #ff6b6b, #ff8e8e);
        color: white;
    }
    .resource-panel.word {
        background: white;
        border: 1px solid #e0e0e0;
    }
    .resource-panel.excel {
        background: linear-gradient(135deg, #7ae98c, #5ac768);
        color: white;
    }
    .resource-panel.powerpoint {
        background: linear-gradient(135deg, #ff9a44, #ff6b6b);
        color: white;
    }
    .resource-panel.video {
        background: linear-gradient(135deg, #6b66ff, #8e8aff);
        color: white;
    }
    .resource-panel.archive {
        background: linear-gradient(135deg, #a5a5a5, #7a7a7a);
        color: white;
    }
    .resource-panel.image {
        background: linear-gradient(135deg, #ffb347, #ffcc33);
        color: white;
    }
    .resource-panel.code {
        background: linear-gradient(135deg, #47b8ff, #3399ff);
        color: white;
    }
    .resource-panel.default {
        background: linear-gradient(135deg, #b8b8b8, #9e9e9e);
        color: white;
    }
    
    /* Đảm bảo chữ nổi bật trên mọi màu nền */
    .resource-panel .resource-title {
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 5px;
    }
    .resource-panel .resource-author {
        font-size: 13px;
        font-weight: 500;
        opacity: 0.9;
    }
    .resource-panel .file-info {
        font-size: 12px;
        margin-top: 8px;
        opacity: 0.8;
    }
    
    .row {
        margin-bottom: 20px;
    }
    </style>
</head>
<body>
    <div id="wrapper">
    <?php
    require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/slidebar_Sinhvien.php";
    ?>
        
        <div id="page-wrapper">
            <div class="container-fluid">
                <h1 class="page-header">Tài nguyên</h1>
                
                <?php
                try {
                    // Truy vấn danh sách tài nguyên chỉ lấy loại "Tainguyen"
                    $sql = "SELECT f.*, sv.Ten AS TenSinhVien, sv.Lop, sv.MSSV 
                            FROM file f
                            JOIN sinhvien sv ON f.ID_SV = sv.ID_TaiKhoan
                            WHERE f.TrangThai = 1 AND f.Loai = 'Tainguyen' 
                            ORDER BY f.NgayNop DESC";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute();
                    $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (count($resources) > 0) {
                        echo '<div class="row">';
                        $counter = 0;
                        
                        foreach ($resources as $resource) {
                            if ($counter > 0 && $counter % 4 == 0) {
                                echo '</div><div class="row">';
                            }
                            
                            $fileExtension = pathinfo($resource['DIR'], PATHINFO_EXTENSION);
                            $icon = getFileIcon($fileExtension);
                            $panelClass = getPanelClass($fileExtension);
                            $uploadDate = date('d/m/Y', strtotime($resource['NgayNop']));
                            
                            echo '<div class="col-md-3">';
                            echo '<a href="' . htmlspecialchars($resource['DIR']) . '" target="_blank" style="text-decoration: none;">';
                            echo '<div class="resource-panel ' . $panelClass . '">';
                            echo '<div style="display: flex; align-items: center;">';
                            echo '<i class="fa ' . $icon . ' fa-fw fa-3x resource-icon"></i>';
                            echo '<div>';
                            echo '<div class="resource-title">' . htmlspecialchars($resource['Ten']) . '</div>';
                            echo '<div class="resource-author">' . htmlspecialchars($resource['TenSinhVien']) . '</div>';
                            echo '<div class="file-info">Ngày tải lên: ' . $uploadDate . '</div>';
                            echo '</div></div></div></a></div>';
                            
                            $counter++;
                        }
                        
                        echo '</div>';
                    } else {
                        echo '<div class="alert alert-info">Không có tài nguyên nào được tìm thấy.</div>';
                    }
                } catch(PDOException $e) {
                    echo '<div class="alert alert-danger">Lỗi khi tải tài nguyên: ' . $e->getMessage() . '</div>';
                }
                
                // Hàm xác định icon dựa trên loại file
                function getFileIcon($fileType) {
                    $fileType = strtolower($fileType);
                    switch ($fileType) {
                        case 'pdf': return 'fa-file-pdf-o';
                        case 'doc': case 'docx': return 'fa-file-word-o';
                        case 'xls': case 'xlsx': return 'fa-file-excel-o';
                        case 'ppt': case 'pptx': return 'fa-file-powerpoint-o';
                        case 'mp4': case 'avi': case 'mov': return 'fa-file-video-o';
                        case 'zip': case 'rar': return 'fa-file-archive-o';
                        case 'jpg': case 'png': case 'gif': return 'fa-file-image-o';
                        case 'php': case 'html': case 'js': case 'css': return 'fa-file-code-o';
                        default: return 'fa-file-o';
                    }
                }
                
                // Hàm xác định class màu cho panel dựa trên loại file
                function getPanelClass($fileType) {
                    $fileType = strtolower($fileType);
                    switch ($fileType) {
                        case 'pdf': return 'pdf';
                        case 'doc': case 'docx': return 'word';
                        case 'xls': case 'xlsx': return 'excel';
                        case 'ppt': case 'pptx': return 'powerpoint';
                        case 'mp4': case 'avi': case 'mov': return 'video';
                        case 'zip': case 'rar': return 'archive';
                        case 'jpg': case 'png': case 'gif': return 'image';
                        case 'php': case 'html': case 'js': case 'css': return 'code';
                        default: return 'default';
                    }
                }
                ?>
            </div>
        </div>
    </div>
</body>
</html>