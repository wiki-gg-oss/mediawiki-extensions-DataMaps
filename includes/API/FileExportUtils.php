<?php

namespace MediaWiki\Extension\DataMaps\Api;

use Exception;
use File;
use RepoGroup;

class FileExportUtils {
    public function __construct(
        private readonly RepoGroup $repoGroup
    ) { }

    public function findFile( string $fileName ): ?File {
        $retval = $this->repoGroup->findFile( $fileName );
        if ( $retval === false ) {
            $retval = null;
        }
        return $retval;
    }

    public function getDimensionsVec( ?File $fileObj, string|array $dimensHint ): array {
        if ( is_array( $dimensHint ) ) {
            return $dimensHint;
        }

        switch ( $dimensHint ) {
            case 'same-as-file':
                if ( $fileObj === null ) {
                    // TODO: handle missing files
                }
                return [ $fileObj->getWidth(), $fileObj->getHeight() ];

            default:
                throw new Exception( 'Invalid dimensions hint' );
        }
    }

    public function getFullResImageUrl( ?File $fileObj ): string {
        if ( $fileObj === null ) {
            // TODO: handle missing files
        }

        return $fileObj->getUrl();
    }
}
