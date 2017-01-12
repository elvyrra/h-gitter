<?php

namespace Hawk\Plugins\HGitter;

class TagController extends Controller {
    /**
     * Display the list of the repository tags
     * @return string The HTML response
     */
    public function index() {
        $repo = Repo::getById($this->repoId);

        $tags = array_map(function($tag) use($repo) {
            $info = $repo->getCommitInformation($tag, false);
            $info->name = $tag;

            return $info;
        }, $repo->getTags());



        $list = new ItemList(array(
            'id' => 'h-gitter-tags-list',
            'data' => $tags,
            'sorts' => array(
                'date' => DB::SORT_DESC
            ),
            'navigation' => false,
            'lines' => ItemList::ALL_LINES,
            'controls' => array(
                array(
                    'icon' => 'plus',
                    'class' => 'btn-success',
                    'label' => Lang::get($this->_plugin . '.new-tag-btn'),
                    'href' => App::router()->getUri('h-gitter-repo-tag', array(
                        'repoId' => $this->repoId,
                        'tag' => '$'
                    )),
                    'target' => 'dialog'
                )
            ),
            'fields' => array(
                'name' => array(),

                'author' => array(
                    'sort' => false,
                    'search' => false
                ),

                'date' => array(
                    'display' => function($value) {
                        return Utils::timeAgo($value);
                    },
                    'search' => false,
                    'sort' => false
                ),

                'actions' => array(
                    'independant' => true,
                    'search' => false,
                    'sort' => false,
                    'display' => function($value, $field, $line) {
                        return ButtonInput::getInstance(array(
                            'label' => Lang::get($this->_plugin . '.repo-commits-browse-files'),
                            'icon' => 'files-o',
                            'href' => App::router()->getUri('h-gitter-repo-code-folder', array(
                                'repoId' => $this->repoId,
                                'type' => 'tag',
                                'revision' => $value
                            ))
                        )) .

                        ButtonInput::getInstance(array(
                            'icon' => 'trash',
                            'class' => 'btn-danger delete-tag',
                            'attributes' => array(
                                'data-tag' => $line->name
                            )
                        ));
                    }
                )
            )
        ));

        $content = $list->display();

        if($list->isRefreshing()) {
            return $content;
        }

        $this->addJavaScript($this->getPlugin()->getJsUrl('tags.js'));
        $this->addKeysTojavaScript($this->_plugin . '.confirm-delete-tag');

        return RepoController::getInstance(array(
            'repoId' => $this->repoId
        ))->display('tags', $content);
    }

    public function edit() {
        $repo = Repo::getById($this->repoId);
        $tag = array_pop($repo->getTags($this->tag));
        $branches = $repo->getBranches();

        if(App::request()->getMethod() === 'delete') {
            // Delete the tag
            App::response()->setContentType('json');

            $repo->removeTag($this->tag);

            App::response()->setStatus(204);
            return array();
        }

        $form = new Form(array(
            'id' => 'h-gitter-new-tag-form',
            'fieldsets' => array(
                'form' => array(
                    new TextInput(array(
                        'name' => 'tag',
                        'required' => true,
                        'label' => Lang::get($this->_plugin . '.new-tag-name-label'),
                        'pattern' => '/^[^ ]+$/'
                    )),

                    new SelectInput(array(
                        'name' => 'branch',
                        'required' => true,
                        'default' => $repo->defaultBranch,
                        'label' => Lang::get($this->_plugin . '.new-tag-branch-label'),
                        'options' => array_combine($branches, $branches)
                    ))
                ),
                'submits' => array(
                    new SubmitInput(array(
                        'name' => 'valid',
                        'value' => Lang::get('main.valid-button')
                    )),

                    new ButtonInput(array(
                        'name' => 'cancel',
                        'value' => Lang::get('main.cancel-button'),
                        'onclick' => 'app.dialog("close")'
                    ))
                )
            ),
            'onsuccess' => 'app.dialog("close");app.load(app.tabset.activeTab.uri);'
        ));

        if(!$form->submitted()) {
            return Dialogbox::make(array(
                'title' => Lang::get($this->_plugin . '.new-tag-form-title'),
                'icon' => 'tag',
                'page' => $form->display()
            ));
        }

        if($form->check()) {
            // Check if the tag does not exists yet
            $existingTag = $repo->getTags($form->getData('tag'));

            if(!empty($existingTag)) {
                $form->error('tag', Lang::get($this->_plugin . '.existing-tag-message'));

                return $form->response(Form::STATUS_CHECK_ERROR);
            }

            $repo->tag($form->getData('tag'), $form->getData('branch'));

            return $form->response(Form::STATUS_SUCCESS);
        }
    }
}