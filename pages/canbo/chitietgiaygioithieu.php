<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';

require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['giay_id'])) {
    $_SESSION['giay_id'] = $_POST['giay_id'];
    $id = $_POST['giay_id'];
} else {
    $id = $_SESSION['giay_id'] ?? '';
}

$giay = null;
$tensinhvien = '';
$mssv = '';
$message = '';

// Lấy thông tin giấy giới thiệu và sinh viên
if ($id) {
    $stmt = $conn->prepare("
        SELECT g.*, s.Ten AS Tensinhvien, s.MSSV
        FROM giaygioithieu g
        LEFT JOIN sinhvien s ON g.Idsinhvien = s.ID_taikhoan
        WHERE g.ID = ?
    ");
    $stmt->execute([$id]);
    $giay = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($giay) {
        $tensinhvien = $giay['Tensinhvien'] ?? '';
        $mssv = $giay['MSSV'] ?? '';
    }
}

// Xử lý duyệt giấy giới thiệu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['duyet']) && $id) {
    // Lấy lại thông tin giấy để lấy mã số thuế và thông tin công ty
    $stmt = $conn->prepare("
        SELECT TenCty, MaSoThue, LinhVuc, Sdt, Email, DiaChi
        FROM giaygioithieu
        WHERE ID = ?
    ");
    $stmt->execute([$id]);
    $giayInfo = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($giayInfo) {
        // Kiểm tra công ty đã tồn tại chưa (theo MaSoThue)
        $stmtCheck = $conn->prepare("SELECT ID FROM congty WHERE MaSoThue = ?");
        $stmtCheck->execute([$giayInfo['MaSoThue']]);
        if (!$stmtCheck->fetch()) {
            // Nếu chưa có thì thêm vào bảng congty
            $stmtInsert = $conn->prepare("INSERT INTO congty (MaSoThue, TenCty, LinhVuc, Sdt, Email, DiaChi, TrangThai) VALUES (?, ?, ?, ?, ?, ?, 1)");
            $stmtInsert->execute([
                $giayInfo['MaSoThue'],
                $giayInfo['TenCty'],
                $giayInfo['LinhVuc'],
                $giayInfo['Sdt'],
                $giayInfo['Email'],
                $giayInfo['DiaChi']
            ]);
        }
    }

    // Cập nhật trạng thái giấy giới thiệu
    $stmt = $conn->prepare("UPDATE giaygioithieu SET TrangThai = 1 WHERE ID = ?");
    if ($stmt->execute([$id])) {
        $message = "Đã duyệt giấy giới thiệu thành công!";
        // Cập nhật lại dữ liệu sau khi duyệt
        $stmt = $conn->prepare("
            SELECT g.*, s.Ten AS Tensinhvien, s.MSSV
            FROM giaygioithieu g
            LEFT JOIN sinhvien s ON g.Idsinhvien = s.ID_taikhoan
            WHERE g.ID = ?
        ");
        $stmt->execute([$id]);
        $giay = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($giay) {
            $tensinhvien = $giay['Tensinhvien'] ?? '';
            $mssv = $giay['MSSV'] ?? '';
        }
    } else {
        $message = "Duyệt thất bại!";
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết giấy giới thiệu</title>
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php"; ?>
    <style>
        body {
            background: linear-gradient(135deg, #e3f0ff 0%, #f8fafc 100%);
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
            line-height: 1.6;
            color: #374151;
        }
        
        .detail-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .page-header h1 {
            color: #1e40af;
            font-size: 2rem;
            font-weight: 700;
            margin: 0 0 10px 0;
        }
        
        .student-info {
            background: #3b82f6;
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 20px;
            font-size: 1.1rem;
            font-weight: 500;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: none;
            font-weight: 500;
        }
        
        .alert-success {
            background: #dcfce7;
            color: #16a34a;
            border-left: 4px solid #16a34a;
        }
        
        .alert-danger {
            background: #fee2e2;
            color: #dc2626;
            border-left: 4px solid #dc2626;
        }
        
        .detail-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .card-header {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            padding: 20px;
            text-align: center;
        }
        
        .card-header h3 {
            margin: 0;
            font-size: 1.3rem;
            font-weight: 600;
        }
        
        .card-body {
            padding: 0;
        }
        
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .info-table tr {
            border-bottom: 1px solid #f3f4f6;
        }
        
        .info-table tr:last-child {
            border-bottom: none;
        }
        
        .info-table th {
            background: #f8fafc;
            padding: 15px 20px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            width: 30%;
            border-right: 1px solid #e5e7eb;
        }
        
        .info-table td {
            padding: 15px 20px;
            color: #6b7280;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }
        
        .status-pending {
            background: #fef3c7;
            color: #d97706;
        }
        
        .status-approved {
            background: #dcfce7;
            color: #16a34a;
        }
        
        .status-rejected {
            background: #fee2e2;
            color: #dc2626;
        }
        
        .action-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 30px;
            gap: 15px;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }
        
        .btn-default {
            background: #f3f4f6;
            color: #374151;
            border: 1px solid #d1d5db;
        }
        
        .btn-default:hover {
            background: #e5e7eb;
            transform: translateY(-1px);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }
        
        .btn-success:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
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
        
        .not-found {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .not-found i {
            font-size: 4rem;
            color: #d1d5db;
            margin-bottom: 20px;
        }
        
        .not-found h3 {
            color: #374151;
            margin-bottom: 10px;
        }
        
        .not-found p {
            color: #6b7280;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .detail-container {
                margin: 20px auto;
                padding: 0 15px;
            }
            
            .action-bar {
                flex-direction: column;
                align-items: stretch;
            }
            
            .info-table th {
                width: 40%;
                padding: 12px 15px;
            }
            
            .info-table td {
                padding: 12px 15px;
            }
        }
        
        @media (max-width: 480px) {
            .info-table th,
            .info-table td {
                display: block;
                width: 100%;
                border: none;
                padding: 10px 15px;
            }
            
            .info-table th {
                background: #f8fafc;
                font-weight: 600;
                color: #374151;
            }
            
            .info-table td {
                background: white;
                margin-bottom: 1px;
                color: #6b7280;
            }
        }
    </style>
</head>
<body>
    <div class="detail-container">
        <div class="page-header">
            <h1>
                <i class="fa fa-file-text"></i>
                Chi tiết giấy giới thiệu
            </h1>
        </div>
        
        <?php if ($tensinhvien || $mssv): ?>
            <div class="student-info">
                <i class="fa fa-user"></i>
                <?php echo htmlspecialchars($tensinhvien); ?>
                <?php if ($mssv): ?>
                    (<?php echo htmlspecialchars($mssv); ?>)
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($message): ?>
            <div class="alert alert-success">
                <i class="fa fa-check-circle"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($giay): ?>
            <div class="detail-card">
                <div class="card-header">
                    <h3><?php echo htmlspecialchars($giay['TenCty']); ?></h3>
                </div>
                <div class="card-body">
                    <table class="info-table">
                        <tr>
                            <th><i class="fa fa-building"></i> Tên công ty</th>
                            <td><?php echo htmlspecialchars($giay['TenCty']); ?></td>
                        </tr>
                        <tr>
                            <th><i class="fa fa-barcode"></i> Mã số thuế</th>
                            <td><?php echo htmlspecialchars($giay['MaSoThue']); ?></td>
                        </tr>
                        <tr>
                            <th><i class="fa fa-map-marker"></i> Địa chỉ</th>
                            <td><?php echo htmlspecialchars($giay['DiaChi']); ?></td>
                        </tr>
                        <tr>
                            <th><i class="fa fa-industry"></i> Lĩnh vực</th>
                            <td><?php echo htmlspecialchars($giay['LinhVuc']); ?></td>
                        </tr>
                        <tr>
                            <th><i class="fa fa-phone"></i> SĐT</th>
                            <td><?php echo htmlspecialchars($giay['Sdt']); ?></td>
                        </tr>
                        <tr>
                            <th><i class="fa fa-envelope"></i> Email</th>
                            <td><?php echo htmlspecialchars($giay['Email']); ?></td>
                        </tr>
                        <tr>
                            <th><i class="fa fa-info-circle"></i> Trạng thái</th>
                            <td>
                                <?php if ($giay['TrangThai'] == 0): ?>
                                    <span class="status-badge status-pending">
                                        <i class="fa fa-clock-o"></i>
                                        Đang chờ duyệt
                                    </span>
                                <?php elseif ($giay['TrangThai'] == 1): ?>
                                    <span class="status-badge status-approved">
                                        <i class="fa fa-check"></i>
                                        Đã duyệt
                                    </span>
                                <?php else: ?>
                                    <span class="status-badge status-rejected">
                                        <i class="fa fa-times"></i>
                                        Từ chối
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <?php if ($giay['TrangThai'] == 0): ?>
                <form method="post" id="approvalForm">
                    <input type="hidden" name="giay_id" value="<?php echo htmlspecialchars($giay['ID']); ?>">
                    <div class="action-bar">
                        <a href="/datn/pages/canbo/quanlygiaygioithieu" class="btn btn-default">
                            <i class="fa fa-arrow-left"></i>
                            Quay lại
                        </a>
                        <button type="submit" name="duyet" class="btn btn-success" id="approveBtn">
                            <i class="fa fa-check"></i>
                            Duyệt giấy giới thiệu
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <div class="action-bar">
                    <a href="/datn/pages/canbo/quanlygiaygioithieu" class="btn btn-default">
                        <i class="fa fa-arrow-left"></i>
                        Quay lại
                    </a>
                    <?php if ($giay['TrangThai'] == 1): ?>
                        <button type="button" class="btn btn-primary" onclick="printLetter()">
                            <i class="fa fa-print"></i>
                            In giấy giới thiệu
                        </button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="not-found">
                <i class="fa fa-file-text"></i>
                <h3>Không tìm thấy giấy giới thiệu</h3>
                <p>Giấy giới thiệu bạn tìm kiếm không tồn tại hoặc đã bị xóa.</p>
                <a href="/datn/pages/canbo/quanlygiaygioithieu" class="btn btn-primary">
                    <i class="fa fa-arrow-left"></i>
                    Quay lại danh sách
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const approvalForm = document.getElementById('approvalForm');
            if (approvalForm) {
                approvalForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    if (confirm('Bạn có chắc chắn muốn duyệt giấy giới thiệu này?\n\nViệc duyệt sẽ thêm công ty vào hệ thống và không thể hoàn tác.')) {
                        // Show loading state
                        const submitBtn = document.getElementById('approveBtn');
                        const originalText = submitBtn.innerHTML;
                        submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Đang xử lý...';
                        submitBtn.disabled = true;
                        
                        // Submit form
                        this.submit();
                    }
                });
            }
        });
        
        function printLetter() {
            // Lấy ID giấy giới thiệu từ form
            const letterId = <?php echo json_encode($giay['ID'] ?? 0); ?>;
            if (letterId) {
                const printUrl = '/datn/pages/canbo/print_letter_template.php?id=' + letterId;
                window.open(printUrl, '_blank', 'width=800,height=600,scrollbars=yes,resizable=yes');
            } else {
                alert('Không thể in giấy giới thiệu này!');
            }
        }
    </script>
</body>
</html>