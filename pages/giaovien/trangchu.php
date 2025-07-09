<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';

require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";

$idTaiKhoan = $_SESSION['user_id'] ?? null;

// Lấy danh sách ID đợt mà giáo viên này hướng dẫn sinh viên
$stmt = $conn->prepare("
    SELECT DISTINCT sv.ID_Dot
    FROM SinhVien sv
    WHERE sv.ID_GVHD = ?
");
$stmt->execute([$idTaiKhoan]);
$dsDot = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Lấy thông báo thuộc các đợt này
$thongbaos = [];
if (!empty($dsDot)) {
    $placeholders = implode(',', array_fill(0, count($dsDot), '?'));
    $stmt = $conn->prepare("
        SELECT tb.ID, tb.TIEUDE, tb.NOIDUNG, tb.NGAYDANG, tb.ID_Dot, dt.TenDot
        FROM THONGBAO tb
        LEFT JOIN DotThucTap dt ON tb.ID_Dot = dt.ID
        WHERE tb.ID_Dot IN ($placeholders) AND tb.TRANGTHAI=1
        ORDER BY tb.NGAYDANG DESC
        LIMIT 50
    ");
    $stmt->execute($dsDot);
    $thongbaos = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Lấy thông tin đợt thực tập hiện tại
$currentDot = null;
if (!empty($dsDot)) {
    $stmt = $conn->prepare("
        SELECT dt.*, COUNT(sv.ID) as SoLuongSV
        FROM DotThucTap dt
        LEFT JOIN SinhVien sv ON dt.ID = sv.ID_Dot AND sv.ID_GVHD = ?
        WHERE dt.ID IN (" . implode(',', array_fill(0, count($dsDot), '?')) . ")
        GROUP BY dt.ID
        ORDER BY dt.THOIGIANBATDAU DESC
        LIMIT 1
    ");
    $params = array_merge([$idTaiKhoan], $dsDot);
    $stmt->execute($params);
    $currentDot = $stmt->fetch(PDO::FETCH_ASSOC);
}

$today = date('Y-m-d');

// Cập nhật trạng thái kết thúc
$updateStmt = $conn->prepare("UPDATE DOTTHUCTAP 
    SET TRANGTHAI = 0 
    WHERE THOIGIANKETTHUC <= :today AND TRANGTHAI = 2");
$updateStmt->execute(['today' => $today]);

// Cập nhật trạng thái đã bắt đầu
$updateStmt2 = $conn->prepare("UPDATE DOTTHUCTAP 
    SET TRANGTHAI = 2 
    WHERE THOIGIANBATDAU <= :today AND TRANGTHAI != -1 AND TRANGTHAI != 0");
$updateStmt2->execute(['today' => $today]);

// Xác định trạng thái và màu sắc hiển thị
$statusInfo = ['text' => 'Chưa có đợt thực tập', 'class' => 'default', 'icon' => 'fa-info-circle'];
if ($currentDot) {
    $batdau = $currentDot['THOIGIANBATDAU'];
    $ketthuc = $currentDot['THOIGIANKETTHUC'];
    $trangthai = $currentDot['TRANGTHAI'];
    
    if ($trangthai == -1) {
        $statusInfo = ['text' => 'Đợt đã bị hủy', 'class' => 'danger', 'icon' => 'fa-ban'];
    } elseif ($trangthai == 0) {
        $statusInfo = ['text' => 'Đợt đã kết thúc', 'class' => 'default', 'icon' => 'fa-check-circle'];
    } elseif ($today < $batdau) {
        $statusInfo = ['text' => 'Đợt chuẩn bị', 'class' => 'warning', 'icon' => 'fa-clock-o'];
    } elseif ($today >= $batdau && $today <= $ketthuc) {
        $statusInfo = ['text' => 'Đợt đang diễn ra', 'class' => 'success', 'icon' => 'fa-play-circle'];
    } else {
        $statusInfo = ['text' => 'Đợt đã kết thúc', 'class' => 'default', 'icon' => 'fa-check-circle'];
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <title>Trang Chủ</title>
  <?php
  require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php";

  ?>
</head>

<body>
  <div id="wrapper">
    <?php
    require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/slidebar_GiaoVien.php";
    ?>
    <div id="page-wrapper">
      <div class="container-fluid">
        <!-- Thông tin đợt thực tập -->
        <?php if ($currentDot): ?>
        <div class="row">
          <div class="col-lg-12">
            <div class="alert alert-<?= $statusInfo['class'] ?> status-alert">
              <div class="row">
                <div class="col-sm-8">
                  <h4><i class="fa <?= $statusInfo['icon'] ?>"></i> <?= htmlspecialchars($currentDot['TENDOT']) ?></h4>
                  <p class="status-text">
                    <span class="label label-<?= $statusInfo['class'] ?>"><?= $statusInfo['text'] ?></span>
                    <span class="period-info">
                      <?= date('d/m/Y', strtotime($currentDot['THOIGIANBATDAU'])) ?> - 
                      <?= date('d/m/Y', strtotime($currentDot['THOIGIANKETTHUC'])) ?>
                    </span>
                  </p>
                </div>
                <div class="col-sm-4 text-right">
                  <div class="status-stats">
                    <i class="fa fa-users"></i> <?= $currentDot['SoLuongSV'] ?> sinh viên
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <div class="row">
          <div class="col-lg-12">
            <h1 class="page-header">
              <i class="fa fa-graduation-cap"></i> Quy Trình Hướng Dẫn Thực Tập
            </h1>
          </div>
        </div>
        
        <div class="row process-panels">
          <div class="col-md-3 col-sm-6">
            <div class="process-panel panel-step-1">
              <div class="panel-icon">
                <i class="fa fa-search"></i>
              </div>
              <div class="panel-content">
                <h4>Hỗ trợ tìm kiếm</h4>
                <p>Hướng dẫn sinh viên tìm công ty thực tập phù hợp</p>
                <div class="panel-badge">
                  <span class="badge badge-info">Hỗ trợ</span>
                </div>
              </div>
            </div>
          </div>
          
          <div class="col-md-3 col-sm-6">
            <div class="process-panel panel-step-2 <?= ($currentDot && $currentDot['TRANGTHAI'] >= 1) ? 'active' : '' ?>">
              <div class="panel-icon">
                <i class="fa fa-file-text"></i>
              </div>
              <div class="panel-content">
                <h4>Giấy giới thiệu</h4>
                <p>Phê duyệt và quản lý giấy giới thiệu</p>
                <div class="panel-badge">
                  <?php if ($currentDot && $currentDot['TRANGTHAI'] >= 1): ?>
                    <span class="badge badge-success">ĐANG HOẠT ĐỘNG</span>
                  <?php else: ?>
                    <span class="badge badge-default">Chờ kích hoạt</span>
                  <?php endif; ?>
                </div>
              </div>
              <a href="pages/giaovien/quanlygiaygioithieu" class="panel-link"></a>
            </div>
          </div>
          
          <div class="col-md-3 col-sm-6">
            <div class="process-panel panel-step-3 <?= ($currentDot && $currentDot['TRANGTHAI'] == 2) ? 'active' : '' ?>">
              <div class="panel-icon">
                <i class="fa fa-briefcase"></i>
              </div>
              <div class="panel-content">
                <h4>Theo dõi thực tập</h4>
                <p>Xem báo cáo tuần và theo dõi tiến độ</p>
                <div class="panel-badge">
                  <?php if ($currentDot && $currentDot['TRANGTHAI'] == 2): ?>
                    <span class="badge badge-success">ĐANG HOẠT ĐỘNG</span>
                  <?php else: ?>
                    <span class="badge badge-default">Chờ kích hoạt</span>
                  <?php endif; ?>
                </div>
              </div>
              <a href="pages/giaovien/xembaocaosinhvien" class="panel-link"></a>
            </div>
          </div>
          
          <div class="col-md-3 col-sm-6">
            <div class="process-panel panel-step-4">
              <div class="panel-icon">
                <i class="fa fa-check-circle"></i>
              </div>
              <div class="panel-content">
                <h4>Chấm điểm</h4>
                <p>Đánh giá và chấm điểm kết thúc</p>
                <div class="panel-badge">
                  <span class="badge badge-warning">Cuối kỳ</span>
                </div>
              </div>
              <a href="pages/giaovien/chamdiem" class="panel-link"></a>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-lg-12">
            <h2 class="section-header">
              <i class="fa fa-bell"></i> Thông Báo Các Đợt Hướng Dẫn
            </h2>
          </div>
        </div>
        
        <div class="container-fluid">
          <div id="notification-list" class="notification-container">
            <!-- Thông báo sẽ được load bằng JavaScript -->
          </div>
          
          <div class="text-center pagination-container" style="margin-top: 20px;">
            <button id="prevBtn" class="btn btn-default btn-nav">
              <i class="fa fa-chevron-left"></i> Trước
            </button>
            <span id="pageInfo" class="page-info"></span>
            <button id="nextBtn" class="btn btn-default btn-nav">
              Sau <i class="fa fa-chevron-right"></i>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php
  require $_SERVER['DOCUMENT_ROOT'] . "/datn/template/footer.php"
    ?>
  <script>
    const thongbaos = <?= json_encode($thongbaos) ?>;
    const pageSize = 5;
    let currentPage = 0;

    function renderNotifications() {
      const container = document.getElementById('notification-list');
      const pageInfo = document.getElementById('pageInfo');

      container.classList.add('fade-out');

      setTimeout(() => {
        const start = currentPage * pageSize;
        const end = start + pageSize;
        const list = thongbaos.slice(start, end);

        container.innerHTML = '';

        if (thongbaos.length === 0) {
          // Hiển thị thông báo khi không có thông báo nào
          const emptyHtml = `
            <div class="empty-notification">
              <div class="text-center">
                <i class="fa fa-bell-slash fa-4x text-muted" style="margin-bottom: 20px;"></i>
                <h3 class="text-muted">Chưa có thông báo</h3>
                <p class="text-muted">Hiện tại chưa có thông báo nào cho các đợt thực tập bạn hướng dẫn.</p>
                <p class="text-muted"><i class="fa fa-lightbulb-o"></i> Thông báo sẽ xuất hiện khi có cập nhật từ các đợt thực tập.</p>
              </div>
            </div>
          `;
          container.innerHTML = emptyHtml;
          pageInfo.textContent = '';
        } else if (list.length === 0) {
          // Hiển thị khi hết thông báo trong trang hiện tại
          const noMoreHtml = `
            <div class="empty-notification">
              <div class="text-center">
                <i class="fa fa-info-circle fa-3x text-info" style="margin-bottom: 15px;"></i>
                <h4 class="text-info">Đã hết thông báo</h4>
                <p class="text-muted">Bạn đã xem hết tất cả thông báo.</p>
              </div>
            </div>
          `;
          container.innerHTML = noMoreHtml;
          pageInfo.textContent = '';
        } else {
          list.forEach(tb => {
            const html = `
              <div class="notification-card">
                <div class="row">
                  <div class="col-sm-2 col-xs-3 notification-image-container">
                    <a href="pages/giaovien/chitietthongbao.php?id=${tb.ID}">
                      <img src="/datn/uploads/Images/ThongBao.jpg" alt="${tb.TIEUDE}" class="notification-image">
                    </a>
                  </div>
                  <div class="col-sm-10 col-xs-9">
                    <div class="notification-content">
                      <a href="pages/giaovien/chitietthongbao.php?id=${tb.ID}" class="notification-title">
                        ${tb.TIEUDE}
                      </a>
                      <div class="notification-meta">
                        <span class="meta-item">
                          <i class="fa fa-bullhorn"></i> Thông báo
                        </span>
                        <span class="meta-separator">|</span>
                        <span class="meta-item">
                          <i class="fa fa-calendar"></i> ${new Date(tb.NGAYDANG).toLocaleDateString('vi-VN')}
                        </span>
                        ${tb.TenDot ? `
                          <span class="meta-separator">|</span>
                          <span class="meta-item">
                            <i class="fa fa-tag"></i> ${tb.TenDot}
                          </span>
                        ` : ''}
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            `;
            container.insertAdjacentHTML('beforeend', html);
          });
          
          // Cập nhật thông tin phân trang
          const totalPages = Math.ceil(thongbaos.length / pageSize);
          pageInfo.textContent = `Trang ${currentPage + 1} / ${totalPages}`;
        }

        // Cập nhật trạng thái nút
        document.getElementById('prevBtn').disabled = currentPage === 0;
        document.getElementById('nextBtn').disabled = end >= thongbaos.length;

        // Ẩn/hiện nút phân trang
        const paginationContainer = document.querySelector('.pagination-container');
        if (thongbaos.length === 0) {
          paginationContainer.style.display = 'none';
        } else {
          paginationContainer.style.display = 'block';
        }

        container.classList.remove('fade-out');
        container.classList.add('fade-in');

        setTimeout(() => container.classList.remove('fade-in'), 500);
      }, 300);
    }

    document.getElementById('prevBtn').addEventListener('click', () => {
      if (currentPage > 0) {
        currentPage--;
        renderNotifications();
      }
    });

    document.getElementById('nextBtn').addEventListener('click', () => {
      if ((currentPage + 1) * pageSize < thongbaos.length) {
        currentPage++;
        renderNotifications();
      }
    });

    renderNotifications();
  </script>
</body>
</html>
<style>
  /* Status Alert */
  .status-alert {
    border-left: 4px solid #337ab7;
    border-radius: 8px;
    margin-bottom: 25px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  }
  
  .status-alert.alert-success {
    border-left-color: #5cb85c;
  }
  
  .status-alert.alert-warning {
    border-left-color: #f0ad4e;
  }
  
  .status-alert.alert-danger {
    border-left-color: #d9534f;
  }
  
  .status-text {
    margin-bottom: 0;
    font-size: 14px;
  }
  
  .period-info {
    margin-left: 10px;
    color: #666;
  }
  
  .status-stats {
    font-size: 16px;
    font-weight: bold;
    color: #337ab7;
    margin-top: 10px;
  }

  /* Section Header */
  .section-header {
    color: #337ab7;
    border-bottom: 2px solid #337ab7;
    padding-bottom: 10px;
    margin-bottom: 25px;
  }

  /* Process Panels */
  .process-panels {
    margin-bottom: 40px;
  }

  .process-panel {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    text-align: center;
    position: relative;
    transition: all 0.3s ease;
    min-height: 200px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  }

  .process-panel:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
  }

  .process-panel.active {
    border-color: #5cb85c;
    box-shadow: 0 0 10px rgba(92, 184, 92, 0.3);
  }

  .process-panel .panel-icon {
    font-size: 48px;
    color: #337ab7;
    margin-bottom: 15px;
  }

  .process-panel.active .panel-icon {
    color: #5cb85c;
  }

  .process-panel h4 {
    color: #333;
    margin-bottom: 10px;
    font-weight: 600;
  }

  .process-panel p {
    color: #666;
    margin-bottom: 15px;
    line-height: 1.4;
  }

  .panel-badge {
    position: absolute;
    top: 10px;
    right: 10px;
  }

  .panel-badge .badge {
    font-size: 10px;
    padding: 4px 8px;
  }

  .badge-success {
    background-color: #5cb85c;
  }

  .badge-info {
    background-color: #5bc0de;
  }

  .badge-warning {
    background-color: #f0ad4e;
  }

  .badge-default {
    background-color: #999;
  }

  .panel-link {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    text-decoration: none;
    z-index: 1;
  }

  /* Notification Container */
  .notification-container {
    min-height: 300px;
  }

  .notification-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 15px;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  }

  .notification-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  }

  .notification-image-container {
    text-align: center;
  }

  .notification-image {
    width: 80px;
    height: 80px;
    border-radius: 8px;
    object-fit: cover;
    border: 2px solid #ddd;
    transition: all 0.3s ease;
  }

  .notification-image:hover {
    border-color: #337ab7;
  }

  .notification-content {
    padding-left: 15px;
  }

  .notification-title {
    font-size: 18px;
    font-weight: 600;
    color: #333;
    text-decoration: none;
    display: block;
    margin-bottom: 10px;
  }

  .notification-title:hover {
    color: #337ab7;
    text-decoration: none;
  }

  .notification-meta {
    color: #666;
    font-size: 14px;
  }

  .meta-item {
    margin-right: 8px;
  }

  .meta-separator {
    margin: 0 8px;
    color: #ccc;
  }

  /* Empty State */
  .empty-notification {
    background: #f9f9f9;
    border: 2px dashed #ddd;
    border-radius: 8px;
    padding: 60px 20px;
    text-align: center;
    margin: 20px 0;
  }

  .empty-notification h3 {
    margin-bottom: 15px;
  }

  .empty-notification p {
    margin-bottom: 10px;
    line-height: 1.6;
  }

  /* Pagination */
  .pagination-container {
    padding: 20px 0;
    border-top: 1px solid #eee;
  }

  .btn-nav {
    margin: 0 10px;
    padding: 8px 16px;
    border-radius: 4px;
    transition: all 0.3s ease;
  }

  .btn-nav:hover:not(:disabled) {
    background-color: #337ab7;
    color: white;
    border-color: #337ab7;
  }

  .btn-nav:disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }

  .page-info {
    margin: 0 15px;
    font-weight: 600;
    color: #666;
  }

  /* Fade Effects */
  #notification-list {
    transition: opacity 0.3s ease;
    opacity: 1;
  }

  #notification-list.fade-out {
    opacity: 0;
  }

  #notification-list.fade-in {
    opacity: 1;
  }

  /* Responsive Design */
  @media (max-width: 768px) {
    .process-panel {
      min-height: 160px;
      margin-bottom: 15px;
    }
    
    .process-panel .panel-icon {
      font-size: 36px;
    }
    
    .notification-image {
      width: 60px;
      height: 60px;
    }
    
    .notification-content {
      padding-left: 10px;
    }
    
    .notification-title {
      font-size: 16px;
    }
    
    .status-stats {
      text-align: center;
      margin-top: 15px;
    }
  }

  @media (max-width: 480px) {
    .process-panel {
      min-height: 140px;
    }
    
    .notification-card {
      padding: 15px;
    }
    
    .meta-item {
      display: block;
      margin-bottom: 5px;
    }
    
    .meta-separator {
      display: none;
    }
  }

  /* Container Responsive */
  .container,
  .container-fluid,
  #wrapper,
  #page-wrapper {
    max-width: 100%;
    overflow-x: hidden;
  }
</style>