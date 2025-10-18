/**
 * MindDB Reports - Chart.js Visualization
 * 
 * Vanilla JavaScript for rendering charts on report pages.
 * Uses Chart.js library for data visualization.
 * 
 * @version 1.0.0
 * @requires Chart.js 4.4.0+
 */

(function() {
    'use strict';

    // Check if Chart.js is loaded
    if (typeof Chart === 'undefined') {
        console.error('Chart.js library not loaded. Charts will not be rendered.');
        showChartError();
        return;
    }

    /**
     * Show error message if Chart.js fails to load
     */
    function showChartError() {
        const chartContainers = document.querySelectorAll('.chart-container');
        chartContainers.forEach(container => {
            container.innerHTML = `
                <div style="padding: 40px; text-align: center; background: #fee2e2; border-radius: 8px;">
                    <p style="color: #991b1b; font-weight: 600;">⚠️ Chart.js library failed to load</p>
                    <p style="color: #666; margin-top: 10px;">Please check your internet connection or view the data table below.</p>
                </div>
            `;
        });
    }

    /**
     * Initialize charts based on page context
     */
    function initializeCharts() {
        // Weekly Mood Report Charts
        if (typeof weeklyData !== 'undefined' && weeklyData.length > 0) {
            renderMoodTrendChart(weeklyData);
            renderMoodDistributionChart(weeklyData);
        }

        // Top Mood Tags Report Charts
        if (typeof tagData !== 'undefined' && tagData.length > 0) {
            renderTagFrequencyChart(tagData);
            renderTagMoodChart(tagData);
        }
    }

    /**
     * Render Mood Trend Line Chart
     * @param {Array} data Weekly mood data
     */
    function renderMoodTrendChart(data) {
        const canvas = document.getElementById('moodTrendChart');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');

        // Prepare data
        const labels = data.map(week => {
            const date = new Date(week.week_start_date);
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        });

        const avgMoodData = data.map(week => parseFloat(week.avg_mood));
        const minMoodData = data.map(week => parseFloat(week.min_mood));
        const maxMoodData = data.map(week => parseFloat(week.max_mood));

        // Create chart
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Average Mood',
                        data: avgMoodData,
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        pointBackgroundColor: '#667eea',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2
                    },
                    {
                        label: 'Min Mood',
                        data: minMoodData,
                        borderColor: '#f87171',
                        backgroundColor: 'transparent',
                        borderWidth: 2,
                        borderDash: [5, 5],
                        fill: false,
                        tension: 0.4,
                        pointRadius: 3,
                        pointHoverRadius: 5
                    },
                    {
                        label: 'Max Mood',
                        data: maxMoodData,
                        borderColor: '#34d399',
                        backgroundColor: 'transparent',
                        borderWidth: 2,
                        borderDash: [5, 5],
                        fill: false,
                        tension: 0.4,
                        pointRadius: 3,
                        pointHoverRadius: 5
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: false
                    },
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            font: {
                                size: 14,
                                family: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto'
                            },
                            padding: 15,
                            usePointStyle: true
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        titleFont: {
                            size: 14,
                            weight: 'bold'
                        },
                        bodyFont: {
                            size: 13
                        },
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.parsed.y.toFixed(2) + '/10';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 10,
                        ticks: {
                            stepSize: 1,
                            callback: function(value) {
                                return value + '/10';
                            },
                            font: {
                                size: 12
                            }
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                size: 12
                            }
                        },
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    /**
     * Render Mood Distribution Doughnut Chart
     * @param {Array} data Weekly mood data
     */
    function renderMoodDistributionChart(data) {
        const canvas = document.getElementById('moodDistributionChart');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');

        // Use latest week data
        const latestWeek = data[data.length - 1];

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Low Mood (1-3)', 'Medium Mood (4-7)', 'High Mood (8-10)'],
                datasets: [{
                    data: [
                        parseFloat(latestWeek.pct_low_mood),
                        parseFloat(latestWeek.pct_medium_mood),
                        parseFloat(latestWeek.pct_high_mood)
                    ],
                    backgroundColor: [
                        'rgba(248, 113, 113, 0.8)',  // Red for low
                        'rgba(251, 191, 36, 0.8)',   // Yellow for medium
                        'rgba(52, 211, 153, 0.8)'    // Green for high
                    ],
                    borderColor: [
                        'rgba(248, 113, 113, 1)',
                        'rgba(251, 191, 36, 1)',
                        'rgba(52, 211, 153, 1)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: {
                            font: {
                                size: 13,
                                family: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto'
                            },
                            padding: 15,
                            usePointStyle: true
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + context.parsed.toFixed(1) + '%';
                            }
                        }
                    }
                }
            }
        });
    }

    /**
     * Render Tag Frequency Bar Chart
     * @param {Array} data Tag frequency data
     */
    function renderTagFrequencyChart(data) {
        const canvas = document.getElementById('tagFrequencyChart');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');

        // Prepare data
        const labels = data.map(tag => tag.tag);
        const frequencies = data.map(tag => parseInt(tag.frequency));

        // Generate gradient colors
        const colors = frequencies.map((freq, index) => {
            const hue = 280 + (index * 15); // Purple to pink gradient
            return `hsla(${hue}, 70%, 60%, 0.8)`;
        });

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Frequency',
                    data: frequencies,
                    backgroundColor: colors,
                    borderColor: colors.map(color => color.replace('0.8', '1')),
                    borderWidth: 2,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        titleFont: {
                            size: 14,
                            weight: 'bold'
                        },
                        bodyFont: {
                            size: 13
                        },
                        callbacks: {
                            label: function(context) {
                                const tag = data[context.dataIndex];
                                return [
                                    'Uses: ' + context.parsed.y,
                                    'Percentage: ' + tag.percentage + '%',
                                    'Avg Mood: ' + tag.avg_mood_with_tag + '/10'
                                ];
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            font: {
                                size: 12
                            }
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                size: 11
                            },
                            maxRotation: 45,
                            minRotation: 45
                        },
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    /**
     * Render Tag Average Mood Horizontal Bar Chart
     * @param {Array} data Tag frequency data
     */
    function renderTagMoodChart(data) {
        const canvas = document.getElementById('tagMoodChart');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');

        // Sort by avg_mood_with_tag descending
        const sortedData = [...data].sort((a, b) => 
            parseFloat(b.avg_mood_with_tag) - parseFloat(a.avg_mood_with_tag)
        ).slice(0, 10); // Top 10

        // Prepare data
        const labels = sortedData.map(tag => tag.tag);
        const avgMoods = sortedData.map(tag => parseFloat(tag.avg_mood_with_tag));

        // Color based on mood level
        const colors = avgMoods.map(mood => {
            if (mood >= 8) return 'rgba(52, 211, 153, 0.8)';  // Green for high
            if (mood >= 4) return 'rgba(251, 191, 36, 0.8)';  // Yellow for medium
            return 'rgba(248, 113, 113, 0.8)';                // Red for low
        });

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Average Mood',
                    data: avgMoods,
                    backgroundColor: colors,
                    borderColor: colors.map(color => color.replace('0.8', '1')),
                    borderWidth: 2,
                    borderRadius: 6
                }]
            },
            options: {
                indexAxis: 'y', // Horizontal bar chart
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        callbacks: {
                            label: function(context) {
                                return 'Average Mood: ' + context.parsed.x.toFixed(2) + '/10';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        max: 10,
                        ticks: {
                            stepSize: 1,
                            callback: function(value) {
                                return value + '/10';
                            },
                            font: {
                                size: 12
                            }
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    y: {
                        ticks: {
                            font: {
                                size: 12
                            }
                        },
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    /**
     * Fallback: Show data table if charts fail
     */
    function ensureDataTablesExist() {
        const chartContainers = document.querySelectorAll('.chart-container canvas');
        
        chartContainers.forEach(canvas => {
            if (!canvas.getContext) {
                const container = canvas.parentElement;
                container.innerHTML = `
                    <div style="padding: 20px; background: #f9fafb; border-radius: 8px; text-align: center;">
                        <p style="color: #666;">📊 Chart not available. Please view the data table below.</p>
                    </div>
                `;
            }
        });
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeCharts);
    } else {
        initializeCharts();
    }

    // Fallback check
    setTimeout(ensureDataTablesExist, 2000);

})();
