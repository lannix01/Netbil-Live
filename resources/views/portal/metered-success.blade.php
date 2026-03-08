@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-body text-center">
                    <div class="alert alert-success" role="alert">
                        <h4 class="alert-heading">
                            <i class="fas fa-check-circle"></i> Connection Successful
                        </h4>
                    </div>

                    <div class="service-info mt-4 mb-4">
                        <p class="text-muted mb-3">Service Started</p>
                        <h5 class="font-weight-bold">
                            {{ $connection->service_start_time ?? now()->format('M d, Y H:i:s') }}
                        </h5>
                        <small class="text-secondary">
                            Connection established at {{ now()->diffForHumans() }}
                        </small>
                    </div>

                    <hr>

                    <div class="mt-4">
                        <button class="btn btn-primary" data-toggle="modal" data-target="#speedTestModal">
                            <i class="fas fa-tachometer-alt"></i> Check Internet Speed
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Speed Test Modal -->
<div class="modal fade" id="speedTestModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Speed Test</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <div id="speedTestResults">
                    <p class="text-muted mb-4">Running speed test...</p>
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Testing...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="retestBtn" style="display:none;" onclick="runSpeedTest()">
                    Retest
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function runSpeedTest() {
    document.getElementById('speedTestResults').innerHTML = '<p class="text-muted mb-4">Running speed test...</p><div class="spinner-border text-primary" role="status"><span class="sr-only">Testing...</span></div>';
    document.getElementById('retestBtn').style.display = 'none';
    
    fetch('/api/speed-test')
        .then(response => response.json())
        .then(data => {
            displaySpeedResults(data);
        })
        .catch(error => {
            document.getElementById('speedTestResults').innerHTML = '<p class="text-danger">Error testing connection</p>';
        });
}

function displaySpeedResults(data) {
    const html = `
        <div class="speed-results">
            <h6 class="mb-3">Results</h6>
            <div class="mb-3">
                <small class="text-muted">Download Speed</small>
                <h5 class="text-success">${data.download.toFixed(2)} Mbps</h5>
            </div>
            <div class="mb-3">
                <small class="text-muted">Upload Speed</small>
                <h5 class="text-success">${data.upload.toFixed(2)} Mbps</h5>
            </div>
            <div>
                <small class="text-muted">Latency</small>
                <h5 class="text-success">${data.latency} ms</h5>
            </div>
        </div>
    `;
    document.getElementById('speedTestResults').innerHTML = html;
    document.getElementById('retestBtn').style.display = 'block';
}

document.getElementById('speedTestModal').addEventListener('show.bs.modal', function() {
    runSpeedTest();
});
</script>
@endsection