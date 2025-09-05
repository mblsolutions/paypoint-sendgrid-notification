<?php

namespace MBLSolutions\SendgridNotification\Http;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;
use Symfony\Component\Mailer\Transport\Dsn;
use MBLSolutions\SendgridNotification\Jobs\CreateNotificationLog;

class LoggingHttpClient implements HttpClientInterface
{
    private const HOST = 'api.%region_dot%sendgrid.com';

    private HttpClientInterface $client;

    private String $host;

    public function __construct($client)
    {
        $this->client = $client;
        $dsn = Dsn::fromString(config('mail.mailers.sendgrid.dsn'));
        $host = 'default' === $dsn->getHost() ? null : $dsn->getHost();
        $region = $dsn->getOption('region');
        $host = $host ?: str_replace('%region_dot%', '', self::HOST);
        if (null !== $region && null === $host) {
            $host = str_replace('%region_dot%', $region.'.', self::HOST);
        }
        $this->host = $host;
    }

    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        
        $response = $this->client->request($method, $url, $options);
        
        // Add a condition to check for the SendGrid API domain
        if (str_contains($url, $this->host)) {            
            $customArgs = &$options['json']['personalizations'][0]['custom_args'];
            // Get the Auth User Id
            $authUserId = $customArgs['X-Auth-User-Id']??null;
            // Remove 'X-Auth-User-Id' from the original array
            unset($customArgs['X-Auth-User-Id']);
            
            $message_id = $customArgs[config('notification.unique_email_identifier')] ?? Str::uuid();
            // Log the request details here
            $request = [
                'method' => $method,
                'url' => $url,
                'request_body' => \json_encode($options['json']) ?? null,
            ];
            dispatch(new CreateNotificationLog($message_id,$request,$response,$authUserId));                         
        }
         
        return $response;
    }
    
    public function stream(ResponseInterface|iterable $responses, ?float $timeout = null): ResponseStreamInterface
    {
        return $this->client->stream($responses, $timeout);
    }

    public function withOptions(array $options): static
    {
        return $this->client->withOptions($options);
    }

    public function getHost(): string
    {
        return $this->host;
    }
}