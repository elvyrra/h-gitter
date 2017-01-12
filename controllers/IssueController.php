<?php

namespace Hawk\Plugins\HGitter;

use \Hawk\Plugins\HTracker as HTracker;

class IssueController extends Controller {
    private function init() {
        $htracker = Plugin::get('h-tracker');

        if(!$htracker || !$htracker->isInstalled()) {
            throw new InternalErrorException('The plugin "H-tracker" needs to be installed to display this page.');
        }

        $this->_repo = Repo::getById($this->repoId);

        // Check if the project exists on h-tracker, else create it
        $project = HTracker\Project::getByExample(new DBExample(array(
            'name' => $this->_repo->name
        )));

        if(!$project) {
            $project = new HTracker\Project(array(
                'name' => $this->_repo->name,
                'description' => $this->_repo->description,
                'author' => App::session()->getUser()->id,
                'ctime' => time(),
                'mtime' => time()
            ));

            $project->save();
        }

        $this->_project = $project;
    }

    /**
     * Display the list of the issues of repository
     */
    public function index() {
        $this->init();
        $users = array();

        foreach($this->_repo->getUsers() as $user) {
            $users[$user->id] = $user;
        }
        $options = json_decode(Option::get('h-tracker.status'));
        $status = array();
        foreach($options as $option){
            $status[$option->id] = $option->label;
        }

        $filters = IssueFilterWidget::getInstance()->getFilters();
        $filter = array(
            'projectId' => $this->_project->id
        );

        if(!empty($filters['status'])) {
            $filter['status'] = array(
                '$in' => array_keys($filters['status'])
            );
        }

        $param = array(
            'id' => 'h-gitter-issues-list',
            'model' => '\Hawk\Plugins\HTracker\Ticket',
            'filter' => new DBExample($filter),
            'reference' => 'id',
            'controls' => array(
                 array(
                    'label' => Lang::get($this->_plugin . '.issue-new-btn'),
                    'icon' => 'plus',
                    'class' => 'btn-primary',
                    'href' => App::router()->getUri('h-gitter-repo-issue', array(
                        'repoId' => $this->repoId,
                        'issueId' => 0
                    ))
                )
            ),
            'fields' => array(
                'id' => array(
                    'label' => Lang::get('h-tracker.ticket-list-id-label'),
                    'display' => function ($value) {
                        return '#'.$value;
                    },
                    'href' => function ($value, $field, $ticket) {
                        return App::router()->getUri('h-gitter-repo-issue', array(
                            'repoId' => $this->repoId,
                            'issueId' => $ticket->id
                        ));
                    },
                ),

                'title' => array(
                    'label' => Lang::get('h-tracker.ticket-list-title-label'),
                    'href'  => function ($value, $field, $ticket) {
                        return App::router()->getUri('h-gitter-repo-issue', array(
                            'repoId' => $this->repoId,
                            'issueId' => $ticket->id
                        ));
                    },
                ),

                'target' => array(
                    'label'   => Lang::get('h-tracker.ticket-list-target-label'),
                    'display' => function ($value, $field, $ticket) use ($users){
                        if(empty($value)) {
                            return ' - ';
                        }

                        $user = isset($users[$value]) ? $users[$value] : null;
                        if($user) {
                            return $user->username;
                        }
                        else {
                            return ' - ';
                        }
                    },
                    'search'  => array(
                        'type'       => 'select',
                        'invitation' => ' - ',
                        'options'    => array_map(function($user) {
                            return $user->username;
                        }, $users),
                    ),
                ),

                'priority' => array(
                    'label' => Lang::get('h-tracker.ticket-list-priority-label'),
                    'display' => function ($value, $field, $line) {
                        return Lang::get('h-tracker.ticket-priority-'.(string) $value);
                    },
                    'search' => array(
                        'type' => 'select',
                        'invitation' => ' - ',
                        'options' => HTracker\Ticket::getPrioritiesList(),
                    ),
                ),

                'status' => array(
                    'label' => Lang::get('h-tracker.ticket-list-status-label'),
                    'search' => false,
                    'display' => function ($value) use ($status) {
                        return isset($status[$value]) ? $status[$value] : '';
                    },
                ),

                'actions' => array(
                    'independant' => true,
                    'display' => function($value, $field, $issue) {
                        $defaultName = 'h-gitter-issue-' . $issue->id;

                        return ButtonInput::getInstance(array(
                            'value' => Lang::get($this->_plugin . '.create-branch-from-issue-btn'),
                            'icon' => 'code-fork',
                            'href' => App::router()->getUri(
                                'h-gitter-repo-branch', 
                                array(
                                    'repoId' => $this->repoId,
                                    'branch' => '$'
                                ),
                                array(
                                    'name' => $defaultName
                                )
                            ),
                            'target' => 'dialog'
                        ));
                    }
                )
            )
        );


        $list = new ItemList($param);

        $content = $list->display();

        if($list->isRefreshing()) {
            return $content;
        }

        return RepoController::getInstance(array(
            'repoId' => $this->repoId
        ))->display('issues', $content, array(IssueFilterWidget::getInstance()));

    }


    /**
     * Create / Edit / Remove an issue
     */
    public function edit() {
        $this->init();

        // Listen on list instanciation to modify it after
        $form = null;

        $controller = HTracker\TicketController::getInstance(array('ticketId' => $this->issueId));

        Event::on('form.htracker-ticket-form.instanciated', function(Event $e) {
            $form = $e->getData('form');

            if(!$form->submitted()) {

                $form->id = 'h-gitter-issue-form';

                $form->inputs['projectId'] = new HiddenInput(array(
                    'name' => 'projectId',
                    'value' => $this->_project->id
                ));

                $form->inputs['cancel']->href = App::router()->getUri('h-gitter-repo-issues', array(
                    'repoId' => $this->repoId
                ));

                $form->onsuccess = 'app.load(app.getUri("h-gitter-repo-issues", {repoId : ' . $this->repoId . '}))';
            }
        });

        $content = $controller->edit();

        return RepoController::getInstance(array(
            'repoId' => $this->repoId
        ))->display('issues', $content);

        return $result;
    }
}