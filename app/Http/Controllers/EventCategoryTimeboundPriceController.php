<?php

namespace App\Http\Controllers;

use App\Models\EventCategoryTimeboundPrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EventCategoryTimeboundPriceController extends Controller
{
    /**
     * Display a listing of the timebound prices.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $timeboundPrices = EventCategoryTimeboundPrice::with('ticketCategory')->get();
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
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
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
        $timeboundPrice = EventCategoryTimeboundPrice::with('ticketCategory')->find($id);

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
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after:start_date',
            'price' => 'sometimes|required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
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
     * Get active prices for a specific date.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getActivePrices(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $date = $request->date;

        $activePrices = EventCategoryTimeboundPrice::with('ticketCategory')
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->get();

        return response()->json(['data' => $activePrices], 200);
    }
}