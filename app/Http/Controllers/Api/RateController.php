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
     * Get the latest rate for each source.
     */
    public function latest()
    {
        // Get all unique sources
        $sources = Rate::distinct()->pluck('source');
        
        $latestRates = [];
        
        foreach ($sources as $source) {
            $rate = Rate::where('source', $source)
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

