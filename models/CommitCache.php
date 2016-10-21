<?php

namespace Hawk\Plugins\HGitter;

class CommitCache extends Model {
    protected static $tablename = 'HGitterCommitCache';

    protected static $primaryColumn = 'hash';

    protected static $fields = array(
        'hash' => array(
            'type' => 'varchar(40)'
        ),
        'shortHash' => array(
            'type' => 'varchar(7)'
        ),
        'date' => array(
            'type' => 'int(11)'
        ),
        'message' => array(
            'type' => 'varchar(4096)'
        ),
        'author' => array(
            'type' => 'varchar(64)'
        ),
        'authorEmail' => array(
            'type' => 'varchar(256)'
        ),
        'userId' => array(
            'type' => 'int(11)'
        ),
        'tag' => array(
            'type' => 'varchar(64)'
        ),
        'parent' => array(
            'type' => 'varchar(40)'
        )
    );

    protected static $constraints = array(
        'shortHash' => array(
            'type' => 'index',
            'fields' => array(
                'shortHash'
            )
        )
    );
}