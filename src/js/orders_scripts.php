document.addEventListener('DOMContentLoaded', function() {
    // Проверка наличия jQuery и DataTables
    if (typeof $ !== 'undefined' && typeof $.fn.DataTable !== 'undefined') {
        // Уничтожаем существующую таблицу, если она есть
        if ($.fn.dataTable.isDataTable('.datatable')) {
            $('.datatable').DataTable().destroy();
        }
        
        // Инициализируем с русской локализацией
        $('.datatable').DataTable({
            // Вместо URL можно использовать прямой объект локализации
            language: {
                "processing": "Подождите...",
                "search": "Поиск:",
                "lengthMenu": "Показать _MENU_ записей",
                "info": "Записи с _START_ до _END_ из _TOTAL_ записей",
                "infoEmpty": "Записи с 0 до 0 из 0 записей",
                "infoFiltered": "(отфильтровано из _MAX_ записей)",
                "loadingRecords": "Загрузка записей...",
                "zeroRecords": "Записи отсутствуют.",
                "emptyTable": "В таблице отсутствуют данные",
                "paginate": {
                    "first": "Первая",
                    "previous": "Предыдущая",
                    "next": "Следующая",
                    "last": "Последняя"
                }
                // (здесь сокращённая версия, можете добавить все опции из вашего JSON)
            },
            pageLength: 25,
            order: [[4, 'desc']] // Сортировка по дате назначения
        });
    }
});