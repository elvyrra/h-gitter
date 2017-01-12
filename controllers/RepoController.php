<?php

namespace Hawk\Plugins\HGitter;

use Hawk\Plugins\HWidgets as HWidgets;

class RepoController extends Controller {
    /**
     * Display the list of the repositories of the current project
     * @return string The HTML response
     */
    public function index() {
        $project = Project::getById($this->projectId);

        $repos = Repo::getListByExample(new DBExample(array(
            'projectId' => $this->projectId
        )));

        $repos = array_filter($repos, function($repo) {
            return $repo->isVisible();
        });

        $list = new ItemList(array(
            'id' => 'h-gitter-repos-list',
            'sorts' => array(
                'mtime' => DB::SORT_DESC
            ),
            'data' => $repos,
            'controls' => array(
                $project->isUserMaster() ?
                    array(
                        'icon' => 'plus',
                        'href' => App::router()->getUri('h-gitter-edit-repo', array('repoId' => 0)) .'?projectId=' . $this->projectId,
                        'label' => Lang::get($this->_plugin . '.new-repo-btn'),
                        'class' => 'btn-primary',
                        'target' => 'dialog'
                    ) : null,

                $project->isUserMaster() ?
                    array(
                        'icon' => 'cogs',
                        'label' => Lang::get($this->_plugin . '.repos-list-project-settings-btn'),
                        'class' => 'btn-info',
                        'href' => App::router()->getUri('h-gitter-edit-project', array('projectId' => $this->projectId)),
                        'target' => 'dialog'
                    ) : null,

                array(
                    'icon' => 'reply',
                    'label' => Lang::get('main.back-button'),
                    'href' => App::router()->getUri('h-gitter-index')
                ),


            ),

            'fields' => array(
                'actions' => array(
                    'independant' => true,
                    'sort' => false,
                    'search' => false,
                    'display' => function($value, $field, $line) {
                        return Icon::make(array(
                            'icon' => 'cogs',
                            'size' => 'lg',
                            'class' => 'disabled',
                            'href' => App::router()->getUri('h-gitter-edit-repo', array(
                                'repoId' => $line->id
                            )),
                            'title' => Lang::get($this->_plugin . '.repos-list-repos-settings-btn'),
                            'target' => 'dialog'
                        ));
                    }
                ),
                'name' => array(
                    'label' => Lang::get($this->_plugin . '.repos-list-name-label'),
                    'href' => function($value, $field, $repo) {
                        return App::router()->getUri('h-gitter-display-repo', array('repoId' => $repo->id));
                    },
                    'display' => function($value, $field, $repo) {
                        return HWidgets\MetaData::getInstance(array(
                            'avatar' => $repo->getAvatarUrl(),
                            'name' => $repo->name,
                            'meta' => $repo->name,
                            'description' => substr($repo->description, 0, 300),
                            'size' => 'small'
                        ))->display();
                    }
                ),

                'description' => array(
                    'label' => Lang::get($this->_plugin . '.repos-list-description-label')
                ),

                'mtime' => array(
                    'label' => Lang::get($this->_plugin . '.repos-list-mtime-label'),
                    'display' => function($value) {
                        return Utils::timeAgo($value);
                    }
                ),
            )
        ));

        if($list->isRefreshing()) {
            return $list->display();
        }

        return NoSidebarTab::make(array(
            'title' => Lang::get($this->_plugin . '.repos-list-title', array(
                'project' => $project->name
            )),
            'icon' => 'git-square',
            'page' => $list->display()
        ));
    }

    /**
     * Create / Edit / Delete a repository
     */
    public function edit() {
        if(empty($this->repoId)) {
            $repo = null;
            $projectId = App::request()->getParams('projectId');
            $branches = array();
        }
        else {
            $repo = Repo::getById($this->repoId);
            $projectId = $repo->projectId;
            $branches = $repo->getBranches();
        }

        $project = Project::getById($projectId);

        if(!$project) {
            throw new PageNotFoundException('', array(
                'resource' => 'project',
                'id' => $projectId
            ));
        }

        if(!$project->isUserMaster()) {
            throw new ForbiddenException();
        }

        $users = array_filter(User::getAll(), function($user) use($project) {
            return $project->isVisible($user);
        });

        $masters = $repo ? $repo->decodedMasters : array();
        foreach($users as $user) {
            if($project->isUserMaster($user)) {
                $masters[] = $user->id;
            }
        }
        $masters = array_unique($masters);


        $form = new Form(array(
            'id' => 'h-gitter-repo-form',
            'model' => 'Repo',
            'object' => $repo,
            'fieldsets' => array(
                'global' => array(
                    'legend' => Lang::get($this->_plugin . '.edit-repo-global-legend'),

                    empty($this->repoId) ?
                        new HiddenInput(array(
                            'name' => 'ctime',
                            'value' => time()
                        )) : null,


                    new CheckboxInput(array(
                        'name' => 'existing-repo',
                        'independant' => true,
                        'attributes' => array(
                            'e-value' => 'existingRepo'
                        ),
                        'disabled' => $this->repoId,
                        'default' => $this->repoId ? true : false,
                        'label' => Lang::get($this->_plugin . '.edit-repo-existing-repo-label'),
                        'labelWidth' => 'auto'
                    )),

                    new TextInput(array(
                        'name' => 'name',
                        'maxlength' => 64,
                        'label' => Lang::get($this->_plugin . '.edit-repo-name-label'),
                        'pattern' => '/^[^\/]+$/',
                        'disabled' => $this->repoId,
                        'required' => App::request()->getMethod() === 'post' && !$this->repoId  && !App::request()->getBody('existing-repo'),
                        'attributes' => $this->repoId ? array() : array(
                            'e-disabled' => 'existingRepo'
                        ),
                    )),

                    new TextInput(array(
                        'name' => 'path',
                        'unique' => true,
                        'maxlength' => 192,
                        'label' => Lang::get($this->_plugin . '.edit-repo-path-label'),
                        'disabled' => $this->repoId,
                        'required' => !$this->repoId && App::request()->getBody('existingRepo'),
                        'attributes' => $this->repoId ? array() : array(
                            'e-disabled' => '!existingRepo'
                        ),
                    )),

                    new HiddenInput(array(
                        'name' => 'projectId',
                        'required' => true,
                        'default' => $projectId
                    )),

                    new HWidgets\MarkdownInput(array(
                        'name' => 'description',
                        'rows' => '3',
                        'required' => true,
                        'maxlength' => 4096,
                        'label' => Lang::get($this->_plugin . '.edit-repo-description-label')
                    )),

                    new FileInput(array(
                        'name' => 'avatar',
                        'extensions' => array(
                            'png',
                            'jpg',
                            'gif',
                            'tif'
                        ),
                        'label' => Lang::get($this->_plugin . '.edit-repo-avatar-label'),
                        'after' => ($repo && $repo->getAvatarUrl()) ? '<img src="' . $repo->getAvatarUrl() . '" class="user-avatar"/>' : '',
                    )),

                    new SelectInput(array(
                        'name' => 'defaultBranch',
                        'label' => Lang::get($this->_plugin . '.edit-repo-default-branch-label'),
                        'options' => array_combine($branches, $branches),
                        'notDisplayed' => !$this->repoId
                    ))
                ),

                'privileges' => array_map(function($user) use($masters, $project) {
                    return new CheckboxInput(array(
                        'name' => 'masters[' . $user->id . ']',
                        'default' => in_array($user->id, $masters) ? true : false,
                        'disabled' => $project->isUserMaster($user)
                    ));
                }, $users),

                'submits' => array(
                    new SubmitInput(array(
                        'name'  => 'valid',
                        'value' => Lang::get('main.valid-button'),
                    )),

                    new DeleteInput(array(
                        'name' => 'delete',
                        'value' => Lang::get('main.delete-button'),
                        'notDisplayed' => !$this->repoId
                    )),

                    new ButtonInput(array(
                        'name'    => 'cancel',
                        'value'   => Lang::get('main.cancel-button'),
                        'onclick' => 'app.dialog("close")',
                    )),
                ),
            ),
            'onsuccess' => 'app.dialog("close"); app.lists["h-gitter-repos-list"].refresh();'
        ));

        switch($form->submitted()) {
            case false :
                $this->addJavaScript($this->getPlugin()->getJsUrl('edit-repo.js'));

                return Dialogbox::make(array(
                    'title' => Lang::get($this->_plugin . '.edit-repo-title'),
                    'icon' => 'git-square',
                    'page' => View::make($this->getPlugin()->getView('edit-repo.tpl'), array(
                        'form' => $form,
                        'users' => $users
                    ))
                ));

            case 'delete' :
                $form->delete(false);

                // Remove the clone repository
                if(is_dir($repo->getCloneRepoDirname())) {
                    App::fs()->remove($repo->getCloneRepoDirname());
                }

                // Remove the repository if it is managed only under H Gitter
                if(strpos($repo->path, $this->getPlugin()->getUserfilesDir()) !== false) {
                    App::fs()->remove($repo->path);
                }

                // Remove the avatar filename
                if(is_file($repo->getAvatarFilename())) {
                    App::fs()->remove($repo->getAvatarFilename());
                }

                return $form->response(Form::STATUS_SUCCESS);

            default :
                if($form->check()) {
                    if(!$repo) {
                        $existingRepo = Repo::getByExample(new DBExample(array(
                            'id' => array(
                                '$ne' => $this->repoId
                            ),
                            'projectId' => $form->getData('projectId'),
                            'name' => $form->getData('existing-repo') ? basename($form->getData('path')) : $form->getData('name')
                        )));

                        if($existingRepo) {
                            $form->error($form->getData('existing-repo') ? 'path' : 'name', Lang::get($this->_plugin . '.edit-repo-same-name-message'));
                            return $form->response(Form::STATUS_CHECK_ERROR, Lang::get($this->_plugin . '.edit-repo-same-name-message'));
                        }

                        // New repository
                        $repo = new Repo(array(
                            'projectId' => $form->getData('projectId'),
                            'ctime' => $form->getData('ctime'),
                            'description' => $form->getData('description'),
                            'userId' => App::session()->getUser()->id
                        ));

                        if($form->getData('existing-repo')) {
                            // Create the repository object from an existing Git repository
                            $repo->path = $form->getData('path');
                            $repo->name = basename($repo->path);

                            // Check the folder exists and is a git repository
                            try {
                                $git = Git::open($repo->path);
                                if(!$git->isBare()) {
                                    throw new \Exception(Lang::get($this->_plugin . '.edit-repo-non-bare-message'));
                                }
                            }
                            catch(\Exception $e) {
                                return $form->response(Form::STATUS_CHECK_ERROR, $e->getMessage());
                            }
                        }
                        else {
                            // Create a non existing repository
                            $repo->name = $form->getData('name');
                            $repo->path = $project->getDirname() . '/' . $repo->name;

                            // Check the folder exists and is a git repository
                            try {
                                $git = Git::create($repo->path);
                                $git->config('core.bare', 'true');
                            }
                            catch(GitException $e) {
                                return $form->response(Form::STATUS_CHECK_ERROR, $e->getMessage());
                            }
                        }

                        // Clone the repository in userfiles
                        Git::cloneRemote($repo->path, $repo->getCloneRepoDirname());

                        // Save the repository object in the database
                        $repo->save();
                    }
                    else {
                        if(App::request()->getBody('masters')) {
                            $form->object->decodedMasters = array_keys(App::request()->getBody('masters'));
                        }

                        $form->register(false);
                    }

                    $upload = Upload::getInstance('avatar');

                    if($upload) {
                        $basename = $repo->getAvatarBasename();
                        $dirname = $this->getPlugin()->getPublicUserfilesDir();

                        $upload->move($upload->getFile(), $dirname, $basename);
                    }

                    return $form->response(Form::STATUS_SUCCESS);
                }
            }
    }

    /**
     * display the content of a repository
     * @returns string The HTML result
     */
    public function display($section = 'home', $sectionContent = '', $widgets = array()) {
        $repo = Repo::getById($this->repoId);

        $menuItems = array(
            'home' => array(
                'icon' => 'home',
                'url' => App::router()->getUri('h-gitter-repo-home', array('repoId' => $repo->id))
            ),
            'code' => array(
                'icon' => 'code',
                'url' => App::router()->getUri('h-gitter-repo-code-folder', array('repoId' => $repo->id))
            ),
            'commits' => array(
                'icon' => 'floppy-o',
                'url' => App::router()->getUri('h-gitter-repo-commits', array('repoId' => $repo->id))
            ),
            'branches' => array(
                'icon' => 'code-fork',
                'number' => count($repo->getBranches()),
                'url' => App::router()->getUri('h-gitter-repo-branches', array('repoId' => $repo->id))
            ),
            'tags' => array(
                'icon' => 'tags',
                'number' => count($repo->getTags()),
                'url' => App::router()->getUri('h-gitter-repo-tags', array('repoId' => $repo->id))
            ),
            'issues' => PLugin::get('h-tracker') ? array(
                'icon' => 'bug',
                'number' => count($repo->getIssues()),
                'url' => App::router()->getUri('h-gitter-repo-issues', array('repoId' => $repo->id))
            ) : null,
            'merge-requests' => array(
                'icon' => 'code-fork icon-flip-vertical',
                'number' => count($repo->getOpenMergeRequests()),
                'url' => App::router()->getUri('h-gitter-repo-merge-requests', array('repoId' => $repo->id))
            ),
            'settings' => array(
                'icon' => 'cogs',
                'url' => App::router()->getUri('h-gitter-edit-repo', array('repoId' => $repo->id)),
                'target' => 'dialog',
                'auth' => $repo->isUserMaster()
            )
        );

        if(!$sectionContent) {
            $parser = new Parsedown();

            $filename = 'README.md';

            try {
                $readme = $repo->show($filename);
            }
            catch(GitException $e) {
                $readme = '';
            }

            $sectionContent = Panel::make(array(
                'type' => 'info',
                'title' => $filename,
                'icon' => 'book',
                'content' => $parser->text($readme)
            ));
        }

        $content = View::make($this->getPlugin()->getView('repo-index.tpl'), array(
            'contentId' => 'h-gitter-repo-content',
            'repo' => $repo,
            'menuItems' => $menuItems,
            'home' => $sectionContent,
            'active' => $section
        ));


        $this->addJavaScript($this->getPlugin()->getJsUrl('repository.js'));
        $this->addCss($this->getPlugin()->getCssUrl('repository.less'));

        array_unshift($widgets, RepositoryPageWidget::getInstance(array(
            'id' => $repo->id
        )));

        return LeftSidebarTab::make(array(
            'title' => Lang::get($this->_plugin . '.repo-index-title', array(
                'repo' => $repo->name
            )),
            'icon' => 'git-square',
            'page' => array(
                'content' => $content
            ),
            'sidebar' => array(
                'widgets' => $widgets
            )
        ));
    }

    /**
     * Display the home page of a repository
     * @return string The HTML result
     */
    public function home() {
        return $this->display();
    }
}