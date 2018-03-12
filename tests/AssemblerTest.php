<?php

namespace NextSeason\Test\Model\SQL;

use PHPUnit\Framework\TestCase;
use NextSeason\Model\SQL\{ Quoter, Assembler, Placeholder, Group, Expression };

final class AssemblerTest extends TestCase {
    public function testAssembleIn() : void {
        $quoter = new Quoter();

        $assembler = new Assembler( $quoter );

        $this->assertEquals(
            $assembler->in( [ 10, 20, 30 ] ),
            [
                'query' => '( ?, ?, ? )',
                'params' => [ 10, 20, 30 ]
            ]
        );

        $this->assertEquals(
            $assembler->in( [ 
                [ 10, 20 ],
                [ 30, 40 ]
            ] ),
            [
                'query' => '( ( ?, ? ), ( ?, ? ) )',
                'params' => [ 10, 20, 30, 40 ]
            ]
        );

        $this->assertEquals(
            $assembler->in( [ 
                [ [ 1, 2 ], [ 3, 4 ] ],
                [ [ 5, 6 ], [ 7, 8 ] ]
            ] ),
            [
                'query' => '( ( ( ?, ? ), ( ?, ? ) ), ( ( ?, ? ), ( ?, ? ) ) )',
                'params' => [ 1, 2, 3, 4, 5, 6, 7, 8 ]
            ]
        );

        $this->assertEquals(
            $assembler->in( [ new Expression( 'id', $quoter ), 40 ] ),
            [
                'query' => '( `id`, ? )',
                'params' => [ 40 ]
            ]
        );

        $this->assertEquals(
            $assembler->in( [ new Expression( 'CONCAT( prefix + "str" )', $quoter ), 40 ] ),
            [
                'query' => '( CONCAT( `prefix` + ? ), ? )',
                'params' => [ 'str', 40 ]
            ]
        );

    }

    public function itestAssembleColumns() : void {

        $assembler = new Assembler( new Quoter() );

        $this->assertEquals(
            $assembler->columns(),
            [
                'query' => '*',
                'params' => []
            ]
        );

        $this->assertEquals(
            $assembler->columns( [ '*' ] ),
            [
                'query' => '*',
                'params' => []
            ]
        );

        $this->assertEquals(
            $assembler->columns( [] ),
            [
                'query' => '*',
                'params' => []
            ]
        );

        $this->assertEquals( 
            $assembler->columns( [ 'a', 'b', 'c' ] ), 
            [
                'query' => '`a`, `b`, `c`',
                'param' => []
            ]
        );

        $this->assertEquals(
            $assembler->columns( [ 'a as c1', 'b as c2', 'c' ] ),
            [
                'query' => '`a` as `c1`, `b` as `c2`, `c`',
                'params' => []
            ]
        );

        $this->assertEquals(
            $assembler->columns( [ 'a c1', 'b c2', 'c' ] ),
            [
                'query' => '`a` `c1`, `b` `c2`, `c`',
                'params' => []
            ]
        );

        $this->assertEquals(
            $assembler->columns( [ 't1.a as c1', 't1.b as c2', 'c' ] ),
            [
                'query' => '`t1`.`a` as `c1`, `t1`.`b` as `c2`, `c`',
                'params' => []
            ]
        );

        $this->assertEquals(
            $assembler->columns( [ 'count(*)', 'id' ] ),
            [
                'query' => 'count(*), `id`',
                'params' => []
            ]
        );

        $this->assertEquals(
            $assembler->columns( [ 'count(*) AS total', 'id' ] ),
            [
                'query' => 'count(*) AS `total`, `id`',
                'params' => []
            ]
        );

        $this->assertEquals(
            $assembler->columns( [ 'count( id ) AS total', 'id' ] ),
            [
                'query' => 'count( `id` ) AS `total`, `id`',
                'params' => []
            ]
        );

        $this->assertEquals(
            $assembler->columns( [ 'count(id) AS total', 'id' ] ),
            [
                'query' => 'count(`id`) AS `total`, `id`',
                'params' => []
            ]
        );

        $this->assertEquals(
            $assembler->columns( [ 't1.id', 'title' ] ),
            [
                'query' => '`t1`.`id`, `title`',
                'params' => []
            ]
        );
    }

    public function testAssembleTables() : void {

        $assembler = new Assembler( new Quoter() );

        $this->assertEquals(
            $assembler->tables( [ 'tbl' ] ),
            '`tbl`'
        );

        $this->assertEquals(
            $assembler->tables( [ 'tbl AS t1' ] ),
            '`tbl` AS `t1`'
        );
    }

    public function testAssembleConditions() : void {

        $quoter = new Quoter();

        $assembler = new Assembler( $quoter );

        $this->assertEquals(
            $assembler->conditions( [ [ 'id', NULL ] ] ),
            [
                'query' => '`id` IS ?',
                'params' => [ NULL ]
            ]
        );

        $this->assertEquals(
            $assembler->conditions( [ [ 'id', 'IS', NULL ] ] ),
            [
                'query' => '`id` IS ?',
                'params' => [ NULL ]
            ]
        );

        $this->assertEquals(
            $assembler->conditions( [
                [ 'id', 1 ]
            ] ),
            [ 
                'query' => '`id` = ?',
                'params' => [ 1 ]
            ]
        );

        $this->assertEquals(
            $assembler->conditions( [
                [ 'id', 1 ]
            ] ),
            [
                'query' => '`id` = ?',
                'params' => [ 1 ]
            ]
        );

        $this->assertEquals(
            $assembler->conditions( [
                [ 'id', '<>', 1 ]
            ] ),
            [
                'query' => '`id` <> ?',
                'params' => [ 1 ]
            ]
        );

        $this->assertEquals(
            $assembler->conditions( [
                [ 'id', 1 ],
                Placeholder::AND,
                [ 'age', '>', 10 ],
                Placeholder::AND,
                [ 'name', 'like', '%l' ],
                Placeholder::AND,
                [ 'and', 'x' ]
            ] ),
            [
                'query' => '`id` = ? AND `age` > ? AND `name` like ? AND `and` = ?',
                'params' => [ 1, 10, '%l', 'x' ]
            ]
        );

        $this->assertEquals(
            $assembler->conditions( [
                [ 't1.id', new Expression( 't2.id', $quoter ) ],
                Placeholder::AND,
                [ 't1.age', '>', new Expression( 't2.age', $quoter ) ],
                Placeholder::AND,
                [ 't1.ctime', '>', new Expression( 't2.id + t1.ctime', $quoter ) ],
                Placeholder::AND,
                [ 't2.ctime', 'LIKE', new Expression( 't2.id + "abc"', $quoter ) ]
            ] ),
            [
                'query' => '`t1`.`id` = `t2`.`id` AND `t1`.`age` > `t2`.`age` AND `t1`.`ctime` > `t2`.`id` + `t1`.`ctime` AND `t2`.`ctime` LIKE `t2`.`id` + ?',
                'params' => [ 'abc' ]
            ]
        );

        $this->assertEquals(
            $assembler->conditions( [
                [ 'age', 'BETWEEN', [ 10, 20 ] ]
            ] ),
            [
                'query' => '`age` BETWEEN ? AND ?',
                'params' => [ 10, 20 ]
            ]
        );

        $this->assertEquals(
            $assembler->conditions( [
                [ 'age', 'NOT BETWEEN', [ 10, 20 ] ]
            ] ),
            [
                'query' => '`age` NOT BETWEEN ? AND ?',
                'params' => [ 10, 20 ]
            ]
        );

        $this->assertEquals(
            $assembler->conditions( [
                [ 'age', 'NOT BETWEEN', [ 'a', 'z' ] ]
            ] ),
            [
                'query' => '`age` NOT BETWEEN ? AND ?',
                'params' => [ 'a', 'z' ]
            ]
        );

        $this->assertEquals(
            $assembler->conditions( [
                [ 'age', 'IN', [ 10, 20 ] ]
            ] ),
            [
                'query' => "`age` IN ( ?, ? )",
                'params' => [ 10, 20 ]
            ]
        );

        $this->assertEquals(
            $assembler->conditions( [
                [ 'age', 'NOT IN', [ 10, 20 ] ]
            ] ),
            [
                'query' => "`age` NOT IN ( ?, ? )",
                'params' => [ 10, 20 ]
            ]
        );

        $this->assertEquals(
            $assembler->conditions( [
                [ 'age', 'NOT IN', [ 'a', 'b' ] ]
            ] ),
            [
                'query' => "`age` NOT IN ( ?, ? )",
                'params' => [ 'a', 'b' ]
            ]
        );
    }

    public function testAssembleConditionsWithGroups() : void {

        $assembler = new Assembler( new Quoter() );

        $g1 = new Group();
        $g1->and( 'id', 1 );
        $g1->or( 'id', 2 );

        $this->assertEquals( 
            $assembler->conditions( [ $g1 ] ),
            [ 
                'query' => '( `id` = ? OR `id` = ? )',
                'params' => [ 1, 2 ]
            ]
        );

        $g2 = new Group();
        $g2->and( 'id', 3 );
        $g2->or( 'id', 4 );

        $this->assertEquals( 
            $assembler->conditions( [ $g1, Placeholder::AND, $g2 ] ),
            [
                'query' => '( `id` = ? OR `id` = ? ) AND ( `id` = ? OR `id` = ? )',
                'params' => [ 1, 2, 3, 4 ]
            ]
        );

        $g3 = new Group();
        $g3->and( 'age', '>', 10 );
        $g3->or( 'age', '<', 5 );

        $g2->and( $g3 );

        $this->assertEquals( 
            $assembler->conditions( [ $g1, Placeholder::AND, $g2 ] ),
            [
                'query' => '( `id` = ? OR `id` = ? ) AND ( `id` = ? OR `id` = ? AND ( `age` > ? OR `age` < ? ) )',
                'params' => [ 1, 2, 3, 4, 10, 5 ]
            ]
        );
    }

    public function testAssemblePartitions() : void {

        $assembler = new Assembler( new Quoter() );

        $this->assertEquals( 
            $assembler->partitions( [ 'p1' ] ),
            '( `p1` )'
        );

        $this->assertEquals( 
            $assembler->partitions( [ 'p1', 'p2' ] ),
            '( `p1`, `p2` )'
        );
    }

    public function testAssembleLimit() : void {
        $assembler = new Assembler( new Quoter() );

        $this->assertEquals(
            $assembler->limit( [ 5 ] ),
            [
                'query' => '?',
                'params' => [ 5 ]
            ]
        );
        $this->assertEquals(
            $assembler->limit( [ 5, 10 ] ),
            [
                'query' => '?, ?',
                'params' => [ 5, 10 ]
            ]
        );
    }

    public function testAssembleOrderBy() : void {
        $assembler = new Assembler( new Quoter() );

        $this->assertEquals( $assembler->orderBy( [ NULL ] ), 'NULL' );
        $this->assertEquals(
            $assembler->orderBy( [
                'id', 'age'
            ] ),
            '`id`, `age`'
        );

        $this->assertEquals(
            $assembler->orderBy( [
                [ 'id', 'ASC' ],
                [ 'age', 'DESC' ]
            ] ),
            '`id` ASC, `age` DESC'
        );
    }

    public function testAssembleSet() : void {
        $quoter = new Quoter();

        $assembler = new Assembler( $quoter );

        $this->assertEquals(
            $assembler->set( [ 
                'id' => 1,
                'name' => 'x'
            ] ),
            [
                'query' => '`id` = ?, `name` = ?',
                'params' => [ 1, 'x' ]
            ]
        );

        $this->assertEquals(
            $assembler->set( [ 
                'id' => new Expression( 'id + 1', $quoter ),
                'name' => 'x'
            ] ),
            [
                'query' => '`id` = `id` + ?, `name` = ?',
                'params' => [ 1, 'x' ]
            ]
        );

        $this->assertEquals(
            $assembler->set( [ 
                'id' => new Expression( 'id + 1', $quoter ),
                'name' => new Expression( 'CONCAT( name, \'x\' )', $quoter )
            ] ),
            [
                'query' => '`id` = `id` + ?, `name` = CONCAT( `name`, ? )',
                'params' => [ 1, 'x' ]
            ]
        );

        $this->assertEquals(
            $assembler->set( [ 
                't1.name' => new Expression( 't2.name', $quoter )
            ] ),
            [
                'query' => '`t1`.`name` = `t2`.`name`',
                'params' => []
            ]
        );
    }

    public function testAssembleGroupBy() : void {
        $assembler = new Assembler( new Quoter() );

        $this->assertEquals(
            $assembler->groupBy( [ 'id', 'name' ] ),
            '`id`, `name`'
        );

        $this->assertEquals(
            $assembler->groupBy( [ 
                [ 'id', 'ASC', 'INDEX' => [] ],
                [ 'name', 'DESC WITH ROLLUP' ]
            ] ),
            '`id` ASC, `name` DESC WITH ROLLUP'
        );
    }

    public function testAssembleJoin() : void {

        $quoter = new Quoter();

        $assembler = new Assembler( $quoter );

        $this->assertEquals(
            $assembler->join( [ 
                't1',
                [ Placeholder::JOIN, 't2' ],
                [ Placeholder::INNER_JOIN, 't3' ],
                [ Placeholder::CROSS_JOIN, 't4' ]
            ] ),
            [
                'query' => '`t1` JOIN `t2` INNER JOIN `t3` CROSS JOIN `t4`',
                'params' => []
            ]
        );

        $this->assertEquals(
            $assembler->join( [ 
                't1',
                [ Placeholder::JOIN, [
                    't2',
                    [ Placeholder::JOIN, 't3' ]
                ] ],
                [ Placeholder::JOIN, 't4' ]
            ] ),
            [
                'query' => '`t1` JOIN ( `t2` JOIN `t3` ) JOIN `t4`',
                'params' => []
            ]
        );

        $this->assertEquals(
            $assembler->join( [ 
                't1',
                [ Placeholder::JOIN, [
                    't2',
                    [ Placeholder::JOIN, 't3' ],
                    [ Placeholder::ON, [ [ 't1.id', new Expression( 't2.id', $quoter ) ] ] ]
                ] ],
                [ Placeholder::ON, [ 
                    [ 't1.id', new Expression( 't2.id', $quoter ) ],
                    Placeholder::AND,
                    [ 't1.sex', new Expression( 't2.sex', $quoter ) ]
                ] ]
            ] ),
            [
                'query' => '`t1` JOIN ( `t2` JOIN `t3` ON `t1`.`id` = `t2`.`id` ) ON `t1`.`id` = `t2`.`id` AND `t1`.`sex` = `t2`.`sex`',
                'params' => []
            ]
        );

        $this->assertEquals(
            $assembler->join( [ 
                't1',
                [ Placeholder::JOIN, [
                    't2',
                    [ Placeholder::JOIN, 't3' ]
                ] ],
                [ Placeholder::JOIN, 't4' ],
                [ Placeholder::USING, 'c1' ]
            ] ),
            [
                'query' => '`t1` JOIN ( `t2` JOIN `t3` ) JOIN `t4` USING ( `c1` )',
                'params' => []
            ]
        );

        $this->assertEquals(
            $assembler->join( [ 
                't1',
                [ Placeholder::JOIN, [
                    't2',
                    [ Placeholder::JOIN, 't3' ]
                ] ],
                [ Placeholder::JOIN, 't4' ],
                [ Placeholder::USING, [ 'c1', 'c2', 'c3' ] ]
            ] ),
            [
                'query' => '`t1` JOIN ( `t2` JOIN `t3` ) JOIN `t4` USING ( `c1`, `c2`, `c3` )',
                'params' => []
            ]
        );

        $this->assertEquals(
            $assembler->join( [ 
                'tbl_name AS t1',
                [ Placeholder::JOIN, [
                    't2',
                    [ Placeholder::JOIN, 't3' ]
                ] ],
                [ Placeholder::JOIN, 't4' ],
                [ Placeholder::USING, [ 'c1', 'c2', 'c3' ] ]
            ] ),
            [
                'query' => '`tbl_name` AS `t1` JOIN ( `t2` JOIN `t3` ) JOIN `t4` USING ( `c1`, `c2`, `c3` )',
                'params' => []
            ]
        );
    }
}
