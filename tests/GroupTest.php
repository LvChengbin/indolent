<?php

namespace NextSeason\Test\Model\SQL;

use PHPUnit\Framework\TestCase;
use NextSeason\Model\SQL\{ Placeholder, Group };

final class GroupTest extends TestCase {

    public function testAnd() : void {
        $this->assertSame(
            ( new Group() )->and( 'id', 1 )->conditions(),
            [ [ 'id', 1 ] ]
        );

        $this->assertEquals(
            ( new Group() )->and( 'id', 1 )->and( 'age', '>', 10 )->conditions(),
            [
                [ 'id', 1 ],
                Placeholder::AND,
                [ 'age', '>', 10 ]
            ]
        );

        $this->assertEquals(
            ( new Group() )->and( 'id = 1' )->and( 'age > 10' )->conditions(),
            [ 'id = 1', Placeholder::AND, 'age > 10' ]
        );

        $this->assertEquals(
            ( new Group() )->and( '`id` = 1' )->and( '`age` > 10' )->conditions(),
            [ '`id` = 1', Placeholder::AND, '`age` > 10' ]
        );

        $this->assertEquals(
            ( new Group() )->and( 't1.id', 1 )->conditions(),
            [ [ 't1.id', '1' ] ]
        );

        $this->assertEquals(
            ( new Group() )->and( [ [ 'id', 1 ], [ 'age', '>', 10 ], [ 'name = "x"' ] ] )->conditions(),
            [ [ 'id', 1 ], Placeholder::AND, [ 'age', '>', 10 ], Placeholder::AND, [ 'name = "x"' ] ]
        );

        $g1 = new Group();

        $this->assertEquals(
            ( new Group() )->and( [ [ 'id', 1 ] ] )->and( $g1 )->conditions(),
            [ [ 'id', 1 ], Placeholder::AND, $g1 ]
        );

    }
}
