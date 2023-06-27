<?php

declare(strict_types=1);

namespace App\Markdown\CommonMark;

use App\Repository\EmbedRepository;
use App\Service\ImageManager;
use App\Service\MentionManager;
use App\Service\SettingsManager;
use League\CommonMark\ElementRendererInterface;
use League\CommonMark\HtmlElement;
use League\CommonMark\Inline\Element\AbstractInline;
use League\CommonMark\Inline\Element\Link;
use League\CommonMark\Inline\Element\Text;
use League\CommonMark\Inline\Renderer\InlineRendererInterface;
use League\CommonMark\Util\ConfigurationAwareInterface;
use League\CommonMark\Util\ConfigurationInterface;

final class ExternalLinkRenderer implements InlineRendererInterface, ConfigurationAwareInterface
{
    private ConfigurationInterface $config;

    public function __construct(
        private readonly EmbedRepository $embedRepository,
        private readonly SettingsManager $settingsManager,
    ) {
    }

    public function render(
        AbstractInline $inline,
        ElementRendererInterface $htmlRenderer
    ): HtmlElement {
        if (!$inline instanceof Link) {
            throw new \InvalidArgumentException(sprintf('Incompatible inline type: %s', \get_class($inline)));
        }

        $url = $title = $inline->getUrl();

        if ($inline->firstChild() instanceof Text) {
            $title = $htmlRenderer->renderInline($inline->firstChild());
        }

        $embed = false;
        if (filter_var($url, FILTER_VALIDATE_URL) && !str_starts_with($title, '@') && !str_starts_with(
                $title,
                '#'
            )) {
            if ($entity = $this->embedRepository->findOneBy(['url' => $url])) {
                $embed = $entity->hasEmbed;
            }
        }

        if (ImageManager::isImageUrl($url) || $embed) {
            return EmbedElement::buildEmbed($url, $title);
        }

        $htmlTitle = $inline->data['title'] ?? '';
        $attr = ['class' => 'kbin-media-link', 'rel' => 'nofollow noopener noreferrer'];

        foreach (['@', '!', '#'] as $tag) {
            if (str_starts_with($title, $tag)) {
                $attr = match ($tag) {
                    '@' => [
                        'class' => 'mention u-url',
                        'title' => substr_count($htmlTitle, '@') === 1 ? $htmlTitle.'@'.$this->settingsManager->get(
                                'KBIN_DOMAIN'
                            ) : $htmlTitle,
                        'data-action' => 'mouseover->kbin#mention',
                        'data-kbin-username-param' => isset($inline->data['title']) ? MentionManager::getRoute(
                            [$inline->data['title']]
                        )[0] : '',
                    ],
                    '#' => ['class' => 'hashtag tag', 'rel' => 'tag'],
                    default => [],
                };
            }
        }

        if (false !== filter_var($url, FILTER_VALIDATE_URL) && !$this->settingsManager->isLocalUrl($url)) {
            $attr['rel'] = 'noopener noreferrer nofollow';
            $attr['target'] = '_blank';
        }

        if (str_starts_with($url, 'javascript:')) {
            $url = '';
        }

        return new HtmlElement(
            'a',
            ['href' => $url] + $attr,
            $title
        );
    }

    public function setConfiguration(
        ConfigurationInterface $configuration
    ): void {
        $this->config = $configuration;
    }
}
