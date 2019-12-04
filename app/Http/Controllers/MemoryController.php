<?php

namespace App\Http\Controllers;

use App\Memory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MemoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->json(Auth::user()->memories()->with('reasons')->orderBy('date', 'desc')->get());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $memory = Memory::create([
            'date' => $request->date,
            'title' => $request->title,
            'emotion_id' => $request->emotion,
            'details' => $request->details,
            'user_id' => Auth::user()->id
        ]);

        //Pivot table relationship for selected reasons attached to this memory.
        foreach ($request->reasons as $id => $value) {
            if ($value) {
                $memory->reasons()->attach($id + 1);
            }
        }

        return response()->json([
            'status' => (bool) $memory,
            'data' => $memory,
            'message' => $memory ? 'Memory created successfully!' : 'Error adding memory!'
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Memory $memory
     * @return \Illuminate\Http\Response
     */
    public function show(Memory $memory)
    {
        return response()->json($memory->where('id', $memory->id)->with('emotion')->with('reasons')->first());
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Memory  $memory
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Memory $memory)
    {
        if (auth()->id() != $request->user_id) {
            $status = false;
        } else {
            $status = $memory->update(
                $request->only(['date', 'title', 'emotion', 'details'])
            );

            //Pivot table relationship for reasons attached to this memory.
            foreach ($request->reasons as $reason_id => $status) {
                $memory->reasons()->attach($reason_id);
            }
        }

        return response()->json([
            'status' => $status,
            'message' => $status ? 'Memory updated successfully!' : 'Error updating memory!'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Memory $memory
     * @return \Illuminate\Http\Response
     */
    public function destroy(Memory $memory)
    {
        $status = $memory->delete();

        return response()->json([
            'status' => $status,
            'message' => $status ? 'Memory deleted successfully!' : 'Error deleting memory!'
        ]);
    }

    public function upload(Request $request, Memory $memory)
    {
        if ($request->hasFile('photos')) {
            echo "hasfile!<br>";
            dd([$request, $memory]);

            $file = $request->file('photo');

            $filename = time() . '.' . $file->getClientOriginalExtension();
            $imageFile = '/storage/memories/' . $filename;
            $file->move(storage_path('app/public/memories'), $filename);

            $request->request->add(['image' => $imageFile]);
        } else {
            echo "no files...<Br>";
            dd([$request, $memory]);
        }
    }
}
