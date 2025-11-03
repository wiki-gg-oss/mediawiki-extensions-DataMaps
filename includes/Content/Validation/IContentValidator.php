<?php
namespace MediaWiki\Extension\DataMaps\Content\Validation;

use MediaWiki\Extension\DataMaps\Content\MapContent;
use MediaWiki\Status\Status;

interface IContentValidator {
    public function validateContentObject( MapContent $content ): Status;
}
