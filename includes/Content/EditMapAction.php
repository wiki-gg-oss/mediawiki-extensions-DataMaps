<?php
namespace MediaWiki\Extension\DataMaps\Content;

use FormlessAction;
use MediaWiki\Extension\DataMaps\Constants;
use MediaWiki\Extension\DataMaps\HookHandler;
use MediaWiki\Extension\DataMaps\Rendering\EmbedRenderOptions;
use MediaWiki\Html\Html;
use MediaWiki\MediaWikiServices;
use MediaWiki\Parser\ParserOptions;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\SlotRecord;
use OOUI\ProgressBarWidget;

class EditMapAction extends FormlessAction {
    /**
     * @return string
     */
    public function getName() {
        return 'editmap';
    }

    /**
     * @return string|null
     */
    public function onView() {
        return null;
    }

    public function doesWrites() {
        return true;
    }

    public function show() {
        $this->useTransactionalTimeLimit();

        $req = $this->getRequest();
        $article = $this->getArticle();
        $user = $this->getAuthority()->getUser();
        $out = $this->getOutput();

        // The editor should always see the latest content when starting their edit
        $out->setRobotPolicy( 'noindex,nofollow' );
        $out->disableClientCache();

        // Set up page title and revision ID
        $out->setPageTitle( wfMessage( 'editing', $article->getTitle()->getPrefixedText() ) );
        $out->setRevisionId( $req->getInt( 'oldid', $article->getRevIdFetched() ) );

        // Check if VE can be used on this page
        if ( !HookHandler::canUseVE( null, $this->getArticle()->getTitle() ) ) {
            // Add an extra message if the page is a mix-in - these cannot be edited in the visual editor at the moment
            $pageProps = MediaWikiServices::getInstance()->getPageProps();
            if ( count( $pageProps->getProperties( $article->getTitle(), Constants::PAGEPROP_IS_MIXIN ) ) > 0 ) {
                $out->addWikiMsg( 'datamap-ve-cannot-edit-mixins' );
            }

            $out->addWikiMsg( 'datamap-ve-needs-fallback-to-source', $this->getArticle()->getTitle()->getFullURL( [
                'action' => 'edit'
            ] ) );
            return;
        }

        // Check if the user can edit this page, and resort back to source editor (which should display the errors and
        // a source view) if they can't.
        $permErrors = MediaWikiServices::getInstance()->getPermissionManager()
            ->getPermissionErrors( 'edit', $user, $article->getTitle(), PermissionManager::RIGOR_FULL );
        if ( $permErrors ) {
            return;
        }

        // Fetch the content object
        /** @var DataMapContent */
        $content = $article->fetchRevisionRecord()->getContent( SlotRecord::MAIN, RevisionRecord::FOR_THIS_USER,
            $this->getAuthority() );

        // Run validation as the visual editor cannot handle source-level errors
        $status = $content->getValidationStatus();
        if ( !$status->isOk() ) {
            $out->addWikiMsg( 'datamap-ve-cannot-edit-validation-errors', $status->getMessage( false, false ) );
            $out->addWikiMsg( 'datamap-ve-needs-fallback-to-source', $this->getArticle()->getTitle()->getFullURL( [
                'action' => 'edit'
            ] ) );
            return;
        }

        $out->enableOOUI();
        \OOUI\Theme::setSingleton( new \OOUI\WikimediaUITheme() );
        \OOUI\Element::setDefaultDir( 'ltr' );

        // Render a placeholder message for the editor
        $out->addHTML( Html::rawElement( 'div',
            [
                'class' => 'ext-datamaps-ve-load-placeholder'
            ],
            Html::element( 'p', [], $this->msg( 'datamap-ve-to-load' ) )
            . ( new ProgressBarWidget( [ 'progress' => false ] ) )->toString()
        ) );

        // Render an empty embed with no markers
        $parserOptions = ParserOptions::newFromAnon();
        $parserOptions->setIsPreview( true );
        $parser = MediaWikiServices::getInstance()->getParser();
        $parser->setOptions( $parserOptions );
        $parserOutput = new ParserOutput();

        // Get an embed renderer
        $embedRenderer = $content->getEmbedRenderer( $article->getTitle(), $parser, $parserOutput, [
            've' => true
        ] );

        // Render a marker-less embed
        $embedRenderer->prepareOutput();
        $out->addParserOutputMetadata( $parserOutput );
        $out->addHTML( $embedRenderer->getHtml( new EmbedRenderOptions() ) );

        // Inject the JavaScript module
        $out->addModules( [
            'ext.datamaps.ve'
        ] );
    }
}
