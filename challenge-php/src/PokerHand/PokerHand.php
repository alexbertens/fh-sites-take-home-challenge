<?php

namespace PokerHand;

class PokerHand
{
    /*
     * Poker cards come in as strings like "Ah", "10s", or "Kd".
     *
     * The first part is the card value:
     * A, K, Q, J, 10, 9, etc.
     *
     * The last character is the suit:
     * c = clubs, d = diamonds, h = hearts, s = spades.
     *
     * It is easier to compare poker hands if the face cards become numbers.
     * For example, Ace becomes 14 and King becomes 13. That lets the straight
     * logic do simple math instead of checking a bunch of card names.
     */
    private const CARD_VALUES = [
        '2' => 2,
        '3' => 3,
        '4' => 4,
        '5' => 5,
        '6' => 6,
        '7' => 7,
        '8' => 8,
        '9' => 9,
        '10' => 10,
        'T' => 10,
        'J' => 11,
        'Q' => 12,
        'K' => 13,
        'A' => 14,
    ];

    /*
     * This stores the final answer for the hand, like "Flush" or "Two Pair".
     * The constructor figures this out once, and getRank() returns it later.
     */
    private string $rank;

    public function __construct(?string $hand)
    {
        /*
         * If someone passes null, there is no hand to rank.
         * I am throwing InvalidArgumentException here so bad poker input is
         * handled in one consistent way.
         */
        if ($hand === null) {
            throw new \InvalidArgumentException('A poker hand cannot be null.');
        }

        /*
         * Split the hand into two useful lists:
         *
         * $values will hold numbers like [14, 13, 12, 11, 10]
         * $suits will hold letters like ['s', 's', 's', 's', 's']
         *
         * Keeping these separate makes the ranking checks much simpler.
         */
        [$values, $suits] = $this->parseHand($hand);

        /*
         * Once the cards are parsed, we can rank the hand and save that result.
         * After this point, getRank() does not need to redo any work.
         */
        $this->rank = $this->rankHand($values, $suits);
    }

    public function getRank(): string
    {
        /*
         * This is intentionally simple. The hand was already ranked when the
         * object was created, so this just hands that value back.
         */
        return $this->rank;
    }

    private function parseHand(string $hand): array
    {
        /*
         * These arrays start empty and get filled as each card is read.
         * By the end, both arrays should have exactly five items.
         */
        $values = [];
        $suits = [];

        /*
         * trim() removes extra spaces from the beginning and end.
         * preg_split('/\s+/') then splits on one or more spaces.
         *
         * That means a hand with weird spacing like:
         * "Ah   Ks Qd   Jc 10s"
         *
         * still turns into five card strings correctly.
         */
        $cards = preg_split('/\s+/', trim($hand), -1, PREG_SPLIT_NO_EMPTY);

        /*
         * A normal poker hand has exactly five cards.
         * If there are three cards, six cards, or even zero cards, we stop here
         * because the ranking rules below only make sense for five cards.
         */
        if (count($cards) !== 5) {
            throw new \InvalidArgumentException('A poker hand must contain exactly 5 cards.');
        }

        foreach ($cards as $card) {
            /*
             * The suit is always the last character, so:
             *
             * "10s" becomes value "10" and suit "s"
             * "Ah" becomes value "A" and suit "h"
             *
             * substr($card, 0, -1) means "everything except the last character".
             * substr($card, -1) means "just the last character".
             */
            $value = strtoupper(substr($card, 0, -1));
            $suit = strtolower(substr($card, -1));

            /*
             * Make sure both pieces are real poker-card pieces.
             *
             * CARD_VALUES tells us if the value is allowed.
             * The suit list tells us if the suit is allowed.
             *
             * So "16h" fails because 16 is not a card value.
             * "Ax" fails because x is not one of the four suits.
             */
            if (!isset(self::CARD_VALUES[$value]) || !in_array($suit, ['c', 'd', 'h', 's'], true)) {
                throw new \InvalidArgumentException('Invalid card: ' . $card);
            }

            /*
             * Store the normalized value and suit.
             * At this point the value is a number and the suit is lowercase,
             * which keeps the rest of the class from worrying about formatting.
             */
            $values[] = self::CARD_VALUES[$value];
            $suits[] = $suit;
        }

        /*
         * Return both lists together. The caller uses PHP array destructuring to
         * receive them as [$values, $suits].
         */
        return [$values, $suits];
    }

    private function rankHand(array $values, array $suits): string
    {
        /*
         * Count how many times each card value appears.
         *
         * Example:
         * ['K', 'K', '3', '3', '2'] became [13, 13, 3, 3, 2]
         *
         * array_count_values gives counts like:
         * 13 appears 2 times
         * 3 appears 2 times
         * 2 appears 1 time
         *
         * We only need the count numbers, so array_values gives [2, 2, 1].
         */
        $valueCounts = array_values(array_count_values($values));

        /*
         * Sort the counts from largest to smallest.
         *
         * This makes pattern checks easy:
         * [4, 1] means four of a kind
         * [3, 2] means full house
         * [3, 1, 1] means three of a kind
         * [2, 2, 1] means two pair
         * [2, 1, 1, 1] means one pair
         */
        rsort($valueCounts);

        /*
         * Straights need unique values, because duplicate values cannot make a
         * five-card straight.
         *
         * Example:
         * [14, 2, 3, 4, 4] is not a straight because there are only four unique
         * values after removing the duplicate 4.
         */
        $uniqueValues = array_values(array_unique($values));

        /*
         * Sort lowest to highest so the straight check can look at the first and
         * last values. For a normal straight, highest - lowest should be 4.
         */
        sort($uniqueValues);

        /*
         * A flush means every card has the same suit.
         *
         * array_unique($suits) removes duplicates, so if all five cards are
         * hearts, the unique suit list only has one item.
         */
        $isFlush = count(array_unique($suits)) === 1;

        /*
         * A straight means the five values are consecutive.
         * There is one special ace case handled inside isStraight().
         */
        $isStraight = $this->isStraight($uniqueValues);

        /*
         * The order of these checks matters a lot.
         *
         * A royal flush is also technically a straight, and also technically a
         * flush, but we want to return the best possible rank. So the strongest
         * poker hands are checked first, then the weaker hands after that.
         */

        /*
         * Royal Flush:
         * 10, J, Q, K, A all in the same suit.
         */
        if ($isFlush && $uniqueValues === [10, 11, 12, 13, 14]) {
            return 'Royal Flush';
        }

        /*
         * Straight Flush:
         * Five cards in a row, all with the same suit.
         */
        if ($isFlush && $isStraight) {
            return 'Straight Flush';
        }

        /*
         * Four of a Kind:
         * The biggest count is 4, like 9h 9d 9s 9c 2h.
         */
        if ($valueCounts[0] === 4) {
            return 'Four of a Kind';
        }

        /*
         * Full House:
         * One value appears 3 times and another value appears 2 times.
         */
        if ($valueCounts[0] === 3 && $valueCounts[1] === 2) {
            return 'Full House';
        }

        /*
         * Flush:
         * All same suit, but not a straight flush or royal flush because those
         * were already cheked above.
         */
        if ($isFlush) {
            return 'Flush';
        }

        /*
         * Straight:
         * Five values in a row, but mixed suits.
         */
        if ($isStraight) {
            return 'Straight';
        }

        /*
         * Three of a Kind:
         * One value appears 3 times, but it is not a full house because the
         * full-house case already returned above.
         */
        if ($valueCounts[0] === 3) {
            return 'Three of a Kind';
        }

        /*
         * Two Pair:
         * The two biggest counts are both 2.
         */
        if ($valueCounts[0] === 2 && $valueCounts[1] === 2) {
            return 'Two Pair';
        }

        /*
         * One Pair:
         * The biggest count is 2, and it was not two pair.
         */
        if ($valueCounts[0] === 2) {
            return 'One Pair';
        }

        /*
         * If none of the patterns matched, the hand is just ranked by its high
         * card. Since this class only returns the rank name, "High Card" is the
         * final fallback.
         */
        return 'High Card';
    }

    private function isStraight(array $uniqueValues): bool
    {
        /*
         * A straight needs five different values.
         * If there are duplicates, it cannot be a real five-card straight.
         */
        if (count($uniqueValues) !== 5) {
            return false;
        }

        /*
         * Normal straight:
         * If the cards are sorted, the highest card minus the lowest card will
         * be 4. Example: [5, 6, 7, 8, 9], and 9 - 5 = 4.
         *
         * Ace-low straight:
         * Ace normally has value 14, but A-2-3-4-5 is also a valid straight.
         * Sorted with Ace as 14, that looks like [2, 3, 4, 5, 14], so it needs
         * its own explicit check.
         */
        return $uniqueValues[4] - $uniqueValues[0] === 4
            || $uniqueValues === [2, 3, 4, 5, 14];
    }
}
