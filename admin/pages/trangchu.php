<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';

require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";
require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/includes/ThongBao_funtions.php";
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
    require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/admin/template/slidebar.php";
    ?>
    <div id="page-wrapper">
      <div class="container-fluid">
        <div class="row">
          <div class="col-lg-12">
            <h1 class="page-header">Quy Trình Thực Tập Tốt Nghiệp</h1>
          </div>
        </div>
        <div class="row panel-row">
          <div class="col-md-3 panel-container">
            <a href="admin/pages/danhsachcongty" style="text-decoration: none; color: inherit;">
              <div class="panel panel-default" style="min-height: 170px;">
                <div class="panel-heading">Tìm công ty thực tập</div>
                <div class="panel-body">
                  <p>&bull; Xem danh sách công ty từ các khóa trước</p>
                  <p>&bull; Tìm trên các trang web</p>
                </div>
              </div>
            </a>
          </div>
          <div class="col-md-3 panel-container">
            <a style="text-decoration: none; color: inherit;">
              <div class="panel panel-default" style="min-height: 170px;">
                <div class="panel-heading">Xin giấy giới thiệu thực tập</div>
                <div class="panel-body">
                  <p>&bull; Gửi thông tin đăng ký xin giấy giới thiệu thực tập</p>
                </div>
              </div>
            </a>
          </div>
          <div class="col-md-3 panel-container">
            <a style="text-decoration: none; color: inherit;">
              <div class="panel panel-default" style="min-height: 170px;">
                <div class="panel-heading">Thực tập, báo cáo tuần</div>
                <div class="panel-body">
                  <p>&bull; Bắt dầu thực tập, gửi báo cáo hằng tuần cho giáo viên hướng dẫn</p>
                </div>
              </div>
            </a>
          </div>
          <div class="col-md-3 ">
            <a href=""style="text-decoration: none; color: inherit;" data-toggle="modal" data-target="#detailModal" style="text-decoration: none; color: inherit;">
              <div class="panel panel-default" style="min-height: 170px;">
                <div class="panel-heading">Chấm điểm kết thúc</div>
                <div class="panel-body">
                  <p>&bull; Phiếu chấm điểm...</p>
                  <p>&bull; Nhận xét thực tập...</p>
                  <p>&bull; Quyển báo cáo...</p>
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
  require $_SERVER['DOCUMENT_ROOT'] . "/datn/template/footer.php"
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

        list.forEach(tb => {
          const html = `
                <div class="row" style="margin-bottom: 15px; border: 1px solid #ddd; padding: 10px; border-radius: 4px;">
                    <div class="col-md-2 text-center">
                        <a href="admin/pages/chitietthongbao?id=${tb.ID}">
                            <img src="/datn/uploads/Images/ThongBao.jpg" alt="${tb.TIEUDE}" style="width: 100px; height: 70px; object-fit: cover;">
                        </a>
                    </div>
                    <div class="col-md-10">
                        <p style="margin-bottom: 5px;">
                            <a href="admin/pages/chitietthongbao?id=${tb.ID}" style="font-weight: bold; text-decoration: none;">
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
        document.getElementById('nextBtn').disabled = end >= thongbaos.length;

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
  .panel-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: relative;
    margin-bottom: 30px;
  }

  .panel-container {
    position: relative;
  }

  .panel-container:not(:last-child)::after {
    content: "";
    position: absolute;
    top: 50%;
    right: -15px;
    width: 30px;
    height: 2px;
    background-color: rgb(0, 0, 0);
    z-index: 1;
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