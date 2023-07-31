<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Api\User;

use App\DTO\UserSettingsDto;
use App\Entity\User;
use App\Tests\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UserUpdateApiTest extends WebTestCase
{
    public string $kibbyPath;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->kibbyPath = dirname(__FILE__, 5).'/assets/kibby_emoji.png';
    }

    public function testApiCannotUpdateCurrentUserProfileWithoutScope(): void
    {
        $client = self::createClient();
        self::createOAuth2AuthCodeClient();
        $testUser = $this->getUserByUsername('JohnDoe');
        $client->loginUser($testUser);
        $codes = self::getAuthorizationCodeTokenResponse($client, scopes: 'read user:profile:read');

        $client->jsonRequest(
            'PUT', '/api/users/profile',
            parameters: [
                'about' => 'Updated during test'
            ],
            server: ['HTTP_AUTHORIZATION' => $codes['token_type'].' '.$codes['access_token']]
        );
        self::assertResponseStatusCodeSame(403);
    }

    public function testApiCanUpdateCurrentUserProfile(): void
    {
        $client = self::createClient();
        self::createOAuth2AuthCodeClient();
        $testUser = $this->getUserByUsername('JohnDoe');
        $client->loginUser($testUser);
        $codes = self::getAuthorizationCodeTokenResponse($client, scopes: 'read user:profile:edit user:profile:read');

        $client->request('GET', '/api/users/'.(string) $testUser->getId(), server: ['HTTP_AUTHORIZATION' => $codes['token_type'].' '.$codes['access_token']]);
        self::assertResponseIsSuccessful();

        $jsonData = self::getJsonResponse($client);

        self::assertIsArray($jsonData);
        self::assertArrayKeysMatch(UserRetrieveApiTest::USER_RESPONSE_KEYS, $jsonData);
        self::assertSame($testUser->getId(), $jsonData['userId']);
        self::assertNull($jsonData['about']);

        $client->jsonRequest(
            'PUT', '/api/users/profile',
            parameters: [
                'about' => 'Updated during test'
            ],
            server: ['HTTP_AUTHORIZATION' => $codes['token_type'].' '.$codes['access_token']]
        );
        self::assertResponseIsSuccessful();

        $jsonData = self::getJsonResponse($client);

        self::assertIsArray($jsonData);
        self::assertArrayKeysMatch(UserRetrieveApiTest::USER_RESPONSE_KEYS, $jsonData);
        self::assertSame($testUser->getId(), $jsonData['userId']);
        self::assertEquals('Updated during test', $jsonData['about']);

        $client->request('GET', '/api/users/'.(string) $testUser->getId(), server: ['HTTP_AUTHORIZATION' => $codes['token_type'].' '.$codes['access_token']]);
        self::assertResponseIsSuccessful();

        $jsonData = self::getJsonResponse($client);

        self::assertIsArray($jsonData);
        self::assertArrayKeysMatch(UserRetrieveApiTest::USER_RESPONSE_KEYS, $jsonData);
        self::assertSame($testUser->getId(), $jsonData['userId']);
        self::assertEquals('Updated during test', $jsonData['about']);
    }

    public function testApiCannotUpdateCurrentUserSettingsWithoutScope(): void
    {
        $client = self::createClient();
        self::createOAuth2AuthCodeClient();
        $testUser = $this->getUserByUsername('JohnDoe');
        $client->loginUser($testUser);
        $codes = self::getAuthorizationCodeTokenResponse($client, scopes: 'read user:profile:read');

        $settings = (new UserSettingsDto(
            false,
            false,
            false,
            false,
            false,
            false,
            false,
            false,
            false,
            false,
            false,
            User::HOMEPAGE_MOD,
            ['test'],
            ['en']
        ))->jsonSerialize();

        $client->jsonRequest(
            'PUT', '/api/users/settings',
            parameters: $settings,
            server: ['HTTP_AUTHORIZATION' => $codes['token_type'].' '.$codes['access_token']]
        );
        self::assertResponseStatusCodeSame(403);
    }

    public function testApiCanUpdateCurrentUserSettings(): void
    {
        $client = self::createClient();
        self::createOAuth2AuthCodeClient();
        $testUser = $this->getUserByUsername('JohnDoe');
        $client->loginUser($testUser);
        $codes = self::getAuthorizationCodeTokenResponse($client, scopes: 'read user:profile:edit user:profile:read');

        $settings = (new UserSettingsDto(
            false,
            false,
            false,
            false,
            false,
            false,
            false,
            false,
            false,
            false,
            false,
            User::HOMEPAGE_MOD,
            ['test'],
            ['en']
        ))->jsonSerialize();

        $client->jsonRequest(
            'PUT', '/api/users/settings',
            parameters: $settings,
            server: ['HTTP_AUTHORIZATION' => $codes['token_type'].' '.$codes['access_token']]
        );
        self::assertResponseIsSuccessful();

        $jsonData = self::getJsonResponse($client);

        self::assertIsArray($jsonData);
        self::assertArrayKeysMatch(UserRetrieveApiTest::USER_SETTINGS_KEYS, $jsonData);

        self::assertFalse($jsonData['notifyOnNewEntry']);
        self::assertFalse($jsonData['notifyOnNewEntryReply']);
        self::assertFalse($jsonData['notifyOnNewEntryCommentReply']);
        self::assertFalse($jsonData['notifyOnNewPost']);
        self::assertFalse($jsonData['notifyOnNewPostReply']);
        self::assertFalse($jsonData['notifyOnNewPostCommentReply']);
        self::assertFalse($jsonData['hideAdult']);
        self::assertFalse($jsonData['showProfileSubscriptions']);
        self::assertFalse($jsonData['showProfileFollowings']);
        self::assertFalse($jsonData['addMentionsEntries']);
        self::assertFalse($jsonData['addMentionsPosts']);
        self::assertEquals(User::HOMEPAGE_MOD, $jsonData['homepage']);
        self::assertEquals(['test'], $jsonData['featuredMagazines']);
        self::assertEquals(['en'], $jsonData['preferredLanguages']);

        $client->request('GET', '/api/users/settings', server: ['HTTP_AUTHORIZATION' => $codes['token_type'].' '.$codes['access_token']]);
        self::assertResponseIsSuccessful();

        $jsonData = self::getJsonResponse($client);

        self::assertIsArray($jsonData);
        self::assertArrayKeysMatch(UserRetrieveApiTest::USER_SETTINGS_KEYS, $jsonData);

        self::assertFalse($jsonData['notifyOnNewEntry']);
        self::assertFalse($jsonData['notifyOnNewEntryReply']);
        self::assertFalse($jsonData['notifyOnNewEntryCommentReply']);
        self::assertFalse($jsonData['notifyOnNewPost']);
        self::assertFalse($jsonData['notifyOnNewPostReply']);
        self::assertFalse($jsonData['notifyOnNewPostCommentReply']);
        self::assertFalse($jsonData['hideAdult']);
        self::assertFalse($jsonData['showProfileSubscriptions']);
        self::assertFalse($jsonData['showProfileFollowings']);
        self::assertFalse($jsonData['addMentionsEntries']);
        self::assertFalse($jsonData['addMentionsPosts']);
        self::assertEquals(User::HOMEPAGE_MOD, $jsonData['homepage']);
        self::assertEquals(['test'], $jsonData['featuredMagazines']);
        self::assertEquals(['en'], $jsonData['preferredLanguages']);
    }

    public function testApiCannotUpdateCurrentUserAvatarWithoutScope(): void
    {
        $client = self::createClient();
        self::createOAuth2AuthCodeClient();
        $testUser = $this->getUserByUsername('JohnDoe');
        $client->loginUser($testUser);
        $codes = self::getAuthorizationCodeTokenResponse($client, scopes: 'read user:profile:read');

        // Uploading a file appears to delete the file at the given path, so make a copy before upload
        copy($this->kibbyPath, $this->kibbyPath.'.tmp');
        $image = new UploadedFile($this->kibbyPath.'.tmp', 'kibby_emoji.png', 'image/png');

        $client->request(
            'POST', '/api/users/avatar',
            files: ['uploadImage' => $image],
            server: ['HTTP_AUTHORIZATION' => $codes['token_type'].' '.$codes['access_token']]
        );
        self::assertResponseStatusCodeSame(403);
    }

    public function testApiCannotUpdateCurrentUserCoverWithoutScope(): void
    {
        $client = self::createClient();
        self::createOAuth2AuthCodeClient();
        $testUser = $this->getUserByUsername('JohnDoe');
        $client->loginUser($testUser);
        $codes = self::getAuthorizationCodeTokenResponse($client, scopes: 'read user:profile:read');

        // Uploading a file appears to delete the file at the given path, so make a copy before upload
        copy($this->kibbyPath, $this->kibbyPath.'.tmp');
        $image = new UploadedFile($this->kibbyPath.'.tmp', 'kibby_emoji.png', 'image/png');

        $client->request(
            'POST', '/api/users/cover',
            files: ['uploadImage' => $image],
            server: ['HTTP_AUTHORIZATION' => $codes['token_type'].' '.$codes['access_token']]
        );
        self::assertResponseStatusCodeSame(403);
    }

    public function testApiCannotDeleteCurrentUserAvatarWithoutScope(): void
    {
        $client = self::createClient();
        self::createOAuth2AuthCodeClient();
        $testUser = $this->getUserByUsername('JohnDoe');
        $client->loginUser($testUser);
        $codes = self::getAuthorizationCodeTokenResponse($client, scopes: 'read user:profile:read');

        $client->request('DELETE', '/api/users/avatar', server: ['HTTP_AUTHORIZATION' => $codes['token_type'].' '.$codes['access_token']]);
        self::assertResponseStatusCodeSame(403);
    }

    public function testApiCannotDeleteCurrentUserCoverWithoutScope(): void
    {
        $client = self::createClient();
        self::createOAuth2AuthCodeClient();
        $testUser = $this->getUserByUsername('JohnDoe');
        $client->loginUser($testUser);
        $codes = self::getAuthorizationCodeTokenResponse($client, scopes: 'read user:profile:read');

        $client->request('DELETE', '/api/users/cover', server: ['HTTP_AUTHORIZATION' => $codes['token_type'].' '.$codes['access_token']]);
        self::assertResponseStatusCodeSame(403);
    }

    public function testApiCanUpdateAndDeleteCurrentUserAvatar(): void
    {
        $client = self::createClient();
        self::createOAuth2AuthCodeClient();
        $testUser = $this->getUserByUsername('JohnDoe');
        $client->loginUser($testUser);
        $codes = self::getAuthorizationCodeTokenResponse($client, scopes: 'read user:profile:edit user:profile:read');

        // Uploading a file appears to delete the file at the given path, so make a copy before upload
        copy($this->kibbyPath, $this->kibbyPath.'.tmp');
        $image = new UploadedFile($this->kibbyPath.'.tmp', 'kibby_emoji.png', 'image/png');

        $client->request(
            'POST', '/api/users/avatar',
            files: ['uploadImage' => $image],
            server: ['HTTP_AUTHORIZATION' => $codes['token_type'].' '.$codes['access_token']]
        );
        self::assertResponseIsSuccessful();

        $jsonData = self::getJsonResponse($client);

        self::assertIsArray($jsonData);
        self::assertArrayKeysMatch(UserRetrieveApiTest::USER_RESPONSE_KEYS, $jsonData);
        
        self::assertIsArray($jsonData['avatar']);
        self::assertArrayKeysMatch(self::IMAGE_KEYS, $jsonData['avatar']);
        self::assertSame(96, $jsonData['avatar']['width']);
        self::assertSame(96, $jsonData['avatar']['height']);
        self::assertEquals('a8/1c/a81cc2fea35eeb232cd28fcb109b3eb5a4e52c71bce95af6650d71876c1bcbb7.png', $jsonData['avatar']['filePath']);

        // Clean up test data as well as checking that DELETE works
        //      This isn't great, but since people could have their media directory
        //      pretty much anywhere, its difficult to reliably clean up uploaded files
        //      otherwise. This is certainly something that could be improved.
        $client->request('DELETE', '/api/users/avatar', server: ['HTTP_AUTHORIZATION' => $codes['token_type'].' '.$codes['access_token']]);
        self::assertResponseIsSuccessful();

        $jsonData = self::getJsonResponse($client);

        self::assertIsArray($jsonData);
        self::assertArrayKeysMatch(UserRetrieveApiTest::USER_RESPONSE_KEYS, $jsonData);
        self::assertNull($jsonData['avatar']);
    }

    public function testApiCanUpdateAndDeleteCurrentUserCover(): void
    {
        $client = self::createClient();
        self::createOAuth2AuthCodeClient();
        $testUser = $this->getUserByUsername('JohnDoe');
        $client->loginUser($testUser);
        $codes = self::getAuthorizationCodeTokenResponse($client, scopes: 'read user:profile:edit user:profile:read');

        // Uploading a file appears to delete the file at the given path, so make a copy before upload
        copy($this->kibbyPath, $this->kibbyPath.'.tmp');
        $image = new UploadedFile($this->kibbyPath.'.tmp', 'kibby_emoji.png', 'image/png');

        $client->request(
            'POST', '/api/users/cover',
            files: ['uploadImage' => $image],
            server: ['HTTP_AUTHORIZATION' => $codes['token_type'].' '.$codes['access_token']]
        );
        self::assertResponseIsSuccessful();

        $jsonData = self::getJsonResponse($client);

        self::assertIsArray($jsonData);
        self::assertArrayKeysMatch(UserRetrieveApiTest::USER_RESPONSE_KEYS, $jsonData);
        
        self::assertIsArray($jsonData['cover']);
        self::assertArrayKeysMatch(self::IMAGE_KEYS, $jsonData['cover']);
        self::assertSame(96, $jsonData['cover']['width']);
        self::assertSame(96, $jsonData['cover']['height']);
        self::assertEquals('a8/1c/a81cc2fea35eeb232cd28fcb109b3eb5a4e52c71bce95af6650d71876c1bcbb7.png', $jsonData['cover']['filePath']);

        // Clean up test data as well as checking that DELETE works
        //      This isn't great, but since people could have their media directory
        //      pretty much anywhere, its difficult to reliably clean up uploaded files
        //      otherwise. This is certainly something that could be improved.
        $client->request('DELETE', '/api/users/cover', server: ['HTTP_AUTHORIZATION' => $codes['token_type'].' '.$codes['access_token']]);
        self::assertResponseIsSuccessful();

        $jsonData = self::getJsonResponse($client);

        self::assertIsArray($jsonData);
        self::assertArrayKeysMatch(UserRetrieveApiTest::USER_RESPONSE_KEYS, $jsonData);
        self::assertNull($jsonData['cover']);
    }
}
