<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';

require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";

if (!isset($_GET['id'])) {
  die("Không tìm thấy ID thông báo.");
}

$id = intval($_GET['id']);

$idTaiKhoan = $_SESSION['user']['ID_TaiKhoan'];
$stmt = $conn->prepare("SELECT ID_Dot FROM SinhVien WHERE ID_TaiKhoan = ?");
$stmt->execute([$idTaiKhoan]);
$idDot = $stmt->fetchColumn();

$idThongBao = $_POST['idThongBao'] ?? null;
// ✅ Đánh dấu đã xem ngay khi truy cập chi tiết
if ($idTaiKhoan && $id) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM ThongBao_Xem WHERE ID_TaiKhoan = ? AND ID_ThongBao = ?");
    $stmt->execute([$idTaiKhoan, $id]);
    if ($stmt->fetchColumn() == 0) {
        $stmt = $conn->prepare("INSERT INTO ThongBao_Xem (ID_TaiKhoan, ID_ThongBao) VALUES (?, ?)");
        $stmt->execute([$idTaiKhoan, $id]);
    }
}
// Lấy thông báo chi tiết (chỉ thuộc đợt của SV)
$stmt = $conn->prepare("SELECT tb.ID, tb.TIEUDE, tb.NOIDUNG, tb.ID_TAIKHOAN, tb.NGAYDANG, tb.TRANGTHAI, tb.ID_Dot, dt.TenDot
    FROM THONGBAO tb
    LEFT JOIN DotThucTap dt ON tb.ID_Dot = dt.ID
    WHERE tb.ID = ? AND tb.ID_Dot = ?");
$stmt->execute([$id, $idDot]);
$thongbao = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$thongbao) {
  die("Không tìm thấy thông báo.");
}

// Lấy các thông báo khác cùng đợt
$stmt_khac = $conn->prepare("SELECT tb.ID, tb.TIEUDE, tb.NOIDUNG, tb.ID_TAIKHOAN, tb.NGAYDANG, tb.TRANGTHAI, tb.ID_Dot, dt.TenDot
    FROM THONGBAO tb
    LEFT JOIN DotThucTap dt ON tb.ID_Dot = dt.ID
    WHERE tb.ID != ? AND tb.ID_Dot = ? AND tb.TRANGTHAI = 1
    ORDER BY tb.NGAYDANG DESC LIMIT 20");
$stmt_khac->execute([$id, $idDot]);
$thongbao_khac = $stmt_khac->fetchAll(PDO::FETCH_ASSOC);

// Lấy danh sách ID thông báo đã xem
$stmt = $conn->prepare("SELECT ID_ThongBao FROM ThongBao_Xem WHERE ID_TaiKhoan = ?");
$stmt->execute([$idTaiKhoan]);
$daXem = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'ID_ThongBao');
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
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/slidebar_SinhVien.php"; ?>

    <div id="page-wrapper">
      <div class="container-fluid">
        <div class="row">
          <div class="col-lg-12">
            <h1 class="page-header">[THÔNG BÁO] <?= htmlspecialchars($thongbao['TIEUDE']) ?></h1>
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
  </div>
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
        <a href="pages/sinhvien/chitietthongbao.php?id=${tb.ID}">
          <img src="/datn/uploads/Images/ThongBao.jpg" alt="${tb.TIEUDE}" style="width: 100px; height: 70px; object-fit: cover;">
        </a>
      </div>
      <div class="col-lg-10">
        <p style="margin-bottom: 5px;">
          <a href="#" class="thongbao-link" data-id="${tb.ID}" style="font-weight: bold; text-decoration: none;">
            ${tb.TIEUDE}
          </a>
        </p>
        <ul class="list-inline" style="color: #888; font-size: 13px; margin: 0;">
          <li>Thông báo</li>
          <li>|</li>
          <li>${new Date(tb.NGAYDANG).toLocaleDateString('vi-VN')}</li>
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
  document.addEventListener('click', function (e) {
  // Tìm phần tử cha có class .thongbao-link nếu click vào ảnh bên trong
  let link = e.target.closest('.thongbao-link');
  if (link) {
    e.preventDefault();
    const id = link.getAttribute('data-id');
    fetch('pages/sinhvien/ajax_danhdau_thongbao.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'idThongBao=' + encodeURIComponent(id)
    }).then(() => {
      window.location.href = 'pages/sinhvien/chitietthongbao.php?id=' + id;
    });
  }
});
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

  .panel-body p {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    margin-bottom: 0;
  }

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