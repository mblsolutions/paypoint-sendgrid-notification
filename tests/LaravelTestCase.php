<?php

namespace MBLSolutions\SendgridNotification\Tests;

use Mockery;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Config;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as OTBTestCase;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class LaravelTestCase extends OTBTestCase
{
    use RefreshDatabase;

    protected $mockSuccessResponse;
    protected $mockFailureResponse;

    protected function getMockHttpClient(): HttpClientInterface
    {
        // Mock a successful 202 Accepted response with an empty body
        $this->mockSuccessResponse = Mockery::mock(ResponseInterface::class);
        $this->mockSuccessResponse->shouldReceive('getInfo')->with('debug')->andReturn('');
        $this->mockSuccessResponse->shouldReceive('getHeaders')
                ->with(false)
                ->andReturn([
                    'x-message-id' => [Str::uuid()],
                ]);
        $this->mockSuccessResponse->shouldReceive('getStatusCode')->andReturn(202);
        $this->mockSuccessResponse->shouldReceive('getContent')->with(false)->andReturn('');
        $this->mockSuccessResponse->shouldReceive('toArray')->with(false)->andReturn([]);

        // Mock a failed 401 Unauthorized response with a JSON error body
        $this->mockFailureResponse = Mockery::mock(ResponseInterface::class);
        $this->mockFailureResponse->shouldReceive('getInfo')->with('debug')->andReturn('');
        $this->mockFailureResponse->shouldReceive('getStatusCode')->andReturn(401);
        $this->mockFailureResponse->shouldReceive('getHeaders')->andReturn(['content-type' => ['application/json']]);
        $this->mockFailureResponse->shouldReceive('getContent')->andReturn('{"errors": [{"message": "The provided authorization grant is invalid, expired, or revoked" ,field": null,"help": null}]}');
        $this->mockFailureResponse->shouldReceive('toArray')->andReturn(['errors' => [['message' => 'The provided authorization grant is invalid, expired, or revoked',
                                                                                    'field' => null,
                                                                                    'help' => null,
                                                                                ]]]);
        
        // Create a mock of the Guzzle client
        $mockHttpClient = Mockery::mock(HttpClientInterface::class);
            
        // Configure the client mock to return the response mock
        $mockHttpClient->shouldReceive('request')
                        ->andReturnUsing(function ($method, $url, $options) {
                            //check the secret key
                            if (!\str_contains(env('SENDGRID_DSN'),$options['auth_bearer'])){
                                return $this->mockFailureResponse;
                            }
                            // Otherwise, return the successful mock
                            return $this->mockSuccessResponse;
                        });
        return $mockHttpClient;
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // Set up the database name and load the migrations in database for test cases     
        Config::set('notification.database.table', 'notification_logs');
        $this->app = $app;
        $this->loadMigrationsFrom(__DIR__ . '/../src/Console/stubs');
    }

}