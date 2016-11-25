<?php

namespace Hawk\Plugins\HGitter;

class BranchController extends Controller {
    public function index() {
        $repo = Repo::getById($this->repoId);

        $branches = array_map(function($branch) use ($repo) {
            $isDefaultBranch = $branch === $repo->defaultBranch;

            if(!$isDefaultBranch) {
                $ahead = (int) trim($repo->run('rev-list ' . $branch . '..' . $repo->defaultBranch . ' --count --no-merges'));
                $behind = (int) trim($repo->run('rev-list ' . $repo->defaultBranch . '..' . $branch . ' --count --no-merges'));
            }
            else {
                $ahead = 0;
                $behind = 0;
            }

            $info = $repo->getCommitInformation($branch, false);

            $diffTitle = '';
            if($ahead && !$behind) {
                $diffTitle = Lang::get($this->_plugin . '.branch-diff-ahead', array(
                    'ahead' => $ahead,
                    'default' => $repo->defaultBranch
                ));
            }
            elseif($behind && !$ahead) {
                $diffTitle = Lang::get($this->_plugin . '.branch-diff-behind', array(
                    'behind' => $behind,
                    'default' => $repo->defaultBranch
                ));
            }
            elseif($ahead && $behind) {
                $diffTitle = Lang::get($this->_plugin . '.branch-diff-ahead-behind', array(
                    'ahead' => $ahead,
                    'behind' => $behind,
                    'default' => $repo->defaultBranch
                ));
            }

            return (object) array(
                'name' => $branch,
                'default' => $branch === $repo->defaultBranch,
                'ahead' => $ahead,
                'behind' => $behind,
                'merged' => $ahead == 0 && $behind == 0,
                'time' => $info->date,
                'date' => Utils::timeAgo((int) $info->date),
                'author' => $info->author,
                'user' => $info->user,
                'diffTitle' => $diffTitle
            );
        }, $repo->getBranches());

        usort($branches, function($branch1, $branch2) {
            return $branch2->time - $branch1->time;
        });

        $list = new ItemList(array(
            'id' => 'h-gitter-repo-branches-list',
            'navigation' => false,
            'noHeader' => true,
            'data' => $branches,
            'fields' => array(
                'name' => array(
                    'display' => function($value, $field, $branch) use ($repo){
                        return View::make($this->getPlugin()->getView('branches/list-name-cell.tpl'), array(
                            'branch' => $branch,
                            'repo' => $repo
                        ));
                    }
                ),

                'ahead' => array (
                    'display' => function($value, $field, $branch) {
                        return View::make($this->getPLugin()->getView('branches/list-diff-cell.tpl'), array(
                            'branch' => $branch
                        ));
                    }
                ),

                'actions' => array(
                    // 'independant' => true,
                    'display' => function($value, $field, $branch) {
                        $result = '';

                        if(!$branch->default) {
                            $result .= new ButtonInput(array(
                                'icon' => 'trash',
                                'class' => 'btn-danger pull-right delete-branch',
                                'href' => App::router()->getUri('h-gitter-repo-branch', array(
                                    'repoId' => $this->repoId,
                                    'branch' => $branch->name
                                )),
                                'title' => Lang::get($this->_plugin . '.delete-branch-title')
                            ));

                            if(!$branch->merged && $branch->behind) {
                                $result .= new ButtonInput(array(
                                    'icon' => 'code-fork icon-flip-vertical',
                                    'class' => 'pull-right',
                                    'label' => Lang::get($this->_plugin . '.new-merge-request-btn'),
                                    'href' => App::router()->getUri(
                                        'h-gitter-repo-merge-request',
                                        array(
                                            'repoId' => $this->repoId,
                                            'mergeRequestId' => 0
                                        ),
                                        array(
                                            'branch' => $branch->name
                                        )
                                    )
                                ));
                            }
                        }

                        return $result;
                    }
                )
            )
        ));

        if($list->isRefreshing()) {
            return $list->display();
        }

        $this->addKeysToJavaScript($this->_plugin . '.delete-branch-confirmation');

        $content = View::make($this->getPlugin()->getView('branches/list.tpl'), array(
            'form' => $this->edit(),
            'list' => $list
        ));

        return RepoController::getInstance(array(
            'repoId' => $this->repoId
        ))->display('branches', $content);
    }


    /**
     * Create a new branch on the repository
     * @return array The result as array
     */
    public function edit() {
        $repo = Repo::getById($this->repoId);

        $branches = $repo->getBranches();

        $form = new Form(array(
            'id' => 'h-gitter-branch-form',
            'action' => App::router()->getUri('h-gitter-repo-branch', array(
                'repoId' => $this->repoId,
                'branch' => '$'
            )),
            'inputs' => array(
                new TextInput(array(
                    'name' => 'name',
                    'required' => true,
                    'label' => Lang::get($this->_plugin . '.new-branch-form-name-label')
                )),

                new SelectInput(array(
                    'name' => 'from',
                    'required' => true,
                    'default' => 'master',
                    'options' => array_combine($branches, $branches),
                    'label' => Lang::get($this->_plugin . '.new-branch-form-from-label'),
                    'nl' => false
                )),

                new SubmitInput(array(
                    'name' => 'submit',
                    'value' => Lang::get($this->_plugin . '.new-branch-form-submit-label')
                ))
            ),
            'onsuccess' => 'app.tabset.activeTab.reload();'
        ));

        switch($form->submitted()) {
            case false :
                return $form->display();

            case 'delete' :
                $repo->deleteBranch($this->branch, true);

                App::response()->setStatus(204);
                return;

            default :
                if($form->check()){
                    if(in_array($form->getData('name'), $branches)) {
                        $form->error('name', Lang::get($this->_plugin . '.new-branch-form-name-already-exists'));

                        return $form->response(Form::STATUS_CHECK_ERROR);
                    }

                    $repo->createBranch($form->getData('name'), $form->getData('from'));

                    return $form->response(Form::STATUS_SUCCESS);
                }
                break;
        }
    }

    /**
     * Delete a branch of the repository
     */
    public function delete() {

    }
}