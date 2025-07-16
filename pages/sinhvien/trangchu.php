<?php
// Bật error reporting để debug
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../error.log');

// Bắt đầu session nếu chưa có
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra session user
if (!isset($_SESSION['user'])) {
    header("Location: " . ($_SERVER['HTTPS'] ? 'https' : 'http') . "://" . $_SERVER['HTTP_HOST'] . "/datn/login.php");
    exit;
}

// Include files với error handling
try {
    require_once __DIR__ . '/../../middleware/check_role.php';
    require_once __DIR__ . "/../../template/config.php";
} catch (Exception $e) {
    die("Lỗi load file: " . $e->getMessage());
}

$idTaiKhoan = $_SESSION['user']['ID_TaiKhoan'] ?? null;
$today = date('Y-m-d');

// Khởi tạo biến mặc định
$baocao = null;
$baocao_dir = null;
$baocao_trangthai = null;
$ten_sv = '';
$cho_phep_nop = false;
$errorMsg = '';
$thongbaos = [];
$idDot = null;
$trangThaiDot = null;

try {
    // Cập nhật trạng thái kết thúc (sử dụng tên bảng đúng)
    $updateStmt = $conn->prepare("UPDATE dotthuctap 
        SET TrangThai = 0 
        WHERE ThoiGianKetThuc <= :today AND TrangThai != -1");
    $updateStmt->execute(['today' => $today]);

    // Cập nhật trạng thái đã bắt đầu
    $updateStmt2 = $conn->prepare("UPDATE dotthuctap 
        SET TrangThai = 2 
        WHERE ThoiGianBatDau <= :today AND TrangThai > 0");
    $updateStmt2->execute(['today' => $today]);

    $now = date('Y-m-d H:i:s');

    // Cập nhật trạng thái khảo sát: 2 = Đã hết hạn (tên bảng và cột đúng)
    $updateKhaoSatStmt = $conn->prepare("UPDATE khaosat 
        SET TrangThai = 2 
        WHERE ThoiHan <= :now AND TrangThai != 2 AND TrangThai != 0");
    $updateKhaoSatStmt->execute(['now' => $now]);

    // Lấy thông tin đợt của sinh viên
    $stmt = $conn->prepare("SELECT sv.ID_Dot, dt.TrangThai 
        FROM sinhvien sv 
        LEFT JOIN dotthuctap dt ON sv.ID_Dot = dt.ID 
        WHERE sv.ID_TaiKhoan = ?");
    $stmt->execute([$idTaiKhoan]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $idDot = $row['ID_Dot'] ?? null;
    $trangThaiDot = $row['TrangThai'] ?? null;

    // Lấy tên sinh viên và id tài khoản giáo viên hướng dẫn
    $stmt = $conn->prepare("SELECT Ten, ID_GVHD FROM sinhvien WHERE ID_TaiKhoan = ?");
    $stmt->execute([$idTaiKhoan]);
    $row_sv = $stmt->fetch(PDO::FETCH_ASSOC);
    $id_gvhd = $row_sv['ID_GVHD'] ?? null;

    // Kiểm tra trạng thái cho phép nộp báo cáo tổng kết của giáo viên hướng dẫn
    if ($id_gvhd) {
        $stmt = $conn->prepare("SELECT TrangThai FROM baocaotongket WHERE ID_TaiKhoan = ? AND ID_Dot = ?");
        $stmt->execute([$id_gvhd, $idDot]);
        $trangthai_baocaotongket = $stmt->fetchColumn();
        $cho_phep_nop = ($trangthai_baocaotongket == 1);
    }

    // Lấy thông báo
    if ($idTaiKhoan == null) {
        $stmt = $conn->prepare("
            SELECT tb.ID, tb.TieuDe, tb.NoiDung, tb.NgayDang, tb.ID_Dot, dt.TenDot
            FROM thongbao tb
            LEFT JOIN dotthuctap dt ON tb.ID_Dot = dt.ID
            WHERE tb.TrangThai = 1
            ORDER BY tb.NgayDang DESC
            LIMIT 10
        ");
        $stmt->execute();
        $thongbaos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($idDot) {
        $stmt = $conn->prepare("
            SELECT tb.ID, tb.TieuDe, tb.NoiDung, tb.NgayDang, tb.ID_Dot, dt.TenDot
            FROM thongbao tb
            LEFT JOIN dotthuctap dt ON tb.ID_Dot = dt.ID
            WHERE tb.ID_Dot = ? AND tb.TrangThai=1
            ORDER BY tb.NgayDang DESC
            LIMIT 10
        ");
        $stmt->execute([$idDot]);
        $thongbaos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lấy trạng thái đợt cuối cùng
    $stmt = $conn->prepare("SELECT dt.TrangThai 
        FROM sinhvien sv 
        LEFT JOIN dotthuctap dt ON sv.ID_Dot = dt.ID 
        WHERE sv.ID_TaiKhoan = ?");
    $stmt->execute([$idTaiKhoan]);
    $trangThaiDot = $stmt->fetchColumn();

} catch (PDOException $e) {
    // Ghi log lỗi
    error_log("Database error in trangchu.php: " . $e->getMessage());
    $errorMsg = "Có lỗi xảy ra khi tải dữ liệu. Vui lòng thử lại sau.";
    
    // Đặt giá trị mặc định để tránh lỗi
    $thongbaos = [];
    $trangThaiDot = 0;
    $cho_phep_nop = false;
    $idDot = null;
}

// Xử lý trạng thái panel
$panelActive = [];
$statusInfo = [
  'message' => '',
  'class' => '',
  'icon' => ''
];

if ($idTaiKhoan) {
  if ($trangThaiDot >= 1) {
    if ($trangThaiDot == 1) {
      $panelActive = [0]; // Nổi bật cả tìm công ty và xin giấy giới thiệu
      $statusInfo = [
        'message' => 'Giai đoạn: Tìm công ty và xin giấy giới thiệu thực tập (cùng thực hiện)',
        'class' => 'status-finding',
        'icon' => 'fa-search'
      ];
    } elseif ($trangThaiDot == 2) {
      if ($cho_phep_nop) {
        $panelActive = [3]; // Kết thúc và nộp báo cáo
        $statusInfo = [
          'message' => 'Giai đoạn: Kết thúc và nộp báo cáo',
          'class' => 'status-completion',
          'icon' => 'fa-check-circle'
        ];
      } else {
        $panelActive = [2]; // Thực tập và báo cáo tuần
        $statusInfo = [
          'message' => 'Giai đoạn: Thực tâp, báo cáo tuần',
          'class' => 'status-finding',
          'icon' => 'fa-search'
        ];
      }
    } elseif ($trangThaiDot == 3) {
      $panelActive = [0, 1];
      $statusInfo = [
        'message' => 'Giai đoạn: Tìm công ty và xin giấy giới thiệu thực tập (cùng thực hiện)',
        'class' => 'status-internship',
        'icon' => 'fa-briefcase'
      ];
    }
  } elseif ($trangThaiDot <= 0) {
    // Đợt đã kết thúc
    $statusInfo = [
      'message' => 'Đợt thực tập đã kết thúc',
      'class' => 'status-ended',
      'icon' => 'fa-flag-checkered'
    ];
  } elseif ($trangThaiDot < 2 && $trangThaiDot > 0) {
    // Đợt đang chuẩn bị bắt đầu
    $statusInfo = [
      'message' => 'Đợt đang chuẩn bị bắt đầu',
      'class' => 'status-preparing',
      'icon' => 'fa-clock-o'
    ];
  } else {
    // Trạng thái cũ cho các đợt khác
    if ($trangThaiDot == 1)
      $panelActive = [0];
    elseif ($trangThaiDot == 2)
      $panelActive = [1];
    elseif ($trangThaiDot == 0)
      $panelActive = [2];
  }
}

?>
<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <title>Trang Chủ</title>
  <?php
  require_once __DIR__ . "/../../template/head.php";
  ?>
  <style>

  </style>
</head>

<body>

  <div id="wrapper">
    <?php
    require_once __DIR__ . "/../../template/slidebar_Sinhvien.php";
    ?>
    <div id="page-wrapper">
      <div class="container-fluid">
        <div class="row">
          <div class="col-lg-12">
            <h1 class="page-header">Quy Trình Thực Tập Tốt Nghiệp</h1>

            <?php if (!empty($errorMsg)): ?>
              <div class="alert alert-danger">
                <i class="fa fa-exclamation-triangle"></i> <?= htmlspecialchars($errorMsg) ?>
              </div>
            <?php endif; ?>

            <?php if ($idTaiKhoan && $statusInfo['message']): ?>
              <div class="status-indicator <?= $statusInfo['class'] ?>">
                <i class="fa <?= $statusInfo['icon'] ?>"></i>
                <span class="status-message"><?= $statusInfo['message'] ?></span>
              </div>
            <?php endif; ?>
          </div>
        </div>
        <div class="row panel-row">
          <div class="col-md-3 panel-container">
            <a href="pages/sinhvien/xemdanhsachcongty" style="text-decoration: none; color: inherit;">
              <div class="panel panel-default <?= in_array(0, $panelActive) ? 'active-step' : '' ?>"
                style="min-height: 180px;">
                <div class="panel-heading">
                  <i class="fa fa-search"></i> Tìm công ty thực tập
                </div>
                <div class="panel-body">
                  <p><i class="fa fa-check-circle text-success"></i> Xem danh sách công ty từ các khóa trước</p>
                  <p><i class="fa fa-check-circle text-success"></i> Tìm trên các trang web</p>
                  <p><i class="fa fa-check-circle text-success"></i> Liên hệ với các công ty</p>
                </div>
              </div>
            </a>
          </div>
          <div class="col-md-3 panel-container">
            <a <?= ($trangThaiDot >= 2) ? 'href="pages/sinhvien/dangkygiaygioithieu"' : '' ?>
              style="text-decoration: none; color: inherit;">
              <div class="panel panel-default <?= in_array(1, $panelActive) ? 'active-step' : '' ?>"
                style="min-height: 180px;">
                <div class="panel-heading">
                  <i class="fa fa-file-text"></i> Xin giấy giới thiệu thực tập
                </div>
                <div class="panel-body">
                  <p><i class="fa fa-info-circle text-info"></i> Gửi thông tin đăng ký xin giấy giới thiệu thực tập</p>
                  <p><i class="fa fa-clock-o text-warning"></i> Chờ phê duyệt từ khoa</p>
                  <?php if ($trangThaiDot < 2): ?>
                    <p><i class="fa fa-lock text-muted"></i> <small>Chưa mở</small></p>
                  <?php endif; ?>
                </div>
              </div>
            </a>
          </div>
          <div class="col-md-3 panel-container">
            <a <?= (!$trangThaiDot >= 2 && $trangThaiDot != 3) ? 'href="pages/sinhvien/baocaotuan"' : '' ?>
              style="text-decoration: none; color: inherit;">
              <div class="panel panel-default <?= in_array(2, $panelActive) ? 'active-step' : '' ?>"
                style="min-height: 180px;">
                <div class="panel-heading">
                  <i class="fa fa-briefcase"></i> Thực tập, báo cáo tuần
                </div>
                <div class="panel-body">
                  <p><i class="fa fa-calendar text-primary"></i> Bắt đầu thực tập</p>
                  <p><i class="fa fa-file-text-o text-primary"></i> Gửi báo cáo hằng tuần</p>
                  <?php if ($trangThaiDot >= 2 && $trangThaiDot != 3): ?>
                    <p><i class="fa fa-lock text-muted"></i> <small>Chưa mở</small></p>
                  <?php endif; ?>
                </div>
              </div>
            </a>
          </div>
          <div class="col-md-3 panel-container">
            <a <?= ($cho_phep_nop) ? 'href="pages/sinhvien/nopketqua"' : 'href="#" data-toggle="modal" data-target="#detailModal"' ?> style="text-decoration: none; color: inherit;">
              <div class="panel panel-default <?= in_array(3, $panelActive) ? 'active-step' : '' ?>"
                style="min-height: 180px;">
                <div class="panel-heading">
                  <i class="fa fa-check-circle"></i> Kết thúc và nộp báo cáo
                </div>
                <div class="panel-body">
                  <p><i class="fa fa-file-pdf-o text-danger"></i> Phiếu chấm điểm...</p>
                  <p><i class="fa fa-comment text-info"></i> Nhận xét thực tập...</p>
                  <p><i class="fa fa-book text-success"></i> Quyển báo cáo...</p>
                  <?php if (!$cho_phep_nop): ?>
                    <p><i class="fa fa-lock text-muted"></i> <small>Chưa mở</small></p>
                  <?php endif; ?>
                </div>
              </div>
            </a>
          </div>
          <div class="modal fade" id="detailModal" tabindex="-1" role="dialog">
            <div class="modal-dialog">
              <div class="modal-content">
                <div class="modal-header">
                  <h4 class="modal-title">Chấm điểm kết thúc</h4>
                </div>
                <div class="modal-body">
                  <ul>
                    <li>Phiếu chấm điểm thực tập tốt nghiệp (có điểm và chữ ký của Cán bộ hướng dẫn của công ty, kèm
                      mộc)</li>
                    <li>Phiếu khảo sát thực tập</li>
                    <li>Nhận xét thực tập (đính kèm trong báo cáo, kèm mộc)</li>
                    <li>Quyển báo cáo theo quy định</li>
                  </ul>
                </div>
                <div class="modal-footer">
                  <button class="btn btn-default" data-dismiss="modal">Đóng</button>
                  <a <?= ($trangThaiDot != 5) ? 'disabled' : '' ?> href="pages/sinhvien/nopketqua"
                    class="btn btn-primary">Đến nộp</a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="row">
        <h1>Thông Báo</h1>
      </div>
      <div class="container mt-4">
        <div id="notification-list">
        </div>
        <div class="text-center" style="margin-top: 20px;">
          <button id="prevBtn" class="btn btn-default">&laquo; Trước</button>
          <button id="nextBtn" class="btn btn-default">Sau &raquo;</button>
        </div>
      </div>
    </div>
  </div>
  </div>

  <?php
  require __DIR__ . "/../../template/footer.php";
  ?>
  <script>
    const thongbaos = <?= json_encode($thongbaos) ?>;
    const pageSize = 5;
    let currentPage = 0;

    function renderNotifications() {
      const container = document.getElementById('notification-list');

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
                <i class="fa fa-bell-slash fa-3x text-muted" style="margin-bottom: 15px;"></i>
                <h4 class="text-muted">Không có thông báo</h4>
                <p class="text-muted">Hiện tại chưa có thông báo nào được đăng tải.</p>
              </div>
            </div>
          `;
          container.innerHTML = emptyHtml;
        } else if (list.length === 0) {
          // Hiển thị khi hết thông báo trong trang hiện tại
          const noMoreHtml = `
            <div class="empty-notification">
              <div class="text-center">
                <i class="fa fa-info-circle fa-2x text-info" style="margin-bottom: 15px;"></i>
                <h5 class="text-muted">Đã hết thông báo</h5>
                <p class="text-muted">Bạn đã xem hết tất cả thông báo.</p>
              </div>
            </div>
          `;
          container.innerHTML = noMoreHtml;
        } else {
          list.forEach(tb => {
            const html = `
                  <div class="notification-card">
                    <div class="row">
                      <div class="col-sm-2 text-center">
                        <a href="pages/sinhvien/chitietthongbao?id=${tb.ID}">
                          <img src="/datn/uploads/Images/ThongBao.jpg" alt="${tb.TieuDe}" class="notification-image">
                        </a>
                      </div>
                      <div class="col-sm-10">
                        <a href="#" class="thongbao-link notification-title" data-id="${tb.ID}">
                          ${tb.TieuDe}
                        </a>
                        <ul class="list-inline notification-meta">
                          <li><i class="fa fa-bullhorn"></i> Thông báo</li>
                          <li>|</li>
                          <li><i class="fa fa-calendar"></i> ${new Date(tb.NgayDang).toLocaleDateString('vi-VN')}</li>
                          ${tb.TenDot ? `<li>|</li><li><i class="fa fa-tag"></i> ${tb.TenDot}</li>` : ''}
                        </ul>
                      </div>
                    </div>
                  </div>
                `;
            container.insertAdjacentHTML('beforeend', html);
          });
        }

        document.getElementById('prevBtn').disabled = currentPage === 0;
        document.getElementById('nextBtn').disabled = end >= thongbaos.length;

        // Ẩn/hiện nút phân trang
        const paginationButtons = document.querySelector('.text-center');
        if (thongbaos.length === 0) {
          paginationButtons.style.display = 'none';
        } else {
          paginationButtons.style.display = 'block';
        }

        container.classList.remove('fade-out');
        container.classList.add('fade-in');

        setTimeout(() => container.classList.remove('fade-in'), 500);
      }, 300);
    }
    $(function () {
      $('[data-toggle="tooltip"]').tooltip();
    });

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
    document.addEventListener('click', function (e) {
      if (e.target.classList.contains('thongbao-link')) {
        e.preventDefault();
        const id = e.target.getAttribute('data-id');
        fetch('pages/sinhvien/ajax_danhdau_thongbao.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: 'idThongBao=' + encodeURIComponent(id)
        }).then(() => {
          window.location.href = 'pages/sinhvien/chitietthongbao?id=' + id;
        });
      }
    });

    // Thêm class has-active cho các panel container có panel active
    document.addEventListener('DOMContentLoaded', function () {
      const activePanels = document.querySelectorAll('.panel.active-step');
      activePanels.forEach(panel => {
        const container = panel.closest('.panel-container');
        if (container) {
          container.classList.add('has-active');
        }
      });
    });

    history.pushState(null, "", location.href);
    window.onpopstate = function () {
      history.pushState(null, "", location.href);
    };
  </script>
</body>

</html>
<style>
  /* === STATUS INDICATOR === */
  .status-indicator {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 15px 25px;
    border-radius: 25px;
    margin-bottom: 25px;
    text-align: center;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    font-size: 16px;
    font-weight: 500;
    animation: fadeInDown 0.8s ease-out;
  }

  .status-indicator.status-finding {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    animation: pulse-status 3s infinite;
  }

  .status-indicator.status-internship {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
  }

  .status-indicator.status-completion {
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
  }

  .status-indicator.status-preparing {
    background: linear-gradient(135deg, #fdbb2d 0%, #22c1c3 100%);
  }

  .status-indicator.status-ended {
    background: linear-gradient(135deg, #bdc3c7 0%, #2c3e50 100%);
  }

  .status-indicator i {
    margin-right: 10px;
    font-size: 18px;
  }

  @keyframes pulse-status {

    0%,
    100% {
      transform: scale(1);
      box-shadow: 0 4px 15px rgba(240, 147, 251, 0.3);
    }

    50% {
      transform: scale(1.02);
      box-shadow: 0 6px 25px rgba(240, 147, 251, 0.5);
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
  .panel-container:first-child.has-active+.panel-container.has-active::before {
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

    0%,
    100% {
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
  .panel-container:first-child .panel.active-step+.panel-container:nth-child(2) .panel.active-step {
    animation: sync-pulse 2s infinite;
  }

  .panel-container:first-child .panel.active-step {
    animation: sync-pulse 2s infinite;
  }

  @keyframes sync-pulse {

    0%,
    100% {
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

  /* === NOTIFICATION STYLES === */
  .ls-list {
    margin-bottom: 20px;
  }

  .noidung-rutgon {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    display: block;
  }

  #notification-list {
    transition: opacity 0.5s ease;
    opacity: 1;
  }

  #notification-list.fade-out {
    opacity: 0;
  }

  #notification-list.fade-in {
    opacity: 1;
  }

  /* === RESPONSIVE === */
  .container,
  .container-fluid,
  #wrapper,
  #page-wrapper {
    max-width: 100%;
    overflow-x: hidden;
  }

  .row {
    margin-left: 0;
    margin-right: 0;
  }

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

  /* === NOTIFICATION CARD STYLES === */
  .notification-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    margin-bottom: 15px;
    padding: 15px;
    border-left: 4px solid #007bff;
    transition: all 0.3s ease;
  }

  .notification-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
  }

  .notification-title {
    font-weight: 600;
    color: #2c3e50;
    text-decoration: none;
    font-size: 16px;
    margin-bottom: 5px;
    display: block;
  }

  .notification-title:hover {
    color: #007bff;
    text-decoration: none;
  }

  .notification-meta {
    color: #6c757d;
    font-size: 13px;
    margin: 0;
  }

  .notification-meta li {
    display: inline-block;
    margin-right: 10px;
  }

  .notification-image {
    width: 80px;
    height: 60px;
    object-fit: cover;
    border-radius: 8px;
    border: 2px solid #e9ecef;
  }

  /* === EMPTY NOTIFICATION STYLES === */
  .empty-notification {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    padding: 40px 20px;
    text-align: center;
    margin: 20px 0;
    border: 2px dashed #e0e0e0;
  }

  .empty-notification i {
    opacity: 0.5;
  }

  .empty-notification h4,
  .empty-notification h5 {
    margin: 15px 0 10px 0;
  }

  .empty-notification p {
    margin-bottom: 0;
    font-size: 14px;
  }
</style>