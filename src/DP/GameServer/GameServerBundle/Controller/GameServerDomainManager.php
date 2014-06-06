<?php

namespace DP\GameServer\GameServerBundle\Controller;

use Dedipanel\PHPSeclibWrapperBundle\Connection\Exception\ConnectionErrorException;
use DP\Core\CoreBundle\Controller\Server\ServerDomainManager;
use DP\GameServer\GameServerBundle\Entity\GameServer;
use Sylius\Bundle\ResourceBundle\Event\ResourceEvent;
use DP\Core\CoreBundle\Exception\MissingPacketException;
use DP\Core\CoreBundle\Exception\NotImplementedException;
use DP\Core\GameBundle\Entity\Plugin;

class GameServerDomainManager extends ServerDomainManager
{
    /**
     * Install a game server
     *
     * @param GameServer $server
     * @return GameServer|null
     */
    public function getInstallationProgress(GameServer $server)
    {
        /** @var ResourceEvent $event */
        $event = $this->dispatchEvent('pre_fetch_install_progress', new ResourceEvent($server));

        if ($event->isStopped()) {
            $this->flashHelper->setFlash(
                $event->getMessageType(),
                $event->getMessage(),
                $event->getMessageParameters()
            );

            return null;
        }

        try {
            $status = $server->getInstallationStatus();

            if ($status < 100 && $status !== null) {
                $status = $server->getInstallationProgress();
                $server->setInstallationStatus($status);
            }
        }
        catch (ConnectionErrorException $e) {
            $this->flashHelper->setFlash(
                ResourceEvent::TYPE_ERROR,
                'dedipanel.machine.connection_failed'
            );

            return null;
        }

        $this->manager->persist($server);
        $this->dispatchEvent('post_fetch_install_progress', $event);
        $this->manager->flush();

        return $server;
    }

    /**
     * Regenerate configuration files on $server
     *
     * @param GameServer $server
     * @return GameServer|null
     */
    public function regenerateConfig(GameServer $server)
    {
        /** @var ResourceEvent $event */
        $event = $this->dispatchEvent('pre_regen', new ResourceEvent($server));

        if ($event->isStopped()) {
            $this->flashHelper->setFlash(
                $event->getMessageType(),
                $event->getMessage(),
                $event->getMessageParameters()
            );

            return null;
        }

        try {
            $server->regenerateScripts($this->templating);
            $this->dispatchEvent('regen', $event);
        }
        catch (ConnectionErrorException $e) {
            $this->flashHelper->setFlash(
                ResourceEvent::TYPE_ERROR,
                'dedipanel.machine.connection_failed'
            );

            return null;
        }

        $this->flashHelper->setFlash(ResourceEvent::TYPE_SUCCESS, 'dedipanel.flashes.config_regenerated');

        $this->dispatchEvent('post_regen', $event);

        return $server;
    }

    /**
     * Install a $plugin on $server
     *
     * @param GameServer $server
     * @param Plugin $plugin
     * @return GameServer|null
     */
    public function installPlugin(GameServer $server, Plugin $plugin)
    {
        /** @var ResourceEvent $event */
        $event = $this->dispatchEvent('pre_install_plugin', new ResourceEvent($server, array('plugin' => $plugin)));

        if ($event->isStopped()) {
            $this->flashHelper->setFlash(
                $event->getMessageType(),
                $event->getMessage(),
                $event->getMessageParameters()
            );

            return null;
        }

        try {
            $server->installPlugin($this->templating, $plugin);
            $server->addPlugin($plugin);
        }
        catch (MissingPacketException $e) {
            $this->flashHelper->setFlash(
                ResourceEvent::TYPE_ERROR,
                'dedipanel.game.missingPacket',
                array('%plugin%' => strval($plugin), '%packet%' => $e->getPackets())
            );

            return null;
        }
        catch (NotImplementedException $e) {
            $this->flashHelper->setFlash(
                ResourceEvent::TYPE_ERROR,
                'dedipanel.game.cant_install_plugin'
            );

            return null;
        }
        catch (ConnectionErrorException $e) {
            $this->flashHelper->setFlash(
                ResourceEvent::TYPE_ERROR,
                'dedipanel.machine.connection_failed'
            );

            return null;
        }

        $this->manager->persist($server);
        $this->manager->flush();

        $this->flashHelper->setFlash(ResourceEvent::TYPE_SUCCESS, 'dedipanel.flashes.install_plugin');

        $this->dispatchEvent('post_install_plugin', $event);

        return $server;
    }

    /**
     * Uninstall a $plugin from the $server
     *
     * @param GameServer $server
     * @param Plugin $plugin
     * @return GameServer|null
     */
    public function uninstallPlugin(GameServer $server, Plugin $plugin)
    {
        /** @var ResourceEvent $event */
        $event = $this->dispatchEvent('pre_uninstall_plugin', new ResourceEvent($server, array('plugin' => $plugin)));

        if ($event->isStopped()) {
            $this->flashHelper->setFlash(
                $event->getMessageType(),
                $event->getMessage(),
                $event->getMessageParameters()
            );

            return null;
        }

        try {
            $server->uninstallPlugin($this->templating, $plugin);
            $server->removePlugin($plugin);
        }
        catch (ConnectionErrorException $e) {
            $this->flashHelper->setFlash(
                ResourceEvent::TYPE_ERROR,
                'dedipanel.machine.connection_failed'
            );

            return null;
        }

        $this->manager->persist($server);
        $this->manager->flush();

        $this->flashHelper->setFlash(ResourceEvent::TYPE_SUCCESS, 'dedipanel.flashes.uninstall_plugin');

        $this->dispatchEvent('post_uninstall_plugin', $event);

        return $server;
    }

    /**
     * Fetch $server logs
     *
     * @param GameServer $server
     * @return array|string
     */
    public function getServerLogs(GameServer $server)
    {
        /** @var ResourceEvent $event */
        $event = $this->dispatchEvent('pre_fetch_logs', new ResourceEvent($server));

        if ($event->isStopped()) {
            $this->flashHelper->setFlash(
                $event->getMessageType(),
                $event->getMessage(),
                $event->getMessageParameters()
            );

            return null;
        }

        $logs = array();

        try {
            if ($server->isInstallationEnded()) {
                $logs = $server->getServerLogs();
            }
            else {
                $logs = $server->getInstallLogs();
            }
        }
        catch (ConnectionErrorException $e) {
            $this->flashHelper->setFlash(
                ResourceEvent::TYPE_ERROR,
                'dedipanel.machine.connection_failed'
            );

            return null;
        }

        /** @var ResourceEvent $event */
        $event = $this->dispatchEvent('post_fetch_logs', new ResourceEvent($server, array('logs' => $logs)));

        if ($event->isStopped()) {
            $this->flashHelper->setFlash(
                $event->getMessageType(),
                $event->getMessage(),
                $event->getMessageParameters()
            );

            return null;
        }
        elseif ($logs === null) {
            $this->flashHelper->setFlash(ResourceEvent::TYPE_ERROR, 'dedipanel.game.cantGetLog');

            return null;
        }

        return $logs;
    }

    /**
     * Verify if the server is callable by the query
     *
     * @param GameServer $server
     * @return bool
     */
    public function isAccessibleFromQuery(GameServer $server)
    {
        $online = $server->getQuery()->isOnline();
        $banned = $server->getQuery()->isBanned();

        if (!$online) {
            $this->flashHelper->setFlash(
                ResourceEvent::TYPE_WARNING,
                'dedipanel.game.server_offline'
            );
        }
        elseif ($banned) {
            $this->flashHelper->setFlash(
                ResourceEvent::TYPE_WARNING,
                'dedipanel.game.banned_from_server'
            );
        }

        return $online && !$banned;
    }

    /**
     * Execute a rcon command against the $server
     * and return the result
     *
     * @param GameServer $server
     * @param $cmd
     * @return string
     */
    public function executeRconCmd(GameServer $server, $cmd)
    {
        /** @var ResourceEvent $event */
        $event = $this->dispatchEvent('pre_rcon_cmd', new ResourceEvent($server));

        // Exécution de la commande
        $ret = $server->getRcon()->sendCmd($cmd);

        $this->dispatchEvent('post_rcon_cmd', $event);

        return $ret;
    }
}
