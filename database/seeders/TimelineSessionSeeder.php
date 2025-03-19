<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TimelineSession;
use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Support\Str;

class TimelineSessionSeeder extends Seeder
{
    /**
     * Urutan nama session
     */
    protected $sessionNames = [
        'Early Bird',
        'Presale 1',
        'Presale 2',
        'Member Access',
        'VIP Access',
        'Regular Sale',
        'Flash Sale',
        'General Sale',
        'Last Minute Sale'
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Hapus data timeline sebelumnya untuk memastikan konsistensi
        TimelineSession::query()->delete();

        // Dapatkan semua event
        $events = Event::all();

        foreach ($events as $event) {
            // Ambil tanggal awal dan akhir event
            $eventStartDate = Carbon::parse($event->start_date);
            $eventEndDate = Carbon::parse($event->event_date);

            // Hitung berapa hari sebelum event dimulai untuk timeline session
            // Kita akan mulai 30 hari sebelum event
            $timelineStartDate = (clone $eventStartDate)->subDays(30);

            // Pastikan tanggal akhir timeline session tidak melewati tanggal event
            $timelineEndDate = $eventEndDate;

            // Hitung berapa hari tersedia untuk session
            $availableDays = $timelineStartDate->diffInDays($timelineEndDate);

            // Tentukan jumlah session (maksimal 5 atau sesuai jumlah hari yang tersedia)
            $sessionCount = min(5, $availableDays / 3); // minimal 3 hari per session

            // Pastikan setidaknya ada 1 session
            $sessionCount = max(1, (int)$sessionCount);

            // Hitung durasi rata-rata untuk setiap session
            $avgDuration = $availableDays / $sessionCount;

            // Buat session per event
            $currentDate = $timelineStartDate;

            for ($i = 0; $i < $sessionCount; $i++) {
                // Durasi session bervariasi sedikit dari rata-rata
                $durationDays = max(1, $avgDuration + rand(-1, 1));

                // Pastikan durasi minimal 2 hari
                $durationDays = max(2, $durationDays);

                // Pastikan session terakhir berakhir tepat saat event dimulai
                $nextEndDate = ($i == $sessionCount - 1)
                    ? $timelineEndDate
                    : (clone $currentDate)->addDays($durationDays);

                // Pastikan tidak melewati tanggal event
                if ($nextEndDate > $timelineEndDate) {
                    $nextEndDate = $timelineEndDate;
                }

                // Jika start date sama dengan atau melewati end date,
                // berarti tidak ada cukup waktu untuk session ini
                if ($currentDate >= $nextEndDate) {
                    break;
                }

                // Buat timeline session
                TimelineSession::create([
                    'timeline_id' => (string) Str::uuid(),
                    'event_id' => $event->event_id,
                    'name' => $this->sessionNames[$i % count($this->sessionNames)],
                    'start_date' => $currentDate,
                    'end_date' => $nextEndDate,
                ]);

                // Pindah ke tanggal berikutnya
                $currentDate = $nextEndDate;
            }
        }
    }
}
