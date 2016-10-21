<?php

namespace Hawk\Plugins\HGitter;

class ProjectController extends Controller {
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
            'fields' => array(
                'name' => array(
                    'label' => Lang::get($this->_plugin . '.projects-list-name-label'),
                    'href' => function($value, $field, $project) {
                        return App::router()->getUri('h-gitter-project-repos', array('projectId' => $project->id));
                    },
                ),

                'description' => array(
                    'label' => Lang::get($this->_plugin . '.projects-list-description-label')
                ),

                'ctime' => array(
                    'label' => Lang::get($this->_plugin . '.projects-list-ctime-label'),
                    'display' => function($value) {
                        return date(Lang::get('main.date-format'), $value);
                    }
                ),

                'userId' => array(
                    'label' => Lang::get($this->_plugin . '.projects-list-userId-label'),
                    'display' => function($value) {
                        return User::getById($value)->username;
                    }
                )
            )
        ));

        if($list->isRefreshing()) {
            return $list->display();
        }

        return NoSidebarTab::make(array(
            'icon' => 'git-square',
            'title' => Lang::get($this->_plugin . '.projects-list-title'),
            'page' => $list->display()
        ));
    }

    public function edit() {
        if(empty($this->projectId)) {
            if(!App::session()->isAllowed($this->_plugin . '.create-projects')) {
                throw new ForbiddenException();
            }
        }
        else {
            $project = Project::getById($this->projectId);

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

                    new TextareaInput(array(
                        'name' => 'description',
                        'rows' => '3',
                        'required' => true,
                        'maxlength' => 4096,
                        'label' => Lang::get($this->_plugin . '.edit-project-description-label')
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
                        'default' => '{}',
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
                        'value' => View::make($this->getPlugin()->getView('project-privileges.tpl'))
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

        if(!$form->submitted()) {
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
        }
        else {
            return $form->treat();
        }
    }
}