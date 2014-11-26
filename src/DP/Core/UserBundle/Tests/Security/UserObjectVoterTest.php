<?php

namespace DP\Core\UserBundle\Tests\Security;

use DP\Core\UserBundle\Security\UserObjectVoter;
use DP\Core\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class UserObjectVoterTest extends \PHPUnit_Framework_TestCase
{
    const SAME = 'same';

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $groupRepo;

    /** @var \DP\Core\UserBundle\Security\UserObjectVoter */
    private $voter;

    /** @var \ReflectionMethod */
    private $reflective;

    public function setUp()
    {
        $this->groupRepo = $this->getMockBuilder('DP\Core\UserBundle\Entity\GroupRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->token = $this->getMock('Symfony\Component\Security\Core\Authentication\TokenInterface');

        $this->voter = new UserObjectVoter($this->groupRepo);

        // Enable the voting method used internally
        // The method vote() pass get_class() result to supportsClass() method as argument,
        // so we need to bypass this way.
        $this->reflective = new \ReflectionMethod($this->voter, 'voting');
        $this->reflective->setAccessible(true);
    }

    public function testSupportsClass()
    {
        $this->assertTrue($this->voter->supportsClass(get_class(new User)));
        $this->assertFalse($this->voter->supportsClass('Foo'));
    }

    public function testSupportsAttribute()
    {
        $this->assertTrue($this->voter->supportsAttribute('ROLE_DP_TEST'));
        $this->assertFalse($this->voter->supportsAttribute('ROLE_FOO_TEST'));
    }

    public function testOnSameObject()
    {
        $user  = $this->getMock('DP\Core\UserBundle\Entity\User');
        $group = $this->getMock('DP\Core\UserBundle\Entity\Group');
        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');

        $user->expects($this->atLeastOnce()) // called 3 times per call to voting() method
            ->method('isSuperAdmin')
            ->will($this->returnValue(false));
        $user->expects($this->atLeastOnce()) // called 3 times per call to voting() method
            ->method('getGroup')
            ->will($this->returnValue($group));
        $token->expects($this->atLeastOnce())
            ->method('getUser')
            ->will($this->returnValue($user));
        $this->groupRepo->expects($this->any())
            ->method('getChildren')
            ->will($this->returnValue([$group]));

        // Can view is own profile
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->reflective->invoke($this->voter, $token, $user, ['ROLE_DP_ADMIN_USER_SHOW']));

        // Can not update/delete himself if not super admin
        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->reflective->invoke($this->voter, $token, $user, ['ROLE_DP_ADMIN_USER_UPDATE']));
        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->reflective->invoke($this->voter, $token, $user, ['ROLE_DP_ADMIN_USER_DELETE']));
    }

    public function testOnSameObjectWhenSuperAdmin()
    {
        $user  = $this->getMock('DP\Core\UserBundle\Entity\User');
        $group = $this->getMock('DP\Core\UserBundle\Entity\Group');
        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');

        $user->expects($this->atLeastOnce()) // called 3 times per call to voting() method
            ->method('isSuperAdmin')
            ->will($this->returnValue(true));
        $user->expects($this->atLeastOnce()) // called 3 times per call to voting() method
            ->method('getGroup')
            ->will($this->returnValue($group));
        $token->expects($this->atLeastOnce())
            ->method('getUser')
            ->will($this->returnValue($user));
        $this->groupRepo->expects($this->any())
            ->method('getChildren')
            ->will($this->returnValue([$group]));

        // Can update/delete himself if super admin
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->reflective->invoke($this->voter, $token, $user, ['ROLE_DP_ADMIN_USER_UPDATE']));
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->reflective->invoke($this->voter, $token, $user, ['ROLE_DP_ADMIN_USER_DELETE']));
    }

    public function testOnOtherObject()
    {
        $user  = $this->getMock('DP\Core\UserBundle\Entity\User');
        $other = $this->getMock('DP\Core\UserBundle\Entity\User');
        $group = $this->getMock('DP\Core\UserBundle\Entity\Group');
        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');

        $user->expects($this->any())
            ->method('isSuperAdmin')
            ->will($this->returnValue(false));
        $user->expects($this->any())
            ->method('getGroup')
            ->will($this->returnValue($group));
        $other->expects($this->any())
            ->method('getGroup')
            ->will($this->returnValue($group));
        $token->expects($this->any())
            ->method('getUser')
            ->will($this->returnValue($user));
        $this->groupRepo->expects($this->any())
            ->method('getChildren')
            ->will($this->returnValue([$group]));

        // Can view, update, delete profile of other users
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->reflective->invoke($this->voter, $token, $other, ['ROLE_DP_ADMIN_USER_SHOW']));
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->reflective->invoke($this->voter, $token, $other, ['ROLE_DP_ADMIN_USER_UPDATE']));
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->reflective->invoke($this->voter, $token, $other, ['ROLE_DP_ADMIN_USER_DELETE']));
    }
}
