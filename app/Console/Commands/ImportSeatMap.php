<?php

namespace App\Console\Commands;

use App\Models\Seat;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportSeatMap extends Command
{
    protected $signature = 'seats:import {file : Path to JSON config file}';
    protected $description = 'Import seat configuration from JSON';

    private $seatNumberCounters = [];

    public function handle()
    {
        $jsonPath = $this->argument('file');
        $jsonContent = file_get_contents($jsonPath);
        $config = json_decode($jsonContent, true);

        try {
            DB::beginTransaction();

            // Reset seat number counters
            $this->seatNumberCounters = [];

            foreach ($config['layout']['items'] as $item) {
                if (!isset($item['type'])) {
                    $item['type'] = 'seat';
                }

                if ($item['type'] === 'seat') {
                    // Parse position untuk mendapatkan row dan column
                    $position = $item['position'] ?? '';
                    if (!preg_match('/^([A-Za-z]+)(\d+)$/', $position, $matches)) {
                        throw new \Exception("Invalid position format: {$position}");
                    }

                    $row = strtoupper($matches[1]);
                    $column = (int) $matches[2];

                    // Generate seat number berdasarkan row
                    $seatNumber = $this->generateSeatNumber($row);

                    // Generate unique seat_id
                    $seatId = $this->generateUniqueSeatId();

                    $seat = Seat::create([
                        'seat_id'     => $seatId,
                        'venue_id'    => $config['venue_id'],
                        'seat_number' => $seatNumber,
                        'position'    => $position,
                        'row'         => $row,
                        'column'      => $column,
                    ]);

                    $this->info("Created seat: {$seatNumber} at position {$position}");
                }
            }

            DB::commit();
            $this->info('Seat map imported successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Generate seat number berdasarkan row
     * Format: [ROW][INCREMENT_NUMBER]
     */
    private function generateSeatNumber(string $row): string
    {
        if (!isset($this->seatNumberCounters[$row])) {
            $this->seatNumberCounters[$row] = 0;
        }

        $this->seatNumberCounters[$row]++;
        return $row . $this->seatNumberCounters[$row];
    }

    /**
     * Generate unique seat_id
     */
    private function generateUniqueSeatId(): string
    {
        do {
            // Generate format: ST-XXXXX (X adalah random number)
            $seatId = 'ST-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
        } while (Seat::where('seat_id', $seatId)->exists());

        return $seatId;
    }
}