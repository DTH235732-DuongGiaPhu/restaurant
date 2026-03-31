/* Admin JS */
$(document).ready(function() {
    // Sidebar toggle
    window.toggleSidebar = function() {
        $('#adminSidebar').toggleClass('open');
        $('#sidebarOverlay').toggleClass('show');
    };

    // Auto close flash
    setTimeout(function() { $('.admin-flash').fadeOut(300); }, 4000);

    // Confirm delete
    $(document).on('click', '[data-confirm]', function(e) {
        if (!confirm($(this).data('confirm') || 'Bạn có chắc muốn xóa?')) e.preventDefault();
    });
});
