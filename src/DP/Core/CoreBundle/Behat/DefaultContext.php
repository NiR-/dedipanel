<?php

namespace DP\Core\CoreBundle\Behat;

use Sylius\Bundle\ResourceBundle\Behat\DefaultContext as BaseDefaultContext;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Exception\ElementNotFoundException;

class DefaultContext extends BaseDefaultContext
{
    protected $users = [];

    /** {@inheritdoc} */
    protected function generatePageUrl($page, array $parameters = array())
    {
        if (is_object($page)) {
            return $this->locatePath($this->generateUrl($page, $parameters));
        }

        $route  = str_replace(' ', '_', trim($page));
        $routes = $this->getContainer()->get('router')->getRouteCollection();

        if (null === $routes->get($route)) {
            $route = 'dedipanel_'.$route;
        }

        $route = str_replace(array_keys($this->actions), array_values($this->actions), $route);
        $route = str_replace(' ', '_', $route);

        return $this->locatePath($this->generateUrl($route, $parameters));
    }

    protected function getRepository($resource)
    {
        return $this->getService('dedipanel.repository.'.$resource);
    }

    public function thereIsUser($username, $email, $password, $role = null, $enabled = true, $group = null, $flush = true)
    {
        if (null === $user = $this->getRepository('user')->findOneBy(array('username' => $username))) {
            /* @var $user FOS\UserBundle\Model\UserInterface */
            $user = $this->getRepository('user')->createNew();
            $user->setUsername($username);
            $user->setEmail($email);
            $user->setEnabled($enabled);
            $user->setPlainPassword($password);
            $user->setPassword(''); // Set empty hashed password for validation

            if (null !== $role) {
                $user->addRole($role);
            }

            if ($group !== null) {
                $group = $this->thereIsGroup($group);
                $user->addGroup($group);
            }

            $this->validate($user);

            $this->getEntityManager()->persist($user);

            if ($flush) {
                $this->getEntityManager()->flush();
            }

            $this->users[$username] = $password;
        }

        return $user;
    }

    public function thereIsGame($name, $installName, $launchName, $bin, $type, $available = true, $flush = true)
    {
        if (null === $game = $this->getRepository('game')->findOneBy(array('name' => $name))) {
            $game = $this->getRepository('game')->createNew();
            $game->setName($name);
            $game->setInstallName($installName);
            $game->setLaunchName($launchName);
            $game->setBin($bin);
            $game->setType($type);
            $game->setAvailable($available);

            $this->validate($game);

            $this->getEntityManager()->persist($game);

            if ($flush) {
                $this->getEntityManager()->flush();
            }
        }

        return $game;
    }

    /**
     * @Given /^I am on the (.+) (page)?$/
     * @When /^I go to the (.+) (page)?$/
     */
    public function iAmOnThePage($page)
    {
        $this->getSession()->visit($this->generatePageUrl($page));
    }

    /**
     * @Then /^I should be on the (.+) (page)$/
     * @Then /^I should be redirected to the (.+) (page)$/
     * @Then /^I should still be on the (.+) (page)$/
     */
    public function iShouldBeOnThePage($page)
    {
        $this->assertSession()->addressEquals($this->generatePageUrl($page));
        $this->assertStatusCodeEquals(200);
    }

    /**
     * @Then /^I should be unauthorized on the (.+) (page)$/
     */
    public function iShouldBeUnauthorizedOnThePage($page)
    {
        $this->assertSession()->addressEquals($this->generatePageUrl($page));
        $this->assertStatusCodeEquals(403);
    }

    /**
     * @Given /^I leave "([^"]*)" empty$/
     * @Given /^I leave "([^"]*)" field blank/
     */
    public function iLeaveFieldEmpty($field)
    {
        $this->fillField($field, '');
    }

    /**
     * @Then /^I should see (\d+) validation errors?$/
     */
    public function iShouldSeeFieldsOnError($amount)
    {
        $this->iShouldSeeAlertMessage($amount, 'error');
    }

    /**
     * @Then /^I should see (\d+) (?:alert )?((error|success) )?message$/
     */
    public function iShouldSeeAlertMessage($amount, $type = '')
    {
        $class = '.alert' . (!empty($type) ? '-' . $type : '');

        $this->assertSession()->elementsCount('css', $class . ' > ul > li', $amount);
    }

    /**
     * @Given /^there are following users:$/
     */
    public function thereAreFollowingUsers(TableNode $table)
    {
        foreach ($table->getHash() as $data) {
            $this->thereIsUser(
                $data['username'],
                $data['email'],
                $data['password'],
                isset($data['role']) ? $data['role'] : 'ROLE_USER',
                isset($data['enabled']) ? $data['enabled'] : true,
                isset($data['group']) && !empty($data['group']) ? $data['group'] : null,
                false
            );
        }

        $this->getEntityManager()->flush();
    }

    /**
     * @Given /^I am logged in with (.*) account$/
     */
    public function iAmLoggedInWithAccount($username)
    {
        if (!isset($this->users[$username])) {
            throw new \RuntimeException('Given user ("' . $username . '") does not exists.');
        }

        $this->getSession()->visit($this->generatePageUrl('fos_user_security_login'));

        $this->fillField("Nom d'utilisateur", $username);
        $this->fillField('Mot de passe', $this->users[$username]);
        $this->pressButton('Connexion');
    }

    /**
     * @Given /^there are following games:$/
     */
    public function thereAreFollowingGames(TableNode $table)
    {
        foreach ($table->getHash() as $data) {
            $this->thereIsGame(
                $data['name'],
                $data['installName'],
                isset($data['launchName']) ? $data['launchName'] : $data['installName'],
                $data['bin'],
                $data['type'],
                isset($data['available']) ? $data['available'] : true,
                false
            );
        }

        $this->getEntityManager()->flush();
    }

    /**
     * @When /^I fill in (.+) form with:$/
     */
    public function whenIFillInFormWith($base, TableNode $table)
    {
        $page = $this->getSession()->getPage();

        foreach ($table->getTable() AS $data) {
            list($name, $value) = $data;
            $field     = $this->findField($base, $name);
            $fieldName = $field->getAttribute('name');

            if ($field->getTagName() == 'select') {
                $this->selectOption($fieldName, $value);

                continue;
            }

            if ($field->getAttribute('type') == 'checkbox') {
                if ($value == 'yes') {
                    $page->checkField($fieldName);
                }
                elseif ($value == 'no') {
                    $page->uncheckField($fieldName);
                }
                else {
                    throw new \RuntimeException(sprintf('Unsupported value "%s" for the checkbox field "%s"', $value, $fieldName));
                }

                continue;
            }

            $this->fillField($fieldName, $value);
        }
    }

    /**
     * @Then /^I should see [\w\s]+ with [\w\s]+ "([^""]*)" in (that|the) list$/
     */
    public function iShouldSeeResourceWithValueInThatList($value)
    {
        $this->assertSession()->elementTextContains('css', 'table', $value);
    }

    /**
     * @Then /^I should not see [\w\s]+ with [\w\s]+ "([^""]*)" in (that|the) list$/
     */
    public function iShouldNotSeeResourceWithValueInThatList($value)
    {
        $this->assertSession()->elementTextNotContains('css', 'table', $value);
    }

    /**
     * @Then /^I should see (\d+) ([^""]*) in (that|the) list$/
     */
    public function iShouldSeeThatMuchResourcesInTheList($amount, $type)
    {
        if (1 === count($this->getSession()->getPage()->findAll('css', 'table'))) {
            $this->assertSession()->elementsCount('css', 'table tbody > tr', $amount);
        } else {
            $this->assertSession()->elementsCount('css', sprintf('table#%s tbody > tr', str_replace(' ', '-', $type)), $amount);
        }
    }

    /**
     * @Then /^I should be on the page of ([^""]*) with ([^""]*) "([^""]*)"$/
     * @Then /^I should still be on the page of ([^""]*) with ([^""]*) "([^""]*)"$/
     */
    public function iShouldBeOnTheResourcePage($type, $property, $value)
    {
        $type = str_replace(' ', '_', $type);
        $resource = $this->findOneBy($type, array($property => $value));

        $this->assertSession()->addressEquals($this->generatePageUrl(sprintf('%s_show', $type), array('id' => $resource->getId())));
        $this->assertStatusCodeEquals(200);
    }

    /**
     * @Then /^I should be on the page of ([^""(w)]*) "([^""]*)"$/
     * @Then /^I should still be on the page of ([^""(w)]*) "([^""]*)"$/
     */
    public function iShouldBeOnTheResourcePageByName($type, $name)
    {
        $this->iShouldBeOnTheResourcePage($type, 'name', $name);
    }

    /**
     * @Given /^I am on the page of ([^""]*) with ([^""]*) "([^""]*)"$/
     * @Given /^I go to the page of ([^""]*) with ([^""]*) "([^""]*)"$/
     */
    public function iAmOnTheResourcePage($type, $property, $value)
    {
        $type = str_replace(' ', '_', $type);

        $resource = $this->findOneBy($type, array($property => $value));

        $this->getSession()->visit($this->generatePageUrl(sprintf('%s_show', $type), array('id' => $resource->getId())));
    }

    /**
     * @Given /^I am on the page of ([^""(w)]*) "([^""]*)"$/
     * @Given /^I go to the page of ([^""(w)]*) "([^""]*)"$/
     */
    public function iAmOnTheResourcePageByName($type, $name)
    {
        $this->iAmOnTheResourcePage($type, 'name', $name);
    }

    /**
     * @Given /^I am (building|viewing|editing) ([^""]*) with ([^""]*) "([^""]*)"$/
     */
    public function iAmDoingSomethingWithResource($action, $type, $property, $value)
    {
        $type = str_replace(' ', '_', $type);

        $action = str_replace(array_keys($this->actions), array_values($this->actions), $action);
        $resource = $this->findOneBy($type, array($property => $value));

        $this->getSession()->visit($this->generatePageUrl(sprintf('%s_%s', $type, $action), array('id' => $resource->getId())));
    }

    /**
     * @Given /^I am (building|viewing|editing) ([^""(w)]*) "([^""]*)"$/
     */
    public function iAmDoingSomethingWithResourceByName($action, $type, $name)
    {
        $this->iAmDoingSomethingWithResource($action, $type, 'name', $name);
    }

    /**
     * @Then /^I should be (building|viewing|editing) ([^"]*) with ([^"]*) "([^""]*)"$/
     */
    public function iShouldBeDoingSomethingWithResource($action, $type, $property, $value)
    {
        $type = str_replace(' ', '_', $type);

        $action = str_replace(array_keys($this->actions), array_values($this->actions), $action);
        $resource = $this->findOneBy($type, array($property => $value));

        $this->assertSession()->addressEquals($this->generatePageUrl(sprintf('dedipanel_%s_%s', $type, $action), array('id' => $resource->getId())));
        $this->assertStatusCodeEquals(200);
    }

    /**
     * @Then /^I should be (building|viewing|editing) ([^""(w)]*) "([^""]*)"$/
     */
    public function iShouldBeDoingSomethingWithResourceByName($action, $type, $name)
    {
        $this->iShouldBeDoingSomethingWithResource($action, $type, 'name', $name);
    }

    /**
     * @When /^I (?:click|press|follow) "([^"]*)" near "([^"]*)"$/
     */
    public function iClickNear($button, $value)
    {
        $tr = $this->assertSession()->elementExists('css', sprintf('table tbody tr:contains("%s")', $value));

        $locator = sprintf('button:contains("%s")', $button);

        if ($tr->has('css', $locator)) {
            $tr->find('css', $locator)->press();
        } else {
            $tr->clickLink($button);
        }
    }

    /**
     * Assert that given code equals the current one.
     *
     * @param integer $code
     */
    protected function assertStatusCodeEquals($code)
    {
        $this->assertSession()->statusCodeEquals($code);
    }

    /**
     * @Given /^there are following plugins:$/
     */
    public function thereAreFollowingPlugins(TableNode $table)
    {
        foreach ($table->getHash() as $data) {
            $this->thereIsPlugin(
                $data['name'],
                $data['version'],
                $data['scriptName'],
                'http://' . $data['downloadUrl'],
                false
            );
        }

        $this->getEntityManager()->flush();
    }

    public function thereIsPlugin($name, $version, $scriptName, $downloadUrl, $flush = true)
    {
        if (null === $plugin = $this->getRepository('plugin')->findOneBy(array('name' => $name))) {
            $plugin = $this->getRepository('plugin')->createNew();
            $plugin->setName($name);
            $plugin->setVersion($version);
            $plugin->setScriptName($scriptName);
            $plugin->setDownloadUrl($downloadUrl);

            $this->validate($plugin);

            $this->getEntityManager()->persist($plugin);

            if ($flush) {
                $this->getEntityManager()->flush();
            }
        }

        return $plugin;
    }

    /**
     * @Then /^I should see (\d+) associated games?$/
     */
    public function iShouldSeeAssociatedGames($amount)
    {
        $this->assertSession()->elementsCount('css', 'ul.associated-games > li', $amount);
    }

    /**
     * @Given /^there are following groups:$/
     */
    public function thereAreFollowingGroups(TableNode $table)
    {
        foreach ($table->getHash() as $data) {
            $this->thereIsGroup(
                $data['name'],
                isset($data['roles']) ? array_map('trim', explode(',', $data['roles'])) : array(),
                !empty($data['parent']) ? $data['parent'] : null
            );
        }

        $this->getEntityManager()->flush();
    }

    public function thereIsGroup($name, array $roles = array(), $parent = null, $flush = true)
    {
        if (null === $group = $this->getRepository('group')->findOneBy(array('name' => $name))) {
            /* @var $group UserInterface */
            $group = $this->getRepository('group')->createNew();
            $group->setName($name);
            $group->setRoles($roles);

            if ($parent !== null) {
                $parent = $this->thereIsGroup($parent);
                $group->setParent($parent);
                $parent->addChildren($group);

                $this->getEntityManager()->persist($parent);
            }

            $this->validate($group);

            $this->getEntityManager()->persist($group);

            if ($flush) {
                $this->getEntityManager()->flush();
            }
        }

        return $group;
    }

    protected function validate($data)
    {
        $violationList = $this->getService('validator')->validate($data);

        if ($violationList->count() != 0) {
            throw new \RuntimeException(sprintf('Data not valid (%s).', $violationList));
        }
    }

    /**
     * @Then /^I should see (\d+) buttons? "([^"]+)"$/
     */
    public function iShouldSeeButton($count, $value)
    {
        $locator = sprintf('a:contains("%s"), button:contains("%s")', $value, $value);

        $this->assertSession()->elementsCount('css', $locator, $count);
    }

    /**
     * @Then /^I should not see button "([^"]+)"$/
     */
    public function iShouldNotSeeButton($value)
    {
        $locator = sprintf('a:contains("%s"), button:contains("%s")', $value, $value);

        $this->assertSession()->elementsCount('css', $locator, 0);
    }

    /**
     * @Then /^I should be on 403 page$/
     * @Then /^I should be on 403 page with "([^"]+)"$/
     */
    public function iShouldBeOn403($message = "Vous n'avez pas accès à cette page.")
    {
        $this->assertStatusCodeEquals(403);

        $this->iShouldSeeAlertMessage(1, 'error');
        $this->assertSession()->pageTextContains($message);
    }

    /**
     * @Then /^I should see (\d+) "([^"]+)" checkbox(?:es)? in "([^"]+)" form$/
     */
    public function iShouldSeeCheckboxes($count, $type, $form)
    {
        $locator = sprintf('//input[@type="checkbox"][@name="%s[%s][]"]', $form, $type);

        $this->assertSession()->elementscount('xpath', $locator, $count);
    }

    /**
     * @Then /^I should see (\d+) "([^"]+)" options in "([^"]+)" form$/
     */
    public function iShouldSeeOptionsInSelect($count, $type, $form)
    {
        $xpath   = sprintf('%s[%s]', $form, $type);
        $locator = sprintf('//select[@name="%s" or @name="%s[]"]/option', $xpath, $xpath);

        $this->assertSession()->elementsCount('xpath', $locator, $count);
    }

    /**
     * @Given /^there are following machines:$/
     */
    public function thereAreFollowingMachines(TableNode $table)
    {
        foreach ($table->getHash() as $data) {
            $this->thereIsMachine(
                $data['privateIp'],
                $data['username'],
                $data['key'],
                $data['group'],
                false
            );
        }

        $this->getEntityManager()->flush();
    }

    public function thereIsMachine($privateIp, $user, $privateKey, $group = null, $flush = true)
    {
        if (null === $machine = $this->getRepository('machine')->findOneBy(array('ip' => $privateIp, 'username' => $user))) {
            $machine = $this->getRepository('machine')->createNew();
            $machine->setIp($privateIp);
            $machine->setUsername($user);
            $machine->setPrivateKeyName($privateKey);

            if ($group !== null) {
                $group = $this->thereIsGroup($group);
                $machine->addGroup($group);
            }

            $this->getEntityManager()->persist($machine);

            if ($flush) {
                $this->getEntityManager()->flush();
            }
        }

        return $machine;
    }

    public function findField($form, $fieldName)
    {
        $page = $this->getSession()->getPage();
        $fieldName = sprintf('%s[%s]', $form, $fieldName);

        if ((null === $field = $page->findField($fieldName)) && (null === $field = $page->findField($fieldName . '[]'))) {
            throw new ElementNotFoundException(sprintf('Form field with id|name|label|value "%s" or "%s[]" not found.', $fieldName, $fieldName));
        }

        return $field;
    }
}
