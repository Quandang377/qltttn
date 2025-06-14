<?php
function initDataTableJS($tableId,$btnID) {
    echo "
    <script>
    $(document).ready(function () {
        if ($.fn.dataTable.isDataTable('#$tableId')) {
            $('#$tableId').DataTable().destroy();
        }

        var table = $('#$tableId').DataTable({
            responsive: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/vi.json'
            }
        });

        $('#$tableId tbody').on('click', 'tr', function () {
            if ($(this).hasClass('selected')) {
                $(this).removeClass('selected');
            } else {
                table.$('tr.selected').removeClass('selected');
                $(this).addClass('selected');
            }
        });

        $('#$btnID').on('click', function() {
            var selectedData = table.row('.selected').data();
            if (!selectedData) {
                alert('Vui lòng chọn một dòng trước khi nhấn nút!');
                return;
            }
            alert('Bạn đã chọn: ' + selectedData.join(' | '));
            
        });
    });
    </script>
    ";
}
?>
