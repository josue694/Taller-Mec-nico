$(function () {

    // Sidebar toggle
    $('#sidebarToggle').on('click', function () {
        $('#sidebar').toggleClass('collapsed open');
    });

    // Marcar enlace activo
    const currentPath = window.location.pathname;
    $('.sidebar-nav a').each(function () {
        if ($(this).attr('href') && currentPath.includes($(this).attr('href').split('/').pop())) {
            $(this).addClass('active');
        }
    });

    // DataTables — sin warning
    if ($.fn.DataTable) {
        $('.datatable').each(function () {
            const numCols     = $(this).find('thead tr:first th').length;
            const numColsBody = $(this).find('tbody tr:first td').length;
            if (numColsBody === 0 || numCols === numColsBody) {
                if (!$.fn.DataTable.isDataTable(this)) {
                    $(this).DataTable({
                        language: {
                            url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                        },
                        pageLength: 15,
                        responsive: true
                    });
                }
            }
        });
    }

    // Auto-hide alerts
    setTimeout(function () {
        $('.alert-dismissible').fadeOut('slow');
    }, 4000);

    // Confirmar acciones
    $(document).on('click', '.btn-confirm', function (e) {
        const msg = $(this).data('confirm') || '¿Está seguro?';
        if (!confirm(msg)) e.preventDefault();
    });

    // Tooltips Bootstrap
    document.querySelectorAll('[data-bs-toggle="tooltip"]')
        .forEach(el => new bootstrap.Tooltip(el));
});