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
    <!-- NO AJAX VERSION - PURE JAVASCRIPT ONLY -->
    <title>Quản lý giấy giới thiệu</title>
    
    <script>
    // ULTIMATE AJAX BLOCKER - Monitor all network requests
    (function() {
        console.log('🛡️ AJAX BLOCKER ACTIVATED');
        
        // Monitor all network requests
        if (window.performance && window.performance.getEntriesByType) {
            setInterval(() => {
                const entries = window.performance.getEntriesByType('resource');
                entries.forEach(entry => {
                    if (entry.name.includes('/ajax/') || entry.name.includes('get_companies')) {
                        console.error('🚨 DETECTED AJAX REQUEST:', entry.name);
                    }
                });
            }, 1000);
        }
    })();
    </script>
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
        
        /* === ENHANCED FILTER BAR === */
        .filter-bar {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border-radius: 16px;
            padding: 25px 30px;
            margin-bottom: 30px;
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.08);
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
            position: relative;
            overflow: hidden;
        }

        .filter-bar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #3b82f6 0%, #10b981 50%, #8b5cf6 100%);
        }
        
        .status-toggle {
            display: flex;
            gap: 8px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            padding: 6px;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        
        .status-btn {
            padding: 12px 18px;
            border: none;
            background: transparent;
            color: #64748b;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 110px;
            justify-content: center;
        }

        .status-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s ease;
        }

        .status-btn:hover::before {
            left: 100%;
        }
        
        .status-btn:hover {
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            color: #475569;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
        }
        
        .status-btn.active {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
            transform: translateY(-2px);
        }

        .status-btn.active:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        }

        .status-btn i {
            font-size: 14px;
            opacity: 0.8;
        }

        .status-btn.active i {
            opacity: 1;
        }
        
        .status-btn .badge {
            background: linear-gradient(135deg, #94a3b8 0%, #64748b 100%);
            color: white;
            font-size: 11px;
            font-weight: 700;
            padding: 4px 8px;
            border-radius: 12px;
            margin-left: 6px;
            min-width: 22px;
            text-align: center;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        .status-btn.active .badge {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.9) 0%, rgba(255, 255, 255, 0.7) 100%);
            color: #1e40af;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.2);
            transform: scale(1.05);
        }

        .status-btn:hover .badge {
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .status-btn.active:hover .badge {
            transform: scale(1.05);
        }
        
        .search-input {
            flex: 1;
            min-width: 300px;
            padding: 14px 20px 14px 50px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 14px;
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            color: #374151;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.05);
            position: relative;
            font-weight: 500;
        }

        .search-input::placeholder {
            color: #9ca3af;
            font-weight: 400;
        }
        
        .search-input:focus {
            outline: none;
            border-color: #3b82f6;
            background: white;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1), inset 0 2px 4px rgba(0, 0, 0, 0.05);
            transform: translateY(-1px);
        }

        .search-input:hover {
            border-color: #cbd5e1;
            background: white;
        }
        
        .action-buttons {
            display: flex;
            gap: 12px;
            margin-left: auto;
            align-items: center;
        }
        
        .btn {
            padding: 12px 20px;
            border: 2px solid transparent;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: relative;
            overflow: hidden;
            min-width: 140px;
            justify-content: center;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }

        .btn:hover::before {
            left: 100%;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            border-color: #1d4ed8;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
            border-color: #1e40af;
        }

        .btn i {
            font-size: 14px;
            opacity: 0.9;
        }

        .btn:hover i {
            opacity: 1;
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
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: 2px solid #e2e8f0;
            position: relative;
            opacity: 0;
            transform: translateY(20px);
            overflow: hidden;
        }

        .letter-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #3b82f6 0%, #10b981 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .letter-card:hover::before {
            opacity: 1;
        }
        
        .letter-card.fade-in {
            opacity: 1;
            transform: translateY(0);
            animation: slideInUp 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .letter-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.15);
            border-color: #3b82f6;
            cursor: pointer;
            background: white;
        }
        
        .letter-card.has-checkbox {
            padding-left: 50px;
            cursor: pointer;
            position: relative;
        }
        
        .letter-card.has-checkbox::after {
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

        /* === SUB-TABS FOR APPROVED - Modern Design === */
        .sub-tabs {
            display: none;
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border-radius: 12px;
            padding: 20px;
            margin: 0 0 24px 0;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .sub-tabs.show {
            display: block;
            animation: fadeInUp 0.3s ease-out;
        }

        @keyframes fadeInUp {
            from { 
                opacity: 0; 
                transform: translateY(-8px);
            }
            to { 
                opacity: 1; 
                transform: translateY(0);
            }
        }

        .sub-toggle {
            display: flex;
            gap: 4px;
            background: #f1f5f9;
            border-radius: 10px;
            padding: 4px;
            border: 1px solid #e2e8f0;
            width: fit-content;
            margin: 0 auto;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .sub-btn {
            padding: 12px 20px;
            border: none;
            background: transparent;
            color: #64748b;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 140px;
            justify-content: center;
            position: relative;
        }

        .sub-btn:hover {
            background: rgba(255, 255, 255, 0.8);
            color: #475569;
            transform: translateY(-1px);
        }

        .sub-btn.active {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
            transform: translateY(-1px);
        }

        .sub-btn.active:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        }

        .sub-btn i {
            font-size: 14px;
        }

        .sub-btn .badge {
            background: #e5e7eb;
            color: #6b7280;
            font-size: 11px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 12px;
            margin-left: 4px;
            min-width: 22px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .sub-btn.active .badge {
            background: rgba(255, 255, 255, 0.25);
            color: white;
            backdrop-filter: blur(10px);
        }

        .sub-btn.active .badge {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        /* Container improvements */
        .tab-content {
            padding: 20px 0;
            min-height: 300px;
        }

        /* Ensure cards display properly in all sections */
        .individual-letters-grid,
        .grouped-letters-container,
        #companies-container {
            width: 100%;
            overflow: hidden;
        }

        /* Fix for proper clearfix */
        .individual-letters-grid::after,
        .grouped-letters-container::after,
        #companies-container::after {
            content: "";
            display: table;
            clear: both;
        }

        /* Prevent layout collapse */
        .sub-section {
            min-height: 150px;
            width: 100%;
            overflow: hidden;
        }

        /* Ensure proper display of cards */
        .letter-card,
        .company-group {
            display: flex;
            flex-direction: column;
            width: 100%;
        }

        /* Individual Letters Grid - Fixed Bootstrap 3 style */
        .individual-letters-grid {
            overflow: hidden;
            margin-left: -15px;
            margin-right: -15px;
        }

        .individual-letters-grid .letter-card-wrapper {
            float: left;
            width: 50%;
            padding-left: 15px;
            padding-right: 15px;
            margin-bottom: 20px;
            box-sizing: border-box;
        }
        }

        .individual-letters-grid::after {
            content: "";
            display: table;
            clear: both;
        }

        /* Company Groups Grid - Fixed Bootstrap 3 style */
        .grouped-letters-container {
            overflow: hidden;
            margin-left: -15px;
            margin-right: -15px;
        }
        
        #companies-container {
            overflow: hidden;
            margin-left: -15px;
            margin-right: -15px;
        }

        .company-group-wrapper {
            float: left;
            width: 50%;
            padding-left: 15px;
            padding-right: 15px;
            margin-bottom: 20px;
            box-sizing: border-box;
        }

        .company-group-wrapper::after,
        #companies-container::after {
            content: "";
            display: table;
            clear: both;
        }

        /* Letter Cards - Clean and modern design */
        .letter-card {
            display: flex;
            flex-direction: column;
            padding: 20px;
            border: 1px solid #e1e5e9;
            border-radius: 12px;
            background: white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            transition: all 0.3s ease;
            width: 100%;
            position: relative;
            overflow: hidden;
            box-sizing: border-box;
        }

        .letter-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #3b82f6, #10b981);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .letter-card:hover::before {
            opacity: 1;
        }

        .letter-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
            border-color: #cbd5e1;
        }

        /* Individual Section specific styling */
        #individual-section .letter-card {
            min-height: 280px;
            height: auto;
        }

        #individual-section .letter-card:hover::before {
            opacity: 1;
        }

        /* Card Content Area with simple scroll */
        #individual-section .card-content {
            flex: 1;
            overflow-y: auto;
            margin-bottom: 16px;
        }

        /* Simplified scrollbar */
        #individual-section .card-content::-webkit-scrollbar {
            width: 4px;
        }

        #individual-section .card-content::-webkit-scrollbar-track {
            background: #f8fafc;
        }

        #individual-section .card-content::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 2px;
        }

        #individual-section .letter-card .card-header {
            margin-bottom: 16px;
            padding-bottom: 16px;
            border-bottom: 1px solid #f1f5f9;
        }

        #individual-section .letter-card .company-name {
            font-size: 18px;
            margin-bottom: 8px;
            font-weight: 600;
            color: #1e293b;
            line-height: 1.4;
        }

        #individual-section .letter-card .company-address {
            font-size: 14px;
            color: #64748b;
            line-height: 1.5;
            margin-bottom: 4px;
        }

        #individual-section .letter-card .tax-code {
            font-size: 13px;
            color: #94a3b8;
            margin-top: 6px;
            font-weight: 500;
        }

        #individual-section .letter-card .student-info {
            margin-bottom: 16px;
            font-size: 15px;
            color: #0f766e;
            font-weight: 500;
            line-height: 1.5;
        }

        #individual-section .letter-card .student-info i {
            color: #10b981;
            margin-right: 6px;
        }

        #individual-section .letter-card .card-actions {
            display: flex;
            gap: 10px;
            margin-top: auto;
            padding-top: 16px;
            border-top: 1px solid #f1f5f9;
        }

        #individual-section .letter-card .btn {
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 500;
            border-radius: 8px;
            flex: 1;
            border: none;
            transition: all 0.2s ease;
        }

        #individual-section .letter-card .btn-primary {
            background: #3b82f6;
            color: white;
        }

        #individual-section .letter-card .btn-primary:hover {
            background: #2563eb;
            transform: translateY(-1px);
        }

        #individual-section .letter-card .btn-success {
            background: #10b981;
            color: white;
        }

        #individual-section .letter-card .btn-success:hover {
            background: #059669;
            transform: translateY(-1px);
        }

        #individual-section .letter-card .status-badge {
            position: absolute;
            top: 16px;
            right: 16px;
            font-size: 11px;
            font-weight: 600;
            padding: 6px 10px;
            border-radius: 20px;
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        #individual-section .letter-card.has-checkbox {
            padding-left: 50px;
            position: relative;
        }

        #individual-section .letter-card .card-checkbox {
            position: absolute;
            top: 20px;
            left: 20px;
            width: 16px;
            height: 16px;
            accent-color: #10b981;
        }

        /* Modern hover effect */
        #individual-section .letter-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
            border-color: #cbd5e1;
        }

        /* Clear float and fix any layout issues */
        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }

        /* Ensure proper box sizing */
        .letter-card-wrapper,
        .company-group-wrapper,
        .letter-card,
        .company-group {
            box-sizing: border-box;
        }

        /* Better spacing for parent containers */
        .tab-content {
            padding: 0;
        }

        .sub-section {
            min-height: 200px;
        }

        /* Fix potential z-index issues */
        .letter-card,
        .company-group {
            position: relative;
            z-index: 1;
        }

        .letter-card:hover,
        .company-group:hover {
            z-index: 2;
        }

        /* Responsive adjustments - Fixed Bootstrap 3 style */
        /* Better mobile responsiveness */
        @media (max-width: 767px) {
            .individual-letters-grid .letter-card-wrapper,
            .company-group-wrapper {
                width: 100% !important;
                float: none !important;
                padding-left: 0 !important;
                padding-right: 0 !important;
                margin-bottom: 15px;
            }
            
            .individual-letters-grid,
            .grouped-letters-container,
            #companies-container {
                margin-left: 0 !important;
                margin-right: 0 !important;
            }
            
            .letter-card,
            .company-group {
                margin-left: 0;
                margin-right: 0;
            }
        }
        
        @media (min-width: 992px) {
            .individual-letters-grid .letter-card-wrapper,
            .company-group-wrapper {
                width: 50%;
            }
        }

        /* Company Groups - Modern and elegant design */
        .company-group {
            border: 1px solid #e1e5e9;
            border-radius: 12px;
            background: white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            height: 360px;
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease;
            width: 100%;
            overflow: hidden;
            position: relative;
        }

        .company-group::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #059669, #10b981);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .company-group:hover::before {
            opacity: 1;
        }
        
        .company-group:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
            border-color: #cbd5e1;
        }

        .company-group-header {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-shrink: 0;
            height: 100px;
        }

        .company-info h4 {
            margin: 0 0 8px 0;
            font-size: 17px;
            font-weight: 600;
            line-height: 1.3;
        }

        .company-details {
            margin: 0;
            opacity: 0.95;
            font-size: 14px;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .group-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }

        .student-count {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(10px);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 4px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .btn-outline-light {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            font-weight: 500;
            transition: all 0.2s ease;
            font-size: 12px;
            padding: 8px 14px;
            border-radius: 20px;
            backdrop-filter: blur(10px);
        }

        .btn-outline-light:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.5);
            color: white;
            transform: translateY(-1px);
        }

        .btn-light {
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid transparent;
            color: #047857;
            font-weight: 500;
            transition: all 0.2s ease;
            font-size: 12px;
            padding: 8px 14px;
            border-radius: 20px;
            backdrop-filter: blur(10px);
        }

        .btn-light:hover {
            background: white;
            color: #065f46;
            transform: translateY(-1px);
        }

        .company-students-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 10px;
            padding: 20px;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            overflow-y: auto;
            flex: 1;
            height: calc(100% - 100px);
        }

        /* Modern scrollbar */
        .company-students-grid::-webkit-scrollbar {
            width: 6px;
        }

        .company-students-grid::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.5);
            border-radius: 3px;
        }

        .company-students-grid::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }

        .company-students-grid::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* Compact Letter Cards - Modern and clean */
        .letter-card.compact {
            padding: 14px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            transition: all 0.2s ease;
            position: relative;
            font-size: 13px;
            min-height: 80px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .letter-card.compact:hover {
            border-color: #10b981;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.15);
        }

        .letter-card.compact.selected {
            border-color: #10b981;
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.2);
        }

        .letter-card.compact .status-badge {
            top: 8px;
            right: 8px;
            font-size: 10px;
            padding: 4px 8px;
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
            border-radius: 12px;
            font-weight: 600;
        }

        .letter-card.compact.has-checkbox {
            padding-left: 40px;
        }

        .letter-card.compact .card-checkbox {
            top: 14px;
            left: 14px;
            width: 16px;
            height: 16px;
            accent-color: #10b981;
        }

        .student-info.compact {
            font-size: 13px;
            margin: 8px 0;
            line-height: 1.4;
            color: #374151;
        }

        .student-info.compact strong {
            color: #1e293b;
            font-weight: 600;
            font-size: 14px;
        }

        .card-actions.compact {
            margin-top: 10px;
            display: flex;
            gap: 8px;
        }

        .card-actions.compact .btn {
            padding: 6px 10px;
            font-size: 11px;
            border-radius: 6px;
            font-weight: 500;
            border: none;
            transition: all 0.2s ease;
        }

        .card-actions.compact .btn-primary {
            background: #3b82f6;
            color: white;
        }

        .card-actions.compact .btn-primary:hover {
            background: #2563eb;
            transform: translateY(-1px);
        }

        .card-actions.compact .btn-success {
            background: #10b981;
            color: white;
        }

        .card-actions.compact .btn-success:hover {
            background: #059669;
            transform: translateY(-1px);
        }

        .btn.btn-xs {
            padding: 3px 6px;
            font-size: 9px;
            border-radius: 4px;
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
            .grouped-letters-container,
            #companies-container {
                grid-template-columns: 1fr !important;
                grid-template-rows: auto !important;
                height: auto !important;
                gap: 15px;
            }
            
            /* Reset grid positioning on mobile */
            .company-group:nth-child(1),
            .company-group:nth-child(2),
            .company-group:nth-child(3),
            .company-group:nth-child(4) {
                grid-row: auto !important;
                grid-column: auto !important;
            }
            
            .company-group {
                height: auto !important; /* Auto height on mobile */
                width: 100%;
            }
            
            .company-students-grid {
                height: auto !important;
                max-height: none !important;
                overflow-y: visible;
                padding: 12px;
            }
            
            .company-group-header {
                flex-direction: column;
                align-items: flex-start;
                height: auto !important;
                min-height: auto !important;
                gap: 10px;
                padding: 12px 15px;
            }
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
            .individual-letters-grid {
                grid-template-columns: 1fr !important;
            }
            
            .grouped-letters-container,
            #companies-container {
                grid-template-columns: 1fr !important;
                grid-template-rows: auto !important;
                min-height: auto !important;
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
        
        /* === ENHANCED SELECTION STYLES === */
        .selection-controls {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-radius: 16px;
            padding: 20px 25px;
            margin-bottom: 25px;
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.1);
            border: 2px solid #cbd5e1;
            display: none;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
            position: relative;
            overflow: hidden;
        }

        .selection-controls::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #f59e0b 0%, #ef4444 50%, #ec4899 100%);
        }
        
        .selection-controls.show {
            display: flex;
            animation: slideDown 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .selection-info {
            color: #374151;
            font-weight: 700;
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .selection-info::before {
            content: '✅';
            font-size: 16px;
        }
        
        .selection-actions {
            display: flex;
            gap: 12px;
            margin-left: auto;
        }
        
        .card-checkbox {
            position: absolute;
            top: 20px;
            left: 20px;
            width: 22px;
            height: 22px;
            accent-color: #3b82f6;
            cursor: pointer;
            z-index: 10;
            border-radius: 4px;
            transition: all 0.2s ease;
        }

        .card-checkbox:hover {
            transform: scale(1.1);
        }
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
        
        /* === ENHANCED RESPONSIVE === */
        @media (max-width: 768px) {
            #page-wrapper { 
                padding: 15px; 
            }
            
            .filter-bar { 
                flex-direction: column; 
                align-items: stretch;
                gap: 20px;
                padding: 20px;
            }
            
            .status-toggle {
                flex-direction: column;
                padding: 8px;
                gap: 10px;
            }
            
            .status-btn {
                min-width: 100%;
                justify-content: center;
                padding: 15px 20px;
                font-size: 13px;
            }
            
            .search-input {
                min-width: 100%;
                padding: 16px 20px;
                font-size: 16px; /* Prevent zoom on iOS */
            }
            
            .action-buttons { 
                margin-left: 0;
                justify-content: center;
                flex-direction: column;
                gap: 10px;
            }
            
            .btn {
                width: 100%;
                padding: 15px 20px;
            }
            
            .selection-controls {
                flex-direction: column;
                align-items: stretch;
                gap: 15px;
                padding: 18px;
            }
            
            .selection-actions {
                margin-left: 0;
                justify-content: center;
            }
            
            .status-section { 
                grid-template-columns: 1fr; 
                gap: 15px;
            }
            
            .page-header h1 { 
                font-size: 1.8rem; 
            }
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
        
        /* Simplified Pagination Styles */
        .pagination-wrapper {
            margin: 15px 0;
            text-align: center;
        }
        
        .pagination-controls {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
        }
        
        .page-numbers {
            display: flex;
            gap: 4px;
        }
        
        .page-btn {
            min-width: 32px;
            height: 28px;
            border-radius: 4px;
            border: 1px solid #d1d5db;
            background: white;
            color: #374151;
            font-size: 12px;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .page-btn:hover {
            background: #f3f4f6;
            border-color: #9ca3af;
        }
        
        .page-btn.active {
            background: #3b82f6;
            border-color: #3b82f6;
            color: white;
        }
        
        .pagination-info {
            color: #6b7280;
            font-size: 12px;
            margin-top: 4px;
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
        
        /* Tablet responsive for perfect 2x2 grid */
        @media (max-width: 1024px) and (min-width: 769px) {
            .grouped-letters-container,
            #companies-container {
                grid-template-columns: 1fr 1fr;
                grid-template-rows: 1fr 1fr;
                gap: 12px;
                height: 600px; /* Proportional height for tablet */
                grid-auto-flow: row;
            }
            
            /* Maintain perfect grid position for tablet */
            .company-group:nth-child(1) { grid-row: 1; grid-column: 1; }
            .company-group:nth-child(2) { grid-row: 1; grid-column: 2; }
            .company-group:nth-child(3) { grid-row: 2; grid-column: 1; }
            .company-group:nth-child(4) { grid-row: 2; grid-column: 2; }
            
            .company-group {
                width: 100%;
                height: 100%;
            }
            
            .company-group-header {
                height: 70px;
                min-height: 70px;
                padding: 10px 12px;
            }
            
            .company-students-grid {
                height: calc(100% - 70px);
                padding: 8px 10px;
            }
        }
        
        /* Large tablet responsive */
        @media (max-width: 1200px) and (min-width: 1025px) {
            .grouped-letters-container,
            #companies-container {
                grid-template-columns: 1fr 1fr;
                grid-template-rows: 1fr 1fr;
                gap: 14px;
                height: 480px;
                grid-auto-flow: row;
            }
            
            /* Maintain perfect grid position for large tablet */
            .company-group:nth-child(1) { grid-row: 1; grid-column: 1; }
            .company-group:nth-child(2) { grid-row: 1; grid-column: 2; }
            .company-group:nth-child(3) { grid-row: 2; grid-column: 1; }
            .company-group:nth-child(4) { grid-row: 2; grid-column: 2; }
            
            .company-group {
                width: 100%;
                height: 100%;
            }
            
            .company-group-header {
                height: 75px;
                min-height: 75px;
            }
            
            .company-students-grid {
                height: calc(100% - 75px);
            }
        }

        /* === RESPONSIVE DESIGN FOR SUB-TABS === */
        @media (max-width: 768px) {
            .sub-tabs {
                padding: 15px 15px 10px 15px;
                margin: 0 0 20px 0;
                border-radius: 8px;
            }
            
            .sub-toggle {
                flex-direction: column;
                gap: 8px;
                padding: 6px;
                border-radius: 8px;
                width: 100%;
            }
            
            .sub-btn {
                padding: 14px 16px;
                min-width: 100%;
                font-size: 13px;
                border-radius: 6px;
            }
            
            .sub-btn i {
                font-size: 13px;
            }
            
            .sub-btn .badge {
                font-size: 10px;
                padding: 2px 6px;
                margin-left: 8px;
            }
        }

        @media (max-width: 480px) {
            .sub-tabs {
                padding: 12px 10px 8px 10px;
                margin: 0 0 15px 0;
            }
            
            .sub-btn {
                padding: 12px 14px;
                font-size: 12px;
                letter-spacing: 0.25px;
            }
            
            .sub-btn i {
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <div id="wrapper">
       <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/admin/template/slidebar.php"; ?>
        
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
                        <button class="status-btn" data-status="approved" id="btnapp">
                            <i class="fa fa-check-circle"></i>
                            Đã duyệt
                            <span class="badge"><?php echo count($approvedList); ?></span>
                        </button>
                        <button class="status-btn active" data-status="pending" id="btnpen">
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
                    <div id="approved-cards" class="status-section hidden">
                        <!-- Individual Letters Sub-section -->
                        <div id="individual-section" class="sub-section">
                            <div class="individual-letters-grid">
                            <?php if (count($individualLetters) > 0): ?>
                                <?php foreach ($individualLetters as $letter): ?>
                                    <div class="letter-card-wrapper">
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
                                                        <p class="tax-code"><i class="fa fa-barcode"></i> <?php echo htmlspecialchars($letter['MaSoThue']); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <div class="student-info">
                                                    <i class="fa fa-user"></i>
                                                    <?php echo htmlspecialchars($letter['Tensinhvien']); ?> 
                                                    (<?php echo htmlspecialchars($letter['MSSV']); ?>)
                                                    <?php if ($letter['TenDot']): ?>
                                                        <br><small>Đợt: <?php echo htmlspecialchars($letter['TenDot']); ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            
                                            <div class="card-actions">
                                                <button type="button" class="btn btn-sm btn-success" onclick="event.stopPropagation(); printLetter(<?php echo $letter['ID']; ?>)">
                                                    <i class="fa fa-print"></i> In
                                                </button>
                                            </div>
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
                    <div id="pending-cards" class="status-section">
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
                                        <button type="button" class="btn btn-sm btn-warning" onclick="markAsWaiting(<?php echo $letter['ID']; ?>)">
                                            <i class="fa fa-hourglass-half"></i> Chờ lấy
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
                                        <button type="button" class="btn btn-sm btn-success" onclick="selectReceiver(<?php echo $letter['ID']; ?>)">
                                            <i class="fa fa-user"></i> Chọn người nhận
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
                
                <!-- Modal chọn người nhận -->
                <div id="selectReceiverModal" class="modal fade" tabindex="-1" role="dialog">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h4 class="modal-title">
                                    <i class="fa fa-user"></i>
                                    Chọn người nhận giấy giới thiệu
                                </h4>
                                <button type="button" class="close" data-dismiss="modal">
                                    <span>&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <form id="receiverForm">
                                    <input type="hidden" id="select_letter_id" name="letter_id">
                                    
                                    <!-- Thông tin công ty -->
                                    <div class="form-group">
                                        <label class="font-weight-bold">Thông tin công ty:</label>
                                        <div id="company_info_display" class="alert alert-info">
                                            <!-- Sẽ được load bằng JS -->
                                        </div>
                                    </div>
                                    
                                    <!-- Danh sách sinh viên cùng công ty để chọn người nhận -->
                                    <div class="form-group">
                                        <label class="font-weight-bold">Danh sách sinh viên cùng công ty:</label>
                                        <div class="student-filter mb-3">
                                            <input type="text" id="receiver_search" class="form-control" 
                                                   placeholder="🔍 Tìm kiếm sinh viên theo MSSV hoặc tên...">
                                        </div>
                                        <div id="students_list_display" class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                                            <!-- Sẽ được load bằng JS -->
                                        </div>
                                    </div>
                                    
                                    <!-- Chọn người nhận -->
                                    <div class="form-group">
                                        <label for="selected_receiver" class="font-weight-bold">Chọn người đại diện nhận giấy: <span class="text-danger">*</span></label>
                                        <select id="selected_receiver" name="nguoinhan" class="form-control" required>
                                            <option value="">-- Chọn sinh viên đại diện --</option>
                                        </select>
                                    </div>
                                    
                                    <!-- Ghi chú -->
                                    <div class="form-group">
                                        <label for="receiver_note" class="font-weight-bold">Ghi chú:</label>
                                        <textarea id="receiver_note" name="ghichu" class="form-control" rows="3" 
                                                  placeholder="Nhập ghi chú nếu có..."></textarea>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                    <i class="fa fa-times"></i> Hủy
                                </button>
                                <button type="button" class="btn btn-success" onclick="saveReceiverInfo()">
                                    <i class="fa fa-save"></i> Lưu thông tin người nhận
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
    let currentStatus = 'pending';
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
        
        // FORCE REMOVE ANY OLD loadCompaniesPage FUNCTION
        if (window.loadCompaniesPage && window.loadCompaniesPage.toString().includes('ajax') || window.loadCompaniesPage.toString().includes('XMLHttpRequest')) {
            console.warn('🔄 FOUND OLD loadCompaniesPage WITH AJAX - REPLACING IT');
            delete window.loadCompaniesPage;
        }
        
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
                // Block ANY request to ajax folder or companies endpoints
                if (url && (
                    url.includes('/ajax/') || 
                    url.includes('get_companies') || 
                    url.includes('companies_page') ||
                    url.includes('/datn/ajax/')
                )) {
                    console.error('🚫 BLOCKED AJAX CALL TO:', url);
                    console.trace('Call stack:', new Error().stack);
                    // Return a fake successful response
                    setTimeout(() => {
                        if (xhr.onload) xhr.onload();
                    }, 1);
                    return;
                }
                return originalOpen.apply(this, [method, url, ...args]);
            };
            
            return xhr;
        };
        
        // Override fetch to block old AJAX calls  
        window.fetch = function(url, ...args) {
            if (url && (
                url.includes('/ajax/') || 
                url.includes('get_companies') || 
                url.includes('companies_page') ||
                url.includes('/datn/ajax/')
            )) {
                console.error('🚫 BLOCKED FETCH CALL TO:', url);
                console.trace('Call stack:', new Error().stack);
                return Promise.resolve(new Response('{"success": false, "message": "Blocked"}'));
            }
            return originalFetch.apply(this, [url, ...args]);
        };
        
        initializeEventListeners();
        updateDisplay();
        updateSelectionDisplay();
        updateSubTabsVisibility();
        
        // FORCE REDEFINE loadCompaniesPage TO ENSURE NO AJAX
        window.loadCompaniesPage = function(page) {
            console.log('🟢 USING PURE JAVASCRIPT loadCompaniesPage - page:', page);
            currentCompanyPage = page;
            
            // Convert grouped letters to companies array
            companiesData = Object.keys(groupedLettersData).map(taxCode => ({
                taxCode: taxCode,
                letters: groupedLettersData[taxCode]
            }));
            
            console.log('🟢 Companies data:', companiesData.length, 'companies total');
            
            // Calculate pagination
            const totalCompanies = companiesData.length;
            const totalPages = Math.ceil(totalCompanies / companiesPerPage);
            const startIndex = (page - 1) * companiesPerPage;
            const endIndex = startIndex + companiesPerPage;
            const pageCompanies = companiesData.slice(startIndex, endIndex);
            
            console.log('🟢 Page', page, '- showing companies', startIndex, 'to', endIndex-1);
            
            // Generate companies HTML
            const companiesContainer = document.getElementById('companies-container');
            if (companiesContainer) {
                companiesContainer.innerHTML = generateCompaniesHTML(pageCompanies);
                console.log('🟢 Updated companies container');
            }
            
            // Generate pagination HTML
            const paginationContainer = document.getElementById('companies-pagination');
            if (paginationContainer) {
                paginationContainer.innerHTML = generatePaginationHTML(page, totalPages);
                console.log('🟢 Updated pagination');
            }
        };
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
        
        // Receiver search in modal
        document.getElementById('receiver_search').addEventListener('input', filterStudentsInReceiverModal);
        
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
        
        // Hiển thị nút in cho trạng thái đã duyệt
        if (currentStatus === 'approved') {
            printBtn.style.display = 'inline-flex';
        } else {
            printBtn.style.display = 'none';
        }
    }

    function viewDetails(letterId) {
        // Tạo form ẩn để submit
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/datn/admin/pages/chitietgiaygioithieu';
        
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
        xhr.open('POST', '/datn/admin/pages/approve_letter_ajax.php', true);
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

    function selectReceiver(letterId) {
        console.log('selectReceiver called with ID:', letterId);
        
        // Kiểm tra jQuery
        if (typeof $ === 'undefined') {
            console.error('jQuery not loaded!');
            alert('Lỗi: jQuery chưa được tải. Vui lòng tải lại trang.');
            return;
        }
        
        // Load thông tin cho modal
        loadReceiverModalData(letterId);
        
        // Hiển thị modal với Bootstrap 3
        try {
            $('#selectReceiverModal').modal('show');
        } catch (e) {
            console.error('Error showing modal:', e);
            // Fallback: show modal manually
            document.getElementById('selectReceiverModal').style.display = 'block';
            document.body.classList.add('modal-open');
        }
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
        xhr.open('POST', '/datn/admin/pages/mark_as_waiting.php', true);
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
    
    function loadReceiverModalData(letterId) {
        console.log('Loading modal data for letter:', letterId);
        
        // Set letter ID
        const letterIdInput = document.getElementById('select_letter_id');
        if (letterIdInput) {
            letterIdInput.value = letterId;
        }
        
        // Load thông tin công ty và danh sách sinh viên
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '/datn/admin/pages/get_company_students.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                console.log('Response status:', xhr.status);
                console.log('Response text:', xhr.responseText);
                
                if (xhr.status !== 200) {
                    alert('❌ Lỗi kết nối server (Status: ' + xhr.status + ')');
                    return;
                }
                
                try {
                    const response = JSON.parse(xhr.responseText);
                    console.log('Parsed response:', response);
                    
                    if (response.success) {
                        // Hiển thị thông tin công ty
                        const companyInfoElement = document.getElementById('company_info_display');
                        if (companyInfoElement) {
                            companyInfoElement.innerHTML = `
                                <strong>${response.company.TenCty}</strong><br>
                                <small>Địa chỉ: ${response.company.DiaChi}</small><br>
                                <small>Đợt: ${response.company.TenDot || 'Chưa xác định'}</small>
                            `;
                        }
                        
                        // Hiển thị danh sách sinh viên
                        displayStudentsListForReceiver(response.students);
                        
                        // Populate select box
                        populateReceiverSelect(response.students);
                    } else {
                        alert('❌ ' + response.message);
                    }
                } catch (e) {
                    console.error('Parse error:', e);
                    console.error('Raw response:', xhr.responseText);
                    alert('❌ Có lỗi xảy ra khi xử lý dữ liệu từ server');
                }
            }
        };
        
        xhr.onerror = function() {
            console.error('Network error');
            alert('❌ Lỗi kết nối mạng');
        };
        
        console.log('Sending request with letter_id:', letterId);
        xhr.send('letter_id=' + encodeURIComponent(letterId));
    }
    
    function displayStudentsListForReceiver(students) {
        const container = document.getElementById('students_list_display');
        
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
    
    function populateReceiverSelect(students) {
        const select = document.getElementById('selected_receiver');
        select.innerHTML = '<option value="">-- Chọn sinh viên đại diện --</option>';
        
        students.forEach(student => {
            const option = document.createElement('option');
            option.value = student.ID_TaiKhoan;
            option.textContent = `${student.Ten} (${student.MSSV})`;
            select.appendChild(option);
        });
    }
    
    function filterStudentsInReceiverModal() {
        const searchValue = document.getElementById('receiver_search').value.toLowerCase();
        const rows = document.querySelectorAll('#students_list_display tbody tr');
        
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
    
    function saveReceiverInfo() {
        // Validate
        const nguoinhan = document.getElementById('selected_receiver').value;
        const letterId = document.getElementById('select_letter_id').value;
        const ghichu = document.getElementById('receiver_note').value;
        
        if (!nguoinhan) {
            alert('Vui lòng chọn người đại diện nhận giấy!');
            return;
        }
        
        if (!letterId) {
            alert('Không tìm thấy ID giấy giới thiệu!');
            return;
        }
        
        // Disable submit button
        const submitBtn = event.target;
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Đang lưu...';
        submitBtn.disabled = true;
        
        // Prepare data
        const formData = new URLSearchParams();
        formData.append('letter_id', letterId);
        formData.append('nguoinhan', nguoinhan);
        formData.append('ghichu', ghichu);
        
        // Send request
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '/datn/admin/pages/save_receiver_info.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                console.log('Response status:', xhr.status);
                console.log('Response text:', xhr.responseText);
                
                try {
                    const response = JSON.parse(xhr.responseText);
                    
                    if (response.success) {
                        alert('✅ ' + response.message);
                        // Ẩn modal với Bootstrap 3
                        $('#selectReceiverModal').modal('hide');
                        location.reload();
                    } else {
                        alert('❌ ' + response.message);
                    }
                } catch (e) {
                    alert('❌ Có lỗi xảy ra khi lưu thông tin: ' + xhr.responseText);
                    console.error('Parse error:', e);
                    console.error('Response:', xhr.responseText);
                }
                
                // Restore button
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        };
        
        xhr.send(formData.toString());
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
        const printUrl = '/datn/admin/pages/print_letter_template.php?id=' + letterId;
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
        xhr.open('POST', '/datn/admin/pages/mark_as_printed.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    
                    if (response.success) {
                        alert('✅ Đã chuyển giấy sang trạng thái "ĐÃ IN"!');
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
                const printUrl = '/datn/admin/pages/print_letter_template.php?id=' + id;
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
        xhr.open('POST', '/datn/admin/pages/mark_as_printed.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    
                    if (response.success) {
                        alert('✅ Đã chuyển tất cả giấy sang trạng thái "ĐÃ IN"!');
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
            // Ẩn modal với Bootstrap 3
            $('#printConfirmModal').modal('hide');
            onConfirm();
        };
        
        // Hiển thị modal với Bootstrap 3
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
        console.log('🟢 Loading companies page:', page, '(JavaScript only, no AJAX)');
        currentCompanyPage = page;
        
        // Convert grouped letters to companies array with sorted order
        companiesData = Object.keys(groupedLettersData).map(taxCode => ({
            taxCode: taxCode,
            letters: groupedLettersData[taxCode]
        }));
        
        console.log('🟢 Companies data:', companiesData.length, 'companies total');
        
        // Calculate pagination
        const totalCompanies = companiesData.length;
        const totalPages = Math.ceil(totalCompanies / companiesPerPage);
        const startIndex = (page - 1) * companiesPerPage;
        const endIndex = startIndex + companiesPerPage;
        const pageCompanies = companiesData.slice(startIndex, endIndex);
        
        console.log('🟢 Page', page, '- showing companies', startIndex+1, 'to', Math.min(endIndex, totalCompanies));
        console.log('🟢 Companies order on this page:', pageCompanies.map((c, i) => `Position ${i+1}: ${c.letters[0].TenCty}`));
        
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
                <div class="company-group-wrapper">
                    <div class="company-group" data-tax-code="${escapeHtml(company.taxCode)}">
                        <div class="company-group-header">
                            <div class="company-info">
                                <h4>${escapeHtml(firstLetter.TenCty)}</h4>
                                <p class="company-details">
                                    ${escapeHtml(shortAddress(firstLetter.DiaChi, 50))}
                                    ${company.taxCode ? ` | ${escapeHtml(company.taxCode)}` : ''}
                                </p>
                            </div>
                            <div class="group-actions">
                                <button class="btn btn-sm btn-outline-primary" 
                                        onclick="printByCompany('${escapeHtml(firstLetter.TenCty)}', '${escapeHtml(company.taxCode)}')"
                                        title="In giấy giới thiệu theo công ty">
                                    <i class="fa fa-print"></i>
                                    In theo công ty
                                </button>
                                <button class="btn btn-sm btn-outline-secondary" 
                                        onclick="selectCompanyGroup('${escapeHtml(company.taxCode)}')"
                                        title="Chọn tất cả sinh viên của công ty này">
                                    <i class="fa fa-check-square-o"></i>
                                    Chọn tất cả
                                </button>
                                <span class="student-count">
                                    ${company.letters.length} SV
                                </span>
                            </div>
                        </div>
                        
                        <div class="company-students-grid">
                            ${company.letters.map(letter => generateLetterCardHTML(letter, company.taxCode)).join('')}
                        </div>
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
                    <i class="fa fa-check"></i>
                </div>
                
                <div class="student-info compact">
                    <strong>${escapeHtml(letter.Tensinhvien)}</strong>
                    <br>${escapeHtml(letter.MSSV)}
                    ${letter.TenDot ? `<br><small>${escapeHtml(letter.TenDot)}</small>` : ''}
                </div>
                
                <div class="card-actions compact">
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
                <i class="fa fa-chevron-left"></i>
            </button>`;
        }
        
        // Page numbers (simplified)
        paginationHTML += '<div class="page-numbers">';
        for (let i = 1; i <= totalPages; i++) {
            const activeClass = i === currentPage ? 'active' : '';
            paginationHTML += `<button type="button" class="btn btn-sm btn-outline-primary page-btn ${activeClass}" onclick="loadCompaniesPage(${i})">${i}</button>`;
        }
        paginationHTML += '</div>';
        
        // Next button
        if (currentPage < totalPages) {
            paginationHTML += `<button type="button" class="btn btn-sm btn-outline-secondary" onclick="loadCompaniesPage(${currentPage + 1})">
                <i class="fa fa-chevron-right"></i>
            </button>`;
        }
        
        paginationHTML += '</div>';
        paginationHTML += `<div class="pagination-info">${currentPage}/${totalPages} (${companiesData.length} công ty)</div>`;
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
        
        // Show/hide selection controls - Always show for approved and printed tabs
        const selectionControls = document.getElementById('selectionControls');
        if (currentStatus === 'approved' || currentStatus === 'printed') {
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
    
    // Function to print by company - opens new window with all students of the company
    function printByCompany(companyName, taxCode) {
        if (!companyName) {
            showNotification('Thiếu thông tin công ty', 'error');
            return;
        }
        
        // Build URL parameters
        const params = new URLSearchParams({
            company: companyName
        });
        
        if (taxCode) {
            params.append('tax_code', taxCode);
        }
        
        // Open print page in new window
        const printUrl = `/datn/admin/pages/print_by_company.php?${params.toString()}`;
        window.open(printUrl, '_blank', 'width=1200,height=800');
        
        showNotification(`Đang mở trang in cho công ty: ${companyName}`, 'info');
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
        
        // Always show selection controls for approved and printed tabs
        if (currentStatus === 'approved' || currentStatus === 'printed') {
            selectionControls.classList.add('show');
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
        const printUrl = '/datn/admin/pages/print_selected_letters.php?' + 
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
        xhr.open('POST', '/datn/admin/pages/mark_as_printed.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    
                    if (response.success) {
                        alert(`✅ Đã chuyển ${selectedIds.length} giấy giới thiệu sang trạng thái "ĐÃ IN"!`);
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
        const modal = document.getElementById('printConfirmModal');
        if (modal) {
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                const modalInstance = bootstrap.Modal.getInstance(modal);
                if (modalInstance) modalInstance.hide();
            } else {
                modal.style.display = 'none';
                document.body.classList.remove('modal-open');
                const backdrop = document.querySelector('.modal-backdrop');
                if (backdrop) backdrop.remove();
            }
        }
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
        xhr.open('POST', '/datn/admin/pages/mark_as_waiting.php', true);
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
    
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/footer.php"; ?>
</body>
</html>