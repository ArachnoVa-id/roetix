<?php

namespace App\Console\Commands;

use App\Models\Seat;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportSeatMap extends Command
{
    protected $signature = 'seats:import {file : Path to JSON config file} {venue_id : Venue ID}';
    protected $description = 'Import seat configuration from JSON';

    // private $seatNumberCounters = [];

    public static function generateFromConfig($config = null, $venueId = null, $successLineCallback = null, $successCallback = null, $failedCallback = null)
    {
        try {
            DB::beginTransaction();

            // Reset seat number counters
            $seatNumberCounters = [];

            foreach ($config['layout']['items'] as $item) {
                // Parse position untuk mendapatkan row dan column
                $position = $item ?? '';
                if (!preg_match('/^([A-Za-z]+)(\d+)$/', $position, $matches)) {
                    throw new \Exception("Invalid position format: {$position}");
                }

                // check if position already exists
                if (Seat::where('venue_id', $venueId)->where('position', $position)->exists()) {
                    throw new \Exception("Seat at position {$position} already exists");
                }

                $row = strtoupper($matches[1]);
                $column = (int) $matches[2];

                // Generate seat number berdasarkan row
                $seatNumber = self::generateSeatNumber($row, $seatNumberCounters);

                // Generate unique seat_id
                $seatId = self::generateSeatId($venueId, $seatNumber);
                Seat::create([
                    'id'     => $seatId,
                    'venue_id'    => $venueId,
                    'seat_number' => $seatNumber,
                    'position'    => $position,
                    'row'         => $row,
                    'column'      => $column,
                ]);

                $successLineCallback("Created seat: {$seatNumber} at position {$position}");
            }

            DB::commit();
            $successCallback('Seat map imported successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            $failedCallback('Import failed: ' . $e->getMessage());
        }
    }

    public function handle()
    {
        $jsonPath = $this->argument('file');
        $venueId = $this->argument('venue_id');
        $jsonContent = file_get_contents($jsonPath);
        $config = json_decode($jsonContent, true);

        self::generateFromConfig(
            config: $config,
            venueId: $venueId,
            successLineCallback: fn($line) => $this->info($line),
            successCallback: fn($message) => $this->info($message),
            failedCallback: fn($message) => $this->error($message)
        );
    }

    /**
     * Generate seat number berdasarkan row
     * Format: [ROW][INCREMENT_NUMBER]
     */
    private static function generateSeatNumber(string $row, &$seatNumberCounters): string
    {
        if (!isset($seatNumberCounters[$row])) {
            $seatNumberCounters[$row] = 0;
        }

        $seatNumberCounters[$row]++;
        return $row . $seatNumberCounters[$row];
    }

    /**
     * Generate unique seat_id
     */
    private static function generateSeatId(string $venueId, string $seatNumber): string
    {
        // Gabungkan venue_id dan seat_number
        $seatId = $venueId . '-' . $seatNumber;

        // Cek apakah ID sudah ada, jika ada tambahkan suffix
        $counter = 1;
        $originalSeatId = $seatId;
        while (Seat::where('id', $seatId)->exists()) {
            $seatId = $originalSeatId . '-' . $counter;
            $counter++;
        }

        return $seatId;
    }
}
