<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../error.log');
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/middleware/check_role.php";
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";

$idtaikhoan = $_SESSION['user_id'] ?? null;

// Lấy thông tin sinh viên, đợt, giáo viên hướng dẫn, email giáo viên
$stmt = $conn->prepare("
    SELECT 
        sv.Ten, 
        tksv.taikhoan AS Email, 
        sv.Lop, 
        dt.TenDot, dt.TrangThai,dt.ThoiGianBatDau, dt.ThoiGianKetThuc,
        gv.Ten AS TenGV, 
        tkgv.taikhoan AS EmailGV
    FROM sinhvien sv
    LEFT JOIN taikhoan tksv ON sv.ID_taikhoan = tksv.ID_taikhoan
    LEFT JOIN dotthuctap dt ON sv.ID_Dot = dt.ID
    LEFT JOIN giaovien gv ON sv.ID_GVHD = gv.ID_taikhoan
    LEFT JOIN taikhoan tkgv ON gv.ID_taikhoan = tkgv.ID_taikhoan
    WHERE sv.ID_taikhoan = ?
");
$stmt->execute([$idtaikhoan]);
$info = $stmt->fetch(PDO::FETCH_ASSOC);

$HienTrangThaiDot = '';
$trangThaiDot = $info['TrangThai'];
if ($trangThaiDot == 1)
    $HienTrangThaiDot = 'Đang chuẩn bị';
elseif ($trangThaiDot == 3)
    $HienTrangThaiDot = 'Hoàn Tất phân công';
elseif ($trangThaiDot == 2)
    $HienTrangThaiDot = 'Đã bắt đầu';
elseif ($trangThaiDot == 0)
    $HienTrangThaiDot = 'Đã kết thúc';

?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Thông tin cá nhân</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php"; ?>
<style>
        @media (max-width: 768px) {
    .panel-row {
      flex-direction: column;
      flex-wrap: wrap;
    }
    
    .panel-container {
      margin: 10px 0;
      max-width: 100%;
      flex: none;
    }
    
    .panel-container:not(:last-child)::after {
      display: none;
    }

    .status-indicator {
      font-size: 14px;
      padding: 12px 20px;
    }

    .panel-heading {
      font-size: 12px;
    }

    .panel-body p {
      font-size: 12px;
    }
  }

  @media (max-width: 992px) and (min-width: 769px) {
    .panel-container {
      max-width: 50%;
    }
    
    .panel-row {
      flex-wrap: wrap;
    }
  }
  /* === PANEL STYLES === */
  .panel-row {
    display: flex;
    justify-content: space-between;
    align-items: stretch;
    position: relative;
    margin-bottom: 30px;
    flex-wrap: nowrap;
  }

  .panel-container {
    position: relative;
    flex: 1;
    margin: 0 10px;
    max-width: 25%;
  }

  .panel-container:first-child {
    margin-left: 0;
  }

  .panel-container:last-child {
    margin-right: 0;
  }

  .panel-container:not(:last-child)::after {
    content: "→";
    position: absolute;
    top: 50%;
    right: -20px;
    transform: translateY(-50%);
    font-size: 24px;
    color: #007bff;
    z-index: 1;
    font-weight: bold;
    transition: all 0.3s ease;
  }

  /* Hiệu ứng đặc biệt cho mũi tên khi 2 panel đầu cùng active */
  .panel-container:first-child.has-active + .panel-container.has-active::before {
    content: "↔";
    position: absolute;
    top: 50%;
    left: -20px;
    transform: translateY(-50%);
    font-size: 24px;
    color: #28a745;
    z-index: 2;
    font-weight: bold;
    animation: pulse-arrow 2s infinite;
  }

  @keyframes pulse-arrow {
    0%, 100% { 
      transform: translateY(-50%) scale(1);
      color: #28a745;
    }
    50% { 
      transform: translateY(-50%) scale(1.2);
      color: #20c997;
    }
  }

  /* Màu mũi tên khi panel đang active */
  .panel-container.has-active:not(:last-child)::after {
    color: #28a745;
    animation: pulse-arrow 2s infinite;
  }

  .panel {
    border: 2px solid #e3eafc !important;
    border-radius: 16px !important;
    background: #fff;
    box-shadow: 0 2px 8px rgba(0, 123, 255, 0.1);
    transition: all 0.3s ease;
    height: 100%;
    position: relative;
    overflow: hidden;
  }

  .panel:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0, 123, 255, 0.2);
  }

  .panel.active-step {
    border: 2.5px solid #28a745 !important;
    box-shadow: 0 4px 24px rgba(40, 167, 69, 0.3);
    background: linear-gradient(135deg, #e8f5e8 0%, #f0fff0 100%);
    transform: scale(1.02);
    position: relative;
  }

  /* Hiệu ứng đặc biệt khi 2 panel đầu cùng active */
  .panel-container:first-child .panel.active-step + 
  .panel-container:nth-child(2) .panel.active-step {
    animation: sync-pulse 2s infinite;
  }

  .panel-container:first-child .panel.active-step {
    animation: sync-pulse 2s infinite;
  }

  @keyframes sync-pulse {
    0%, 100% { 
      transform: scale(1.02);
      box-shadow: 0 4px 24px rgba(40, 167, 69, 0.3);
    }
    50% { 
      transform: scale(1.05);
      box-shadow: 0 8px 35px rgba(40, 167, 69, 0.5);
    }
  }

  .panel.active-step::before {
    content: "ĐANG HOẠT ĐỘNG";
    position: absolute;
    top: 0;
    right: 0;
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
    padding: 5px 15px;
    font-size: 10px;
    font-weight: bold;
    transform: rotate(45deg) translate(25%, -50%);
    transform-origin: center;
    width: 120px;
    text-align: center;
    animation: glow 2s infinite alternate;
  }

  @keyframes glow {
    from {
      box-shadow: 0 0 5px rgba(40, 167, 69, 0.5);
    }
    to {
      box-shadow: 0 0 20px rgba(40, 167, 69, 0.8);
    }
  }

  .panel.active-step:hover {
    transform: scale(1.04);
    box-shadow: 0 8px 30px rgba(0, 123, 255, 0.4);
  }

  .panel-heading {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%) !important;
    border-bottom: 2px solid #dee2e6 !important;
    font-weight: 600;
    font-size: 14px;
    padding: 15px 20px;
    color: #495057;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }

  .panel.active-step .panel-heading {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important;
    color: white !important;
    border-bottom: 2px solid #20c997 !important;
    animation: header-glow 3s infinite alternate;
  }

  @keyframes header-glow {
    from {
      box-shadow: inset 0 0 10px rgba(255, 255, 255, 0.2);
    }
    to {
      box-shadow: inset 0 0 20px rgba(255, 255, 255, 0.4);
    }
  }

  .panel-heading i {
    margin-right: 8px;
    font-size: 16px;
  }

  .panel-body {
    padding: 20px !important;
  }

  .panel-body p {
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    font-size: 13px;
    line-height: 1.4;
  }

  .panel-body p i {
    margin-right: 8px;
    width: 16px;
    text-align: center;
  }
    </style>
</head>

<body>
    <div id="wrapper">
        <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/slidebar_Sinhvien.php"; ?>
        <div id="page-wrapper">
            <div id="pages-heading"><H1>Thông tin cá nhân</H1> </div>
            <div class="container-fluid">
                <div class="row" style="margin-top: 40px;">
                    <div class="col-md-12">
                        <div class="col-md-4 text-center">
                            <img src="/datn/access/img/accc.PNG" class="img-circle"
                                style="width: 120px; height: 120px; object-fit: cover; border: 3px solid #eee;">
                            <h3 style="margin-top: 10px;"><?= htmlspecialchars($info['Ten'] ?? 'Chưa cập nhật') ?></h3>
                            <p class="text-muted"><?= htmlspecialchars($info['Email'] ?? '') ?></p>

                        </div>

                        <div class="col-md-8">
                            <div class="panel panel-default">
                                <div class="panel-heading"><strong>Thông tin cá nhân</strong></div>
                                <div class="panel-body">
                                    <div class="row" style="margin-bottom: 15px;">
                                        <div class="col-sm-6">
                                            <label>Lớp:</label>
                                            <p><?= htmlspecialchars($info['Lop'] ?? '') ?></p>
                                        </div>
                                        <div class="col-sm-6">
                                            <label>Đợt thực tập:</label>
                                            <p><?= htmlspecialchars($info['TenDot'] ?? 'Chưa phân công') ?>:
                                                <?= $HienTrangThaiDot ?>  <span
                                                class="text-muted">(<?= date('d/m/Y', strtotime($info['ThoiGianBatDau'])) ?>
                                                - <?= date('d/m/Y', strtotime($info['ThoiGianKetThuc'])) ?>)</span></p>
                                           
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <label>Giáo viên hướng dẫn:</label>
                                            <p><?= htmlspecialchars($info['TenGV'] ?? 'Chưa phân công') ?></p>
                                        </div>
                                        <div class="col-sm-6">
                                            <label>Email GVHD:</label>
                                            <p><?= htmlspecialchars($info['EmailGV'] ?? '') ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12 text-center">
                        <a href="doimatkhau" class="btn btn-primary" style="margin-top: 20px;">
                            <span class="glyphicon glyphicon-lock"></span> Đổi mật khẩu
                        </a>
                    </div>
                </div>
            </div>
        </div>
</body>

</html>