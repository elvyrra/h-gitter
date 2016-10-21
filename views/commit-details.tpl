<div class="commit-details">
    {assign name="presentationTitle"}
        <div class="row">
            <div class="col-xs-12">
                {button class="btn-info pull-right"
                        label="{text key='h-gitter.repo-commits-browse-files'}"
                        href="{uri action='h-gitter-repo-code-folder' repoId='{$repo->id}' type='commit' revision='{$commit->hash}'}"
                }
                <span class="lead"> {{ $commit->message }} </span> <br />
            </div>
        </div>
    {/assign}

    {assign name="presentationContent"}
        {if($commit->avatar)}
            <img src="{{ $commit->avatar }}" class="commit-avatar small" />
        {else}
            {icon icon='user' class="commit-avatar default-avatar small" size="lg"}
        {/if}
        <span> {text key="h-gitter.commit-commited-by" author="{$commit->author}" date="{$commit->formattedDate}"}</span>

        <div class="pull-right">
            <div>
                {text key="h-gitter.commit-parent-label"}
                {if($commit->parent)}
                    <a href="{uri action='h-gitter-repo-commit' repoId='{$repo->id}' commit='{$commit->parent}'}">{{ $commit->parent }}</a>
                {else}
                    {text key="h-gitter.commit-no-parent"}
                {/if}
            </div>
            <div>
                {if($commit->tag)}
                    {icon icon="tag"}
                    <a href="{uri action='h-gitter-repo-tag' repoId='{$repo->id}' tag='{$commit->tag}'}">{{ $commit->tag }}</a>
                {/if}
            </div>
        </div>
    {/assign}


    {panel type="warning" title="{$presentationTitle}" content="{$presentationContent}" id="commit-details-header"}

    <div class="commit-summary lead">
        {icon icon="files-o"}
        <span>{text key="h-gitter.commit-difference-label-intro"} </span>
        <a role="button" data-toggle="collapse" href="#commit-files-summary" class="text-primary bold">{text key="h-gitter.commit-difference-label-changed-filed" files="{count($diff['differences'])}"}</a>
        <span>{text key="h-gitter.commit-difference-label-outro" additions="{$diff['additions']}" deletions="{$diff['deletions']}"}</span>
    </div>

    <div class="collapse" id="commit-files-summary">
        <ul>
            {foreach($diff['differences'] as $filename => $tmp)}
                {if($tmp['type'] === 'added')}
                    <li class="text-success">
                        {icon icon="plus"} {{ $filename }}
                    </li>
                {elseif($tmp['type'] === 'deleted')}
                    <li class="text-danger">
                        {icon icon="minus"} {{ $filename }}
                    </li>
                {else}
                    <li>{icon icon="eye"} {{ $filename }}</li>
                {/if}
            {/foreach}
        </ul>
    </div>

    {foreach($diff['differences'] as $filename => $fileDiffs)}
        {assign name="panelTitle"}
            <div class="row">
                <div class="col-xs-12">
                    {button class="btn-default pull-right"
                            label="{text key='h-gitter.commit-view-file-btn'}"
                            href="{uri action='h-gitter-repo-code-file' repoId='{$repo->id}' type='commit' revision='{$commit->hash}' path='{$filename}'}"
                    }

                    <span class="lead">{{ $filename }}</span> ({text key="h-gitter.commit-file-differences" additions="{$fileDiffs['additions']}" deletions="{$fileDiffs['deletions']}"})
                </div>
            </div>
        {/assign}

        {assign name="panelContent"}
            <table class="file-diff">
                {foreach($fileDiffs['differences'] as $diff)}
                    <tr>
                        <td colspan="2" class="empty"></td>
                        <td class="file-diff-block-summary">{{ $diff['summary'] }}</td>
                    </tr>
                    {foreach($diff['details'] as $i => $line)}
                        <tr class="{{ $line['type'] === 'addition' ? 'alert-success' : ($line['type'] === 'deletion' ? 'alert-danger' : '')}}">
                            <td class="prev-line-number">{{ $line['leftLineNumber'] }}</td>
                            <td class="new-line-number">{{ $line['rightLineNumber'] }}</td>
                            <td class="code">{{{ $line['code'] }}}</td>
                        </tr>
                    </tr>
                    {/foreach}
                {/foreach}
            </table>
        {/assign}

        {panel type="info" title="{$panelTitle}" content="{$panelContent}"}
    {/foreach}
</div>