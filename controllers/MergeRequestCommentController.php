<?php

namespace Hawk\Plugins\HGitter;

class MergeRequestCommentController extends Controller {
    /**
     * Create / Edit / remove a comment
     */
    public function edit() {
        if(App::request()->getMethod() === 'delete') {
            // Remove the comment
            $comment = MergeRequestComment::getByExample(new DBExample(array(
                'id' => $this->commentId,
                'mergeRequestId' => $this->mergeRequestId,
            )));

            if(!$comment) {
                throw new PageNotFoundException('', array(
                    'resource' => 'merge-request-comment',
                    'resourceId' => $this->commentId
                ));
            }

            $comment->delete();

            MergeRequestComment::deleteByExample(new DBExample(array(
                'mergeRequestId' => $this->mergeRequestId,
                'parentId' => $this->commentId
            )));

            App::response()->setStatus(204);

            return;
        }


        $form = new Form(array(
            'id' => 'h-gitter-merge-request-comment-form-' . uniqid(),
            'class' => 'h-gitter-merge-request-comment-form',
            'action' => App::router()->getUri('h-gitter-merge-request-comment', array(
                'repoId' => $this->repoId,
                'mergeRequestId' => $this->mergeRequestId,
                'commentId' => empty($this->commentId) ? 0 : $this->commentId
            )),
            'model' => 'MergeRequestComment',
            'reference' => array(
                'id' => $this->commentId
            ),
            'fieldsets' => array(
                'form' => array(
                    new HiddenInput(array(
                        'name' => 'mergeRequestId',
                        'value' => $this->mergeRequestId
                    )),

                    new HiddenInput(array(
                        'name' => 'userId',
                        'value' => App::session()->getUser()->id
                    )),

                    new HiddenInput(array(
                        'name' => 'file',
                        'default' => App::request()->getParams('file')
                    )),

                    new HiddenInput(array(
                        'name' => 'line',
                        'default' => App::request()->getParams('line')
                    )),

                    new HiddenInput(array(
                        'name' => 'parentId',
                        'default' => App::request()->getParams('parentId')
                    )),

                    new HiddenInput(array(
                        'name' => 'ctime',
                        'default' => time()
                    )),

                    new TextareaInput(array(
                        'name' => 'comment',
                        'placeholder' => Lang::get($this->_plugin . '.comment-form-comment-placeholder'),
                        'required' => true,
                        'rows' => 5,
                        'cols' => 50
                    )),
                ),

                'submits' => array(
                    new SubmitInput(array(
                        'name' => 'valid',
                        'icon' => 'pencil',
                        'value' => lang::get($this->_plugin . '.comment-form-submit-btn')
                    )),

                    new ButtonInput(array(
                        'name' => 'cancel',
                        'value' => Lang::get('main.cancel-button'),
                        'attributes' => array(
                            'e-click' => 'function() { commentFormDisplayed = false; }'
                        )
                    ))
                )
            )
        ));

        if(!$form->submitted()) {
            return $form->display();
        }
        elseif($form->check()) {
            $form->register(false);

            $form->addReturn($form->object);

            return $form->response(Form::STATUS_SUCCESS);
        }
    }
}