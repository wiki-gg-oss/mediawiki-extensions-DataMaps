<?php
namespace MediaWiki\Extension\DataMaps\LegacyCompat\Content;

use MediaWiki\Extension\DataMaps\LegacyCompat\Content\DataConstraints\AssociationStringGroupExistsConstraint;
use MediaWiki\Extension\DataMaps\LegacyCompat\Content\DataConstraints\BackgroundLayerExistsConstraint;
use MediaWiki\Extension\DataMaps\LegacyCompat\Content\DataConstraints\CollectibleDependentPropertiesConstraint;
use MediaWiki\Extension\DataMaps\LegacyCompat\Content\DataConstraints\DataConstraint;
use MediaWiki\Extension\DataMaps\LegacyCompat\Content\DataConstraints\DeprecationConstraint;
use MediaWiki\Extension\DataMaps\LegacyCompat\Content\DataConstraints\LayerIdNoOverlapConstraint;
use MediaWiki\Extension\DataMaps\LegacyCompat\Content\DataConstraints\MarkerUidNoOverlapConstraint;
use MediaWiki\Extension\DataMaps\LegacyCompat\Content\DataConstraints\RequiredFilesConstraint;
use MediaWiki\Extension\DataMaps\LegacyCompat\Content\DataConstraints\SearchDependentPropertiesConstraint;
use MediaWiki\Extension\DataMaps\LegacyCompat\Content\DataConstraints\ZoomMinMaxConstraint;
use MediaWiki\Status\Status;
use stdClass;

class MapDataConstraintChecker {
    public function __construct(
        private readonly MapVersionInfo $version,
        private readonly stdClass $data,
        private readonly Status $status
    ) { }

    /**
     * @return DataConstraint[]
     */
    private function getConstraints(): array {
        return [
            new AssociationStringGroupExistsConstraint(),
            new LayerIdNoOverlapConstraint(),
            new MarkerUidNoOverlapConstraint(),
            new ZoomMinMaxConstraint(),
            new RequiredFilesConstraint(),
            new CollectibleDependentPropertiesConstraint(),
            new SearchDependentPropertiesConstraint(),
            new DeprecationConstraint(),
            new BackgroundLayerExistsConstraint(),
        ];
    }

    public function run(): bool {
        $result = true;
        $individual = [];
        foreach ( $this->getConstraints() as $constraint ) {
            foreach ( $constraint->getDependencies() as $dependency ) {
                if ( !$individual[$dependency] ) {
                    continue;
                }
            }

            $constraint->setStatus( $this->status, $this->version->isFragment );
            $constraintResult = $constraint->run( $this->version, $this->data );
            $constraint->setStatus( null );
            $individual[$constraint::class] = $constraintResult;
            $result = $result && $constraintResult;
        }
        return true;
    }
}
