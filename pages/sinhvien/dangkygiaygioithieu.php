<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";
require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';

// Kiểm tra đăng nhập và quyền truy cập
if (!isset($_SESSION['user_id'])) {
    header('Location: /datn/login.php');
    exit();
}

// Utility functions
function shortAddress($address, $max = 50) {
    $address = trim($address);
    if (mb_strlen($address, 'UTF-8') > $max) {
        return mb_substr($address, 0, $max, 'UTF-8') . '...';
    }
    return $address;
}

function getLettersByStatus() {
    global $conn;
    
    try {
        // Optimized query - lấy tất cả trong một lần và group theo status
        $stmt = $conn->prepare("
                SELECT 
                    g.ID, g.TenCty, g.DiaChi, g.Idsinhvien, g.TrangThai, g.id_dot, 
                    g.id_nguoinhan, g.ngay_nhan, g.ghi_chu, g.MaSoThue,
                    s.Ten AS Tensinhvien, s.MSSV,
                    sn.Ten AS TenNguoiNhan, sn.MSSV AS MSSVNguoiNhan,
                    d.TenDot, d.ThoiGianBatDau, d.ThoiGianKetThuc
                FROM giaygioithieu g
                LEFT JOIN sinhvien s ON g.Idsinhvien = s.ID_taikhoan
                LEFT JOIN sinhvien sn ON g.id_nguoinhan = sn.ID_taikhoan
                LEFT JOIN dotthuctap d ON g.id_dot = d.ID
                ORDER BY g.TrangThai ASC, g.MaSoThue, g.TenCty, g.ID DESC
        ");
        $stmt->execute();
        $allLetters = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Group by status
        $result = [
            'pending' => [],   // TrangThai = 0
            'approved' => [],  // TrangThai = 1  
            'printed' => [],   // TrangThai = 2
            'waiting' => [],   // TrangThai = 4 - Chờ lấy
            'received' => []   // TrangThai = 3
        ];
        
        foreach ($allLetters as $letter) {
            switch ($letter['TrangThai']) {
                case 0:
                    $result['pending'][] = $letter;
                    break;
                case 1:
                    $result['approved'][] = $letter;
                    break;
                case 2:
                    $result['printed'][] = $letter;
                    break;
                case 3:
                    $result['received'][] = $letter;
                    break;
                case 4:
                    $result['waiting'][] = $letter;
                    break;
            }
        }
        
        return $result;
    } catch (PDOException $e) {
        error_log("Database error in getLettersByStatus: " . $e->getMessage());
        return ['pending' => [], 'approved' => [], 'printed' => [], 'waiting' => [], 'received' => []];
    }
}

// Lấy dữ liệu
$letters = getLettersByStatus();
$pendingList = $letters['pending'];
$approvedList = $letters['approved'];
$printedList = $letters['printed'];
$waitingList = $letters['waiting'];
$receivedList = $letters['received'];

// Phân chia danh sách đã duyệt thành 2 phần
function categorizeApprovedLetters($approvedList) {
    // Đếm số lượng sinh viên theo mã số thuế
    $taxCodeCount = [];
    foreach ($approvedList as $letter) {
        $taxCode = $letter['MaSoThue'] ?? '';
        if (!isset($taxCodeCount[$taxCode])) {
            $taxCodeCount[$taxCode] = 0;
        }
        $taxCodeCount[$taxCode]++;
    }
    
    $individualLetters = []; // Sinh viên đăng ký riêng lẻ
    $groupedLetters = [];    // Sinh viên cùng công ty
    
    foreach ($approvedList as $letter) {
        $taxCode = $letter['MaSoThue'] ?? '';
        
        if ($taxCodeCount[$taxCode] === 1) {
            // Chỉ có 1 sinh viên với mã số thuế này -> riêng lẻ
            $individualLetters[] = $letter;
        } else {
            // Có nhiều sinh viên với mã số thuế này -> nhóm
            if (!isset($groupedLetters[$taxCode])) {
                $groupedLetters[$taxCode] = [];
            }
            $groupedLetters[$taxCode][] = $letter;
        }
    }
    
    return [
        'individual' => $individualLetters,
        'grouped' => $groupedLetters
    ];
}

$categorizedApproved = categorizeApprovedLetters($approvedList);
$individualLetters = $categorizedApproved['individual'];
$groupedLetters = $categorizedApproved['grouped'];

// Hàm gộp giấy theo công ty để in
function groupLettersByCompany($letters) {
    $grouped = [];
    foreach ($letters as $letter) {
        $key = $letter['TenCty'] . '|' . $letter['id_dot'];
        if (!isset($grouped[$key])) {
            $grouped[$key] = [
                'company_info' => $letter,
                'students' => []
            ];
        }
        $grouped[$key]['students'][] = $letter;
    }
    return $grouped;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <!-- Force refresh timestamp: <?php echo time(); ?> -->
    <title>Quản lý giấy giới thiệu</title>
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php"; ?>
    <style>
        /* === RESET & BASE === */
        * {
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #e3f0ff 0%, #f8fafc 100%);
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
            line-height: 1.6;
            color: #374151;
        }
        
        /* === LAYOUT === */
        #page-wrapper {
            padding: 20px;
            min-height: 100vh;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .page-header h1 {
            font-size: 2.25rem;
            font-weight: 700;
            color: #1e40af;
            margin: 0;
            text-shadow: 0 2px 4px rgba(30, 64, 175, 0.1);
        }
        
        /* === FILTER BAR === */
        .filter-bar {
            background: white;      
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .status-toggle {
            display: flex;
            gap: 5px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #f9fafb;
            padding: 4px;
        }
        
        .status-btn {
            padding: 10px 16px;
            border: none;
            background: transparent;
            color: #6b7280;
            font-weight: 500;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
        }
        
        .status-btn:hover {
            background: #f3f4f6;
            color: #374151;
        }
        
        .status-btn.active {
            background: #3b82f6;
            color: white;
            box-shadow: 0 2px 4px rgba(59, 130, 246, 0.2);
        }
        
        .status-btn .badge {
            background: #6b7280;
            color: white;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 10px;
            margin-left: 5px;
        }
        
        .status-btn.active .badge {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .search-input {
            flex: 1;
            min-width: 250px;
            padding: 10px 16px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            background: #f9fafb;
            transition: all 0.2s ease;
        }
        
        .search-input:focus {
            outline: none;
            border-color: #3b82f6;
            background: white;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-left: auto;
        }
        
        .btn {
            padding: 10px 16px;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
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
        
        .btn-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }
        
        .btn-success:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        
        .btn.disabled {
            opacity: 0.6;
            cursor: not-allowed;
            pointer-events: none;
        }
        
        /* === CARDS GRID === */
        .cards-container {
            margin-bottom: 30px;
        }
        
        .status-section {
            display: block; /* Change from grid to block */
        }
        
        /* Default grid layout for other status sections */
        .status-section:not(#approved-cards) {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }
        
        .letter-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border: 2px solid transparent;
            position: relative;
            opacity: 0;
            transform: translateY(10px);
        }
        
        .letter-card.fade-in {
            opacity: 1;
            transform: translateY(0);
        }
        
        .letter-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            border-color: #3b82f6;
            cursor: pointer;
        }
        
        .letter-card.has-checkbox {
            padding-left: 45px;
            cursor: pointer;
            position: relative;
        }
        
        .letter-card.has-checkbox::before {
            content: "👆 Click để chọn";
            position: absolute;
            top: -30px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(59, 130, 246, 0.9);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            opacity: 0;
            transition: opacity 0.2s ease;
            pointer-events: none;
            z-index: 100;
        }
        
        .letter-card.has-checkbox:hover::before {
            opacity: 1;
        }
        
        .letter-card.has-checkbox.selected::before {
            content: "✅ Đã chọn";
            background: rgba(16, 185, 129, 0.9);
        }
        
        .letter-card.has-checkbox:hover {
            background: #f0f9ff;
        }
        
        .letter-card.clickable:hover {
            background: #f8fafc;
            cursor: pointer;
        }

        /* Section Headers - Compact */
        .section-header {
            margin: 15px 0 10px 0;
            padding: 12px 18px;
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            border-radius: 8px;
            color: white;
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.2);
            border-left: 3px solid #10b981;
        }

        .section-header h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-header h3 i {
            font-size: 16px;
            color: #fbbf24;
        }

        .badge-count {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 8px;
        }

        /* === SUB-TABS FOR APPROVED === */
        .sub-tabs {
            display: none;
            background: white;
            border-radius: 8px;
            padding: 15px 20px 0 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .sub-tabs.show {
            display: block;
        }

        .sub-toggle {
            display: flex;
            gap: 4px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            background: #f9fafb;
            padding: 3px;
            width: fit-content;
        }

        .sub-btn {
            padding: 8px 16px;
            border: none;
            background: transparent;
            color: #6b7280;
            font-weight: 500;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 14px;
            position: relative;
        }

        .sub-btn:hover {
            background: #f3f4f6;
            color: #374151;
        }

        .sub-btn.active {
            background: #10b981;
            color: white;
            box-shadow: 0 1px 3px rgba(16, 185, 129, 0.3);
        }

        .sub-btn .badge {
            background: #9ca3af;
            color: white;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 8px;
            margin-left: 6px;
        }

        .sub-btn.active .badge {
            background: rgba(255, 255, 255, 0.25);
        }

        /* Sub-sections */
        .sub-section {
            transition: all 0.3s ease;
        }

        .sub-section.hidden {
            display: none;
        }

        /* Individual Letters Grid - Side by Side Layout */
        .individual-letters-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
            align-items: start;
        }

        /* Company Groups Grid - Side by Side Layout */
        .grouped-letters-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            align-items: start;
        }

        /* Individual Letter Card - Fixed Height with Scroll */
        #individual-section .letter-card {
            display: flex;
            flex-direction: column;
            padding: 20px;
            height: 300px; /* Fixed height for all cards */
            overflow: hidden;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        /* Card Content Area with Scroll */
        #individual-section .card-content {
            flex: 1;
            overflow-y: auto;
            padding-right: 5px;
            margin-bottom: 15px;
        }

        /* Custom Scrollbar for card content */
        #individual-section .card-content::-webkit-scrollbar {
            width: 6px;
        }

        #individual-section .card-content::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        #individual-section .card-content::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }

        #individual-section .card-content::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        #individual-section .letter-card .card-header {
            flex-shrink: 0;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f3f4f6;
        }

        #individual-section .letter-card .company-name {
            font-size: 18px;
            margin-bottom: 8px;
            font-weight: 700;
            color: #1f2937;
            line-height: 1.3;
        }

        #individual-section .letter-card .company-address {
            font-size: 14px;
            color: #6b7280;
            line-height: 1.4;
        }

        #individual-section .letter-card .student-info {
            flex-shrink: 0;
            margin-bottom: 15px;
            font-size: 14px;
            color: #3b82f6;
            font-weight: 500;
        }

        #individual-section .letter-card .card-actions {
            flex-shrink: 0;
            display: flex;
            gap: 10px;
            margin-top: auto;
            padding-top: 15px;
            border-top: 1px solid #f3f4f6;
        }

        #individual-section .letter-card .status-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            z-index: 10;
        }

        #individual-section .letter-card.has-checkbox {
            padding-left: 45px;
            position: relative;
        }

        /* Hover effect for fixed height cards */
        #individual-section .letter-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            border-color: #3b82f6;
        }

        /* Responsive for individual cards */
        @media (max-width: 768px) {
            .individual-letters-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .grouped-letters-container {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            #individual-section .letter-card {
                height: 250px; /* Smaller height on mobile */
            }
        }

        /* Company Groups - Compact for 2-column layout */
        .company-group {
            margin-bottom: 20px;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            overflow: hidden;
            background: white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            height: fit-content; /* Change back to fit-content */
        }

        .company-group-header {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .company-info h4 {
            margin: 0 0 4px 0;
            font-size: 16px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .company-info h4::before {
            content: "🏢";
            font-size: 14px;
        }

        .company-details {
            margin: 0;
            opacity: 0.9;
            font-size: 13px;
            line-height: 1.4;
        }

        .group-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .student-count {
            background: rgba(255, 255, 255, 0.2);
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .btn-outline-light {
            background: transparent;
            border: 1px solid rgba(255, 255, 255, 0.6);
            color: white;
            font-weight: 500;
            transition: all 0.2s ease;
            font-size: 12px;
            padding: 6px 10px;
        }

        .btn-outline-light:hover {
            background: rgba(255, 255, 255, 0.15);
            border-color: white;
            color: white;
        }

        .btn-light {
            background: rgba(255, 255, 255, 0.85);
            border: 1px solid transparent;
            color: #047857;
            font-weight: 500;
            transition: all 0.2s ease;
            font-size: 12px;
            padding: 6px 10px;
        }

        .btn-light:hover {
            background: white;
            color: #065f46;
        }

        .company-students-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 12px;
            padding: 15px;
            background: #f8fafc;
        }

        /* Compact Letter Cards - Compact */
        .letter-card.compact {
            padding: 15px;
            min-height: auto;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            transition: all 0.2s ease;
            position: relative;
        }

        .letter-card.compact:hover {
            border-color: #3b82f6;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.1);
        }

        .letter-card.compact.selected {
            border-color: #10b981;
            background: #f0fdf4;
            box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.1);
        }

        .student-info.compact {
            font-size: 14px;
            margin: 10px 0;
            line-height: 1.5;
        }

        .student-info.compact strong {
            color: #1f2937;
            font-weight: 600;
        }

        .card-actions.compact {
            margin-top: 12px;
            display: flex;
            gap: 6px;
        }

        .btn.btn-xs {
            padding: 4px 8px;
            font-size: 11px;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .btn.btn-xs:hover {
            transform: translateY(-1px);
        }

        /* Empty Sections - Compact */
        .empty-section {
            text-align: center;
            padding: 30px 20px;
            color: #6b7280;
            background: #f9fafb;
            border-radius: 8px;
            border: 1px dashed #d1d5db;
            margin: 15px 0;
        }

        .empty-section i {
            font-size: 36px;
            margin-bottom: 12px;
            opacity: 0.4;
            color: #9ca3af;
        }

        .empty-section p {
            margin: 0 0 5px 0;
            font-size: 14px;
            font-weight: 500;
        }

        .empty-section small {
            font-size: 12px;
            color: #9ca3af;
        }
        
        /* Responsive improvements */
        @media (max-width: 768px) {
            .company-students-grid {
                grid-template-columns: 1fr;
                gap: 10px;
                padding: 12px;
            }
            
            .company-group-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
                padding: 12px 15px;
            }
            
            .group-actions {
                width: 100%;
                justify-content: space-between;
            }
            
            .company-info h4 {
                font-size: 14px;
            }
            
            .company-details {
                font-size: 12px;
            }
            
            .section-header {
                margin: 10px 0 8px 0;
                padding: 10px 15px;
            }
            
            .section-header h3 {
                font-size: 14px;
            }
            
            /* Override for approved cards sub-sections */
            .individual-letters-grid,
            .grouped-letters-container {
                grid-template-columns: 1fr !important;
            }
        }
        
        @media (max-width: 480px) {
            .company-group-header {
                padding: 10px 12px;
            }
            
            .company-info h4 {
                font-size: 13px;
            }
            
            .group-actions {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
                width: 100%;
            }
            
            .btn.btn-xs {
                font-size: 10px;
                padding: 3px 6px;
            }
        }
        
        
        .card-header {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .company-name {
            font-size: 18px;
            font-weight: 700;
            color: #1f2937;
            margin: 0 0 8px 0;
            line-height: 1.3;
        }
        
        .company-address {
            color: #6b7280;
            font-size: 14px;
            line-height: 1.4;
        }
        
        .student-info {
            color: #3b82f6;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 15px;
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
        
        .status-pending {
            background: #fef3c7;
            color: #d97706;
        }
        
        .status-approved {
            background: #dcfce7;
            color: #16a34a;
        }
        
        .status-printed {
            background: #e0e7ff;
            color: #4f46e5;
        }
        
        .status-received {
            background: #f3e8ff;
            color: #7c3aed;
        }
        
        .status-waiting {
            background: #fef3c7;
            color: #f59e0b;
        }
        
        .received-note {
            background: #f8fafc;
            border-left: 3px solid #3b82f6;
            padding: 8px 12px;
            margin: 10px 0;
            border-radius: 4px;
            font-size: 13px;
        }
        
        /* === MODAL STYLES === */
        .modal-content {
            border: none;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }
        
        .modal-header {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            border-radius: 12px 12px 0 0;
            border: none;
        }
        
        .modal-title {
            font-weight: 600;
        }
        
        .close {
            color: white;
            opacity: 0.8;
        }
        
        .close:hover {
            color: white;
            opacity: 1;
        }
        
        .student-filter {
            position: sticky;
            top: 0;
            background: white;
            z-index: 10;
        }
        
        .card-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }
        
        /* === SELECTION STYLES === */
        .selection-controls {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            display: none;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .selection-controls.show {
            display: flex;
        }
        
        .selection-info {
            color: #374151;
            font-weight: 500;
        }
        
        .selection-actions {
            display: flex;
            gap: 10px;
            margin-left: auto;
        }
        
        .card-checkbox {
            position: absolute;
            top: 15px;
            left: 15px;
            width: 20px;
            height: 20px;
            accent-color: #3b82f6;
            cursor: pointer;
            z-index: 10;
        }
        
        .letter-card.selected {
            border: 2px solid #3b82f6;
            background: #f0f9ff;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
        }
        
        .letter-card.selected:hover {
            background: #e0f2fe;
        }
        
        .letter-card.has-checkbox {
            padding-left: 45px;
        }
        
        /* === EMPTY STATE === */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
            grid-column: 1 / -1;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #d1d5db;
        }
        
        .empty-state h3 {
            margin: 0 0 10px 0;
            color: #4b5563;
        }
        
        /* === RESPONSIVE === */
        @media (max-width: 768px) {
            #page-wrapper { padding: 15px; }
            .filter-bar { 
                flex-direction: column; 
                align-items: stretch;
                gap: 15px;
            }
            .action-buttons { 
                margin-left: 0;
                justify-content: center;
            }
            .status-section { 
                grid-template-columns: 1fr; 
                gap: 15px;
            }
            .page-header h1 { font-size: 1.8rem; }
        }
        
        @media (max-width: 480px) {
            .status-toggle { 
                flex-direction: column; 
                gap: 5px;
            }
            .status-btn { 
                padding: 12px 16px; 
                text-align: center;
            }
            .letter-card {
                padding: 15px;
            }
        }
        
        /* === PRINT STYLES === */
        @media print {
            .print-controls, .warning-banner {
                display: none !important;
            }
            
            body {
                background: white !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                font-size: 12pt;
            }
        }
        
        /* === LOADING & ANIMATIONS === */
        .fade-in {
            animation: fadeIn 0.3s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .hidden { display: none !important; }
        .loading { opacity: 0.6; pointer-events: none; }
        
        /* Pagination Styles */
        .pagination-wrapper {
            margin: 20px 0;
            text-align: center;
        }
        
        .pagination-controls {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .page-numbers {
            display: flex;
            gap: 5px;
        }
        
        .page-btn {
            min-width: 40px;
            height: 32px;
            border-radius: 6px;
            border: 1px solid #d1d5db;
            background: white;
            color: #374151;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .page-btn:hover {
            background: #f3f4f6;
            border-color: #9ca3af;
            transform: translateY(-1px);
        }
        
        .page-btn.active {
            background: #3b82f6;
            border-color: #3b82f6;
            color: white;
        }
        
        .pagination-info {
            color: #6b7280;
            font-size: 14px;
            margin-top: 5px;
        }
        
        /* Companies Container */
        #companies-container {
            min-height: 200px;
        }
        
        @media (max-width: 768px) {
            .pagination-controls {
                flex-direction: column;
                gap: 15px;
            }
            
            .page-numbers {
                flex-wrap: wrap;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div id="wrapper">
       <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/slidebar_Sinhvien.php"; ?>
        
        <div id="page-wrapper">
            <div class="container-fluid">
                <div class="page-header">
                    <h1>
                        <i class="fa fa-file-text"></i>
                        Quản lý giấy giới thiệu
                    </h1>
                </div>
                
                <!-- Filter Bar -->
                <div class="filter-bar">
                    <div class="status-toggle">
                        <button class="status-btn active" data-status="approved" id="btnapp">
                            <i class="fa fa-check-circle"></i>
                            Đã duyệt
                            <span class="badge"><?php echo count($approvedList); ?></span>
                        </button>
                        <button class="status-btn" data-status="pending" id="btnpen">
                            <i class="fa fa-clock-o"></i>
                            Chưa duyệt
                            <span class="badge"><?php echo count($pendingList); ?></span>
                        </button>
                        <button class="status-btn" data-status="printed" id="btnprinted">
                            <i class="fa fa-print"></i>
                            Đã in
                            <span class="badge"><?php echo count($printedList); ?></span>
                        </button>
                        <button class="status-btn" data-status="waiting" id="btnwaiting">
                            <i class="fa fa-hourglass-half"></i>
                            Chờ lấy
                            <span class="badge"><?php echo count($waitingList); ?></span>
                        </button>
                        <button class="status-btn" data-status="received" id="btnreceived">
                            <i class="fa fa-check-square"></i>
                            Đã nhận
                            <span class="badge"><?php echo count($receivedList); ?></span>
                        </button>
                    </div>
                    
                    <input type="text" class="search-input" id="searchInput" 
                           placeholder="🔍 Tìm kiếm theo MSSV, tên sinh viên hoặc tên công ty...">
                    
                    <div class="action-buttons">
                        <button id="printGroupedBtn" class="btn btn-primary">
                            <i class="fa fa-print"></i>
                            In theo công ty
                        </button>
                        <button id="printAllBtn" class="btn btn-primary">
                            <i class="fa fa-print"></i>
                            In tất cả
                        </button>
                    </div>
                </div>

                <!-- Selection Controls -->
                <div class="selection-controls" id="selectionControls">
                    <div class="selection-info">
                        <i class="fa fa-check-square-o"></i>
                        Đã chọn: <span id="selectedCount">0</span> giấy
                    </div>
                    <div class="selection-actions">
                        <button id="selectAllBtn" class="btn btn-primary btn-sm">
                            <i class="fa fa-check-square"></i>
                            Chọn tất cả
                        </button>
                        <button id="clearSelectionBtn" class="btn btn-secondary btn-sm">
                            <i class="fa fa-times"></i>
                            Bỏ chọn
                        </button>
                        <button id="printSelectedBtn" class="btn btn-success btn-sm">
                            <i class="fa fa-print"></i>
                            In đã chọn
                        </button>
                        <button id="markSelectedBtn" class="btn btn-warning btn-sm" style="display: none;">
                            <i class="fa fa-edit"></i>
                            Cập nhật đã chọn
                        </button>
                    </div>
                </div>

                <!-- Sub-tabs for Approved Letters -->
                <div class="sub-tabs" id="approvedSubTabs">
                    <div class="sub-toggle">
                        <button class="sub-btn active" data-sub-status="individual" id="btnIndividual">
                            <i class="fa fa-user"></i>
                            Riêng lẻ
                            <span class="badge"><?php echo count($individualLetters); ?></span>
                        </button>
                        <button class="sub-btn" data-sub-status="grouped" id="btnGrouped">
                            <i class="fa fa-users"></i>
                            Cùng công ty
                            <span class="badge"><?php echo array_sum(array_map('count', $groupedLetters)); ?></span>
                        </button>
                    </div>
                </div>

                <!-- Cards Container -->
                <div class="cards-container" id="cardsContainer">
                    <!-- Approved Letters -->
                    <div id="approved-cards" class="status-section">
                        <!-- Individual Letters Sub-section -->
                        <div id="individual-section" class="sub-section">
                            <div class="individual-letters-grid">
                            <?php if (count($individualLetters) > 0): ?>
                                <?php foreach ($individualLetters as $letter): ?>
                                    <div class="letter-card fade-in has-checkbox" 
                                         data-id="<?php echo $letter['ID']; ?>"
                                         data-status="approved"
                                         data-type="individual"
                                         data-mssv="<?php echo htmlspecialchars($letter['MSSV']); ?>"
                                         data-student="<?php echo htmlspecialchars($letter['Tensinhvien']); ?>"
                                         data-company="<?php echo htmlspecialchars($letter['TenCty']); ?>"
                                         onclick="toggleCardSelectionByClick(this)">
                                        
                                        <input type="checkbox" class="card-checkbox" 
                                               data-id="<?php echo $letter['ID']; ?>"
                                               onclick="event.stopPropagation()">
                                        
                                        <div class="status-badge status-approved">
                                            <i class="fa fa-check"></i> Đã duyệt
                                        </div>
                                        
                                        <div class="card-content">
                                            <div class="card-header">
                                                <h3 class="company-name"><?php echo htmlspecialchars($letter['TenCty']); ?></h3>
                                                <p class="company-address"><?php echo htmlspecialchars(shortAddress($letter['DiaChi'])); ?></p>
                                                <?php if ($letter['MaSoThue']): ?>
                                                    <p class="tax-code"><i class="fa fa-barcode"></i> MST: <?php echo htmlspecialchars($letter['MaSoThue']); ?></p>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="student-info">
                                                <i class="fa fa-user"></i>
                                                <?php echo htmlspecialchars($letter['Tensinhvien']); ?> 
                                                (<?php echo htmlspecialchars($letter['MSSV']); ?>)
                                                <?php if ($letter['TenDot']): ?>
                                                    <br><i class="fa fa-calendar"></i>
                                                    <small>Đợt: <?php echo htmlspecialchars($letter['TenDot']); ?></small>
                                                    <?php if ($letter['ThoiGianBatDau'] && $letter['ThoiGianKetThuc']): ?>
                                                        <br><small style="color: #6b7280;">
                                                            <?php echo date('d/m/Y', strtotime($letter['ThoiGianBatDau'])); ?> - 
                                                            <?php echo date('d/m/Y', strtotime($letter['ThoiGianKetThuc'])); ?>
                                                        </small>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="card-actions">
                                            <button type="button" class="btn btn-sm btn-primary" onclick="event.stopPropagation(); viewDetails(<?php echo $letter['ID']; ?>)">
                                                <i class="fa fa-eye"></i> Chi tiết
                                            </button>
                                            <button type="button" class="btn btn-sm btn-success" onclick="event.stopPropagation(); printLetter(<?php echo $letter['ID']; ?>)">
                                                <i class="fa fa-print"></i> In
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-section">
                                    <i class="fa fa-user-plus"></i>
                                    <p>Chưa có đăng ký riêng lẻ</p>
                                    <small>Sinh viên đăng ký công ty riêng sẽ hiển thị ở đây</small>
                                </div>
                            <?php endif; ?>
                        </div>
                        </div>

                        <!-- Grouped Letters Sub-section -->
                        <div id="grouped-section" class="sub-section hidden">
                            <div id="companies-container" class="grouped-letters-container">
                                <!-- Companies will be loaded here via JavaScript pagination -->
                            </div>
                            
                            <!-- Pagination container -->
                            <div id="companies-pagination">
                                <!-- Pagination will be loaded here via JavaScript -->
                            </div>
                        </div>
                    </div>

                    <!-- Pending Letters -->
                    <div id="pending-cards" class="status-section hidden">
                        <?php if (count($pendingList) > 0): ?>
                            <?php foreach ($pendingList as $letter): ?>
                                <div class="letter-card fade-in" 
                                     data-id="<?php echo $letter['ID']; ?>"
                                     data-status="pending"
                                     data-mssv="<?php echo htmlspecialchars($letter['MSSV']); ?>"
                                     data-student="<?php echo htmlspecialchars($letter['Tensinhvien']); ?>"
                                     data-company="<?php echo htmlspecialchars($letter['TenCty']); ?>">
                                    
                                    <div class="status-badge status-pending">
                                        <i class="fa fa-clock-o"></i> Chưa duyệt
                                    </div>
                                    
                                    <div class="card-header">
                                        <h3 class="company-name"><?php echo htmlspecialchars($letter['TenCty']); ?></h3>
                                        <p class="company-address"><?php echo htmlspecialchars(shortAddress($letter['DiaChi'])); ?></p>
                                    </div>
                                    
                                    <div class="student-info">
                                        <i class="fa fa-user"></i>
                                        <?php echo htmlspecialchars($letter['Tensinhvien']); ?> 
                                        (<?php echo htmlspecialchars($letter['MSSV']); ?>)
                                        <?php if ($letter['TenDot']): ?>
                                            <br><i class="fa fa-calendar"></i>
                                            <small>Đợt: <?php echo htmlspecialchars($letter['TenDot']); ?></small>
                                            <?php if ($letter['ThoiGianBatDau'] && $letter['ThoiGianKetThuc']): ?>
                                                <br><small style="color: #6b7280;">
                                                    <?php echo date('d/m/Y', strtotime($letter['ThoiGianBatDau'])); ?> - 
                                                    <?php echo date('d/m/Y', strtotime($letter['ThoiGianKetThuc'])); ?>
                                                </small>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="card-actions">
                                        <button type="button" class="btn btn-sm btn-primary" onclick="event.stopPropagation(); viewDetails(<?php echo $letter['ID']; ?>)">
                                            <i class="fa fa-eye"></i> Chi tiết
                                        </button>
                                        <button type="button" class="btn btn-sm btn-success" onclick="event.stopPropagation(); approveLetter(<?php echo $letter['ID']; ?>)">
                                            <i class="fa fa-check"></i> Duyệt
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fa fa-clock-o"></i>
                                <h3>Không có giấy giới thiệu chưa duyệt</h3>
                                <p>Tất cả giấy giới thiệu đã được xử lý</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Printed Letters -->
                    <div id="printed-cards" class="status-section hidden">
                        <?php if (count($printedList) > 0): ?>
                            <?php foreach ($printedList as $letter): ?>
                                <div class="letter-card fade-in has-checkbox" 
                                     data-id="<?php echo $letter['ID']; ?>"
                                     data-status="printed"
                                     data-mssv="<?php echo htmlspecialchars($letter['MSSV']); ?>"
                                     data-student="<?php echo htmlspecialchars($letter['Tensinhvien']); ?>"
                                     data-company="<?php echo htmlspecialchars($letter['TenCty']); ?>"
                                     onclick="toggleCardSelectionByClick(this)">
                                    
                                    <input type="checkbox" class="card-checkbox" 
                                           data-id="<?php echo $letter['ID']; ?>"
                                           onclick="event.stopPropagation()">
                                    
                                    <div class="status-badge status-printed">
                                        <i class="fa fa-print"></i> Đã in
                                    </div>
                                    
                                    <div class="card-header">
                                        <h3 class="company-name"><?php echo htmlspecialchars($letter['TenCty']); ?></h3>
                                        <p class="company-address"><?php echo htmlspecialchars(shortAddress($letter['DiaChi'])); ?></p>
                                    </div>
                                    
                                    <div class="student-info">
                                        <i class="fa fa-user"></i>
                                        <?php echo htmlspecialchars($letter['Tensinhvien']); ?> 
                                        (<?php echo htmlspecialchars($letter['MSSV']); ?>)
                                        <?php if ($letter['TenDot']): ?>
                                            <br><i class="fa fa-calendar"></i>
                                            <small>Đợt: <?php echo htmlspecialchars($letter['TenDot']); ?></small>
                                            <?php if ($letter['ThoiGianBatDau'] && $letter['ThoiGianKetThuc']): ?>
                                                <br><small style="color: #6b7280;">
                                                    <?php echo date('d/m/Y', strtotime($letter['ThoiGianBatDau'])); ?> - 
                                                    <?php echo date('d/m/Y', strtotime($letter['ThoiGianKetThuc'])); ?>
                                                </small>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="card-actions">
                                        <button type="button" class="btn btn-sm btn-primary" onclick="viewDetails(<?php echo $letter['ID']; ?>)">
                                            <i class="fa fa-eye"></i> Chi tiết
                                        </button>
                                        <button type="button" class="btn btn-sm btn-warning" onclick="markAsWaiting(<?php echo $letter['ID']; ?>)">
                                            <i class="fa fa-hourglass-half"></i> Chờ lấy
                                        </button>
                                        <button type="button" class="btn btn-sm btn-success" onclick="markAsReceived(<?php echo $letter['ID']; ?>)">
                                            <i class="fa fa-hand-o-up"></i> Ghi nhận
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fa fa-print"></i>
                                <h3>Không có giấy giới thiệu đã in</h3>
                                <p>Chưa có giấy giới thiệu nào được in</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Waiting Letters -->
                    <div id="waiting-cards" class="status-section hidden">
                        <?php if (count($waitingList) > 0): ?>
                            <?php foreach ($waitingList as $letter): ?>
                                <div class="letter-card fade-in" 
                                     data-id="<?php echo $letter['ID']; ?>"
                                     data-status="waiting"
                                     data-mssv="<?php echo htmlspecialchars($letter['MSSV']); ?>"
                                     data-student="<?php echo htmlspecialchars($letter['Tensinhvien']); ?>"
                                     data-company="<?php echo htmlspecialchars($letter['TenCty']); ?>">
                                    
                                    <div class="status-badge status-waiting">
                                        <i class="fa fa-hourglass-half"></i> Chờ lấy
                                    </div>
                                    
                                    <div class="card-header">
                                        <h3 class="company-name"><?php echo htmlspecialchars($letter['TenCty']); ?></h3>
                                        <p class="company-address"><?php echo htmlspecialchars(shortAddress($letter['DiaChi'])); ?></p>
                                    </div>
                                    
                                    <div class="student-info">
                                        <i class="fa fa-user"></i>
                                        <?php echo htmlspecialchars($letter['Tensinhvien']); ?> 
                                        (<?php echo htmlspecialchars($letter['MSSV']); ?>)
                                        <?php if ($letter['TenDot']): ?>
                                            <br><i class="fa fa-calendar"></i>
                                            <small>Đợt: <?php echo htmlspecialchars($letter['TenDot']); ?></small>
                                            <?php if ($letter['ThoiGianBatDau'] && $letter['ThoiGianKetThuc']): ?>
                                                <br><small style="color: #6b7280;">
                                                    <?php echo date('d/m/Y', strtotime($letter['ThoiGianBatDau'])); ?> - 
                                                    <?php echo date('d/m/Y', strtotime($letter['ThoiGianKetThuc'])); ?>
                                                </small>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="card-actions">
                                        <button type="button" class="btn btn-sm btn-primary" onclick="viewDetails(<?php echo $letter['ID']; ?>)">
                                            <i class="fa fa-eye"></i> Chi tiết
                                        </button>
                                        <button type="button" class="btn btn-sm btn-success" onclick="markAsReceived(<?php echo $letter['ID']; ?>)">
                                            <i class="fa fa-hand-o-up"></i> Ghi nhận
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fa fa-hourglass-half"></i>
                                <h3>Không có giấy giới thiệu chờ lấy</h3>
                                <p>Chưa có giấy giới thiệu nào ở trạng thái chờ lấy</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Received Letters -->
                    <div id="received-cards" class="status-section hidden">
                        <?php if (count($receivedList) > 0): ?>
                            <?php foreach ($receivedList as $letter): ?>
                                <div class="letter-card fade-in" 
                                     data-id="<?php echo $letter['ID']; ?>"
                                     data-status="received"
                                     data-mssv="<?php echo htmlspecialchars($letter['MSSV']); ?>"
                                     data-student="<?php echo htmlspecialchars($letter['Tensinhvien']); ?>"
                                     data-company="<?php echo htmlspecialchars($letter['TenCty']); ?>">
                                    
                                    <div class="status-badge status-received">
                                        <i class="fa fa-check-square"></i> Đã nhận
                                    </div>
                                    
                                    <div class="card-header">
                                        <h3 class="company-name"><?php echo htmlspecialchars($letter['TenCty']); ?></h3>
                                        <p class="company-address"><?php echo htmlspecialchars(shortAddress($letter['DiaChi'])); ?></p>
                                    </div>
                                    
                                    <div class="student-info">
                                        <i class="fa fa-user"></i>
                                        <?php echo htmlspecialchars($letter['Tensinhvien']); ?> 
                                        (<?php echo htmlspecialchars($letter['MSSV']); ?>)
                                        <?php if ($letter['TenDot']): ?>
                                            <br><i class="fa fa-calendar"></i>
                                            <small>Đợt: <?php echo htmlspecialchars($letter['TenDot']); ?></small>
                                            <?php if ($letter['ThoiGianBatDau'] && $letter['ThoiGianKetThuc']): ?>
                                                <br><small style="color: #6b7280;">
                                                    <?php echo date('d/m/Y', strtotime($letter['ThoiGianBatDau'])); ?> - 
                                                    <?php echo date('d/m/Y', strtotime($letter['ThoiGianKetThuc'])); ?>
                                                </small>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        
                                        <?php if ($letter['TenNguoiNhan']): ?>
                                            <br><i class="fa fa-hand-o-up"></i>
                                            <small style="color: #16a34a;">
                                                Người nhận: <?php echo htmlspecialchars($letter['TenNguoiNhan']); ?> 
                                                (<?php echo htmlspecialchars($letter['MSSVNguoiNhan']); ?>)
                                            </small>
                                        <?php endif; ?>
                                        
                                        <?php if ($letter['ngay_nhan']): ?>
                                            <br><i class="fa fa-clock-o"></i>
                                            <small style="color: #6b7280;">
                                                Ngày nhận: <?php echo date('d/m/Y H:i', strtotime($letter['ngay_nhan'])); ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if ($letter['ghi_chu']): ?>
                                        <div class="received-note">
                                            <i class="fa fa-comment"></i>
                                            <small><?php echo htmlspecialchars($letter['ghi_chu']); ?></small>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="card-actions">
                                        <button type="button" class="btn btn-sm btn-primary" onclick="viewDetails(<?php echo $letter['ID']; ?>)">
                                            <i class="fa fa-eye"></i> Chi tiết
                                        </button>
                                        <button type="button" class="btn btn-sm btn-success disabled">
                                            <i class="fa fa-check"></i> Đã hoàn thành
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fa fa-check-square"></i>
                                <h3>Không có giấy giới thiệu đã nhận</h3>
                                <p>Chưa có giấy giới thiệu nào được nhận</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Modal ghi nhận thông tin -->
                <div id="receiveModal" class="modal fade" tabindex="-1" role="dialog">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h4 class="modal-title">
                                    <i class="fa fa-hand-o-up"></i>
                                    Ghi nhận thông tin nhận giấy
                                </h4>
                                <button type="button" class="close" data-dismiss="modal">
                                    <span>&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <form id="receiveForm">
                                    <input type="hidden" id="receive_letter_id" name="letter_id">
                                    
                                    <!-- Thông tin công ty -->
                                    <div class="form-group">
                                        <label class="font-weight-bold">Thông tin công ty:</label>
                                        <div id="company_info" class="alert alert-info">
                                            <!-- Sẽ được load bằng JS -->
                                        </div>
                                    </div>
                                    
                                    <!-- Danh sách sinh viên cùng công ty -->
                                    <div class="form-group">
                                        <label class="font-weight-bold">Danh sách sinh viên cùng công ty:</label>
                                        <div class="student-filter mb-3">
                                            <input type="text" id="student_search" class="form-control" 
                                                   placeholder="🔍 Tìm kiếm sinh viên theo MSSV hoặc tên...">
                                        </div>
                                        <div id="students_list" class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                                            <!-- Sẽ được load bằng JS -->
                                        </div>
                                    </div>
                                    
                                    <!-- Chọn người nhận -->
                                    <div class="form-group">
                                        <label for="receive_nguoinhan" class="font-weight-bold">Người đại diện nhận giấy: <span class="text-danger">*</span></label>
                                        <select id="receive_nguoinhan" name="nguoinhan" class="form-control" required>
                                            <option value="">-- Chọn sinh viên đại diện --</option>
                                        </select>
                                    </div>
                                    
                                    <!-- Ghi chú -->
                                    <div class="form-group">
                                        <label for="receive_ghichu" class="font-weight-bold">Ghi chú:</label>
                                        <textarea id="receive_ghichu" name="ghichu" class="form-control" rows="3" 
                                                  placeholder="Nhập ghi chú nếu có..."></textarea>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                    <i class="fa fa-times"></i> Hủy
                                </button>
                                <button type="button" class="btn btn-success" onclick="submitReceiveInfo()">
                                    <i class="fa fa-save"></i> Lưu thông tin
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Modal xác nhận in -->
                <div id="printConfirmModal" class="modal fade" tabindex="-1" role="dialog">
                    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 500px;">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h4 class="modal-title">
                                    <i class="fa fa-print"></i>
                                    Xác nhận đã in xong
                                </h4>
                                <button type="button" class="close" data-dismiss="modal">
                                    <span>&times;</span>
                                </button>
                            </div>
                            <div class="modal-body text-center">
                                <div class="mb-4">
                                    <i class="fa fa-question-circle text-warning" style="font-size: 4rem;"></i>
                                </div>
                                <h5 id="printConfirmTitle" class="mb-3"></h5>
                                <p id="printConfirmMessage" class="text-muted mb-4"></p>
                                <div class="alert alert-info">
                                    <i class="fa fa-info-circle"></i>
                                    <strong>Lưu ý:</strong> Chỉ xác nhận "Đã in xong" khi việc in đã hoàn tất thành công. 
                                    Nếu có vấn đề kỹ thuật, hãy chọn "Chưa xong" để in lại sau.
                                </div>
                            </div>
                            <div class="modal-footer justify-content-center">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                    <i class="fa fa-times"></i> Chưa xong
                                </button>
                                <button type="button" class="btn btn-success" id="confirmPrintBtn">
                                    <i class="fa fa-check"></i> Đã in xong
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
    // Global variables
    let currentStatus = 'approved';
    let currentSubStatus = 'individual'; // New variable for sub-tabs
    let searchKeyword = '';
    let selectedCards = new Set();

    // DOM ready
    document.addEventListener('DOMContentLoaded', function() {
        // FORCE CLEAR ALL AJAX CACHE AND OLD FUNCTIONS
        console.log('=== FORCE CLEARING ALL AJAX CACHE ===');
        
        // Clear any old AJAX intervals or cached calls
        if (window.oldAjaxInterval) {
            clearInterval(window.oldAjaxInterval);
        }
        
        // Override any possible old AJAX functions
        window.loadCompaniesAjax = undefined;
        window.loadCompaniesPage_old = undefined;
        window.fetchCompaniesPage = undefined;
        
        // Clear any potential fetch cache
        if ('caches' in window) {
            caches.keys().then(function(names) {
                names.forEach(function(name) {
                    if (name.includes('companies') || name.includes('ajax')) {
                        caches.delete(name);
                    }
                });
            });
        }
        
        // Force disable any potential service worker cache
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.getRegistrations().then(function(registrations) {
                registrations.forEach(function(registration) {
                    registration.unregister();
                });
            });
        }
        
        console.log('=== INITIALIZING PURE JAVASCRIPT VERSION v3.0 ===');
        console.log('Grouped letters data:', Object.keys(groupedLettersData).length, 'companies');
        
        // OVERRIDE AND BLOCK ANY OLD AJAX CALLS
        const originalXHR = window.XMLHttpRequest;
        const originalFetch = window.fetch;
        
        // Override XMLHttpRequest to block old AJAX calls
        window.XMLHttpRequest = function() {
            const xhr = new originalXHR();
            const originalOpen = xhr.open;
            
            xhr.open = function(method, url, ...args) {
                if (url && (url.includes('get_companies_page') || url.includes('get_companies_simple') || url.includes('get_companies_mock'))) {
                    console.error('BLOCKED OLD AJAX CALL TO:', url);
                    return; // Block the call
                }
                return originalOpen.apply(this, [method, url, ...args]);
            };
            
            return xhr;
        };
        
        // Override fetch to block old AJAX calls
        window.fetch = function(url, ...args) {
            if (url && (url.includes('get_companies_page') || url.includes('get_companies_simple') || url.includes('get_companies_mock'))) {
                console.error('BLOCKED OLD FETCH CALL TO:', url);
                return Promise.reject(new Error('Blocked old AJAX call'));
            }
            return originalFetch.apply(this, [url, ...args]);
        };
        
        initializeEventListeners();
        updateDisplay();
        updateSelectionDisplay();
    });

    function initializeEventListeners() {
        // Status toggle buttons
        document.querySelectorAll('.status-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const newStatus = this.getAttribute('data-status');
                if (newStatus !== currentStatus) {
                    switchStatus(newStatus);
                }
            });
        });

        // Sub-status toggle buttons (for approved tab)
        document.querySelectorAll('.sub-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const newSubStatus = this.getAttribute('data-sub-status');
                if (newSubStatus !== currentSubStatus) {
                    switchSubStatus(newSubStatus);
                }
            });
        });

        // Search input
        const searchInput = document.getElementById('searchInput');
        searchInput.addEventListener('input', function() {
            searchKeyword = this.value.trim().toLowerCase();
            filterCards();
        });

        // Print all button
        document.getElementById('printAllBtn').addEventListener('click', handlePrintAll);
        
        // Print grouped button  
        document.getElementById('printGroupedBtn').addEventListener('click', handlePrintGrouped);
        
        // Student search in modal
        document.getElementById('student_search').addEventListener('input', filterStudentsInModal);
        
        // Selection controls
        document.getElementById('selectAllBtn').addEventListener('click', selectAllCards);
        document.getElementById('clearSelectionBtn').addEventListener('click', clearAllSelection);
        document.getElementById('printSelectedBtn').addEventListener('click', printSelectedCards);
        document.getElementById('markSelectedBtn').addEventListener('click', markSelectedCards);
    }

    function switchStatus(newStatus) {
        // Update current status
        currentStatus = newStatus;
        
        // Update button states
        document.querySelectorAll('.status-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector(`[data-status="${newStatus}"]`).classList.add('active');
        
        // Update display
        updateDisplay();
        
        // Reset search
        document.getElementById('searchInput').value = '';
        searchKeyword = '';
        
        // Clear selection when switching tabs
        clearAllSelection();
        
        // Update print button visibility
        updatePrintButtonVisibility();
        
        // Update selection controls visibility
        updateSelectionControlsVisibility();
        
        // Update sub-tabs visibility
        updateSubTabsVisibility();
    }

    // Grouped letters data for JavaScript pagination
    const groupedLettersData = <?php echo json_encode($groupedLetters); ?>;
    
    // Pagination variables
    let currentCompanyPage = 1;
    const companiesPerPage = 4;
    let companiesData = [];

    function switchSubStatus(newSubStatus) {
        // Update current sub status
        currentSubStatus = newSubStatus;
        
        // Update sub-button states
        document.querySelectorAll('.sub-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector(`[data-sub-status="${newSubStatus}"]`).classList.add('active');
        
        // Update sub-section display
        updateSubSectionDisplay();
        
        // Load companies for grouped view
        if (newSubStatus === 'grouped') {
            console.log('SWITCHING TO GROUPED VIEW - JAVASCRIPT ONLY');
            currentCompanyPage = 1;
            loadCompaniesPage(1);
        }
        
        // Clear selection when switching sub-tabs
        clearAllSelection();
    }

    function updateSubTabsVisibility() {
        const subTabs = document.getElementById('approvedSubTabs');
        
        if (currentStatus === 'approved') {
            subTabs.classList.add('show');
        } else {
            subTabs.classList.remove('show');
        }
    }

    function updateSubSectionDisplay() {
        // Hide all sub-sections
        document.querySelectorAll('.sub-section').forEach(section => {
            section.classList.add('hidden');
        });
        
        // Show current sub-section
        const currentSubSection = document.getElementById(`${currentSubStatus}-section`);
        if (currentSubSection) {
            currentSubSection.classList.remove('hidden');
            
            // Add fade-in animation to cards
            const cards = currentSubSection.querySelectorAll('.letter-card');
            cards.forEach((card, index) => {
                setTimeout(() => {
                    card.classList.add('fade-in');
                }, index * 30);
            });
        }
    }

    function updateDisplay() {
        // Hide all sections
        document.querySelectorAll('.status-section').forEach(section => {
            section.classList.add('hidden');
        });
        
        // Show current section
        const currentSection = document.getElementById(`${currentStatus}-cards`);
        if (currentSection) {
            currentSection.classList.remove('hidden');
            
            // For approved status, also update sub-sections
            if (currentStatus === 'approved') {
                updateSubSectionDisplay();
            } else {
                // Add fade-in animation to cards for non-approved sections
                const cards = currentSection.querySelectorAll('.letter-card');
                cards.forEach((card, index) => {
                    setTimeout(() => {
                        card.classList.add('fade-in');
                    }, index * 50);
                });
            }
        }
    }

    function filterCards() {
        const currentSection = document.getElementById(`${currentStatus}-cards`);
        if (!currentSection) return;
        
        let cards;
        if (currentStatus === 'approved') {
            // For approved status, filter within current sub-section
            const currentSubSection = document.getElementById(`${currentSubStatus}-section`);
            cards = currentSubSection ? currentSubSection.querySelectorAll('.letter-card') : [];
        } else {
            cards = currentSection.querySelectorAll('.letter-card');
        }
        
        let visibleCount = 0;
        
        cards.forEach(card => {
            const mssv = card.getAttribute('data-mssv') || '';
            const student = card.getAttribute('data-student') || '';
            const company = card.getAttribute('data-company') || '';
            
            const searchText = (mssv + ' ' + student + ' ' + company).toLowerCase();
            const isVisible = searchKeyword === '' || searchText.indexOf(searchKeyword) !== -1;
            
            if (isVisible) {
                card.style.display = '';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });
        
        // Show/hide empty state
        const emptyState = currentSection.querySelector('.empty-state');
        if (emptyState) {
            emptyState.style.display = visibleCount === 0 ? 'block' : 'none';
        }
    }

    function updatePrintButtonVisibility() {
        const printBtn = document.getElementById('printAllBtn');
        const printGroupedBtn = document.getElementById('printGroupedBtn');
        
        // Hiển thị nút in cho trạng thái đã duyệt
        if (currentStatus === 'approved') {
            printBtn.style.display = 'inline-flex';
            printGroupedBtn.style.display = 'inline-flex';
        } else {
            printBtn.style.display = 'none';
            printGroupedBtn.style.display = 'none';
        }
    }

    function viewDetails(letterId) {
        // Tạo form ẩn để submit
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/datn/pages/canbo/chitietgiaygioithieu';
        
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'giay_id';
        input.value = letterId;
        
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }

    function approveLetter(letterId) {
        if (!confirm('Bạn có chắc chắn muốn duyệt giấy giới thiệu này?\n\nViệc duyệt sẽ thêm công ty vào hệ thống và không thể hoàn tác.')) {
            return;
        }
        
        // Hiển thị loading
        const btn = event.target.closest('button');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Đang xử lý...';
        btn.disabled = true;
        
        // Gửi AJAX request
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '/datn/pages/canbo/approve_letter_ajax.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    
                    if (response.success) {
                        // Hiển thị thông báo thành công
                        alert('✅ ' + response.message);
                        
                        // Reload trang để cập nhật dữ liệu
                        location.reload();
                    } else {
                        // Hiển thị lỗi
                        alert('❌ ' + response.message);
                        
                        // Khôi phục nút
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                    }
                } catch (e) {
                    alert('❌ Có lỗi xảy ra khi xử lý phản hồi');
                    console.error('Parse error:', e);
                    
                    // Khôi phục nút
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            }
        };
        
        xhr.onerror = function() {
            alert('❌ Có lỗi kết nối');
            
            // Khôi phục nút
            btn.innerHTML = originalText;
            btn.disabled = false;
        };
        
        xhr.send('letter_id=' + encodeURIComponent(letterId));
    }

    function markAsReceived(letterId) {
        // Load thông tin cho modal
        loadReceiveModalData(letterId);
        
        // Hiển thị modal
        $('#receiveModal').modal('show');
    }
    
    function markAsWaiting(letterId) {
        if (!confirm('Chuyển giấy giới thiệu này sang trạng thái "Chờ lấy"?\n\nSinh viên sẽ có thể theo dõi và đến nhận giấy.')) {
            return;
        }
        
        // Hiển thị loading
        const btn = event.target.closest('button');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Đang xử lý...';
        btn.disabled = true;
        
        // Gửi AJAX request
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '/datn/pages/canbo/mark_as_waiting.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    
                    if (response.success) {
                        alert('✅ ' + response.message);
                        location.reload();
                    } else {
                        alert('❌ ' + response.message);
                        // Khôi phục nút
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                    }
                } catch (e) {
                    alert('❌ Có lỗi xảy ra khi xử lý phản hồi');
                    console.error('Parse error:', e);
                    // Khôi phục nút
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            }
        };
        
        xhr.onerror = function() {
            alert('❌ Có lỗi kết nối');
            // Khôi phục nút
            btn.innerHTML = originalText;
            btn.disabled = false;
        };
        
        xhr.send('letter_ids[]=' + encodeURIComponent(letterId));
    }
    
    function loadReceiveModalData(letterId) {
        // Set letter ID
        document.getElementById('receive_letter_id').value = letterId;
        
        // Load thông tin công ty và danh sách sinh viên
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '/datn/pages/canbo/get_company_students.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    
                    if (response.success) {
                        // Hiển thị thông tin công ty
                        document.getElementById('company_info').innerHTML = `
                            <strong>${response.company.TenCty}</strong><br>
                            <small>Địa chỉ: ${response.company.DiaChi}</small><br>
                            <small>Đợt: ${response.company.TenDot || 'Chưa xác định'}</small>
                        `;
                        
                        // Hiển thị danh sách sinh viên
                        displayStudentsList(response.students);
                        
                        // Populate select box
                        populateStudentSelect(response.students);
                    } else {
                        alert('❌ ' + response.message);
                    }
                } catch (e) {
                    alert('❌ Có lỗi xảy ra khi tải dữ liệu');
                    console.error('Parse error:', e);
                }
            }
        };
        
        xhr.send('letter_id=' + encodeURIComponent(letterId));
    }
    
    function displayStudentsList(students) {
        const container = document.getElementById('students_list');
        
        if (students.length === 0) {
            container.innerHTML = '<p class="text-muted">Không có sinh viên nào.</p>';
            return;
        }
        
        let html = '<div class="table-responsive"><table class="table table-sm table-striped">';
        html += '<thead><tr><th>STT</th><th>MSSV</th><th>Họ tên</th><th>Đợt</th></tr></thead><tbody>';
        
        students.forEach((student, index) => {
            html += `
                <tr data-mssv="${student.MSSV}" data-name="${student.Ten}">
                    <td>${index + 1}</td>
                    <td>${student.MSSV}</td>
                    <td>${student.Ten}</td>
                    <td>${student.TenDot || 'Chưa xác định'}</td>
                </tr>
            `;
        });
        
        html += '</tbody></table></div>';
        container.innerHTML = html;
    }
    
    function populateStudentSelect(students) {
        const select = document.getElementById('receive_nguoinhan');
        select.innerHTML = '<option value="">-- Chọn sinh viên đại diện --</option>';
        
        students.forEach(student => {
            const option = document.createElement('option');
            option.value = student.ID_taikhoan;
            option.textContent = `${student.Ten} (${student.MSSV})`;
            select.appendChild(option);
        });
    }
    
    function filterStudentsInModal() {
        const searchValue = document.getElementById('student_search').value.toLowerCase();
        const rows = document.querySelectorAll('#students_list tbody tr');
        
        rows.forEach(row => {
            const mssv = row.getAttribute('data-mssv').toLowerCase();
            const name = row.getAttribute('data-name').toLowerCase();
            
            if (mssv.includes(searchValue) || name.includes(searchValue)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
    
    function submitReceiveInfo() {
        const form = document.getElementById('receiveForm');
        const formData = new FormData(form);
        
        // Validate
        const nguoinhan = document.getElementById('receive_nguoinhan').value;
        if (!nguoinhan) {
            alert('Vui lòng chọn người đại diện nhận giấy!');
            return;
        }
        
        // Disable submit button
        const submitBtn = event.target;
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Đang lưu...';
        submitBtn.disabled = true;
        
        // Send request
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '/datn/pages/canbo/save_receive_info.php', true);
        
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    
                    if (response.success) {
                        alert('✅ ' + response.message);
                        $('#receiveModal').modal('hide');
                        location.reload();
                    } else {
                        alert('❌ ' + response.message);
                    }
                } catch (e) {
                    alert('❌ Có lỗi xảy ra khi lưu thông tin');
                    console.error('Parse error:', e);
                }
                
                // Restore button
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        };
        
        xhr.send(formData);
    }
    
    function handlePrintGrouped() {
        // Kiểm tra xem có giấy nào để in không
        const approvedCount = <?php echo count($approvedList); ?>;
        if (approvedCount === 0) {
            alert('Không có giấy giới thiệu nào để in!');
            return;
        }
        
        if (!confirm('In theo công ty sẽ gộp sinh viên cùng công ty vào một giấy.\n\nBạn có muốn tiếp tục không?')) {
            return;
        }
        
        // Hiển thị loading
        const printBtn = document.getElementById('printGroupedBtn');
        const originalText = printBtn.innerHTML;
        printBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Đang mở...';
        printBtn.disabled = true;
        
        // Mở file in trước, chưa cập nhật trạng thái
        const printUrl = '/datn/pages/canbo/print_grouped_letters.php';
        const printWindow = window.open(printUrl, '_blank', 'width=1024,height=768,scrollbars=yes,resizable=yes');
        
        // Khôi phục nút sau khi mở
        setTimeout(() => {
            printBtn.innerHTML = originalText;
            printBtn.disabled = false;
            
            // Hiển thị modal xác nhận đã in xong
            setTimeout(() => {
                showPrintConfirmModal(
                    'Xác nhận in theo công ty',
                    'Bạn đã in xong tất cả giấy theo công ty chưa?',
                    () => confirmPrintGroupedCompleted()
                );
            }, 2000); // Delay 2s để người dùng thấy trang in
        }, 1000);
    }
    
    function confirmPrintGroupedCompleted() {
        // Hiển thị loading
        const printBtn = document.getElementById('printGroupedBtn');
        const originalText = printBtn.innerHTML;
        printBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Đang cập nhật...';
        printBtn.disabled = true;
        
        // Cập nhật trạng thái đã in
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '/datn/pages/canbo/mark_as_printed.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    
                    if (response.success) {
                        alert('✅ Đã chuyển tất cả giấy sang trạng thái "Chờ lấy"! Sinh viên có thể đến nhận giấy.');
                        location.reload();
                    } else {
                        alert('❌ ' + response.message);
                        // Khôi phục nút
                        printBtn.innerHTML = originalText;
                        printBtn.disabled = false;
                    }
                } catch (e) {
                    alert('❌ Có lỗi xảy ra khi cập nhật trạng thái');
                    console.error('Parse error:', e);
                    // Khôi phục nút
                    printBtn.innerHTML = originalText;
                    printBtn.disabled = false;
                }
            }
        };
        
        xhr.send('action=print_grouped');
    }

    function printLetter(letterId) {
        if (!confirm('In giấy giới thiệu này?\n\nBạn có muốn tiếp tục không?')) {
            return;
        }
        
        // Hiển thị loading
        const btn = event.target.closest('button');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Đang mở...';
        btn.disabled = true;
        
        // Mở trang in trước, chưa cập nhật trạng thái
        const printUrl = '/datn/pages/canbo/print_letter_template.php?id=' + letterId;
        const printWindow = window.open(printUrl, '_blank', 'width=800,height=600,scrollbars=yes,resizable=yes');
        
        // Khôi phục nút sau khi mở
        setTimeout(() => {
            btn.innerHTML = originalText;
            btn.disabled = false;
            
            // Hiển thị modal xác nhận đã in xong
            setTimeout(() => {
                showPrintConfirmModal(
                    'Xác nhận in giấy giới thiệu',
                    'Bạn đã in xong giấy giới thiệu này chưa?',
                    () => confirmPrintSingleCompleted(letterId)
                );
            }, 2000); // Delay 2s để người dùng thấy trang in
        }, 1000);
    }
    
    function confirmPrintSingleCompleted(letterId) {
        // Cập nhật trạng thái đã in
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '/datn/pages/canbo/mark_as_printed.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    
                    if (response.success) {
                        alert('✅ Đã chuyển giấy sang trạng thái "Chờ lấy"! Sinh viên có thể đến nhận giấy.');
                        location.reload();
                    } else {
                        alert('❌ ' + response.message);
                    }
                } catch (e) {
                    alert('❌ Có lỗi xảy ra khi cập nhật trạng thái');
                    console.error('Parse error:', e);
                }
            }
        };
        
        xhr.send('action=print_single&letter_ids[]=' + encodeURIComponent(letterId));
    }

    function handlePrintAll() {
        // Kiểm tra xem có giấy nào để in không
        const approvedCount = <?php echo count($approvedList); ?>;
        if (approvedCount === 0) {
            alert('Không có giấy giới thiệu nào để in!');
            return;
        }
        
        if (!confirm(`Bạn có muốn in tất cả ${approvedCount} giấy giới thiệu đã duyệt?\n\nMỗi sinh viên sẽ có một giấy riêng. Bạn có muốn tiếp tục không?`)) {
            return;
        }
        
        // Hiển thị loading
        const printBtn = document.getElementById('printAllBtn');
        const originalText = printBtn.innerHTML;
        printBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Đang mở...';
        printBtn.disabled = true;
        
        // Mở từng giấy trong tab mới với delay, chưa cập nhật trạng thái
        const letterIds = <?php echo json_encode(array_column($approvedList, 'ID')); ?>;
        
        letterIds.forEach((id, index) => {
            setTimeout(() => {
                const printUrl = '/datn/pages/canbo/print_letter_template.php?id=' + id;
                window.open(printUrl, '_blank' + index, 'width=800,height=600,scrollbars=yes,resizable=yes');
            }, index * 500); // Delay 500ms giữa các tab
        });
        
        // Khôi phục nút sau khi mở hết các tab
        setTimeout(() => {
            printBtn.innerHTML = originalText;
            printBtn.disabled = false;
            
            // Hiển thị modal xác nhận đã in xong
            setTimeout(() => {
                showPrintConfirmModal(
                    'Xác nhận in tất cả giấy',
                    `Bạn đã in xong tất cả ${approvedCount} giấy giới thiệu chưa?`,
                    () => confirmPrintAllCompleted()
                );
            }, 3000); // Delay 3s để người dùng thấy các trang in
        }, letterIds.length * 500 + 1000);
    }
    
    function confirmPrintAllCompleted() {
        // Hiển thị loading
        const printBtn = document.getElementById('printAllBtn');
        const originalText = printBtn.innerHTML;
        printBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Đang cập nhật...';
        printBtn.disabled = true;
        
        // Cập nhật trạng thái đã in cho tất cả
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '/datn/pages/canbo/mark_as_printed.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    
                    if (response.success) {
                        alert('✅ Đã chuyển tất cả giấy sang trạng thái "Chờ lấy"! Sinh viên có thể đến nhận giấy.');
                        location.reload();
                    } else {
                        alert('❌ ' + response.message);
                        // Khôi phục nút
                        printBtn.innerHTML = originalText;
                        printBtn.disabled = false;
                    }
                } catch (e) {
                    alert('❌ Có lỗi xảy ra khi cập nhật trạng thái');
                    console.error('Parse error:', e);
                    // Khôi phục nút
                    printBtn.innerHTML = originalText;
                    printBtn.disabled = false;
                }
            }
        };
        
        xhr.send('action=print_all');
    }

    // Utility functions
    function showLoading(element) {
        element.classList.add('loading');
    }

    function hideLoading(element) {
        element.classList.remove('loading');
    }
    
    function showPrintConfirmModal(title, message, onConfirm) {
        // Cập nhật nội dung modal
        document.getElementById('printConfirmTitle').textContent = title;
        document.getElementById('printConfirmMessage').textContent = message;
        
        // Xóa event listener cũ và thêm mới
        const confirmBtn = document.getElementById('confirmPrintBtn');
        const newConfirmBtn = confirmBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
        
        newConfirmBtn.onclick = function() {
            $('#printConfirmModal').modal('hide');
            onConfirm();
        };
        
        // Hiển thị modal
        $('#printConfirmModal').modal('show');
    }
    
    // === SELECTION FUNCTIONS ===
    function toggleCardSelection(checkbox) {
        const cardId = checkbox.getAttribute('data-id');
        const card = checkbox.closest('.letter-card');
        
        if (checkbox.checked) {
            selectedCards.add(cardId);
            card.classList.add('selected');
        } else {
            selectedCards.delete(cardId);
            card.classList.remove('selected');
        }
        
        updateSelectionDisplay();
    }
    
    function toggleCardSelectionByClick(cardElement) {
        // Tìm checkbox trong card này
        const checkbox = cardElement.querySelector('.card-checkbox');
        if (!checkbox) return;
        
        // Toggle checkbox
        checkbox.checked = !checkbox.checked;
        
        // Cập nhật selection
        const cardId = checkbox.getAttribute('data-id');
        
        if (checkbox.checked) {
            selectedCards.add(cardId);
            cardElement.classList.add('selected');
        } else {
            selectedCards.delete(cardId);
            cardElement.classList.remove('selected');
        }
        
        updateSelectionDisplay();
    }
    
    function selectAllCards() {
        const currentSection = document.getElementById(`${currentStatus}-cards`);
        if (!currentSection) return;
        
        const checkboxes = currentSection.querySelectorAll('.card-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = true;
            const cardId = checkbox.getAttribute('data-id');
            const card = checkbox.closest('.letter-card');
            selectedCards.add(cardId);
            card.classList.add('selected');
        });
        
        updateSelectionDisplay();
    }
    
    // Companies pagination functions
    function loadCompaniesPage(page) {
        console.log('Loading companies page:', page, '(JavaScript only, no AJAX)');
        currentCompanyPage = page;
        
        // Convert grouped letters to companies array
        companiesData = Object.keys(groupedLettersData).map(taxCode => ({
            taxCode: taxCode,
            letters: groupedLettersData[taxCode]
        }));
        
        console.log('Companies data:', companiesData.length, 'companies total');
        
        // Calculate pagination
        const totalCompanies = companiesData.length;
        const totalPages = Math.ceil(totalCompanies / companiesPerPage);
        const startIndex = (page - 1) * companiesPerPage;
        const endIndex = startIndex + companiesPerPage;
        const pageCompanies = companiesData.slice(startIndex, endIndex);
        
        console.log('Page', page, '- showing companies', startIndex, 'to', endIndex-1);
        
        // Generate companies HTML
        const companiesContainer = document.getElementById('companies-container');
        companiesContainer.innerHTML = generateCompaniesHTML(pageCompanies);
        
        // Generate pagination HTML
        const paginationContainer = document.getElementById('companies-pagination');
        paginationContainer.innerHTML = generatePaginationHTML(page, totalPages);
    }
    
    function generateCompaniesHTML(companies) {
        if (companies.length === 0) {
            return `
                <div class="empty-section">
                    <i class="fa fa-building-o"></i>
                    <p>Chưa có nhóm công ty</p>
                    <small>Sinh viên cùng mã số thuế sẽ được nhóm ở đây</small>
                </div>
            `;
        }
        
        return companies.map(company => {
            const firstLetter = company.letters[0];
            return `
                <div class="company-group" data-tax-code="${escapeHtml(company.taxCode)}">
                    <div class="company-group-header">
                        <div class="company-info">
                            <h4>${escapeHtml(firstLetter.TenCty)}</h4>
                            <p class="company-details">
                                <i class="fa fa-map-marker"></i> ${escapeHtml(shortAddress(firstLetter.DiaChi, 60))}
                                ${company.taxCode ? `| <i class="fa fa-barcode"></i> MST: ${escapeHtml(company.taxCode)}` : ''}
                            </p>
                        </div>
                        <div class="group-actions">
                            <span class="student-count">
                                <i class="fa fa-users"></i>
                                ${company.letters.length}
                            </span>
                            <button type="button" class="btn btn-xs btn-outline-light" onclick="selectCompanyGroup('${escapeHtml(company.taxCode)}')">
                                <i class="fa fa-check"></i> Chọn
                            </button>
                            <button type="button" class="btn btn-xs btn-light" onclick="printCompanyGroup('${escapeHtml(company.taxCode)}')">
                                <i class="fa fa-print"></i> In
                            </button>
                        </div>
                    </div>
                    
                    <div class="company-students-grid">
                        ${company.letters.map(letter => generateLetterCardHTML(letter, company.taxCode)).join('')}
                    </div>
                </div>
            `;
        }).join('');
    }
    
    function generateLetterCardHTML(letter, taxCode) {
        return `
            <div class="letter-card fade-in has-checkbox compact" 
                 data-id="${letter.ID}"
                 data-status="approved"
                 data-type="grouped"
                 data-tax-code="${escapeHtml(taxCode)}"
                 data-mssv="${escapeHtml(letter.MSSV)}"
                 data-student="${escapeHtml(letter.Tensinhvien)}"
                 data-company="${escapeHtml(letter.TenCty)}"
                 onclick="toggleCardSelectionByClick(this)">
                
                <input type="checkbox" class="card-checkbox" 
                       data-id="${letter.ID}"
                       onclick="event.stopPropagation()">
                
                <div class="status-badge status-approved">
                    <i class="fa fa-check"></i> Đã duyệt
                </div>
                
                <div class="student-info compact">
                    <i class="fa fa-user"></i>
                    <strong>${escapeHtml(letter.Tensinhvien)}</strong>
                    <br>MSSV: ${escapeHtml(letter.MSSV)}
                    ${letter.TenNguoiNhan ? `<br><small><i class="fa fa-arrow-right"></i> ${escapeHtml(letter.TenNguoiNhan)}</small>` : ''}
                    ${letter.TenDot ? `<br><small><i class="fa fa-calendar"></i> ${escapeHtml(letter.TenDot)}</small>` : ''}
                    ${letter.ghi_chu ? `<br><small><i class="fa fa-sticky-note"></i> ${escapeHtml(letter.ghi_chu)}</small>` : ''}
                </div>
                
                <div class="card-actions compact">
                    <button type="button" class="btn btn-xs btn-primary" onclick="event.stopPropagation(); viewDetails(${letter.ID})">
                        <i class="fa fa-eye"></i>
                    </button>
                    <button type="button" class="btn btn-xs btn-success" onclick="event.stopPropagation(); printLetter(${letter.ID})">
                        <i class="fa fa-print"></i>
                    </button>
                </div>
            </div>
        `;
    }
    
    function generatePaginationHTML(currentPage, totalPages) {
        if (totalPages <= 1) return '';
        
        let paginationHTML = '<div class="pagination-wrapper">';
        paginationHTML += '<div class="pagination-controls">';
        
        // Previous button
        if (currentPage > 1) {
            paginationHTML += `<button type="button" class="btn btn-sm btn-outline-secondary" onclick="loadCompaniesPage(${currentPage - 1})">
                <i class="fa fa-chevron-left"></i> Trước
            </button>`;
        }
        
        // Page numbers
        paginationHTML += '<div class="page-numbers">';
        for (let i = 1; i <= totalPages; i++) {
            const activeClass = i === currentPage ? 'active' : '';
            paginationHTML += `<button type="button" class="btn btn-sm btn-outline-primary page-btn ${activeClass}" onclick="loadCompaniesPage(${i})">${i}</button>`;
        }
        paginationHTML += '</div>';
        
        // Next button
        if (currentPage < totalPages) {
            paginationHTML += `<button type="button" class="btn btn-sm btn-outline-secondary" onclick="loadCompaniesPage(${currentPage + 1})">
                Sau <i class="fa fa-chevron-right"></i>
            </button>`;
        }
        
        paginationHTML += '</div>';
        paginationHTML += `<div class="pagination-info">Trang ${currentPage} / ${totalPages} (${companiesData.length} công ty)</div>`;
        paginationHTML += '</div>';
        
        return paginationHTML;
    }
    
    function escapeHtml(unsafe) {
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
    
    function shortAddress(address, maxLength) {
        if (!address || address.length <= maxLength) {
            return address || '';
        }
        return address.substring(0, maxLength) + '...';
    }
    
    function clearAllSelection() {
        selectedCards.clear();
        
        document.querySelectorAll('.card-checkbox').forEach(checkbox => {
            checkbox.checked = false;
        });
        
        document.querySelectorAll('.letter-card.selected').forEach(card => {
            card.classList.remove('selected');
        });
        
        updateSelectionDisplay();
    }
    
    function updateSelectionDisplay() {
        const count = selectedCards.size;
        document.getElementById('selectedCount').textContent = count;
        
        // Show/hide selection controls
        const selectionControls = document.getElementById('selectionControls');
        if ((currentStatus === 'approved' || currentStatus === 'printed') && count > 0) {
            selectionControls.classList.add('show');
        } else {
            selectionControls.classList.remove('show');
        }
        
        // Enable/disable buttons
        const printSelectedBtn = document.getElementById('printSelectedBtn');
        const markSelectedBtn = document.getElementById('markSelectedBtn');
        
        printSelectedBtn.disabled = count === 0;
        markSelectedBtn.disabled = count === 0;
        
        // Show appropriate buttons based on current status
        if (currentStatus === 'approved') {
            printSelectedBtn.style.display = 'inline-flex';
            markSelectedBtn.style.display = 'none';
        } else if (currentStatus === 'printed') {
            printSelectedBtn.style.display = 'none';
            markSelectedBtn.style.display = 'inline-flex';
            markSelectedBtn.innerHTML = '<i class="fa fa-hourglass-half"></i> Đổi sang "Chờ lấy"';
        } else {
            printSelectedBtn.style.display = 'none';
            markSelectedBtn.style.display = 'none';
        }
    }
    
    // Company group selection functions
    function selectCompanyGroup(taxCode) {
        // Find all letter cards with the same tax code
        const groupCards = document.querySelectorAll(`[data-tax-code="${taxCode}"]`);
        
        groupCards.forEach(card => {
            const id = card.dataset.id;
            const checkbox = card.querySelector('.card-checkbox');
            
            if (!selectedCards.has(id)) {
                selectedCards.add(id);
                card.classList.add('selected');
                checkbox.checked = true;
            }
        });
        
        updateSelectionDisplay();
        
        // Show success message
        showNotification(`Đã chọn ${groupCards.length} sinh viên từ cùng công ty`, 'success');
    }
    
    function printCompanyGroup(taxCode) {
        // Find all letter cards with the same tax code
        const groupCards = document.querySelectorAll(`[data-tax-code="${taxCode}"]`);
        const ids = Array.from(groupCards).map(card => card.dataset.id);
        
        if (ids.length === 0) {
            showNotification('Không tìm thấy sinh viên nào trong nhóm này', 'error');
            return;
        }
        
        // Clear current selection and select this group
        clearAllSelection();
        groupCards.forEach(card => {
            const id = card.dataset.id;
            const checkbox = card.querySelector('.card-checkbox');
            
            selectedCards.add(id);
            card.classList.add('selected');
            checkbox.checked = true;
        });
        
        updateSelectionDisplay();
        
        // Trigger print
        printSelectedLetters();
    }
    
    function showNotification(message, type = 'info') {
        // Create notification element if it doesn't exist
        let notification = document.getElementById('notification');
        if (!notification) {
            notification = document.createElement('div');
            notification.id = 'notification';
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                border-radius: 8px;
                color: white;
                font-weight: 500;
                z-index: 10000;
                transform: translateX(400px);
                transition: transform 0.3s ease;
            `;
            document.body.appendChild(notification);
        }
        
        // Set style based on type
        switch(type) {
            case 'success':
                notification.style.background = 'linear-gradient(135deg, #10b981, #059669)';
                break;
            case 'error':
                notification.style.background = 'linear-gradient(135deg, #ef4444, #dc2626)';
                break;
            default:
                notification.style.background = 'linear-gradient(135deg, #3b82f6, #2563eb)';
        }
        
        notification.textContent = message;
        notification.style.transform = 'translateX(0)';
        
        // Auto hide after 3 seconds
        setTimeout(() => {
            notification.style.transform = 'translateX(400px)';
        }, 3000);
    }
    
    function updateSelectionControlsVisibility() {
        const selectionControls = document.getElementById('selectionControls');
        
        // Only show selection controls for approved and printed tabs
        if (currentStatus === 'approved' || currentStatus === 'printed') {
            // Don't hide immediately, let updateSelectionDisplay handle it
        } else {
            selectionControls.classList.remove('show');
        }
    }
    
    function printSelectedCards() {
        if (selectedCards.size === 0) {
            alert('Vui lòng chọn ít nhất một giấy giới thiệu để in!');
            return;
        }
        
        const selectedIds = Array.from(selectedCards);
        if (!confirm(`In ${selectedIds.length} giấy giới thiệu đã chọn?\n\nBạn có muốn tiếp tục không?`)) {
            return;
        }
        
        // Tạo URL với các ID đã chọn
        const printUrl = '/datn/pages/canbo/print_selected_letters.php?' + 
                        selectedIds.map(id => `ids[]=${id}`).join('&');
        
        // Mở trang in
        const printWindow = window.open(printUrl, '_blank', 'width=1024,height=768,scrollbars=yes,resizable=yes');
        
        // Hiển thị modal xác nhận sau khi in
        setTimeout(() => {
            showPrintConfirmModal(
                'Xác nhận đã in xong',
                `Bạn đã in xong ${selectedIds.length} giấy giới thiệu?`,
                () => confirmPrintSelectedCompleted(selectedIds)
            );
        }, 2000);
    }
    
    function confirmPrintSelectedCompleted(selectedIds) {
        // Cập nhật trạng thái đã in cho các giấy đã chọn
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '/datn/pages/canbo/mark_as_printed.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    
                    if (response.success) {
                        alert(`✅ Đã chuyển ${selectedIds.length} giấy giới thiệu sang trạng thái "Chờ lấy"!`);
                        location.reload();
                    } else {
                        alert('❌ ' + response.message);
                    }
                } catch (e) {
                    alert('❌ Có lỗi xảy ra khi cập nhật trạng thái');
                    console.error('Parse error:', e);
                }
            }
        };
        
        const params = 'action=print_selected&' + selectedIds.map(id => `letter_ids[]=${id}`).join('&');
        xhr.send(params);
        
        // Ẩn modal
        $('#printConfirmModal').modal('hide');
    }
    
    function markSelectedCards() {
        if (selectedCards.size === 0) {
            alert('Vui lòng chọn ít nhất một giấy giới thiệu!');
            return;
        }
        
        const selectedIds = Array.from(selectedCards);
        if (!confirm(`Chuyển ${selectedIds.length} giấy giới thiệu sang trạng thái "Chờ lấy"?\n\nBạn có muốn tiếp tục không?`)) {
            return;
        }
        
        // Cập nhật trạng thái
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '/datn/pages/canbo/mark_as_waiting.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    
                    if (response.success) {
                        alert(`✅ Đã chuyển ${selectedIds.length} giấy giới thiệu sang trạng thái "Chờ lấy"!`);
                        location.reload();
                    } else {
                        alert('❌ ' + response.message);
                    }
                } catch (e) {
                    alert('❌ Có lỗi xảy ra khi cập nhật trạng thái');
                    console.error('Parse error:', e);
                }
            }
        };
        
        const params = selectedIds.map(id => `letter_ids[]=${id}`).join('&');
        xhr.send(params);
    }

    // Initialize display on load
    updatePrintButtonVisibility();
    </script>
    <!-- jQuery đã được include trong template head.php -->
</body>
</html>