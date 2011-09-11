<?php

/**
 * BaseGroup
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 * 
 * @property integer $id
 * @property string $nom
 * @property Doctrine_Collection $GroupUsers
 * @property Doctrine_Collection $GroupVms
 * 
 * @package    ##PACKAGE##
 * @subpackage ##SUBPACKAGE##
 * @author     ##NAME## <##EMAIL##>
 * @version    SVN: $Id: Builder.php 7490 2010-03-29 19:53:27Z jwage $
 */
abstract class BaseGroup extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->setTableName('groups');
        $this->hasColumn('id', 'integer', null, array(
             'type' => 'integer',
             'primary' => true,
             'autoincrement' => true,
             ));
        $this->hasColumn('nom', 'string', 15, array(
             'type' => 'string',
             'notnull' => true,
             'length' => '15',
             ));
    }

    public function setUp()
    {
        parent::setUp();
        $this->hasMany('GroupUser as GroupUsers', array(
             'local' => 'id',
             'foreign' => 'group_id'));

        $this->hasMany('GroupVm as GroupVms', array(
             'local' => 'id',
             'foreign' => 'group_id'));
    }
}