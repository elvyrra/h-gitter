<div class="merge-request-score">
    {button icon="thumbs-o-up" label="${likes}" e-click="like" class="btn-success"}
    {button icon="thumbs-o-down" label="${unlikes}" e-click="unlike" class="btn-warning"}
</div>


<div class="merge-request-discussion panel panel-info" e-each="{$data : discussions, $item : 'discussion'}">
    <div class="panel-heading" e-with="comments[0]">
        <img e-if="$root.getParticipant(userId).avatar" e-attr="{src : $root.getParticipant(userId).avatar}" class="user-avatar small" />
        <span class="user-avatar small" e-if="!$root.getParticipant(userId).avatar">
            <i class="icon icon-user"></i>
        </span>
        {text key="h-gitter.discussion-opened" username="${$root.getParticipant(userId).username}" ago="${moment(ctime * 1000).fromNow()}"}
    </div>
    <dvi class="panel-body">
        <div e-template="'merge-request-discussion'"></div>
    </dvi>
</div>

<template id="merge-request-comment-form-template">
    {{ $commentController->edit() }}
</template>

<input type="text" class="form-control new-comment-input" placeholder="{text key='h-gitter.discussion-new-btn'}" e-click="$root.displayNewCommentForm.bind($root)" e-show="!$root.commentFormDisplayed" />
<div class="response-wrapper col-xs-12" e-show="$root.commentFormDisplayed">
    <div e-template="commentForm"></div>
</div>