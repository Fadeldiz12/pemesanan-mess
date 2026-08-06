import './bootstrap';

document.addEventListener('DOMContentLoaded', function () {
    document.body.addEventListener('click', function (e) {
        const toggleCell = e.target.closest('.table-accordion td.toggle-cell');
        if (!toggleCell || window.innerWidth >= 768) return;
        const tr = toggleCell.closest('tr');
        tr.classList.toggle('is-expanded');
    });
});