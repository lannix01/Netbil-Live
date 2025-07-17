<?php



namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\StkPushLog;
use Yajra\DataTables\Facades\DataTables;



class InvoiceController extends Controller
{
    public function index() {
        return view('invoices.index');
    }

    public function users(Request $request)
    {
        $query = User::query();

        return DataTables::of($query)

            ->editColumn('status', fn($row) => $row->status === 'active' ? 'Active' : 'Inactive')
            ->make(true);
    }

    public function userDetails($userId)
    {
        return response()->json([
            'invoices' => Invoice::where('user_id', $userId)->latest()->get(),
            'payments' => Payment::where('user_id', $userId)->latest()->get(),
            'stk' => StkPushLog::where('user_id', $userId)->latest()->get(),
        ]);
    }
}
