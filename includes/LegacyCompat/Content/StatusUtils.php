<?php
namespace MediaWiki\Extension\DataMaps\LegacyCompat\Content;

use MediaWiki\Language\RawMessage;

final class StatusUtils {
    public static function formatArray( array $data, string $wrapperTag = 'code' ): RawMessage {
        $formatted = implode(
            ', ',
            array_map(
                fn ( $el ) => "<$wrapperTag>" . wfEscapeWikiText( $el ) . "</$wrapperTag>",
                $data
            )
        );
        return new RawMessage( $formatted );
    }

    public static function formatArrayUnescaped( array $data, string $wrapperTag = 'code' ): RawMessage {
        $formatted = implode(
            ', ',
            array_map(
                fn ( $el ) => "<$wrapperTag>" . $el . "</$wrapperTag>",
                $data
            )
        );
        return new RawMessage( $formatted );
    }
}
