<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";

if (!isset($_GET['id'])) {
  die("Không tìm thấy ID thông báo.");
}

$id = intval($_GET['id']);
$idTaiKhoan = $_SESSION['user_id'] ?? null;

// Lấy danh sách đợt mà cán bộ này quản lý
$stmt = $conn->prepare("SELECT ID FROM DotThucTap WHERE NguoiQuanLy = ?");
$stmt->execute([$idTaiKhoan]);
$dsDot = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Nếu không có đợt nào thì không truy vấn
if (empty($dsDot)) {
  die("Bạn chưa quản lý đợt thực tập nào.");
}

$placeholders = implode(',', array_fill(0, count($dsDot), '?'));

// Lấy chi tiết thông báo
$stmt = $conn->prepare("
    SELECT tb.ID, tb.TIEUDE, tb.NOIDUNG, tb.ID_TAIKHOAN, tb.NGAYDANG, tb.TRANGTHAI, tb.ID_Dot, dt.TenDot
    FROM THONGBAO tb
    LEFT JOIN DotThucTap dt ON tb.ID_Dot = dt.ID
    WHERE tb.ID = ? AND tb.ID_Dot IN ($placeholders)
");
$stmt->execute(array_merge([$id], $dsDot));
$thongbao = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$thongbao) {
  die("Không tìm thấy thông báo.");
}

// Lấy các thông báo khác cùng đợt quản lý
$stmt_khac = $conn->prepare("
    SELECT tb.ID, tb.TIEUDE, tb.NOIDUNG, tb.ID_TAIKHOAN, tb.NGAYDANG, tb.TRANGTHAI, tb.ID_Dot, dt.TenDot
    FROM THONGBAO tb
    LEFT JOIN DotThucTap dt ON tb.ID_Dot = dt.ID
    WHERE tb.ID != ? AND tb.ID_Dot IN ($placeholders) AND tb.TRANGTHAI = 1
    ORDER BY tb.NGAYDANG DESC
    LIMIT 50
");
$stmt_khac->execute(array_merge([$id], $dsDot));
$thongbao_khac = $stmt_khac->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>[THÔNG BÁO] <?= htmlspecialchars($thongbao['TIEUDE']) ?></title>
  <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php"; ?>
</head>
<body>
<div id="wrapper">
  <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/slidebar_CanBo.php"; ?>

  <div id="page-wrapper">
    <div class="container-fluid">
      <div class="row">
        <div class="col-lg-12">
          <h1 class="page-header">[THÔNG BÁO] <?= htmlspecialchars($thongbao['TIEUDE']) ?></h1>
          <?php if (!empty($thongbao['TenDot'])): ?>
            <p><strong>Đợt thực tập:</strong> <?= htmlspecialchars($thongbao['TenDot']) ?></p>
          <?php endif; ?>
        </div>
      </div>
      <div class="news-content mb-4">
        <?= $thongbao['NOIDUNG'] ?>
      </div>

      <div class="row mt-5">
        <div class="col-lg-12">
          <h2 class="page-header">Thông báo khác</h2>
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
<?php require $_SERVER['DOCUMENT_ROOT'] . "/datn/template/footer.php"; ?>
</body>
</html>

<script>
  const thongbao_khac = <?= json_encode($thongbao_khac) ?>;
  const pageSize = 5;
  let currentPage = 0;

  function renderNotifications() {
    const container = document.getElementById('notification-list');
    container.classList.add('fade-out');

    setTimeout(() => {
      const start = currentPage * pageSize;
      const end = start + pageSize;
      const list = thongbao_khac.slice(start, end);

      container.innerHTML = '';
      list.forEach(tb => {
        const html = `
          <div class="row" style="margin-bottom: 15px; border: 1px solid #ddd; padding: 10px; border-radius: 4px;">
            <div class="col-md-2 text-center">
              <a href="pages/canbo/chitietthongbao.php?id=${tb.ID}">
                <img src="/datn/uploads/Images/ThongBao.jpg" alt="${tb.TIEUDE}" style="width: 100px; height: 70px; object-fit: cover;">
              </a>
            </div>
            <div class="col-lg-10">
              <p style="margin-bottom: 5px;">
                <a href="pages/canbo/chitietthongbao.php?id=${tb.ID}" style="font-weight: bold; text-decoration: none;">
                  ${tb.TIEUDE}
                </a>
              </p>
              <ul class="list-inline" style="color: #888; font-size: 13px; margin: 0;">
                <li>Thông báo</li>
                <li>|</li>
                <li>${new Date(tb.NGAYDANG).toLocaleDateString('vi-VN')}</li>
                ${tb.TenDot ? `<li>|</li><li>Đợt: ${tb.TenDot}</li>` : ''}
              </ul>
            </div>
          </div>
        `;
        container.insertAdjacentHTML('beforeend', html);
      });

      document.getElementById('prevBtn').disabled = currentPage === 0;
      document.getElementById('nextBtn').disabled = end >= thongbao_khac.length;

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
</script>

<style>
  .news-content p,
  .news-content ul,
  .news-content ol {
    font-size: 16px;
    line-height: 1.6;
    margin-bottom: 1em;
  }

  .news-content ul {
    padding-left: 20px;
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
