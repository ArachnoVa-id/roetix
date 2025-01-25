<?php

namespace App\Http\Controllers;

use App\Models\Seat;
use App\Models\Section;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class SeatController extends Controller
{
    public function index()
    {
        $seats = Seat::orderBy('row')->orderBy('column')->get();
        
        $layout = [
            'totalRows' => count(array_unique($seats->pluck('row')->toArray())),
            'totalColumns' => $seats->max('column'),
            'items' => $seats->map(function($seat) {
                return [
                    'type' => 'seat',
                    'seat_id' => $seat->seat_id,
                    'row' => $seat->row,
                    'column' => $seat->column,
                    'status' => $seat->status,
                    'category' => $seat->category,
                    'price' => $seat->price
                ];
            })->values()
        ];

        // Add stage label
        $layout['items'][] = [
            'type' => 'label',
            'row' => $layout['totalRows'],
            'column' => floor($layout['totalColumns'] / 2),
            'text' => 'STAGE'
        ];

        return Inertia::render('Seat/Index', [
            'layout' => $layout
        ]);
    }

    public function importMap(Request $request)
    {
        $request->validate([
            'config' => 'required|file'
        ]);

        $json = json_decode(file_get_contents($request->file('config')->path()), true);

        try {
            \DB::beginTransaction();

            foreach ($json['items'] as $item) {
                if ($item['type'] === 'seat') {
                    Seat::create([
                        'seat_id' => $item['seat_id'],
                        'row' => $item['row'],
                        'column' => $item['column'],
                        'status' => $item['status'],
                        'category' => $item['category'],
                        'price' => $item['price']
                    ]);
                }
            }

            \DB::commit();
            return back()->with('message', 'Seat map imported successfully');

        } catch (\Exception $e) {
            \DB::rollBack();
            return back()->withErrors(['error' => 'Failed to import seat map']);
        }
    }

    public function edit()
    {
        // Load existing seat data
        $seatData = [
            'sections' => Section::with(['seats' => function ($query) {
                $query->orderBy('row')->orderBy('column');
            }])->get()->map(function ($section) {
                return [
                    'id' => $section->id,
                    'name' => $section->name,
                    'rows' => $section->seats->pluck('row')->unique()->values()->all(),
                    'seats' => $section->seats->map(function ($seat) {
                        return [
                            'seat_id' => $seat->seat_id,
                            'seat_number' => $seat->seat_number,
                            'position' => $seat->position,
                            'status' => $seat->status,
                            'category' => $seat->category,
                            'row' => $seat->row,
                            'column' => $seat->column
                        ];
                    })->values()->all()
                ];
            })->values()->all()
        ];

        return Inertia::render('Seat/Edit', [
            'seatData' => $seatData
        ]);
    }

    // SeatController.php
public function update(Request $request)
{
    $validated = $request->validate([
        'seats' => 'required|array',
        'seats.*.seat_id' => 'required|string',
        'seats.*.status' => 'required|string|in:available,booked,in-transaction,not_available',
    ]);

    try {
        DB::beginTransaction();

        foreach ($validated['seats'] as $seatData) {
            Seat::where('seat_id', $seatData['seat_id'])
                ->update([
                    'status' => $seatData['status']
                ]);
        }

        DB::commit();
        return redirect()
            ->route('seats.index')
            ->with('message', 'Seats updated successfully');

    } catch (\Exception $e) {
        DB::rollBack();
        return back()->withErrors(['error' => 'Failed to update seats: ' . $e->getMessage()]);
    }
}
}