<?php
namespace MediaWiki\Extension\DataMaps\Content\Validation;

class Trace {
    private array $stack = [];
    private int $len = 0;

    public function push( string|int $part ): void {
        if ( is_int( $part ) ) {
            $part = (string)$part;
        }
        $this->stack[] = $part;
        $this->len++;
    }

    public function back(): void {
        array_pop( $this->stack );
        $this->len--;
    }

    public function getLeaf(): string|int {
        return $this->stack[$this->len - 1];
    }

    public function toString( ?string $leaf = null ): string {
        if ( $leaf === null ) {
            return implode( '/', $this->stack );
        }
        return implode( '/', $this->stack ) . "/$leaf";
    }
}
