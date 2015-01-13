<?php

/**
 * This file is part of Dedipanel project
 *
 * (c) 2010-2015 Dedipanel <http://www.dedicated-panel.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DP\VoipServer\VoipServerBundle\Entity;

use DP\Core\CoreBundle\Entity\MachineRelatedRepository;
use Doctrine\ORM\QueryBuilder;

abstract class VoipServerInstanceRepository extends MachineRelatedRepository
{
    abstract protected function validate(VoipServer $server);

    public function createNewInstance(VoipServer $server)
    {
        $this->validate($server);

        $className = $this->getClassName();

        return new $className($server);
    }

    protected function applyCriteria(QueryBuilder $queryBuilder, array $criteria = null)
    {
        if (isset($criteria['groups'])) {
            $queryBuilder
                ->innerJoin($this->getAlias() . '.server', 's', 'WITH', $this->getAlias() . '.server = s.id')
                ->innerJoin('s.machine', 'm', 'WITH', 's.machine = m.id')
                ->innerJoin('m.groups', 'g', 'WITH', $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->in('g.id', $criteria['groups'])
                ))
            ;

            unset($criteria['groups']);
        }

        parent::applyCriteria($queryBuilder, $criteria);
    }
}
