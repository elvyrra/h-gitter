<div class="merge-request-score">
    {button icon="thumbs-o-up" label="${likes}" e-click="like" class="btn-success"}
    {button icon="thumbs-o-down" label="${unlikes}" e-click="unlike" class="btn-warning"}
</div>

<div class="discussions">
    <div class="merge-request-discussion panel panel-info" e-each="{$data : discussions, $item : 'discussion'}">
        <div class="panel-heading" e-with="comments[0]">
            <img e-if="$root.getParticipant(userId).avatar" e-attr="{src : $root.getParticipant(userId).avatar}" class="user-avatar x-small" />
            <span class="user-avatar x-small" e-if="!$root.getParticipant(userId).avatar">
                <i class="icon icon-user"></i>
            </span>
            {text key="h-gitter.discussion-opened" username="${$root.getParticipant(userId).username}" ago="${moment(ctime * 1000).fromNow()}"}
        </div>
        <div class="panel-body">
            <div e-template="'merge-request-discussion'"></div>
        </div>
    </div>
</div>

{widget plugin="h-widgets"
        class="CommentForm"
        id="h-gitter-merge-request-new-comment-form"
        action="{uri action='h-gitter-merge-request-comment' repoId='{$repoId}' mergeRequestId='{$mergeRequestId}' commentId='0'}"
}
