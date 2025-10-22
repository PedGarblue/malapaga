<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Event;

class EventController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Event::with(['items.participations'])->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $event = Event::create($request->validate([
            'title' => 'required|string|max:255',
            'date' => 'nullable|date'
        ]) + ['user_id' => $request->user()->id ?? null]);

        return $event;
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return Event::with(['items.participations', 'consumers'])->findOrFail($id);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $event = Event::findOrFail($id);
        $event->update($request->validate([
            'title' => 'required|string|max:255',
            'date' => 'nullable|date'
        ]));
        return $event;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $event = Event::findOrFail($id);
        $event->delete();
        return response()->noContent();
    }
}
