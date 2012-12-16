<?php

namespace DP\GameServer\MinecraftServerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use DP\GameServer\GameServerBundle\Entity\GameServer;
use Symfony\Component\Validator\Constraints as Assert;
use DP\Core\MachineBundle\PHPSeclibWrapper\PHPSeclibWrapper;

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
     * @ORM\Column(name="queryPort", type="integer")
     * @Assert\Min(limit="1", message="gameServer.assert.queryPort.min")
     * @Assert\Max(limit="65536", message="gameServer.assert.queryPort.max")
     */
    protected $queryPort;
    
    public function setQueryPort($queryPort)
    {
        $this->queryPort = $queryPort;
    }
    
    public function getQueryPort()
    {
        return $this->queryPort;
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
        
        $uploadMinecraftScript = $sec->upload($scriptPath, $minecraftScript, 0750);
//        echo'<pre>'; print_r($sec->getSFTP()->getSFTPLog()); echo'</pre>';
        
        if ($uploadMinecraftScript) {
            $this->installationStatus = 101;
            
            return true;
        }
        else {
            return false;
        }
    }
}
