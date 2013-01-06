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

namespace DP\GameServer\MinecraftServerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use DP\GameServer\GameServerBundle\Entity\GameServer;
use Symfony\Component\Validator\Constraints as Assert;
use DP\Core\MachineBundle\PHPSeclibWrapper\PHPSeclibWrapper;
use DP\Core\GameBundle\Entity\Plugin;

/**
 * DP\GameServer\MinecraftServerBundle\Entity\MinecraftServer
 *
 * @ORM\Table(name="minecraft_server")
 * @ORM\Entity(repositoryClass="DP\GameServer\MinecraftServerBundle\Entity\MinecraftServerRepository")
 */
class MinecraftServer extends GameServer
{
    
    /**
     * @var integer $queryPort
     *
     * @ORM\Column(name="queryPort", type="integer", nullable=true)
     * @Assert\Min(limit="1", message="minecraft.assert.queryPort.min")
     * @Assert\Max(limit="65536", message="minecraft.assert.queryPort.max")
     */
    protected $queryPort;
    
    /**
     * @var integer $rconPort
     *
     * @ORM\Column(name="rconPort", type="integer")
     * @Assert\Min(limit="1", message="minecraft.assert.rconPort.min")
     * @Assert\Max(limit="65536", message="minecraft.assert.rconPort.max")
     */
    protected $rconPort;
    
    /*
     * Set minecraft query port
     * 
     * @param integer $queryPort
     */
    public function setQueryPort($queryPort)
    {
        $this->queryPort = $queryPort;
    }
    
    /*
     * Get minecraft query port
     * 
     * @return integer Query port
     */
    public function getQueryPort()
    {
        if (isset($this->queryPort)) {
            return $this->queryPort;
        }
        else {
            return $this->getPort();
        }
    }
    
    /*
     * Set rcon port
     * 
     * @param integer $rconPort
     */
    public function setRconPort($rconPort)
    {
        $this->rconPort = $rconPort;
    }
    
    /*
     * Get rcon port
     * 
     * @return integer RCON Port
     */
    public function getRconPort()
    {
        return $this->rconPort;
    }
    
    /**
     * Download server
     */
    public function installServer()
    {      
        $installDir = $this->getAbsoluteDir();
        $logPath = $installDir . 'install.log';
        
        $mkdirCmd = 'if [ ! -e ' . $installDir . ' ]; then mkdir ' . $installDir . '; fi';
        
        $dlUrl = 'https://s3.amazonaws.com/MinecraftDownload/launcher/minecraft_server.jar';
        if ($this->game->getInstallName() == 'bukkit') {
            $dlUrl = 'http://dl.bukkit.org/latest-rb/craftbukkit.jar';
        }
        
        $dlCmd = 'cd ' . $installDir . ' && wget -N -o ' . $logPath . ' ' . $dlUrl . ' &';
        
        $sec = PHPSeclibWrapper::getFromMachineEntity($this->getMachine());
        $sec->exec($mkdirCmd);
        $sec->exec($dlCmd);
        
        $this->installationStatus = 0;
    }
    
    public function getInstallationProgress(\Twig_Environment $twig)
    {      
        $installDir = $this->getAbsoluteDir();
        $logPath = $installDir . 'install.log';
        $binPath = $installDir . $this->getGame()->getBin();
        
        $sec = PHPSeclibWrapper::getFromMachineEntity($this->getMachine());
        
        // On détermine si le log d'installation est présent
        // Si ce n'est pas le cas mais que le binaire est là, c'est que l'install est déjà terminé
        $statusCmd = $twig->render('DPMinecraftServerBundle:sh:installStatus.sh.twig', array(
            'installDir'    => $installDir, 
            'logPath'       => $logPath, 
            'binPath'       => $binPath,
        ));
        $status = intval($sec->exec($statusCmd));
        
        if ($status == 2) {
            return 100;
        }
        if ($status == 1) {
            // On récupère les 20 dernières lignes du fichier afin de déterminer le pourcentage
            $tailCmd = 'tail -n 20 ' . $logPath;
            $installLog = $sec->exec($tailCmd);
            $percent = $this->getPercentFromInstallLog($installLog);
            
            if (!empty($percent)) {
                if ($percent == 100) {
                    $sec->exec('rm ' . $logPath);
                }
                
                return $percent;
            }
        }
        
        return null;
    }
    
    protected function getPercentFromInstallLog($installLog)
    {
        // On recherche dans chaque ligne en commencant par la fin
        // Un signe "%" afin de connaître le % le plus à jour
        $lines = array_reverse(explode("\n", $installLog));

        foreach ($lines AS $line) {                    
            $percentPos = strpos($line, '%');

            if ($percentPos !== false) {
                $line = substr($line, 0, $percentPos);
                $spacePos = strrpos($line, ' ')+1;

                return substr($line, $spacePos);
            }
        }
        
        return null;
    }
    
    public function uploadShellScripts(\Twig_Environment $twig)
    {
        $sec = PHPSeclibWrapper::getFromMachineEntity($this->getMachine());
        $game = $this->getGame(); 
        
        $scriptPath = $this->getAbsoluteDir() . 'minecraft.sh';
        
        $minecraftScript = $twig->render('DPMinecraftServerBundle:sh:minecraft.sh.twig', array(
            'screenName' => $this->getScreenName(), 'bin' => $game->getBin(), 
            'options' => 'nogui', 'minHeap' => 1024, 'maxHeap' => '1024', 
            'parallelThreads' => 1, 'binDir' => $this->getAbsoluteBinDir(), 
        ));
        
        if (!$sec->upload($scriptPath, $minecraftScript, 0750)) {
            return false;
        }
        
        if (!$this->uploadDefaultServerPropertiesFile($twig)) {
            return false;
        }
        
        $this->installationStatus = 101;
            
        return true;
    }
    
    public function changeStateServer($state)
    {
        $scriptPath = $this->getAbsoluteDir() . 'minecraft.sh';
        
        return PHPSeclibWrapper::getFromMachineEntity($this->getMachine())
                ->exec($scriptPath . ' ' . $state);
    }
    
    public function uploadDefaultServerPropertiesFile(\Twig_Environment $twig)
    {
        $sec = PHPSeclibWrapper::getFromMachineEntity($this->getMachine());
        $cfgPath = $this->getAbsoluteDir() . 'server.properties';
        
        // Supression du fichier s'il existe déjà
        $sec->exec('if [ -e ' . $cfgPath . ']; then rm ' . $cfgPath . '; fi');
        
        $template = 'DPMinecraftServerBundle:cfg:server.properties.' . $this->getGame()->getInstallName() . '.twig';
        $cfgFile = $twig->render($template, array(
            'serverPort'    => $this->getPort(), 
            'queryPort'     => $this->getQueryPort(), 
            'rconPort'      => $this->getRconPort(), 
            'rconPassword'  => $this->getRconPassword(), 
            'maxPlayers'    => $this->getMaxplayers(), 
            'serverName'    => $this->getName(), 
            'ip'            => $this->getMachine()->getPublicIp(), 
        ));
        
        return $sec->upload($cfgPath, $cfgFile, 0750);
    }
    
    public function execPluginScript(\Twig_Environment $twig, Plugin $plugin, $action)
    {
        $dir = $this->getAbsoluteDir();
        $scriptPath = $dir . 'plugin.sh';
        
        // Hashage du screen name pour qu'il ne dépasse pas le max de caractère
        $screenName = sha1($this->getMachine()->getUser() . '-plugin-' . $this->getDir(), true);
        $screenCmd  = 'screen -dmS ' . $screenName . ' ' . $scriptPath . ' ' . $action;
        
        if ($action == 'install') {
            $screenCmd .= ' "' . $plugin->getDownloadUrl () . '"';
        }
        
        $pluginScript = $twig->render(
            'DPMinecraftServerBundle:sh:plugin.sh.twig', array('gameDir' => $dir . 'plugins'));
        
        $sec = PHPSeclibWrapper::getFromMachineEntity($this->getMachine());
        $sec->upload($scriptPath, $pluginScript);
        $sec->exec($screenCmd);
    }
}
