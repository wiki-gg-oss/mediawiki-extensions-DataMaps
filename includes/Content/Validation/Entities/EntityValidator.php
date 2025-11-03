<?php
namespace MediaWiki\Extension\DataMaps\Content\Validation\Entities;

use MediaWiki\Extension\DataMaps\Content\MapContentVersion;
use MediaWiki\Extension\DataMaps\Content\Validation\Trace;
use MediaWiki\Status\Status;
use stdClass;

abstract class EntityValidator {
    public const NULLABLE = 'nullable';
    public const CHECK_CLASS = 'checker';
    public const ITEM_SPEC = 'itemSpec';

    public function __construct(
        protected readonly Trace $trace,
        protected readonly MapContentVersion $contentVersion,
        protected readonly Status $status
    ) { }

    abstract public function validateObject( stdClass $data ): bool;

    protected function hasAnyProperty( stdClass $data, array $props ): bool {
        foreach ( $props as $prop ) {
            if ( isset( $data->{$prop} ) ) {
                return true;
            }
        }
        return false;
    }

    protected function expectProperties( stdClass $data, array $props ): array {
        $presentProps = [];
        $validatorCache = [];

        foreach ( $props as $prop => $spec ) {
            if ( $this->expectValue( $prop, $data->{$prop} ?? null, $spec ) ) {
                $presentProps[] = $prop;
            }
        }

        return $presentProps;
    }

    private function expectValue( string|int $name, $value, array $spec ): bool {
        // Check nullability
        if ( $value === null ) {
            if ( !( $spec[EntityValidator::NULLABLE] ?? false ) ) {
                $this->status->fatal( 'navigator-validate-unexpected-null', $this->trace->toString( $name ) );
            }
            return false;
        }

        // Check direct type
        if ( !$spec[0]( $value ) ) {
            $this->status->fatal( 'navigator-validate-unexpected-type', $this->trace->toString( $name ) );
            return false;
        }

        // Invoke the root checker
        if ( array_key_exists( EntityValidator::CHECK_CLASS, $spec ) ) {
            $validatorClass = $spec[EntityValidator::CHECK_CLASS];
            $validatorCache[$validatorClass] ??= new $validatorClass( $this->trace, $this->contentVersion,
                $this->status );
            $this->trace->push( $name );
            $isGood = $validatorCache[$validatorClass]->validateObject( $value );
            $this->trace->back();

            if ( !$isGood ) {
                return false;
            }
        }

        // Check the item type
        if ( array_key_exists( EntityValidator::ITEM_SPEC, $spec ) ) {
            $itemSpec = $spec[EntityValidator::ITEM_SPEC];
            $this->trace->push( $name );
            $isGood = true;
            foreach ( $value as $key => $item ) {
                $isGood &= $this->expectValue( $key, $item, $itemSpec );
            }
            $this->trace->back();

            if ( !$isGood ) {
                return false;
            }
        }

        // TODO: do more granular checks + descend

        return true;
    }
}
