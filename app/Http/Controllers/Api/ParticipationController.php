<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Participation;

class ParticipationController extends Controller
{
    /**
     * Display a listing of the resource always by item_id.
     */
    public function index(string $item_id)
    {
        return Participation::where('item_id', $item_id)->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $participation = Participation::create($request->validate([
            'item_id' => 'required|exists:items,id',
            'consumer_id' => 'required|exists:consumers,id',
            'qty' => 'required|numeric',
            'paid_by_id' => 'required|exists:consumers,id'
        ]));

        return $participation;
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
        $participation = Participation::findOrFail($id);
        $participation->update($request->validate([
            'qty' => 'required|numeric',
            'paid_by_id' => 'required|exists:consumers,id'
        ]));
        return $participation;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $participation = Participation::findOrFail($id);
        $participation->delete();
        return response()->noContent();
    }
}
