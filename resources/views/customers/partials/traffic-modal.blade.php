<!-- Traffic Modal -->
<div class="modal fade" id="trafficModal" tabindex="-1" aria-labelledby="trafficModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="trafficModalLabel">User Traffic Details</h5>
                    <div class="small text-white-50" id="trafficModalSubtitle">Session delta with router assist, sampled every 1 second</div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-3">
                <!-- Loader while fetching data -->
                <div class="d-flex justify-content-center align-items-center py-5" id="trafficModalLoader">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>

                <!-- Traffic content goes here -->
                <div id="trafficModalContent" style="display: none;">
                    <div class="traffic-chart-shell mb-3">
                        <div class="traffic-chart-head">
                            <div>
                                <div class="traffic-chart-kicker" id="trafficChartKicker">Live Session Telemetry</div>
                                <div class="traffic-chart-title" id="trafficChartTitle">Live Throughput</div>
                            </div>
                            <div class="traffic-source-badge" id="trafficSourceBadge" data-state="warmup">
                                <span class="dot"></span>
                                <span class="label">Calibrating</span>
                            </div>
                        </div>
                        <div class="traffic-chart-wrap">
                            <canvas id="trafficLiveChart"></canvas>
                        </div>
                        <div class="traffic-chart-foot">
                            <div class="small" id="trafficChartHint">Waiting for the second sample...</div>
                            <div class="traffic-live-stats">
                                <span class="traffic-live-pill tx">TX <strong id="trafficCurrentTx">Sampling...</strong></span>
                                <span class="traffic-live-pill rx">RX <strong id="trafficCurrentRx">Sampling...</strong></span>
                                <span class="traffic-live-pill avg">AVG <strong id="trafficCurrentAvg">0 bps</strong></span>
                                <span class="traffic-live-pill peak">PEAK <strong id="trafficCurrentPeak">0 bps</strong></span>
                            </div>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-3">
                            <div class="traffic-stat-card">
                                <div class="small text-muted">Status</div>
                                <div class="fw-bold" id="trafficStatusText">-</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="traffic-stat-card">
                                <div class="small text-muted">Session Usage</div>
                                <div class="fw-bold" id="trafficSessionUsage">0 B</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="traffic-stat-card">
                                <div class="small text-muted">Total User Usage</div>
                                <div class="fw-bold" id="trafficTotalUsage">0 B</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="traffic-stat-card">
                                <div class="small text-muted">IP / MAC</div>
                                <div class="fw-bold small" id="trafficIdentity">-</div>
                            </div>
                        </div>
                    </div>
                    <table class="table table-striped table-bordered display nowrap" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>Username</th>
                                <th>Interface</th>
                                <th>Source</th>
                                <th>TX Rate</th>
                                <th>RX Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- AJAX content inserted here -->
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
