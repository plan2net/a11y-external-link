<?php

declare(strict_types=1);

namespace Plan2net\ExternalLinkAccessibility;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

final readonly class Configuration
{

    private const EXTENSION_KEY = 'external_link_accessibility';

    public function __construct(private ExtensionConfiguration $extensionConfiguration) {}

    public function isEnabled(): bool
    {
        $configuration = $this->getConfiguration();

        return (bool)($configuration['enabled'] ?? true);
    }

    public function screenReaderClass(): string
    {
        $configuration = $this->getConfiguration();
        $value = (string)($configuration['screenReaderClass'] ?? 'sr-only');

        return $value !== '' ? $value : 'sr-only';
    }

    /** @return array<string,mixed> */
    private function getConfiguration(): array
    {
        try {
            $configuration = $this->extensionConfiguration->get(self::EXTENSION_KEY);

            return is_array($configuration) ? $configuration : [];
        } catch (\Throwable) {
            return [];
        }
    }
}
