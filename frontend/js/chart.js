(function () {
  'use strict';

  window.SMS = window.SMS || {};

  SMS.renderBarChart = function (canvasId, labels, values, label) {
    const canvas = document.getElementById(canvasId);
    if (!canvas || typeof Chart === 'undefined') return;

    const role = document.body.className.match(/role-(\w+)/);
    const colors = {
      admin: '#475569',
      teacher: '#5b6b7c',
      student: '#6b7280',
    };
    const color = colors[role ? role[1] : 'admin'] || '#6b7280';

    new Chart(canvas, {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [{
          label: label || 'Count',
          data: values,
          backgroundColor: color + '99',
          borderColor: color,
          borderWidth: 1,
          borderRadius: 4,
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          y: { beginAtZero: true, grid: { color: '#e5e7eb' } },
          x: { grid: { display: false } },
        },
      },
    });
  };

  SMS.renderDoughnut = function (canvasId, labels, values) {
    const canvas = document.getElementById(canvasId);
    if (!canvas || typeof Chart === 'undefined') return;

    new Chart(canvas, {
      type: 'doughnut',
      data: {
        labels: labels,
        datasets: [{
          data: values,
          backgroundColor: ['#94a3b8', '#64748b', '#475569', '#cbd5e1'],
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
      },
    });
  };

  document.addEventListener('DOMContentLoaded', function () {
    const el = document.getElementById('statsChart');
    if (el && el.dataset.labels) {
      SMS.renderBarChart(
        'statsChart',
        JSON.parse(el.dataset.labels),
        JSON.parse(el.dataset.values),
        el.dataset.label || 'Total'
      );
    }

    const pie = document.getElementById('roleChart');
    if (pie && pie.dataset.labels) {
      SMS.renderDoughnut(
        'roleChart',
        JSON.parse(pie.dataset.labels),
        JSON.parse(pie.dataset.values)
      );
    }
  });
})();
