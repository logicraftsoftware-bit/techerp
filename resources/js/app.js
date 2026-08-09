import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';
import DataTable from 'datatables.net-dt';
import Swal from 'sweetalert2';
import 'datatables.net-dt/css/dataTables.dataTables.css';

window.Alpine = Alpine;
window.Swal = Swal;
Alpine.start();

const flashSuccess = document.querySelector('[data-flash-success]')?.dataset.flashSuccess;
if (flashSuccess) {
    Swal.fire({ icon: 'success', title: 'Success', text: flashSuccess, timer: 1800, timerProgressBar: true, showConfirmButton: false });
}

document.addEventListener('submit', async (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || form.dataset.sweetAlertConfirmed === 'true') return;

    const method = form.querySelector('input[name="_method"]')?.value?.toUpperCase();
    const isLogout = form.action.includes('/logout');
    if (method !== 'DELETE' && !isLogout) return;

    event.preventDefault();
    event.stopPropagation();
    const result = await Swal.fire({
        icon: 'warning',
        title: isLogout ? 'Logout?' : 'Are you sure?',
        text: isLogout ? 'Do you want to end your current session?' : 'This record will be deleted permanently.',
        showCancelButton: true,
        confirmButtonText: isLogout ? 'Yes, logout' : 'Yes, delete',
        cancelButtonText: 'Cancel',
        confirmButtonColor: isLogout ? '#2563eb' : '#dc2626',
        reverseButtons: true,
    });

    if (result.isConfirmed) {
        form.dataset.sweetAlertConfirmed = 'true';
        form.submit();
    }
}, true);

document.querySelectorAll('table[data-datatable]').forEach((table) => new DataTable(table, {
    paging: false,
    searching: false,
    info: false,
    order: [],
}));

Chart.defaults.font.family = 'Instrument Sans, ui-sans-serif, system-ui';
Chart.defaults.color = '#64748b';
window.renderDashboardCharts = (data) => {
    const common = { responsive: true, maintainAspectRatio: false, plugins: { legend: { labels: { usePointStyle: true, boxWidth: 8 } } } };
    const work = document.getElementById('workChart');
    if (work) new Chart(work, { type: 'line', data: { labels: data.work.labels, datasets: [
        { label: 'Completed', data: data.work.completed, borderColor: '#2563eb', backgroundColor: 'rgba(37,99,235,.1)', fill: true, tension: .4 },
        { label: 'Pending', data: data.work.pending, borderColor: '#f59e0b', tension: .4 }
    ]}, options: { ...common, scales: { y: { beginAtZero: true, grid: { color: '#f1f5f9' } }, x: { grid: { display: false } } } } });
    const doughnut = (id, labels, values, colors) => { const el = document.getElementById(id); if (el) new Chart(el, { type: 'doughnut', data: { labels, datasets: [{ data: values.every(v => v === 0) ? values.map(() => 1) : values, backgroundColor: colors, borderWidth: 0 }] }, options: { ...common, cutout: '72%' } }); };
    doughnut('statusChart', data.status.labels, data.status.values, ['#10b981','#f59e0b','#2563eb','#ef4444']);
    doughnut('attendanceChart', data.attendance.labels, data.attendance.values, ['#10b981','#ec4899','#94a3b8']);
};

document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-toggle-user]');
    if (!button) return;
    const confirmation = await Swal.fire({ icon: 'question', title: 'Change user status?', showCancelButton: true, confirmButtonText: 'Yes, change it', confirmButtonColor: '#2563eb' });
    if (!confirmation.isConfirmed) return;
    button.disabled = true;
    try {
        const response = await fetch(button.dataset.toggleUser, { method: 'PATCH', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } });
        const data = await response.json();
        if (!response.ok) throw new Error(data.message || 'Unable to update status.');
        button.textContent = data.is_active ? 'Active' : 'Inactive';
        button.className = `badge ${data.is_active ? 'badge-success' : 'badge-danger'}`;
    } catch (error) { Swal.fire({ icon: 'error', title: 'Unable to update', text: error.message }); } finally { button.disabled = false; }
});
