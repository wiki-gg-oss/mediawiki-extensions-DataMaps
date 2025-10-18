<?php

namespace MediaWiki\Extension\DataMaps\Output;

use MediaWiki\Page\PageReference;

final class MapOutputFactory {
    public const SERVICE_NAME = 'DataMaps.OutputFactory';

    public function createMapMetadataEmitter( PageReference $page ): MapMetadataEmitter {
        return new MapMetadataEmitter( $page );
    }

    public function createMapRenderer( PageReference $page ): MapRenderer {
        return new MapRenderer( $page );
    }
}
