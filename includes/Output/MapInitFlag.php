<?php
namespace MediaWiki\Extension\DataMaps\Output;

enum MapInitFlag: int {
    case None = 0;
    case Lazy = ( 1 << 0 );
}
