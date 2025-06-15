<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/includes/ThongBao_funtions.php";

if (!isset($_GET['id'])) {
  die("Không tìm thấy ID thông báo.");
}

$id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT ID, TIEUDE, NOIDUNG ,ID_TAIKHOAN,NGAYDANG,TRANGTHAI FROM THONGBAO WHERE ID = ?");
$stmt->execute([$id]);
$thongbao = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$thongbao) {
  die("Không tìm thấy thông báo.");
}
<<<<<<< HEAD
$stmt_khac = $conn->prepare("SELECT ID, TIEUDE, NOIDUNG ,ID_TAIKHOAN,NGAYDANG,TRANGTHAI FROM THONGBAO WHERE ID != ? and TRANGTHAI=1 ORDER BY NGAYDANG DESC LIMIT 4");
=======
$stmt_khac = $conn->prepare("SELECT ID, TIEUDE, NOIDUNG ,ID_TAIKHOAN,NGAYDANG,TRANGTHAI FROM THONGBAO WHERE ID != ? ORDER BY NGAYDANG DESC LIMIT 4");
>>>>>>> 4fd8ce05db2488642b901eba16148a94e291076e
$stmt_khac->execute([$id]);
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
<<<<<<< HEAD
  <?php
  require $_SERVER['DOCUMENT_ROOT'] . "/datn/template/footer.php"
    ?>
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
=======
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
>>>>>>> 4fd8ce05db2488642b901eba16148a94e291076e
                <div class="row" style="margin-bottom: 15px; border: 1px solid #ddd; padding: 10px; border-radius: 4px;">
                    <div class="col-md-2 text-center">
                        <a href="pages/sinhvien/chitietthongbao.php?id=${tb.ID}">
                            <img src="/datn/uploads/Images/ThongBao.jpg" alt="${tb.TIEUDE}" style="width: 100px; height: 70px; object-fit: cover;">
                        </a>
                    </div>
                    <div class="col-lg-10">
                        <p style="margin-bottom: 5px;">
                            <a href="pages/sinhvien/chitietthongbao.php?id=${tb.ID}" style="font-weight: bold; text-decoration: none;">
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
<<<<<<< HEAD
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
</body>

</html>
=======
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
>>>>>>> 4fd8ce05db2488642b901eba16148a94e291076e
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