<?php

namespace App\Http\Controllers;

use App\Memory;
use App\MemoryPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

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
        if (auth()->id() == $memory->user_id) {
            return response()->json($memory->where('id', $memory->id)->with('emotion')->with(['reasons', 'photos'])->first());
        }
        return false;
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
                $request->only(['date', 'title', 'details'])
            );
        }

        return response()->json([
            'status' => $status,
            'message' => $status ? 'Memory updated successfully!' : 'Error updating memory!'
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateEmojis(Request $request)
    {
        if (auth()->id() != $request->user_id) {
            $status = false;
        } else {
            $memory = Memory::find($request->id);

            $status = $memory->update(
                $request->only(['emotion_id'])
            );

            // Delete all current memory reasons so they can be re-added.
            $memory->reasons()->detach();

            //Pivot table relationship for selected reasons attached to this memory.
            foreach ($request->reasons as $id => $value) {
                if ($value) {
                    $memory->reasons()->attach($id + 1);
                }
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

        if ($status) {
            Storage::deleteDirectory('./storage/memories/' . $memory->id);
        }

        return response()->json([
            'status' => $status,
            'message' => $status ? 'Memory deleted successfully!' : 'Error deleting memory!'
        ]);
    }

    public function upload(Request $request)
    {
        $status = false;

        if ($request->hasfile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $filename = $photo->store('/storage/memories/' . $request->memory_id . '/photos');
                $photo->move(storage_path('app/public/memories/' . $request->memory_id . '/photos'), $filename);

                MemoryPhoto::create([
                    'memory_id' => $request->memory_id,
                    'filename' => '/' . $filename
                ]);

                $status = true;
            }

            MemoryPhoto::correctImageOrientation('storage/memories/' . $request->memory_id);
        }

        return response()->json([
            'status' => $status,
            'message' => $status ? 'Your photos were uploaded successfully!' : 'There was an error uploading your photos. Please try again.'
        ]);
    }

    public function deletePhoto($id)
    {
        $photo = MemoryPhoto::find($id);

        // Delete file from server
        unlink('./' . $photo->filename);

        // Delete file from database
        $status = $photo->delete();

        return response()->json([
            'status' => $status,
            'message' => $status ? 'Photo deleted successfully!' : 'Error deleting photo!'
        ]);
    }

    public function setPrimaryPhoto(Request $request)
    {
        $memory = Memory::find($request->id);

        $status = $memory->update(
            $request->only(['photo'])
        );

        return response()->json([
            'status' => $status,
            'message' => $status ? 'Primary photo updated successfully!' : 'Error updating primary photo!'
        ]);
    }
}
