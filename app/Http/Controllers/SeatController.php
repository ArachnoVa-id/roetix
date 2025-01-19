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

        return Inertia::render('Seat/Index', [
            'seatData' => $seatData
        ]);
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

    public function update(Request $request)
    {
        $validated = $request->validate([
            'sections' => 'required|array',
            'sections.*.id' => 'required|string',
            'sections.*.name' => 'required|string',
            'sections.*.rows' => 'required|array',
            'sections.*.seats' => 'required|array',
            'sections.*.seats.*.seat_id' => 'required|string',
            'sections.*.seats.*.seat_number' => 'required|string',
            'sections.*.seats.*.position' => 'required|string',
            'sections.*.seats.*.status' => 'required|string|in:available,booked,reserved,in_transaction',
            'sections.*.seats.*.category' => 'required|string|in:diamond,gold,silver',
            'sections.*.seats.*.row' => 'required|string',
            'sections.*.seats.*.column' => 'required|integer'
        ]);

        try {
            DB::beginTransaction();

            foreach ($validated['sections'] as $sectionData) {
                $section = Section::findOrFail($sectionData['id']);
                
                foreach ($sectionData['seats'] as $seatData) {
                    Seat::where('seat_id', $seatData['seat_id'])
                        ->where('section_id', $section->id)
                        ->update([
                            'position' => $seatData['position'],
                            'status' => $seatData['status'],
                            'category' => $seatData['category'],
                            'row' => $seatData['row'],
                            'column' => $seatData['column']
                        ]);
                }
            }

            DB::commit();

            return redirect()
                ->route('seats.index')
                ->with('message', 'Seat map updated successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to update seat map: ' . $e->getMessage()]);
        }
    }
}