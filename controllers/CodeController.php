<?php

namespace Hawk\Plugins\HGitter;

class CodeController extends Controller {
    /**
     * Display the cotent of a folder on a given beanch
     * @return string The HTML response
     */
    public function displayFolder() {
        $start = microtime(true);

        $repo = Repo::getById($this->repoId);

        $revision = $this->revision;

        if($this->type === 'tag') {
            // Get the commit of the tag
            $revision = trim($repo->run('rev-list -n 1 ' . $this->revision));
        }

        try {
            $tree = explode(PHP_EOL, $repo->ls($this->path, $revision));


            $elements = array();
            foreach($tree as $element) {
                if($element) {
                    $info = preg_split('/\s+/', $element);
                    $rights = $info[0];
                    $type = $info[1];
                    $basename = $info[3];

                    $path = ($this->path ? $this->path : '.') . '/' . $basename;

                    $commitHash = trim($repo->run('log --format="%H" -n 1 ' . $revision . ' -- ' . $path));
                    $commit = $repo->getCommitInformation($commitHash);

                    $elements[] = (object) array(
                        'type' => $type,
                        'basename' => $basename,
                        'path' => $path,
                        'commit' => $commit->message,
                        'hash' => $commit->hash,
                        'date' => $commit->date,
                        'author' => $commit->author
                    );
                }
            }

            usort($elements, function($element1, $element2) {
                if($element1->type === $element2->type) {
                    return $element1->basename < $element2->basename ? -1 : 1;
                }

                return $element1->type === 'tree' ? -1  : 1;
            });

            $list = new ItemList(array(
                'id' => 'h-gitter-repo-tree',
                'data' => $elements,
                'navigation' => false,
                'noHeader' => true,
                'lines' => 'all',
                'fields' => array(
                    'basename' => array(
                        'href' => function($value, $field, $element) {
                            return  $element->type === 'tree' ?
                                App::router()->getUri('h-gitter-repo-code-folder', array(
                                    'repoId' => $this->repoId,
                                    'type' => $this->type,
                                    'revision' => $this->revision,
                                    'path' => $element->path
                                )) :
                                App::router()->getUri('h-gitter-repo-code-file', array(
                                    'repoId' => $this->repoId,
                                    'type' => $this->type,
                                    'revision' => $this->revision,
                                    'path' => $element->path
                                ));
                        },
                        'display' => function($value, $field, $element) {
                            return Icon::make(array(
                                'icon' => $element->type === 'tree' ? 'folder' : 'file-o',
                                'size' => 'lg'
                            )) . ' ' . $element->basename;
                        },
                    ),

                    'commit' => array(
                        'class' => 'text-primary',
                        'href' => function($value, $field, $element) {
                            return App::router()->getUri('h-gitter-repo-commit', array(
                                'repoId' => $this->repoId,
                                'commit' => $element->hash
                            ));
                        }
                    ),

                    'author' => array(),

                    'date' => array(
                        'display' => function($value) {
                            return Utils::timeAgo($value);
                        }
                    )
                )
            ));

            if($list->isRefreshing()) {
                return $list->display();
            }
        }
        catch(GitException $err) {
            // The current folder does not exist in the selected revision
            $list = View::make(Theme::getSelected()->getView('error.tpl'), array(
                'level' => 'warning',
                'icon' => 'exclamation-triangle',
                'title' => Lang::get($this->_plugin . '.repo-code-path-no-exists-title'),
                'message' => Lang::get($this->_plugin . '.repo-code-folder-no-exists', array(
                    'revision' => $revision
                )),
                'trace' => array()
            ));
        }

        $breadcrumb = $this->getBreadcrumb();

        $content = View::make($this->getPlugin()->getView('code/folder.tpl'), array (
            'list' => $list,
            'breadcrumb' => $breadcrumb,
            'type' => $this->type,
            'revision' => $this->revision,
            'path' => $this->path,
            'repoId' => $this->repoId
        ));

        return RepoController::getInstance(array(
            'repoId' => $this->repoId
        ))

        ->display('code', $content, Lang::get($this->_plugin . ($this->path ? '.repo-code-title' : '.repo-code-root-title'), array(
            'path' => $this->path,
            'branch' => $this->revision,
            'repo' => $repo->name
        )));
    }

    /**
     * Display the content of a file on a given branch and given commit
     * @return string The HTML response
     */
    public function displayFile() {
        $repo = Repo::getById($this->repoId);

        $revision = $this->revision;

        if($this->type === 'tag') {
            // Get the commit of the tag
            $revision = trim($repo->run('rev-list -n 1 ' . $revision));
        }

        try {
            $fileContent = $repo->show($this->path, $revision);
        }
        catch(GitException $err) {
            // The file does not exist for the selected revision
            $fileContent = Lang::get($this->_plugin . '.repo-code-file-no-exists', array(
                'revision' => $revision
            ));
        }

        $breadcrumb = $this->getBreadcrumb();

        $content = View::make($this->getPlugin()->getView('code/file.tpl'), array (
            'basename' => basename($this->path),
            'breadcrumb' => $breadcrumb,
            'fileContent' => $fileContent,
            'extension' => $this->getFileAceLanguage($this->path),
            'type' => $this->type,
            'revision' => $this->revision,
            'path' => $this->path,
            'repoId' => $this->repoId
        ));

        return RepoController::getInstance(array(
            'repoId' => $this->repoId
        ))

        ->display('code', $content, Lang::get($this->_plugin . '.repo-code-title', array(
            'path' => $this->path,
            'repo' => $repo->name
        )));
    }



    /**
     * Display the file history
     */
    public function fileHistory() {
        return CommitController::getInstance(array(
            'repoId' => $this->repoId,
            'revision' => $this->revision,
            'type' => $this->type
        ))->index($this->path);
    }


    /**
     * get File Ace language (javascript, php, ...)
     * @param   string $file The file to analyse
     * @returns string        An array containing the icon and the language of the file
     */
    public function getFileAceLanguage($file) {
        // Get all available ace languages
        $modes = glob(Plugin::get('main')->getPublicJsDir() . 'ext/ace/mode-*.js');

        $languages = array_map(function($mode) {
            preg_match('/mode\-(\w+)\.js$/', $mode, $match);

            return $match[1];
        }, $modes);

        $extension = pathinfo($file, PATHINFO_EXTENSION);

        if(in_array($extension, $languages)) {
            return $extension;
        }

        switch($extension) {
            case 'as' :
                return 'actionscript';

            case 'c' :
            case 'cpp' :
                return 'c_cpp';

            case 'cs' :
                return 'csharp';

            case 'js' :
                return 'javascript';

            case 'htm' :
            case 'xhtml' :
            case 'tpl' :
                return 'html';

            case 'pl' :
                return 'perl';

            case 'py' :
                return 'python';

            case 'ts' :
                return 'typescript';

            default :
                return 'text';
        }
    }

    public function getBreadcrumb() {
        $repo = Repo::getById($this->repoId);

        $breadcrumb = array();

        // Add the root element
        $breadcrumb[] = array(
            'label' => $repo->name,
            'url' => App::router()->getUri('h-gitter-repo-code-folder', array(
                'repoId' => $this->repoId,
                'type' => $this->type,
                'revision' => $this->revision
            ))
        );

        // Add parent dirs
        $dirs = explode('/', $this->path);
        foreach(array_slice($dirs, 0, -1) as $i => $parent) {
            $breadcrumb[] = array (
                'label' => $parent,
                'url' => App::router()->getUri('h-gitter-repo-code-folder', array(
                    'repoId' => $this->repoId,
                    'type' => $this->type,
                    'revision' => $this->revision,
                    'path' => implode('/', array_slice($dirs, 0, $i + 1))
                ))
            );
        }

        // Add the last element
        $breadcrumb[] = array(
            'label' => end($dirs),
            'url' => ''
        );

        return $breadcrumb;
    }
}