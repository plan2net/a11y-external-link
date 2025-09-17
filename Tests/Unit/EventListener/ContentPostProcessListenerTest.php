<?php

declare(strict_types=1);

namespace Plan2net\ExternalLinkAccessibility\Tests\Unit\EventListener;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Plan2net\ExternalLinkAccessibility\Configuration;
use Plan2net\ExternalLinkAccessibility\EventListener\ContentPostProcessListener;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Event\AfterCacheableContentIsGeneratedEvent;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use Psr\Http\Message\ServerRequestInterface;

final class ContentPostProcessListenerTest extends TestCase
{
    #[Test]
    public function modifiesPageContent(): void
    {
        $html = '<a href="https://external.com" target="_blank">External</a>';

        $controller = $this->createMock(TypoScriptFrontendController::class);
        $controller->content = $html;

        $request = $this->createMock(ServerRequestInterface::class);
        $site = new Site('test', 1, ['base' => 'https://example.com']);
        $siteLanguage = $site->getDefaultLanguage();
        $request->method('getAttribute')->willReturnMap([
            ['site', $site],
            ['language', $siteLanguage],
        ]);

        $event = new AfterCacheableContentIsGeneratedEvent(
            $request,
            $controller,
            'cache-id',
            true
        );

        $extensionConfiguration = $this->createMock(ExtensionConfiguration::class);
        $extensionConfiguration->method('get')->with('external_link_accessibility')->willReturn([
            'enabled' => 1,
            'screenReaderClass' => 'sr-only',
        ]);
        $configuration = new Configuration($extensionConfiguration);

        // Mock the LanguageService via LanguageServiceFactory for SiteLanguage-based lookup
        $languageService = $this->createMock(LanguageService::class);
        $languageService->method('sL')->willReturn('(opens in new window)');

        $languageServiceFactory = $this->getMockBuilder(\TYPO3\CMS\Core\Localization\LanguageServiceFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createFromSiteLanguage'])
            ->getMock();
        $languageServiceFactory->method('createFromSiteLanguage')->with($siteLanguage)->willReturn($languageService);
        GeneralUtility::addInstance(\TYPO3\CMS\Core\Localization\LanguageServiceFactory::class, $languageServiceFactory);

        $listener = new ContentPostProcessListener($configuration);
        $listener->__invoke($event);

        self::assertStringContainsString('<span class="sr-only"> (opens in new window)</span>', $controller->content);
    }
}
