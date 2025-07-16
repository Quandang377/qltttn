<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
 require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';

require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";

if (!isset($_GET['id'])) {
  die("Không tìm thấy ID thông báo.");
}

$id = intval($_GET['id']);

// Lấy thông báo kèm tên đợt
$stmt = $conn->prepare("SELECT tb.ID, tb.TIEUDE, tb.NOIDUNG, tb.ID_TAIKHOAN, tb.NGAYDANG, tb.TRANGTHAI, tb.ID_Dot, dt.TenDot
    FROM thongbao tb
    LEFT JOIN dotthuctap dt ON tb.ID_Dot = dt.ID
    WHERE tb.ID = ?");
$stmt->execute([$id]);
$thongbao = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$thongbao) {
  die("Không tìm thấy thông báo.");
}
function renderMediaEmbed($content)
{
  return preg_replace_callback('/<oembed url="([^"]+)"><\/oembed>/', function ($matches) {
    $url = $matches[1];

    // Chuyển youtu.be hoặc youtube.com thành dạng embed
    if (strpos($url, 'youtu') !== false) {
      $videoId = null;
      if (preg_match('/youtu\.be\/([^\?&]+)/', $url, $m)) {
        $videoId = $m[1];
      } elseif (preg_match('/v=([^\?&]+)/', $url, $m)) {
        $videoId = $m[1];
      }

      if ($videoId) {
        return '<iframe width="560" height="315" src="https://www.youtube.com/embed/' . $videoId . '" frameborder="0" allowfullscreen></iframe>';
      }
    }

    // Nếu không phải YouTube, trả nguyên hoặc ẩn
    return '';
  }, $content);
}
// Lấy các thông báo khác kèm tên đợt
$stmt_khac = $conn->prepare("SELECT tb.ID, tb.TIEUDE, tb.NOIDUNG, tb.ID_TAIKHOAN, tb.NGAYDANG, tb.TRANGTHAI, tb.ID_Dot, dt.TenDot
    FROM thongbao tb
    LEFT JOIN dotthuctap dt ON tb.ID_Dot = dt.ID
    WHERE tb.ID != ? and tb.TRANGTHAI=1
    ORDER BY tb.NGAYDANG DESC LIMIT 4");
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
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/admin/template/slidebar.php"; ?>

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
          <?= renderMediaEmbed($thongbao['NOIDUNG']) ?>

        </div>
        <script>
          document.querySelectorAll('oembed[url]').forEach(el => {
            const url = el.getAttribute('url');
            if (url.includes('youtu')) {
              const match = url.match(/(?:youtu\.be\/|v=)([^&]+)/);
              if (match) {
                const iframe = document.createElement('iframe');
                iframe.setAttribute('width', '560');
                iframe.setAttribute('height', '315');
                iframe.setAttribute('src', 'https://www.youtube.com/embed/' + match[1]);
                iframe.setAttribute('frameborder', '0');
                iframe.setAttribute('allowfullscreen', '');
                el.replaceWith(iframe);
              }
            }
          });
        </script>

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
                <div class="row" style="margin-bottom: 15px; border: 1px solid #ddd; padding: 10px; border-radius: 4px;">
                    <div class="col-md-2 text-center">
                        <a href="admin/pages/chitietthongbao?id=${tb.ID}">
                            <img src="/datn/uploads/Images/ThongBao.jpg" alt="${tb.TIEUDE}" style="width: 100px; height: 70px; object-fit: cover;">
                        </a>
                    </div>
                    <div class="col-lg-10">
                        <p style="margin-bottom: 5px;">
                            <a href="admin/pages/chitietthongbao?id=${tb.ID}" style="font-weight: bold; text-decoration: none;">
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
</body>

</html>
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