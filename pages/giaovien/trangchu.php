<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';

require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";

$idTaiKhoan = $_SESSION['user_id'] ?? null;
$today = date('Y-m-d');
// Lấy danh sách ID đợt mà giáo viên này hướng dẫn sinh viên
// 1. Lấy danh sách đợt theo giáo viên hướng dẫn
$stmt2 = $conn->prepare("
    SELECT DISTINCT dt.ID, dt.TenDot
    FROM dot_giaovien dg
    JOIN dotthuctap dt ON dg.ID_Dot = dt.ID
    WHERE dg.ID_GVHD = ?
    ORDER BY dt.ID DESC
");
$stmt2->execute([$idTaiKhoan]);
$dsDot = $stmt2->fetchAll(PDO::FETCH_ASSOC);

// Tách mảng ID từ $dsDot để sử dụng trong IN (...)
$dsDotIDs = array_column($dsDot, 'ID');  // [1, 2, 3, ...]

// 2. Lấy thông báo thuộc các đợt này
$thongbaos = [];
if (!empty($dsDotIDs)) {
    $placeholders = implode(',', array_fill(0, count($dsDotIDs), '?'));
    $stmt = $conn->prepare("
        SELECT tb.ID, tb.TIEUDE, tb.NOIDUNG, tb.NGAYDANG, tb.ID_Dot, dt.TenDot
        FROM THONGBAO tb
        LEFT JOIN DotThucTap dt ON tb.ID_Dot = dt.ID
        WHERE tb.ID_Dot IN ($placeholders) AND tb.TRANGTHAI = 1
        ORDER BY tb.NGAYDANG DESC
        LIMIT 50
    ");
    $stmt->execute($dsDotIDs);
    $thongbaos = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 3. Lấy thông tin chi tiết các đợt mà giáo viên hướng dẫn
$currentDots = [];
if (!empty($dsDotIDs)) {
    $placeholders = implode(',', array_fill(0, count($dsDotIDs), '?'));
    $stmt = $conn->prepare("
        SELECT dt.ID, dt.TenDot, dt.ThoiGianBatDau, dt.ThoiGianKetThuc, dt.TrangThai, 
        COUNT(sv.ID_TaiKhoan) as SoLuongSV
        FROM DotThucTap dt
        LEFT JOIN SinhVien sv ON dt.ID = sv.ID_Dot AND sv.ID_GVHD = ?
        WHERE dt.ID IN ($placeholders)
        GROUP BY dt.ID, dt.TenDot, dt.ThoiGianBatDau, dt.ThoiGianKetThuc, dt.TrangThai
        ORDER BY dt.ThoiGianBatDau DESC
    ");

    // Ghép $idTaiKhoan + mảng ID
    $params = array_merge([$idTaiKhoan], $dsDotIDs);
    $stmt->execute($params);
    $currentDots = $stmt->fetchAll(PDO::FETCH_ASSOC);
}


// Tìm đợt đang hoạt động (ưu tiên trạng thái 3, 4, 5)
$activeDot = null;
$allDots = [];

foreach ($currentDots as $dot) {
    $trangthai = $dot['TrangThai'] ?? 0;
    $batdau = $dot['ThoiGianBatDau'] ?? '';
    $ketthuc = $dot['ThoiGianKetThuc'] ?? '';
    
    // Xác định trạng thái hiển thị
    if ($trangthai == -1) {
        $statusInfo = ['text' => 'Đã hủy', 'class' => 'danger', 'icon' => 'fa-ban'];
    } elseif ($trangthai == 0) {
        $statusInfo = ['text' => 'Đã kết thúc', 'class' => 'default', 'icon' => 'fa-check-circle'];
    } elseif ($trangthai == 3) {
        $statusInfo = ['text' => 'Tìm công ty & Xin giấy', 'class' => 'warning', 'icon' => 'fa-search'];
    } elseif ($trangthai == 4) {
        $statusInfo = ['text' => 'Đang thực tập', 'class' => 'success', 'icon' => 'fa-briefcase'];
    } elseif ($trangthai == 5) {
        $statusInfo = ['text' => 'Kết thúc - Chấm điểm', 'class' => 'info', 'icon' => 'fa-check-circle'];
    } elseif ($batdau && $today < $batdau) {
        $statusInfo = ['text' => 'Chuẩn bị', 'class' => 'warning', 'icon' => 'fa-clock-o'];
    } elseif ($batdau && $ketthuc && $today >= $batdau && $today <= $ketthuc) {
        $statusInfo = ['text' => 'Đang diễn ra', 'class' => 'success', 'icon' => 'fa-play-circle'];
    } else {
        $statusInfo = ['text' => 'Đã kết thúc', 'class' => 'default', 'icon' => 'fa-check-circle'];
    }
    
    // Chuẩn hóa dữ liệu
    $dot['TENDOT'] = $dot['TenDot'];
    $dot['THOIGIANBATDAU'] = $batdau;
    $dot['THOIGIANKETTHUC'] = $ketthuc;
    $dot['TRANGTHAI'] = $trangthai;
    $dot['STATUS_INFO'] = $statusInfo;
    
    $allDots[] = $dot;
    
    // Ưu tiên đợt đang hoạt động (trạng thái 3, 4, 5)
    if (in_array($trangthai, [3, 4, 5]) && !$activeDot) {
        $activeDot = $dot;
    }
}

// Nếu không có đợt hoạt động, lấy đợt gần nhất
if (!$activeDot && !empty($allDots)) {
    $activeDot = $allDots[0];
}

// Cập nhật trạng thái kết thúc
$updateStmt = $conn->prepare("UPDATE DotThucTap 
    SET TrangThai = 0 
    WHERE ThoiGianKetThuc <= :today AND TRANGTHAI != -1");
$updateStmt->execute(['today' => $today]);

// Cập nhật trạng thái đã bắt đầu
$updateStmt2 = $conn->prepare("UPDATE DotThucTap 
    SET TrangThai = 2 
    WHERE ThoiGianBatDau <= :today AND TrangThai != -1 AND TrangThai != 0");
$updateStmt2->execute(['today' => $today]);

// Xác định trạng thái và màu sắc hiển thị cho đợt chính
$statusInfo = ['text' => 'Chưa có đợt thực tập', 'class' => 'default', 'icon' => 'fa-info-circle'];
$currentDot = $activeDot; // Sử dụng activeDot làm currentDot để tương thích
if ($activeDot) {
    $statusInfo = $activeDot['STATUS_INFO'];
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
      <div class="container-fluid" style="margin-top: 60px;">
        <!-- Thông tin tất cả các đợt thực tập -->
        <div class="row">
          <div class="col-lg-12">
            <h1 class="page-header">
              <i class="fa fa-graduation-cap"></i> Các Đợt Thực Tập Hướng Dẫn
            </h1>
          </div>
        </div>
        
        <?php if (!empty($allDots)): ?>
        <!-- Tab Navigation -->
        <div class="row">
          <div class="col-lg-12">
            <ul class="nav nav-tabs internship-tabs" role="tablist">
              <?php foreach ($allDots as $index => $dot): ?>
              <li role="presentation" class="<?= $index === 0 ? 'active' : '' ?>">
                <a href="#dot-<?= $dot['ID'] ?>" aria-controls="dot-<?= $dot['ID'] ?>" role="tab" data-toggle="tab">
                  <i class="fa <?= $dot['STATUS_INFO']['icon'] ?>"></i>
                  <?= htmlspecialchars($dot['TENDOT']) ?>
                  <span class="tab-badge badge-<?= $dot['STATUS_INFO']['class'] ?>"><?= $dot['STATUS_INFO']['text'] ?></span>
                </a>
              </li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>

        <!-- Tab Content -->
        <div class="tab-content internship-tab-content">
          <?php foreach ($allDots as $index => $dot): ?>
          <div role="tabpanel" class="tab-pane <?= $index === 0 ? 'active' : '' ?>" id="dot-<?= $dot['ID'] ?>">
            <!-- Thông tin tóm tắt đợt -->
            <div class="row">
              <div class="col-lg-12">
                <div class="alert alert-<?= $dot['STATUS_INFO']['class'] ?> dot-summary">
                  <div class="row">
                    <div class="col-sm-8">
                      <h4><i class="fa <?= $dot['STATUS_INFO']['icon'] ?>"></i> <?= htmlspecialchars($dot['TENDOT']) ?></h4>
                      <p class="period-info">
                        <i class="fa fa-calendar"></i> 
                        <?= date('d/m/Y', strtotime($dot['THOIGIANBATDAU'])) ?> - 
                        <?= date('d/m/Y', strtotime($dot['THOIGIANKETTHUC'])) ?>
                      </p>
                    </div>
                    <div class="col-sm-4 text-right">
                      <div class="status-stats">
                        <i class="fa fa-users"></i> <?= $dot['SoLuongSV'] ?> sinh viên
                      </div>
                      <div class="tab-actions">
                        <a href="pages/giaovien/quanlygiaygioithieu?dot=<?= $dot['ID'] ?>" class="btn btn-sm btn-primary">
                          <i class="fa fa-file-text"></i> Giấy GT
                        </a>
                        <a href="pages/giaovien/xembaocaosinhvien?dot=<?= $dot['ID'] ?>" class="btn btn-sm btn-success">
                          <i class="fa fa-briefcase"></i> Báo cáo
                        </a>
                        <a href="pages/giaovien/chamdiem?dot=<?= $dot['ID'] ?>" class="btn btn-sm btn-warning">
                          <i class="fa fa-check-circle"></i> Chấm điểm
                        </a>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Quy trình 4 panel cho đợt này -->
            <div class="row process-panels">
              <div class="col-md-3 col-sm-6">
                <div class="process-panel panel-step-1 <?= ($dot['TRANGTHAI'] == 3) ? 'active' : '' ?>">
                  <div class="panel-icon">
                    <i class="fa fa-search"></i>
                  </div>
                  <div class="panel-content">
                    <h4>Tìm công ty thực tập</h4>
                    <p>Hướng dẫn sinh viên tìm kiếm và liên hệ công ty</p>
                    <div class="panel-badge">
                      <?php if ($dot['TRANGTHAI'] == 3): ?>
                        <span class="badge badge-success">ĐANG HOẠT ĐỘNG</span>
                      <?php else: ?>
                        <span class="badge badge-info">Hỗ trợ</span>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              </div>
              
              <div class="col-md-3 col-sm-6">
                <div class="process-panel panel-step-2 <?= ($dot['TRANGTHAI'] == 3) ? 'active' : '' ?>">
                  <div class="panel-icon">
                    <i class="fa fa-file-text"></i>
                  </div>
                  <div class="panel-content">
                    <h4>Xin giấy giới thiệu</h4>
                    <p>Phê duyệt và quản lý giấy giới thiệu thực tập</p>
                    <div class="panel-badge">
                      <?php if ($dot['TRANGTHAI'] == 3): ?>
                        <span class="badge badge-success">ĐANG HOẠT ĐỘNG</span>
                      <?php else: ?>
                        <span class="badge badge-default">Chờ kích hoạt</span>
                      <?php endif; ?>
                    </div>
                  </div>
                  <a href="pages/giaovien/quanlygiaygioithieu?dot=<?= $dot['ID'] ?>" class="panel-link"></a>
                </div>
              </div>
              
              <div class="col-md-3 col-sm-6">
                <div class="process-panel panel-step-3 <?= ($dot['TRANGTHAI'] == 4) ? 'active' : '' ?>">
                  <div class="panel-icon">
                    <i class="fa fa-briefcase"></i>
                  </div>
                  <div class="panel-content">
                    <h4>Thực tập báo cáo tuần</h4>
                    <p>Theo dõi báo cáo tuần và tiến độ thực tập</p>
                    <div class="panel-badge">
                      <?php if ($dot['TRANGTHAI'] == 4): ?>
                        <span class="badge badge-success">ĐANG HOẠT ĐỘNG</span>
                      <?php else: ?>
                        <span class="badge badge-default">Chờ kích hoạt</span>
                      <?php endif; ?>
                    </div>
                  </div>
                  <a href="pages/giaovien/xembaocaosinhvien?dot=<?= $dot['ID'] ?>" class="panel-link"></a>
                </div>
              </div>
              
              <div class="col-md-3 col-sm-6">
                <div class="process-panel panel-step-4 <?= ($dot['TRANGTHAI'] == 5) ? 'active' : '' ?>">
                  <div class="panel-icon">
                    <i class="fa fa-check-circle"></i>
                  </div>
                  <div class="panel-content">
                    <h4>Kết thúc và nộp báo cáo</h4>
                    <p>Chấm điểm và đánh giá báo cáo kết thúc</p>
                    <div class="panel-badge">
                      <?php if ($dot['TRANGTHAI'] == 5): ?>
                        <span class="badge badge-success">ĐANG HOẠT ĐỘNG</span>
                      <?php else: ?>
                        <span class="badge badge-warning">Cuối kỳ</span>
                      <?php endif; ?>
                    </div>
                  </div>
                  <a href="pages/giaovien/chamdiem?dot=<?= $dot['ID'] ?>" class="panel-link"></a>
                </div>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="row">
          <div class="col-lg-12">
            <div class="alert alert-info">
              <h4><i class="fa fa-info-circle"></i> Thông báo</h4>
              <p>Hiện tại bạn chưa có đợt thực tập nào để hướng dẫn.</p>
            </div>
          </div>
        </div>
        <?php endif; ?>

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
    
    // Initialize Bootstrap tabs
    $('.internship-tabs a').click(function (e) {
      e.preventDefault();
      $(this).tab('show');
    });
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

  /* Internship Tabs */
  .internship-tabs {
    border-bottom: 2px solid #337ab7;
    margin-bottom: 0;
  }

  .internship-tabs > li {
    margin-bottom: -2px;
  }

  .internship-tabs > li > a {
    border: 1px solid transparent;
    border-radius: 4px 4px 0 0;
    color: #666;
    padding: 10px 15px;
    margin-right: 2px;
    font-weight: 600;
    transition: all 0.3s ease;
  }

  .internship-tabs > li > a:hover {
    border-color: #e9ecef #e9ecef #337ab7;
    background-color: #f8f9fa;
    color: #337ab7;
  }

  .internship-tabs > li.active > a,
  .internship-tabs > li.active > a:hover,
  .internship-tabs > li.active > a:focus {
    color: #337ab7;
    background-color: #fff;
    border: 1px solid #337ab7;
    border-bottom-color: transparent;
    cursor: default;
  }

  .internship-tabs .tab-badge {
    font-size: 10px;
    padding: 2px 6px;
    margin-left: 8px;
    border-radius: 10px;
    font-weight: 600;
  }

  .tab-badge.badge-success {
    background-color: #5cb85c;
    color: white;
  }

  .tab-badge.badge-warning {
    background-color: #f0ad4e;
    color: white;
  }

  .tab-badge.badge-info {
    background-color: #5bc0de;
    color: white;
  }

  .tab-badge.badge-danger {
    background-color: #d9534f;
    color: white;
  }

  .tab-badge.badge-default {
    background-color: #999;
    color: white;
  }

  /* Tab Content */
  .internship-tab-content {
    background: #fff;
    border: 1px solid #337ab7;
    border-top: none;
    border-radius: 0 0 4px 4px;
    padding: 20px;
    margin-bottom: 30px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  }

  .internship-tab-content .tab-pane {
    display: none;
  }

  .internship-tab-content .tab-pane.active {
    display: block;
  }

  /* Dot Summary */
  .dot-summary {
    margin-bottom: 25px;
    border-radius: 6px;
    border-left: 4px solid #337ab7;
  }

  .dot-summary.alert-success {
    border-left-color: #5cb85c;
  }

  .dot-summary.alert-warning {
    border-left-color: #f0ad4e;
  }

  .dot-summary.alert-info {
    border-left-color: #5bc0de;
  }

  .dot-summary.alert-danger {
    border-left-color: #d9534f;
  }

  .dot-summary h4 {
    margin-top: 0;
    margin-bottom: 10px;
    color: #333;
  }

  .dot-summary .period-info {
    color: #666;
    font-size: 14px;
    margin-bottom: 0;
  }

  .dot-summary .status-stats {
    color: #337ab7;
    font-weight: bold;
    font-size: 16px;
    margin-bottom: 10px;
  }

  .tab-actions {
    margin-top: 10px;
  }

  .tab-actions .btn {
    margin-left: 5px;
    padding: 5px 10px;
    font-size: 12px;
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

  /* Dot Panel - New styles for individual internship periods */
  .dot-panel {
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border-radius: 8px;
    overflow: hidden;
  }

  .dot-panel .panel-heading {
    background: #f8f9fa;
    padding: 15px;
    border-bottom: 1px solid #e9ecef;
  }

  .dot-panel .panel-heading h4 {
    margin: 0;
    color: #333;
    font-weight: 600;
  }

  .dot-panel .panel-body {
    padding: 15px;
  }

  .dot-panel .period-info {
    color: #666;
    font-size: 14px;
    margin-bottom: 15px;
  }

  .dot-panel .status-stats {
    color: #337ab7;
    font-weight: bold;
    font-size: 14px;
  }

  /* Mini Process Row */
  .mini-process-row {
    display: flex;
    justify-content: space-between;
    margin: 15px 0;
    padding: 10px 0;
    border-top: 1px solid #e9ecef;
    border-bottom: 1px solid #e9ecef;
  }

  .mini-process-step {
    flex: 1;
    text-align: center;
    padding: 8px 5px;
    color: #999;
    transition: all 0.3s ease;
  }

  .mini-process-step.active {
    color: #5cb85c;
    background: rgba(92, 184, 92, 0.1);
    border-radius: 4px;
  }

  .mini-process-step i {
    font-size: 18px;
    margin-bottom: 5px;
    display: block;
  }

  .mini-step-text {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
  }

  /* Panel Actions */
  .panel-actions {
    margin-top: 15px;
    text-align: center;
  }

  .panel-actions .btn {
    margin: 0 3px;
    padding: 5px 10px;
    font-size: 12px;
  }

  /* Badge colors for dot panels */
  .dot-panel.panel-success .panel-heading {
    background: #d4edda;
    color: #155724;
  }

  .dot-panel.panel-warning .panel-heading {
    background: #fff3cd;
    color: #856404;
  }

  .dot-panel.panel-info .panel-heading {
    background: #cce7ff;
    color: #0c5460;
  }

  .dot-panel.panel-danger .panel-heading {
    background: #f8d7da;
    color: #721c24;
  }

  .dot-panel.panel-default .panel-heading {
    background: #f8f9fa;
    color: #6c757d;
  
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

  /* Page wrapper adjustment for sidebar */
  #page-wrapper {
    padding-top: 20px;
  }

  /* Responsive adjustment for smaller screens */
  @media (max-width: 768px) {
    .container-fluid {
      margin-top: 80px !important;
    }
    
    .internship-tabs > li {
      width: 100%;
      margin-bottom: 2px;
    }
    
    .internship-tabs > li > a {
      display: block;
      text-align: center;
      padding: 8px 10px;
      font-size: 14px;
    }
    
    .internship-tab-content {
      padding: 15px;
    }
    
    .dot-summary {
      margin-bottom: 20px;
    }
    
    .dot-summary h4 {
      font-size: 16px;
    }
    
    .dot-summary .status-stats {
      font-size: 14px;
      text-align: center;
      margin-top: 10px;
    }
    
    .tab-actions {
      text-align: center;
    }
    
    .tab-actions .btn {
      display: block;
      margin: 5px 0;
      width: 100%;
      font-size: 12px;
    }
    
    .dot-panel {
      margin-bottom: 15px;
    }
    
    .dot-panel .panel-heading {
      padding: 10px;
    }
    
    .dot-panel .panel-body {
      padding: 10px;
    }
    
    .mini-process-row {
      flex-wrap: wrap;
    }
    
    .mini-process-step {
      min-width: 50%;
      margin-bottom: 10px;
    }
    
    .panel-actions .btn {
      display: block;
      margin: 5px 0;
      width: 100%;
    }
    
    .notification-content {
      padding-left: 10px;
    }
  }

  @media (max-width: 480px) {
    .container-fluid {
      margin-top: 100px !important;
    }
    
    .internship-tabs > li > a {
      font-size: 12px;
      padding: 6px 8px;
    }
    
    .tab-badge {
      display: block;
      margin: 5px 0 0 0;
    }
    
    .internship-tab-content {
      padding: 10px;
    }
    
    .dot-summary h4 {
      font-size: 14px;
    }
    
    .dot-summary .status-stats {
      font-size: 12px;
    }
    
    .mini-process-step {
      min-width: 100%;
    }
    
    .dot-panel .panel-heading h4 {
      font-size: 14px;
    }
    
    .status-stats {
      font-size: 12px;
    }
  }
</style>