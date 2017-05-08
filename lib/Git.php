<?php

namespace Hawk\Plugins\HGitter;

class Git {

    /**
     * Git executable location
     *
     * @var string
     */
    protected static $bin = 'git';


    /**
     * Repository path
     *
     * @var string
     */
    private $path;


    private function __construct($path) {
        $this->path = $path;
    }

    /**
     * Create a new git repository
     *
     * Accepts a creation path, and, optionally, a source path
     *
     * @access  public
     * @param   string  $path   repository path
     * @param   string  $source directory to source
     * @return  Git
     */
    public static function create($path, $source = null) {
        if (is_dir($path . '/.git')) {
            throw new GitException($path . ' is already a git repository');
        }

        if(!is_dir(dirname($path))) {
            throw new GitException('Impossible to create the directory ' . basename($path) . ' in ' . dirname($path) . ' : No such file or directory');
        }

        mkdir($path);

        $repo = new self($path);

        $repo->run('init');

        return $repo;
    }

    /**
     * Open an existing git repository
     *
     * Accepts a repository path
     *
     * @access  public
     * @param   string  $path repository path
     * @return  Git
     */
    public static function open($path) {
        if (!is_dir($path . '/.git')) {
            throw new GitException($path . ' is not a git repository');
        }

        return new self($path);
    }


    /**
     * Clone a remote git repository
     *
     * @param  string $remote The remote URL
     * @param  string $path   The local path to clone to repository to
     * @return Git
     */
    public static function cloneRemote($remote, $path = '') {
        if(!$path) {
            $dirname = realpath(Option::get(Plugin::current()->getName() . '.default-folder'));
            $basename = basename($remote, '.git');

            $path = $dirname . '/' . $basename;
        }

        mkdir($path);

        $repo = new self($path);

        $repo->run('clone ' . $remote . ' ' . $path);

        return $repo;
    }


    /**
     * Tests if git is installed
     *
     * @access  public
     * @return  bool
     */
    public static function isGitInstalled() {
        $descriptorspec = array(
            1 => array('pipe', 'w'),
            2 => array('pipe', 'w'),
        );
        $pipes = array();
        $resource = proc_open(self::$bin, $descriptorspec, $pipes);

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        foreach ($pipes as $pipe) {
            fclose($pipe);
        }

        $status = trim(proc_close($resource));
        return ($status != 127);
    }

    /**
     * Run a git command in the git repository
     *
     * Accepts a git command to run
     *
     * @access  public
     * @param   string  command to run
     * @return  string
     */
    public function run($command) {
        $descriptorspec = array(
            1 => array('pipe', 'w'),
            2 => array('pipe', 'w'),
        );
        $pipes = array();

        $cwd = $this->path;
        $cmd = self::$bin . ' ' . $command;

        $resource = proc_open($cmd, $descriptorspec, $pipes, $cwd);

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        foreach ($pipes as $pipe) {
            fclose($pipe);
        }

        $status = trim(proc_close($resource));
        if ($status) {
            throw new GitException($stderr);
        }

        return $stdout;
    }

    /**
     * Runs a 'git status' call
     *
     * @access public
     *
     * @param bool $short if set to true, the will return the result in short format
     *
     * @return string
     */
    public function status($short = false) {
        $command = 'status' . ($short ? ' -s' : '');

        return $this->run($command);

        return $msg;
    }

    /**
     * Runs a `git add` call
     *
     * Accepts a list of files to add
     *
     * @access  public
     * @param   mixed   files to add
     * @return  string
     */
    public function add($files = "*") {
        if (is_array($files)) {
            $files = implode(' ', $files);
        }

        return $this->run('add ' . $files);
    }

    /**
     * Runs a `git rm` call
     *
     * Accepts a list of files to remove
     *
     * @access  public
     * @param   mixed    files to remove
     * @param   Boolean  use the --cached flag?
     * @return  string
     */
    public function rm($files = "*", $cached = false) {
        if (is_array($files)) {
            $files = implode(' ', $files);
        }
        return $this->run('rm ' . ($cached ? '--cached ' : '') . $files);
    }


    /**
     * Runs a `git commit` call
     *
     * Accepts a commit message string
     *
     * @access  public
     * @param   string  commit message
     * @param   boolean  should all files be committed automatically (-a flag)
     * @return  string
     */
    public function commit($message = '', $commit_all = true) {
        $flags = $commit_all ? '-am' : '-m';

        return $this->run('commit ' . $flags . ' ' . escapeshellarg($message));
    }

    /**
     * Runs a `git clean` call
     *
     * Accepts a remove directories flag
     *
     * @access  public
     * @param   bool $dirs delete directories?
     * @param   bool $force force clean?
     * @return  string
     */
    public function clean($dirs = false, $force = false) {
        return $this->run('clean' . ($force ? ' -f' : '') . ($dirs ? ' -d' : ''));
    }

    /**
     * Runs a `git branch` call
     *
     * Accepts a name for the branch
     *
     * @access  public
     * @param   string  branch name
     * @return  string
     */
    public function createBranch($branch, $from) {
        return $this->run('branch ' . $branch . ' ' . $from);
    }

    /**
     * Runs a `git branch -[d|D]` call
     *
     * Accepts a name for the branch
     *
     * @access  public
     * @param   string  branch name
     * @return  string
     */
    public function deleteBranch($branch, $force = false) {
        return $this->run('branch ' . ($force ? '-D' : '-d') . ' ' . $branch);
    }

    /**
     * Runs a `git branch` call
     *
     * @access  public
     * @return  array
     */
    public function getBranches() {
        $branches = explode(PHP_EOL, $this->run('branch'));

        foreach($branches as $i => &$branch) {
            $branch = trim($branch);
            $branch = str_replace('* ', '', $branch);

            if(!$branch) {
                unset($branches[$i]);
            }
        }

        return $branches;
    }

    /**
     * Returns name of active branch
     *
     * @access  public
     * @param   bool    keep asterisk mark on branch name
     * @return  string
     */
    public function getActiveBranch() {
        $branches = explode(PHP_EOL, $this->run('branch'));
        $regex = '/^\* /';

        foreach($branches as &$branch) {
            $branch = trim($branch);

            if(preg_match($regex, $branch)) {
                return preg_replace($regex, '', $branch);
            }
        }
    }

    /**
     * Runs a `git checkout` call
     *
     * Accepts a name for the branch
     *
     * @access  public
     * @param   string  branch name
     * @return  string
     */
    public function checkout($branch) {
        return $this->run('checkout ' . $branch);
    }


    /**
     * Runs a `git merge` call
     *
     * Accepts a name for the branch to be merged
     *
     * @access  public
     * @param   string $branch
     * @return  string
     */
    public function merge($branch) {
        return $this->run('merge ' . $branch);
    }


    /**
     * Add a new tag on the current position
     *
     * Accepts the name for the tag and the message
     *
     * @param string $tag       The name of the tag to create
     * @param string $revision  The revision name to apply the tag on
     * @return string
     */
    public function tag($tag, $revision = '') {
        return $this->run('tag ' . $tag . ' ' . $revision);
    }

    /**
     * Remove a tag
     */
    public function removeTag($tag){
        return $this->run('tag -d ' . $tag);
    }

    /**
     * List all the available repository tags.
     *
     * Optionally, accept a shell wildcard pattern and return only tags matching it.
     *
     * @access  public
     * @param   string  $pattern    Shell wildcard pattern to match tags against.
     * @return  array               Available repository tags.
     */
    public function getTags($pattern = '') {
        $tags = explode(PHP_EOL, $this->run('tag -l ' . $pattern));

        foreach ($tags as $i => &$tag) {
            $tag = trim($tag);

            if(!$tag) {
                unset($tags[$i]);
            }
        }

        return $tags;
    }

    /**
     * Push specific branch to a remote
     *
     * Accepts the name of the remote and local branch
     *
     * @param string $remote
     * @param string $branch
     * @return string
     */
    public function push($remote = 'origin', $branch = '') {
        if(!$branch) {
            $branch = $this->getActiveBranch();
        }

        return $this->run("push --tags $remote $branch");
    }

    /**
     * Pull specific branch from remote
     *
     * Accepts the name of the remote and local branch
     *
     * @param string $remote
     * @param string $branch
     * @return string
     */
    public function pull($remote = 'origin', $branch = '') {
        if(!$branch) {
            $branch = $this->getActiveBranch();
        }

        return $this->run("pull $remote $branch");
    }

    /**
     * List log entries.
     *
     * @param strgin $format
     * @return string
     */
    public function log($format = null) {
        if ($format === null) {
            return $this->run('log');
        }
        else {
            return $this->run('log --pretty=format:"' . $format . '"');
        }
    }

    /**
     * Get the differences between two branches
     * @returns string
     */
    public function diff($branch1, $branch2, $options = '', $file = '') {
        return $this->run('diff ' . $options . ' ' . $branch1 . ' ' . $branch2 . ' -- ' . $file);
    }


    /**
     * Get the last common ancestor between twon branches
     * @param  string $branch1 The first branch
     * @param  string $branch2 The second branch
     * @return string          The last common ancestor
     */
    public function mergeBase($branch1, $branch2) {
        return trim($this->run('merge-base ' . $branch1 . ' ' . $branch2));
    }


    /**
     * Display the content of a file in the repository, for a given version
     * @param  string $file    The path to the file, relative to the repository root folder
     * @param  string $version The version to read (default HEAD)
     * @return string          The content of the file
     */
    public function show($file, $version = 'HEAD') {
        return $this->run("show $version:$file");
    }

    /**
     * Display the content of a folder
     * @param  string $folder  The folder to get the content
     * @param  string $version The version
     * @return string
     */
    public function ls($folder = '', $version = 'HEAD') {
        if($folder) {
            return $this->run("ls-tree $version:$folder");
        }
        else {
            return $this->run("ls-tree $version");
        }
    }

    /**
     * Get a config property vlaue / Set a config property
     * @param  string $prop  The configuration property
     * @param  string $value The value to set. If not set, the function will return the current value of the property
     * @return string        Returns the value of the property
     */
    public function config($prop, $value = null) {
        if($value === null) {
            return trim($this->run('config ' . $prop));
        }

        $this->run('config ' . $prop . ' ' . $value);

        return $value;
    }

    /**
     * Check if the repository is a bare repository
     * @return boolean
     */
    public function isBare() {
        return $this->config('core.bare') === 'true';
    }

    /**
     * Downlaod objects and refs from a remote repo
     * @param string $origin The origin repository to fetch the references and objects
     */
    public function fetch($origin = 'origin') {
        $this->run('fetch ' . $origin);
    }
}
