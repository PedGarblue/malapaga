<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Consumer;

class ConsumerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return Consumer::where('user_id', $request->user()->id)->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $consumer = Consumer::create($request->validate([
            'name' => 'required|string|max:255',
        ]) + ['user_id' => $request->user()->id]);

        return $consumer;
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $consumer = Consumer::where('user_id', $request->user()->id)->findOrFail($id);
        return $consumer;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $consumer = Consumer::where('user_id', $request->user()->id)->findOrFail($id);
        $consumer->update($request->validate([
            'name' => 'required|string|max:255',
        ]));
        return $consumer;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $consumer = Consumer::where('user_id', $request->user()->id)->findOrFail($id);
        $consumer->delete();
        return response()->noContent();
    }
}

