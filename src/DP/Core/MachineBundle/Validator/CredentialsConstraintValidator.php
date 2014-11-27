<?php

/**
 * This file is part of Dedipanel project
 *
 * (c) 2010-2014 Dedipanel <http://www.dedicated-panel.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DP\Core\MachineBundle\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Dedipanel\PHPSeclibWrapperBundle\Connection\ConnectionManagerInterface;
use Dedipanel\PHPSeclibWrapperBundle\Connection\Exception\ConnectionErrorException;
use Symfony\Component\Validator\Context\ExecutionContextInterface;


class CredentialsConstraintValidator extends ConstraintValidator
{
    protected $manager;
    
    public function __construct(ConnectionManagerInterface $manager)
    {
        $this->manager = $manager;
    }
    
    public function validate($value, Constraint $constraint)
    {
        // N'exécute pas la validation des identifiants
        // s'il y a déjà eu des erreurs
        if (count($this->context->getViolations()) === 0) {
            $conn = $this->manager->getConnectionFromServer($value, 0);
            $test = false;

            try {
                $test = $conn->testSSHConnection();
            }
            catch (ConnectionErrorException $e) {
                // The test failed
            }

            if (!$test) {
                $this->context->buildViolation('machine.assert.bad_credentials')->addViolation();

            }
        }
    }
}
