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
                // Set default type to 'seat' jika tidak disediakan
                if (!isset($item['type'])) {
                    $item['type'] = 'seat';
                }

                if ($item['type'] === 'seat') {
                    // Ambil row dan column dari seat_id
                    $seatId = $item['seat_id'];
                    preg_match('/^([A-Za-z]+)(\d+)$/', $seatId, $matches);
                    if ($matches) {
                        $row    = strtoupper($matches[1]);
                        $column = (int) $matches[2];
                    } else {
                        $row    = $item['row'] ?? '';
                        $column = $item['column'] ?? 0;
                    }

                    $existingSeat = Seat::where('seat_id', $item['seat_id'])->first();

                    if (!$existingSeat) {
                        Seat::create([
                            'seat_id'     => $item['seat_id'],
                            'venue_id'    => $config['venue_id'],
                            'seat_number' => $item['seat_id'],
                            'position'    => "{$row}-{$column}",
                            'status'      => $item['status'] ?? 'available',
                            'category'    => $item['category'],
                            'row'         => $row,
                            'column'      => $column,
                            'price'       => $item['price'] ?? 0
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