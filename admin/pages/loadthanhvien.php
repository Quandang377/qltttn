<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/middleware/check_role.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/datn/template/config.php';
$vaiTro = $_GET['vaiTro'] ?? 'Tất cả';

$sql = "SELECT 
            tk.ID_TaiKhoan, 
            tk.TaiKhoan,
            tk.VaiTro, 
            tk.TrangThai,
            COALESCE(sv.Ten, gv.Ten, cbk.Ten,ad.Ten) AS HoTen
        FROM TaiKhoan tk 
        LEFT JOIN SinhVien sv ON sv.ID_TaiKhoan = tk.ID_TaiKhoan
        LEFT JOIN GiaoVien gv ON gv.ID_TaiKhoan = tk.ID_TaiKhoan
        LEFT JOIN CanBoKhoa cbk ON cbk.ID_TaiKhoan = tk.ID_TaiKhoan
        LEFT JOIN Admin ad ON ad.ID_TaiKhoan = tk.ID_TaiKhoan";
$params = [];
if ($vaiTro != 'Tất cả') {
    $sql .= " WHERE tk.VaiTro = ?";
    $params[] = $vaiTro;
}
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$danhSachThanhVien = $stmt->fetchAll(PDO::FETCH_ASSOC);

$dsHoatDong = [];
$dsNgung = [];
foreach ($danhSachThanhVien as $tv) {
    if ($tv['TrangThai'] == 1) {
        $dsHoatDong[] = $tv;
    } else {
        $dsNgung[] = $tv;
    }
}
?>
<table class="table" id="tableThanhVienHoatDong">
    <thead>
        <tr>
            <th></th>
            <th>Tài Khoản</th>
            <th>Họ Tên</th>
            <th>Vai trò</th>
            <th>Trạng Thái</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($dsHoatDong as $tv): ?>
    <tr>
        <td><input type="checkbox" name="chon[]" value="<?= $tv['ID_TaiKhoan'] ?>"></td>
        <td style="cursor:pointer" onclick='moModalChinhSua(<?= json_encode($tv, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'><?= $tv['TaiKhoan'] ?></td>
        <td style="cursor:pointer" onclick='moModalChinhSua(<?= json_encode($tv, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'><?= $tv['HoTen'] ?></td>
        <td style="cursor:pointer" onclick='moModalChinhSua(<?= json_encode($tv, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'><?= $tv['VaiTro'] ?></td>
        <td>Hoạt động</td>
    </tr>
<?php endforeach; ?>
    </tbody>
</table>

<!-- Bảng Ngừng hoạt động -->
<table class="table" id="tableThanhVienNgung">
    <thead>
        <tr>
            <th></th>
            <th>Tài Khoản</th>
            <th>Họ Tên</th>
            <th>Vai trò</th>
            <th>Trạng Thái</th>
            <th>Mở khóa</th>
        </tr>
    </thead>

    <tbody>
        <?php foreach ($dsNgung as $tv): ?>
            <tr>
                <td><input type="checkbox" name="chon[]" value="<?= $tv['ID_TaiKhoan'] ?>"></td>
                <td><?= $tv['TaiKhoan'] ?></td>
                <td><?= $tv['HoTen'] ?></td>
                <td><?= $tv['VaiTro'] ?></td>
                <td>Ngừng hoạt động</td>
                <td>
                    <button type="button" class="btn btn-success btn-xs btn-mo-khoa" data-id="<?= $tv['ID_TaiKhoan'] ?>">Mở
                        khóa</button>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>