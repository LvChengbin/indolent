<?php

namespace NextSeason\Model\SQL;

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
    public function in( array $in ) : array {

        $query = [];
        $params = [];

        foreach( $in as $value ) {
            /**
             * suportting nested IN syntax
             * eg. ( ( 1, 2 ), ( 3, 4 ) )
             */
            if( is_array( $value ) ) {
                $res = $this->in( $value );
                $query[] = $res[ 'query' ];
                $params = array_merge( $params, $res[ 'params' ] );
            } else if( $value instanceof Expression ) {
                $params = array_merge( $params, $value->params );
                $query[] = $value;
            } else {
                $query[] = '?';
                $params[] = $value;
            }
        }

        return [
            'query' => '( ' . implode( ', ', $query ) . ' )',
            'params' => $params
        ];
    }

    public function columns( array $columns = null ) : array {
        if( empty( $columns ) ) {
            return [
                'query' => '*',
                'params' => []
            ];
        }

        for( $i = 0, $l = count( $columns ); $i < $l; ++$i ) {
            $columns[ $i ] = $this->quoter->name( $columns[ i ], false );
        }

        return [
            'query' => implode( $columns, ', ' ),
            'params' => []
        ];
    }

    public function tables( array $tables ) : string {

        for( $i = 0, $l = count( $tables ); $i < $l; ++$i ) {
            /**
             * tbl_name -> `tbl_name`
             * tbl_name AS t1 -> `tbl_name` AS t1
             */
            $tables[ $i ] = $this->quoter->name( $tables[ $i ], false ); 
        }

        return implode( $tables, ', ' );
    }

    public function conditions( $conditions ) : array {
        $quoter = $this->quoter;

        $query = '';
        $params = [];

        foreach( $conditions as $value ) {
            if( $value instanceof Group ) {
                $res = $this->conditions( $value->conditions() );
                $query .= ' ( ' . $res[ 'query' ] . ' )';
                $params = array_merge( $params, $res[ 'params' ] );
            } else if( is_array( $value ) ) {
                if( count( $value ) === 2 ) {
                    if( is_null( $value[ 1 ] ) ) {
                        $value[ 2 ] = $value[ 1 ];
                        $value[ 1 ] = 'IS';
                    } else {
                        $value[ 2 ] = $value[ 1 ];
                        $value[ 1 ] = '=';
                    }
                }
                $query .= " {$quoter->name( $value[ 0 ], false )} {$value[ 1 ]} ";

                switch( strtolower( $value[ 1 ] ) ) {
                    case 'between' :
                    case 'not between' :
                        $params = array_merge( $params, $value[ 2 ] );
                        $query .= '? AND ? ';
                        break;
                    case 'exists' :
                    case 'not exists' :
                        break;
                    case 'in' :
                    case 'not in' :
                        $res = $this->in( $value[ 2 ] );
                        $query .= $res[ 'query' ];
                        $params = array_merge( $params, $res[ 'params' ] );
                        break;
                    default : 
                        if( $value[ 2 ] instanceof Expression ) {
                            $params = array_merge( $params, $value[ 2 ]->params );
                            $query .= $value[ 2 ];
                        } else {
                            $query .= '?';
                            $params[] = $value[ 2 ];
                        }
                        break;
                }
            } else {
                if( $value === Placeholder::AND ) {
                    $query .= ' AND';
                } else if( $value === Placeholder::OR ) {
                    $query .= ' OR';
                } else {
                    $query .= ' ' . $quoter->name( $value, false );
                }
            }
        }

        return [
            'query' => trim( $query ),
            'params' => $params
        ];
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
     * USE INDEX( index list... ) FOR GROUP BY
     * IGNORE INDEX FOR GROUP BY
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

    public function limit( array $limit ) : array {
        if( count( $limit ) === 1 ) {
            return [
                'query' => '?',
                'params' => $limit
            ];
        }

        return [
            'query' => '?, ?',
            'params' => $limit
        ];
    }

    /**
     * JOIN ( t1, t2, t3 )
     * JOIN t1 JOIN t2 JOIN t3
     * JOIN ( t1 JOIN t2 JOIN t3 )
     * JOIN tbl_name AS t1
     * FROM t1 JOIN t2 ON t1.id = t2.id
     *
     * [ 
     *      t1, 
     *      Placeholder::JOIN => [ 
     *          t2,
     *          Placeholder::JOIN => t3
     *      ], 
     *      ON => [],
     *      Placeholder::JOIN => t4
     *  ]
     */
    public function join( array $join ) : array {
        $quoter = $this->quoter;
        $query = '';
        $params = [];

        foreach( $join as $value ) {
            if( !is_array( $value ) ) {
                switch( (string)$value ) {
                    case Placeholder::ON :
                        $query .= ' ON';
                        break;
                    case Placeholder::USING :
                        $query .= ' USING';
                        break;
                    default :
                        $query .= ' ' . $quoter->name( $value, false );
                        break;
                }
            } else {

                switch( (string)$value[ 0 ] ) {
                    case Placeholder::JOIN :
                        $query .= ' JOIN';
                        break;
                    case Placeholder::CROSS_JOIN :
                        $query .= ' CROSS JOIN';
                        break;
                    case Placeholder::INNER_JOIN :
                        $query .= ' INNER JOIN';
                        break;
                    case Placeholder::OUTER_JOIN :
                        $query .= ' OUTER JOIN';
                        break;
                    case Placeholder::LEFT_JOIN :
                        $query .= ' LEFT JOIN';
                        break;
                    case Placeholder::RIGHT_JOIN :
                        $query .= ' RIGHT JOIN';
                        break;
                    case Placeholder::STRAIGHT_JOIN :
                        $query .= ' STRAIGHT JOIN';
                        break;
                    case Placeholder::ON :
                        $query .= ' ON';
                        break;
                    case Placeholder::USING :
                        $query .= ' USING';
                        break;
                } 

                if( $value[ 0 ] === Placeholder::ON ) {
                    $res = $this->conditions( $value[ 1 ] );
                    $query .= ' ' . $res[ 'query' ];
                    $params = array_merge( $params, $res[ 'params' ] );
                } else if( $value[ 0 ] === Placeholder::USING ) {
                    $query .= ' ( ';
                    if( is_array( $value[ 1 ] ) ) {
                        $query .= $quoter->name( implode( ', ', $value[ 1 ] ), false );
                    } else {
                        $query .= $quoter->name( $value[ 1 ], false );
                    }
                    $query .= ' ) ';
                } else {

                    if( is_array( $value[ 1 ] ) ) {
                        $res = $this->join( $value[ 1 ] );
                        $query .= ' ( ' . $res[ 'query' ] . ' )';
                        $params = array_merge( $params, $res[ 'params' ] );
                    } else {
                        $query .= ' ' . $quoter->name( $value[ 1 ], false );
                    }
                }
            }
        }

        return [
            'query' => trim( $query, ' ,' ),
            'params' => $params
        ];

    }

    /**
     * [ 'col' => 'value' ]
     * [ 'col1' => 'col2' ]
     * [ 'col' => 'col + 1' ]
     * [ 'col' => 'FUNC(col)' ]
     * [ 'col' => 'CONCAT( col, "x" )' ]
     */
    public function set( array $set ) : array {
        $quoter = $this->quoter;
        $query = '';
        $params = [];

        foreach( $set as $key => $value ) {
            $query .= ', ' . $quoter->name( $key, false ) . ' = ';
            if( $value instanceof Expression ) {
                $params = array_merge( $params, $value->params );
                $query .= $value;
            } else {
                $params[] = $value;
                $query .= '?';
            }

        }
        return [
            'query' => trim( $query, ' ,' ),
            'params' => $params
        ];
    }

    /**
     * to generate clause for index hint for where syntax
     *
     * USE INDEX ( index1, index2 )
     * IGNORE INDEX ( index1, index2 )
     * FORCE INDEX ( index1, index2 )
     *
     */
    public function index( array $indexes ) : string {
    }
}
