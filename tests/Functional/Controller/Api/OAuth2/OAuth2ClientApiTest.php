<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Security;

use App\Tests\WebTestCase;

class OAuth2ClientApiTest extends WebTestCase
{
    public function testApiCanCreateWorkingClient(): void
    {
        $client = self::createClient();

        $requestData = [
            'name' => '/kbin API Created Test Client',
            'description' => 'An OAuth2 client for testing purposes, created via the API',
            'contactEmail' => 'test@kbin.test',
            'redirectUris' => [
                'https://localhost:3002'
            ],
            'grants' => [
                'authorization_code',
                'refresh_token',
            ],
            'scopes' => [
                'read',
                'write',
                'admin:oauth_clients:read',
            ],
        ];

        $client->jsonRequest('POST', '/api/client', $requestData);

        self::assertResponseIsSuccessful();
        $response = $client->getResponse();

        self::assertJson($response->getContent());

        $clientData = json_decode($response->getContent(), associative: true);
        self::assertIsArray($clientData);
        self::assertArrayHasKey('identifier', $clientData);
        self::assertArrayHasKey('secret', $clientData);
        self::assertNotNull($clientData['secret']);
        self::assertArrayHasKey('name', $clientData);
        self::assertEquals($requestData['name'], $clientData['name']);
        self::assertArrayHasKey('contactEmail', $clientData);
        self::assertEquals($requestData['contactEmail'], $clientData['contactEmail']);
        self::assertArrayHasKey('description', $clientData);
        self::assertEquals($requestData['description'], $clientData['description']);
        self::assertArrayHasKey('user', $clientData);
        self::assertNull($clientData['user']);
        self::assertArrayHasKey('redirectUris', $clientData);
        self::assertIsArray($clientData['redirectUris']);
        self::assertEquals($requestData['redirectUris'], $clientData['redirectUris']);
        self::assertArrayHasKey('grants', $clientData);
        self::assertIsArray($clientData['grants']);
        self::assertEquals($requestData['grants'], $clientData['grants']);
        self::assertArrayHasKey('scopes', $clientData);
        self::assertIsArray($clientData['scopes']);
        self::assertEquals($requestData['scopes'], $clientData['scopes']);
        self::assertArrayHasKey('image', $clientData);
        self::assertNull($clientData['image']);

        $client->loginUser($this->getUserByUsername('JohnDoe'));

        $jsonData = self::getAuthorizationCodeTokenResponse(
            $client,
            clientId: $clientData['identifier'],
            clientSecret: $clientData['secret'],
            redirectUri: $clientData['redirectUris'][0],
        );

        self::assertResponseIsSuccessful();
        self::assertIsArray($jsonData);
    }
}
