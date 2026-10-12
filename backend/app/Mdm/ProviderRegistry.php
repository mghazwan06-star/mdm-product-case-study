<?php

namespace App\Mdm;

use App\Mdm\Contracts\MdmProvider;
use InvalidArgumentException;

/**
 * Looks up the MDM providers listed in config/mdm.php.
 */
final class ProviderRegistry
{
    /**
     * @throws InvalidArgumentException for a provider that isn't listed
     */
    public function get(?string $name = null): MdmProvider
    {
        $name ??= config('mdm.default_provider');
        $class = config("mdm.providers.{$name}");

        if ($class === null) {
            throw new InvalidArgumentException("Unknown MDM provider: {$name}");
        }

        return app($class);
    }
}
