<?php

namespace App\Http\Controllers;

use App\Models\TicketCategory;
use App\Models\EventCategoryTimeboundPrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class TicketCategoryController extends Controller
{
    public function index(string $eventId): JsonResponse
    {
        $categories = TicketCategory::where('event_id', $eventId)
            ->with([
                'timeboundPrices' => function ($query) {
                    $query->where('start_date', '<=', now())
                        ->where('end_date', '>=', now());
                }
            ])
            ->get()
            ->map(function ($category) {
                $category->current_price = $category->timeboundPrices->first();
                unset($category->timeboundPrices);
                return $category;
            });

        return response()->json($categories);
    }

    public function store(Request $request, string $eventId): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'required|string|max:7',
            'timebound_prices' => 'required|array',
            'timebound_prices.*.start_date' => 'required|date',
            'timebound_prices.*.end_date' => 'required|date|after:timebound_prices.*.start_date',
            'timebound_prices.*.price' => 'required|numeric|min:0'
        ]);

        try {
            DB::beginTransaction();

            $category = TicketCategory::create([
                'event_id' => $eventId,
                'name' => $validated['name'],
                'color' => $validated['color']
            ]);

            foreach ($validated['timebound_prices'] as $priceData) {
                EventCategoryTimeboundPrice::create([
                    'ticket_category_id' => $category->ticket_category_id,
                    'start_date' => $priceData['start_date'],
                    'end_date' => $priceData['end_date'],
                    'price' => $priceData['price']
                ]);
            }

            DB::commit();

            return response()->json($category, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to create ticket category'], 500);
        }
    }

    public function update(Request $request, string $categoryId): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'color' => 'sometimes|required|string|max:7'
        ]);

        $category = TicketCategory::findOrFail($categoryId);
        $category->update($validated);

        return response()->json($category);
    }

    public function addPrice(Request $request, string $categoryId): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'price' => 'required|numeric|min:0'
        ]);

        $price = EventCategoryTimeboundPrice::findOrFail($priceId);
        $price->delete();

        return response()->json(['message' => 'Price period deleted successfully']);
    }

    public function getCurrentPrice(string $categoryId): JsonResponse
    {
        $currentPrice = EventCategoryTimeboundPrice::where('ticket_category_id', $categoryId)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->first();

        if (!$currentPrice) {
            return response()->json(['error' => 'No active price found'], 404);
        }

        return response()->json($currentPrice);
    }

    public function getPriceHistory(string $categoryId): JsonResponse
    {
        $prices = EventCategoryTimeboundPrice::where('ticket_category_id', $categoryId)
            ->orderBy('start_date', 'desc')
            ->get();

        return response()->json($prices);
    }
}