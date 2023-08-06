<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Security;

use App\Entity\User;
use App\Tests\WebTestCase;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Contracts\Translation\TranslatorInterface;

class RegisterControllerTest extends WebTestCase
{
    public function testUserCanVerifyAccount(): void
    {
        $client = $this->createClient();
        $translator = $this->getService(TranslatorInterface::class);

        $this->registerUserAccount($client);

        $this->assertEmailCount(1);

        /** @var TemplatedEmail $email */
        $email = $this->getMailerMessage();

        $this->assertEmailHeaderSame($email, 'To', 'johndoe@kbin.pub');

        $verifyLink = [];
        preg_match('#<a href="(?P<link>.+)">#', $email->getHtmlBody(), $verifyLink);

        $client->request('GET', $verifyLink['link']);
        $crawler = $client->followRedirect();

        $client->submit(
            $crawler->selectButton($translator->trans('login'))->form(
                [
                    'email' => 'JohnDoe',
                    'password' => 'secret',
                ]
            )
        );

        $client->followRedirect();

        $this->assertSelectorTextNotContains('#header', $translator->trans('login'));
    }

    private function registerUserAccount(KernelBrowser $client): void
    {
        $crawler = $client->request('GET', '/register');
        $translator = $this->getService(TranslatorInterface::class);

        $client->submit(
            $crawler->filter('form[name=user_register]')->selectButton($translator->trans('register'))->form(
                [
                    'user_register[username]' => 'JohnDoe',
                    'user_register[email]' => 'johndoe@kbin.pub',
                    'user_register[plainPassword][first]' => 'secret',
                    'user_register[plainPassword][second]' => 'secret',
                    'user_register[agreeTerms]' => true,
                ]
            )
        );
    }

    public function testUserCannotLoginWithoutConfirmation()
    {
        $client = $this->createClient();
        $translator = $this->getService(TranslatorInterface::class);

        $this->registerUserAccount($client);

        $crawler = $client->followRedirect();

        $crawler = $client->click($crawler->filter('#header')->selectLink($translator->trans('login'))->link());

        $client->submit(
            $crawler->selectButton($translator->trans('login'))->form(
                [
                    'email' => 'JohnDoe',
                    'password' => 'wrong_password',
                ]
            )
        );

        $client->followRedirect();

        $this->assertSelectorTextContains('.alert__danger', $translator->trans('your_account_is_not_active'));
    }

    public static function register($active = false): KernelBrowser
    {
        $client = self::createClient();
        $crawler = $client->request('GET', '/register');
        $translator = self::getContainer()->get(TranslatorInterface::class);

        $client->submit(
            $crawler->filter('form[name=user_register]')->selectButton($translator->trans('register'))->form(
                [
                    'user_register[username]' => 'JohnDoe',
                    'user_register[email]' => 'johndoe@kbin.pub',
                    'user_register[plainPassword][first]' => 'secret',
                    'user_register[plainPassword][second]' => 'secret',
                    'user_register[agreeTerms]' => true,
                ]
            )
        );

        if ($active) {
            $user = self::getContainer()->get('doctrine')->getRepository(User::class)
                ->findOneBy(['username' => 'JohnDoe']);
            $user->isVerified = true;

            self::getContainer()->get('doctrine')->getManager()->flush();
            self::getContainer()->get('doctrine')->getManager()->refresh($user);
        }

        return $client;
    }
}
