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
                'date' => date(Lang::get('main.time-format'), (int) $info->date),
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
            'controls' => array(
                array (
                    'icon' => 'plus',
                    'class' => 'btn-primary',
                    'label' => Lang::get($this->_plugin . '.new-branch-btn')
                )
            ),
            'fields' => array(
                'name' => array(
                    'display' => function($value, $field, $branch) use ($repo){
                        return View::make($this->getPlugin()->getView('repo-branches-name.tpl'), array(
                            'branch' => $branch,
                            'repo' => $repo
                        ));
                    }
                ),

                'ahead' => array (
                    'display' => function($value, $field, $branch) {
                        return View::make($this->getPLugin()->getView('repo-branches-diff.tpl'), array(
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
                                'class' => 'btn-danger pull-right',
                                'title' => Lang::get($this->_plugin . '.delete-branch-title')
                            ));

                            if(!$branch->merged && $branch->behind) {
                                $result .= new ButtonInput(array(
                                    'icon' => 'code-fork icon-flip-vertical',
                                    'class' => 'pull-right',
                                    'label' => Lang::get($this->_plugin . '.new-merge-request-btn')
                                ));
                            }
                        }

                        return $result;
                    }
                )
            )
        ));

        $content = $list->display();

        return RepoController::getInstance(array(
            'repoId' => $this->repoId
        ))->display('branches', $content);
    }


    /**
     * Create a new branch on the repository
     * @return array The result as array
     */
    public function create() {

    }

    /**
     * Delete a branch of the repository
     */
    public function delete() {

    }
}