<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng ký giấy giới thiệu</title>
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php"; ?>
    <style>
        body {
            background: linear-gradient(135deg, #e3f0ff 0%, #f8fafc 100%);
            font-family: 'Segoe UI', 'Roboto', Arial, sans-serif;
        }
        #page-wrapper {
            padding: 30px;
            min-height: 100vh;
        }
        .page-header {
            font-size: 2.2rem;
            font-weight: 700;
            color:rgb(0, 58, 217);
            letter-spacing: 1px;
            margin-bottom: 32px;
            text-align: center;
            text-shadow: 0 2px 8px #b6d4fe44;
        }
        .search-bar {
            margin-bottom: 28px;
            display: flex;
            gap: 14px;
            align-items: center;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 2px 12px #007bff11;
            padding: 14px 18px;
        }
        .search-bar input {
            flex: 1;
            border-radius: 10px;
            border: 1.5px solid #b6d4fe;
            padding: 10px 18px;
            font-size: 17px;
            background: #fafdff;
            transition: border 0.2s;
        }
        .search-bar input:focus {
            border: 1.5px solid #007bff;
            outline: none;
            background: #f0f8ff;
        }
        .search-bar button {
            border-radius: 10px;
            padding: 10px 22px;
            font-size: 17px;
            font-weight: 600;
            background: linear-gradient(90deg, #007bff 70%, #5bc0f7 100%);
            color: #fff;
            border: none;
            box-shadow: 0 2px 8px #007bff22;
            transition: background 0.2s, box-shadow 0.2s;
        }
        .search-bar button:hover {
            background: linear-gradient(90deg, #0056b3 70%, #3bb3e0 100%);
            box-shadow: 0 4px 16px #007bff33;
        }
        .card {
            border-radius: 22px !important;
            border: 2px solid #e3eafc !important;    /* màu viền nhạt hơn */
            box-shadow: 0 4px 18px rgba(0,123,255,0.06); /* bóng nhẹ hơn */
            margin-bottom: 36px;
            background: #fcfdff;                     /* nền nhạt hơn */
        }
        .card-header {
            border-radius: 22px 22px 0 0 !important;
            font-size: 22px;
            font-weight: bold;
            letter-spacing: 1px;
            padding: 18px 28px !important;
            background: linear-gradient(90deg, #e3f0ff 70%, #f8fafc 100%); /* gradient nhạt */
            color: #007bff;
            box-shadow: 0 1px 4px #007bff11;
        }
        .card-body {
            background: #fafdff;
            border-radius: 0 0 22px 22px;
            min-height: 300px;
            padding: 36px 24px 18px 24px !important;
        }
        .company-panel {
            border: 2px solid #e3e6f0;
            border-radius: 16px;
            background: #fff;
            margin-bottom: 36px;
            padding: 28px 18px 22px 18px;
            box-shadow: 0 2px 16px rgba(0,123,255,0.07);
            min-height: 140px;
            transition: box-shadow 0.2s, border-color 0.2s, background 0.2s;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        .company-panel:hover {
            border-color: #007bff;
            box-shadow: 0 4px 24px rgba(0,123,255,0.16);
            background: #f0f8ff;
            transform: translateY(-2px) scale(1.01);
        }
        .company-panel .icon {
            position: absolute;
            top: 18px;
            right: 18px;
            font-size: 28px;
            color: #007bff33;
        }
        .company-panel .font-weight-bold {
            font-size: 18px;
            color: #007bff;
            margin-bottom: 8px;
        }
        .company-panel div {
            font-size: 15px;
        }
        .company-list-pagination {
            display: flex;
            justify-content: center;
            margin: 18px 0 0 0;
            gap: 10px;
            padding: 10px 18px;
            border: none;
            border-radius: 16px;
            background: #fff;
            box-shadow: 0 4px 24px rgba(0,0,0,0.10);
        }
        .company-list-pagination button {
            border: 1.5px solid #e3eafc;
            background: #fff;
            color: #007bff;
            border-radius: 8px;
            width: 38px;
            height: 38px;
            font-weight: 700;
            font-size: 17px;
            cursor: pointer;
            transition: background 0.2s, color 0.2s, transform 0.1s, box-shadow 0.2s, border 0.2s;
            box-shadow: 0 2px 8px #007bff11;
            outline: none;
        }
        .company-list-pagination button.active,
        .company-list-pagination button:hover {
            background: #f0f8ff;
            color: #007bff;
            border: 2px solid #b6d4fe;
            transform: translateY(-2px) scale(1.08);
            box-shadow: 0 4px 16px #007bff22;
        }
        .company-list-pagination span {
            padding: 0 8px;
            color: #007bff;
            font-weight: bold;
            font-size: 18px;
            user-select: none;
        }
        /* Modal thông báo tuyệt đẹp */
        #notifyModal .modal-content {
            border-radius: 22px;
            box-shadow: 0 8px 40px rgba(0,0,0,0.18);
            border: none;
            background: #fff;
        }
        #notifyModal .modal-body {
            padding-top: 36px !important;
            padding-bottom: 10px !important;
        }
        #notifyIcon {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 18px;
        }
        #notifyModalLabel {
            font-weight: 800;
            font-size: 1.35rem;
            margin-bottom: 10px;
            letter-spacing: 1px;
        }
        #notifyModalBody {
            font-size: 1.08rem;
            color: #444;
            margin-bottom: 10px;
        }
        #notifyModal .modal-footer {
            border: none;
            justify-content: center;
            padding-bottom: 28px;
            padding-top: 0;
            background: none;
        }
        #notifyModal .btn-close-modal {
            border-radius: 50%;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.8rem;
            color: #888;
            background: #f8fafc;
            box-shadow: 0 2px 12px #007bff22;
            border: none;
            margin: 0 auto;
            transition: background 0.2s, color 0.2s, transform 0.1s;
        }
        #notifyModal .btn-close-modal:hover {
            background: #e3f0ff;
            color: #dc3545;
            transform: scale(1.08);
        }
        .modal-content {
            border-radius: 18px;
            box-shadow: 0 4px 32px rgba(0,0,0,0.12);
        }
        .modal-header {
            border-bottom: none;
            background: linear-gradient(90deg, #007bff 70%, #5bc0f7 100%);
            color: #fff;
            border-radius: 18px 18px 0 0;
        }
        .modal-title {
            font-weight: 700;
            font-size: 1.2rem;
        }
        .form-group label {
            font-weight: 600;
            color: #007bff;
        }
        .form-control {
            border-radius: 8px;
            border: 1.5px solid #b6d4fe;
            font-size: 16px;
            padding: 8px 14px;
            background: #fafdff;
            transition: border 0.2s;
        }
        .form-control:focus {
            border: 1.5px solid #007bff;
            background: #f0f8ff;
            outline: none;
        }
        .btn-success, .btn-info, .btn-primary {
            border-radius: 8px;
            font-weight: 600;
            box-shadow: 0 2px 8px #007bff22;
            transition: background 0.2s, box-shadow 0.2s;
        }
        .btn-success:hover, .btn-info:hover, .btn-primary:hover {
            box-shadow: 0 4px 16px #007bff33;
        }
        .giay-panel {
            border: 2px solid #e3e6f0;
            border-radius: 16px;
            background: #fff;
            margin-bottom: 24px;
            padding: 18px 14px 14px 14px;
            box-shadow: 0 2px 12px rgba(0,123,255,0.07);
            min-height: 80px;
            transition: box-shadow 0.2s, border-color 0.2s, background 0.2s;
            position: relative;
        }
        .giay-panel .tencty {
            font-size: 16px;
            font-weight: 600;
            color: #007bff;
            margin-bottom: 6px;
        }
        .giay-panel .trangthai {
            font-size: 15px;
            margin-top: 4px;
        }
        .giay-list-pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            padding: 6px 0 0 0;
            border: none;
            background: none;
        }
        .giay-list-pagination button {
            border: 1.5px solid #e3eafc;
            background: #fff;
            color: #007bff;
            border-radius: 8px;
            width: 32px;
            height: 32px;
            font-weight: 700;
            font-size: 15px;
            cursor: pointer;
            transition: background 0.2s, color 0.2s, transform 0.1s, box-shadow 0.2s, border 0.2s;
            box-shadow: 0 2px 8px #007bff11;
            outline: none;
        }
        .giay-list-pagination button.active,
        .giay-list-pagination button:hover {
            background: #f0f8ff;
            color: #007bff;
            border: 2px solid #b6d4fe;
            transform: translateY(-2px) scale(1.08);
            box-shadow: 0 4px 16px #007bff22;
        }
        .giay-list-pagination span {
            padding: 0 8px;
            color: #007bff;
            font-weight: bold;
            font-size: 16px;
            user-select: none;
        }
        @media (max-width: 991px) {
            .card-body { padding: 18px 6px 12px 6px !important; }
            .company-panel { padding: 18px 8px 14px 8px; }
            .search-bar { flex-direction: column; gap: 8px; }
        }

        #company-panel-list > .col-md-3:last-child .company-panel,
        #company-panel-list > .col-sm-6:last-child .company-panel {
            margin-bottom: 0 !important;
        }
    </style>
</head>
<body>
    <div id="wrapper">
        <?php
            require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";
            require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/slidebar_Sinhvien.php";

            $message = '';
            $messageType = 'success';

            // Lấy ID sinh viên từ session
            $idSinhVien = $_SESSION['user']['ID_TaiKhoan'] ?? 3;
            
            // Lấy thông tin đợt thực tập của sinh viên
            $stmt = $conn->prepare("SELECT ID_Dot FROM SinhVien WHERE ID_TaiKhoan = ?");
            $stmt->execute([$idSinhVien]);
            $sinhVienInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            $idDot = $sinhVienInfo['ID_Dot'] ?? null;
            
            // Lấy thông tin chi tiết đợt thực tập
            $dotThucTapInfo = null;
            if ($idDot) {
                $stmt = $conn->prepare("SELECT TenDot, ThoiGianBatDau, ThoiGianKetThuc FROM DotThucTap WHERE ID = ?");
                $stmt->execute([$idDot]);
                $dotThucTapInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            }

            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['from_panel'])) {
                $taxCode = trim($_POST['ma_so_thue']);
                $name = trim($_POST['ten_cong_ty']);
                $address = trim($_POST['dia_chi']);
                $field = trim($_POST['linh_vuc']);
                $phone = trim($_POST['sdt']);
                $email = trim($_POST['email']);

                // Kiểm tra dữ liệu phía server
                if (!$taxCode || !$name || !$address || !$field || !$phone || !$email) {
                    $message = 'Vui lòng nhập đầy đủ tất cả các trường!';
                    $messageType = 'danger';
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $message = 'Email không hợp lệ!';
                    $messageType = 'danger';
                } elseif (!preg_match('/^[0-9\-\+\s]{8,}$/', $phone)) {
                    $message = 'Số điện thoại không hợp lệ!';
                    $messageType = 'danger';
                } else {
                    try {
                        $stmt = $conn->prepare("INSERT INTO giaygioithieu (TenCty, MaSoThue, DiaChi, LinhVuc, Sdt, Email, IdSinhVien, id_dot, TrangThai) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)");
                        $stmt->execute([$name, $taxCode, $address, $field, $phone, $email, $idSinhVien, $idDot]);
                        $message = 'Đã gửi phiếu đăng ký thực tập, vui lòng chờ duyệt!';
                        $messageType = 'success';
                    } catch (Exception $e) {
                        $message = 'Có lỗi xảy ra khi lưu dữ liệu: ' . $e->getMessage();
                        $messageType = 'danger';
                    }
                }
            }

            // Lấy danh sách công ty
            $stmt = $conn->prepare("SELECT ID, TenCty, MaSoThue, DiaChi, Sdt, Email, Linhvuc FROM congty WHERE TrangThai = 1");
            $stmt->execute();
            $companyList = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Lấy danh sách giấy giới thiệu của sinh viên với thông tin đợt thực tập
            $stmt = $conn->prepare("
                SELECT g.ID, g.TenCty, g.MaSoThue, g.DiaChi, g.Sdt, g.Email, g.LinhVuc, g.TrangThai,
                       d.TenDot, d.ThoiGianBatDau, d.ThoiGianKetThuc
                FROM giaygioithieu g
                LEFT JOIN DotThucTap d ON g.id_dot = d.ID
                WHERE g.IdSinhVien = ?
                ORDER BY g.ID DESC
            ");
            $stmt->execute([$idSinhVien]);
            $giayList = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
        <div id="page-wrapper">
            <div class="container-fluid">
                <h1 class="page-header">Đăng ký giấy giới thiệu</h1>
                
                <!-- Thông tin đợt thực tập -->
                <?php if ($dotThucTapInfo): ?>
                    <div class="alert alert-info" style="border-radius: 12px; background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%); border: 1px solid #b8daff;">
                        <i class="fa fa-info-circle"></i>
                        <strong>Đợt thực tập:</strong> <?php echo htmlspecialchars($dotThucTapInfo['TenDot']); ?>
                        <?php if ($dotThucTapInfo['ThoiGianBatDau'] && $dotThucTapInfo['ThoiGianKetThuc']): ?>
                            | <strong>Thời gian:</strong> 
                            <?php echo date('d/m/Y', strtotime($dotThucTapInfo['ThoiGianBatDau'])); ?> - 
                            <?php echo date('d/m/Y', strtotime($dotThucTapInfo['ThoiGianKetThuc'])); ?>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning" style="border-radius: 12px; background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%); border: 1px solid #ffeaa7;">
                        <i class="fa fa-exclamation-triangle"></i>
                        <strong>Chưa có đợt thực tập:</strong> Bạn chưa được phân công vào đợt thực tập nào. Vui lòng liên hệ giáo viên hướng dẫn.
                    </div>
                <?php endif; ?>

                <!-- Modal thông báo tuyệt đẹp -->
                <div class="modal fade" id="notifyModal" tabindex="-1" role="dialog" aria-labelledby="notifyModalLabel" aria-hidden="true">
                  <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 370px;">
                    <div class="modal-content">
                      <div class="modal-body px-4 pt-4 pb-2 text-center position-relative">
                        <div id="notifyIcon"></div>
                        <h5 class="modal-title w-100" id="notifyModalLabel"></h5>
                        <div id="notifyModalBody"></div>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn-close-modal" data-dismiss="modal" aria-label="Đóng">
                          &times;
                        </button>
                      </div>
                    </div>
                  </div>
                </div>

                <?php if (!empty($message)): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        $('.modal').modal('hide');
        setTimeout(function() {
            var modal = $('#notifyModal');
            var type = '<?= $messageType ?>';
            // Icon tuyệt đẹp căn giữa trên
            let iconHtml = '';
            if(type === 'success') {
                iconHtml = `<div style="margin-bottom:0;">
                  <svg width="70" height="70" fill="none">
                    <circle cx="35" cy="35" r="35" fill="#e6f9ed"/>
                    <path d="M20 37l10 10 20-24" stroke="#28a745" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </div>`;
            } else {
                iconHtml = `<div style="margin-bottom:0;">
                  <svg width="70" height="70" fill="none">
                    <circle cx="35" cy="35" r="35" fill="#fdeaea"/>
                    <path d="M25 25l20 20M45 25l-20 20" stroke="#dc3545" stroke-width="4" stroke-linecap="round"/>
                  </svg>
                </div>`;
            }
            $('#notifyIcon').html(iconHtml);
            $('#notifyModalLabel').text(type === 'success' ? 'Thành công' : 'Lỗi');
            $('#notifyModalLabel').css('color', type === 'success' ? '#28a745' : '#dc3545');
            $('#notifyModalBody').html('<?= htmlspecialchars($message) ?>');
            modal.modal({backdrop: 'static', keyboard: true});
            modal.modal('show');
            // Tự động đóng sau 3s
            setTimeout(function() {
                modal.modal('hide');
            }, 3000);
        }, 300);
    });
</script>
<?php endif; ?>

                <!-- Thanh tìm kiếm và nút tự điền -->
                <div class="search-bar">
                    <input type="text" id="search-company" placeholder="Tìm kiếm công ty theo tên, MST, lĩnh vực...">
                    <button type="button" class="btn btn-info" id="btn-add-manual">
                        <i class="fa fa-plus"></i> Thêm
                    </button>
                </div>
                <div class="row">
                <!-- Card 1: Danh sách công ty thực tập -->
                <div class="col-lg-9 mb-4">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-primary text-white font-weight-bold">
                            Danh sách công ty thực tập
                        </div>
                        <div class="card-body">
                            <!-- Danh sách công ty dạng panel -->
                            <div id="company-panel-list" class="row"></div>
                        </div>
                        <div class="card-footer bg-white" style="border-radius: 0 0 22px 22px;">
                            <div class="company-list-pagination" id="company-pagination"></div>
                        </div>
                    </div>
                </div>
                <!-- Card 2: Trạng thái giấy đăng ký giới thiệu -->
                <div class="col-lg-3 mb-4">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-primary text-white font-weight-bold">
                            Phiếu đăng ký
                        </div>
                        <div class="card-body">
                            <div id="giay-panel-list"></div>
                        </div>
                        <div class="card-footer bg-white" style="border-radius: 0 0 22px 22px;">
                            <div class="giay-list-pagination" id="giay-pagination"></div>
                        </div>
                    </div>
                </div>
            </div>
               

                <!-- Modal chi tiết công ty -->
                <div class="modal fade" id="companyModal" tabindex="-1" role="dialog" aria-labelledby="companyModalLabel" aria-hidden="true">
                  <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="companyModalLabel">Thông tin công ty</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
                          <span aria-hidden="true">&times;</span>
                        </button>
                      </div>
                      <div class="modal-body" id="company-modal-body">
                        <!-- Nội dung sẽ được fill bằng JS -->
                        <form method="post" id="company-approve-form" style="display:none;">
                            <input type="hidden" name="ma_so_thue" id="approve-ma-so-thue">
                            <input type="hidden" name="ten_cong_ty" id="approve-ten-cong-ty">
                            <input type="hidden" name="dia_chi" id="approve-dia-chi">
                            <input type="hidden" name="linh_vuc" id="approve-linh-vuc">
                            <input type="hidden" name="sdt" id="approve-sdt">
                            <input type="hidden" name="email" id="approve-email">
                            <input type="hidden" name="from_panel" value="1">
                        </form>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-success" id="btn-approve-company">
                            <i class="fa fa-check"></i> Gửi (Đã duyệt)
                        </button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Modal nhập thủ công -->
                <div class="modal fade" id="manualModal" tabindex="-1" role="dialog" aria-labelledby="manualModalLabel" aria-hidden="true">
                  <div class="modal-dialog" role="document">
                    <form method="post" id="manual-company-form" class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="manualModalLabel">Nhập thông tin công ty thủ công</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
                          <span aria-hidden="true">&times;</span>
                        </button>
                      </div>
                      <div class="modal-body">
                        <div class="form-group">
                            <label for="manual-ma-so-thue">Mã số thuế</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="manual-ma-so-thue" name="ma_so_thue" placeholder="Mã số thuế">
                                <div class="input-group-append">
                                    <button class="btn btn-primary" type="button" id="btn-fill-api">
                                        <i class="fa fa-sync"></i> Lấy thông tin
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="manual-ten-cong-ty">Tên công ty</label>
                            <input type="text" class="form-control" id="manual-ten-cong-ty" name="ten_cong_ty" placeholder="Tên công ty">
                        </div>
                        <div class="form-group">
                            <label for="manual-dia-chi">Địa chỉ</label>
                            <input type="text" class="form-control" id="manual-dia-chi" name="dia_chi" placeholder="Địa chỉ">
                        </div>
                        <div class="form-group">
                            <label for="manual-linh-vuc">Lĩnh vực</label>
                            <input type="text" class="form-control" id="manual-linh-vuc" name="linh_vuc" placeholder="Lĩnh vực">
                        </div>
                        <div class="form-group">
                            <label for="manual-sdt">SĐT</label>
                            <input type="text" class="form-control" id="manual-sdt" name="sdt" placeholder="Số điện thoại">
                        </div>
                        <div class="form-group">
                            <label for="manual-email">Email</label>
                            <input type="email" class="form-control" id="manual-email" name="email" placeholder="Email">
                        </div>
                      </div>
                      <div class="modal-footer">
                        <button type="submit" class="btn btn-success"><i class="fa fa-paper-plane"></i> Gửi yêu cầu</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                      </div>
                    </form>
                  </div>
                </div>
            </div>
        </div>
    </div>
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/footer.php"; ?>
    <script>
    const companyList = <?= json_encode($companyList) ?>;
    let filteredCompanies = [...companyList];
    let currentPage = 1;
    const perPage = 8;

    function renderCompanyPanels() {
        const list = document.getElementById('company-panel-list');
        list.innerHTML = '';
        const start = (currentPage - 1) * perPage;
        const end = start + perPage;
        const pageCompanies = filteredCompanies.slice(start, end);

        if (pageCompanies.length === 0) {
            list.innerHTML = '<div class="col-12 text-center text-muted">Không tìm thấy công ty phù hợp.</div>';
            return;
        }

        pageCompanies.forEach((cty, idx) => {
            const col = document.createElement('div');
            col.className = 'col-md-3 col-sm-6';
            col.innerHTML = `
                <div class="company-panel" data-index="${start + idx}">
                    <div class="font-weight-bold mb-2">${cty.TenCty}</div>
                    <div><b>MST:</b> ${cty.MaSoThue}</div>
                    <div><b>Lĩnh vực:</b> ${cty.Linhvuc}</div>
                </div>
            `;
            list.appendChild(col);
        });
    }

    function renderPagination() {
        const pag = document.getElementById('company-pagination');
        pag.innerHTML = '';
        let total = Math.ceil(filteredCompanies.length / perPage);
        if (total < 1) total = 1;

        const maxShow = 5; // Số nút trang hiển thị gần trang hiện tại
        let pages = [];

        if (total <= maxShow + 2) {
            // Hiển thị tất cả nếu số trang ít
            for (let i = 1; i <= total; i++) pages.push(i);
        } else {
            // Luôn hiển thị trang đầu và cuối
            pages.push(1);
            let start = Math.max(2, currentPage - 2);
            let end = Math.min(total - 1, currentPage + 2);

            if (start > 2) pages.push('...');
            for (let i = start; i <= end; i++) pages.push(i);
            if (end < total - 1) pages.push('...');
            pages.push(total);
        }

        pages.forEach(p => {
            if (p === '...') {
                const span = document.createElement('span');
                span.textContent = '...';
                span.style.cssText = 'padding:0 8px;color:#007bff;font-weight:bold;font-size:18px;user-select:none;';
                pag.appendChild(span);
            } else {
                const btn = document.createElement('button');
                btn.textContent = p;
                btn.className = (p === currentPage) ? 'active' : '';
                btn.onclick = () => {
                    currentPage = p;
                    renderCompanyPanels();
                    renderPagination();
                };
                pag.appendChild(btn);
            }
        });
    }

    function filterCompanies(keyword) {
        keyword = keyword.trim().toLowerCase();
        if (!keyword) {
            filteredCompanies = [...companyList];
        } else {
            filteredCompanies = companyList.filter(c =>
                (c.TenCty && c.TenCty.toLowerCase().includes(keyword)) ||
                (c.MaSoThue && c.MaSoThue.toLowerCase().includes(keyword)) ||
                (c.Linhvuc && c.Linhvuc.toLowerCase().includes(keyword))
            );
        }
        currentPage = 1;
        renderCompanyPanels();
        renderPagination();
    }

    // Dữ liệu giấy giới thiệu từ PHP
    const giayList = <?= json_encode($giayList) ?>;
    let giayCurrentPage = 1;
    const giayPerPage = 3; // mỗi trang chỉ 3 panel

    function renderGiayPanels() {
        const list = document.getElementById('giay-panel-list');
        list.innerHTML = '';
        const start = (giayCurrentPage - 1) * giayPerPage;
        const end = start + giayPerPage;
        const pageGiay = giayList.slice(start, end);

        if (pageGiay.length === 0) {
            list.innerHTML = '<div class="text-center text-muted">Chưa có phiếu giới thiệu nào.</div>';
            return;
        }

        const trangThaiMap = {
            0: '<span class="badge badge-warning">Chờ duyệt</span>',
            1: '<span class="badge badge-success">Đã duyệt</span>',
            2: '<span class="badge badge-danger">Từ chối</span>',
        };

        pageGiay.forEach(giay => {
            const div = document.createElement('div');
            div.className = 'giay-panel mb-2';
            
            // Hiển thị thông tin đợt thực tập nếu có
            let dotInfo = '';
            if (giay.TenDot) {
                dotInfo = `<div style="font-size: 13px; color: #6c757d; margin-top: 4px;">
                    <i class="fa fa-calendar"></i> ${giay.TenDot}`;
                if (giay.ThoiGianBatDau && giay.ThoiGianKetThuc) {
                    const startDate = new Date(giay.ThoiGianBatDau).toLocaleDateString('vi-VN');
                    const endDate = new Date(giay.ThoiGianKetThuc).toLocaleDateString('vi-VN');
                    dotInfo += `<br><small>${startDate} - ${endDate}</small>`;
                }
                dotInfo += `</div>`;
            }
            
            div.innerHTML = `
                <div class="tencty">${giay.TenCty}</div>
                <div class="trangthai">Trạng thái: ${trangThaiMap[giay.TrangThai] || '<span class="badge badge-secondary">Không xác định</span>'}</div>
                ${dotInfo}
            `;
            list.appendChild(div);
        });
    }

    function renderGiayPagination() {
        const pag = document.getElementById('giay-pagination');
        pag.innerHTML = '';
        let total = Math.ceil(giayList.length / giayPerPage);
        if (total < 1) total = 1;

        const maxShow = 3;
        let pages = [];

        if (total <= maxShow + 2) {
            for (let i = 1; i <= total; i++) pages.push(i);
        } else {
            pages.push(1);
            let start = Math.max(2, giayCurrentPage - 1);
            let end = Math.min(total - 1, giayCurrentPage + 1);

            if (start > 2) pages.push('...');
            for (let i = start; i <= end; i++) pages.push(i);
            if (end < total - 1) pages.push('...');
            pages.push(total);
        }

        pages.forEach(p => {
            if (p === '...') {
                const span = document.createElement('span');
                span.textContent = '...';
                pag.appendChild(span);
            } else {
                const btn = document.createElement('button');
                btn.textContent = p;
                btn.className = (p === giayCurrentPage) ? 'active' : '';
                btn.onclick = () => {
                    giayCurrentPage = p;
                    renderGiayPanels();
                    renderGiayPagination();
                };
                pag.appendChild(btn);
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        renderCompanyPanels();
        renderPagination();
        renderGiayPanels();
        renderGiayPagination();

        document.getElementById('search-company').addEventListener('input', function() {
            filterCompanies(this.value);
        });

        // Panel click mở modal
        document.getElementById('company-panel-list').addEventListener('click', function(e) {
            let panel = e.target.closest('.company-panel');
            if (!panel) return;
            const idx = +panel.getAttribute('data-index');
            const cty = filteredCompanies[idx];
            if (!cty) return;

            // Fill modal (thêm in đậm cho nhãn) + form ẩn
            document.getElementById('company-modal-body').innerHTML = `
                <div><b>Tên công ty:</b> ${cty.TenCty}</div>
                <div><b>Mã số thuế:</b> ${cty.MaSoThue}</div>
                <div><b>Địa chỉ:</b> ${cty.DiaChi}</div>
                <div><b>Lĩnh vực:</b> ${cty.Linhvuc}</div>
                <div><b>SĐT:</b> ${cty.Sdt}</div>
                <div><b>Email:</b> ${cty.Email}</div>
                <form method="post" id="company-approve-form" style="display:none;">
                    <input type="hidden" name="ma_so_thue" id="approve-ma-so-thue">
                    <input type="hidden" name="ten_cong_ty" id="approve-ten-cong-ty">
                    <input type="hidden" name="dia_chi" id="approve-dia-chi">
                    <input type="hidden" name="linh_vuc" id="approve-linh-vuc">
                    <input type="hidden" name="sdt" id="approve-sdt">
                    <input type="hidden" name="email" id="approve-email">
                    <input type="hidden" name="from_panel" value="1">
                </form>
            `;
            $('#companyModal').modal('show');

            // Đảm bảo chỉ gán sự kiện 1 lần: Xóa sự kiện cũ trước khi gán mới
            const btnApprove = document.getElementById('btn-approve-company');
            const newBtnApprove = btnApprove.cloneNode(true);
            btnApprove.parentNode.replaceChild(newBtnApprove, btnApprove);

            newBtnApprove.onclick = function() {
                // Lấy lại input hidden vừa tạo trong modal-body
                document.getElementById('approve-ten-cong-ty').value = cty.TenCty;
                document.getElementById('approve-ma-so-thue').value = cty.MaSoThue;
                document.getElementById('approve-dia-chi').value = cty.DiaChi;
                document.getElementById('approve-linh-vuc').value = cty.Linhvuc;
                document.getElementById('approve-sdt').value = cty.Sdt;
                document.getElementById('approve-email').value = cty.Email;
                // Submit form ẩn để lưu về CSDL bằng PHP thuần
                document.getElementById('company-approve-form').submit();
            };
        });

        // Nút mở modal nhập thủ công
        document.getElementById('btn-add-manual').onclick = function() {
            $('#manualModal').modal('show');
        };

        // Giữ lại đoạn này để lấy thông tin công ty bằng API khi bấm nút "Lấy thông tin"
        document.getElementById('btn-fill-api').onclick = async function(e) {
            e.preventDefault();
            const taxCode = document.getElementById('manual-ma-so-thue').value.trim();
            if (!taxCode) {
                alert('Vui lòng nhập mã số thuế');
                return;
            }
            const info = await getBusinessInfoByTaxCode(taxCode);
            if (info) {
                document.getElementById('manual-ten-cong-ty').value = info.name || info.shortName || '';
                document.getElementById('manual-dia-chi').value = info.address || info.diaChi || '';
                document.getElementById('manual-linh-vuc').value = info.businessLine || info.linhVuc || '';
                document.getElementById('manual-sdt').value = info.phone || info.soDienThoai || '';
                document.getElementById('manual-email').value = info.email || '';
            } else {
                alert('Không tìm thấy thông tin doanh nghiệp hoặc API bị lỗi.');
            }
        };

        // Gửi form nhập thủ công (submit về PHP, không gọi API, chỉ validate dữ liệu)
        document.getElementById('manual-company-form').onsubmit = function(e) {
            const taxCode = document.getElementById('manual-ma-so-thue').value.trim();
            const name = document.getElementById('manual-ten-cong-ty').value.trim();
            const address = document.getElementById('manual-dia-chi').value.trim();
            const field = document.getElementById('manual-linh-vuc').value.trim();
            const phone = document.getElementById('manual-sdt').value.trim();
            const email = document.getElementById('manual-email').value.trim();

            // Kiểm tra rỗng
            if (!taxCode || !name || !address || !field || !phone || !email) {
                alert('Vui lòng nhập đầy đủ tất cả các trường!');
                e.preventDefault();
                return false;
            }
            // Kiểm tra email hợp lệ
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                alert('Email không hợp lệ!');
                e.preventDefault();
                return false;
            }
            // Kiểm tra số điện thoại (chỉ số, tối thiểu 8 ký tự)
            const phoneRegex = /^[0-9\-\+\s]{8,}$/;
            if (!phoneRegex.test(phone)) {
                alert('Số điện thoại không hợp lệ!');
                e.preventDefault();
                return false;
            }
            // Không gọi API ở đây nữa!
            return true;
        };
        // Form nhập thủ công
        const manualForm = document.getElementById('manual-company-form');
        if (manualForm) {
            manualForm.addEventListener('submit', function() {
                $('#loadingModal').modal('show');
            });
        }
    });
    </script>
    <script src="/datn/api/getapi.js"></script>
</body>
</html>