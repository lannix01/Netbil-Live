import './bootstrap';
import 'bootstrap/dist/css/bootstrap.min.css';

import Alpine from 'alpinejs';
window.Alpine = Alpine;
Alpine.start();

import axios from 'axios';
import Chart from 'chart.js/auto';

document.addEventListener('DOMContentLoaded', () => {
    const interfaceHistory = {};

    function formatBits(bits) {
        bits = parseInt(bits);
        if (bits >= 1e9) return (bits / 1e9).toFixed(2) + ' Gbps';
        if (bits >= 1e6) return (bits / 1e6).toFixed(2) + ' Mbps';
        if (bits >= 1e3) return (bits / 1e3).toFixed(2) + ' Kbps';
        return bits + ' bps';
    }

    function updateDashboard() {
        axios.get('/metrics/fetch')
            .then(({ data }) => {
                if (data.error) return console.error(data.error);

                // System stats
                document.getElementById('ram-usage').innerText = `RAM Usage: ${data.system.ram_usage} (${data.system.ram_percentage}%)`;
                document.querySelector('.progress-bar.ram').style.width = `${data.system.ram_percentage}%`;

                document.getElementById('cpu-usage').innerText = `CPU Usage: ${data.system.cpu_usage}%`;
                document.querySelector('.progress-bar.cpu').style.width = `${data.system.cpu_usage}%`;

                document.getElementById('hdd-usage').innerText = `Storage Usage: ${data.system.storage_usage} (${data.system.hdd_percentage}%)`;
                document.querySelector('.progress-bar.hdd').style.width = `${data.system.hdd_percentage}%`;

                document.getElementById('uptime').innerText = `Uptime: ${data.system.uptime}`;

                // Interface table
                const tbody = document.querySelector('#traffic-table tbody');
                tbody.innerHTML = '';
                data.interface_traffic.forEach(iface => {
                    const tr = document.createElement('tr');
                    tr.classList.add('interface-row');
                    tr.setAttribute('data-name', iface.name);
                    tr.setAttribute('data-rx', iface.rx);
                    tr.setAttribute('data-tx', iface.tx);
                    tr.innerHTML = `
                        <td>${iface.name}</td>
                        <td>${formatBits(iface.rx)}</td>
                        <td>${formatBits(iface.tx)}</td>
                        <td>
                            <span class="badge ${iface.status === 'up' ? 'bg-success' : 'bg-danger'}">
                                ${iface.status === 'up' ? 'Tx/Rx Up' : 'Tx/Rx Down'}
                            </span>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });

                document.querySelectorAll('.interface-row').forEach(row => {
                    row.addEventListener('click', () => {
                        const name = row.getAttribute('data-name');
                        const rx = parseInt(row.getAttribute('data-rx'));
                        const tx = parseInt(row.getAttribute('data-tx'));
                        if (!interfaceHistory[name]) {
                            interfaceHistory[name] = { timestamps: [], rx: [], tx: [] };
                        }
                        const now = new Date().toLocaleTimeString();
                        interfaceHistory[name].timestamps.push(now);
                        interfaceHistory[name].rx.push(rx);
                        interfaceHistory[name].tx.push(tx);
                        if (interfaceHistory[name].rx.length > 10) {
                            interfaceHistory[name].timestamps.shift();
                            interfaceHistory[name].rx.shift();
                            interfaceHistory[name].tx.shift();
                        }
                        showTrafficModal(name, interfaceHistory[name]);
                    });
                });

                // Uplink graph
                if (data.uplink && data.uplink.interface) {
                    const name = data.uplink.interface;
                    const rx = data.uplink.rx;
                    const tx = data.uplink.tx;

                                        const container = document.getElementById('uplink-graph-container');
                    const graphName = document.getElementById('uplink-name');
                    const instructions = document.getElementById('uplink-instructions');

                    if (!interfaceHistory[name]) {
                        interfaceHistory[name] = { timestamps: [], rx: [], tx: [] };
                    }

                    const now = new Date().toLocaleTimeString();
                    interfaceHistory[name].timestamps.push(now);
                    interfaceHistory[name].rx.push(rx);
                    interfaceHistory[name].tx.push(tx);

                    if (interfaceHistory[name].rx.length > 10) {
                        interfaceHistory[name].timestamps.shift();
                        interfaceHistory[name].rx.shift();
                        interfaceHistory[name].tx.shift();
                    }

                    if (container && graphName) {
                        graphName.innerText = name;
                        container.style.display = 'block';
                        if (instructions) instructions.style.display = 'none';
                        drawUplinkChart(interfaceHistory[name]);
                    }
                } else {
                    const container = document.getElementById('uplink-graph-container');
                    const instructions = document.getElementById('uplink-instructions');
                    if (container) container.style.display = 'none';
                    if (instructions) instructions.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Dashboard fetch error:', error);
            });
    }

    function drawUplinkChart(history) {
        const ctx = document.getElementById('uplinkChart')?.getContext('2d');
        if (!ctx) return;

        if (window.uplinkChart) window.uplinkChart.destroy();

        window.uplinkChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: history.timestamps,
                datasets: [
                    {
                        label: 'Download (RX)',
                        data: history.rx,
                        borderColor: '#0d6efd',
                        backgroundColor: 'rgba(13,110,253,0.2)',
                        tension: 0.2
                    },
                    {
                        label: 'Upload (TX)',
                        data: history.tx,
                        borderColor: '#fd7e14',
                        backgroundColor: 'rgba(253,126,20,0.2)',
                        tension: 0.2
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    function showTrafficModal(name, history) {
        const ctx = document.getElementById('trafficChart')?.getContext('2d');
        if (!ctx) return;

        document.getElementById('trafficModalLabel').innerText = `${name} Traffic`;

        if (window.trafficChart) window.trafficChart.destroy();

        window.trafficChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: history.timestamps,
                datasets: [
                    {
                        label: 'Download (RX)',
                        data: history.rx,
                        borderColor: 'green',
                        backgroundColor: 'rgba(0,255,0,0.1)',
                        tension: 0.3
                    },
                    {
                        label: 'Upload (TX)',
                        data: history.tx,
                        borderColor: 'red',
                        backgroundColor: 'rgba(255,0,0,0.1)',
                        tension: 0.3
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        const modal = new bootstrap.Modal(document.getElementById('trafficModal'));
        modal.show();
    }

    // Initial call
    updateDashboard();
    setInterval(updateDashboard, 5000); // refresh every 5s
});
