<div class="card shadow-sm p-3">
    <h4 class="mb-3">{{ $title }}</h4>

    <div class="table-responsive">
        <table id="{{ $id }}" class="table table-striped table-bordered nowrap" width="100%">
            <thead class="table-dark">
                <tr>
                    @foreach ($headers as $header)
                        <th>{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                {{ $slot }}
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    new DataTable("#{{ $id }}", {
        responsive: true,
        pageLength: 10,
        lengthMenu: [5, 10, 25, 50],
    });
});
</script>
