<?php

namespace NextSeason\Test\Model\SQL;

use PHPUnit\Framework\TestCase;
use NextSeason\Model\SQL\Quoter;

final class QuoterTest extends TestCase {
    public function testQuoted() : void {

        $quoter = new Quoter();
        $this->assertTrue( $quoter->quoted( '``' ) );
        $this->assertTrue( $quoter->quoted( '`abc`' ) );
        $this->assertTrue( $quoter->quoted( ' `abc` ' ) );
        $this->assertTrue( $quoter->quoted( '`abc` `c1`' ) );
        $this->assertTrue( $quoter->quoted( '`abc`, `c1`' ) );

        $this->assertFalse( $quoter->quoted( '`' ) );
        $this->assertFalse( $quoter->quoted( '`abc' ) );
        $this->assertFalse( $quoter->quoted( '`abc`.id AS `c1`' ) );
    }

    public function testQuote() : void {
        $quoter = new Quoter();

        $this->assertEquals( $quoter->name( '*', false ), '*' );

        $this->assertEquals( $quoter->name( '*.*', false ), '*.*' );

        $this->assertEquals(
            $quoter->name( 'table.*', false ),
            '`table`.*'
        );

        $this->assertEquals(
            $quoter->name( '`table.column`' ),
            '`table.column`'
        );

        $this->assertEquals(
            $quoter->name( 'count(*)',false ),
            'count(*)'
        );

        $this->assertEquals(
            $quoter->name( 'count(id)', false ),
            'count(`id`)'
        );

        $this->assertEquals(
            $quoter->name( 'table.column', false ),
            '`table`.`column`'
        );

        $this->assertEquals(
            $quoter->name( 'tbl_name' ),
            '`tbl_name`'
        );

        $this->assertEquals(
            $quoter->name( 'tbl_name', false ),
            '`tbl_name`'
        );

        $this->assertEquals(
            $quoter->name( '`tbl_name` AS `t1`', false ),
            '`tbl_name` AS `t1`'
        );

        $this->assertEquals(
            $quoter->name( 'tbl AS t1', false ),
            '`tbl` AS `t1`'
        );

        $this->assertEquals(
            $quoter->name( 'tbl AS t1, tbl2 AS t2', false ),
            '`tbl` AS `t1`, `tbl2` AS `t2`'
        );

        $this->assertEquals(
            $quoter->name( 'tbl.name AS c1', false ),
            '`tbl`.`name` AS `c1`'
        );

        $this->assertEquals(
            $quoter->name( 'tbl.name AS name, tbl.age AS age', false ),
            '`tbl`.`name` AS `name`, `tbl`.`age` AS `age`'
        );

        $this->assertEquals(
            $quoter->name( 'tbl.id IN ( 1, 2, 3 )', false ),
            '`tbl`.`id` IN ( 1, 2, 3 )'
        );

        $this->assertEquals(
            $quoter->name( 'id IN ( 1, 2, 3 )', false ),
            '`id` IN ( 1, 2, 3 )'
        );

        $this->assertEquals(
            $quoter->name( 'id >= 1', false ),
            '`id` >= 1'
        );

        $this->assertEquals(
            $quoter->name( 'id >= ANY( 1, 2, 3 )', false ),
            '`id` >= ANY( 1, 2, 3 )'
        );

        $this->assertEquals(
            $quoter->name( 'tbl.id >= 1', false ),
            '`tbl`.`id` >= 1'
        );

        $this->assertEquals(
            $quoter->name( 'name like "%l"', false ),
            '`name` like "%l"'
        );

        $this->assertEquals(
            $quoter->name( 'like = 1', false ),
            '`like` = 1'
        );

        $this->assertEquals(
            $quoter->name( 'column1, column2, column3, column4', false ),
            '`column1`, `column2`, `column3`, `column4`'
        );

        $this->assertEquals(
            $quoter->name( 'column1 AS c1, column2, column3, column4', false ),
            '`column1` AS `c1`, `column2`, `column3`, `column4`'
        );

        $this->assertEquals(
            $quoter->name( '*, *.*, f.bar, foo.bar, CONCAT( \'foo.bar\', "baz.dib" ) AS zim', false ),
            '*, *.*, `f`.`bar`, `foo`.`bar`, CONCAT( \'foo.bar\', "baz.dib" ) AS `zim`'
        );
    }
}
