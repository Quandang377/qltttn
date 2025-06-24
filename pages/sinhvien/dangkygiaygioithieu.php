<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng ký giấy giới thiệu</title>
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php"; ?>
    <style>
        body { background: #f8fafc; }
        #page-wrapper { padding: 30px; min-height: 100vh; }
        .search-bar {
            margin-bottom: 28px;
            display: flex;
            gap: 14px;
            align-items: center;
        }
        .search-bar input {
            flex: 1;
            border-radius: 10px;
            border: 1.5px solid #b6d4fe;
            padding: 10px 18px;
            font-size: 17px;
            background: #fafdff;
        }
        .search-bar button {
            border-radius: 10px;
            padding: 10px 22px;
            font-size: 17px;
            font-weight: 600;
        }
        .card {
            border-radius: 22px !important;
            border: 3px solid #007bff !important;
            box-shadow: 0 6px 32px rgba(0,123,255,0.09);
            margin-bottom: 36px;
        }
        .card-header {
            border-radius: 22px 22px 0 0 !important;
            font-size: 22px;
            font-weight: bold;
            letter-spacing: 1px;
            padding: 18px 28px !important;
            background: linear-gradient(90deg, #007bff 70%, #5bc0f7 100%);
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
        }
        .company-panel:hover {
            border-color: #007bff;
            box-shadow: 0 4px 24px rgba(0,123,255,0.16);
            background: #f0f8ff;
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
        }
        .company-list-pagination button {
            border: none;
            background: #e9ecef;
            color: #007bff;
            border-radius: 50%;
            width: 38px;
            height: 38px;
            font-weight: 700;
            font-size: 17px;
            cursor: pointer;
            transition: background 0.2s, color 0.2s;
        }
        .company-list-pagination button.active,
        .company-list-pagination button:hover {
            background: #007bff;
            color: #fff;
        }
        @media (max-width: 991px) {
            .card-body { padding: 18px 6px 12px 6px !important; }
            .company-panel { padding: 18px 8px 14px 8px; }
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

            // Giả sử lấy ID sinh viên từ session hoặc gán tạm
            $idSinhVien = 3;

            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ma_so_thue'])) {
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
                    // Thêm phiếu đăng ký thực tập vào bảng giaygioithieu với trạng thái 0 (chờ duyệt)
                    try {
                        $stmt = $conn->prepare("INSERT INTO giaygioithieu (TenCty, MaSoThue, DiaChi, LinhVuc, Sdt, Email, IdSinhVien, TrangThai) VALUES (?, ?, ?, ?, ?, ?, ?, 0)");
                        $stmt->execute([$name, $taxCode, $address, $field, $phone, $email, $idSinhVien]);
                        $message = 'Đã gửi phiếu đăng ký thực tập, vui lòng chờ duyệt!';
                        $messageType = 'success';
                    } catch (Exception $e) {
                        $message = 'Có lỗi xảy ra khi lưu dữ liệu!';
                        $messageType = 'danger';
                    }
                }
            }

            // Lấy danh sách công ty
            $stmt = $conn->prepare("SELECT ID, TenCty, MaSoThue, DiaChi, Sdt, Email, Linhvuc FROM congty WHERE TrangThai = 1");
            $stmt->execute();
            $companyList = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
        <div id="page-wrapper">
            <div class="container-fluid">
                <h1 class="page-header">Đăng ký giấy giới thiệu</h1>

                <!-- Thanh tìm kiếm và nút tự điền -->
                <div class="search-bar">
                    <input type="text" id="search-company" placeholder="Tìm kiếm công ty theo tên, MST, lĩnh vực...">
                    <button type="button" class="btn btn-info" id="btn-add-manual">
                        <i class="fa fa-plus"></i> Tự điền thông tin công ty
                    </button>
                </div>

                <!-- Đóng khung danh sách công ty bằng card Bootstrap -->
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
        if (total < 1) total = 1; // Luôn có ít nhất 1 trang

        for (let i = 1; i <= total; i++) {
            const btn = document.createElement('button');
            btn.textContent = i;
            btn.className = (i === currentPage) ? 'active' : '';
            btn.onclick = () => {
                currentPage = i;
                renderCompanyPanels();
                renderPagination();
            };
            pag.appendChild(btn);
        }
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

    document.addEventListener('DOMContentLoaded', function() {
        renderCompanyPanels();
        renderPagination();

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

            // Fill modal (thêm in đậm cho nhãn)
            document.getElementById('company-modal-body').innerHTML = `
                <div><b>Tên công ty:</b> ${cty.TenCty}</div>
                <div><b>Mã số thuế:</b> ${cty.MaSoThue}</div>
                <div><b>Địa chỉ:</b> ${cty.DiaChi}</div>
                <div><b>Lĩnh vực:</b> ${cty.Linhvuc}</div>
                <div><b>SĐT:</b> ${cty.Sdt}</div>
                <div><b>Email:</b> ${cty.Email}</div>
            `;
            $('#companyModal').modal('show');

            // Đảm bảo chỉ gán sự kiện 1 lần: Xóa sự kiện cũ trước khi gán mới
            const btnApprove = document.getElementById('btn-approve-company');
            const newBtnApprove = btnApprove.cloneNode(true);
            btnApprove.parentNode.replaceChild(newBtnApprove, btnApprove);

            newBtnApprove.onclick = function() {
                fetch('/datn/template/approve_company.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ id: cty.ID })
                })
                .then(res => res.json())
                .then(data => {
                    // Xóa alert cũ nếu có
                    const oldAlert = document.querySelector('#company-modal-body .alert');
                    if (oldAlert) oldAlert.remove();

                    let alertDiv = document.createElement('div');
                    alertDiv.className = 'alert text-center mt-3 ' + (data.success ? 'alert-success' : 'alert-danger');
                    alertDiv.innerText = data.success
                        ? 'Đã gửi phiếu đăng ký thực tập và duyệt thành công!'
                        : (data.message || 'Có lỗi xảy ra!');
                    document.getElementById('company-modal-body').appendChild(alertDiv);

                    if (data.success) {
                        setTimeout(() => {
                            $('#companyModal').modal('hide');
                            location.reload();
                        }, 1200);
                    }
                });
            };
        });

        // Nút mở modal nhập thủ công
        document.getElementById('btn-add-manual').onclick = function() {
            $('#manualModal').modal('show');
        };

        // Nút tự động fill thông tin công ty bằng API
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

        // Gửi form nhập thủ công (submit về PHP như cũ)
        document.getElementById('manual-company-form').onsubmit = function(e) {
            // Lấy giá trị các trường
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
            // Có thể kiểm tra thêm mã số thuế nếu muốn
            // Nếu hợp lệ thì cho submit
            return true;
        };
    });
    </script>
    <script src="/datn/api/getapi.js"></script>
</body>
</html>