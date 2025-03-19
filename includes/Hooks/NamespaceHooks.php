<?php
namespace MediaWiki\Extension\DataMaps\Hooks;

use MediaWiki\Extension\DataMaps\ExtensionConfig;

// @phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName

final class NamespaceHooks implements
    \MediaWiki\Hook\CanonicalNamespacesHook
{
    public function __construct(
        private readonly ExtensionConfig $config
    ) { }

    /**
     * Registers Map namespace if configured so (default behaviour). Sets the robot policy if namespace ID is 2900.
     *
     * @param string[] &$namespaces
     * @return void
     */
    public function onCanonicalNamespaces( &$namespaces ) {
        if ( $this->config->isNamespaceManaged() ) {
            $namespaces[NS_MAP] = 'Map';
            $namespaces[NS_MAP_TALK] = 'Map_talk';
        }

        // If our default namespace ID is used (because some earlier wiki.gg DataMaps deployments had to define their own
        // namespace as we didn't manage any back then) set the robot policy to disallow indexing if it hasn't been specified by
        // local sysadmins.
        //
        // Articles should embed the maps as needed, as that is the most likely target for map usage anyway. Source pages should
        // not compete.
        global $wgNamespaceRobotPolicies;
        if ( $this->config->getNamespaceId() === 2900 && !isset( $wgNamespaceRobotPolicies[2900] ) ) {
            $wgNamespaceRobotPolicies[2900] = 'noindex,follow';
        }
    }
}
