<?php

namespace NextSeason\Test\Model\SQL;

use PHPUnit\Framework\TestCase;
use NextSeason\Model\SQL\Common\Assembler;
use NextSeason\Model\SQL\MySQL\Quoter;
use NextSeason\Model\SQL\Common\Group;

final class AssemblerTest extends TestCase {
    public function testAssembleIn() : void {
        $assembler = new Assembler( new Quoter() );

        $this->assertEquals(
            $assembler->in( [ 10, 20, 30 ] ),
            "( 10, 20, 30 )"
        );

        $this->assertEquals(
            $assembler->in( [ 
                [ 10, 20 ],
                [ 30, 40 ]
            ] ),
            "( ( 10, 20 ), ( 30, 40 ) )"
        );

        $this->assertEquals(
            $assembler->in( [ 
                [ [ 1, 2 ], [ 3, 4 ] ],
                [ [ 5, 6 ], [ 7, 8 ] ]
            ] ),
            "( ( ( 1, 2 ), ( 3, 4 ) ), ( ( 5, 6 ), ( 7, 8 ) ) )"
        );
    }

    public function testAssembleColumns() : void {

        $assembler = new Assembler( new Quoter() );

        $this->assertEquals( $assembler->columns(), '*' );

        $this->assertEquals( $assembler->columns( [ '*' ] ), '*' );

        $this->assertEquals( $assembler->columns( [] ), '*' );

        $this->assertEquals( 
            $assembler->columns( [ 'a', 'b', 'c' ] ), 
            '`a`, `b`, `c`'
        );

        $this->assertEquals(
            $assembler->columns( [ 'a as c1', 'b as c2', 'c' ] ),
            '`a` as `c1`, `b` as `c2`, `c`'
        );

        $this->assertEquals(
            $assembler->columns( [ 'a c1', 'b c2', 'c' ] ),
            '`a` `c1`, `b` `c2`, `c`'
        );

        $this->assertEquals(
            $assembler->columns( [ 't1.a as c1', 't1.b as c2', 'c' ] ),
            '`t1`.`a` as `c1`, `t1`.`b` as `c2`, `c`'
        );

        $this->assertEquals(
            $assembler->columns( [ 'count(*)', 'id' ] ),
            'count(*), `id`'
        );

        $this->assertEquals(
            $assembler->columns( [ 'count(*) AS total', 'id' ] ),
            'count(*) AS `total`, `id`'
        );

        $this->assertEquals(
            $assembler->columns( [ 'count( id ) AS total', 'id' ] ),
            'count( `id` ) AS `total`, `id`'
        );

        $this->assertEquals(
            $assembler->columns( [ 'count(id) AS total', 'id' ] ),
            'count(`id`) AS `total`, `id`'
        );

        $this->assertEquals(
            $assembler->columns( [ 't1.id', 'title' ] ),
            '`t1`.`id`, `title`'
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

        $assembler = new Assembler( new Quoter() );

        $this->assertEquals(
            $assembler->conditions( [ [ 'id', NULL ] ] ),
            '`id` IS NULL'
        );

        $this->assertEquals(
            $assembler->conditions( [ [ 'id', 'IS', NULL ] ] ),
            '`id` IS NULL'
        );

        $this->assertEquals(
            $assembler->conditions( [
                [ 'id = 1' ]
            ] ),
            '`id` = 1'
        );

        $this->assertEquals(
            $assembler->conditions( [
                [ 'id', 1 ]
            ] ),
            '`id` = 1'
        );

        $this->assertEquals(
            $assembler->conditions( [
                [ 'id', 1 ]
            ] ),
            '`id` = 1'
        );

        $this->assertEquals(
            $assembler->conditions( [
                [ 'id', '<>', 1 ]
            ] ),
            '`id` <> 1'
        );

        $this->assertEquals(
            $assembler->conditions( [
                [ 'id', 1 ],
                'AND',
                [ 'age', '>', 10 ],
                'AND',
                'name like "%l"',
                'AND',
                [ 'and', 'x' ]
            ] ),
            '`id` = 1 AND `age` > 10 AND `name` like "%l" AND `and` = \'x\''
        );

        $this->assertEquals(
            $assembler->conditions( [
                [ 'age', 'BETWEEN', [ 10, 20 ] ]
            ] ),
            "`age` BETWEEN 10 AND 20"
        );

        $this->assertEquals(
            $assembler->conditions( [
                [ 'age', 'NOT BETWEEN', [ 10, 20 ] ]
            ] ),
            "`age` NOT BETWEEN 10 AND 20"
        );

        $this->assertEquals(
            $assembler->conditions( [
                [ 'age', 'IN', [ 10, 20 ] ]
            ] ),
            "`age` IN ( 10, 20 )"
        );

        $this->assertEquals(
            $assembler->conditions( [
                [ 'age', 'NOT IN', [ 10, 20 ] ]
            ] ),
            "`age` NOT IN ( 10, 20 )"
        );

        $this->assertEquals(
            $assembler->conditions( [
                [ 'age', 'NOT IN', [ 'a', 'b' ] ]
            ] ),
            "`age` NOT IN ( 'a', 'b' )"
        );
    }

    public function testAssembleConditionsWithGroups() : void {

        $assembler = new Assembler( new Quoter() );

        $g1 = new Group();
        $g1->and( 'id', 1 );
        $g1->or( 'id', 2 );

        $this->assertEquals( 
            $assembler->conditions( [ $g1 ] ),
            "( `id` = 1 OR `id` = 2 )"
        );

        $g2 = new Group();
        $g2->and( 'id', 3 );
        $g2->or( 'id', 4 );

        $this->assertEquals( 
            $assembler->conditions( [ $g1, 'AND', $g2 ] ),
            "( `id` = 1 OR `id` = 2 ) AND ( `id` = 3 OR `id` = 4 )"
        );

        $g3 = new Group();
        $g3->and( 'age', '>', 10 );
        $g3->or( 'age', '<', 5 );

        $g2->and( $g3 );

        $this->assertEquals( 
            $assembler->conditions( [ $g1, 'AND', $g2 ] ),
            "( `id` = 1 OR `id` = 2 ) AND ( `id` = 3 OR `id` = 4 AND ( `age` > 10 OR `age` < 5 ) )"
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

        $this->assertEquals( $assembler->limit( [ 5 ] ), '5' );
        $this->assertEquals( $assembler->limit( [ 5, 10 ] ), '5, 10' );
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
        $assembler = new Assembler( new Quoter() );

        $this->assertEquals(
            $assembler->set( [ 
                'id' => 1,
                'name' => 'x'
            ] ),
            '`id` = 1, `name` = \'x\''
        );

        $this->assertEquals(
            $assembler->set( [ 
                'id' => 1,
                'name' => 'x'
            ] ),
            '`id` = 1, `name` = \'x\''
        );

        $this->assertEquals(
            $assembler->set( [ 
                'id' => [ 'id + 1' ],
                'name' => 'x'
            ] ),
            '`id` = `id` + 1, `name` = \'x\''
        );

        $this->assertEquals(
            $assembler->set( [ 
                'id' => [ 'id + 1' ],
                'name' => [ 'CONCAT( name, \'x\' )' ]
            ] ),
            '`id` = `id` + 1, `name` = CONCAT( `name`, \'x\' )'
        );

        $this->assertEquals(
            $assembler->set( [ 
                't1.name' => [ 't2.name' ]
            ] ),
            '`t1`.`name` = `t2`.`name`'
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

        $assembler = new Assembler( new Quoter() );

        $this->assertEquals(
            $assembler->groupBy( [ 'id', 'name' ] ),
            '`id`, `name`'
        );

    }
}
