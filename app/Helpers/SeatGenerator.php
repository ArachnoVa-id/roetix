<?php
// app/Helpers/SeatGenerator.php

namespace App\Helpers;

use Ramsey\Uuid\Uuid;

class SeatGenerator
{
    public static function generateSeatMap()
    {
        $sections = [];
        
        // Section kiri
        $sections[] = self::generateSection(
            'section-left',
            'Left Wing',
            ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'],
            [4, 5, 6, 7, 8, 9, 10, 11, 12, 13], // Jumlah kursi per baris
            'gold'
        );

        // Section tengah
        $sections[] = self::generateSection(
            'section-center',
            'Center',
            ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'],
            [15, 15, 15, 15, 15, 15, 15, 15, 15, 15], // Kursi seragam
            'diamond'
        );

        // Section kanan
        $sections[] = self::generateSection(
            'section-right',
            'Right Wing',
            ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'],
            [5, 5, 6, 7, 8, 9, 10, 11, 12, 13], // Mirror dari kiri
            'gold'
        );

        return [
            'sections' => $sections
        ];
    }

    private static function generateSection($id, $name, $rows, $seatsPerRow, $defaultCategory)
    {
        $seats = [];
        $statuses = ['available', 'booked', 'reserved', 'in_transaction', 'not_available'];
        $categories = ['diamond', 'gold', 'silver'];

        foreach ($rows as $rowIndex => $row) {
            $numSeats = $seatsPerRow[$rowIndex];
            
            for ($col = 1; $col <= $numSeats; $col++) {
                // Random status dengan kemungkinan terbesar 'available'
                $status = (rand(1, 100) > 20) ? 'available' : $statuses[array_rand(array_slice($statuses, 1))];
                
                // Category berdasarkan posisi
                $category = $defaultCategory;
                if ($rowIndex >= count($rows) * 0.7) {
                    $category = 'silver';
                }

                $seats[] = [
                    'seat_id' => Uuid::uuid4()->toString(),
                    'seat_number' => $row . $col,
                    'position' => $row . '-' . $col,
                    'status' => $status,
                    'category' => $category,
                    'row' => $row,
                    'column' => $col
                ];
            }
        }

        return [
            'id' => $id,
            'name' => $name,
            'rows' => $rows,
            'seats' => $seats
        ];
    }
}