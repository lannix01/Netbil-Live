@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4">
    <h2 class="mb-4 text-primary text-center fw-bold">MikroTik Management</h2>

    <div class="row">
        <!-- LEFT: System Info -->
        <div class="col-md-4">
            @if($connected)
                <div class="alert alert-success">
                     Successfully connected to MikroTik at <strong>{{ $host }}</strong>
                </div>

                <h4 class="mt-4"> System Status</h4>
                <ul class="list-group mb-3">
                    <li class="list-group-item"><strong>Identity:</strong> {{ $system['identity'] }}</li>
                    <li class="list-group-item"><strong>Board:</strong> {{ $system['boardname'] }}</li>
                    <li class="list-group-item"><strong>RouterOS Version:</strong> {{ $system['version'] }}</li>
                    <li class="list-group-item"><strong>Uptime:</strong> {{ $system['uptime'] }}</li>
                    <li class="list-group-item"><strong>CPU:</strong> {{ $system['cpu'] }} ({{ $system['cpuLoad'] }}% load)</li>
                    <li class="list-group-item"><strong>Memory:</strong> {{ $system['totalMem'] }} MB total / {{ $system['freeMem'] }} MB free</li>
                </ul>
            @else
                <div class="alert alert-danger">
                     Failed to connect to MikroTik at <strong>{{ $host }}</strong>
                </div>
            @endif
        </div>

        <!-- RIGHT: Terminal -->
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white">
                    <i class="bi bi-terminal"></i> Terminal
                </div>
                <div class="card-body p-0">
                    <div id="terminal" style="height:70vh; background:#000;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- xterm.js -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/xterm/css/xterm.css" />
<script src="https://cdn.jsdelivr.net/npm/xterm/lib/xterm.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const term = new Terminal({
        cursorBlink: true,
        scrollback: 2000,
        fontFamily: 'monospace',
        fontSize: 14,
    });

    term.open(document.getElementById('terminal'));
    term.writeln('Connected to MikroTik. Type your commands below:\r\n');

    // Command history
    let commandBuffer = '';
    let history = [];
    let historyIndex = -1;

    term.write('> ');

    term.onKey(e => {
        const char = e.key;
        const ev = e.domEvent;

        if (ev.key === "Enter") {
            term.writeln('');
            const command = commandBuffer.trim();
            if (command.length === 0) {
                term.write('> ');
                commandBuffer = '';
                return;
            }

            // Save history
            history.push(command);
            historyIndex = history.length;

            // Send command to backend
            fetch('{{ route("terminal.run") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ command })
            })
            .then(res => res.json())
            .then(res => {
                if(res.output && res.output.length > 0){
                    res.output.forEach(line => term.writeln(line));
                }
                term.write('> ');
            })
            .catch(err => {
                term.writeln(' Error executing command: ' + err);
                term.write('> ');
            });

            commandBuffer = '';
        } else if (ev.key === "Backspace") {
            if (commandBuffer.length > 0) {
                commandBuffer = commandBuffer.slice(0, -1);
                term.write('\b \b');
            }
        } else if (ev.key === "ArrowUp") {
            if (historyIndex > 0) {
                commandBuffer = history[--historyIndex];
                term.write('\r\x1b[K> ' + commandBuffer);
            }
        } else if (ev.key === "ArrowDown") {
            if (historyIndex < history.length - 1) {
                commandBuffer = history[++historyIndex];
                term.write('\r\x1b[K> ' + commandBuffer);
            } else {
                historyIndex = history.length;
                commandBuffer = '';
                term.write('\r\x1b[K> ');
            }
        } else if (char.length === 1) {
            commandBuffer += char;
            term.write(char);
        }
    });
});
</script>
@endsection
