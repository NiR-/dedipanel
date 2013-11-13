<?php

namespace DP\Core\UserBundle\Form\Modifier;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;
use Symfony\Component\Security\Core\Role\Role;

class SecurityRolesModifier implements EventSubscriberInterface
{
    private $roleHierarchy;
    private $roles;
    
    public function __construct(RoleHierarchyInterface $roleHierarchy, array $roles)
    {
        $this->roleHierarchy = $roleHierarchy;
        $this->roles = array_keys($roles);
    }
    
    public static function getSubscribedEvents()
    {
        return array(
            // FormEvents::PRE_SET_DATA => 'completeRolesFieldsByDepth',
            FormEvents::SUBMIT       => 'completeEntityRolesByDepth', 
        );
    }
    
    public function completeRolesFieldsByDepth(FormEvent $event)
    {
        $entity = $event->getForm()->getParent()->getData();
        
        $selectedRoles = array();
        $hierarchy = $this->getHierarchy();
        
        foreach ($this->roles AS $role) {            
            if ($entity->hasRole($role)) {
                $selectedRoles = array_merge($selectedRoles, array($role), $hierarchy[$role]);
            }
        }
        
        $event->setData(array_unique($selectedRoles));
    }
    
    public function completeEntityRolesByDepth(FormEvent $event)
    {
        $entity = $event->getForm()->getParent()->getData();
        $hierarchy = $this->getHierarchy();
        $roles = array();
        
        foreach ($entity->getRoles() AS $role) {
            $roles = array_merge($roles, array($role), $hierarchy[$role]);
        }
        
        $event->setData(array_unique($roles));
    }
    
    public function getHierarchy()
    {
        $hierarchy = array();
        
        foreach ($this->roles AS $role) {
            $hierarchy[$role] = array();
            
            foreach ($this->roleHierarchy->getReachableRoles(array(new Role($role))) AS $childRole) {
                $hierarchy[$role][] = $childRole->getRole();
            }
        }
        
        return $hierarchy;
    }
}
