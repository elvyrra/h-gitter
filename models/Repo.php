<?php

/**
 * Repo.php
 *
 * @author Elvyrra SAS
 */

namespace Hawk\Plugins\HGitter;


class Repo extends Model {
    protected static $tablename = 'HGitterRepo';

    protected static $primaryColumn = 'id';

    protected static $fields = array(
        'id' => array(
            'type' => 'int(11)',
            'auto_increment' => true
        ),

        'projectId' => array(
            'type' => 'int(11)',
        ),

        'name' => array(
            'type' => 'varchar(64)'
        ),

        'userId' => array(
            'type' => 'int(11)',
        ),

        'ctime' => array(
            'type' => 'int(11)'
        ),

        'mtime' => array(
            'type' => 'int(11)'
        ),

        'description' => array(
            'type' => 'varchar(4096)'
        ),

        'path' => array(
            'type' => 'varchar(192)'
        ),

        'masters' => array(
            'type' => 'text'
        ),

        'defaultBranch' => array(
            'type' => 'varchar(256)',
            'default' => 'master'
        )
    );

    protected static $constraints = array(
        'name' => array(
            'type' => 'unique',
            'fields' => array(
                'projectId',
                'name'
            )
        ),
        'path' => array(
            'type' => 'unique',
            'fields' => array(
                'path'
            )
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
        ),
        'projectId' => array(
            'type' => 'foreign',
            'fields' => array(
                'projectId'
            ),
            'references' => array(
                'model' => 'Project',
                'fields' => array('id')
            ),
            'on_update' => 'CASCADE',
            'on_delete' => 'CASCADE'
        )
    );

    private $gitRepo;

    /**
     * Constructor
     * @param array $data The initial project data
     */
    public function __construct($data = array()) {
        parent::__construct($data);

        $this->decodedMasters = array();

        if(!empty($this->masters)) {
            $this->decodedMasters = json_decode($this->masters, true);
        }
    }

    /**
     * Check if the repository is visible for the user
     * @param  User  $user The user
     * @return boolean
     */
    public function isVisible($user = null) {
        if(!$user) {
            $user = App::session()->getUser();
        }

        if($this->userId === $user->id) {
            return true;
        }

        $project = $this->getProject();

        if($project && $project->isVisible($user)) {
            return true;
        }

        return false;
    }

    /**
     * Check if a user has master privilges on the repository
     * @param  User  $user The user
     * @return boolean
     */
    public function isUserMaster($user = null) {
        if(!$user) {
            $user = App::session()->getUser();
        }

        if($this->userId === $user->id) {
            return true;
        }

        $project = $this->getProject();

        if($project->isUserMaster($user)) {
            return true;
        }

        if(in_array($user->id, $this->decodedMasters)) {
            return true;
        }

        return false;
    }

    /**
     * Prepare the repository to be inserted in the database
     * @return array
     */
    public function prepareDatabaseData() {
        $insert = parent::prepareDatabaseData();

        $insert['mtime'] = time();

        return $insert;
    }

    /**
     * Get the users that can access the repository
     * @return array
     */
    public function getUsers() {
        $project = $this->getProject();

        return $project->getUsers();
    }

    /**
     * Get the SSH URL to access the Git repository
     * @return string
     */
    public function getSshUrl() {
        return get_current_user() . '@' . getenv('HTTP_HOST') . ':' . $this->path;
    }


    /**
     * Get the project containing the repository
     * @return Project
     */
    public function getProject() {
        return Project::getById($this->projectId);
    }

    /**
     * Get the git repository of this repository
     * @return Git
     */
    public function getGitRepo() {
        if(!$this->gitRepo) {
            $this->gitRepo =  Git::open($this->path);
        }

        return $this->gitRepo;
    }


    /**
     * Get information about a commit. This method caches the commit informations in database to increase treatments
     * @param   string      $hash     The commit hash on 40 characters
     * @param   bool        $useCache If set to true (default), try to get information from the cache, and store the information in the cache
     * @return  CommitCache           The commit information
     */
    public function getCommitInformation($hash, $useCache = true) {
        if($useCache) {
            $cache = CommitCache::getById($hash);

            if($cache) {
                $cache->user = User::getById($cache->userId);

                return $cache;
            }
        }

        $info = $this->run('log --pretty="format:%H%n%h%n%s%n%ct%n%an%n%ae%n%P" -n 1 ' . $hash . ' --');
        list($longHash, $shortHash, $message, $date, $author, $email, $parents) = array_map('trim', explode(PHP_EOL, $info));

        $user = User::getByExample(new DBExample(array(
            '$or' => array(
                array(
                    'username' => $author
                ),
                array(
                    'email' => $email
                )
            )
        )));

        try {
            $tag = trim($this->run('describe --tags ' . $hash));
        }
        catch(GitException $e) {
            $tag = '';
        }

        $cache = new CommitCache(array(
            'hash' => $longHash,
            'shortHash' => $shortHash,
            'date' => $date,
            'message' => $message,
            'author' => $user ? $user->username : $author,
            'authorEmail' => $user ? $user->email : $email,
            'userId' => $user ? $user->id : 0,
            'tag' => $tag,
            'user' => $user,
            'parent' => substr($parents, 0, 40)
        ));

        if($useCache) {
            $cache->save();
        }

        return $cache;
    }


    public function getDiff($old, $new) {
        // Get the raw difference
        $rawDiff = $this->diff($old, $new);
        $result = array(
            'additions' => 0,
            'deletions' => 0,
            'differences' => array()
        );

        if(!$rawDiff) {
            return $result;
        }

        $blocks = array_slice(preg_split('/diff \-\-git .*$/m', $rawDiff), 1);

        foreach($blocks as $block) {
            $block = ltrim($block);
            $type = 'modified';

            if(substr($block, 0, 5) === 'index' || substr($block, 0, 8) === 'new file') {
                // New files / File modification
                preg_match('/^\+\+\+ b\/(.*)$/m', $block, $match);
                $filename = $match[1];
                if(substr($block, 0, 8) === 'new file') {
                    $type = 'added';
                }
            }
            else {
                // File deletion
                preg_match('/^\-\-\- a\/(.*)$/m', $block, $match);
                $filename = $match[1];
                $type = 'deleted';
            }

            $result['differences'][$filename] = array(
                'differences' => array(),
                'type' => $type,
                'additions' => 0,
                'deletions' => 0
            );

            // Get all the differences block in the file
            $subBlocks = preg_split('/^(\@\@ \-\d+(?:,\d+)? \+\d+(?:,\d+)? \@\@)/m', $block, -1, PREG_SPLIT_DELIM_CAPTURE);
            $subBlocks = array_slice($subBlocks, 1);


            for($i = 0; $i < count($subBlocks); $i += 2) {
                preg_match('/^\@\@ \-(\d+)(?:,(\d+))? \+(\d+)(?:,(\d+))? \@\@/', $subBlocks[$i], $match);

                $leftFirstLine = $match[1];
                $leftOffset = 0;

                $rightFirstLine = $match[3];
                $rightOffset = 0;

                $lines = array_slice(explode(PHP_EOL, $subBlocks[$i + 1]), 1, -1);
                $details = array();

                foreach($lines as $line) {
                    $detailsLine = array(
                        'code' => $line
                    );

                    if(substr($line, 0, 1) === '+') {
                        $detailsLine['rightLineNumber'] = $rightFirstLine + $rightOffset;
                        $detailsLine['leftLineNumber'] = '';
                        $detailsLine['type'] = 'addition';
                        $rightOffset++;
                        $result['differences'][$filename]['additions'] ++;
                    }
                    elseif(substr($line, 0, 1) === '-') {
                        $detailsLine['leftLineNumber'] = $leftFirstLine + $leftOffset;
                        $detailsLine['rightLineNumber'] = '';
                        $detailsLine['type'] = 'deletion';
                        $leftOffset++;
                        $result['differences'][$filename]['deletions'] ++;
                    }
                    else {
                        $detailsLine['rightLineNumber'] = $rightFirstLine + $rightOffset;
                        $detailsLine['leftLineNumber'] = $leftFirstLine + $leftOffset;
                        $detailsLine['type'] = '';
                        $rightOffset++;
                        $leftOffset++;
                    }

                    $details[] = $detailsLine;
                }

                $result['differences'][$filename]['differences'][] = array(
                    'summary' => $subBlocks[$i],
                    'details' => $details
                );

            }

            $result['additions'] += $result['differences'][$filename]['additions'];
            $result['deletions'] += $result['differences'][$filename]['deletions'];
        }

        return $result;
    }

    public function __call($name, $arguments) {
        return call_user_func_array(array($this->getGitRepo(), $name), $arguments);
    }
}