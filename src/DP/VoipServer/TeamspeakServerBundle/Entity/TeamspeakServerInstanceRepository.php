<?php

namespace DP\VoipServer\TeamspeakServerBundle\Entity;

use DP\VoipServer\VoipServerBundle\Entity\VoipServerInstanceRepository;
use DP\VoipServer\VoipServerBundle\Entity\VoipServer;

class TeamspeakServerInstanceRepository extends VoipServerInstanceRepository
{
    public function createNewInstance(VoipServer $server)
    {
        if (!$server instanceof TeamspeakServer) {
            throw new \InvalidArgumentException('You need to provide a teamspeak server if you want to create a teamspeak instance.');
        }

        $className = $this->getClassName();

        return new $className($server);
    }
}