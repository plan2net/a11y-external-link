# TYPO3 External Link Accessibility Extension

[![Tests](https://github.com/plan2net/a11y-external-link/actions/workflows/tests.yml/badge.svg)](https://github.com/plan2net/a11y-external-link/actions/workflows/tests.yml)
[![PHP](https://img.shields.io/badge/PHP-8.2%20|%208.3-blue.svg)](https://www.php.net/)
[![TYPO3](https://img.shields.io/badge/TYPO3-12.4%20|%2013.4-orange.svg)](https://typo3.org/)
[![License](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](LICENSE)

Automatically enhance external links with screen reader text for improved web
accessibility in TYPO3 websites. The extension clearly indicates when links open
in new windows.

## Quick Start

```bash
composer require plan2net/a11y-external-link
```

The extension works immediately after installation — no configuration required!
External links automatically get screen reader text added:

```html
<!-- Before (external, opens new window) -->
<a href="https://external.com" target="_blank">Visit our partner</a>

<!-- After (annotated for screen readers) -->
<a href="https://external.com" target="_blank"
    >Visit our partner<span class="sr-only">, opens an external URL in a new window</span></a
>
```

## Why This Extension?

### The Problem

External links often open in new windows without warning users, which:

- Violates WCAG 2.1 accessibility guidelines
- Confuses screen reader users
- Creates poor user experience for keyboard navigation
- Can be disorienting for users with cognitive disabilities

### The Solution

This extension automatically:

- Detects all external links in your content
- Adds hidden screen reader text to warn users
- Preserves existing accessibility markup
- Works with all TYPO3 content elements
- Supports multilingual sites

## Features

- Automatic detection — Identifies external links by comparing domains
- Screen reader support — Adds hidden text announced by assistive technology
- Zero configuration — Works out of the box with sensible defaults
- Customizable — Configure text and CSS class via Extension Configuration
- Performance focused — Processes content once during page rendering
- Smart detection — Handles protocol‑relative URLs; preserves existing screen
  reader text
- Fully tested — Developed with TDD and unit tests

## How It Works

The extension listens to TYPO3's `AfterCacheableContentIsGeneratedEvent` and:

1. **Detects Current Domain** — Reads from TYPO3 site configuration
2. **Parses HTML Content** — Uses DOMDocument for reliable HTML processing
3. **Identifies External Links** — Compares link domains with current site
4. **Adds Accessibility Text** — Appends screen reader-only span elements only
   when `target="_blank"` is present
5. **Preserves Existing Markup** — Skips links that already have screen reader
   text

## Configuration

Manage settings in Extension Configuration (ext_conf_template):

- enabled: toggles processing (default: 1)
- screenReaderClass: CSS class for assistive text (default: `sr-only`)

The appended warning text is localized via
`Resources/Private/Language/locallang.xlf` and follows the current site
language.

### CSS Requirements

Ensure your site includes CSS for the screen reader class:

```css
.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}
```

## Technical Details

### Supported Link Types

Detected as external:

- `https://external.com` — Different domain
- `http://external.com` — Different domain with HTTP
- `//external.com` — Protocol-relative URLs

❌ **Not Modified (Internal):**

- `/about` — Root-relative paths
- `./page` — Relative paths
- `#section` — Anchor links
- `mailto:` — Email links
- `tel:` — Phone links
- `javascript:` — JavaScript links

## License

GNU General Public License version 2 or later (GPL-2.0-or-later)

## Credits

Developed by [plan2net GmbH](https://www.plan2net.com)

---

_Making the web more accessible, one link at a time._
