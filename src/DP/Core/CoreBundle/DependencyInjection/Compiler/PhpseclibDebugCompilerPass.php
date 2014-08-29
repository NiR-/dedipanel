<?php

namespace DP\Core\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Modify services requiring logger with phpseclib channel
 * and inject NullLogger if debug is disabled
 *
 * @package DP\Core\CoreBundle\DependencyInjection\Compiler
 */
class PhpseclibDebugCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->getParameterBag()->get('dedipanel.debug')) {
            $this->replaceByNullLogger($container, 'monolog.handler.phpseclib_wrapper');
            $this->replaceByNullLogger($container, 'monolog.handler.phpseclib_internal');
        }
    }

    private function replaceByNullLogger(ContainerBuilder $container, $handler)
    {
        $def = $container->getDefinition($handler);
        $def->setClass('%monolog.handler.null.class%');

        $container->setDefinition($handler, $def);
    }
}
