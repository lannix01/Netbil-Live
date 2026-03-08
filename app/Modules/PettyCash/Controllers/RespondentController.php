<?php

namespace App\Modules\PettyCash\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Modules\PettyCash\Models\Respondent;

class RespondentController extends Controller
{
    public function index()
    {
        $respondents = Respondent::orderBy('name')->paginate(20)->withQueryString();
        return view('pettycash::respondents.index', compact('respondents'));
    }

    public function create()
    {
        return view('pettycash::respondents.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:150'],
            'phone' => ['nullable','string','max:50'],
            'category' => ['nullable','string','max:80'],
        ]);

        Respondent::create($data);

        return redirect()->route('petty.respondents.index')->with('success', 'Respondent added.');
    }

    public function edit(Respondent $respondent)
    {
        return view('pettycash::respondents.edit', compact('respondent'));
    }

    public function update(Request $request, Respondent $respondent)
    {
        $data = $request->validate([
            'name' => ['required','string','max:150'],
            'phone' => ['nullable','string','max:50'],
            'category' => ['nullable','string','max:80'],
        ]);

        $respondent->update($data);

        return redirect()->route('petty.respondents.index')->with('success', 'Respondent updated.');
    }
}
