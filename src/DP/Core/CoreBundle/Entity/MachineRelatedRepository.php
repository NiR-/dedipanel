<?php

namespace DP\Core\CoreBundle\Entity;

use DP\Core\CoreBundle\Entity\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class MachineRelatedRepository extends EntityRepository
{
    protected function applyCriteria(QueryBuilder $queryBuilder, array $criteria = null)
    {
        if (isset($criteria['groups'])) {
            $queryBuilder
                ->innerJoin($this->getAlias() . '.machine', 'm', 'WITH', $this->getAlias() . '.machine = m.id')
                ->innerJoin('m.groups', 'g', 'WITH', $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->in('g.id', $criteria['groups'])
                ))
            ;

            unset($criteria['groups']);
        }

        parent::applyCriteria($queryBuilder, $criteria);
    }
}

