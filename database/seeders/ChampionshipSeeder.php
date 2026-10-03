<?php

namespace Database\Seeders;

use App\Models\Arena;
use App\Models\Athlete;
use App\Models\Category;
use App\Models\Contingent;
use App\Models\Equipment;
use App\Models\Schedule;
use App\Services\BracketService;
use Illuminate\Database\Seeder;

class ChampionshipSeeder extends Seeder
{
    public function run(): void
    {
        $categories = $this->seedCategories();
        $contingents = $this->seedContingents();
        $this->seedArenas($categories);
        $this->seedEquipment();
        $this->seedAthletes($categories, $contingents);
        $this->seedArtSchedules($categories);
        $this->seedBracket($categories);
    }

    protected function seedCategories(): array
    {
        $rows = [
            ['name' => 'Daeryun Putra', 'type' => 'daeryun', 'gender' => 'male'],
            ['name' => 'Daeryun Putri', 'type' => 'daeryun', 'gender' => 'female'],
            ['name' => 'Hyung Putri', 'type' => 'art', 'gender' => 'female'],
            ['name' => 'Hosinsul', 'type' => 'art', 'gender' => 'open'],
            ['name' => 'Nakbop', 'type' => 'art', 'gender' => 'open'],
            ['name' => 'Lompat Tinggi', 'type' => 'art', 'gender' => 'open'],
            ['name' => 'Seni Senjata', 'type' => 'art', 'gender' => 'open'],
        ];

        $result = [];

        foreach ($rows as $row) {
            $result[$row['name']] = Category::firstOrCreate(
                ['slug' => str()->slug($row['name'])],
                $row + ['age_class' => 'Umum']
            );
        }

        return $result;
    }

    protected function seedContingents(): array
    {
        $rows = [
            ['name' => 'ITERA Lampung', 'code' => 'ITERA', 'region' => 'Lampung', 'coach_name' => 'Rudi Hartono'],
            ['name' => 'Surabaya Taekwondo', 'code' => 'SBY', 'region' => 'Jawa Timur', 'coach_name' => 'Andi Saputra'],
            ['name' => 'Malang Warrior', 'code' => 'MLG', 'region' => 'Jawa Timur', 'coach_name' => 'Dewi Lestari'],
            ['name' => 'Bandung Fighter', 'code' => 'BDG', 'region' => 'Jawa Barat', 'coach_name' => 'Rizky Pratama'],
            ['name' => 'Jakarta Elite', 'code' => 'JKT', 'region' => 'DKI Jakarta', 'coach_name' => 'Sari Melati'],
            ['name' => 'Lampung Champion', 'code' => 'LPG', 'region' => 'Lampung', 'coach_name' => 'Budi Santoso'],
        ];

        $result = [];

        foreach ($rows as $row) {
            $result[$row['code']] = Contingent::firstOrCreate(['code' => $row['code']], $row);
        }

        return $result;
    }

    protected function seedArenas(array $categories): void
    {
        $rows = [
            ['name' => 'A', 'label' => 'Arena A', 'type' => 'Daeryun Putra'],
            ['name' => 'B', 'label' => 'Arena B', 'type' => 'Hyung Putri'],
            ['name' => 'C', 'label' => 'Arena C', 'type' => 'Hosinsul'],
        ];

        foreach ($rows as $row) {
            Arena::firstOrCreate(
                ['name' => $row['name']],
                [
                    'label' => $row['label'],
                    'category_id' => $categories[$row['type']]?->id,
                    'match_type' => 'daeryun',
                    'status' => 'preparation',
                ]
            );
        }
    }

    protected function seedEquipment(): void
    {
        $colors = ['Merah', 'Biru'];
        $sizes = ['S', 'M', 'L'];

        foreach (['head_guard' => 'HG', 'body_protector' => 'BP'] as $type => $prefix) {
            $index = 1;

            foreach ($colors as $color) {
                foreach ($sizes as $size) {
                    $code = sprintf('%s-%s-%02d', $prefix, substr($color, 0, 1), $index);

                    Equipment::firstOrCreate(
                        ['code' => $code],
                        [
                            'type' => $type,
                            'color' => $color,
                            'size' => $size,
                            'status' => 'available',
                        ]
                    );

                    $index++;
                }
            }
        }
    }

    protected function seedAthletes(array $categories, array $contingents): void
    {
        $plan = [
            'Daeryun Putra' => [
                ['Bima Sakti', 'male', 'ITERA'],
                ['Dimas Prasetyo', 'male', 'SBY'],
                ['Eka Firmansyah', 'male', 'MLG'],
                ['Fajar Nugroho', 'male', 'BDG'],
                ['Rizky Pratama', 'male', 'JKT'],
                ['Yoga Saputra', 'male', 'LPG'],
                ['Ilham Maulana', 'male', 'ITERA'],
                ['Hendra Gunawan', 'male', 'SBY'],
            ],
            'Daeryun Putri' => [
                ['Tiara Ayu Lestari', 'female', 'ITERA'],
                ['Sinta Maharani', 'female', 'SBY'],
                ['Rani Puspita', 'female', 'BDG'],
                ['Dewi Anggraini', 'female', 'JKT'],
            ],
            'Hyung Putri' => [
                ['Citra Permata', 'female', 'ITERA'],
                ['Mega Puspita', 'female', 'LPG'],
                ['Nora Vans', 'female', 'MLG'],
                ['Ratna Sari', 'female', 'BDG'],
                ['Vina Oktaviani', 'female', 'SBY'],
            ],
            'Hosinsul' => [
                ['Dinda Ayu', 'female', 'MLG'],
                ['Kenneth Miles', 'male', 'JKT'],
                ['Marcus Reed', 'male', 'BDG'],
                ['Daniel Cooper', 'male', 'SBY'],
            ],
            'Nakbop' => [
                ['Agus Salim', 'male', 'ITERA'],
                ['Ilham Ridwan', 'male', 'LPG'],
            ],
            'Lompat Tinggi' => [
                ['Sinta Dewi', 'female', 'LPG'],
                ['Vina Larasati', 'female', 'ITERA'],
            ],
            'Seni Senjata' => [
                ['Bagas Wicaksono', 'male', 'SBY'],
                ['Teguh Iman', 'male', 'JKT'],
            ],
        ];

        $counter = 1;

        foreach ($plan as $categoryName => $athletes) {
            foreach ($athletes as $athlete) {
                Athlete::firstOrCreate(
                    ['participant_number' => sprintf('P%03d', $counter)],
                    [
                        'name' => $athlete[0],
                        'gender' => $athlete[1],
                        'birth_date' => now()->subYears(17 + ($counter % 9))->toDateString(),
                        'id_number' => sprintf('32%010d', 100000 + $counter),
                        'contingent_id' => $contingents[$athlete[2]]->id,
                        'category_id' => $categories[$categoryName]->id,
                        'qr_code' => sprintf('QR%04d', $counter),
                        'status' => 'active',
                    ]
                );

                $counter++;
            }
        }
    }

    protected function seedArtSchedules(array $categories): void
    {
        $plans = [
            ['category' => 'Hyung Putri', 'arena' => 'B', 'match_no' => '008', 'order' => 8],
            ['category' => 'Hosinsul', 'arena' => 'C', 'match_no' => '005', 'order' => 5],
            ['category' => 'Nakbop', 'arena' => 'B', 'match_no' => '012', 'order' => 12],
        ];

        foreach ($plans as $plan) {
            $category = $categories[$plan['category']];
            $arena = Arena::where('name', $plan['arena'])->first();

            $schedule = Schedule::firstOrCreate(
                ['match_no' => $plan['match_no']],
                [
                    'category_id' => $category->id,
                    'arena_id' => $arena?->id,
                    'type' => 'art',
                    'round' => 'penampilan',
                    'order_no' => $plan['order'],
                    'match_date' => now()->toDateString(),
                    'start_time' => sprintf('%02d:00:00', 9 + $plan['order'] % 6),
                    'end_time' => sprintf('%02d:00:00', 10 + $plan['order'] % 6),
                    'status' => $plan['order'] <= 8 ? 'pending' : 'pending',
                ]
            );

            $athletes = $category->athletes()->where('status', 'active')->orderBy('id')->get();

            foreach ($athletes as $index => $athlete) {
                $schedule->performances()->firstOrCreate(
                    ['athlete_id' => $athlete->id],
                    ['order_no' => $index + 1, 'status' => 'waiting']
                );
            }
        }
    }

    protected function seedBracket(array $categories): void
    {
        $service = app(BracketService::class);

        foreach (['Daeryun Putra', 'Daeryun Putri'] as $name) {
            $service->generate($categories[$name]);
        }

        // Jadwal awal (arena & jam) untuk match pertama daeryun
        $arena = Arena::where('name', 'A')->first();

        Schedule::where('type', 'daeryun')
            ->where('category_id', $categories['Daeryun Putra']->id)
            ->orderBy('id')
            ->take(4)
            ->get()
            ->each(function ($schedule, $index) use ($arena) {
                $schedule->update([
                    'arena_id' => $arena?->id,
                    'start_time' => sprintf('%02d:00:00', 9 + $index),
                    'end_time' => sprintf('%02d:00:00', 10 + $index),
                ]);
            });
    }
}
