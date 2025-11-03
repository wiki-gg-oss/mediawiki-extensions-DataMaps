<?php
namespace MediaWiki\Extension\DataMaps\Content\Validation;

use MediaWiki\Extension\DataMaps\Content\MapContent;
use MediaWiki\Extension\DataMaps\Content\MapContentVersion;
use MediaWiki\Extension\DataMaps\Content\Validation\Entities\InteractiveMapValidator;
use MediaWiki\Status\Status;
use stdClass;
use ValueError;

class MapContentValidator implements IContentValidator {
    /**
     * @inheritDoc
     */
    public function validateContentObject( MapContent $content ): Status {
        $retval = new Status();
        $this->validateContentObjectInternal( $content, $retval );
        return $retval;
    }

    private function validateContentObjectInternal( MapContent $content, Status $status ): void {
        // First check if the data is a valid JSON
        $dataStatus = $content->getData();
        if ( !$dataStatus->isGood() ) {
            $status->fatal( 'datamap-error-validate-invalid-json' );
            return;
        }

        $data = $dataStatus->getValue();

        $versionValue = $data->version ?? null;
        if ( $versionValue === null || !is_int( $versionValue ) ) {
            $status->fatal( 'navigator-validate-bad-version' );
            return;
        }

        try {
            $contentVersion = MapContentVersion::from( $versionValue );
        } catch ( ValueError ) {
            $status->fatal( 'navigator-validate-bad-version' );
            return;
        }

        $trace = new Trace();
        $trace->push( '' );
        ( new InteractiveMapValidator( $trace, $contentVersion, $status ) )
            ->validateObject( $data );
    }
}
