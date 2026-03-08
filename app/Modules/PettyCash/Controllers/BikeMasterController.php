<?php

namespace App\Modules\PettyCash\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Modules\PettyCash\Models\Bike;

class BikeMasterController extends Controller
{
    public function index()
    {
        $bikes = Bike::orderBy('plate_no')->paginate(20)->withQueryString();
        return view('pettycash::bikes_master.index', compact('bikes'));
    }

    public function create()
    {
        return view('pettycash::bikes_master.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'plate_no' => ['required','string','max:50','unique:petty_bikes,plate_no'],
            'model' => ['nullable','string','max:120'],
            'status' => ['nullable','string','max:50'],
        ]);

        Bike::create($data);

        return redirect()->route('petty.bikes_master.index')->with('success', 'Bike added.');
    }

    public function edit(Bike $bike)
    {
        return view('pettycash::bikes_master.edit', compact('bike'));
    }

    public function update(Request $request, Bike $bike)
    {
        $data = $request->validate([
            'plate_no' => ['required','string','max:50','unique:petty_bikes,plate_no,'.$bike->id],
            'model' => ['nullable','string','max:120'],
            'status' => ['nullable','string','max:50'],
        ]);

        $bike->update($data);

        return redirect()->route('petty.bikes_master.index')->with('success', 'Bike updated.');
    }
}
