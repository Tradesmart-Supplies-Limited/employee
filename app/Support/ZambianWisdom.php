<?php

namespace App\Support;

/**
 * Zambian proverbs shown on the HR dashboard, each with what it means for
 * people management. One is picked per day; the dashboard can shuffle.
 * Edit / add freely — spellings of proverbs vary between regions.
 */
class ZambianWisdom
{
    public const QUOTES = [
        ['text' => 'Umunwe umo tautola nda.', 'lang' => 'Bemba proverb', 'meaning' => 'One finger cannot pick up a louse.', 'lesson' => 'Teams get done what no one person can. Recognise the group, not just the star.'],
        ['text' => 'Imiti ikula empanga.', 'lang' => 'Bemba proverb', 'meaning' => 'The young trees of today are the forest of tomorrow.', 'lesson' => 'Invest in young staff: training and mentoring today build tomorrow\'s leaders.'],
        ['text' => 'Mutu umodzi susenza denga.', 'lang' => 'Nyanja proverb', 'meaning' => 'One head cannot carry a roof.', 'lesson' => 'Share the load. Check who is overloaded before work piles up.'],
        ['text' => 'Pang\'ono pang\'ono ndi mtolo.', 'lang' => 'Nyanja proverb', 'meaning' => 'Little by little makes a bundle.', 'lesson' => 'Small, steady improvements in attendance, records and wellbeing add up.'],
        ['text' => 'Mwana wa mnzako ndi wako.', 'lang' => 'Nyanja proverb', 'meaning' => 'Your neighbour\'s child is your own child.', 'lesson' => 'Look out for every employee, not only the ones in your department.'],
        ['text' => 'Kali kokha nkanyama, tili awiri ndi anthu.', 'lang' => 'Nyanja proverb', 'meaning' => 'Alone, one is like an animal; together, we are people.', 'lesson' => 'Belonging matters: welcome new starters and include everyone.'],
        ['text' => 'One Zambia, One Nation.', 'lang' => 'National motto', 'meaning' => 'Many peoples, one country.', 'lesson' => 'Many departments, one company: fair treatment for every employee, everywhere.'],
    ];

    /** Today's quote (same all day for everyone), or a specific index. */
    public static function today(?int $index = null): array
    {
        $i = $index ?? ((int) now()->dayOfYear % count(self::QUOTES));

        return self::QUOTES[$i % count(self::QUOTES)] + ['index' => $i % count(self::QUOTES)];
    }
}
