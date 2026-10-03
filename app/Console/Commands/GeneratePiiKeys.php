<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GeneratePiiKeys extends Command
{
    protected $signature = 'pii:generate-keys';
    protected $description = 'Generate separate 256-bit personnel encryption and HMAC search keys for manual placement in .env.';

    public function handle(): int
    {
        $this->warn('Store these values securely. Do not commit them to source control or paste them into tickets/logs.');
        $this->line('PII_ENCRYPTION_KEY=base64:'.base64_encode(random_bytes(32)));
        $this->line('PII_SEARCH_KEY=base64:'.base64_encode(random_bytes(32)));
        return self::SUCCESS;
    }
}
