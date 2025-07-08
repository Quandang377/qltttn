<script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/js/bootstrap.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/datn/access/js/dataTables/jquery.dataTables.min.js"></script>
<script src="/datn/access/js/dataTables/dataTables.bootstrap.min.js"></script>
<?php
$stmt = $conn->query("SELECT * FROM cauhinh");
$cauhinh = [];
foreach ($stmt as $row) {
    $cauhinh[$row['Ten']] = $row['GiaTri'];
}
?>

<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<footer style="background-color: <?= $cauhinh['mau_sac_giaodien'] ?? '#2563eb' ?>; color: white; padding: 30px 0; margin-top: 50px;z-index: 1001;padding: 15px 0;
    font-size: 14px;
    position: relative;">
    <div class="container">
        <!-- Mạng xã hội -->
        <div class="row">
            <div class=" text-center text-md-right">
                <div class="col-md-4 text-center ">
                    <p><i class="fa fa-map-marker"></i> <?= $cauhinh['dia_chi_khoa'] ?? 'Địa chỉ Khoa' ?></p>

                </div>
                <div class="col-md-4 text-center mb-3">
                    <p><i class="fa fa-envelope"></i> <a href="mailto:<?= $cauhinh['email_lienhe'] ?? '#' ?>"
                            style="color: white;"><?= $cauhinh['email_lienhe'] ?? 'Email liên hệ' ?></a></p>
                </div>
                <div class="col-md-4 text-center mb-3">
                </div>
                <p><i class="fa fa-phone"></i> <?= $cauhinh['sdt_lienhe'] ?? 'Số điện thoại' ?></p>
            </div>

        </div>
        <hr style="border-color: rgba(255,255,255,0.3);">
        <div class="text-center" style="font-size: 14px;">
            <a href="<?= $cauhinh['website_khoa'] ?? 'https://cntt.caothang.edu.vn/' ?>"style='color:#ffffff'>
            <?= $cauhinh['footer_text'] ?? '© 2025 Trường Cao Thắng'?>  <i class="fa fa-external-link"></i>
            </a>
        </div>
    </div>
</footer>

<style>
    footer {
    clear: both;
    z-index: 1;
    position: relative;
}
</style>
