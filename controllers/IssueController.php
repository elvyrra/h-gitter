<?php

namespace Hawk\Plugins\HGitter;

use \Hawk\Plugins\HTracker as HTracker;

class IssueController extends Controller {
    private function init() {
        $htracker = Plugin::get('h-tracker');

        if(!$htracker || !$htracker->isInstalled()) {
            throw new InternalErrorException('The plugin "H-tracker" needs to be installed to display this page.');
        }

        $repo = Repo::getById($this->repoId);

        // Check if the project exists on h-tracker, else create it
        $project = HTracker\Project::getByExample(new DBExample(array(
            'name' => $repo->name
        )));

        if(!$project) {
            $project = new HTracker\Project(array(
                'name' => $repo->name,
                'description' => $repo->description,
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

        // Get the list of the project tasks

        // Listen on list instanciation to modify it after
        $list = null;
        Event::on('list.htracker-ticket-list.instanciated', function(Event $event) use(&$list){
            $list = $event->getData('list');
        });

        HTracker\TicketController::getInstance()->index();

        $list->id = 'h-gitter-issues-list';

        // Update the list filter to force to load only the repo tasks
        $list->filter = new DBExample(array(
            'projectId' => $this->_project->id
        ));

        // Update the label of the button to create a new issue
        $list->controls = array(
            array(
                'label' => Lang::get($this->_plugin . '.issue-new-btn'),
                'icon' => 'plus',
                'class' => 'btn-primary',
                'href' => App::router()->getUri('h-gitter-repo-issue', array(
                    'repoId' => $this->repoId,
                    'issueId' => 0
                ))
            )
        );

        // Update the href in the list
        $list->fields['id']->href = function ($value, $field, $ticket) {
            return App::router()->getUri('h-gitter-repo-issue', array(
                'repoId' => $this->repoId,
                'issueId' => $ticket->id
            ));
        };
        $list->fields['title']->href = $list->fields['id']->href;

        $content = $list->display();

        if($list->isRefreshing()) {
            return $content;
        }

        return RepoController::getInstance(array(
            'repoId' => $this->repoId
        ))->display('issues', $content);

    }


    /**
     * Create / Edit / Remove an issue
     */
    public function edit() {
        $this->init();

        // Listen on list instanciation to modify it after
        $form = null;

        $controller = HTracker\TicketController::getInstance(array('ticketId' => $this->issueId));
        $result = $controller->edit();

        $form = Form::getInstance('htracker-ticket-form');

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

            $content = View::make(Plugin::get('h-tracker')->getView("ticket-form.tpl"), array(
                'form' => $form,
                'history' => $controller->history()
            ));

            return RepoController::getInstance(array(
                'repoId' => $this->repoId
            ))->display('issues', $content);
        }

        return $result;
    }
}