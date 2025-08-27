<?php

namespace MBLSolutions\SendgridNotification\Jobs;

use Illuminate\Support\Str;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use MBLSolutions\SendgridNotification\Repositories\NotificationLogRepository;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Contracts\HttpClient\ResponseInterface;

class CreateNotificationLog implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $data = [];

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $id, array $request, ResponseInterface $response)
    {
        $auth = Auth::guard(config('notification.auth_guard'));
        
        $this->data = [
            'id' => $id,
            'user_id' => optional($auth->check()? $auth->user(): null)->getKey(),
            'method' => $request['method'],
            'uri' => $request['url'],
            'status' => $response->getStatusCode(),
            'notification_request' => $this->handleBodyContentIfJson($request['request_body']),
            'notification_response' => $this->handleBodyContentIfJson($response->getContent(false)),          
        ];
    }
    
    public static function setUser($user): void
    {
        if (config('notification.auth_guard')!=null){
            $auth = Auth::guard(config('notification.auth_guard'));
            $auth->setUser($user);
        }
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        $this->getNotificationLogRepository()->create($this->data);
    }

    protected function getNotificationLogRepository(): NotificationLogRepository
    {
        return new NotificationLogRepository;
    }

    protected function maskSensitiveData($data, array $protected = [])
    {
        if (is_array($data)) {
            return $this->maskArrayData($data, $protected);
        }

        if ($data instanceof Collection) {
            return new Collection($this->maskArrayData($data->toArray(), $protected));
        }

        if ($this->isJson($data)) {
            return $this->maskJsonData($data, $protected);
        }
        
        return $data;
    }

    protected function maskJsonData(string $data, array $protected): string
    {
        try {
            $decoded = json_decode($data, true, config('notification.max_loggable_length'), JSON_THROW_ON_ERROR);

            $sanitised = $this->maskArrayData($decoded, $protected);

            return json_encode($sanitised, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {

        }

        return $data;
    }

    protected function maskArrayData(array $data, array $protected): array
    {
        foreach ($data as $key => $datum) {
            if (is_array($datum)) {
                $data[$key] = $this->maskArrayData($datum, $protected);
            }
            if (in_array($key, $protected, true)) {
                $data[$key] = $this->mask($datum);
            }
        }

        return $data;
    }

    protected function mask($datum): string
    {
        if (is_string($datum)) {
            return Str::limit(str_repeat('*', strlen($datum)), 32);
        }

        return '****';
    }

    protected function isJson($data): bool
    {
        json_decode($data);

        return json_last_error() === JSON_ERROR_NONE;
    }

    protected function handleBodyContentIfJson($content): ?string
    {
        if ($this->isJson($content)) {            
            return encrypt($this->convertDataToJson($content));            
        }

        return null;
    }

    protected function convertDataToJson($data = null): ?string
    {
        try {
            if ($data !== null) {
                $sanitised = $this->maskSensitiveData($data, config('notification.protected_keys'));
                
                if (is_string($sanitised)) {
                    $sanitised = Str::limit($sanitised, config('notification.max_loggable_length'));
                }

                return json_encode($sanitised, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
            }
        } catch (JsonException $e) {}

        return null;
    }
}