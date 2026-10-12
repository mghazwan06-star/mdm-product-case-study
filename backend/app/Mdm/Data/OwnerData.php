<?php

namespace App\Mdm\Data;

/**
 * The person a device is assigned to, in a provider-neutral shape.
 */
final readonly class OwnerData
{
    public function __construct(
        public string $email,
        public string $name,
        public ?string $jobTitle,
        public ?string $phone,
        public ?string $username,
    ) {}
}
