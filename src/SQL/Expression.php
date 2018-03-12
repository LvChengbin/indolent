<?php

namespace NextSeason\Model\SQL;

class Expression {
    protected $quoter = null;
    protected $expression = '';

    public $query;
    public $params = [];

    public function __construct( string $expression, Quoter $quoter ) {
        $this->quoter = $quoter;
        $this->expression = $expression;
        $query = $this->quoter->name( $expression, false );

        $regex = '#(?:(?<!\\\)(\'+|"+)(.*?)(?<!\\\)\\1)|(?:\b(\d+)\b)#';

        $this->query = preg_replace_callback( $regex, function( $matches ) use ( $query ) {

            if( count( $matches ) > 3 ) {
                $this->params[] = $matches[ 3 ];
            } else {
                $this->params[] = $matches[ 2 ];
            }
            return '?';

        }, $query );
    }

    public function __toString() {
        return $this->query;
    }
}
