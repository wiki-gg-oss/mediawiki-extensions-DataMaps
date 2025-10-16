<?php
namespace MediaWiki\Extension\DataMaps\LegacyCompat\Data;

use stdClass;

/**
 * @internal
 */
class DataModelMixinTransformer {
    public static function mergeTwoObjects( stdClass $target, stdClass $overlay, $allowObjectArrayMerge = true ): stdClass {
        foreach ( get_object_vars( $overlay ) as $name => $value ) {
            // Do not copy the "is mix-in?" metadata field
            if ( $name === '$fragment' ) {
                continue;
            }

            if ( !isset( $target->$name ) ) {
                // Missing, copy
                $target->$name = $value;
            } else {
                // Merge
                $target->$name = self::mergeUnknown( $target->$name, $value, $allowObjectArrayMerge && $name != 'markers' );
            }
        }
        return $target;
    }

    public static function mergeUnknown( $target, $overlay, $allowObjectArrayMerge = true ) {
        // Check both fields have the same type
        if ( $target !== null && $overlay !== null
            && ( ( is_array( $target ) && is_array( $overlay ) ) || ( $target instanceof stdClass
                && $overlay instanceof stdClass ) ) ) {
            // Proceed to merge
            if ( is_array( $overlay ) ) {
                return self::mergeTwoObjectArrays( $target, $overlay, $allowObjectArrayMerge );
            } else {
                return self::mergeTwoObjects( $target, $overlay, $allowObjectArrayMerge );
            }
        }

        // Override
        return $overlay;
    }

    public static function mergeTwoObjectArrays( array $target, array $overlay, bool $allowObjectMerge = true ) {
        $lenT = count( $target );
        foreach ( $overlay as $key => $value ) {
            if ( is_string( $key ) ) {
                // Key-value pair
                if ( !array_key_exists( $key, $target ) ) {
                    // Missing, copy
                    $target[$key] = $value;
                } else {
                    // Merge
                    $target[$key] = self::mergeUnknown( $target[$key], $value, $allowObjectMerge );
                }
            } elseif ( $allowObjectMerge ) {
                // Indexed
                if ( $key >= $lenT ) {
                    // Index out of target bounds, copy
                    $target[$key] = $value;
                } elseif ( $allowObjectMerge ) {
                    // Merge
                    $target[$key] = self::mergeUnknown( $target[$key], $value, $allowObjectMerge );
                }
            } else {
                // Object merge disallowed, append
                $target[] = $value;
            }
        }
        return $target;
    }
}
