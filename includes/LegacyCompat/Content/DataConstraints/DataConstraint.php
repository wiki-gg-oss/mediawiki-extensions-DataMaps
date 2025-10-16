<?php
namespace MediaWiki\Extension\DataMaps\LegacyCompat\Content\DataConstraints;

use MediaWiki\Extension\DataMaps\LegacyCompat\Content\MapVersionInfo;
use MediaWiki\Status\Status;
use stdClass;

abstract class DataConstraint {
    protected ?Status $status;
    protected bool $permissive = false;

    public function setStatus( ?Status $status, bool $permissive = false ): void {
        $this->status = $status;
        $this->permissive = $permissive;
    }

    protected function emitWarning( string $message, ...$parameters ): void {
        $this->status->warning( $message, ...$parameters );
    }

    protected function emitError( string $message, ...$parameters ): void {
        $this->status->fatal( $message, ...$parameters );
    }

    protected function emitErrorPermissive( string $message, ...$parameters ): void {
        if ( $this->permissive ) {
            $this->status->warning( $message, ...$parameters );
        } else {
            $this->status->fatal( $message, ...$parameters );
        }
    }

    abstract public function getDependencies(): array;
    abstract public function run( MapVersionInfo $version, stdClass $data ): bool;
}
