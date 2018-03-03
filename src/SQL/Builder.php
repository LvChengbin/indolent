<?php

namespace NextSeason\Model\SQL;

/**
 * @method __construct( string $action )
 * @method self select( 
 */

class Builder {
    /**
     * to generate an unique id for current builder instance for generating the placeholder for prepare syntax.
     */
    private $id = null;

    private $quoter = null;

    private $assembler = null;

    /**
     * the action name, supporting insert, replace, delete, update and select.
     */
    protected $action = null;

    /**
     * for storing the modifiers, such as LOW_PRIORITY, IGNORE, etc.
     */
    protected $modifiers = [];

    /**
     * for storing tables
     */
    protected $tables = [];

    /**
     * for storing partitions
     */
    protected $partitions = [];

    /**
     * property for storing the setting data for INSERTION and UPDATE SQL.
     */
    protected $set = [];

    /**
     * structures of tables.
     */
    protected $structures = [];

    /**
     * variable for storing the columns which are going to be select from database.
     */
    protected $select = [];

    /**
     * storing GROUP BY rules.
     */
    protected $groupby = [];

    /**
     * for storing the current process 
     */
    protected $process = null;

    protected $where = [];

    protected $having = [];

    protected $limit = null;

    protected $orderby = [];


    public function __construct( array $options = null ) {

        $this->quoter = $options[ 'quoter' ] ?? new Quoter();

        $this->assembler = new Assembler( $this->quoter );

        $this->id = uniqid();
        if( !is_null( $options ) ) {
            $this->prepare = $options[ 'prepare' ] ?? false;
            $this->tables = $options[ 'table' ] ?? null;
            $this->structures = $options[ 'structures' ] ?? [];
        }
    }

    public function query() {

        switch( $this->action ) {
            case 'SELECT' :
                $query = sprintf( 
                    "SELECT %s FROM %s",
                    Assembler::columns( $this->select ),
                    Assembler::tables( $this->tables )
                );
                break;
        }

        return $query;
    }

    public function params() {
    }

    /**
     * To create a SELECT SQL
     *
     * @param string|array $columns The columns that will be selected from database.
     */
    public function select( $columns = '*' ) : self {
        $this->action = 'SELECT';
        $this->select = $columns;
        return $this;
    }

    /**
     * To create a REPLACE SQL
     *
     * @param string|array The data that will be set, it could be a query string or an array and also can be set with the self::set method later.
     */
    public function replace( $data = null ) : self {
        $this->action = 'REPLACE';

        if( !is_null( $data ) ) {
            return $this->set( $data );
        }
        return $this;
    }

    /**
     * To create an INSERT SQL.
     *
     * @param string|array The data for being inserted, it chould be a query string or an array, and also can be set with the self::set method later.
     */
    public function insert( $data = null ) : self {
        $this->action = 'INSERT';
        if( !is_null( $data ) ) {
            return $this->set( $data );
        }
        return $this;
    }

    /**
     * To create a DELETE SQL
     *
     * @param string|array $tables The table name (or list of multiple table names), same as using self::table method
     */
    public function delete( $tables = null ) : self {
        $this->action = 'DELETE';
        if( !is_null( $tables ) ) {
            return $this->table( $tables );
        }
        return $this;
    }

    /**
     * To create an UPDATE SQL
     *
     * @param string|array $tables The tables which will be updated.
     */
    public function update( $tables = null ) : self {
        if( !is_null( $table ) ) {
            $this->table( $table );
        }
        return $this;
    }

    /**
     * To set the modifiers, the legality of the specified modifier will not be checked..
     *
     * @param string $modifier The name of modifier, such as LOW_PRIORITY, IGNORE, ect.
     */
    public function modifier( $modifiers ) : self {
        $this->modifiers = $modifiers;
        return $this;
    }

    /**
     * To set the table name, can be used for all types of SQL, even though different alias for different type of SQL are supplied, such as $this->into( $table ) and $this->from( $table )
     *
     * @param string|array $table Table name.
     * @param boolean $overwrite Denoting if to overwrite the tables have been set or append to the existing table list.
     */
    public function table( $table, boolean $overwrite = null ) : self {
        $exists = $overwrite ? [] : $this->tables;

        if( is_array( $table ) ) {
            $table = array_merge( $exists, $table );
        } else {
            array_push( $exists, $table );
        }

        $this->tables = array_unique( $exists );

        return $this;
    }

    /**
     * To set the table name, an alias for $this->table method for INSERTION SQL.
     *
     * @see self:table
     */
    public function into( $table, $overwrite = null ) : self {
        return $this->table( $table, $overwrite = null );
    }

    /**
     * To set the table name, an alias for $this->table method for SELECTION SQL.
     *
     * @see self:table
     */
    public function from( $table, $overwrite = null ) : self {
        return $this->table( $table, $overwrite );
    }


    /**
     * To set the partitions for the SQL.
     *
     * @param string|array $partitions A partition name or a list of partition names.
     */
    public function partition( $partitions, $overwrite = null ) : self {
        $exists = $overwrite ? [] : $this->partitions;

        if( is_array( $partitions ) ) {
            $exists = array_merge( $exists, $partitions );
        } else {
            array_push( $exists, $partitions );
        }
        $this->partitions = $exists;
        return $this;
    }

    /**
     * To set values for UPDATE SQL
     *
     * @param string|array $data A query string or an array of data for setting.
     */
    public function set( $data, $overwrite = null ) : self {
        $exists = $overwrite ? [] : $this->set;

        if( is_array( $data ) ) {
            $exists = array_merge( $exists, $data );
        } else {
            array_push( $exists, $data );
        }

        $this->set = $exists;
        return $this;
    }

    public function prepare( boolean $prepare = null ) : self {
        $this->prepare = $prepare ?? true;
    }

    /**
     * id = 1234
     * t1.id = 4
     */
    public function condition(   ) {
    }

    /**
     * self::where( 'id', 1 );
     * self::where( 'id=1' );
     * self::where( [ 'id=1', [ 'name' => 'x' ] ] );
     */
    public function where( $condition  ) : self {
        $this->process = 'where';
        return $this;
    }

    public function order() : self {
        return $this;
    }

    public function orWhere( $condition ) : self {
        return $this;
    }

    /**
     * To set the columns or SQL string for GROUP BY syntax.
     * GROUP BY `id` DESC, `name` ASC WITH ROLLUP
     *
     * @param string|array $columns
     */
    public function groupBy( $columns, $overwrite ) : self {
        $exists = $overwrite ? [] : $this->groupby;

        if( is_array( $columns ) ) {
            $exists = array_merge( $exists, $columns );
        } else {
            array_push( $exists, $columns );
        }

        $this->groupby = $exists;
        return $this;
    }

    public function having() : self {
        $this->process = 'having';
        return $this;
    }

    public function orHaving() : self {
        return $this;
    }

    public function orderBy() : self {
        return $this;
    }

    public function limit() : self {
        return $this;
    }

    public function group() : self {
        return $this;
    }

    public function end() : self {
        return $this;
    }

    public function eq() : self {
        return $this;
    }

    public function gt() : self {
        return $this;
    }

    public function gte() : self {
        return $this;
    }

    public function lt() : self {
        return $this;
    }

    public function let() : self {
        return $this;
    }

    public function ne() : self {
        return $this;
    }

    public function in() : self {
        return $this;
    }

    public function any() : self {
        return $this;
    }

    public function some() : self {
        return $this;
    }
}
