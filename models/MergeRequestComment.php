<?php

namespace Hawk\Plugins\HGitter;

class MergeRequestComment extends Model {
    protected static $tablename = 'HGitterMergeRequestComment';

    protected static $fields = array(
        'id' => array(
            'type' => 'int(11)',
            'auto_increment' => true
        ),

        'mergeRequestId' => array(
            'type' => 'int(11)',
        ),

        'userId' => array(
            'type' => 'int(11)'
        ),

        // The file the comment is applied on
        'file' => array(
            'type' => 'varchar(1024)'
        ),

        // The line number the comment is applied on
        'line' => array(
            'type' => 'int(11)'
        ),

        // Does this comment is a reponse to another one, and if yes, its id
        'parentId' => array(
            'type' => 'int(11)'
        ),

        'comment' => array(
            'type' => 'text'
        ),

        'ctime' => array(
            'type' => 'int(11)'
        )
    );

    protected static $constraints = array(
        'mergeRequestId' => array(
            'type' => 'foreign',
            'fields' => array('mergeRequestId'),
            'references' => array(
                'model' => 'MergeRequest',
                'fields' => array('id')
            ),
            'on_delete' => 'CASCADE',
            'on_update' => 'CASCADE'
        ),

        'userId' => array(
            'type' => 'foreign',
            'fields' => array('userId'),
            'references' => array(
                'model' => 'User',
                'fields' => array('id')
            ),
            'on_delete' => 'RESTRICT',
            'on_update' => 'RESTRICT'
        )
    );

    public function __construct($data = array()) {
        parent::__construct($data);

        $this->parsed = '';
        if(!empty($this->comment)) {
            $parser = new Parsedown();
            $this->parsed = $parser->text($this->comment);
        }
    }

    public function save() {
        if(empty($this->id)) {
            $mr = MergeRequest::getById($this->mergeRequestId);

            // Add the comment author as merge request participant
            $mr->addParticipant($this->userId);

            // TODO : Email the participants a new comment has been written
        }

        return parent::save();
    }
}