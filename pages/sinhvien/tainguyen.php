<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Tài nguyên thực tập</title>
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/head.php"; ?>
    <style>
        #page-wrapper {
            padding: 30px;
            min-height: 100vh;
            box-sizing: border-box;
            max-height: 100%;
            overflow-y: auto;
        }

        .panel-tainguyen {
            padding: 20px;
            min-height: 120px;
            background: #f8fff8;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            margin-bottom: 20px;
            transition: box-shadow 0.2s;
        }

        .panel-tainguyen:hover {
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            background: #eaffea;
        }

        .panel-tainguyen .fa {
            margin-right: 12px;
            font-size: 28px;
            color: #4caf50;
        }

        .panel-tainguyen .ten-tainguyen {
            font-size: 18px;
            font-weight: bold;
            color: #222;
        }

        .panel-tainguyen .tenfile {
            font-size: 13px;
            color: #888;
        }

        .panel-tainguyen .nguoidang {
            font-size: 12px;
            color: #666;
        }

        .panel-tainguyen .btn {
            margin-top: 8px;
        }
    </style>
</head>

<body>
    <div id="wrapper">
        <?php require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/slidebar_Sinhvien.php"; ?>
        <div id="page-wrapper">
            <div class="container-fluid">
                <h1 class="page-header">Tài nguyên</h1>
                <div class="row">
                    <?php
                    require_once $_SERVER['DOCUMENT_ROOT'] . "/datn/template/config.php";
                    // Lấy tài nguyên loại Tainguyen, trạng thái hiển thị
                    $stmt = $conn->prepare("SELECT f.*, vtt.TenNguoiDung
                    FROM file f
                    LEFT JOIN view_taikhoan_ten vtt ON f.ID_SV = vtt.ID_TaiKhoan OR f.ID_GVHD = vtt.ID_TaiKhoan
                    WHERE f.Loai='Tainguyen' AND f.TrangThai=1
                    ORDER BY f.NgayNop DESC");
                    $stmt->execute();
                    $tainguyen = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    if (empty($tainguyen)) {
                        echo '<div class="col-md-12"><div class="alert alert-info">Chưa có tài nguyên nào.</div></div>';
                    }
                    foreach ($tainguyen as $row):
                        // Xác định link file hoặc link ngoài
                        $isFile = strpos($row['DIR'], '/datn/file/') !== false || strpos($row['DIR'], '\\datn\\file\\') !== false;
                        $fileUrl = $isFile ? '/datn/file/' . basename($row['DIR']) : $row['DIR'];
                        ?>
                        <div class="col-md-3 col-sm-6">
                            <div class="panel panel-tainguyen">
                                <div style="display: flex; align-items: center;">
                                    <i class="fa fa-file-o fa-fw"></i>
                                    <div>
                                        <div class="ten-tainguyen"><?= htmlspecialchars($row['Ten']) ?></div>
                                        <div class="tenfile"><?= htmlspecialchars($row['TenFile']) ?></div>
                                        <div class="nguoidang">
                                            <?= $row['TenNguoiDung'] ? 'Người đăng: ' . htmlspecialchars($row['TenNguoiDung']) : '' ?>
                                        </div>
                                        <a href="javascript:void(0)"
                                            onclick="xemFileOnline('<?= htmlspecialchars($fileUrl) ?>')"
                                            class="btn btn-xs btn-info">Xem online</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="modal fade" id="modalXemFile" tabindex="-1" role="dialog">
                    <div class="modal-dialog" style="width:90%;max-width:900px;" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h4 class="modal-title">Xem tài nguyên</h4>
                            </div>
                            <div class="modal-body" id="xemFileBody" style="min-height:500px;text-align:center"></div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-default" data-dismiss="modal">Đóng</button>
                            </div>
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
        function xemFileOnline(url) {
            // Chuẩn hóa đường dẫn
            url = url.replace(/\\/g, '/');

            const ext = url.split('.').pop().toLowerCase();
            let html = '';

            if (['pdf'].includes(ext)) {
                html = `<iframe src="${url}" style="width:100%;height:600px;border:none;"></iframe>`;
            } else if (['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'].includes(ext)) {
                html = `<img src="${url}" style="max-width:100%;max-height:600px;">`;
            } else if (['mp4', 'webm', 'ogg', 'avi', 'mov', 'wmv'].includes(ext)) {
                html = `<video src="${url}" controls style="max-width:100%;max-height:600px;"></video>`;
            } else if (['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'].includes(ext)) {
                html = `<iframe src="https://docs.google.com/gview?url=${location.origin + url}&embedded=true" 
                     style="width:100%;height:600px;" frameborder="0"></iframe>`;
            } else if (url.startsWith('http')) {
                html = `<iframe src="${url}" style="width:100%;height:600px;border:none;"></iframe>`;
            } else if (ext === 'txt') {
                fetch(url).then(res => res.text()).then(text => {
                    $('#xemFileBody').html('<pre style="text-align:left;">' + $('<div>').text(text).html() + '</pre>');
                });
                return;
            } else {
                html = `<a href="${url}" target="_blank">Không thể xem trực tuyến. Bấm để tải file</a>`;
            }

            $('#xemFileBody').html(html);
            $('#modalXemFile').modal('show');
        }
    </script>
</body>

</html>