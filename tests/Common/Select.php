<?php

namespace NextSeason\Test\Model\SQL;

use PHPUnit\Framework\TestCase;
use NextSeason\Model\SQL\Builder;

final class SelectTest extends TestCase {

    public function testSelectFunctionReturnValue() : void {
        $this->assertInstanceOf( Builder::class, ( new Builder() )->select() );
    }

    public function testGeneratedSelectSQL() : void {
        $builder = new Builder();

        $builder->select()->from( 'tbl_name' );

        $this->assertEquals(
            $builder->query(),
            'SELECT * FROM `tbl_name`'
        );

        $builder->from( 'tbl_name2' );

        $this->assertEquals(
            $builder->query(),
            'SELECT * FROM `tbl_name`, `tbl_name2`'
        );

        $builder->from( 'tbl_name2' );

        $this->assertEquals(
            $builder->query(),
            'SELECT * FROM `tbl_name`, `tbl_name2`'
        );

        $builder->from( 'tbl_name3 AS t3' );

        $this->assertEquals(
            $builder->query(),
            'SELECT * FROM `tbl_name`, `tbl_name2`, `tbl_name3` AS t3'
        );

    }
}
