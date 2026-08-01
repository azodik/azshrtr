<?php

namespace Database\Seeders;

use App\Models\Domain;
use Illuminate\Database\Seeder;

class PlatformDomainSeeder extends Seeder
{
    public function run(): void
    {
        $hosts = $this->hostnames();

        foreach ($hosts as $index => $hostname) {
            Domain::query()->updateOrCreate(
                ['hostname' => $hostname],
                [
                    'organization_id' => null,
                    'is_system' => true,
                    'is_primary' => $index === 0,
                    'status' => 'verified',
                    'verified_at' => now(),
                ],
            );
        }
    }

    /**
     * @return list<string>
     */
    private function hostnames(): array
    {
        $configured = strtolower(trim((string) config('azshrtr.domains.root', '')));
        $fromApp = strtolower((string) (parse_url((string) config('app.url'), PHP_URL_HOST) ?: ''));

        $hosts = array_values(array_unique(array_filter([
            $configured !== '' ? $configured : null,
            $fromApp !== '' ? $fromApp : null,
            'localhost',
            '127.0.0.1',
        ])));

        return $hosts !== [] ? $hosts : ['localhost'];
    }
}
