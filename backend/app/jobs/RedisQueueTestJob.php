<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RedisQueueTestJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public string $message = 'Redis queue test job')
    {
    }

    public function handle(): void
    {
        $payload = [
            'message' => $this->message,
            'processed_at' => now()->toDateTimeString(),
            'job' => self::class,
        ];

        Cache::put('redis_queue_test:last_processed', $payload, now()->addMinutes(30));

        Log::info('RedisQueueTestJob processed.', $payload);
    }
}
