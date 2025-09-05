<?php

namespace MBLSolutions\SendgridNotification\Tests\Unit\Services;

use Mockery;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Config;
use MBLSolutions\SendgridNotification\Http\LoggingHttpClient;
use MBLSolutions\SendgridNotification\Jobs\CreateNotificationLog;
use MBLSolutions\SendgridNotification\Mailer\SendgridNotificationTransport;
use MBLSolutions\SendgridNotification\Tests\LaravelTestCase;
use PHPUnit\Util\Test;
use Symfony\Component\Mailer\Bridge\Sendgrid\Transport\SendgridTransportFactory;
use Symfony\Component\Mailer\Exception\HttpTransportException;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Header\MetadataHeader;

class SendgridNotificationTransportTest extends LaravelTestCase
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
        Config::set('notification.unique_email_identifier', env('SENDGRID_EMAIL_IDENTIFIER'));
        
    }

    protected function tearDown(): void
    {
        // Clean up after each test
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function test_successful_send_out_mail_notification()
    {
        $dsn = Dsn::fromString(env('SENDGRID_DSN'));
        $sendgridApiTransport = (new SendgridTransportFactory(null, $this->mockHttpClient))->create($dsn);
        // Instantiate your custom transport with the mocked API transport
        $notificationTransport = new SendgridNotificationTransport($sendgridApiTransport);

        $message = new \Symfony\Component\Mime\Email();
        $message->from('sender@example.com')->to('recipient@example.com')->subject('Test')->text('Test');

        $sentMessage = $notificationTransport->send($message);

        $this->assertNotNull($sentMessage);
        $this->assertEquals(202, $this->mockSuccessResponse->getStatusCode());

    }

    #[Test]
    public function test_failed_send_out_mail_notification()
    {
        $this->expectException(HttpTransportException::class);

        $dsn = Dsn::fromString('sendgrid+api://fakekey@default');
        $sendgridApiTransport = (new SendgridTransportFactory(null, $this->mockHttpClient))->create($dsn);
        // Instantiate your custom transport with the mocked API transport
        $notificationTransport = new SendgridNotificationTransport($sendgridApiTransport);

        $message = new \Symfony\Component\Mime\Email();
        $message->from('sender@example.com')->to('receipient@example.com')->subject('Test')->text('Hello');

        $sentMessage = $notificationTransport->send($message);

        $this->assertNotNull($sentMessage);
        $this->assertEquals(401, $this->mockFailureResponse->getStatusCode());

    }

    #[Test]
    public function test_successful_dispatch_creation_notification_log()
    {
        Queue::fake();
        
        $dsn = Dsn::fromString(env('SENDGRID_DSN'));
        $logClient = new LoggingHttpClient($this->mockHttpClient);
        $sendgridApiTransport = (new SendgridTransportFactory(null, $logClient))->create($dsn);
        // Instantiate your custom transport with the mocked API transport
        $notificationTransport = new SendgridNotificationTransport($sendgridApiTransport);

        $message = new \Symfony\Component\Mime\Email();
        $message->from('sender@example.com')->to('recipient@example.com')->subject('Test')->text('Test');

        $sentMessage = $notificationTransport->send($message);

        $this->assertNotNull($sentMessage);
        $this->assertEquals(202, $this->mockSuccessResponse->getStatusCode());
        
        Queue::assertPushed(CreateNotificationLog::class, function ($event) use ($sentMessage) {
            /**@var Email $email */
            $email = $sentMessage->getOriginalMessage();
            
            $message_id = $email->getHeaders()->get('x-metadata-'.env('SENDGRID_EMAIL_IDENTIFIER'))->getValue();

            return $event->data['id'] === $message_id &&
                   $event->data['method'] === 'POST' &&
                   $event->data['status'] === 202;
        });
    }

    #[Test]
    public function test_failed_dispatch_creation_notification_log()
    {
        Queue::fake();
        $this->expectException(HttpTransportException::class);

        $dsn = Dsn::fromString('sendgrid+api://fakekey@default');
        $logClient = new LoggingHttpClient($this->mockHttpClient);
        $sendgridApiTransport = (new SendgridTransportFactory(null, $logClient))->create($dsn);
        // Instantiate your custom transport with the mocked API transport
        $notificationTransport = new SendgridNotificationTransport($sendgridApiTransport);

        $message = new \Symfony\Component\Mime\Email();
        $message->from('sender@example.com')->to('receipient@example.com')->subject('Test')->text('Hello');

        $sentMessage = $notificationTransport->send($message);

        $this->assertNotNull($sentMessage);
        $this->assertEquals(401, $this->mockFailureResponse->getStatusCode());

        Queue::assertNotPushed(CreateNotificationLog::class);
    }

    #[Test]
    public function test_successful_send_out_mail_notification_with_auth_user()
    {
        Queue::fake();

        $dsn = Dsn::fromString(env('SENDGRID_DSN'));
        $logClient = new LoggingHttpClient($this->mockHttpClient);
        $sendgridApiTransport = (new SendgridTransportFactory(null, $logClient))->create($dsn);
        // Instantiate your custom transport with the mocked API transport
        $notificationTransport = new SendgridNotificationTransport($sendgridApiTransport);

        $user_id = 1;
        $message = new \Symfony\Component\Mime\Email();
        // Create a custom MetadataHeader for auth user
        $metadataHeader = new MetadataHeader(
            'X-Auth-User-Id',
            $user_id
        );
        $message->getHeaders()->add($metadataHeader);          
        $message->from('sender@example.com')->to('recipient@example.com')->subject('Test')->text('Test');

        $sentMessage = $notificationTransport->send($message);

        $this->assertNotNull($sentMessage);
        $this->assertEquals(202, $this->mockSuccessResponse->getStatusCode());
        
        Queue::assertPushed(CreateNotificationLog::class, function ($event) use ($sentMessage) {
            /**@var Email $email */
            $email = $sentMessage->getOriginalMessage();
            
            $user_id = $email->getHeaders()->get('x-metadata-x-auth-user-id')->getValue();

            return $event->data['user_id'] === $user_id &&
                   $event->data['method'] === 'POST' &&
                   $event->data['status'] === 202;
        });

    }

}