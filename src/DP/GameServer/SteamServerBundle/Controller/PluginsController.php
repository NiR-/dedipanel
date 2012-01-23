<?php

/*
** Copyright (C) 2010-2012 Kerouanton Albin, Smedts Jérôme
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License along
** with this program; if not, write to the Free Software Foundation, Inc.,
** 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
*/

namespace DP\GameServer\SteamServerBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

use DP\GameServer\SteamServerBundle\Entity\SteamServer;
use DP\Core\GameBundle\Entity\Plugin;

class PluginsController extends Controller
{
    public function showServerAction($id)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $server = $em->getRepository('DPSteamServerBundle:SteamServer')->find($id);
        
        if (!$server) {
            throw $this->createNotFoundException('Unable to find SteamServer entity.');
        }
        
        return $this->render('DPSteamServerBundle:Plugins:show.html.twig', array(
            'server' => $server
        ));
    }
    
    public function installAction($id, $plugin)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $server = $em->getRepository('DPSteamServerBundle:SteamServer')->find($id);
        $plugin = $em->getRepository('DPGameBundle:Plugin')->find($plugin);
        
        if (!$server) {
            throw $this->createNotFoundException('Unable to find SteamServer entity.');
        }
        if (!$plugin) {
            throw $this->createNotFoundException('Unable to find Plugin entity.');
        }
        
        // On upload et on exécute le script du plugin
        // Puis on supprime la liaison entre le serv et le plugin
        $server->execPluginScript($this->get('twig'), $plugin, 'install');
        $server->addPlugin($plugin);
        $em->flush();
        
        return $this->redirect($this->generateUrl('steam_plugins_show', array('id' => $id)));
    }
    
    public function uninstallAction($id, $plugin)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $server = $em->getRepository('DPSteamServerBundle:SteamServer')->find($id);
        $plugin = $em->getRepository('DPGameBundle:Plugin')->find($plugin);
        
        if (!$server) {
            throw $this->createNotFoundException('Unable to find SteamServer entity.');
        }
        if (!$plugin) {
            throw $this->createNotFoundException('Unable to find Plugin entity.');
        }
        
        // On upload et on exécute le script du plugin
        // Puis on supprime la liaison entre le serv et le plugin
        $server->execPluginScript($this->get('twig'), $plugin, 'uninstall');
        $server->removePlugin($plugin);
        $em->flush();
        
        return $this->redirect($this->generateUrl('steam_plugins_show', array('id' => $id)));
    }
}