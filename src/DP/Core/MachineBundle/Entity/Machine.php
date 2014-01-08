<?php

/*
** Copyright (C) 2010-2013 Kerouanton Albin, Smedts Jérôme
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

namespace DP\Core\MachineBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use DP\Core\MachineBundle\PHPSeclibWrapper\PHPSeclibWrapper;
use DP\GameServer\GameServerBundle\Entity\GameServer;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * DP\Core\MachineBundle\Entity\Machine
 *
 * @ORM\Table(name="machine")
 * @ORM\Entity
 */
class Machine
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var bigint $privateIp
     *
     * @ORM\Column(name="privateIp", type="string", length=15, nullable=true)
     */
    private $privateIp;

    /**
     * @var bigint $publicIp
     *
     * @ORM\Column(name="publicIp", type="string", length=15, nullable=true)
     */
    private $publicIp;

    /**
     * @var integer $port
     *
     * @ORM\Column(name="port", type="integer")
     */
    private $port = 22;

    /**
     * @var string $user
     *
     * @ORM\Column(name="user", type="string", length=16)
     */
    private $user;

    /**
     * @var string $password
     */
    private $password;

    /**
     * @var string $privateKey
     *
     * @ORM\Column(name="privateKey", type="string", length=23)
     */
    private $privateKeyFilename;

    /**
     * @var string $publicKey
     *
     * @ORM\Column(name="publicKey", type="string", length=255, nullable=true)
     */
    private $publicKey;

    /**
     * @var string $home
     *
     * @ORM\Column(name="home", type="string", length=255, nullable=true)
     */
    private $home;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection $gameServers
     *
     * @ORM\OneToMany(targetEntity="DP\GameServer\GameServerBundle\Entity\GameServer", mappedBy="machine", cascade={"persist"})
     */
    private $gameServers;

    /**
     * @var integer
     *
     * @ORM\Column(name="nbCore", type="integer", nullable=true)
     */
    private $nbCore;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is64bit", type="boolean")
     */
    private $is64bit = false;


    public function __construct()
    {
        $this->gameServers = new \Doctrine\Common\Collections\ArrayCollection();
        $this->voipServers = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function addGameServer(GameServer $srv)
    {
        $srv->setMachine($this);
        $this->gameServers[] = $srv;
    }

    public function getGameServers()
    {
        return $this->gameServers;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set privateIp
     *
     * @param bigint $privateIp
     */
    public function setPrivateIp($privateIp)
    {
        $this->privateIp = $privateIp;
    }

    /**
     * Get privateIp
     *
     * @return bigint
     */
    public function getPrivateIp()
    {
        return $this->privateIp;
    }

    /**
     * Set publicIp
     *
     * @param bigint $publicIp
     */
    public function setPublicIp($publicIp)
    {
        $this->publicIp = $publicIp;
    }

    /**
     * Get publicIp
     *
     * @return bigint
     */
    public function getPublicIp()
    {
        if (empty($this->publicIp)) {
            return $this->privateIp;
        }
        else {
            return $this->publicIp;
        }
    }

    /**
     * Set port
     *
     * @param integer $port
     */
    public function setPort($port)
    {
        $this->port = $port;
    }

    /**
     * Get port
     *
     * @return integer
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * Set user
     *
     * @param string $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * Get user
     *
     * @return string
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set filename of the private key
     *
     * @param string $privateKeyFilename
     */
    public function setPrivateKeyFilename($privateKeyFilename) {
        $this->privateKeyFilename = $privateKeyFilename;
    }

    /**
     * Get filename of the private key
     *
     * @return string
     */
    public function getPrivateKeyFilename() {
        return $this->privateKeyFilename;
    }

    /**
     * Set publicKey
     *
     * @param string $publicKey
     */
    public function setPublicKey($publicKey)
    {
        $this->publicKey = $publicKey;
    }

    /**
     * Get publicKey
     *
     * @return string
     */
    public function getPublicKey()
    {
        return $this->publicKey;
    }

    /**
     * Set password
     *
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }
    /**
     * Get password
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set home
     *
     * @param string $home
     */
    public function setHome($home)
    {
        $this->home = $home;
    }
    /**
     * Get home
     *
     * @return string
     */
    public function getHome()
    {
        return $this->home;
    }

    public function __toString() {
        return $this->user . '@' . $this->privateIp . ':' . $this->port;
    }

    /**
     * Set the number of core on the server
     *
     * @param integer $nbCore
     */
    public function setNbCore($nbCore)
    {
        $this->nbCore = $nbCore;
    }

    /**
     * Get the number of core on the server
     *
     * @return integer Number of core
     */
    public function getNbCore()
    {
        return $this->nbCore;
    }

    public function retrieveNbCore()
    {
        return PHPSeclibWrapper::getFromMachineEntity($this)
                ->exec('grep processor /proc/cpuinfo | wc -l');
    }

    /**
     * Sets is 64 bit system
     *
     * @param integer $is64bit Is 64 bit system ?
     *
     * @return Machine
     */
    public function setIs64Bit($is64bit)
    {
        $this->is64bit = $is64bit;

        return $this;
    }

    /**
     * Gets is 64 bit system
     *
     * @return integer Is 64 bit system
     */
    public function getIs64Bit()
    {
        return $this->is64bit;
    }

    public function updateCrontab($search, $replace)
    {
        $cmd  = 'crontab -l | awk \'BEGIN{search="' . $search . '"; replacement="' . $replace . '"}//';
        $cmd .= '{if ($6 == search) { print replacement; found=1} else { print }}';
        $cmd .= 'END{ if (!found) { print replacement }}\' | crontab -';

        return PHPSeclibWrapper::getFromMachineEntity($this)
                ->exec($cmd);
    }

    public function removeFromCrontab($search)
    {
        $cmd  = 'crontab -l | awk \'BEGIN{search="' . $search . '"}//';
        $cmd .= '{if ($6 == search) { found=1} else { print }}\' | crontab -';

        return PHPSeclibWrapper::getFromMachineEntity($this)
                ->exec($cmd);
    }

    public function fileExists($filepath)
    {
        return PHPSeclibWrapper::getFromMachineEntity($this)->fileExists($filepath);
    }

    public function dirExists($dirpath)
    {
        return PHPSeclibWrapper::getFromMachineEntity($this)->dirExists($dirpath);
    }
    
    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('privateIp', new Assert\Ip(array('message' => 'machine.assert.privateIp')));
        $metadata->addPropertyConstraint('publicIp', new Assert\Ip(array('message' => 'machine.assert.publicIp')));
        $metadata->addPropertyConstraint('port', new Assert\Range(array(
            'min' => 1, 
            'minMessage' => 'machine.assert.port', 
            'max' => 65536, 
            'maxMessage' => 'machine.assert.port', 
        )));
        $metadata->addPropertyConstraint('user', new Assert\NotBlank(array('message' => 'machine.assert.user')));
        $metadata->addConstraint(new Assert\Callback(array('methods' => array(
            array('DP\Core\MachineBundle\Validator', 'validateNotEmptyPassword'),
            array('DP\Core\MachineBundle\Validator', 'validateCredentials'),  
        ))));
    }
}
