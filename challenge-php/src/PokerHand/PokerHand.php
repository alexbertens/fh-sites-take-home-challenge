<?php

namespace PokerHand;

class PokerHand
{
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

    private string $rank;

    public function __construct(?string $hand)
    {
        if ($hand === null) {
            throw new \InvalidArgumentException('A poker hand cannot be null.');
        }

        [$values, $suits] = $this->parseHand($hand);

        $this->rank = $this->rankHand($values, $suits);
    }

    public function getRank(): string
    {
        return $this->rank;
    }

    private function parseHand(string $hand): array
    {
        $values = [];
        $suits = [];
        $cards = preg_split('/\s+/', trim($hand), -1, PREG_SPLIT_NO_EMPTY);

        if (count($cards) !== 5) {
            throw new \InvalidArgumentException('A poker hand must contain exactly 5 cards.');
        }

        foreach ($cards as $card) {
            // The suit is always the last character, so "10s" becomes value "10" and suit "s".
            $value = strtoupper(substr($card, 0, -1));
            $suit = strtolower(substr($card, -1));

            if (!isset(self::CARD_VALUES[$value]) || !in_array($suit, ['c', 'd', 'h', 's'], true)) {
                throw new \InvalidArgumentException('Invalid card: ' . $card);
            }

            $values[] = self::CARD_VALUES[$value];
            $suits[] = $suit;
        }

        return [$values, $suits];
    }

    private function rankHand(array $values, array $suits): string
    {
        // Sorting the duplicate counts lets us spot pairs/triples/quads without caring about card order.
        $valueCounts = array_values(array_count_values($values));
        rsort($valueCounts);

        $uniqueValues = array_values(array_unique($values));
        sort($uniqueValues);

        $isFlush = count(array_unique($suits)) === 1;
        $isStraight = $this->isStraight($uniqueValues);

        // Check strongest to weakest so a hand like a straight flush is not labeled as a plain flush.
        if ($isFlush && $uniqueValues === [10, 11, 12, 13, 14]) {
            return 'Royal Flush';
        }

        if ($isFlush && $isStraight) {
            return 'Straight Flush';
        }

        if ($valueCounts[0] === 4) {
            return 'Four of a Kind';
        }

        if ($valueCounts[0] === 3 && $valueCounts[1] === 2) {
            return 'Full House';
        }

        if ($isFlush) {
            return 'Flush';
        }

        if ($isStraight) {
            return 'Straight';
        }

        if ($valueCounts[0] === 3) {
            return 'Three of a Kind';
        }

        if ($valueCounts[0] === 2 && $valueCounts[1] === 2) {
            return 'Two Pair';
        }

        if ($valueCounts[0] === 2) {
            return 'One Pair';
        }

        return 'High Card';
    }

    private function isStraight(array $uniqueValues): bool
    {
        if (count($uniqueValues) !== 5) {
            return false;
        }

        // A-2-3-4-5 is the only straight where Ace acts like the low card.
        return $uniqueValues[4] - $uniqueValues[0] === 4
            || $uniqueValues === [2, 3, 4, 5, 14];
    }
}
