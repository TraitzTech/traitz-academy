<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

class EmailContentSanitizer
{
    /**
     * Sanitize rich HTML content before storing/sending emails.
     */
    public static function sanitize(string $html): string
    {
        $html = trim($html);

        if ($html === '') {
            return '';
        }

        $html = preg_replace('/<\s*(script|style|iframe|object|embed|meta|link)[^>]*>.*?<\s*\/\s*\1\s*>/is', '', $html) ?? '';
        $html = preg_replace('/<!--.*?-->/s', '', $html) ?? '';

        $allowedTags = '<p><br><strong><b><em><i><u><s><ul><ol><li><h1><h2><h3><blockquote><a><img><hr><div><span>';
        $html = strip_tags($html, $allowedTags);

        if (trim(strip_tags($html)) === '') {
            return '';
        }

        $wrappedHtml = '<div id="email-root">'.$html.'</div>';
        $document = new DOMDocument('1.0', 'UTF-8');
        $encodedHtml = function_exists('mb_convert_encoding')
            ? mb_convert_encoding($wrappedHtml, 'HTML-ENTITIES', 'UTF-8')
            : $wrappedHtml;

        $previousLibxmlSetting = libxml_use_internal_errors(true);
        $document->loadHTML($encodedHtml);
        libxml_clear_errors();
        libxml_use_internal_errors($previousLibxmlSetting);

        $root = $document->getElementById('email-root');
        if (! $root) {
            return '';
        }

        $allowedAttributes = [
            '*' => ['style'],
            'a' => ['href', 'title', 'target', 'rel'],
            'img' => ['src', 'alt', 'title', 'width', 'height'],
        ];

        self::sanitizeNode($root, $allowedAttributes);

        return trim(self::innerHtml($root));
    }

    /**
     * @param  array<string, array<int, string>>  $allowedAttributes
     */
    private static function sanitizeNode(DOMNode $node, array $allowedAttributes): void
    {
        if ($node instanceof DOMElement) {
            $tag = strtolower($node->tagName);
            $tagAllowedAttributes = $allowedAttributes[$tag] ?? [];
            $globalAllowedAttributes = $allowedAttributes['*'] ?? [];
            $elementAllowedAttributes = array_flip(array_merge($globalAllowedAttributes, $tagAllowedAttributes));

            $attributeNames = [];
            foreach ($node->attributes as $attribute) {
                $attributeNames[] = $attribute->nodeName;
            }

            foreach ($attributeNames as $attributeName) {
                $attributeNameLower = strtolower($attributeName);
                $value = $node->getAttribute($attributeName);

                if (str_starts_with($attributeNameLower, 'on')) {
                    $node->removeAttribute($attributeName);
                    continue;
                }

                if (! isset($elementAllowedAttributes[$attributeNameLower])) {
                    $node->removeAttribute($attributeName);
                    continue;
                }

                if (in_array($attributeNameLower, ['href', 'src'], true) && ! self::isSafeUrl($tag, $value)) {
                    $node->removeAttribute($attributeName);
                    continue;
                }

                if ($attributeNameLower === 'style') {
                    $cleanStyle = self::sanitizeStyle($value);
                    if ($cleanStyle === '') {
                        $node->removeAttribute($attributeName);
                    } else {
                        $node->setAttribute($attributeName, $cleanStyle);
                    }
                }

                if ($tag === 'a' && $attributeNameLower === 'target' && $value === '_blank') {
                    $existingRel = strtolower($node->getAttribute('rel'));
                    if (! str_contains($existingRel, 'noopener')) {
                        $node->setAttribute('rel', trim($existingRel.' noopener noreferrer'));
                    }
                }
            }
        }

        $children = [];
        foreach ($node->childNodes as $childNode) {
            $children[] = $childNode;
        }

        foreach ($children as $childNode) {
            self::sanitizeNode($childNode, $allowedAttributes);
        }
    }

    private static function isSafeUrl(string $tag, string $value): bool
    {
        $value = trim(html_entity_decode($value));
        if ($value === '') {
            return false;
        }

        if (str_starts_with($value, '#')) {
            return true;
        }

        if (str_starts_with($value, '/')) {
            return true;
        }

        if ($tag === 'img' && preg_match('/^data:image\/(png|jpe?g|gif|webp);base64,/i', $value)) {
            return true;
        }

        $scheme = parse_url($value, PHP_URL_SCHEME);
        if (! is_string($scheme)) {
            return false;
        }

        return in_array(strtolower($scheme), ['http', 'https', 'mailto', 'tel'], true);
    }

    private static function sanitizeStyle(string $style): string
    {
        $style = preg_replace('/expression\s*\(.*?\)/i', '', $style) ?? '';
        $style = preg_replace('/url\s*\(\s*[\'"]?\s*javascript:.*?\)/i', '', $style) ?? '';

        return trim($style);
    }

    private static function innerHtml(DOMElement $element): string
    {
        $html = '';
        foreach ($element->childNodes as $child) {
            $html .= $element->ownerDocument?->saveHTML($child) ?? '';
        }

        return $html;
    }
}
