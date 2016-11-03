<?php

namespace Hawk\Plugins\HGitter;

class MergeRequest extends Model{
    protected static $tablename = 'HGitterMergeRequest';

    protected static $primaryColumn = 'id';

    protected static $fields = array(
        'id' => array(
            'type' => 'int(11)',
            'auto_increment' => true
        ),

        'repoId' => array(
            'type' => 'int(11)'
        ),

        'from' => array(
            'type' => 'varchar(256)'
        ),

        'to' => array(
            'type' => 'varchar(256)'
        ),

        'title' => array(
            'type' => 'varchar(1024)'
        ),

        'description' => array(
            'type' => 'text'
        ),

        'ctime' => array(
            'type' => 'int(11)'
        ),

        'mtime' => array(
            'type' => 'int(11)'
        ),

        'userId' => array(
            'type' => 'int(11)'
        ),

        'participants' => array(
            'type' => 'text'
        )
    );

    protected static $constraints = array(
        'repoId' => array(
            'type' => 'foreign',
            'fields' => array(
                'repoId'
            ),
            'references' => array(
                'model' => 'Repo',
                'fields' => array('id')
            ),
            'on_update' => 'CASCADE',
            'on_delete' => 'CASCADE'
        ),
        'userId' => array(
            'type' => 'foreign',
            'fields' => array(
                'userId'
            ),
            'references' => array(
                'model' => '\Hawk\User',
                'fields' => array('id')
            ),
            'on_update' => 'RESTRICT',
            'on_delete' => 'RESTRICT'
        )
    );

    public function prepareDatabaseData() {
        $insert = parent::prepareDatabaseData();

        if(empty($insert['id'])) {
            $insert['ctime'] = time();
            $insert['userId'] = App::session()->getUser()->id;
        }
        $insert['mtime'] = time();

        return $insert;
    }
}