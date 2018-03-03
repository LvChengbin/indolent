<?php

namespace NextSeason\Model\SQL\Common;
use NextSeason\Model\SQL\Common\Group;

class Assembler {
    private $quoter = null;

    public function __construct( Quoter $quoter ) {
        $this->quoter = $quoter;
    }

    /**
     * To assemble SQL for IN syntax
     *
     * @param array $in
     */
    public function in( array $in ) : string {

        $in = array_unique( $in, SORT_REGULAR );

        $res = [];

        foreach( $in as $value ) {
            /**
             * suportting nested IN syntax
             * eg. ( ( 1, 2 ), ( 3, 4 ) )
             */
            if( is_array( $value ) ) {
                array_push( $res, $this->in( $value ) );
            } else {
                array_push( $res, $this->quoter->value( $value ) );
            }
        }

        return '( ' . implode( ', ', $res ) . ' )';
    }

    public function columns( array $columns = null ) : string {
        if( empty( $columns ) ) {
            return '*';
        }

        for( $i = 0, $l = count( $columns ); $i < $l; ++$i ) {
            $item = $columns[ $i ];

            if( $this->quoter->quoted( $item ) ) {
                continue;
            }
            $columns[ $i ] = $this->quoter->name( $item, false );
        }

        return implode( $columns, ', ' );
    }

    public function tables( array $tables ) : string {

        /**
         * array_unique method will not rearrange the array index.
         * so to create a new array with array_values;
         */
        $tables = array_values( array_unique( $tables ) );

        for( $i = 0, $l = count( $tables ); $i < $l; ++$i ) {
            $item = $tables[ $i ];

            if( $this->quoter->quoted( $item, false ) ) {
                continue;
            }

            /**
             * tbl_name -> `tbl_name`
             * tbl_name AS t1 -> `tbl_name` AS t1
             */
            $tables[ $i ] = $this->quoter->name( $item, false ); 
        }

        return implode( $tables, ', ' );
    }

    public function conditions( $conditions ) : string {
        $quoter = $this->quoter;

        $query = '';

        foreach( $conditions as $value ) {
            if( $value instanceof Group ) {
                $query .= ' ( ' . $this->conditions( $value->conditions() ) . ' )';
            } else if( is_array( $value ) ) {
                $l = count( $value );

                if( $l === 1 ) {
                    $query .= ' ' . $quoter->name( $value[ 0 ], false );
                } else if( $l === 2 ) {
                    $query .= " {$quoter->name( $value[ 0 ] )} = {$this->quoter->value( $value[ 1 ] )}";
                } else {
                    $query .= " {$quoter->name( $value[ 0 ] )} {$value[ 1 ]} ";
                    switch( strtolower( $value[ 1 ] ) ) {
                        case 'between' :
                        case 'not between' :
                            $scope = $value[ 2 ];

                            if( is_array( $scope ) ) {
                                $query .= $scope[ 0 ] . ' AND ' . $scope[ 1 ];
                            } else {
                                $query .= $scope;
                            }
                            break;
                        case 'in' :
                        case 'not in' :
                            $query .= $this->in( $value[ 2 ] );
                            break;
                        default : 
                            $query .= $value[ 2 ];
                            break;
                    }
                }
            } else {
                if( $value === 'AND' || $value === 'OR' ) {
                    $query .= ' ' . $value;
                } else {
                    $query .= ' ' . $quoter->name( $value, false );
                }
            }
        }

        return trim( $query );
    }

    /**
     * To generate SQL part for PARTITION syntax.
     *
     * @param array $partitions An array filled with partitions.
     */
    public function partitions( array $partitions ) : string {
        return '( ' . $this->quoter->name( implode( ', ', $partitions ), false ) . ' )';
    }

    /**
     * GROUP BY column
     * GROUP BY column ASC|DESC
     * GROUP BY column WITH ROLLUP
     * GROUP BY column ASC|DESC WITH ROLLUP
     */
    public function groupBy( array $groups ) : string {
        $quoter = $this->quoter;
        $query = '';

        foreach( $groups as $value ) {
            if( is_array( $value ) ) {
                $query .= ', '. $quoter->name( $value[ 0 ] ) . ' ' . $value[ 1 ];
            } else {
                $query .= ', '. $quoter->name( $value );
            }
        }
        return trim( $query, ' ,' );
    }

    /**
     * ORDER BY `col1`, `col2`
     * ORDER BY NULL
     * ORDER BY `col_name` DESC
     * ORDER BY `col_name` ASC
     */
    public function orderBy( array $order ) : string {
        $query = '';
        foreach( $order as $value ) {
            if( is_null( $value ) ) {
                $query .= ', NULL';
            } else if( is_array( $value ) ) {
                $query .= ', ' . $this->quoter->name( $value[ 0 ] ) . ' ' . $value[ 1 ];
            } else {
                $query .= ', ' . $this->quoter->name( $value );
            }
        }
        return trim( $query, ' ,' );
    }

    public function limit( array $limit ) : string {
        return implode( ', ', $limit );
    }

    public function join( $join ) : string {
    }

    public function leftJoin( $join ) : string {

    }

    public function rightJoin( $join ) : string {
    }

    /**
     * [ 'col' => 'value' ]
     * [ 'col1' => 'col2' ]
     * [ 'col' => 'col + 1' ]
     * [ 'col' => 'FUNC(col)' ]
     * [ 'col' => 'CONCAT( col, "x" )' ]
     */
    public function set( array $set ) : string {
        $quoter = $this->quoter;
        $query = '';

        foreach( $set as $key => $value ) {
            if( is_array( $value ) ) {
                $query .= ', ' . $quoter->name( $key, false ) . ' = ' . $quoter->name( $value[ 0 ], false );
            } else {
                $query .= ', ' . $quoter->name( $key, false ) . ' = ' . $quoter->value( $value );
            }
        }
        return trim( $query, ' ,' );
    }
}
