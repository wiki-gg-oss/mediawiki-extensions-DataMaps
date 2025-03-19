<?php
namespace MediaWiki\Extension\DataMaps\Rendering\Utils;

use File;
use InvalidArgumentException;
use MediaTransformOutput;
use MediaWiki\MediaWikiServices;
use MediaWiki\Parser\ParserOutput;
use ThumbnailImage;

class DataMapFileUtils {
    public const SCALING_THRESHOLD = 64;

    protected static function cleanName( string $title ): string {
        if ( str_starts_with( strtolower( $title ), 'file:' ) ) {
            $title = substr( $title, 5 );
        }
        return trim( $title );
    }

    public static function getFile( string $title ) {
        return MediaWikiServices::getInstance()->getRepoGroup()->findFile( self::cleanName( $title ) );
    }

    public static function getRequiredFile( string $title, int $width = -1, bool $allowUpsizing = false ) {
        $title = self::cleanName( $title );
        $file = self::getFile( $title );
        if ( !$file || !$file->exists() ) {
            throw new InvalidArgumentException( "[[File:$title]] does not exist." );
        }

        if ( $width <= 0 || str_ends_with( strtolower( $title ), '.svg' ) ) {
            return $file;
        }

        $fileWidth = $file->getWidth();
        if ( ( $allowUpsizing || $width < $fileWidth ) && $fileWidth > self::SCALING_THRESHOLD ) {
            $file = $file->transform( [
                'width' => $width
            ] );
        }

        return $file;
    }

    /**
     * @internal unstable
     * @param string $title
     * @param array $options
     *   Scale parameters - may be ignored if it results in degradation:
     *   - width (int)
     *   - height (int)
     *   Behaviour parameters:
     *   - allowUpsizing (bool)
     * @return File|ThumbnailImage|MediaTransformOutput|bool False on failure
     */
    public static function transformScaledImage( string $title, array $options ) {
        $title = self::cleanName( $title );
        $file = self::getFile( $title );
        if ( !$file || !$file->exists() ) {
            return false;
        }

        // Skip any transformations on SVG files
        // TODO: this means we return source dimensions for SVGs, probably not the intended behaviour
        if ( str_ends_with( strtolower( $title ), '.svg' ) ) {
            return $file;
        }

        // Construct transform parameters and perform range checks
        $transformParams = [];
        if ( ( $options['width'] ?? 0 ) > self::SCALING_THRESHOLD ) {
            $srcWidth = $options['width'];
            $transformParams['width'] = min( $srcWidth, $options['width'] );
        }

        if ( ( $options['height'] ?? 0 ) > self::SCALING_THRESHOLD ) {
            $srcHeight = $file->getHeight();
            $transformParams['height'] = min( $srcHeight, $options['height'] );
        }

        if ( empty( $transformParams ) ) {
            return $file;
        }

        return $file->transform( $transformParams );
    }

    public static function getFileUrl( string $title, int $width = -1 ): string {
        return self::getRequiredFile( $title, $width )->getURL();
    }

    public static function getMarkerIconUrl( string $title, int $width ): string {
        // Upsize by 50% to mitigate quality loss at max zoom
        $size = floor( $width * 1.5 );
        // Ensure it's a multiple of 2
        if ( $size % 2 !== 0 ) {
            $size++;
        }
        return self::getFileUrl( $title, $size );
    }

    public static function registerImageDependency( ParserOutput $parserOutput, string $name ): void {
        $file = self::getRequiredFile( $name );
        $parserOutput->addImage( $file->getTitle()->getDBkey(), $file->getTimestamp(), $file->getSha1() );
    }
}
