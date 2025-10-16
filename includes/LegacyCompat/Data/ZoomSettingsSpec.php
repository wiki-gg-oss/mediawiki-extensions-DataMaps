<?php
namespace MediaWiki\Extension\DataMaps\LegacyCompat\Data;

class ZoomSettingsSpec extends DataModel {
    protected static string $publicName = 'ZoomSettingsSpec';

    /**
     * @var bool
     */
    public const DEFAULT_AUTO = true;

    /**
     * @var float
     */
    public const DEFAULT_MINIMUM = -16;

    /**
     * @var float
     */
    public const DEFAULT_MAXIMUM = 6;

    public function isLocked(): bool {
        return $this->raw->lock ?? false;
    }

    public function isMinimumAutomatic(): bool {
        return $this->raw->tryFitEverything ?? self::DEFAULT_AUTO;
    }

    public function getMinimum(): float {
        return $this->raw->min ?? self::DEFAULT_MINIMUM;
    }

    public function getMaximum(): float {
        return $this->raw->max ?? self::DEFAULT_MAXIMUM;
    }

    public function getScrollSpeed(): float {
        return $this->raw->scrollSpeed ?? 1.0;
    }
}
