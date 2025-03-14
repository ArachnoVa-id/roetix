<?php

namespace App\Http\Controllers;

use App\Models\TimelineSession;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TimelineSessionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $timelineSessions = TimelineSession::with('event')->get();
        return response()->json(['data' => $timelineSessions]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'event_id' => 'required|string|max:36|exists:events,event_id',
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $timelineSession = TimelineSession::create($request->all());
        
        return response()->json([
            'message' => 'Timeline session created successfully',
            'data' => $timelineSession
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $timelineSession = TimelineSession::with('event')->findOrFail($id);
        return response()->json(['data' => $timelineSession]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $timelineSession = TimelineSession::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'event_id' => 'sometimes|string|max:36|exists:events,event_id',
            'name' => 'sometimes|string|max:255',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $timelineSession->update($request->all());
        
        return response()->json([
            'message' => 'Timeline session updated successfully',
            'data' => $timelineSession
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $timelineSession = TimelineSession::findOrFail($id);
        $timelineSession->delete();
        
        return response()->json([
            'message' => 'Timeline session deleted successfully'
        ]);
    }

    /**
     * Get timeline sessions by event id.
     *
     * @param  string  $eventId
     * @return \Illuminate\Http\Response
     */
    public function getByEvent($eventId)
    {
        $timelineSessions = TimelineSession::where('event_id', $eventId)
            ->with('event')
            ->orderBy('start_date')
            ->get();
            
        return response()->json(['data' => $timelineSessions]);
    }
}