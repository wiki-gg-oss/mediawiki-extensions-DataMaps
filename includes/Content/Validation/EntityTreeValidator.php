<?php
namespace MediaWiki\Extension\DataMaps\Content\Validation;

use MediaWiki\Extension\DataMaps\Content\MapContent;
use MediaWiki\Extension\DataMaps\Content\MapContentVersion;
use MediaWiki\Status\Status;
use stdClass;

class EntityTreeValidator {
    public function __construct(
        private readonly MapContentVersion $contentVersion
    ) { }

    /**
     * @inheritDoc
     */
    public function validateContentObject( MapContent $content ): Status {
        $retval = new Status();

        // First check if the data is a valid JSON
        $dataStatus = $content->getData();
        if ( !$dataStatus->isGood() ) {
            $retval->fatal( 'datamap-error-validate-invalid-json' );
            return $retval;
        }

        $data = $dataStatus->getValue();

        $retval->merge( $this->validateObject( $data ) );

        return $retval;
    }

    /**
     * @inheritDoc
     */
    public function validateObject( stdClass $data ): Status {
        $retval = new Status();
        return $retval;
    }
}
