<?php

declare(strict_types=1);

namespace Plan2net\ExternalLinkAccessibility\Service;

readonly class LinkModifierService
{
    private const XML_DECL = '<?xml encoding="utf-8" ?>';
    private const XPATH_LINKS_WITH_HREF = '//a[@href]';

    public function __construct(
        private string $currentDomain,
        private string $warningText,
        private string $screenReaderClass = 'sr-only'
    )
    {
    }

    /**
     * @throws \DOMException
     */
    public function modifyLinks(string $html): string
    {
        if ($html === '') {
            return $html;
        }

        $dom = $this->createDom($html);
        $links = $this->getLinks($dom);
        if ($links === false) {
            return $html;
        }

        foreach ($links as $link) {
            if (!$link instanceof \DOMElement) {
                continue;
            }
            $href = (string)$link->getAttribute('href');
            $target = strtolower((string)$link->getAttribute('target'));
            if ($this->isExternalLink($href) && $target === '_blank' && !$this->hasScreenReaderText($link)) {
                $this->appendScreenReaderSpan($dom, $link);
            }
        }

        $result = $dom->saveHTML();
        if ($result === false) {
            return $html;
        }

        if (str_starts_with($result, self::XML_DECL)) {
            $result = substr($result, strlen(self::XML_DECL));
        }

        return trim($result);
    }

    private function isExternalLink(string $href): bool
    {
        if ($href === '' || str_starts_with($href, '#')) {
            return false;
        }

        $scheme = parse_url($href, PHP_URL_SCHEME);
        $allowHttp = match ($scheme) {
            'http', 'https' => true,
            null => null,
            default => false,
        };

        if ($allowHttp === false) {
            return false;
        }

        if ($allowHttp === null) {
            if (
                !str_starts_with($href, '//') && (
                    str_starts_with($href, '/') ||
                    str_starts_with($href, './') ||
                    str_starts_with($href, '../') ||
                    str_starts_with($href, '?')
                )
            ) {
                return false;
            }
        }

        $normalizedHref = str_starts_with($href, '//') ? ('https:' . $href) : $href;
        if (!str_starts_with($normalizedHref, 'http://') &&
            !str_starts_with($normalizedHref, 'https://')) {
            return false;
        }

        $linkHost = parse_url($normalizedHref, PHP_URL_HOST);
        $currentHost = parse_url($this->currentDomain, PHP_URL_HOST);

        return $linkHost !== null && $currentHost !== null && $linkHost !== $currentHost;
    }

    private function hasScreenReaderText(\DOMElement $link): bool
    {
        foreach ($link->childNodes as $child) {
            if ($child->nodeName !== 'span') {
                continue;
            }

            if ($this->elementHasClass($child, $this->screenReaderClass)) {
                return true;
            }
        }

        return false;
    }

    private function createDom(string $html): \DOMDocument
    {
        $dom = new \DOMDocument();
        $previousUseInternalErrors = libxml_use_internal_errors(true);
        try {
            $dom->loadHTML(
                self::XML_DECL . $html,
                LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET
            );
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousUseInternalErrors);
        }

        return $dom;
    }

    private function getLinks(\DOMDocument $dom): \DOMNodeList|false
    {
        $xpath = new \DOMXPath($dom);

        return $xpath->query(self::XPATH_LINKS_WITH_HREF);
    }

    private function elementHasClass(\DOMElement $element, string $needle): bool
    {
        $classAttr = trim($element->getAttribute('class'));
        if ($classAttr === '') {
            return false;
        }

        $classes = preg_split('/\s+/', $classAttr) ?: [];

        return in_array($needle, $classes, true);
    }

    /**
     * @throws \DOMException
     */
    private function appendScreenReaderSpan(\DOMDocument $dom, \DOMElement $link): void
    {
        $span = $dom->createElement('span');
        $span->setAttribute('class', $this->screenReaderClass);
        $span->appendChild($dom->createTextNode(' ' . $this->warningText));
        $link->appendChild($span);
    }
}
