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
        ),

        'merged' => array(
            'type' => 'tinyint(1)',
            'default' => 0
        ),

        'scores' => array(
            'type' => 'varchar(1024)',
            'default' => '{}'
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

    /**
     * Constructor
     * @param array $data The initial data
     */
    public function __construct($data = array()) {
        parent::__construct($data);

        if(empty($this->participants)) {
            $this->participants = array();
        }
        elseif(is_string($this->participants)) {
            $this->participants = json_decode($this->participants, true);
        }

        if(empty($this->userId)) {
            $this->userId = App::session()->getUser()->id;
        }
        $this->addParticipant($this->userId);
    }

    public function prepareDatabaseData() {
        $insert = parent::prepareDatabaseData();

        if(empty($insert['id'])) {
            $insert['ctime'] = time();
            $insert['userId'] = App::session()->getUser()->id;
        }
        $insert['mtime'] = time();

        if(!empty($this->participants)) {
            $this->participants = array_unique($this->participants);
            $insert['participants'] = json_encode($this->participants);
        }

        return $insert;
    }


    /**
     * Defines if the merge request is in the status "Work in progress". In this case, the merge request can't be accepted
     * @return boolean
     */
    public function isWip() {
        return preg_match('/^WIP\s*\:/', $this->title);
    }

    /**
     * Defines if the merge requests presents conflicts
     * @return boolean
     */
    public function hasConflicts() {
        if(!isset($this->hasConflicts)) {
            $repo = Repo::getById($this->repoId);
            $currentBranch = $repo->getActiveBranch();
            $conflicts = false;
            if($currentBranch !== $this->to) {
                $repo->checkout($this->to);
            }

            try {
                $repo->run('merge --no-commit --no-ff ' . $this->from);
            }
            catch(GitException $e) {
                $conflicts = true;
            }

            $repo->run('merge --abort');

            if($currentBranch !== $this->to) {
                $repo->checkout($currentBranch);
            }


            $this->hasConflicts = $conflicts;
        }

        return $this->hasConflicts;
    }

    /**
     * Defines if the merge request can be accepted, and merged
     * @return boolean
     */
    public function isAcceptable() {
        return !$this->isWip() && !$this->hasConflicts();

    }

    /**
     * Get the comments of the merge request
     * @return array The list of comments
     */
    public function getComments() {
        if(!isset($this->comments)) {
            $this->comments = MergeRequestComment::getListByExample(
                new DBExample(array(
                    'mergeRequestId' => $this->id
                )),
                null,
                array(),
                array(
                    'parentId' => DB::SORT_ASC,
                    'ctime' => DB::SORT_ASC
                )
            );
        }

        return $this->comments;
    }

    /**
     * Return all merge request comments, sorted by discussion
     * @return array The list of all discussions, each element contaning the disucssion comments,
     * sorted by incresing creation time
     */
    public function getDiscussions() {
        $comments = $this->getComments();
        $discussions = array();

        foreach($comments as $comment) {
            if(!$comment->parentId) {
                $discussions[$comment->id] = array();
                $discussions[$comment->id][] = $comment;
            }
            else {
                $discussions[$comment->parentId][] = $comment;
            }
        }

        return $discussions;
    }

    public function addParticipant($userId) {
        if(!in_array((int) $userId, $this->participants)) {
            $this->participants[] = (int) $userId;
        }
    }

    /**
     * Add a comment on the merge request
     * @param array $data The comment data
     */
    public function addComment($content, $file = '', $line= 0, $parentId = 0) {
        $comment = new MergeRequestComment(array(
            'mergeRequestId' => $this->id,
            'userId' => App::session()->getUser()->id,
            'file' => $file,
            'line' => $line,
            'comment' => $content,
            'parentId' => $parentId,
            'ctime' => time()
        ));

        $comment->save();
    }


    /**
     * Get the users that participate to this merge request
     * @return array The merge request participants
     */
    public function getParticipants() {
        return User::getListByExample(new DBExample(array(
            'id' => array(
                '$in' => $this->participants
            )
        )));
    }
}