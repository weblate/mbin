<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Post\Comment;

use App\Tests\WebTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class PostCommentEditControllerTest extends WebTestCase
{
    public function testAuthorCanEditOwnPostComment(): void
    {
        $client = $this->createClient();
        $translator = $this->getService(TranslatorInterface::class);
        $client->loginUser($this->getUserByUsername('JohnDoe'));

        $post = $this->createPost('test post 1');
        $this->createPostComment('test comment 1', $post);

        $crawler = $client->request('GET', "/m/acme/p/{$post->getId()}/test-post-1");

        $crawler = $client->click($crawler->filter('#main .post-comment')->selectLink(mb_strtolower($translator->trans('edit')))->link());

        $this->assertSelectorExists('#main .post-comment');
        $this->assertSelectorTextContains('#post_comment_body', 'test comment 1');

        $client->submit(
            $crawler->filter('form[name=post_comment]')->selectButton($translator->trans('edit_comment'))->form(
                [
                    'post_comment[body]' => 'test comment 2 body',
                ]
            )
        );

        $client->followRedirect();

        $this->assertSelectorTextContains('#main .post-comment', 'test comment 2 body');
    }
}
