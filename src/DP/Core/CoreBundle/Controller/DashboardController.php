<?php

/**
 * This file is part of Dedipanel project
 *
 * (c) 2010-2015 Dedipanel <http://www.dedicated-panel.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DP\Core\CoreBundle\Controller;

use DP\Core\CoreBundle\Controller\ResourceController;


class DashboardController extends ResourceController
{
    public function mainAction()
    {
        $steamRepository        = $this->get('dedipanel.repository.steam');
        $minecraftRepository    = $this->get('dedipanel.repository.minecraft');
        $teamspeakRepository    = $this->get('dedipanel.repository.teamspeak');
        $userRepository         = $this->get('dedipanel.repository.user');
        $machineRepository      = $this->get('dedipanel.repository.machine');

        return $this->render('DPCoreBundle:Dashboard:Dashboard.html.twig', array(
            'steamServers'      => $steamRepository->findBy(array(), array('id' => 'desc'), 5),
            'minecraftServers'  => $minecraftRepository->findBy(array(), array('id' => 'desc'), 5),
            'teamspeakServers'  => $teamspeakRepository->findBy(array(), array('id' => 'desc'), 5),
            'users'             => $userRepository->findBy(array(), array('lastLogin' => 'desc'), 10),
            'machines'          => $machineRepository->findBy(array(), array('id' => 'desc'), 5),
        ));
    }
}