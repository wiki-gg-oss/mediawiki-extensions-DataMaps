<?php
namespace MediaWiki\Extension\DataMaps\LegacyCompat\Data;

class CoordinateSystemLegacy extends CoordinateSystem {
    protected static string $publicName = 'CoordinateSystemLegacy';

    /**
     * Undocumented function
     *
     * @return bool
     */
    public function isLegacy(): bool {
        return true;
    }

    public function getOrigin(): int {
        $tl = $this->getTopLeft();
        $br = $this->getBottomRight();
        return ( $tl[0] < $br[0] && $tl[1] < $br[1] ) ? self::ORIGIN_TOPLEFT : self::ORIGIN_BOTTOMLEFT;
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

    public function getTopLeft(): array {
        return $this->raw->topLeft ?? self::DEFAULT_TOP_LEFT;
    }

    public function getBottomRight(): array {
        return $this->raw->bottomRight ?? self::DEFAULT_BOTTOM_RIGHT;
    }

    public function getRotation(): float {
        return deg2rad( $this->raw->rotation ?? 0 );
    }
}
