<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../error.log');
require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";
$idTaiKhoan = $_SESSION['user']['ID_TaiKhoan'] ?? null;
$thongbao_moi = [];

if ($idTaiKhoan) {
    // Lấy các ID đợt giáo viên đang phụ trách
    $stmt = $conn->prepare("SELECT ID_Dot FROM dot_giaovien WHERE ID_GVHD = ?");
    $stmt->execute([$idTaiKhoan]);
    $dsDot = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($dsDot)) {
        $placeholders = implode(',', array_fill(0, count($dsDot), '?'));
        $params = array_merge($dsDot, [$idTaiKhoan]);

        $stmt = $conn->prepare("
            SELECT tb.ID, tb.TIEUDE, tb.NOIDUNG, tb.ID_TAIKHOAN, tb.NGAYDANG, tb.TRANGTHAI, tb.ID_Dot, dt.TenDot
            FROM thongbao tb
            LEFT JOIN dotthuctap dt ON tb.ID_Dot = dt.ID
            WHERE tb.TRANGTHAI = 1
                AND tb.ID_Dot IN ($placeholders)
                AND NOT EXISTS (
                    SELECT 1 FROM thongbao_xem xem 
                    WHERE xem.ID_TaiKhoan = ? AND xem.ID_thongbao = tb.ID
                )
            ORDER BY tb.NGAYDANG DESC
        ");
        $stmt->execute($params);
        $thongbao_moi = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Thông báo mới</title>
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php"; ?>
    <style>
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

    </style>
</head>

<body>
    <div id="wrapper">
        <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/slidebar_Giaovien.php"; ?>
        <div id="page-wrapper">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12">
                        <h1 class="page-header">Thông báo mới</h1>
                    </div>
                </div>
                <div class="row Notification">
                    <div class="container mt-4">
                        <div id="notification-list"></div>
                        <div class="text-center" style="margin-top: 20px;">
                            <button id="prevBtn" class="btn btn-default">&laquo; Trước</button>
                            <button id="nextBtn" class="btn btn-default">Sau &raquo;</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
<script>
    const thongbao_moi = <?= json_encode($thongbao_moi) ?>;
const pageSize = 5;
let currentPage = 0;

function renderNotifications() {
    const container = document.getElementById('notification-list');
    container.classList.add('fade-out');
    setTimeout(() => {
        const start = currentPage * pageSize;
        const end = start + pageSize;
        const list = thongbao_moi.slice(start, end);
        container.innerHTML = '';

        if (list.length === 0) {
            container.innerHTML = '<p class="text-center">Không có thông báo mới.</p>';
        }

        list.forEach(tb => {
            const html = `
                <div class="notification-card row"> 
                    <div class="notification-image-container col-sm-2 col-xs-3">
                        <a href="#" class="thongbao-link" data-id="${tb.ID}">
                        <img src="/datn/uploads/Images/ThongBao.jpg" alt="${tb.TIEUDE}" class="notification-image">
                        </a>
                    </div>
                    <div class="notification-content col-sm-10 col-xs-9">
                        <div>
                        <a href="#" class="thongbao-link notification-title" data-id="${tb.ID}">
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
                    `;
            container.insertAdjacentHTML('beforeend', html);
        });

        document.getElementById('prevBtn').disabled = currentPage === 0;
        document.getElementById('nextBtn').disabled = end >= thongbao_moi.length;
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
        if ((currentPage + 1) * pageSize < thongbao_khac.length) {
            currentPage++;
            renderNotifications();
        }
    });

    renderNotifications();

    document.addEventListener('click', function (e) {
  // Tìm phần tử cha có class .thongbao-link nếu click vào ảnh bên trong
  let link = e.target.closest('.thongbao-link');
  if (link) {
    e.preventDefault();
    const id = link.getAttribute('data-id');
    fetch('pages/giaovien/ajax_danhdau_thongbao.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'idthongbao=' + encodeURIComponent(id)
    }).then(() => {
      window.location.href = 'pages/giaovien/chitietthongbao.php?id=' + id;
    });
  }
});
</script>
<style>
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
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  }

  .notification-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
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
}

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
</style>