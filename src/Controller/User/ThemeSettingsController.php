<?php

declare(strict_types=1);

namespace App\Controller\User;

use App\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ThemeSettingsController extends AbstractController
{
    public const ENTRIES_VIEW = 'entries_view';
    public const ENTRY_COMMENTS_VIEW = 'entry_comments_view';
    public const POST_COMMENTS_VIEW = 'post_comments_view';
    public const KBIN_THEME = 'kbin_theme';
    public const KBIN_FONT_SIZE = 'kbin_font_size';
    public const KBIN_SHOW_USERS_AVATARS = 'kbin_show_users_avatars';
    public const KBIN_SHOW_MAGAZINES_ICONS = 'kbin_show_magazines_icons';
    public const KBIN_SHOW_THREADS_THUMBNAILS = 'kbin_show_threads_thumbnails';

    public const CLASSIC = 'classic';
    public const CHAT = 'chat';
    public const TREE = 'tree';
    public const COMPACT = 'compact';
    public const LIGHT = 'light';
    public const DARK = 'dark';
    public const SOLARIZED_LIGHT = 'solarized-light';
    public const SOLARIZED_DARK = 'solarized-dark';
    public const TRUE = 'true';
    public const FALSE = 'false';


    public const KEYS = [
        self::ENTRIES_VIEW,
        self::ENTRY_COMMENTS_VIEW,
        self::POST_COMMENTS_VIEW,
        self::KBIN_THEME,
        self::KBIN_FONT_SIZE,
        self::KBIN_SHOW_USERS_AVATARS,
        self::KBIN_SHOW_MAGAZINES_ICONS,
        self::KBIN_SHOW_THREADS_THUMBNAILS,
    ];

    public const VALUES = [
        self::CLASSIC,
        self::CHAT,
        self::TREE,
        self::COMPACT,
        self::LIGHT,
        self::DARK,
        self::SOLARIZED_LIGHT,
        self::SOLARIZED_DARK,
        self::TRUE,
        self::FALSE,
        '100',
        '120',
        '150',
    ];

    public function __invoke(string $key, string $value, Request $request): Response
    {
        $response = new Response();

        if (in_array($key, self::KEYS) && in_array($value, self::VALUES)) {
            $response->headers->setCookie(new Cookie($key, $value));
        }

        return new \Symfony\Component\HttpFoundation\RedirectResponse(
            $request->headers->get('referer') ?? '/',
            302,
            $response->headers->all()
        );
    }
}
