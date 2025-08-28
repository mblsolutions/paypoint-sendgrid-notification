<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use MBLSolutions\SendgridNotification\Jobs\CreateNotificationLog;
use MBLSolutions\SendgridNotification\Tests\LaravelTestCase;
use PHPUnit\Util\Test;

class CreateNotificationLogTest extends LaravelTestCase
{
    protected $mockHttpClient;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Temporarily override the config value
        Config::set('notification.protected_keys', [
            'password',
            'password_confirmation',
        ]);
        Config::set('notification.max_loggable_length', 10024);
        Config::set('mail.mailers.sendgrid.dsn',env('SENDGRID_DSN'));

        $this->mockHttpClient = $this->getMockHttpClient();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function test_logging_successful_paypoint_notification_in_database(): void
    {
        $message_id = Str::uuid();
        $method = 'POST';
        $url = 'http://api.test.sendgrid.com';
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
        
        $response = $this->mockHttpClient->request($method, $url, $options);
        
        dispatch(new CreateNotificationLog($message_id,$request,$response));

        $this->assertDatabaseHas(config('notification.database.table'), [
            'id' => $message_id,
            'method' => 'POST',
            'status' => 202,
        ]);
    }

    #[Test]
    public function test_logging_failed_paypoint_notification_in_database(): void
    {
        $message_id = Str::uuid();
        $method = 'POST';
        $url = 'http://api.test.sendgrid.com';
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
        
        $response = $this->mockHttpClient->request($method, $url, $options);
        
        dispatch(new CreateNotificationLog($message_id,$request,$response));

        $this->assertDatabaseHas(config('notification.database.table'), [
            'id' => $message_id,
            'method' => 'POST',
            'status' => 401,
        ]);
    }
}