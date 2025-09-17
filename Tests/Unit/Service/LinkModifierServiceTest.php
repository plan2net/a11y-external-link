<?php

declare(strict_types=1);

namespace Plan2net\ExternalLinkAccessibility\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Plan2net\ExternalLinkAccessibility\Service\LinkModifierService;

final class LinkModifierServiceTest extends TestCase
{
    private LinkModifierService $subject;

    protected function setUp(): void
    {
        $this->subject = new LinkModifierService(
            currentDomain: 'https://example.com',
            warningText: '(opens in new window)',
        );
    }

    #[Test]
    public function returnsInputUnchangedWhenHtmlIsEmpty(): void
    {
        self::assertSame('', $this->subject->modifyLinks(''));
    }

    public static function externalLinksProvider(): array
    {
        return [
            'absolute https' => ['<a href="https://external.com" target="_blank">External</a>'],
            'absolute http' => ['<a href="http://external.com" target="_blank">External</a>'],
            'protocol-relative' => ['<a href="//external.com" target="_blank">External</a>'],
            'different domain' => ['<a href="https://different-domain.com/path" target="_blank">Other</a>'],
        ];
    }

    #[Test]
    #[DataProvider('externalLinksProvider')]
    public function addsScreenReaderTextToExternalLinks(string $html): void
    {
        $result = $this->subject->modifyLinks($html);
        self::assertStringContainsString('<span class="sr-only"> (opens in new window)</span>', $result);
    }



    #[Test]
    public function handlesMultipleLinks(): void
    {
        $html = '
            <a href="https://external.com" target="_blank">External</a>
            <a href="/internal">Internal</a>
            <a href="https://another-external.com" target="_blank">Another External</a>
        ';

        $result = $this->subject->modifyLinks($html);

        self::assertSame(2, substr_count($result, '<span class="sr-only">'));
    }


    #[Test]
    public function preservesExistingScreenReaderText(): void
    {
        $html = '<a href="https://external.com" target="_blank">Link<span class="btn sr-only visually-hidden"> (already has text)</span></a>';

        $result = $this->subject->modifyLinks($html);

        self::assertStringContainsString('btn sr-only visually-hidden', $result);
        self::assertStringContainsString('(already has text)', $result);
        self::assertStringNotContainsString('(opens in new window)', $result);
    }

    #[Test]
    public function doesNotAnnotateExternalWithoutTargetBlank(): void
    {
        $html = '<a href="https://external.com">External</a>';
        $result = $this->subject->modifyLinks($html);
        self::assertStringNotContainsString('(opens in new window)', $result);
    }

    public static function nonExternalLinksProvider(): array
    {
        return [
            'internal absolute same host' => ['<a href="https://example.com/page">Internal</a>'],
            'root-relative' => ['<a href="/about">About</a>'],
            'dot-relative' => ['<a href="./team">Team</a>'],
            'dotdot-relative' => ['<a href="../parent">Parent</a>'],
            'anchor-only' => ['<a href="#section">Jump</a>'],
            'query-only' => ['<a href="?q=abc">Query</a>'],
            'mailto link' => ['<a href="mailto:test@example.com">Email</a>'],
            'tel link' => ['<a href="tel:+1234567890">Phone</a>'],
            'javascript link' => ['<a href="javascript:void(0)">Click</a>'],
            'data link' => ['<a href="data:text/plain,hello">Data</a>'],
            'empty href' => ['<a href="">Empty</a>'],
        ];
    }

    #[Test]
    #[DataProvider('nonExternalLinksProvider')]
    public function doesNotModifyNonExternalLinks(string $html): void
    {
        $result = $this->subject->modifyLinks($html);

        self::assertStringNotContainsString('<span class="sr-only">', $result);
        self::assertSame($html, $result);
    }
}
