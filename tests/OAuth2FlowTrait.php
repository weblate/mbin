<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;

trait OAuth2FlowTrait
{
    protected const JWT_REGEX = '/[0-9a-zA-Z\-_]+(\.[0-9a-zA-Z\-_]+){2}/';
    protected const CODE_REGEX = '/[0-9a-f]{104,}/';

    protected static function runAuthorizationCodeFlowToConsentPage(KernelBrowser $client, string $scopes, string $state): void
    {
        $client->request('GET', "/authorize?response_type=code&client_id=testclient&redirect_uri=https://localhost:3001&scope=$scopes&state=$state");

        $urlEncodedScopes = implode('%20', explode(' ', $scopes));
        $redirectUri = "/consent?response_type=code&client_id=testclient&redirect_uri=https://localhost:3001&scope=$urlEncodedScopes&state=$state";
        // Should already be logged in due to loginUser call above
        self::assertResponseRedirects($redirectUri);
        $client->followRedirect();
    }

    protected static function runAuthorizationCodeFlowToRedirectUri(KernelBrowser $client, string $scopes, string $consent, string $state): void
    {
        $crawler = $client->getCrawler();

        $client->submit(
            $crawler->selectButton('consent')->form(
                [
                    'consent' => $consent
                ]
            )
        );

        $urlEncodedScopes = implode('%20', explode(' ', $scopes));
        $redirectUri = "/authorize?response_type=code&client_id=testclient&redirect_uri=https://localhost:3001&scope=$urlEncodedScopes&state=$state";

        self::assertResponseRedirects($redirectUri);

        $client->followRedirect();

        self::assertResponseRedirects();
    }

    public static function runAuthorizationCodeFlow(KernelBrowser $client, string $consent = 'yes', string $scopes = 'read write', string $state = 'oauth2state'): void
    {
        self::runAuthorizationCodeFlowToConsentPage($client, $scopes, $state);
        self::runAuthorizationCodeFlowToRedirectUri($client, $scopes, $consent, $state);
    }

    public static function getAuthorizationCodeTokenResponse(KernelBrowser $client, string $clientId = 'testclient', string $clientSecret = 'testsecret', string $redirectUri = 'https://localhost:3001', string $scopes = 'read write'): array
    {
        self::runAuthorizationCodeFlow($client, 'yes', $scopes);

        $response = $client->getResponse();
        $parsedUrl = parse_url($response->headers->get('Location'));

        $result = [];
        parse_str($parsedUrl['query'], $result);

        self::assertArrayHasKey('code', $result);
        self::assertMatchesRegularExpression(self::CODE_REGEX, $result['code']);
        self::assertArrayHasKey('state', $result);
        self::assertEquals('oauth2state', $result['state']);

        $client->request('POST', '/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'code' => $result['code'],
            'redirect_uri' => $redirectUri,
        ]);

        $response = $client->getResponse();

        self::assertJson($response->getContent());
        return json_decode($response->getContent(), associative: true);
    }
}
