<?php
namespace MediaWiki\Extension\DataMaps\Data;

class CoordinateSystem extends DataModel {
    protected static string $publicName = 'CoordinateSystem';

    public const DEFAULT_TOP_LEFT = [ 0, 0 ];
    public const DEFAULT_BOTTOM_RIGHT = [ 100, 100 ];
    public const DEFAULT_SPACE = [ self::DEFAULT_TOP_LEFT, self::DEFAULT_BOTTOM_RIGHT ];
    public const DEFAULT_ORDER = 'yx';
    public const DEFAULT_ORIGIN = 'topLeft';
    public const ORDER_YX = 0;
    public const ORDER_XY = 1;
    public const ORIGIN_TOPLEFT = 1;
    public const ORIGIN_BOTTOMLEFT = 2;

    public static function normalisePoint( array $value, int $order ): array {
        if ( $order === self::ORDER_XY ) {
            $value = [ $value[1], $value[0] ];
        }
        return $value;
    }

    public static function normaliseBox( array $value, int $order ): array {
        if ( $order === self::ORDER_XY ) {
            $value = [ [ $value[0][1], $value[0][0] ], [ $value[1][1], $value[1][0] ] ];
        }
        return $value;
    }

    /**
     * @return bool
     */
    public function isLegacy(): bool {
        return false;
    }

    public function getOrigin(): int {
        switch ( $this->raw->origin ?? self::DEFAULT_ORIGIN ) {
            case 'topLeft':
                return self::ORIGIN_TOPLEFT;
            case 'bottomLeft':
                return self::ORIGIN_BOTTOMLEFT;
        }
    }

    public function getOrder(): int {
        $value = $this->raw->order ?? self::DEFAULT_ORDER;
        switch ( $value ) {
            case 'yx':
            case 'latlon':
                return self::ORDER_YX;
            case 'xy':
                return self::ORDER_XY;
        }
    }

    /**
     * @deprecated since v0.17.11, to be removed in v0.18.0, no replacement.
     * @return array
     */
    public function getTopLeft(): array {
        return $this->raw->topLeft ?? self::DEFAULT_TOP_LEFT;
    }

    /**
     * @deprecated since v0.17.11, to be removed in v0.18.0, no replacement.
     * @return array
     */
    public function getBottomRight(): array {
        return $this->raw->bottomRight ?? self::DEFAULT_BOTTOM_RIGHT;
    }

    /**
     * @deprecated since v0.17.11, to be removed in v0.18.0, no replacement.
     * @return array
     */
    public function getBox(): array {
        return [
            $this->getTopLeft(),
            $this->getBottomRight()
        ];
    }

    /**
     * @deprecated since v0.17.11, to be removed in v0.18.0, no replacement.
     * @return array
     */
    public function getNormalisedBox(): array {
        return self::normaliseBox( $this->getBox(), $this->getOrder() );
    }

    public function getRotation(): float {
        return deg2rad( $this->raw->rotation ?? 0 );
    }
}
