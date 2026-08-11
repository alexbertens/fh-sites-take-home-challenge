<?php
namespace PokerHand;

use PHPUnit\Framework\TestCase;

class PokerHandTest extends TestCase
{
    /**
     * @test
     */
    public function itCanRankARoyalFlush()
    {
        $hand = new PokerHand('As Ks Qs Js 10s');
        $this->assertEquals('Royal Flush', $hand->getRank());
    }

    /**
     * @test
     */
    public function itCanRankAPair()
    {
        $hand = new PokerHand('Ah As 10c 7d 6s');
        $this->assertEquals('One Pair', $hand->getRank());
    }

    /**
     * @test
     */
    public function itCanRankTwoPair()
    {
        $hand = new PokerHand('Kh Kc 3s 3h 2d');
        $this->assertEquals('Two Pair', $hand->getRank());
    }

    /**
     * @test
     */
    public function itCanRankAFlush()
    {
        $hand = new PokerHand('Kh Qh 6h 2h 9h');
        $this->assertEquals('Flush', $hand->getRank());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('additionalHandRankProvider')]
    public function testItCanRankAdditionalHands($hand, $expectedRank)
    {
        $hand = new PokerHand($hand);

        $this->assertEquals($expectedRank, $hand->getRank());
    }

    public static function additionalHandRankProvider()
    {
        return [
            'straight flush' => ['9c 8c 7c 6c 5c', 'Straight Flush'],
            'four of a kind' => ['9h 9d 9s 9c 2h', 'Four of a Kind'],
            'full house' => ['Qh Qd Qs 3c 3d', 'Full House'],
            'straight' => ['9h 8d 7s 6c 5h', 'Straight'],
            'three of a kind' => ['4h 4d 4s Kc 2d', 'Three of a Kind'],
            'high card' => ['Ah Kd 9s 7c 3h', 'High Card'],
            'ace-low straight' => ['Ah 2d 3s 4c 5h', 'Straight'],
            'ace-low straight flush' => ['Ah 2h 3h 4h 5h', 'Straight Flush'],
            'ten abbreviation' => ['As Ks Qs Js Ts', 'Royal Flush'],
            'duplicate values are not a straight' => ['Ah 2d 3s 4c 4h', 'One Pair'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidHandProvider')]
    public function testItRejectsInvalidHands($hand)
    {
        $this->expectException(\InvalidArgumentException::class);

        new PokerHand($hand);
    }

    public static function invalidHandProvider()
    {
        return [
            'null hand' => [null],
            'six cards' => ['As Ks Qs Js 10s 9s'],
            'three cards' => ['As Ks Qs'],
            'invalid card value' => ['16h Ks Qs Js 10s'],
            'null card text' => ['Ah Ks null Qd 3c'],
        ];
    }
}
