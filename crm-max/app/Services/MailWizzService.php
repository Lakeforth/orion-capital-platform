<?php

namespace App\Services;

use EmsApi\Base;
use EmsApi\Cache\File;
use EmsApi\Config;
use EmsApi\Endpoint\ListSubscribers;

class MailWizzService
{
    private string $apiKey;
    private string $baseUrl;
    private Config $config;

    /**
     * Create a new class instance.
     * @throws \ReflectionException
     */
    public function __construct()
    {
        $this->baseUrl = config('services.mailwizz.api_url');
        $this->apiKey = config('services.mailwizz.api_key');

        $this->config = new Config([
            'apiUrl' => $this->baseUrl,
            'apiKey' => $this->apiKey,

            // components
            'components' => [
                'cache' => [
                    'class' => File::class,
                    'filesPath' => public_path('mailwizz'), // make sure it is writable by webserver
                ]
            ],
        ]);

        Base::setConfig($this->config);
    }

    public function subscribeNewUser(string $listUniqueId, string $email, string $firstName, string $lastName, ?string $phone = ''): object
    {
        $endpoint   = new ListSubscribers();
        $response   = $endpoint->create($listUniqueId, [
            'EMAIL' => $email,
            'FNAME' => $firstName,
            'LNAME' => $lastName,
            'PHONE' => $phone
        ]);

        $responseBody = $response->body;

        return (object) [
            'status' => $responseBody->itemAt('status'),
            'data' => $responseBody->itemAt('data'),
        ];
    }
}
