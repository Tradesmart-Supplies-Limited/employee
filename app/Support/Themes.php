<?php

namespace App\Support;

/**
 * Colour themes people can pick from the top bar (admin area and portal).
 * The colours themselves live in public/assets/css/theme.css; this list feeds
 * the picker and validates what is saved on the user.
 */
final class Themes
{
    public const DEFAULT = 'tradesmart';

    /** key => [name, swatch (light), swatch (dark), short description] */
    public const ALL = [
        'tradesmart' => ['Tradesmart blue', '#185FA5', '#6CB0F0', 'The company colour. The greeting card follows the time of day.'],
        'zambezi'    => ['Zambezi teal',    '#0F766E', '#2DD4BF', 'Calm river teal.'],
        'copperbelt' => ['Copperbelt',      '#B45309', '#F59E0B', 'Warm copper and amber.'],
        'emerald'    => ['Emerald',         '#047857', '#34D399', 'Fresh green.'],
        'jacaranda'  => ['Jacaranda',       '#6D28D9', '#A78BFA', 'Lusaka\'s purple blossom.'],
        'msasa'      => ['Msasa red',       '#BE123C', '#FB7185', 'The red of new msasa leaves.'],
        'graphite'   => ['Graphite',        '#334155', '#CBD5E1', 'Quiet and neutral.'],
    ];

    public static function valid(?string $key): string
    {
        return array_key_exists((string) $key, self::ALL) ? $key : self::DEFAULT;
    }
}
