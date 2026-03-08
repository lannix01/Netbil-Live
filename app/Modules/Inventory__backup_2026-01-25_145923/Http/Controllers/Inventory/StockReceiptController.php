<?php

namespace Modules\Inventory\Http\Controllers\Inventory;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Inventory\Models\InventoryLog;
use Modules\Inventory\Models\Item;
use Modules\Inventory\Models\ItemUnit;
use Modules\Inventory\Models\StockReceipt;
use Modules\Inventory\Models\StockReceiptLine;

class StockReceiptController extends Controller
{
    public function index()
    {
        $receipts = StockReceipt::query()
            ->with('receiver')
            ->latest()
            ->paginate(20);

        return view('inventory::receipts.index', compact('receipts'));
    }

    public function create()
    {
        $items = Item::query()->where('is_active', true)->orderBy('name')->get();
        return view('inventory::receipts.create', compact('items'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'reference' => ['nullable','string','max:255','unique:inventory_stock_receipts,reference'],
            'received_date' => ['required','date'],
            'supplier_name' => ['nullable','string','max:255'],
            'notes' => ['nullable','string'],

            'lines' => ['required','array','min:1'],
            'lines.*.item_id' => ['required','exists:inventory_items,id'],
            'lines.*.qty_received' => ['required','integer','min:1'],

            // For serialized items, send serials as an array per line:
            // lines.*.serials = ["SN1","SN2"...] and must match qty_received
            'lines.*.serials' => ['nullable','array'],
            'lines.*.serials.*' => ['string','max:255'],
        ]);

        $reference = $data['reference'] ?? ('GRN-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6)));

        DB::transaction(function () use ($data, $reference) {
            $receipt = StockReceipt::create([
                'reference' => $reference,
                'received_date' => $data['received_date'],
                'supplier_name' => $data['supplier_name'] ?? null,
                'notes' => $data['notes'] ?? null,
                'received_by' => auth()->id(),
            ]);

            foreach ($data['lines'] as $line) {
                $item = Item::lockForUpdate()->findOrFail($line['item_id']);

                StockReceiptLine::create([
                    'stock_receipt_id' => $receipt->id,
                    'item_id' => $item->id,
                    'qty_received' => (int)$line['qty_received'],
                ]);

                // Update store qty_on_hand
                $item->increment('qty_on_hand', (int)$line['qty_received']);

                // If serialized: create units
                if ($item->has_serial) {
                    $serials = $line['serials'] ?? [];
                    if (count($serials) !== (int)$line['qty_received']) {
                        abort(422, "Serials count must match qty_received for item: {$item->name}");
                    }

                    foreach ($serials as $serial) {
                        $unit = ItemUnit::create([
                            'item_id' => $item->id,
                            'serial_no' => $serial,
                            'status' => 'in_store',
                        ]);

                        InventoryLog::create([
                            'action' => 'received',
                            'item_id' => $item->id,
                            'item_unit_id' => $unit->id,
                            'qty' => null,
                            'serial_no' => $serial,
                            'reference' => $reference,
                            'notes' => $data['notes'] ?? null,
                            'created_by' => auth()->id(),
                        ]);
                    }
                } else {
                    // Bulk log
                    InventoryLog::create([
                        'action' => 'received',
                        'item_id' => $item->id,
                        'item_unit_id' => null,
                        'qty' => (int)$line['qty_received'],
                        'serial_no' => null,
                        'reference' => $reference,
                        'notes' => $data['notes'] ?? null,
                        'created_by' => auth()->id(),
                    ]);
                }
            }
        });

        return redirect()->route('inventory.receipts.index')->with('success', 'Stock received successfully.');
    }

    public function show(StockReceipt $receipt)
    {
        $receipt->load(['receiver', 'lines.item']);
        return view('inventory::receipts.show', compact('receipt'));
    }
}
