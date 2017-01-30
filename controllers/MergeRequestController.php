<?php

namespace Hawk\Plugins\HGitter;

use \Hawk\Plugins\HTracker as HTracker;

class MergeRequestController extends Controller {
    /**
     * Display the list of the merge requests on the repository
     * @returns string The HTML response
     */
    public function index() {
        $repo = Repo::getById($this->repoId);

        $list = new ItemList(array(
            'id' => 'h-gitter-merge-requests-list',
            'navigation' => false,
            'noHeader' => true,
            'model' => 'MergeRequest',
            'controls' => array(
                array(
                    'icon' => 'plus',
                    'class' => 'btn-primary',
                    'label' => Lang::get($this->_plugin . '.new-merge-request-btn'),
                    'href' => App::router()->getUri('h-gitter-repo-merge-request', array(
                        'repoId' => $this->repoId,
                        'mergeRequestId' => 0
                    )),
                    'target' => 'dialog'
                )
            ),
            'filter' => new DBExample(array(
                'repoId' => $this->repoId,
                'merged' => 0
            )),
            'sorts' => array(
                'ctime' => DB::SORT_DESC
            ),
            'fields' => array(
                'ctime' => array(
                    'hidden' => true
                ),

                'userId' => array(
                    'hidden' => true
                ),

                'from' => array(
                    'hidden' => true
                ),

                'title' => array(
                    'display' => function($value, $field, $mr) use($repo){
                        return View::make($this->getPlugin()->getView('merge-requests/list-name-cell.tpl'), array(
                            'repo' => $repo,
                            'mr' => $mr,
                            'date' => Utils::timeAgo($mr->ctime),
                            'username' => User::getById($mr->userId)->username
                        ));
                    }
                ),

                'action' => array(
                    'independant' => true,
                    'display' => function($value, $field, $mr) use ($repo) {
                        return  new ButtonInput(array(
                            'icon' => 'trash',
                            'class' => 'btn-danger delete-merge-request pull-right',
                            'label' => Lang::get('main.delete-button'),
                            'href' => App::router()->getUri('h-gitter-repo-merge-request', array(
                                'repoId' => $this->repoId,
                                'mergeRequestId' => $mr->id
                            ))
                        ));
                    }
                )
            )
        ));

        $content = $list->display();

        if($list->isRefreshing()) {
            return $content;
        }

        $this->addKeysToJavaScript($this->_plugin . '.delete-merge-request-confirmation');

        return RepoController::getInstance(array(
            'repoId' => $this->repoId
        ))->display('merge-requests', $content);
    }

    /**
     * Create / Edit / Delete a merge request
     */
    public function edit() {
        $repo = Repo::getById($this->repoId);

        if(App::request()->getMethod() === 'delete') {
            // Delete the merge request
            $mr = MergeRequest::getById($this->mergeRequestId);

            if(!$mr) {
                throw new PageNotFoundException('', array(
                    'resource' => 'MergeRequest',
                    'resourceId' => $this->mergeRequestId
                ));
            }

            $mr->delete();

            $subject = Lang::get($this->_plugin . '.merge-request-deleted-subject', array(
                'repo' => $repo->name,
                'id' => $form->object->id
            ));
            $content = View::make($this->getPLugin()->getView('notifications/merge-request-deleted.tpl'), array(
                'author' => User::getById($mr->userId)->username,
                'mrId' => $mr->id
            ));

            $recipients = $mr->getParticipants(array(
                App::session()->getUser()->id
            ));


            if(!empty($recipients)) {
                $email = new Mail();
                $email  ->subject($subject)
                        ->content($content)
                        ->to(array_map(function($user) {
                            return $user->email;
                        }, $recipients))
                        ->send();
            }

            App::response()->setStatus(204);

            return;
        }


        $branch = App::request()->getParams('branch');

        $title = '';
        if($branch) {
            $commit = $repo->getCommitInformation($branch, false);
            $title = $commit->message;
        }

        $branches = $repo->getBranches();
        $branches = array_combine($branches, $branches);

        $form = new Form(array(
            'id' => 'h-gitter-merge-request-form',
            'model' => 'MergeRequest',
            'reference' => array(
                'id' => $this->mergeRequestId
            ),
            'action' => App::router()->getUri('h-gitter-repo-merge-request', array(
                'repoId' => $this->repoId,
                'mergeRequestId' => $this->mergeRequestId
            )),
            'fieldsets' => array(
                'form' => array(
                    new HiddenInput(array(
                        'name' => 'repoId',
                        'value' => $this->repoId
                    )),

                    new SelectInput(array(
                        'name' => 'from',
                        'required' => true,
                        'label' => Lang::get($this->_plugin . '.merge-request-form-from-label'),
                        'default' => $branch ? $branch : '',
                        'invitation' => ' - ',
                        'options' => $branches,
                        'attributes' => array(
                            'e-value' => 'sourceBranch'
                        )
                    )),

                    new SelectInput(array(
                        'name' => 'to',
                        'required' => true,
                        'nl' => false,
                        'labelWidth' => 'auto',
                        'invitation' => ' - ',
                        'default' => $repo->defaultBranch,
                        'label' => Lang::get($this->_plugin . '.merge-request-form-to-label'),
                        'options' => $branches,
                        'attributes' => array(
                            'e-value' => 'toBranch'
                        )
                    )),

                    new TextInput(array(
                        'name' => 'title',
                        'default' => $title,
                        'required' => $this->mergeRequestId,
                        'label' => Lang::get($this->_plugin . '.merge-request-form-title-label'),
                        'attributes' => array(
                            'e-value' => 'title'
                        )
                    )),

                    new \Hawk\Plugins\HWidgets\MarkdownInput(array(
                        'name' => 'description',
                        'label' => Lang::get($this->_plugin . '.merge-request-form-description-label'),
                        'labelWidth' => 'auto',
                    ))
                ),

                'submits' => array(
                    new SubmitInput(array(
                        'name' => 'valid',
                        'value' => Lang::get('main.valid-button'),
                        'attributes' => array(
                            'e-disabled' => '!available'
                        )
                    )),

                    new ButtonInput(array(
                        'name' => 'cancel',
                        'value' => Lang::get('main.cancel-button'),
                        'onclick' => 'app.dialog("close")'
                    ))
                ),
            ),
            'onsuccess' => '
                app.dialog("close");
                app.load(app.getUri("h-gitter-repo-display-merge-request", {
                    repoId : ' . $this->repoId . ',
                    mergeRequestId : data.primary
                }));'
        ));

        if(!$form->submitted()) {
            $this->addJavaScript($this->getPlugin()->getJsUrl('merge-request-form.js'));

            return Dialogbox::make(array(
                'title' => Lang::get($this->_plugin . '.merge-request-form-title'),
                'icon' => 'code-fork icon-flip-horizontal',
                'page' => $form->display()
            ));
        }

        if($form->check()) {
            if(!is_dir($repo->getCloneRepoDirname())) {
                Git::cloneRemote($repo->path, $repo->getCloneRepoDirname());
            }

            $repo->getCloneRepo()->fetch();

            if(!$repo->canMerge($form->getData('from'), $form->getData('to'))) {
                $form->error('from', Lang::get($this->_plugin . '.merge-request-branches-error', array('to' => $this->getData('to'))));

                return $form->response(Form::STATUS_CHECK_ERROR);
            }

            // Register the merge request data
            $form->register(false);

            // Send an email notification to the repository participants
            $mr = $form->object;
            if($form->new) {
                $subject = Lang::get($this->_plugin . '.new-merge-request-subject', array(
                    'repo' => $repo->name,
                    'id' => $mr->id
                ));
                $content = View::make($this->getPLugin()->getView('notifications/new-merge-request.tpl'), array(
                    'author' => User::getById($mr->userId)->username,
                    'project' => Project::getById($repo->projectId)->name,
                    'repo' => $repo->name,
                    'title' => $mr->title,
                    'repoId' => $repo->id,
                    'mrId' => $mr->id
                ));

                $recipients = $repo->getUsers();
            }
            else {
                $subject = Lang::get($this->_plugin . '.merge-request-modification-subject', array(
                    'repo' => $repo->name,
                    'id' => $form->object->id
                ));
                $content = View::make($this->getPLugin()->getView('notifications/merge-request-modification.tpl'), array(
                    'author' => User::getById($mr->userId)->username,
                    'title' => $mr->title,
                    'repoId' => $repo->id,
                    'mrId' => $mr->id
                ));

                $recipients = $mr->getParticipants(array(
                    App::session()->getUser()->id
                ));
            }


            if(!empty($recipients)) {
                $email = new Mail();
                $email  ->subject($subject)
                        ->content($content)
                        ->to(array_map(function($user) {
                            return $user->email;
                        }, $recipients))
                        ->send();
            }

            return $form->response(Form::STATUS_SUCCESS);
        }
    }

    /**
     * Check the availability to merge two branches, and returns the title of the merge request
     * @return array An array contaning two keys : 'available' and 'title'
     */
    public function availabability() {
        $repo = Repo::getById($this->repoId);
        App::response()->setContentType('json');

        $canMerge = $repo->canMerge($this->from, $this->to);
        $title = '';

        if($canMerge) {
            $commit = $repo->getCommitInformation($this->from, false);
            $title = $commit->message;
        }

        return array(
            'available' => $canMerge,
            'title' => $title
        );
    }


    /**
     * Display a merge request
     * @returns string The HTML Response
     */
    public function display() {
        $repo = Repo::getById($this->repoId);

        if(!is_dir($repo->getCloneRepoDirname())) {
            Git::cloneRemote($repo->path, $repo->getCloneRepoDirname());
        }

        $mr = MergeRequest::getByExample(new DBExample(array(
            'id' => $this->mergeRequestId,
            'repoId' => $this->repoId
        )));

        if(!$mr) {
            throw new PageNotFoundException('', array(
                'resource' => 'merge-request',
                'id' => $this->mergeRequestId
            ));
        }

        $parser = new Parsedown();

        $mr->description = $parser->text($mr->description);

        $author = User::getById($mr->userId);

        $mr->formattedDate = Utils::timeAgo($mr->ctime);

        $commit = $repo->getCommitInformation($mr->from, false);

        $acceptForm = $this->accept();

        switch (App::request()->getParams('section')) {
            case 'diff':
                $diff = $repo->getDiff($mr->to, $mr->from);
                $diffNumber = count($diff['differences']);

                $discussionsTab = array(
                    'content' => '',
                    'href' => App::router()->getUri(
                        'h-gitter-repo-display-merge-request',
                        array(
                            'repoId' => $this->repoId,
                            'mergeRequestId' => $this->mergeRequestId
                        ),
                        array(
                            'section' => 'disucssions'
                        )
                    )
                );

                $diffTab = array(
                    'href' => '',
                    'content' => View::make($this->getPlugin()->getView('merge-requests/diff.tpl'), array(
                        'commit' => $commit,
                        'repo' => $repo,
                        'mr' => $mr,
                        'diff' => $diff
                    )),
                );

                $this->addCss('//cdnjs.cloudflare.com/ajax/libs/highlight.js/9.9.0/styles/monokai-sublime.min.css');

                break;

            default:
                $commentController = MergeRequestCommentController::getInstance(array(
                    'repoId' => $this->repoId,
                    'mergeRequestId' => $this->mergeRequestId,
                    'commentId' => 0
                ));
                $discussionsTab = array(
                    'content' => View::make($this->getPlugin()->getView('merge-requests/discussions.tpl'), array(
                        'repoId' => $this->repoId,
                        'mergeRequestId' => $this->mergeRequestId
                    )),
                    'href' => ''
                );

                $diffTab = array(
                    'href' => App::router()->getUri(
                        'h-gitter-repo-display-merge-request',
                        array(
                            'repoId' => $this->repoId,
                            'mergeRequestId' => $this->mergeRequestId
                        ),
                        array(
                            'section' => 'diff'
                        )
                    ),
                    'content' => ''
                );

                $diffNumber = count(explode(PHP_EOL, trim($repo->diff($mr->to, $mr->from, '--name-status'))));

                break;
        }

        $discussionsTab['title'] = Lang::get($this->_plugin . '.merge-request-discussions-tab-title');
        $diffTab['title'] = Lang::get($this->_plugin . '.merge-request-diff-tab-title') . ' <span class="badge">' . $diffNumber . '</span>';

        // Get merge request discussion
        $comments = $mr->getComments();

        // Build the merge request particpants
        $participants = array_map(function($participant) {
            $user = User::getById($participant);

            return array(
                'id' => $participant,
                'username' => $user->username,
                'avatar' => $user->getProfileData('avatar')
            );
        }, $mr->participants);


        $this->addKeysToJavaScript($this->_plugin . '.delete-merge-request-confirmation');
        $this->addJavaScript($this->getPlugin()->getJsUrl('merge-request.js'));

        $content = View::make($this->getPlugin()->getView('merge-requests/merge-request.tpl'), array(
            'repo' => $repo,
            'mr' => $mr,
            'author' => $author,
            'acceptForm' => $acceptForm,
            'tabs' => array(
                'dicussions' => $discussionsTab,
                'diff' => $diffTab
            ),
            'comments' => json_encode($comments, JSON_NUMERIC_CHECK),
            'participants' => json_encode($participants, JSON_NUMERIC_CHECK),
        ));

        return RepoController::getInstance(array(
            'repoId' => $this->repoId
        ))->display('merge-requests', $content);
    }


    public function fileDiff() {
        $repo = Repo::getById($this->repoId);
        $mr = MergeRequest::getByExample(new DBExample(array(
            'id' => $this->mergeRequestId,
            'repoId' => $this->repoId
        )));


        $diff = $repo->getDiff($mr->to, $mr->from, $this->path);

        return View::make($this->getPlugin()->getView('diff/file-diff.tpl'), array(
            'fileDiffs' => $diff['differences'][$this->path]
        ));
    }



    /**
     * Accept a merge request
     */
    public function accept() {
        $repo = Repo::getById($this->repoId);
        $mr = MergeRequest::getByExample(new DBExample(array(
            'id' => $this->mergeRequestId,
            'repoId' => $this->repoId
        )));

        if(!$mr) {
            throw new PageNotFoundException('', array(
                'resource' => 'merge-request',
                'id' => $this->mergeRequestId
            ));
        }

        $form = new Form(array(
            'id' => 'accept-merge-request-form',
            'action' => App::router()->getUri('h-gitter-accept-merge-request', array(
                'repoId' => $this->repoId,
                'mergeRequestId' => $this->mergeRequestId
            )),
            'class' => 'form-inline',
            'inputs' => array(
                new SubmitInput(array(
                    'name' => 'valid',
                    'icon' => 'check',
                    'nl' => false,
                    'value' => Lang::get($this->_plugin . '.merge-request-accept-btn'),
                    'notDisplayed' => !$mr->isAcceptable(),
                    'class' => 'pull-left'
                )),
                new CheckboxInput(array(
                    'name' => 'removeSourceBranch',
                    'nl' => false,
                    'label' => Lang::get($this->_plugin . '.merge-request-delete-source-branch-label'),
                    'notDisplayed' => !$mr->isAcceptable(),
                    'labelWidth' => 'auto'
                )),
            ),
            'onsuccess' => 'app.load(app.getUri("h-gitter-repo-merge-requests", {repoId : ' . $this->repoId . '}));'
        ));

        if(!$form->submitted() && $repo->isUserMaster()) {
            // Display the merge request
            return $form->display();
        }

        if(!$repo->isUserMaster()) {
            throw new ForbiddenException('You don\'t have necessary privileges to merge this request');
        }

        if(!$mr->isAcceptable()) {
            throw new ForbiddenException('This merge request is not acceptable');
        }

        // Merge the source branch. The merge operation is computed on the clone repository, then pushed on the main repository
        $clone = $repo->getCloneRepo();

        $clone->checkout($mr->to);
        $clone->merge($mr->from);

        if($form->getData('removeSourceBranch')) {
            // Remove the source branches on the clone and the main repositories
            $clone->deleteBranch($mr->from, true);
            $repo->deleteBranch($mr->from, true);
        }

        // Push the target branch on the main repository
        $clone->checkout($mr->to);
        $clone->push();

        $mr->merged = 1;
        $mr->save();

        // Send the notification to all of mr participants
        $subject = Lang::get($this->_plugin . '.merge-request-accepted-subject', array(
            'repo' => $repo->name,
            'id' => $mr->id
        ));
        $content = View::make($this->getPLugin()->getView('notifications/merge-request-accepted.tpl'), array(
            'author' => User::getById($mr->userId)->username,
            'mrId' => $mr->id
        ));

        $recipients = $mr->getParticipants(array(
            App::session()->getUser()->id
        ));

        if(!empty($recipients)) {
            $email = new Mail();
            $email  ->subject($subject)
                    ->content($content)
                    ->to(array_map(function($user) {
                        return $user->email;
                    }, $recipients))
                    ->send();
        }

        // Check if an issue is attached to this merge request, and in this case, close it
        if(preg_match_all('/\#(\d+)(?:\b|$)/', $mr->title, $matches, PREG_SET_ORDER)) {
            foreach($matches as $match) {
                $issueId = $match[1];
                $issue = HTracker\Ticket::getById($issueId);

                if($issue) {
                    // An issue is attached to the merge request, close it
                    $issue->status = HTracker\Ticket::STATUS_CLOSED_ID;

                    $issue->save();
                }
            }
        }

        return $form->response(Form::STATUS_SUCCESS);
    }


    public function score() {
        App::response()->setContentType('json');

        $mr = MergeRequest::getByExample(new DBExample(array(
            'id' => $this->mergeRequestId,
            'repoId' => $this->repoId
        )));

        $scores = json_decode($mr->scores, true);

        if(!empty($scores[App::session()->getUser()->id]) && $scores[App::session()->getUser()->id] === $this->score) {
            unset($scores[App::session()->getUser()->id]);
        }
        else {
            $scores[App::session()->getUser()->id] = $this->score;
        }

        $mr->scores = json_encode($scores);

        $mr->save();

        $mr->addComment(Lang::get($this->_plugin . '.score-comment', array(
            'username' => App::session()->getUser()->username,
            'score' => $this->score
        )));

        return $scores;
    }
}