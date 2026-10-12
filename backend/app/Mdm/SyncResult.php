<?php

namespace App\Mdm;

/**
 * What one sync did: the source of the result message and, later, the sync history.
 */
final readonly class SyncResult
{
    public const STATUS_SUCCESS = 'success';

    public const STATUS_FAILED = 'failed';

    /**
     * @param  array<string, int>  $counts
     * @param  list<array{serial: ?string, external_id: ?string, reason: string}>  $skipped
     */
    public function __construct(
        public string $status,
        public string $provider,
        public ?string $message,
        public ?string $error,
        public array $counts,
        public array $skipped,
    ) {}

    public static function failed(string $provider, string $message, string $error): self
    {
        return new self(self::STATUS_FAILED, $provider, $message, $error, [], []);
    }

    public function succeeded(): bool
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'provider' => $this->provider,
            'message' => $this->message,
            'error' => $this->error,
            'counts' => $this->counts,
            'skipped' => $this->skipped,
        ];
    }
}
