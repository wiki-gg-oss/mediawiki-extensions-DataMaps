<?php
namespace MediaWiki\Extension\DataMaps\Data;

use MediaWiki\Extension\DataMaps\Content\DataMapContent;
use MediaWiki\Extension\DataMaps\ExtensionConfig;
use MediaWiki\MediaWikiServices;
use Status;
use stdClass;
use Title;

class DataMapSpec extends DataModel {
    protected static string $publicName = 'DataMapSpec';

    private ?array $cachedMarkerGroups = null;
    private ?array $cachedMarkerLayers = null;
    private ?array $cachedBackgrounds = null;
    private ?CoordinateSystem $coordinateSystem = null;
    private ?MapSettingsSpec $cachedSettings = null;

    public const MARKER_ERROR_LIMIT = 30;

    public static function staticIsFragment( \stdclass $raw ): bool {
        return $raw->{'$fragment'} ?? false;
    }

    public function isFragment(): bool {
        return self::staticIsFragment( $this->raw );
    }

    public function getRequiredFragments(): ?array {
        $config = MediaWikiServices::getInstance()->get( ExtensionConfig::SERVICE_NAME );

        $list = $this->raw->include ?? null;
        if ( $list === null ) {
            return null;
        }

        return array_map( fn ( $el ) => Title::newFromText( $el, $config->getNamespaceId() ), $list );
    }

    /**
     * Retrieves the coordinate system setup.
     *
     * @since 0.16.11
     * @return CoordinateSystem
     */
    public function getCoordinateSystem(): CoordinateSystem {
        if ( $this->coordinateSystem === null ) {
            if ( is_object( $this->raw->coordinates ?? null ) ) {
                $this->coordinateSystem = new CoordinateSystem( $this->raw->coordinates );
                return $this->coordinateSystem;
            }

            if ( is_object( $this->raw->crs ?? null ) ) {
                $this->coordinateSystem = new CoordinateSystemLegacy( $this->raw->crs );
            } else {
                $options = [];
                if ( isset( $this->raw->crs ) ) {
                    $options['topLeft'] = $this->raw->crs[0];
                    $options['bottomRight'] = $this->raw->crs[1];
                }
                $this->coordinateSystem = new CoordinateSystemLegacy( (object)$options );
            }
        }
        return $this->coordinateSystem;
    }

    public function getBackgrounds(): array {
        if ( $this->cachedBackgrounds == null ) {
            if ( is_string( $this->raw->background ?? null ) ) {
                $this->cachedBackgrounds = [
                    MapBackgroundSpec::fromImageName( $this->raw->background )
                ];
            } elseif ( isset( $this->raw->background ) ) {
                $this->cachedBackgrounds = [
                    new MapBackgroundSpec( $this->raw->background )
                ];
            } else {
                $this->cachedBackgrounds = array_map( fn ( $raw ) => new MapBackgroundSpec( $raw ), $this->raw->backgrounds );
            }
        }
        return $this->cachedBackgrounds;
    }

    public function getSettings(): MapSettingsSpec {
        if ( $this->cachedSettings === null ) {
            $this->cachedSettings = new MapSettingsSpec( $this->raw->settings ?? new stdClass() );
        }
        return $this->cachedSettings;
    }

    public function getCustomData(): ?object {
        return isset( $this->raw->custom ) ? $this->raw->custom : null;
    }

    public function getRawMarkerMap(): object {
        return isset( $this->raw->markers ) ? $this->raw->markers : new stdClass();
    }

    public function getRawMarkerGroupMap(): object {
        return $this->raw->groups;
    }

    public function getRawMarkerLayerMap(): object {
        return $this->raw->categories;
    }

    private function warmUpUsedMarkerTypes() {
        $groups = [];
        $specifiers = [];
        foreach ( array_keys( get_object_vars( $this->getRawMarkerMap() ) ) as &$name ) {
            $parts = explode( ' ', $name );
            $groups[] = array_shift( $parts );
            $specifiers = array_merge( $parts, $specifiers );
        }
        $this->cachedMarkerGroups = array_unique( $groups );
        $this->cachedMarkerLayers = array_values( array_unique( $specifiers ) );
    }

    public function getGroupNames(): array {
        if ( $this->cachedMarkerGroups == null ) {
            $this->warmUpUsedMarkerTypes();
        }
        return $this->cachedMarkerGroups;
    }

    public function getLayerNames(): array {
        if ( $this->cachedMarkerLayers == null ) {
            $this->warmUpUsedMarkerTypes();
        }
        return $this->cachedMarkerLayers;
    }

    public function getGroup( string $name ): MarkerGroupSpec {
        return new MarkerGroupSpec( $name, $this->raw->groups->$name );
    }

    public function hasLayer( string $name ): bool {
        return isset( $this->raw->categories->$name );
    }

    public function getLayer( string $name ): ?MarkerLayerSpec {
        return isset( $this->raw->categories ) ? (
            isset( $this->raw->categories->$name ) ? new MarkerLayerSpec( $name, $this->raw->categories->$name ) : null
        ) : null;
    }

    public function getDisclaimerText(): ?string {
        return $this->raw->disclaimer ?? null;
    }

    public function iterateGroups( callable $callback ) {
        foreach ( $this->getGroupNames() as &$name ) {
            $data = $this->getGroup( $name );
            if ( $callback( $data ) === false ) {
                break;
            }
        }
    }

    public function iterateDefinedLayers( callable $callback ) {
        foreach ( $this->getLayerNames() as &$name ) {
            $data = $this->getLayer( $name );
            if ( $data !== null ) {
                if ( $callback( $data ) === false ) {
                    break;
                }
            }
        }
    }

    public function iterateRawMarkerMap( callable $callback ) {
        foreach ( get_object_vars( $this->getRawMarkerMap() ) as $id => $data ) {
            if ( $callback( $id, $data ) === false ) {
                break;
            }
        }
    }

    public function iterateRawLayerMap( callable $callback ) {
        foreach ( get_object_vars( $this->getRawMarkerLayerMap() ) as $id => $data ) {
            if ( $callback( $id, $data ) === false ) {
                break;
            }
        }
    }
}
