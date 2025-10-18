<?php
namespace MediaWiki\Extension\DataMaps\Output;

/**
 * Container holding settings for a map embedded in an article.
 */
class MapRenderOptions {
    private bool $allowLazyLoading = true;

    public function toInitFlags(): int {
        $retval = MapInitFlag::None->value
            | ( $this->isLazyLoadingAllowed() ? MapInitFlag::Lazy->value : 0 );
        return $retval;
    }

    public function setLazyLoadingAllowed( bool $value ): MapRenderOptions {
        $this->allowLazyLoading = $value;
        return $this;
    }

    public function isLazyLoadingAllowed(): bool {
        return $this->allowLazyLoading;
    }
}
