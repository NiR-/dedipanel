<?php

namespace DP\GameServer\SteamServerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use DP\GameServer\GameServerBundle\Entity\GameServer;
use DP\Core\MachineBundle\PHPSeclibWrapper\PHPSeclibWrapper;
use DP\GameServer\SteamServerBundle\SteamQuery\SteamQuery;

/**
 * DP\GameServer\SteamServerBundle\Entity\SteamServer
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="DP\GameServer\SteamServerBundle\Entity\SteamServerRepository")
 */
class SteamServer extends GameServer {
    /**
     * @var integer $autoReboot
     *
     * @ORM\Column(name="autoReboot", type="integer", nullable=true)
     */
    private $autoReboot;

    /**
     * @var string $rcon
     *
     * @ORM\Column(name="rcon", type="string", length=32, nullable=true)
     */
    private $rcon;

    /**
     * @var boolean $munin
     *
     * @ORM\Column(name="munin", type="boolean", nullable=true)
     */
    private $munin;

    /**
     * @var string $sv_passwd
     *
     * @ORM\Column(name="sv_passwd", type="string", length=16, nullable=true)
     */
    private $sv_passwd;

    /**
     * @var integer $core
     *
     * @ORM\Column(name="core", type="integer", nullable=true)
     */
    private $core;
    
    /**
     * @var \Doctrine\Common\Collections\ArrayCollection $plugins
     * 
     * @ORM\ManyToMany(targetEntity="DP\Core\GameBundle\Entity\Plugin") 
     * @ORM\JoinTable(name="steamserver_plugins",
     *      joinColumns={@ORM\JoinColumn(name="server_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="plugin_id", referencedColumnName="id")}
     * )
     */
    private $plugins;
    
    
    public function __construct()
    {
        $this->plugins = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
    /**
     * Set autoReboot
     *
     * @param integer $autoReboot
     */
    public function setAutoReboot($autoReboot)
    {
        $this->autoReboot = $autoReboot;
    }

    /**
     * Get autoReboot
     *
     * @return integer 
     */
    public function getAutoReboot()
    {
        return $this->autoReboot;
    }

    /**
     * Set rcon
     *
     * @param string $rcon
     */
    public function setRcon($rcon)
    {
        $this->rcon = $rcon;
    }

    /**
     * Get rcon
     *
     * @return string 
     */
    public function getRcon()
    {
        return $this->rcon;
    }

    /**
     * Set munin
     *
     * @param boolean $munin
     */
    public function setMunin($munin)
    {
        $this->munin = $munin;
    }

    /**
     * Get munin
     *
     * @return boolean 
     */
    public function getMunin()
    {
        return $this->munin;
    }

    /**
     * Set sv_passwd
     *
     * @param string $svPasswd
     */
    public function setSvPasswd($svPasswd)
    {
        $this->sv_passwd = $svPasswd;
    }

    /**
     * Get sv_passwd
     *
     * @return string 
     */
    public function getSvPasswd()
    {
        return $this->sv_passwd;
    }

    /**
     * Set core
     *
     * @param integer $core
     */
    public function setCore($core)
    {
        $this->core = $core;
    }

    /**
     * Get core
     *
     * @return integer 
     */
    public function getCore()
    {
        return $this->core;
    }
    
    /**
     * Get absolute path of server installation directory
     * 
     * @return string
     */
    private function getAbsoluteDir()
    {
        return $this->machine->getHome() . '/' . $this->dir . '/';
    }
    
    /**
     * Get absolute path of binaries directory
     * 
     * @return string
     */
    private function getAbsoluteBinDir()
    {
        return $this->getAbsoluteDir() . $this->game->getBinDir();
    }
    
    /**
     * Upload & launch game server installation
     * 
     * @param \Twig_Environment $twig Used for generate shell script
     */
    public function installServer(\Twig_Environment $twig)
    {
        $installDir = $this->getAbsoluteDir();
        $scriptPath = $installDir . 'install.sh';
        $logPath = $installDir . 'install.log';
        $screenName = 'install-' . $this->dir;
        $installName = $this->game->getInstallName();
        
        $mkdirCmd = 'if [ ! -e ' . $installDir . ' ]; then mkdir ' . $installDir . '; fi';
        $screenCmd  = 'screen -dmS ' . $screenName . ' ' . $scriptPath . ' "' . $installName . '"';
        
        $installScript = $twig->render('DPSteamServerBundle:sh:install.sh.twig', 
            array('installDir' => $installDir));
        
        $sec = PHPSeclibWrapper::getFromMachineEntity($this->getMachine());
        $sec->exec($mkdirCmd);
        $sec->upload($scriptPath, $installScript);
        $sec->exec($screenCmd);
        
        $this->installationStatus = 0;
    }
    
    public function getGameInstallationProgress()
    {
        $logPath = $this->getAbsoluteDir() . 'install.log';
        $screenName = 'install-' . $this->dir;
        
        $sec = PHPSeclibWrapper::getFromMachineEntity($this->getMachine());
        $installLog = $sec->exec('cat ' . $logPath);
        
        if (strpos($installLog, 'Install ended') !== false) {
            // Si l'installation est terminé, on supprime le fichier de log
            $sec->exec('rm -f ' . $logPath);
           return 101; // 101 == serveur installé
        }
        elseif (strpos($installLog, 'Game install') !== false) {
            // Si on en est rendu au téléchargement des données, 
            // On récupère le pourcentage du dl dans le screen
            // Pour l'afficher à l'utilisateur
            $tmpFile = '/tmp/' . uniqid();
            $cmd = 'screen -S install-' . $this->dir . ' -X hardcopy ' . $tmpFile . '; sleep 1s;';
            $cmd .= 'if [ -e ' . $tmpFile . ' ]; then cat ' . $tmpFile . '; rm -f ' . $tmpFile . '; fi';
            
            $screenContent = $sec->exec($cmd);
            
            if ($screenContent == 'No screen session found.') return null;
            else {
                // Si on a réussi à récupérer le contenu du screen, 
                // On recherche dans chaque ligne en commencant par la fin
                // Un signe "%" afin de connaître le % le plus à jour
                $lines = array_reverse(explode("\n", $screenContent));
                
                foreach ($lines AS $line) {
                    $percentPos = strpos($line, '%');
                    if ($percentPos !== false) {
                        return substr($screenContent, 0, $percentPos);
                    }
                }
            }
        }
        elseif (strpos($installLog, 'Steam updating')) {
            return 2;
        }
        elseif (strpos($installLog, 'DL hldsupdatetool.bin')) {
            return 1;
        }
        else {
            return null;
        }
    }
    
    public function uploadHldsScript(\Twig_Environment $twig)
    {
        $game = $this->getGame();
        $machine = $this->getMachine();
        $screenName = $machine->getUser() . '-' . $this->getDir();
        
        $binDir = $this->getAbsoluteBinDir();
        $scriptPath = $binDir . 'hlds.sh';
        
        $hldsScript = $twig->render('DPSteamServerBundle:sh:hlds.sh.twig', array(
            'screenName' => $screenName, 'bin' => $game->getBin(), 
            'launchName' => $game->getLaunchName(), 'ip' => $machine->getPublicIp(), 
            'port' => $this->getPort(), 'maxplayers' => $this->getMaxplayers(), 
            'startMap' => $game->getMap(), 'binDir' => $binDir, 
        ));
        
        $sec = PHPSeclibWrapper::getFromMachineEntity($machine);
        return $sec->upload($scriptPath, $hldsScript, 0750);
    }
    
    public function changeStateServer($state)
    {
        $scriptPath = $this->getAbsoluteBinDir() . 'hlds.sh';
        
        $sec = PHPSeclibWrapper::getFromMachineEntity($this->getMachine());
        
        return $sec->exec($scriptPath . ' ' . $state);
    }
    
    public function setQuery(SteamQuery $query)
    {
        $this->query = $query;
    }
    
    public function getQuery()
    {
        return $this->query;
    }
    
    /**
     * Add plugin
     * 
     * @param \DP\Core\GameBundle\Entity\Plugin $plugin 
     */
    public function addPlugin(\DP\Core\GameBundle\Entity\Plugin $plugin)
    {
        $this->plugins[] = $plugin;
    }
    
    /**
     * Get plugins recorded as "installed on the server"
     * 
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getPlugins()
    {
        if ($this->plugins instanceof \Doctrine\ORM\PersistentCollection) {
            return $this->plugins->getValues();
        }
        else {
            return $this->plugins;
        }
    }
}