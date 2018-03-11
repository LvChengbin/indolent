<?php

namespace NextSeason\Model\SQL\Common;

class Quoter {

    protected $prefix = '"';

    protected $suffix = '"';

    protected $placeholder_prefix = 'NEXTSEASON_MODEL_SQL_QUOTER_PLACEHOLDER_';

    protected $reserved = [ 'AS', 'LIKE' ];

    public function quoted( string $str ) : bool {
        $reg = "#{$this->prefix}[^{$this->suffix}]*{$this->suffix}#";
        return strlen( trim( preg_replace( $reg, '', $str ), ' ,' ) ) ? false : true;
    }

    /**
     * quote strings
     */
    public function name( string $str, bool $mode = null ) : string {
        if( $this->quoted( $str ) || $str === '*' ) {
            return $str;
        }

        if( is_null( $mode ) || preg_match( '#^[a-zA-Z\d\-\_]+$#', $str ) ) {
            return $this->prefix . $str . $this->suffix;
        }

        if( $mode === 1 ) {
            if( !preg_match( '#^[a-zA-Z\_]$#', $str ) ) {
                return $str;
            }
            return $this->name( $str );
        }

        $i = 0;
        $placeholders = [];
        $regex = "#(?:{$this->prefix}[^{$this->suffix}]*{$this->suffix})|(?:('+|\"+|\\'+|\\\"+).*?\\1)#";
        $temp = [];

        /**
         * to collect all the sub strings in the string that hava already been wrapped by QUOTES;
         */
        $str = preg_replace_callback( $regex, function( $matches ) use ( &$i, &$placeholders, &$temp ) {
            $placeholder = $this->placeholder_prefix . $i++;
            array_push( $placeholders, $placeholder );
            array_push( $temp, $matches[ 0 ] );
            return $placeholder;
        }, $str );

        $str = preg_replace_callback( '#\\b([a-zA-Z_][a-zA-Z\d-_]*)\\b(?!\s*\()#', function( $matches ) {
            $item = $matches[ 1 ];
            if( in_array( strtoupper( $item ), $this->reserved ) || $this->isPlaceholder( $item ) ) {
                return $item;
            }
            return $this->prefix . $item . $this->suffix;
        }, $str );

        $regex = '#\\b(' . implode( '|', $this->reserved ) . ')\s*=#i';

        $str = preg_replace( $regex, $this->prefix . '\\1' . $this->suffix . ' =', $str );

        return str_replace( $placeholders, $temp, $str );
    }

    public function value( $str ) : string {
        if( is_string( $str ) ) {
            return '\'' .addslashes( $str ) . '\'';
        }
        return $str;
    }

    private function isPlaceholder( string $str ) : bool {
        return strpos( $str, $this->placeholder_prefix ) === 0;
    }
}
