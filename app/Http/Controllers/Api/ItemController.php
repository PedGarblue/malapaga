<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Item;

class ItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $item = Item::create($request->validate([
            'event_id' => 'required|exists:events,id',
            'name' => 'required|string|max:255',
            'price_usd' => 'required|numeric',
            'rate_id' => 'required|exists:rates,id',
            'split_type' => 'nullable|in:shared,per-unit'
        ]));
        return $item;
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $item = Item::findOrFail($id);
        $item->update($request->validate([
            'name' => 'required|string|max:255',
            'price_usd' => 'required|numeric',
            'rate_id' => 'required|exists:rates,id',
            'split_type' => 'nullable|in:shared,per-unit'
        ]));
        return $item;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $item = Item::findOrFail($id);
        $item->delete();
        return response()->noContent();
    }
}
