<?php

/**
 * This file is part of Dedipanel project
 *
 * (c) 2010-2015 Dedipanel <http://www.dedicated-panel.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DP\GameServer\MinecraftServerBundle\Form;

use DP\Core\GameBundle\Entity\GameRepository;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;

class MinecraftServerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', 'text', array('label' => 'game.name'))
            ->add('port', 'integer', array('label' => 'game.port'))
            ->add('queryPort', 'integer', array('label' => 'minecraft.queryPort'))
            ->add('rconPort', 'integer', array('label' => 'minecraft.rcon.port'))
            ->add('rconPassword', 'text', array('label' => 'game.rcon.password'))
            ->add('maxplayers', 'integer', array('label' => 'game.maxplayers'))
            ->add('minHeap', 'integer', array('label' => 'minecraft.minHeap'))
            ->add('maxHeap', 'integer', array('label' => 'minecraft.maxHeap'))->add('alreadyInstalled', 'choice', array(
                'choices'  => array(1 => 'game.yes', 0 => 'game.no'),
                'label'    => 'game.isAlreadyInstalled',
                'expanded' => true,
            ))
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $form      = $event->getForm();
            /** @var DP\GameServer\MinecraftServerBundle\Entity\MinecraftServer $minecraft */
            $minecraft = $event->getData();

            $isUpdateForm = ($minecraft->getId() != null);

            $form
                ->add('machine', 'dedipanel_machine_entity', array(
                    'disabled' => $isUpdateForm,
                ))
                ->add('game', 'entity', array(
                    'label' => 'game.selectGame',
                    'class' => 'DPGameBundle:Game',
                    'query_builder' => function(GameRepository $repo) {
                            return $repo->getQBAvailableMinecraftGames();
                        },
                    'disabled' => $isUpdateForm,
                ))
                ->add('dir', 'text', array(
                    'label' => 'game.dir',
                    'disabled' => $isUpdateForm,
                ))
            ;

            if ($minecraft->getId() !== null) {
                $form->remove('alreadyInstalled');

                if ($minecraft->getMachine()->getNbCore() != null) {
                    $choices = array_combine(
                        range(0, $minecraft->getMachine()->getNbCore()-1),
                        range(1, $minecraft->getMachine()->getNbCore())
                    );

                    $form->add('core', 'choice', array(
                        'label'    => 'game.core',
                        'choices'  => $choices,
                        'multiple' => true,
                        'required' => false,
                        'expanded' => true,
                    ));
                }
            }
        });
    }

    public function getName()
    {
        return 'dedipanel_minecraft';
    }
}
