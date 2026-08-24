/* ============================================================
   PhoneVault – dashboard.js (Dashboard Page Analytics & Script)
   ============================================================ */

document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    /* ── Dynamic Greeting Sub-title ── */
    const pageSubtitle = document.querySelector('.pv-page-subtitle');
    if (pageSubtitle) {
        const hour = new Date().getHours();
        let greeting = 'Welcome back!';
        if (hour < 12) greeting = 'Good morning!';
        else if (hour < 18) greeting = 'Good afternoon!';
        else greeting = 'Good evening!';
        
        const dateStrEl = pageSubtitle.querySelector('.pv-date-str');
        const dateStr = dateStrEl ? dateStrEl.textContent : '';
        
        pageSubtitle.innerHTML =
            '<span class="pv-greeting-badge">' + greeting + '</span> ' +
            '<span class="pv-header-sep">&mdash;</span> ' +
            '<i class="fa-regular fa-calendar me-1"></i><span>' + dateStr + '</span>';
    }

    /* ── Stat Cards Entry Animation ── */
    const statCards = document.querySelectorAll('.pv-stat-card');
    statCards.forEach(function (card, idx) {
        card.style.opacity = '0';
        card.style.transform = 'translateY(12px)';
        card.style.transition = 'all 0.4s ease ' + (idx * 0.08) + 's';
        setTimeout(function () {
            card.style.opacity = '1';
            card.style.transform = 'none';
        }, 50);
    });

    /* ── Chart.js Analytics Initialization ── */
    if (typeof Chart === 'undefined' || !window.PV_ANALYTICS) return;

    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const textColor = isDark ? '#94a3b8' : '#64748b';
    const gridColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';
    const currency  = window.PV_ANALYTICS.currencySymbol || '₱';

    // 1. Sales & Revenue Trend Chart (Area Line Chart)
    const trendCtx = document.getElementById('salesTrendChart');
    if (trendCtx) {
        const ctx = trendCtx.getContext('2d');
        const gradient = ctx.createLinearGradient(0, 0, 0, 260);
        gradient.addColorStop(0, 'rgba(99, 102, 241, 0.35)');
        gradient.addColorStop(1, 'rgba(99, 102, 241, 0.0)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: window.PV_ANALYTICS.trendLabels.length > 0 ? window.PV_ANALYTICS.trendLabels : ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Revenue (' + currency + ')',
                    data: window.PV_ANALYTICS.trendTotals.length > 0 ? window.PV_ANALYTICS.trendTotals : [0, 0, 0, 0, 0, 0, 0],
                    borderColor: '#6366f1',
                    backgroundColor: gradient,
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#6366f1',
                    pointHoverRadius: 6,
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                return ' Revenue: ' + currency + parseFloat(ctx.raw).toLocaleString('en-US', { minimumFractionDigits: 2 });
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { color: gridColor },
                        ticks: { color: textColor, font: { family: 'Inter', size: 11 } }
                    },
                    y: {
                        grid: { color: gridColor },
                        ticks: {
                            color: textColor,
                            font: { family: 'Inter', size: 11 },
                            callback: function (val) {
                                return currency + val;
                            }
                        }
                    }
                }
            }
        });
    }

    // 2. Inventory Brand Distribution (Doughnut Chart)
    const brandCtx = document.getElementById('brandChart');
    if (brandCtx) {
        new Chart(brandCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: window.PV_ANALYTICS.brandLabels.length > 0 ? window.PV_ANALYTICS.brandLabels : ['Apple', 'Samsung', 'Google', 'Other'],
                datasets: [{
                    data: window.PV_ANALYTICS.brandCounts.length > 0 ? window.PV_ANALYTICS.brandCounts : [1, 1, 1, 1],
                    backgroundColor: [
                        '#6366f1', // Indigo
                        '#06b6d4', // Cyan
                        '#10b981', // Emerald
                        '#f59e0b', // Amber
                        '#ec4899', // Pink
                        '#8b5cf6'  // Purple
                    ],
                    borderWidth: 2,
                    borderColor: isDark ? '#131c2e' : '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: textColor,
                            font: { family: 'Inter', size: 11 },
                            padding: 15,
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    }
                },
                cutout: '70%'
            }
        });
    }
});
