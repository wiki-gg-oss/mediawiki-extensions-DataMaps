<?php

namespace MediaWiki\Extension\DataMaps\Output;

use MediaWiki\Page\PageIdentity;
use MediaWiki\Parser\Parser;

final class MapOutputFactory {
    public const SERVICE_NAME = 'DataMaps.OutputFactory';

    public function createMapMetadataEmitter( Parser $parser, PageIdentity $page ): MapMetadataEmitter {
        return new MapMetadataEmitter( $parser, $page );
    }

    public function createMapRenderer( Parser $parser, PageIdentity $page ): MapRenderer {
        return new MapRenderer( $parser, $page );
    }
}
