<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Rate;

class RateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Rate::orderBy('effective_at', 'desc')->get();
    }

    /**
     * Get the latest rate for each source and currency_to combination.
     */
    public function latest()
    {
        // Get all unique combinations of source and currency_to
        $combinations = Rate::select('source', 'currency_to')
            ->distinct()
            ->get();
        
        $latestRates = [];
        
        foreach ($combinations as $combo) {
            $rate = Rate::where('source', $combo->source)
                ->where('currency_to', $combo->currency_to)
                ->orderBy('effective_at', 'desc')
                ->first();
            
            if ($rate) {
                $latestRates[] = $rate;
            }
        }
        
        return response()->json($latestRates);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $rate = Rate::create($request->validate([
            'source' => 'required|string|max:255',
            'value' => 'required|numeric',
            'currency_from' => 'required|string|max:10',
            'currency_to' => 'required|string|max:10',
            'effective_at' => 'required|date'
        ]));

        return $rate;
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return Rate::findOrFail($id);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $rate = Rate::findOrFail($id);
        $rate->update($request->validate([
            'source' => 'required|string|max:255',
            'value' => 'required|numeric',
            'currency_from' => 'required|string|max:10',
            'currency_to' => 'required|string|max:10',
            'effective_at' => 'required|date'
        ]));
        return $rate;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $rate = Rate::findOrFail($id);
        $rate->delete();
        return response()->noContent();
    }
}

