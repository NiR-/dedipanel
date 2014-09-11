<?php

namespace DP\Core\CoreBundle\Behat;

use Behat\Behat\Hook\Scope\BeforeFeatureScope;
use Behat\Gherkin\Node\TableNode;
use DP\GameServer\MinecraftServerBundle\Entity\MinecraftServer;
use DP\GameServer\SteamServerBundle\Entity\SteamServer;
use DP\VoipServer\TeamspeakServerBundle\Entity\TeamspeakServer;

class ServerContext extends DefaultContext
{
    /**
     * @Then /^I should be on the page of ([^""(w)]*) server "([^""]*)"$/
     * @Then /^I should still be on the page of ([^""(w)]*) server "([^""]*)"$/
     */
    public function iShouldBeOnTheResourcePageByName($type, $name)
    {
        $this->iShouldBeOnTheResourcePage($type, 'name', $name);
    }

    /**
     * @Given /^I am on the (teamspeak) "([^""]*)" instance index$/
     */
    public function iAmOnTheInstanceIndex($type, $value)
    {
        $parts   = explode('@', $value);
        $machine = $this->findOneBy('machine', array('username' => $parts[0]));
        $server  = $this->findOneBy($type, array('machine' => $machine->getId()));

        $this->getSession()->visit($this->generatePageUrl(sprintf('dedipanel_%s_instance_index', $type), array('serverId' => $server->getId())));
    }

    /**
     * @Then /^I should be on the (teamspeak) "([^"]*)" instance index$/
     */
    public function iShouldBeOnTheInstanceIndexPage($type, $value)
    {
        $parts   = explode('@', $value);
        $machine = $this->findOneBy('machine', array('username' => $parts[0]));
        $server  = $this->findOneBy($type, array('machine' => $machine->getId()));

        $this->assertSession()->addressEquals($this->generatePageUrl(sprintf('dedipanel_%s_instance_index', $type), array('serverId' => $server->getId())));
        $this->assertStatusCodeEquals(200);
    }

    /**
     * @Given /^I am (building|viewing|editing) (teamspeak) "([^""]*)"$/
     */
    public function iAmDoingSomethingWithVoipServer($action, $type, $value)
    {
        $type = str_replace(' ', '_', $type);

        $action = str_replace(array_keys($this->actions), array_values($this->actions), $action);
        $parts   = explode('@', $value);
        $machine = $this->findOneBy('machine', array('username' => $parts[0]));
        $server  = $this->findOneBy($type, array('machine' => $machine->getId()));

        $this->getSession()->visit($this->generatePageUrl(sprintf('dedipanel_%s_%s', $type, $action), array('id' => $server->getId())));
    }

    /**
     * @Then /^I should be (building|viewing|editing|testing) (teamspeak) "([^""]*)"$/
     */
    public function iShouldBeDoingSomethingWithVoipServer($action, $type, $value)
    {
        $type = str_replace(' ', '_', $type);

        $action  = str_replace(array_keys($this->actions), array_values($this->actions), $action);
        $parts   = explode('@', $value);
        $machine = $this->findOneBy('machine', array('username' => $parts[0]));
        $server  = $this->findOneBy($type, array('machine' => $machine->getId()));

        $this->assertSession()->addressEquals($this->generatePageUrl(sprintf('dedipanel_%s_%s', $type, $action), array('id' => $server->getId())));
        $this->assertStatusCodeEquals(200);
    }

    /**
     * @When /^I (?:click|press|follow) "([^"]*)" near "([^"]*)"$/
     */
    public function iClickNear($button, $value)
    {
        $selector = sprintf('.server-list .server-item:contains("%s")', $value);
        $item = $this->assertSession()->elementExists('css', $selector);

        $locator = sprintf('button:contains("%s")', $button);

        if ($item->has('css', $locator)) {
            $item->find('css', $locator)->press();
        } else {
            $item->clickLink($button);
        }
    }

    /**
     * @Then /^I should see "([^"]*)" near "([^"]*)"$/
     */
    public function iShouldSeeNear($text, $value)
    {
        $selector = sprintf('.server-list .server-item:contains("%s")', $value);

        $this->assertSession()->elementContains('css', $selector, $text);
    }

    /**
     * @Then /^I should not see "([^"]*)" near "([^"]*)"$/
     */
    public function iShouldNotSeeNear($text, $value)
    {
        $selector = sprintf('.server-list .server-item:contains("%s")', $value);

        $this->assertSession()->elementNotContains('css', $selector, $text);
    }

    /**
     * @Then /^I should be on the ftp page of ([^""]*) "([^""]*)"$/
     */
    public function iShouldBeOnTheFtpPage($type, $name)
    {
        $type = str_replace(' ', '_', $type);
        $resource = $this->findOneBy($type, array('name' => $name));

        $this->assertSession()->addressEquals($this->generatePageUrl(sprintf('%s_ftp_show', $type), array('id' => $resource->getId())));
        $this->assertStatusCodeEquals(200);
    }

    /**
     * @Given /^there are following minecraft servers:$/
     */
    public function thereAreMinecraftServers(TableNode $table)
    {
        foreach ($table->getHash() as $data) {
            $this->thereIsMinecraftServer(
                $data['name'],
                $data['machine'],
                $data['port'],
                $data['queryPort'],
                $data['rconPort'],
                $data['rconPassword'],
                $data['game'],
                $data['installDir'],
                $data['maxplayers'],
                $data['minHeap'],
                $data['maxHeap'],
                (isset($data['installed']) && $data['installed'] == 'yes'),
                false
            );
        }

        $this->getEntityManager()->flush();
    }

    public function thereIsMinecraftServer($name, $machine = null, $port = 25565, $queryPort = 25565, $rconPort = 25575, $rconPassword = 'test', $game = 'minecraft', $installDir = 'test', $maxplayers = 2, $minHeap = 128, $maxHeap = 256, $installed = true, $flush = true)
    {
        if (null === $server = $this->getRepository('minecraft')->findOneBy(array('name' => $name))) {
            $game    = $this->thereIsGame($game);
            $machine = $this->thereIsMachine($machine);

            $server = new MinecraftServer();
            $server->setName($name);
            $server->setMachine($machine);
            $server->setPort($port);
            $server->setQueryPort($queryPort);
            $server->setRconPort($rconPort);
            $server->setRconPassword($rconPassword);
            $server->setGame($game);
            $server->setDir($installDir);
            $server->setMaxplayers($maxplayers);
            $server->setMinHeap($minHeap);
            $server->setMaxHeap($maxHeap);

            if ($installed) {
                $server->setInstallationStatus(101);
            }

            $this->validate($server);

            $this->getEntityManager()->persist($server);

            if ($flush) {
                $this->getEntityManager()->flush();
            }
        }

        return $server;
    }

    /**
     * @Given /^there are following steam servers:$/
     */
    public function thereAreSteamServers(TableNode $table)
    {
        foreach ($table->getHash() as $data) {
            $this->thereIsSteamServer(
                $data['name'],
                $data['machine'],
                $data['port'],
                $data['rconPassword'],
                $data['game'],
                $data['installDir'],
                $data['maxplayers'],
                (isset($data['installed']) && $data['installed'] == 'yes'),
                false
            );
        }

        $this->getEntityManager()->flush();
    }

    public function thereIsSteamServer($name, $machine = null, $port = 27025, $rconPassword = 'test', $game = 'Counter-Strike', $installDir = 'test', $maxplayers = 2, $installed = true, $flush = true)
    {
        if (null === $server = $this->getRepository('steam')->findOneBy(array('name' => $name))) {
            $game    = $this->thereIsGame($game);
            $machine = $this->thereIsMachine($machine);

            $server = new SteamServer();
            $server->setName($name);
            $server->setMachine($machine);
            $server->setPort($port);
            $server->setRconPassword($rconPassword);
            $server->setGame($game);
            $server->setDir($installDir);
            $server->setMaxplayers($maxplayers);

            if ($installed) {
                $server->setInstallationStatus(101);
            }

            $this->validate($server);

            $this->getEntityManager()->persist($server);

            if ($flush) {
                $this->getEntityManager()->flush();
            }
        }

        return $server;
    }

    /**
     * @Given /^there are following teamspeak servers:$/
     */
    public function thereAreTeamspeakServers(TableNode $table)
    {
        foreach ($table->getHash() as $data) {
            $this->thereIsTeamspeakServer(
                $data['machine'],
                $data['queryPassword'],
                $data['installDir'],
                (isset($data['installed']) && $data['installed'] == 'yes'),
                false
            );
        }

        $this->getEntityManager()->flush();
    }

    public function thereIsTeamspeakServer($machine, $queryPassword = 'test', $installDir = 'test', $installed = true, $flush = true)
    {
        $machine = $this->thereIsMachine($machine);

        if (null === $server = $this->getRepository('teamspeak')->findOneBy(array('machine' => $machine))) {
            $server = new TeamspeakServer();
            $server->setMachine($machine);
            $server->setQueryPassword($queryPassword);
            $server->setDir($installDir);

            if ($installed) {
                $server->setInstallationStatus(101);
            }

            $this->validate($server);

            $this->getEntityManager()->persist($server);

            if ($flush) {
                $this->getEntityManager()->flush();
            }
        }

        return $server;
    }
}
