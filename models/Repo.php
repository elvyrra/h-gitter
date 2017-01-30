<?php

/**
 * Repo.php
 *
 * @author Elvyrra SAS
 */

namespace Hawk\Plugins\HGitter;

use \Hawk\Plugins\HTracker as HTracker;


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

    private $cloneRepo;

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

        $insert['masters'] = json_encode($this->decodedMasters);

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
     * Get the path of the clone repository. The clone repository is used to compute merge actions
     * @return string Tge path of the clone repository
     */
    public function getCloneRepoDirname() {
        return $this->getProject()->getDirname() . '/' . $this->name . '-clone' ;
    }

    /**
     * Get the clone repository
     * @return Git
     */
    public function getCloneRepo() {
        if(!$this->cloneRepo) {
            $this->cloneRepo = Git::open($this->getCloneRepoDirname());
        }

        return $this->cloneRepo;
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
            $cache->addIfNotExists();
        }

        return $cache;
    }

    /**
     * Get the differences between two revisions, and return the diff in a structured array
     * @param  string $old The old revision
     * @param  string $new The new revision
     * @return array       The structured difference
     */
    public function getDiff($old, $new, $file = '') {
        // Get the raw difference
        $result = array(
            'additions' => 0,
            'deletions' => 0,
            'differences' => array()
        );

        $rawDiff = $this->diff($old, $new, '', $file);
        if(empty($rawDiff)) {
            return $result;
        }

        $blocks = array_slice(preg_split('/(diff \-\-git .*$)/m', $rawDiff, -1, PREG_SPLIT_DELIM_CAPTURE), 1);

        for($j = 0; $j < count($blocks); $j+= 2) {
            $title = $blocks[$j];
            if(preg_match('#^diff \-\-git a/(.+?) b/(.+?)#', $title, $match)) {
                $filename = $match[1];
            }

            $block = ltrim($blocks[$j + 1]);
            $type = 'modified';

            if(substr($block, 0, 3) === 'new') {
                $type = 'added';
            }
            elseif(substr($block, 0, 7) === 'deleted') {
                $type = 'deleted';
            }

            $result['differences'][$filename] = array(
                'differences' => array(),
                'type' => $type,
                'additions' => 0,
                'deletions' => 0,
                // 'extension' => CodeController::getInstance()->getFileAceLanguage($filename),
                'extension' => $extension = pathinfo($filename, PATHINFO_EXTENSION)
            );

            if($type !== 'deleted') {
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
                        $detailsLine = array();

                        if(substr($line, 0, 1) === '+') {
                            $detailsLine['rightLineNumber'] = $rightFirstLine + $rightOffset;
                            $detailsLine['leftLineNumber'] = '';
                            $detailsLine['type'] = 'addition';
                            $detailsLine['code'] = substr($line, 1);
                            $rightOffset++;
                            $result['differences'][$filename]['additions'] ++;
                        }
                        elseif(substr($line, 0, 1) === '-') {
                            $detailsLine['leftLineNumber'] = $leftFirstLine + $leftOffset;
                            $detailsLine['rightLineNumber'] = '';
                            $detailsLine['type'] = 'deletion';
                            $detailsLine['code'] = substr($line, 1);
                            $leftOffset++;
                            $result['differences'][$filename]['deletions'] ++;
                        }
                        else {
                            $detailsLine['rightLineNumber'] = $rightFirstLine + $rightOffset;
                            $detailsLine['leftLineNumber'] = $leftFirstLine + $leftOffset;
                            $detailsLine['type'] = '';
                            $detailsLine['code'] = substr($line, 1);
                            $rightOffset++;
                            $leftOffset++;
                        }

                        $details[] = $detailsLine;
                    }

                    $result['differences'][$filename]['differences'][] = array(
                        'summary' => $subBlocks[$i],
                        'details' => $details,
                        'code' => implode(PHP_EOL, $lines)
                    );

                }

                $result['additions'] += $result['differences'][$filename]['additions'];
                $result['deletions'] += $result['differences'][$filename]['deletions'];
            }
        }

        return $result;
    }

    /**
     * Get main information about a difference
     * @param   string $old The old revision
     * @param   string $new The new revision
     */
    public function getDiffStats($old, $new) {
        $lines = explode(PHP_EOL, trim($this->diff($old, $new, '--name-status')));

        $result = array(
            'total' => count($lines),
            'files' => array(),
        );

        // Get files status
        foreach($lines as $line) {
            list($type, $file) = preg_split('/\s+/', trim($line));

            $result['files'][$file] = array(
                'status' => $type
            );
        }

        // Get diff summary
        // $line = trim($this->diff($old, $new, '--shortstat'));

        // if(preg_match('/(\d+) files changed, (\d+) insertions\(\+\), (\d+) deletions/', $line, $match)) {
        //     $result['summary'] = array(
        //         'additions' => $match[2],
        //         'deletions' => $match[3]
        //     );
        // }

        // // Get the diff sumary
        // $lines = explode(PHP_EOL, trim($this->diff($old, $new, '--summary')));

        // foreach($lines as $line) {
        //     list($additions, $deletions, $file) = preg_split('/\s+/', trim($line));

        //     $result['files'][$file]['additions'] = $additions;
        //     $result['files'][$file]['deletions'] = $deletions;
        // }

        return $result;
    }

    /**
     * Get short stats diffenrece
     * @param   string $old The old revision
     * @param   string $new The new revision
     */
    public function getShortStatDiff($old, $new) {
        $line = trim($this->diff($old, $new, '--shortstat'));

        if(preg_match('/(\d+) files changed, (\d+) insertions\(\+\), (\d+) deletions/', line, $match)) {
            return array(
                'files' => $match[1],
                'additions' => $match[2],
                'deletions' => $match[3]
            );
        }

        return null;
    }


    /**
     * Magic function to call Git lib method
     * @param  string $name      The called method
     * @param  array $arguments  The called arguments
     * @return mixed
     */
    public function __call($name, $arguments) {
        return call_user_func_array(array($this->getGitRepo(), $name), $arguments);
    }

    /**
     * Get the repository merge requests
     * @return array The list of the repository merge requests
     */
    public function getMergeRequests() {
        return MergeRequest::getListByExample(new DBExample(array(
            'repoId' => $this->id
        )));
    }


    /**
     * Get the open merge requests
     * @return array The open merge requests
     */
    public function getOpenMergeRequests() {
        return MergeRequest::getListByExample(new DBExample(array(
            'repoId' => $this->id,
            'merged' => 0
        )));
    }


    /**
     *  Check if a branch can be merged on another one
     */
    public function canMerge($from, $to) {
        if($from === $to) {
            return false;
        }
        $behind = (int) trim($this->run('rev-list ' . $to . '..' . $from . ' --count --no-merges'));

        if(!$behind) {
            return false;
        }

        return true;
    }

    /**
     * Get the issues open on the repository
     * @return array The list of HTRacker\Ticket
     */
    public function getIssues() {
        $htracker = Plugin::get('h-tracker');

        if($htracker && $htracker->isInstalled()) {
            $htrackerProject = HTracker\Project::getByExample(new DBExample(array(
                'name' => $this->name
            )));

            if(!$htrackerProject) {
                return array();
            }

            return HTracker\Ticket::getListByExample(new DBExample(array(
                'projectId' => $htrackerProject->id
            )));
        }

        return array();
    }

    /**
     * Get the basename of the repo avatar
     * @return string
     */
    public function getAvatarBasename() {
        return 'repo-avatar-' . $this->id;
    }

    /**
     * Get the full path of the repo avatar
     * @return string
     */
    public function getAvatarFilename() {
        return Plugin::current()->getPublicUserfilesDir() . $this->getAvatarBasename();
    }

    /**
     * Get the avatr URL of the project
     * @return string
     */
    public function getAvatarUrl() {
        $basename = $this->getAvatarBasename();
        $plugin = Plugin::current();

        if(is_file($this->getAvatarFilename())) {
            return $plugin->getUserfilesUrl($basename);
        }

        return '';
    }
}