<?php
namespace MediaWiki\Extension\DataMaps\LegacyCompat\Content\JsonSchemaEx;

use JsonSchema\Constraints\ObjectConstraint;
use JsonSchema\Constraints\UndefinedConstraint;
use JsonSchema\Entity\JsonPointer;

class ObjectConstraintEx extends ObjectConstraint {
    /**
     * @inheritDoc
     */
    public function validateElement(
        $element,
        $matches,
        $schema = null,
        JsonPointer $path = null,
        $properties = null,
        $additionalProp = null
    ) {
        $this->validateMinMaxConstraint( $element, $schema, $path );

        foreach ( $element as $i => $value ) {
            $definition = $this->getProperty( $properties, $i );

            // no additional properties allowed
            if ( !in_array( $i, $matches ) && $additionalProp === false && $this->inlineSchemaProperty !== $i && !$definition ) {
                // PATCH: Added `property` field to the error
                $this->addError(
                    $path,
                    "The property $i is not defined and the definition does not allow additional properties",
                    'additionalProp',
                    [
                        'apProperty' => $i,
                    ]
                );
            }

            // additional properties defined
            if ( !in_array( $i, $matches ) && $additionalProp && !$definition ) {
                if ( $additionalProp === true ) {
                    $this->checkUndefined( $value, null, $path, $i, in_array( $i, $this->appliedDefaults ) );
                } else {
                    $this->checkUndefined( $value, $additionalProp, $path, $i, in_array( $i, $this->appliedDefaults ) );
                }
            }

            // property requires presence of another
            $require = $this->getProperty( $definition, 'requires' );
            if ( $require && !$this->getProperty( $element, $require ) ) {
                $this->addError( $path, 'The presence of the property ' . $i . ' requires that ' . $require . ' also be present',
                    'requires' );
            }

            $property = $this->getProperty( $element, $i, $this->factory->createInstanceFor( 'undefined' ) );
            if ( is_object( $property ) ) {
                $this->validateMinMaxConstraint( !( $property instanceof UndefinedConstraint ) ? $property : $element,
                    $definition, $path );
            }
        }
    }
}
