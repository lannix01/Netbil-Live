@php
$system = $system ?? [];
$webfigUrl = $webfigUrl ?? null;
@endphp

<div class="container py-3">

    {{-- SYSTEM INFO + CHARTS --}}
    <div class="row g-3 mb-4">
        {{-- SYSTEM INFO --}}
        <div class="col-md-4">
            <h6 class="mb-2">System Info</h6>
            <ul class="list-group small">
                <li class="list-group-item"><strong>Identity:</strong> <span id="identity">{{ $system['identity'] ?? '-' }}</span></li>
                <li class="list-group-item"><strong>Board:</strong> <span id="board">{{ $system['boardname'] ?? '-' }}</span></li>
                <li class="list-group-item"><strong>RouterOS:</strong> <span id="version">{{ $system['version'] ?? '-' }}</span></li>
                <li class="list-group-item"><strong>Uptime:</strong> <span id="uptime">{{ $system['uptime'] ?? '-' }}</span></li>
                <li class="list-group-item"><strong>CPU:</strong> <span id="cpuModel">{{ $system['cpu'] ?? '-' }}</span> (<span id="cpuLoad">{{ $system['cpuLoad'] ?? 0 }}</span>%)</li>
                <li class="list-group-item"><strong>Memory:</strong> <span id="memoryUsed">{{ ($system['totalMem'] ?? 0)-($system['freeMem'] ?? 0) }}</span> MB / <span id="memoryFree">{{ $system['freeMem'] ?? 0 }}</span> MB free</li>
            </ul>
        </div>

        {{-- ACTION BUTTONS + DONUTS --}}
        <div class="col-md-8 text-end">
            <div class="mb-3">
                @if($webfigUrl)
                    <a href="{{ $webfigUrl }}"
                       class="btn btn-primary btn-sm me-2"
                       target="_blank"
                       rel="noopener noreferrer">
                        WebFig
                    </a>
                @endif
                <button class="btn btn-dark btn-sm me-2" data-bs-toggle="modal" data-bs-target="#terminalModal">Terminal</button>
                <button class="btn btn-success btn-sm me-2" data-bs-toggle="modal" data-bs-target="#filesModal">Files</button>
            </div>
            <div class="row g-3 justify-content-end">
                <div class="col-md-4 text-center">
                    <div class="small fw-semibold mb-1">CPU</div>
                    <canvas id="cpuChart" height="120"></canvas>
                </div>
                <div class="col-md-4 text-center">
                    <div class="small fw-semibold mb-1">Memory</div>
                    <canvas id="memoryChart" height="120"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- TERMINAL MODAL --}}
<div class="modal fade" id="terminalModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content bg-dark text-light">
            <div class="modal-header">
                <h6 class="modal-title">MikroTik Terminal</h6>
                <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <pre id="terminalOutput" class="bg-black p-2 small" style="height:260px;overflow:auto"></pre>
                <input id="terminalInput" class="form-control form-control-sm bg-dark text-light mt-2" placeholder="/system resource print">
            </div>
        </div>
    </div>
</div>

{{-- FILES MODAL --}}
<div class="modal fade" id="filesModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">Hotspot Files</h6>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-0">
                <div class="col-3 border-end">
                    <ul id="fileList" class="list-group list-group-flush small"></ul>
                    <input type="file" id="uploadFileInput" class="form-control form-control-sm mt-2">
                </div>
                <div class="col-9 p-2">
                    <textarea id="fileEditor" class="form-control" style="height:260px;font-family:monospace"></textarea>
                    <div class="mt-2">
                        <button id="saveFile" class="btn btn-success btn-sm">Save</button>
                        <button id="addFile" class="btn btn-primary btn-sm">Add</button>
                        <button id="removeFile" class="btn btn-danger btn-sm">Remove</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- CHART.JS --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const csrf = '{{ csrf_token() }}';
const mikrotikApiUrl = @json(route('mikrotik.test', [], false));
let currentSystem = @json($system);

/* Donut center plugin */
Chart.register({
    id: 'centerText',
    afterDraw(chart){
        const { ctx, chartArea } = chart;
        const used = chart.data.datasets[0].data[0];
        const free = chart.data.datasets[0].data[1];
        ctx.save();
        ctx.font='bold 13px sans-serif';
        ctx.fillStyle='#212529';
        ctx.textAlign='center';
        ctx.textBaseline='middle';
        ctx.fillText(`Used: ${used}%`,(chartArea.left+chartArea.right)/2,(chartArea.top+chartArea.bottom)/2-8);
        ctx.fillText(`Free: ${free}%`,(chartArea.left+chartArea.right)/2,(chartArea.top+chartArea.bottom)/2+8);
        ctx.restore();
    }
});

/* CPU + Memory Donuts */
const cpuChart = new Chart(document.getElementById('cpuChart'),{
    type:'doughnut',
    data:{datasets:[{data:[currentSystem.cpuLoad ?? 0,100-(currentSystem.cpuLoad ?? 0)],backgroundColor:['#0d6efd','#e9ecef'],borderWidth:0}]},
    options:{cutout:'78%',plugins:{legend:{display:false}}}
});
function memPercent(sys){ return sys.totalMem ? Math.round(((sys.totalMem-sys.freeMem)/sys.totalMem)*100) : 0; }
const memoryChart = new Chart(document.getElementById('memoryChart'),{
    type:'doughnut',
    data:{datasets:[{data:[memPercent(currentSystem),100-memPercent(currentSystem)],backgroundColor:['#198754','#e9ecef'],borderWidth:0}]},
    options:{cutout:'78%',plugins:{legend:{display:false}}}
});

/* Live system refresh */
async function refreshSystem(){
    try{
        const res = await fetch(mikrotikApiUrl,{
            credentials: 'same-origin',
            headers:{'Accept':'application/json'}
        });
        const data = await res.json();
        if(!data.system) return;
        currentSystem=data.system;
        const usedMem=currentSystem.totalMem-currentSystem.freeMem;
        const memPct=memPercent(currentSystem);
        uptime.textContent=currentSystem.uptime;
        cpuLoad.textContent=currentSystem.cpuLoad;
        cpuModel.textContent=currentSystem.cpu;
        memoryUsed.textContent=usedMem;
        memoryFree.textContent=currentSystem.freeMem;
        cpuChart.data.datasets[0].data=[currentSystem.cpuLoad,100-currentSystem.cpuLoad]; cpuChart.update();
        memoryChart.data.datasets[0].data=[memPct,100-memPct]; memoryChart.update();
    }catch(e){console.warn('Live refresh skipped');}
}
setInterval(refreshSystem,2000);

/* TERMINAL WITH HISTORY */
let terminalHistory = [];
let historyIndex = -1;
terminalInput.addEventListener('keydown', async e=>{
    if(e.key==='ArrowUp'){ if(historyIndex>0) historyIndex--; terminalInput.value=terminalHistory[historyIndex]??''; e.preventDefault(); return; }
    if(e.key==='ArrowDown'){ if(historyIndex<terminalHistory.length-1) historyIndex++; terminalInput.value=terminalHistory[historyIndex]??''; e.preventDefault(); return; }
    if(e.key!=='Enter') return;

    const cmd = e.target.value.trim(); if(!cmd) return;
    terminalHistory.push(cmd); historyIndex=terminalHistory.length;
    e.target.value=''; terminalOutput.textContent+=`\n> ${cmd}\n`;

    const r = await fetch(mikrotikApiUrl,{
        method:'POST',
        credentials:'same-origin',
        headers:{
            'X-CSRF-TOKEN':csrf,
            'Accept':'application/json',
            'Content-Type':'application/json'
        },
        body:JSON.stringify({action:'terminal',command:cmd})
    });
    const j = await r.json();
    terminalOutput.textContent+=j.output+'\n';
    terminalOutput.scrollTop=terminalOutput.scrollHeight;
});

/* FILES */
filesModal.addEventListener('shown.bs.modal', loadFileList);

async function loadFileList(){
    const r = await fetch(mikrotikApiUrl,{
        method:'POST',
        credentials:'same-origin',
        headers:{
            'X-CSRF-TOKEN':csrf,
            'Accept':'application/json',
            'Content-Type':'application/json'
        },
        body:JSON.stringify({action:'files'})
    });
    const j = await r.json();
    fileList.innerHTML='';
    j.files.forEach(f=>{
        const li=document.createElement('li');
        li.className='list-group-item';
        li.textContent=f;
        li.onclick=()=>loadFile(f);
        fileList.appendChild(li);
    });
}

async function loadFile(name){
    const r = await fetch(mikrotikApiUrl,{
        method:'POST',
        credentials:'same-origin',
        headers:{
            'X-CSRF-TOKEN':csrf,
            'Accept':'application/json',
            'Content-Type':'application/json'
        },
        body:JSON.stringify({action:'getFile',name})
    });
    const j = await r.json();
    fileEditor.value=j.content;
    saveFile.onclick=()=>saveFileContent(name);
    removeFile.onclick=()=>removeFileContent(name);
}

async function saveFileContent(name){
    await fetch(mikrotikApiUrl,{
        method:'POST',
        credentials:'same-origin',
        headers:{
            'X-CSRF-TOKEN':csrf,
            'Accept':'application/json',
            'Content-Type':'application/json'
        },
        body:JSON.stringify({action:'saveFile',name,content:fileEditor.value})
    });
}

async function removeFileContent(name){
    if(!confirm(`Delete file ${name}?`)) return;
    await fetch(mikrotikApiUrl,{
        method:'POST',
        credentials:'same-origin',
        headers:{
            'X-CSRF-TOKEN':csrf,
            'Accept':'application/json',
            'Content-Type':'application/json'
        },
        body:JSON.stringify({action:'removeFile',name})
    });
    fileEditor.value=''; loadFileList();
}

/* FILE UPLOAD */
uploadFileInput.addEventListener('change', async e=>{
    const file = e.target.files[0];
    if(!file) return;
    const content = await file.text();
    const name = file.name;
    await fetch(mikrotikApiUrl,{
        method:'POST',
        credentials:'same-origin',
        headers:{
            'X-CSRF-TOKEN':csrf,
            'Accept':'application/json',
            'Content-Type':'application/json'
        },
        body:JSON.stringify({action:'addFile',name,content})
    });
    e.target.value='';
    loadFileList();
});

addFile.addEventListener('click', async ()=>{
    const name = prompt('New file name:');
    if(!name) return;
    await fetch(mikrotikApiUrl,{
        method:'POST',
        credentials:'same-origin',
        headers:{
            'X-CSRF-TOKEN':csrf,
            'Accept':'application/json',
            'Content-Type':'application/json'
        },
        body:JSON.stringify({action:'addFile',name,content:''})
    });
    loadFileList();
});
</script>
