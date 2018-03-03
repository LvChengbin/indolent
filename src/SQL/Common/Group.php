<?php

namespace NextSeason\Model\SQL\Common;

class Group {

    public $conditions = [];

    protected function condition( $condition, $name, $operator = null, $value = null ) {
        /**
         * self::condition( $condition, '`id` = 1' )
         * self::condition( $condition, '`id` >= 1' )
         * self::condition( $condition, [ [ 'id', 1 ], [ 'title', 'x' ] ] )
         * self::condition( $condition, [ [ 'id', '>', 1 ], [ 'title', 'x' ] ] )
         * self::condition( $condition, [ 'id > 1', 'id <= 100', [ 'title', 'x' ] ] )
         */
        if( is_null( $operator ) && is_null( $value ) ) {

            if( is_array( $name ) ) {
                foreach( $name as $value ) {
                    array_push( $this->conditions, $condition );
                    array_push( $this->conditions, $value );
                }
                return $this;
            }

            array_push( $this->conditions, $condition );
            array_push( $this->conditions, $name );
            return $this;
        }

        array_push( $this->conditions, $condition );

        /**
         * self::condition( $condition, 'id', 1 );
         */
        if( is_null( $value ) ) {
            array_push( $this->conditions, [ $name, $operator ] );
            return $this;
        }

        array_push( $this->conditions, [ $name, $operator, $value ] );        

        return $this;
    }

    public function and( $name, $operator = null, $value = null ) {
        return $this->condition( 'AND', $name, $operator, $value );
    }

    public function or( $name, $operator = null, $value = null ) {
        return $this->condition( 'OR', $name, $operator, $value );
    }

    public function conditions() {
        $conditions = $this->conditions;

        if( !count( $conditions ) ) {
            return [];
        }

        if( $conditions[ 0 ] === 'AND' || $conditions[ 0 ] == 'OR' ) {
            array_shift( $conditions );
        }
        return $conditions;
    }
}
