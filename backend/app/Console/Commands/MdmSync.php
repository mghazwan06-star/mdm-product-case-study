<?php

namespace App\Console\Commands;

use App\Mdm\Contracts\MdmProvider;
use App\Mdm\MdmSyncService;
use Illuminate\Console\Command;

/**
 * Runs a sync from the command line (the website button arrives in step 4.5).
 */
class MdmSync extends Command
{
    protected $signature = 'mdm:sync {provider=jamf : The MDM provider to sync from}';

    protected $description = 'Sync assigned devices from an MDM provider';

    public function handle(MdmSyncService $service): int
    {
        $class = config('mdm.providers.'.$this->argument('provider'));

        if ($class === null) {
            $this->error('Unknown provider: '.$this->argument('provider'));

            return self::FAILURE;
        }

        /** @var MdmProvider $provider */
        $provider = app($class);
        $result = $service->sync($provider);

        if (! $result->succeeded()) {
            $this->error($result->message);
            $this->line('Reason: '.$result->error);

            return self::FAILURE;
        }

        $this->info("Sync from {$provider->label()} complete.");
        $this->table(['Count', 'Value'], collect($result->counts)->map(fn ($value, $key) => [$key, $value])->values());

        foreach ($result->skipped as $skip) {
            $which = $skip['serial'] ?? ($skip['external_id'] !== null ? "{$provider->label()} ID {$skip['external_id']}" : 'an entry');
            $this->line("Skipped {$which}: {$skip['reason']}");
        }

        return self::SUCCESS;
    }
}
