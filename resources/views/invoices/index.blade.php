@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<div class="container-fluid mt-4">
    <h2>Invoices & Billing</h2>

    <div class="card">
        <div class="card-body">
            <table class="table" id="usersTable">
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Invoices</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal for Invoice, Payments, STK -->
<div class="modal fade" id="billingModal" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Billing Details - <span id="modalUserName"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <h6>Invoices</h6>
        <table class="table table-bordered" id="invoiceTable">
          <thead>
            <tr><th>#</th><th>Amount</th><th>Status</th><th>Issued</th></tr>
          </thead>
          <tbody></tbody>
        </table>

        <h6 class="mt-4">Payments</h6>
        <table class="table table-bordered" id="paymentTable">
          <thead>
            <tr><th>#</th><th>Amount</th><th>Method</th><th>Date</th></tr>
          </thead>
          <tbody></tbody>
        </table>

        <h6 class="mt-4">STK Push History</h6>
        <table class="table table-bordered" id="stkTable">
          <thead>
            <tr><th>#</th><th>Phone</th><th>Amount</th><th>Status</th><th>Time</th></tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
$(function () {
    let usersTable = $('#usersTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '/api/invoices/users',
        columns: [
            { data: 'id' },
            { data: 'name' },
            { data: 'phone' },
            { data: 'email' },
            { data: 'status' },
            {
                data: null,
                render: function (data) {
                    return `<button class="btn btn-sm btn-primary view-btn" data-id="${data.id}" data-name="${data.name}">View</button>`;
                }
            }
        ]
    });

    $('#usersTable tbody').on('click', '.view-btn', function () {
        const userId = $(this).data('id');
        const userName = $(this).data('name');
        $('#modalUserName').text(userName);

        // Load invoice data
        $.get(`/api/invoices/${userId}`, function (res) {
            let invoiceRows = '';
            res.invoices.forEach((inv, i) => {
                invoiceRows += `<tr><td>${i + 1}</td><td>${inv.amount}</td><td>${inv.status}</td><td>${inv.created_at}</td></tr>`;
            });
            $('#invoiceTable tbody').html(invoiceRows);

            let paymentRows = '';
            res.payments.forEach((pay, i) => {
                paymentRows += `<tr><td>${i + 1}</td><td>${pay.amount}</td><td>${pay.method}</td><td>${pay.created_at}</td></tr>`;
            });
            $('#paymentTable tbody').html(paymentRows);

            let stkRows = '';
            res.stk.forEach((stk, i) => {
                stkRows += `<tr><td>${i + 1}</td><td>${stk.phone}</td><td>${stk.amount}</td><td>${stk.status}</td><td>${stk.created_at}</td></tr>`;
            });
            $('#stkTable tbody').html(stkRows);

            $('#billingModal').modal('show');
        });
    });
});
</script>
@endsection
