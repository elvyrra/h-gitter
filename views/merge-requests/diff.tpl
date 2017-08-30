{import file="../diff/revision-diff-summary.tpl"}

{foreach($diff['differences'] as $filename => $fileDiffs)}
    {if($fileDiffs['type'] === 'deleted')}
        <div class="alert alert-info">
            <span class="lead">{{ $filename }}</span> &rarr; {text key="h-gitter.merge-request-diff-file-removed"}
        </div>
    {else}
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
            {if($fileDiffs['additions'] + $fileDiffs['deletions'] > 150)}
                <div class="text-center pointer expand-diff" data-path="{{{ $filename }}}">{text key="h-gitter.merge-request-diff-heavy"}</div>
            {else}
                {import file="../diff/file-diff.tpl" fileDiffs="{$fileDiffs}" comments="true" filename="{$filename}"}
            {/if}
        {/assign}

        {panel type="info" title="{$panelTitle}" content="{$panelContent}"}
    {/if}
{/foreach}
