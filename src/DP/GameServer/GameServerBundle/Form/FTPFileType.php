<?php

/*
 * (c) 2010-2014 Dedipanel <http://www.dedicated-panel.net>
 *  
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DP\GameServer\GameServerBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class FTPFileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', 'text', array(
                'label' => 'game.ftp.filename', 
            ))
            ->add('content', 'textarea', array(
                'label' => 'game.ftp.content',
                'required' => false,
            ))
        ;
    }
    
    public function getName()
    {
        return 'dedipanel_game_ftp_file';
    }
}
