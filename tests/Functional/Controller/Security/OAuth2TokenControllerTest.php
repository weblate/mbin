<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Security;

use App\Tests\WebTestCase;

class OAuth2TokenControllerTest extends WebTestCase
{
    public function testCanGetTokenWithValidClientCredentials(): void
    {
        $client = self::createClient();
        self::createOAuth2ClientCredsClient();

        $client->request('POST', '/token', [
            'grant_type' => 'client_credentials',
            'client_id' => 'testclient',
            'client_secret' => 'testsecret',
            'scope' => 'read write',
        ]);

        $response = $client->getResponse();

        self::assertJson($response->getContent());
        $jsonData = json_decode($response->getContent(), associative: true);

        self::assertIsArray($jsonData);
        self::assertArrayHasKey('token_type', $jsonData);
        self::assertEquals('Bearer', $jsonData['token_type']);
        self::assertArrayHasKey('expires_in', $jsonData);
        self::assertIsInt($jsonData['expires_in']);
        self::assertArrayHasKey('access_token', $jsonData);
        self::assertMatchesRegularExpression(self::JWT_REGEX, $jsonData['access_token']);
        self::assertArrayNotHasKey('refresh_token', $jsonData);
    }

    public function testCanGetTokenWithValidAuthorizationCode(): void
    {
        $client = self::createClient();
        $client->loginUser($this->getUserByUsername('JohnDoe'));
        self::createOAuth2AuthCodeClient();

        $jsonData = self::getAuthorizationCodeTokenResponse($client);

        self::assertResponseIsSuccessful();
        self::assertIsArray($jsonData);
        self::assertArrayHasKey('token_type', $jsonData);
        self::assertEquals('Bearer', $jsonData['token_type']);
        self::assertArrayHasKey('expires_in', $jsonData);
        self::assertIsInt($jsonData['expires_in']);
        self::assertArrayHasKey('access_token', $jsonData);
        self::assertMatchesRegularExpression(self::JWT_REGEX, $jsonData['access_token']);
        self::assertArrayHasKey('refresh_token', $jsonData);
        self::assertMatchesRegularExpression(self::CODE_REGEX, $jsonData['refresh_token']);
    }

    public function testCanGetTokenWithValidAuthorizationCodePKCE(): void
    {
        $client = self::createClient();
        $client->loginUser($this->getUserByUsername('JohnDoe'));
        self::createOAuth2PublicAuthCodeClient();

        $jsonData = self::getPublicAuthorizationCodeTokenResponse($client);

        self::assertResponseIsSuccessful();
        self::assertIsArray($jsonData);
        self::assertArrayHasKey('token_type', $jsonData);
        self::assertEquals('Bearer', $jsonData['token_type']);
        self::assertArrayHasKey('expires_in', $jsonData);
        self::assertIsInt($jsonData['expires_in']);
        self::assertArrayHasKey('access_token', $jsonData);
        self::assertMatchesRegularExpression(self::JWT_REGEX, $jsonData['access_token']);
        self::assertArrayHasKey('refresh_token', $jsonData);
        self::assertMatchesRegularExpression(self::CODE_REGEX, $jsonData['refresh_token']);
    }

    public function testCannotGetTokenWithInvalidVerifierAuthorizationCodePKCE(): void
    {
        $client = self::createClient();
        $client->loginUser($this->getUserByUsername('JohnDoe'));
        self::createOAuth2PublicAuthCodeClient();

        $pkceCodes = self::runPublicAuthorizationCodeFlow($client, 'yes');
        self::runPublicAuthorizationCodeTokenFetch($client, $pkceCodes['verifier'].'fail');

        $response = $client->getResponse();

        self::assertJson($response->getContent());

        $jsonData = json_decode($response->getContent(), associative: true);

        self::assertResponseStatusCodeSame(400);
        self::assertIsArray($jsonData);
        self::assertArrayHasKey('error', $jsonData);
        self::assertEquals('invalid_grant', $jsonData['error']);
        self::assertArrayHasKey('error_description', $jsonData);
        self::assertArrayHasKey('hint', $jsonData);
        self::assertArrayHasKey('message', $jsonData);
    }

    public function testCannotGetTokenWithoutChallengeAuthorizationCodePKCE(): void
    {
        $client = self::createClient();
        $client->loginUser($this->getUserByUsername('JohnDoe'));
        self::createOAuth2PublicAuthCodeClient();

        $query = self::buildPrivateAuthCodeQuery('testpublicclient', 'read write', 'oauth2state', 'https://localhost:3001');

        $uri = '/authorize?'.$query;

        $client->request('GET', $uri);

        $response = $client->getResponse();

        self::assertJson($response->getContent());

        $jsonData = json_decode($response->getContent(), associative: true);

        self::assertResponseStatusCodeSame(400);
        self::assertIsArray($jsonData);
        self::assertArrayHasKey('error', $jsonData);
        self::assertEquals('invalid_request', $jsonData['error']);
        self::assertArrayHasKey('error_description', $jsonData);
        self::assertArrayHasKey('hint', $jsonData);
        self::assertStringContainsStringIgnoringCase('code challenge', $jsonData['hint']);
        self::assertArrayHasKey('message', $jsonData);
    }

    public function testReceiveErrorWithInvalidAuthorizationCode(): void
    {
        $client = self::createClient();
        $client->loginUser($this->getUserByUsername('JohnDoe'));
        self::createOAuth2AuthCodeClient();

        $client->request('POST', '/token', [
            'grant_type' => 'authorization_code',
            'client_id' => 'testclient',
            'client_secret' => 'testsecret',
            'code' => 'deadbeefc0de',
            'redirect_uri' => 'https://localhost:3001',
        ]);

        self::assertResponseStatusCodeSame(400);

        $response = $client->getResponse();

        self::assertJson($response->getContent());
        $jsonData = json_decode($response->getContent(), associative: true);

        self::assertIsArray($jsonData);
        self::assertArrayHasKey('error', $jsonData);
        self::assertEquals('invalid_request', $jsonData['error']);
        self::assertArrayHasKey('error_description', $jsonData);
        self::assertArrayHasKey('hint', $jsonData);
        self::assertArrayHasKey('message', $jsonData);
    }

    public function testReceiveErrorWithInvalidClientId(): void
    {
        $client = self::createClient();
        $client->loginUser($this->getUserByUsername('JohnDoe'));
        self::createOAuth2AuthCodeClient();

        $jsonData = self::getAuthorizationCodeTokenResponse($client, clientId: 'testclientfake');

        self::assertResponseStatusCodeSame(401);

        self::assertIsArray($jsonData);
        self::assertArrayHasKey('error', $jsonData);
        self::assertEquals('invalid_client', $jsonData['error']);
        self::assertArrayHasKey('error_description', $jsonData);
        self::assertArrayHasKey('message', $jsonData);
    }

    public function testReceiveErrorWithInvalidClientSecret(): void
    {
        $client = self::createClient();
        $client->loginUser($this->getUserByUsername('JohnDoe'));
        self::createOAuth2AuthCodeClient();

        $jsonData = self::getAuthorizationCodeTokenResponse($client, clientSecret: 'testsecretfake');

        self::assertResponseStatusCodeSame(401);

        self::assertIsArray($jsonData);
        self::assertArrayHasKey('error', $jsonData);
        self::assertEquals('invalid_client', $jsonData['error']);
        self::assertArrayHasKey('error_description', $jsonData);
        self::assertArrayHasKey('message', $jsonData);
    }

    public function testReceiveErrorWithInvalidRedirectUri(): void
    {
        $client = self::createClient();
        $client->loginUser($this->getUserByUsername('JohnDoe'));
        self::createOAuth2AuthCodeClient();

        $jsonData = self::getAuthorizationCodeTokenResponse($client, redirectUri: 'https://invalid.com');

        self::assertResponseStatusCodeSame(401);

        self::assertIsArray($jsonData);
        self::assertArrayHasKey('error', $jsonData);
        self::assertEquals('invalid_client', $jsonData['error']);
        self::assertArrayHasKey('error_description', $jsonData);
        self::assertArrayHasKey('message', $jsonData);
    }
}
