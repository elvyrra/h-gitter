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
        {widget plugin="h-widgets"
                class="MetaData"
                userId="{$commit->user->id}"
                meta="{text key='h-gitter.commit-commited-by' author='{$commit->author}' date='{$commit->formattedDate}'}"
                size="small"
        }

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

    {import file="../diff/revision-diff-summary.tpl"}

    {foreach($diff['differences'] as $filename => $fileDiffs)}
        {if($fileDiffs['type'] === 'deleted')}
            <div class="alert alert-info">
                <span class="lead">{{ $filename }}</span> &rarr; {text key="h-gitter.merge-request-diff-file-removed"}
            </div>
        {else}
            {import file="./commit-file.tpl" filename="{$filename}" fileDiffs="{$fileDiffs}"}
        {/if}
    {/foreach}
</div>