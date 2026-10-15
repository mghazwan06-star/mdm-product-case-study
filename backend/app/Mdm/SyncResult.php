<?php

namespace App\Mdm;

/**
 * What one sync did: the source of the result message and, later, the sync history.
 */
final readonly class SyncResult
{
    public const STATUS_SUCCESS = 'success';

    public const STATUS_FAILED = 'failed';

    public const STATUS_REFUSED = 'refused';

    /**
     * @param  array<string, int>  $counts
     * @param  list<array{serial: ?string, external_id: ?string, reason: string}>  $skipped
     * @param  list<string>  $seenSerials  every readable serial in the list (for "No longer in Jamf" later)
     * @param  list<array{asset_id: int, status: string, device_name: string, person_name: ?string}>  $needsAttention
     *                                                                                                                 laptops that newly need a person in this sync
     */
    public function __construct(
        public string $status,
        public string $provider,
        public ?string $message,
        public ?string $error,
        public array $counts,
        public array $skipped,
        public array $seenSerials = [],
        public bool $isFirstSync = false,
        public ?int $syncRunId = null,
        public array $needsAttention = [],
        public bool $listWasEmpty = false,
    ) {}

    public static function failed(string $provider, string $message, ?string $error, ?int $syncRunId = null): self
    {
        return new self(self::STATUS_FAILED, $provider, $message, $error, [], [], syncRunId: $syncRunId);
    }

    public static function refused(string $provider, string $message, ?int $syncRunId = null): self
    {
        return new self(self::STATUS_REFUSED, $provider, $message, null, [], [], syncRunId: $syncRunId);
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
            'sync_run_id' => $this->syncRunId,
            'is_first_sync' => $this->isFirstSync,
            'list_was_empty' => $this->listWasEmpty,
            'provider' => $this->provider,
            'message' => $this->message,
            'error' => $this->error,
            'counts' => $this->counts,
            'needs_attention' => $this->needsAttention,
            'skipped' => $this->skipped,
        ];
    }
}
