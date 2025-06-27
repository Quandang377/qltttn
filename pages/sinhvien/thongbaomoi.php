<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";
$idTaiKhoan = $_SESSION['user']['ID_TaiKhoan'] ?? null;

$stmt = $conn->prepare("SELECT ID_Dot FROM SinhVien WHERE ID_TaiKhoan = ?");
$stmt->execute([$idTaiKhoan]);
$idDot = $stmt->fetchColumn();


$stmt = $conn->prepare("
    SELECT tb.ID, tb.TIEUDE, tb.NOIDUNG, tb.ID_TAIKHOAN, tb.NGAYDANG, tb.TRANGTHAI, tb.ID_Dot, dt.TenDot
    FROM THONGBAO tb
    LEFT JOIN DotThucTap dt ON tb.ID_Dot = dt.ID
    WHERE tb.TRANGTHAI = 1 
        AND tb.ID_Dot = ? 
        AND NOT EXISTS (
            SELECT 1 FROM ThongBao_Xem xem 
            WHERE xem.ID_TaiKhoan = ? AND xem.ID_ThongBao = tb.ID
        )
    ORDER BY tb.NGAYDANG DESC
");
$stmt->execute([$idDot, $idTaiKhoan]);
$thongbao_moi = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Thông báo mới</title>
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php"; ?>
</head>

<body>
    <div id="wrapper">
        <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/slidebar_SinhVien.php"; ?>
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
        const list = thongbao_moi.slice(start, end); // ✅ Sửa biến này
        container.innerHTML = '';

        if (list.length === 0) {
            container.innerHTML = '<p class="text-center">Không có thông báo mới.</p>';
        }

        list.forEach(tb => {
            const html = `
                    <div class="row" style="margin-bottom: 15px; border: 1px solid #ddd; padding: 10px; border-radius: 4px;">
                        <div class="col-md-2 text-center">
                            <a href="#" class="thongbao-link" data-id="${tb.ID}">
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