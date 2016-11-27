<?php

namespace Hawk\Plugins\HGitter;

class MergeRequestCommentController extends Controller {
    /**
     * Create / Edit / remove a comment
     */
    public function edit() {
        switch(App::request()->getMethod()) {
            case 'delete' :
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

            case 'get' :
                return \Hawk\Plugins\HWidgets\CommentForm::getInstance(array(
                    'id' => 'h-gitter-merge-request-comment-form-' . uniqid(),
                    'action' => App::router()->getUri(
                        'h-gitter-merge-request-comment',
                        array(
                            'repoId' => $this->repoId,
                            'mergeRequestId' => $this->mergeRequestId,
                            'commentId' => 0
                        ),
                        array(
                            'file' => App::request()->getParams('file'),
                            'line' => App::request()->getParams('line'),
                            'parentId' => App::request()->getParams('parentId')
                        )
                    )
                ))->display();

            default :
                App::response()->setContentType('json');
                $comment = new MergeRequestComment(array(
                    'mergeRequestId' => $this->mergeRequestId,
                    'userId' => App::session()->getUser()->id,
                    'file' => App::request()->getParams('file'),
                    'line' => App::request()->getParams('line'),
                    'parentId' => App::request()->getParams('parentId'),
                    'comment' => App::request()->getBody('content'),
                    'ctime' => time()
                ));

                $comment->save();


                $repo = Repo::getById($this->repoId);
                $mr = MergeRequest::getById($this->mergeRequestId);

                $subject = Lang::get($this->_plugin . '.new-comment-subject-subject', array(
                    'author' => App::session()->getUser()->username,
                    'id' => $mr->id
                ));
                $content = View::make($this->getPLugin()->getView('notifications/new-comment.tpl'), array(
                    'author' => App::session()->getUser()->username,
                    'mrID' => $mr->id,
                    'comment' => $comment->comment,
                    'repoId' => $repo->id
                ));

                $recipients = $mr->getParticipants();

                $email = new Mail();
                $email  ->subject($subject)
                        ->content($content)
                        ->to(array_map(function($user) {
                            return $user->email;
                        }, $recipients))
                        ->send();

                return array(
                    'data' => $comment
                );
        }
    }
}