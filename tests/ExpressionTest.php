<?php

namespace NextSeason\Test\Model\SQL;

use PHPUnit\Framework\TestCase;
use NextSeason\Model\SQL\{ Quoter, Expression };

final class ExpressionTest extends TestCase {
    public function testAll() : void {

        $quoter = new Quoter();

        $expression = new Expression( 'id', $quoter );

        $this->assertEquals(
            $expression->query,
            '`id`'
        );

        $this->assertEquals(
            $expression->params,
            []
        );

        $expression = new Expression( 'count( id )', $quoter );

        $this->assertEquals(
            $expression->query,
            'count( `id` )'
        );

        $this->assertEquals(
            $expression->params,
            []
        );

        $expression = new Expression( 'count( * )', $quoter );

        $this->assertEquals(
            $expression->query,
            'count( * )'
        );

        $this->assertEquals(
            $expression->params,
            []
        );

        $expression = new Expression( 'concat( t1.id, "str" )', $quoter );

        $this->assertEquals(
            $expression->query,
            'concat( `t1`.`id`, ? )'
        );

        $this->assertEquals(
            $expression->params,
            [ 'str' ]
        );

        $expression = new Expression( 'sum( t1.id, 1, 2 )', $quoter );

        $this->assertEquals(
            $expression->query,
            'sum( `t1`.`id`, ?, ? )'
        );

        $this->assertEquals(
            $expression->params,
            [ 1, 2 ]
        );

        $expression = new Expression( '"aa" + "bb" + "cc"', $quoter );

        $this->assertEquals(
            $expression->query,
            '? + ? + ?'
        );

        $this->assertEquals(
            $expression->params,
            [ 'aa', 'bb', 'cc' ]
        );

        $str = "\"'\"";

        $expression = new Expression( $str . ' + "bb" + "cc"', $quoter );

        $this->assertEquals(
            $expression->query,
            '? + ? + ?'
        );

        $this->assertEquals(
            $expression->params,
            [ '\'', 'bb', 'cc' ]
        );

        $expression = new Expression( 't1.name = t1.name + "str" + "suffix"', $quoter );

        $this->assertEquals(
            $expression->query,
            '`t1`.`name` = `t1`.`name` + ? + ?'
        );

        $this->assertEquals(
            $expression->params,
            [ 'str', 'suffix' ]
        );

        $expression = new Expression( 't1.name = t1.name + func( "str" ) + func( "func(\\"str\\")" ) + "suffix"', $quoter );

        $this->assertEquals(
            $expression->query,
            '`t1`.`name` = `t1`.`name` + func( ? ) + func( ? ) + ?'
        );

        $this->assertEquals(
            $expression->params,
            [ 'str', 'func(\"str\")', 'suffix' ]
        );

        $expression = new Expression( 't1.name = t1.name + func( ? ) + ?', $quoter );

        $this->assertEquals(
            $expression->query,
            '`t1`.`name` = `t1`.`name` + func( ? ) + ?'
        );

        $this->assertEquals(
            $expression->params,
            []
        );
    }
}
