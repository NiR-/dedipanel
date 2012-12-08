<?php

namespace DP\Core\DistributionBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use DP\Core\DistributionBundle\Configurator\Step\DoctrineStep;
use DP\Core\DistributionBundle\Configurator\Step\AutoInstallStep;
use DP\Core\DistributionBundle\Configurator\Step\UserStep;

class DPDistributionBundle extends Bundle
{
    public function boot()
    {
        $installer = $this->container->get('dp.webinstaller');
        
        $installer->addStep(new DoctrineStep($this->container));
        $installer->addStep(new AutoInstallStep($this->container));
        $installer->addStep(new UserStep($this->container));
    }
}
