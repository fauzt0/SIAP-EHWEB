<?php

declare(strict_types=1);

namespace Plugins\NotasInternas;

/**
 * Texto de una nota: sin dependencias del ERP para poder probarlo aparte.
 */
final class NoteContent
{
    /** @var list<string> */
    public const COLORS = [
        '#fff9c4',
        '#f8bbd0',
        '#bbdefb',
        '#c8e6c9',
        '#ffe0b2',
        '#e1bee7',
        '#ffffff',
    ];

    public const MAX_BODY = 20000;

    public static function normalizeColor(string $color): string
    {
        $color = strtolower(trim($color));
        if (in_array($color, self::COLORS, true)) {
            return $color;
        }

        return self::COLORS[0];
    }

    /**
     * Deja solo negrita, cursiva, subrayado y saltos. Quita atributos.
     */
    public static function sanitizeBody(string $raw): string
    {
        $raw = str_replace("\0", '', $raw);
        $raw = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', '', $raw) ?? $raw;
        $clean = strip_tags($raw, '<b><i><u><strong><em><br><p>');
        $clean = preg_replace('/<(\/?)(b|i|u|strong|em|br|p)\b[^>]*>/i', '<$1$2>', $clean) ?? '';
        if (strlen($clean) > self::MAX_BODY) {
            $clean = substr($clean, 0, self::MAX_BODY);
        }

        return $clean;
    }

    public static function isEmptyHtml(string $html): bool
    {
        $text = trim(html_entity_decode(strip_tags(str_ireplace(
            ['<br>', '<br/>', '<br />'],
            ' ',
            $html
        )), ENT_QUOTES));

        return $text === '';
    }

    /**
     * Título visible. Null si no hay título ni cuerpo.
     */
    public static function resolveTitle(string $title, string $body): ?string
    {
        $title = trim(preg_replace('/\s+/u', ' ', $title) ?? '');
        if ($title !== '') {
            return self::clip($title, 150);
        }

        if (self::isEmptyHtml($body)) {
            return null;
        }

        $text = trim(html_entity_decode(strip_tags(str_ireplace(
            ['<br>', '<br/>', '<br />', '</p>', '<p>'],
            ' ',
            $body
        )), ENT_QUOTES));
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');
        if ($text === '') {
            return null;
        }

        return self::clip($text, 150);
    }

    private static function clip(string $value, int $max): string
    {
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $max);
        }

        return substr($value, 0, $max);
    }
}
