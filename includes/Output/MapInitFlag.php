<?php
namespace MediaWiki\Extension\DataMaps\Output;

/**
 * Initialisation flags for maps embedded in articles.
 */
enum MapInitFlag: int {
    case None = 0;
    case Lazy = ( 1 << 0 );
}
