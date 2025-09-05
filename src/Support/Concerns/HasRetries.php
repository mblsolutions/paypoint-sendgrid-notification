<?php

namespace MBLSolutions\SendgridNotification\Support\Concerns;

trait HasRetries
{
    /**
     * The number of times the queued job may be attempted.
     *
     * @var int
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public int $timeout = 60;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int|array
     */
    public int $backoff = 60;

    /**
     * The init method of the trait.
     *
     * @return void
     */
    public function initRetries()
    {
        $this->tries = config('notification.retries.tries', 3);
        $this->timeout = config('notification.retries.timeout', 60);
        $this->backoff = config('notification.retries.backoff', 5);
    }
    
    public function setTries (int $tries)
    {
        $this->tries = $tries;
    }

    public function setTimeout (int $timeout)
    {
        $this->timeout = $timeout;
    }

    public function setBackoff (int $backoff)
    {
        $this->backoff = $backoff;
    }
}