<?php
namespace App\Http\Controllers;

use App\Models\EventCategoryTimeboundPrice;
use App\Models\TimelineSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class EventCategoryTimeboundPriceController extends Controller
{
    /**
     * Display a listing of the timebound prices.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $timeboundPrices = EventCategoryTimeboundPrice::with(['ticketCategory', 'timelineSession'])->get();
        return response()->json(['data' => $timeboundPrices], 200);
    }

    /**
     * Store a newly created timebound price in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ticket_category_id' => 'required|exists:ticket_categories,ticket_category_id',
            'timeline_id' => 'required|exists:timeline_sessions,timeline_id',
            'price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check if this ticket category and timeline combination already exists
        $exists = EventCategoryTimeboundPrice::where('ticket_category_id', $request->ticket_category_id)
            ->where('timeline_id', $request->timeline_id)
            ->exists();
            
        if ($exists) {
            return response()->json(['message' => 'A price for this ticket category and timeline already exists'], 422);
        }

        $timeboundPrice = EventCategoryTimeboundPrice::create($request->all());

        return response()->json(['data' => $timeboundPrice, 'message' => 'Timebound price created successfully'], 201);
    }

    /**
     * Display the specified timebound price.
     *
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $timeboundPrice = EventCategoryTimeboundPrice::with(['ticketCategory', 'timelineSession'])->find($id);

        if (!$timeboundPrice) {
            return response()->json(['message' => 'Timebound price not found'], 404);
        }

        return response()->json(['data' => $timeboundPrice], 200);
    }

    /**
     * Update the specified timebound price in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $timeboundPrice = EventCategoryTimeboundPrice::find($id);

        if (!$timeboundPrice) {
            return response()->json(['message' => 'Timebound price not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'ticket_category_id' => 'sometimes|required|exists:ticket_categories,ticket_category_id',
            'timeline_id' => 'sometimes|required|exists:timeline_sessions,timeline_id',
            'price' => 'sometimes|required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        // If changing ticket_category_id or timeline_id, check for existing combinations
        if (($request->has('ticket_category_id') && $request->ticket_category_id != $timeboundPrice->ticket_category_id) ||
            ($request->has('timeline_id') && $request->timeline_id != $timeboundPrice->timeline_id)) {
            
            $exists = EventCategoryTimeboundPrice::where('ticket_category_id', $request->ticket_category_id ?? $timeboundPrice->ticket_category_id)
                ->where('timeline_id', $request->timeline_id ?? $timeboundPrice->timeline_id)
                ->where('timebound_price_id', '!=', $id)
                ->exists();
                
            if ($exists) {
                return response()->json(['message' => 'A price for this ticket category and timeline already exists'], 422);
            }
        }

        $timeboundPrice->update($request->all());

        return response()->json(['data' => $timeboundPrice, 'message' => 'Timebound price updated successfully'], 200);
    }

    /**
     * Remove the specified timebound price from storage.
     *
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $timeboundPrice = EventCategoryTimeboundPrice::find($id);

        if (!$timeboundPrice) {
            return response()->json(['message' => 'Timebound price not found'], 404);
        }

        $timeboundPrice->delete();

        return response()->json(['message' => 'Timebound price deleted successfully'], 200);
    }

    /**
     * Get active prices for the current date or a specific date.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getActivePrices(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $date = $request->has('date') ? Carbon::parse($request->date) : Carbon::now();
        
        // Find active timelines for the given date
        $activeTimelines = TimelineSession::where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->pluck('timeline_id');
            
        // Get prices for those timelines
        $activePrices = EventCategoryTimeboundPrice::with(['ticketCategory', 'timelineSession'])
            ->whereIn('timeline_id', $activeTimelines)
            ->get();

        return response()->json(['data' => $activePrices], 200);
    }
    
    /**
     * Get prices by timeline.
     *
     * @param  string  $timelineId
     * @return \Illuminate\Http\Response
     */
    public function getPricesByTimeline($timelineId)
    {
        $prices = EventCategoryTimeboundPrice::with(['ticketCategory', 'timelineSession'])
            ->where('timeline_id', $timelineId)
            ->get();
            
        return response()->json(['data' => $prices], 200);
    }
    
    /**
     * Get prices by ticket category.
     *
     * @param  string  $ticketCategoryId
     * @return \Illuminate\Http\Response
     */
    public function getPricesByTicketCategory($ticketCategoryId)
    {
        $prices = EventCategoryTimeboundPrice::with(['ticketCategory', 'timelineSession'])
            ->where('ticket_category_id', $ticketCategoryId)
            ->get();
            
        return response()->json(['data' => $prices], 200);
    }
}