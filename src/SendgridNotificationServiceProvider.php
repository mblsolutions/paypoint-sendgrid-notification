<?php

namespace MBLSolutions\SendgridNotification;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use MBLSolutions\SendgridNotification\Console\CreateNotificationLogsTableCommand;
use MBLSolutions\SendgridNotification\Http\LoggingHttpClient;
use MBLSolutions\SendgridNotification\Mailer\SendgridNotificationTransport;
use Symfony\Component\Mailer\Bridge\Sendgrid\Transport\SendgridTransportFactory;


class SendgridNotificationServiceProvider extends ServiceProvider
{
    public function register()
    {
        if (config('mail.default') === 'sendgrid') {
            
            //To log any outgoing request to SendGrid API
            $this->app->singleton(HttpClientInterface::class, function () {
                return new LoggingHttpClient(HttpClient::create(['timeout' => config('notification.timeout')]));
            });
            
            $this->app->singleton(SendgridTransportFactory::class, function ($app) {
                // The container will automatically inject the LoggingHttpClient here.
                return new SendgridTransportFactory(
                    null,
                    $app->make(HttpClientInterface::class)
                );
            });
        }
    }

    public function boot()
    {
        Mail::extend('sendgrid', function (array $config = []) {

            $dsn = Dsn::fromString($config['dsn']);
            
            // Resolve the factory from the container.
            $factory = $this->app->make(SendgridTransportFactory::class);
                    
            $symfonyTransport = ($factory)->create($dsn, $this->app);
            
            return new SendgridNotificationTransport($symfonyTransport);
        });

        // Publish the package config
        $this->publishes([
            __DIR__ . '/../config/notification.php' => config_path('notification.php'),
        ], 'sendgrid-notification-config');

        // Register the command if we are using the application via the CLI
        if ($this->app->runningInConsole()) {
            $this->commands([
                CreateNotificationLogsTableCommand::class,
            ]);
        }
    }
}