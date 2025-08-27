<?php

namespace MBLSolutions\SendgridNotification\Tests\Unit\Http;

use Mockery;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use MBLSolutions\SendgridNotification\Http\LoggingHttpClient;
use MBLSolutions\SendgridNotification\Jobs\CreateNotificationLog;
use MBLSolutions\SendgridNotification\Tests\LaravelTestCase;
use PHPUnit\Util\Test;

class LoggingHttpClientTest extends LaravelTestCase
{
    protected $mockHttpClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockHttpClient = $this->getMockHttpClient();

        // Temporarily override the config value
        Config::set('notification.protected_keys', [
            'password',
            'password_confirmation',
        ]);
        Config::set('notification.max_loggable_length', 10024);
        Config::set('mail.mailers.sendgrid.dsn',env('SENDGRID_DSN'));
        
    }

    protected function tearDown(): void
    {
        // Clean up after each test
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function test_dispatch_successful_paypoint_notification_in_database(): void
    {
        Queue::fake();
        
        $logClient = new LoggingHttpClient($this->getMockHttpClient());

        $message_id = Str::uuid()->toString();
        $method = 'POST';
        $url = $logClient->getHost();
        $options = [
            "json" => [
                "personalizations" => [
                    [
                        "to" => [
                            [
                            "email" => "receipient@example.com"
                            ]
                        ],
                        "subject" => "Test",
                        "custom_args" => [
                            "unique_email_identifier" => $message_id
                        ]
                    ]
                ],
                "from" => [
                    "email" => "sender@example.com"
                ],
                "content" => [
                    [
                        "type" => "text/plain",
                        "value" => "Hello"
                    ]
                ]
            ],
            "auth_bearer" => "secretkey"
        ];
        $request = [
            'method' => $method,
            'url' => $url,
            'request_body' => \json_encode($options['json']) ?? null,
        ];
        
        $logClient->request($method, $url, $options);

        Queue::assertPushed(CreateNotificationLog::class, function ($event) use ($message_id) {
            return $event->data['id'] === $message_id &&
                   $event->data['method'] === 'POST' &&
                   $event->data['status'] === 202;
        });
    }
    
    #[Test]
    public function test_failed_dispatch_paypoint_notification_in_database(): void
    {
        Queue::fake();

        $logClient = new LoggingHttpClient($this->getMockHttpClient());

        $message_id = Str::uuid()->toString();
        $method = 'POST';
        $url = 'http://api.test.sengrid.com';
        $options = [
            "json" => [
                "personalizations" => [
                    [
                        "to" => [
                            [
                            "email" => "receipient@example.com"
                            ]
                        ],
                        "subject" => "Test",
                        "custom_args" => [
                            "unique_email_identifier" => $message_id
                        ]
                    ]
                ],
                "from" => [
                    "email" => "sender@example.com"
                ],
                "content" => [
                    [
                        "type" => "text/plain",
                        "value" => "Hello"
                    ]
                ]
            ],
            "auth_bearer" => "fakekey"
        ];
        $request = [
            'method' => $method,
            'url' => $url,
            'request_body' => \json_encode($options['json']) ?? null,
        ];
        
        $logClient->request($method, $url, $options);

        Queue::assertNotPushed(CreateNotificationLog::class);
    }
}