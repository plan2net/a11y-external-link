<?php

declare(strict_types=1);

namespace Plan2net\ExternalLinkAccessibility\EventListener;

use Plan2net\ExternalLinkAccessibility\Configuration;
use Plan2net\ExternalLinkAccessibility\Service\LinkModifierService;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Event\AfterCacheableContentIsGeneratedEvent;

final readonly class ContentPostProcessListener
{
    public function __construct(private Configuration $config) {}

    public function __invoke(AfterCacheableContentIsGeneratedEvent $event): void
    {
        $request = $event->getRequest();
        $site = $request->getAttribute('site');
        if (!$site instanceof Site) {
            return;
        }

        $currentDomain = (string) $site->getBase();

        if ($this->config->isEnabled() === false) {
            return;
        }

        $controller = $event->getController();
        try {
            $warningText = $this->getLocalizedWarningText($request);
            $modifier = new LinkModifierService(
                $currentDomain,
                $warningText,
                $this->config->screenReaderClass()
            );
            $controller->content = $modifier->modifyLinks($controller->content);
        } catch (\Throwable) {
            // Ignore
        }
    }

    private function getLocalizedWarningText(ServerRequestInterface $request): string
    {
        $label = 'LLL:EXT:external_link_accessibility/Resources/Private/Language/locallang.xlf:opensInNewWindow';
        $siteLanguage = $request->getAttribute('language');
        if ($siteLanguage instanceof SiteLanguage) {
            $factory = GeneralUtility::makeInstance(LanguageServiceFactory::class);
            $languageService = $factory->createFromSiteLanguage($siteLanguage);

            return (string)($languageService->sL($label) ?: '');
        }

        return '';
    }
}
