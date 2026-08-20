@props(['chartData'])

<div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
    <h3 class="text-sm font-semibold text-secondary dark:text-light">Activité des 30 derniers jours</h3>
    <div class="mt-4 h-64">
        <canvas id="activity-chart"></canvas>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const canvas = document.getElementById('activity-chart');

        if (!canvas || !window.Chart) {
            return;
        }

        new window.Chart(canvas, {
            type: 'line',
            data: {
                labels: @json($chartData['labels']),
                datasets: [{
                    label: 'Dépenses (FCFA)',
                    data: @json($chartData['data']),
                    borderColor: '#0EA5A4',
                    backgroundColor: 'rgba(14, 165, 164, 0.1)',
                    tension: 0.3,
                    fill: true,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                },
                scales: {
                    y: { beginAtZero: true },
                },
            },
        });
    });
</script>
