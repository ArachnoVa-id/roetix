<?php

namespace App\Console\Commands;

use App\Models\Seat;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportSeatMap extends Command
{
    protected $signature = 'seats:import {file : Path to JSON config file}';
    protected $description = 'Import seat configuration from JSON';

    public function handle()
    {
        $jsonPath = $this->argument('file');
        $jsonContent = file_get_contents($jsonPath);
        $config = json_decode($jsonContent, true);

        try {
            DB::beginTransaction();

            foreach ($config['layout']['items'] as $item) {
                if ($item['type'] === 'seat') {
                    $existingSeat = Seat::where('seat_id', $item['seat_id'])->first();
            
                    if (!$existingSeat) {
                        Seat::create([
                            'seat_id' => $item['seat_id'],
                            'venue_id' => $config['venue_id'],
                            'seat_number' => $item['seat_id'],
                            'position' => "{$item['row']}-{$item['column']}",
                            'status' => "available",
                            'category' => $item['category'],
                            'row' => $item['row'],
                            'column' => $item['column'],
                            'price' => $item['price'] ?? 0
                        ]);
                    }
                }
            }

            DB::commit();
            $this->info('Seat map imported successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Import failed: ' . $e->getMessage());
        }
    }
}