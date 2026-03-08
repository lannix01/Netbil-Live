<?php

namespace App\Modules\Inventory\Http\Controllers\Inventory;

use App\Modules\Inventory\Http\Controllers\Controller;
use App\Modules\Inventory\Models\InventoryLog;
use Illuminate\Http\Request;

class LogController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string)$request->get('q', ''));
        $action = trim((string)$request->get('action', ''));

        $logs = InventoryLog::query()
            ->with(['item', 'unit', 'fromUser', 'toUser', 'creator'])
            ->when($action !== '', function ($query) use ($action) {
                $query->where('action', $action);
            })
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('serial_no', 'like', "%{$q}%")
                        ->orWhere('site_name', 'like', "%{$q}%")
                        ->orWhere('site_code', 'like', "%{$q}%")
                        ->orWhere('reference', 'like', "%{$q}%")
                        ->orWhereHas('item', function ($itemQ) use ($q) {
                            $itemQ->where('name', 'like', "%{$q}%")
                                  ->orWhere('sku', 'like', "%{$q}%");
                        })
                        ->orWhereHas('fromUser', function ($uQ) use ($q) {
                            $uQ->where('name', 'like', "%{$q}%");
                        })
                        ->orWhereHas('toUser', function ($uQ) use ($q) {
                            $uQ->where('name', 'like', "%{$q}%");
                        })
                        ->orWhereHas('creator', function ($uQ) use ($q) {
                            $uQ->where('name', 'like', "%{$q}%");
                        });
                });
            })
            ->latest()
            ->paginate(50)
            ->withQueryString();

        return view('inventory::logs.index', compact('logs', 'q', 'action'));
    }
}
