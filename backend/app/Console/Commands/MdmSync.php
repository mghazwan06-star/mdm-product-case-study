<?php

namespace App\Console\Commands;

use App\Mdm\MdmSyncService;
use App\Mdm\ProviderRegistry;
use Illuminate\Console\Command;
use InvalidArgumentException;

/**
 * Runs a sync from the command line, with the same sync service as the website's Sync Now button.
 */
class MdmSync extends Command
{
    protected $signature = 'mdm:sync {provider=jamf : The MDM provider to sync from}';

    protected $description = 'Sync assigned devices from an MDM provider';

    public function handle(MdmSyncService $service, ProviderRegistry $providers): int
    {
        try {
            $provider = $providers->get($this->argument('provider'));
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $result = $service->sync($provider);

        if (! $result->succeeded()) {
            $this->error($result->message);
            if ($result->error !== null) {
                $this->line('Reason: '.$result->error);
            }

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
