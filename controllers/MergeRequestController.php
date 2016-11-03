<?php

namespace Hawk\Plugins\HGitter;

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
            'filter' => new DBExample(array(
                'repoId' => $this->repoId
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
                            'date' => date(Lang::get('main.time-format'), $mr->ctime),
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


        if($list->isRefreshing()) {
            return $list->display();
        }

        $this->mergeRequestId = 0;

        $content = View::make($this->getPlugin()->getView('merge-requests/list.tpl'), array(
            'list' => $list,
            'form' => $this->edit(),
            'open' => App::request()->getParams('branch')
        ));

        $this->addKeysToJavaScript($this->_plugin . '.delete-merge-request-confirmation');

        return RepoController::getInstance(array(
            'repoId' => $this->repoId
        ))->display('merge-requests', $content);
    }

    /**
     * Create / Edit / Delete a merge request
     */
    public function edit() {
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

            App::response()->setStatus(204);

            return;
        }

        $repo = Repo::getById($this->repoId);

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
            'inputs' => array(
                new HiddenInput(array(
                    'name' => 'repoId',
                    'value' => $this->repoId
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

                new SubmitInput(array(
                    'name' => 'valid',
                    'value' => Lang::get('main.valid-button'),
                    'attributes' => array(
                        'e-disabled' => '!valid'
                    )
                )),

                new ButtonInput(array(
                    'name' => 'cancel',
                    'value' => Lang::get('main.cancel-button'),
                    'notDisplayed' => !$this->mergeRequestId,
                ))
            ),
            'onsuccess' => 'app.load(app.getUri("h-gitter-repo-display-merge-request", {
                repoId : ' . $this->repoId . ',
                mergeRequestId : data.primary
            }));'
        ));

        if(!$form->submitted()) {
            // $this->addJavaScript($this->getPlugin()->getJsUrl('merge-request-form.js'));

            return $form;
        }

        if($form->check()) {

            if($form->getData('from') === $form->getData('to')) {
                $form->error('from', Lang::get($this->_plugin . '.merge-request-same-branches-error'));

                return $form->response(Form::STATUS_CHECK_ERROR);
            }

            if(!$this->mergeRequestId) {
                $commit = $repo->getCommitInformation($form->getData('from'), false);

                $form->setData(array(
                    'title' => $commit->message
                ));
            }
            return $form->register();
        }
    }


    /**
     * Display a merge request
     * @returns string The HTML Response
     */
    public function display() {}
}