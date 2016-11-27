<div id="h-gitter-merge-request">
    <div class="row">
        <div class="col-xs-12">
            <h3 class="pull-left">{{ $mr->title }}</h3>
            {button class="btn-primary pull-right" icon="pencil" href="{uri action='h-gitter-repo-merge-request' repoId='{$mr->repoId}' mergeRequestId='{$mr->id}'}" target="dialog"}
            {button class="btn-danger pull-right" icon="trash" title="{text key='h-gitter.merge-request-delete-btn'}"}
        </div>
    </div>
    {if($mr->description)}
        <div>
            {{ $mr->description}}
        </div>
    {/if}

    {assign name="presentationContent"}
        {widget plugin="h-widgets"
                class="MetaData"
                userId="{$author->id}"
                meta="{text key='h-gitter.merge-request-opened-label' username='{$author->username}' date='{$mr->formattedDate}'}"
                description="{text key='h-gitter.merge-request-intro' source='{$mr->from}' to='{$mr->to}'}"
                size="small"
        }

        <hr />

        {if($mr->isAcceptable())}
            {{ $acceptForm }}
        {else}
            <div class="alert alert-warning">
                {icon icon="exclamation-triangle" size="2x"}
                {if($mr->isWip())}
                    {text key="h-gitter.merge-request-is-wip"}
                {/if}
                {if($mr->hasConflicts())}
                    {text key="h-gitter.merge-request-has-conflicts" source="{$mr->from}"}
                {/if}
            </div>
        {/if}
    {/assign}

    {panel type="success" title="{$mr->title}" content="{$presentationContent}" id="commit-details-header"}

    <div role="tabpanel">
        <ul class="nav nav-tabs" role="tablist">
            {foreach($tabs as $i => $tab)}
                <li role="presentation" {if(!$tab['href'])}class="active"{/if}><a {if($tab['href'])}href="{{ $tab['href'] }}"{/if} >{{ $tab['title'] }}</a></li>
            {/foreach}
        </ul>

        <div class="tab-content">
            {foreach($tabs as $i => $tab)}
                <div role="tabpanel" class="tab-pane {if(!$tab['href'])}active{/if}">
                    {{ $tab['content'] }}
                </div>
            {/foreach}
        </div>
    </div>

    <template id="merge-request-discussion">
        <div class="media col-xs-12" e-each="{$data : $discussion.comments, $item : 'comment'}">
            <div class="media-left">
                <img e-if="$root.getParticipant(userId).avatar" e-attr="{src : $root.getParticipant(userId).avatar}" class="user-avatar small" />
                <span class="user-avatar small" e-if="!$root.getParticipant(userId).avatar">
                    <i class="icon icon-user"></i>
                </span>
            </div>
            <div class="media-body">
                <span class="icon icon-trash pull-right pointer" e-click="$root.removeComment.bind($root)" title="{text key='h-gitter.remove-comment-title'}"></span>
                <p class="text-primary"><b>${$root.getParticipant(userId).username}</b> ${moment(ctime * 1000).fromNow()}</p>
                <div e-html="parsed"></div>
            </div>
            <hr />
        </div>
        <div class="col-xs-12">
            <input type="text" class="form-control new-comment-input" placeholder="{text key='h-gitter.discussion-response-btn'}" e-click="$root.displayCommentResponseForm.bind($root)" e-show="!$discussion.commentFormDisplayed" />
            <div class="response-wrapper col-xs-12" e-show="$discussion.commentFormDisplayed">
                <div e-html="commentForm" e-attr="{id : 'h-gitter-discussion-response-' + $discussion.id}"></div>
            </div>
        </div>
    </template>

    <input type="hidden" name="comments" value="{{{ $comments }}}" />
    <input type="hidden" name="participants" value="{{{ $participants }}}" />
    <input type="hidden" name="scores" value="{{{ $mr->scores }}}" />
</div>