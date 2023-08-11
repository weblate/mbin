<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Security;

use App\Tests\WebTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class LoginControllerTest extends WebTestCase
{
    public function testUserCanLogin(): void
    {
        $client = RegisterControllerTest::register(true);

        $crawler = $client->request('get', '/');
        $crawler = $client->click($crawler->filter('header')->selectLink('Log in')->link());

        $client->submit(
            $crawler->selectButton('Log in')->form(
                [
                    'email' => 'JohnDoe',
                    'password' => 'secret',
                ]
            )
        );

        $crawler = $client->followRedirect();

        $this->assertSelectorTextContains('#header', 'JohnDoe');
    }

    public function testUserCannotLoginWithoutActivation(): void
    {
        $client = RegisterControllerTest::register();

        $crawler = $client->request('get', '/');
        $crawler = $client->click($crawler->filter('header')->selectLink('Log in')->link());

        $client->submit(
            $crawler->selectButton('Log in')->form(
                [
                    'email' => 'JohnDoe',
                    'password' => 'secret',
                ]
            )
        );

        $client->followRedirect();

        $translator = $this->getService(TranslatorInterface::class);
        $this->assertSelectorTextContains('#main', $translator->trans('your_account_is_not_active'));
    }

    public function testUserCantLoginWithWrongPassword(): void
    {
        $client = $this->createClient();
        $this->getUserByUsername('JohnDoe');

        $crawler = $client->request('GET', '/');
        $crawler = $client->click($crawler->filter('header')->selectLink('Log in')->link());

        $client->submit(
            $crawler->selectButton('Log in')->form(
                [
                    'email' => 'JohnDoe',
                    'password' => 'wrongpassword',
                ]
            )
        );

        $client->followRedirect();

        $this->assertSelectorTextContains('.alert__danger', 'Invalid credentials.'); // @todo
    }
}
