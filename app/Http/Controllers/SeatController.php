<?php

namespace App\Http\Controllers;

use App\Models\Seat;
use App\Models\Section;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
                    'seat_number' => $seat->seat_number, // Tambahkan ini
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
            DB::beginTransaction();

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

            DB::commit();
            return back()->with('message', 'Seat map imported successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to import seat map']);
        }
    }

    public function edit()
{
    try {
        // Ambil semua kursi
        $seats = Seat::orderBy('row')->orderBy('column')->get();
        
        // Format data seperti di method index()
        $layout = [
            'totalRows' => count(array_unique($seats->pluck('row')->toArray())),
            'totalColumns' => $seats->max('column'),
            'items' => $seats->map(function($seat) {
                return [
                    'type' => 'seat',
                    'seat_id' => $seat->seat_id,
                    'seat_number' => $seat->seat_number, // Tambahkan ini
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

        return Inertia::render('Seat/Edit', [
            'layout' => $layout
        ]);

    } catch (\Exception $e) {
        Log::error('Error in edit method: ' . $e->getMessage());
        return redirect()->back()->withErrors(['error' => 'Failed to load seat map']);
    }
}

    // SeatController.php
    public function update(Request $request)
{
    Log::info('Request headers:', $request->headers->all());
    Log::info('Request body:', $request->all());

    $validated = $request->validate([
        'seats' => 'required|array',
        'seats.*.seat_id' => 'required|string',
        'seats.*.status' => 'required|string|in:available,booked,in_transaction,not_available',
    ]);

    try {
        DB::beginTransaction();

        foreach ($validated['seats'] as $seatData) {
            $seat = Seat::where('seat_id', $seatData['seat_id'])->first();

            if ($seat && $seat->status === 'booked' && $seatData['status'] !== 'booked') {
                continue;
            }

            Seat::where('seat_id', $seatData['seat_id'])
                ->update([
                    'status' => $seatData['status']
                ]);
        }

        DB::commit();
        
       

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error updating seats: ' . $e->getMessage());
        return response()->json([
            'error' => 'Failed to update seats'
        ], 422);
    }
}

    public function spreadsheet()
{
    try {
        $seats = Seat::orderBy('row')->orderBy('column')->get();
        
        $layout = [
            'totalRows' => count(array_unique($seats->pluck('row')->toArray())),
            'totalColumns' => $seats->max('column'),
            'items' => $seats->map(function($seat) {
                return [
                    'type' => 'seat',
                    'seat_id' => $seat->seat_id,
                    'seat_number' => $seat->seat_number, // Tambahkan ini
                    'row' => $seat->row,
                    'column' => $seat->column,
                    'status' => $seat->status,
                    'category' => $seat->category,
                    'price' => $seat->price
                ];
            })->values()
        ];

        return Inertia::render('Seat/Spreadsheet', [
            'layout' => $layout
        ]);

    } catch (\Exception $e) {
        Log::error('Error in spreadsheet method: ' . $e->getMessage());
        return redirect()->back()->withErrors(['error' => 'Failed to load seat spreadsheet']);
    }
}
}