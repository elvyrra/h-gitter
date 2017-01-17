<?php

namespace Hawk\Plugins\HGitter;

use \Hawk\Plugins\HWidgets as HWidgets;

class ProjectController extends Controller {
    /**
     * Display the list of the projects
     */
    public function index() {
        $projects = array_filter(Project::getAll(), function($project) {
            return $project->isVisible();
        });

        $list = new ItemList(array(
            'id' => 'h-gitter-projects-list',
            'data' => $projects,
            'controls' => array(
                array(
                    'icon' => 'plus',
                    'label' => Lang::get($this->_plugin . '.new-project-btn'),
                    'href' => App::router()->getUri('h-gitter-edit-project', array('projectId' => 0)),
                    'target' => 'dialog'
                )
            ),
            'fields' => array (
                'actions' => array(
                    'independant' => true,
                    'display' => function($value, $field, $project) {
                        if(!$project->isUserMaster()) {
                            return '';
                        }

                        return Icon::make(array(
                            'icon' => 'cogs',
                            'size' => 'lg',
                            'class' => 'disabled',
                            'href' => App::router()->getUri('h-gitter-edit-project', array(
                                'projectId' => $project->id
                            )),
                            'title' => Lang::get($this->_plugin . '.repos-list-project-settings-btn'),
                            'target' => 'dialog'
                        ));
                    },
                    'search' => false,
                    'sort' => false
                ),

                'name' => array(
                    'label' => '',
                    'sort' => false,
                    'href' => function($value, $field, $project) {
                        return App::router()->getUri('h-gitter-project-repos', array('projectId' => $project->id));
                    },
                    'display' => function($value, $field, $project) {
                        $description = Lang::get($this->_plugin . '.project-list-name-meta', array(
                            'author' => User::getById($project->userId)->username,
                            'date' => date(Lang::get('main.date-format'), $project->ctime)
                        ));

                        return HWidgets\MetaData::getInstance(array(
                            'avatar' => $project->getAvatarUrl(),
                            'name' => $project->name,
                            'meta' => $project->name,
                            'description' => Parsedown::instance()->text($project->description) . $description,
                            'size' => 'small'
                        ))->display();
                    }
                ),

                'description' => array(
                    'hidden' => true
                ),

                'ctime' => array(
                    'hidden' => true
                ),

                'userId' => array(
                    'hidden' => true
                ),

                'info' => array(
                    'independant' => true,
                    'label' => '',
                    'sort' => false,
                    'search' => false,
                    'display' => function($value, $field, $project) {
                        // Display general information on the project (number of repositories, number of merge request, number of issues)
                        $members = $project->getUsers();
                        $repos = Repo::getListByExample(new DBExample(array(
                            'projectId' => $project->id
                        )));

                        $mergeRequests = 0;

                        foreach($repos as $repo) {
                            $mergeRequests += count($repo->getOpenMergeRequests());
                        }

                        return View::make($this->getPlugin()->getView('projects/project-info.tpl'), array(
                            'members' => count($members) + 1,
                            'repos' => count($repos),
                            'mergeRequests' => $mergeRequests
                        ));
                    }
                )
            )
        ));

        if($list->isRefreshing()) {
            return $list->display();
        }

        $this->addCss($this->getPlugin()->getCssUrl('project-list.less'));

        return NoSidebarTab::make(array(
            'icon' => 'git-square',
            'title' => Lang::get($this->_plugin . '.projects-list-title'),
            'page' => $list->display()
        ));
    }


    /**
     * Edit the properties of a project
     */
    public function edit() {
        $project = Project::getById($this->projectId);
        if(empty($this->projectId)) {
            if(!App::session()->isAllowed($this->_plugin . '.create-projects')) {
                throw new ForbiddenException();
            }
        }
        else {
            if(!$project->isUserMaster()) {
                throw new ForbiddenException();
            }
        }

        $users = array_filter(User::getAll(), function($user) {
            return $user->isAllowed($this->_plugin . '.access-plugin') && $user->id !== App::session()->getUser()->id;
        });

        $users = array_map(function($user) {
            return array(
                'id' => (int) $user->id,
                'username' => $user->username
            );
        }, array_values($users));

        $form = new Form(array(
            'id' => 'h-gitter-project-form',
            'model' => 'Project',
            'reference' => array(
                'id' => $this->projectId
            ),
            'object' => $project,
            'fieldsets' => array(
                'global' => array(
                    'legend' => Lang::get($this->_plugin . '.edit-project-global-legend'),

                    new TextInput(array(
                        'name' => 'name',
                        'required' => true,
                        'unique' => true,
                        'maxlength' => 64,
                        'label' => Lang::get($this->_plugin . '.edit-project-name-label')
                    )),

                    new HWidgets\MarkdownInput(array(
                        'name' => 'description',
                        'rows' => '3',
                        'required' => true,
                        'maxlength' => 4096,
                        'label' => Lang::get($this->_plugin . '.edit-project-description-label')
                    )),

                    new FileInput(array(
                        'name' => 'avatar',
                        'extensions' => array(
                            'png',
                            'jpg',
                            'gif',
                            'tif'
                        ),
                        'label' => Lang::get($this->_plugin . '.edit-project-avatar-label'),
                        'after' => $project && $project->getAvatarUrl() ? '<img src="' . $project->getAvatarUrl() . '" class="user-avatar" />' : '',
                    )),

                    $this->projectId ? null : new HiddenInput(array(
                        'name' => 'ctime',
                        'value' => time()
                    )),

                    $this->projectId ? null : new HiddenInput(array(
                        'name' => 'userId',
                        'value' => App::session()->getUser()->id
                    ))
                ),

                'privileges' => array(
                    'legend' => Lang::get($this->_plugin . '.edit-project-privileges-legend'),

                    new HiddenInput(array(
                        'name' => 'privileges',
                        'default' => '[]',
                        'attributes' => array(
                            'e-value' => 'privileges.toString()'
                        )
                    )),

                    new HiddenInput(array(
                        'name' => 'users',
                        'independant' => true,
                        'value' => json_encode($users)
                    )),

                    new TextInput(array(
                        'name' => 'search-user',
                        'independant' => true,
                        'attributes' => array(
                            'e-autocomplete' => "{source : availableUsers, search : 'username', label : 'username', change : addUser, minLength : 1}"
                        ),
                        'placeholder' => Lang::get($this->_plugin . '.edit-project-add-user-placeholder'),
                        'label' => Lang::get($this->_plugin . '.edit-project-add-user-label')
                    )),

                    new HtmlInput(array(
                        'name' => 'privileges-list',
                        'value' => View::make($this->getPlugin()->getView('projects/project-privileges.tpl'))
                    ))
                ),

                'submits' => array(
                    new SubmitInput(array(
                        'name'  => 'valid',
                        'value' => Lang::get('main.valid-button'),
                    )),

                    new DeleteInput(array(
                        'name' => 'delete',
                        'value' => Lang::get('main.delete-button'),
                        'notDisplayed' => !$this->projectId
                    )),

                    new ButtonInput(array(
                        'name'    => 'cancel',
                        'value'   => Lang::get('main.cancel-button'),
                        'onclick' => 'app.dialog("close")',
                    )),
                ),
            ),
            'onsuccess' => 'app.dialog("close"); app.lists["h-gitter-projects-list"] && app.lists["h-gitter-projects-list"].refresh();'
        ));

        switch($form->submitted()) {
            case false :
                $this->addJavaScript($this->getPlugin()->getJsUrl('edit-project.js'));
                $this->addCss($this->getPlugin()->getCssUrl('edit-project.less'));

                $this->addKeysToJavascript(
                    $this->_plugin . '.remove-project-user-confirm'
                );

                return Dialogbox::make(array(
                    'title' => Lang::get($this->_plugin . '.edit-project-title', null, $this->projectId),
                    'icon' => 'git-square',
                    'page' => $form->display()
                ));

            case 'delete' :
                $form->delete(false);

                // remove the folder of the project
                App::fs()->remove($project->getDirname());

                // Remove the avatar filename if exists
                if(is_file($project->getAvatarFilename())) {
                    App::fs()->remove($project->getAvatarFilename());
                }

                return $form->response(Form::STATUS_SUCCESS);

            default :
                $id = $form->treat(false);
                $upload = Upload::getInstance('avatar');

                if($upload) {
                    $basename = $form->object->getAvatarBasename();
                    $dirname = $this->getPlugin()->getPublicUserfilesDir();

                    $upload->move($upload->getFile(), $dirname, $basename);
                }

                if(!$project) {
                    $folderName = 'project-' . $id;
                    // Create the folder for the project in the plugin userfiles folder
                    mkdir($this->getPlugin()->getUserfile($folderName));
                }
                return $form->response(Form::STATUS_SUCCESS);
        }
    }
}