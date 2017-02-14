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
    {if($fileDiffs['additions'] + $fileDiffs['deletions'] > 100)}
        <div class="text-center pointer expand-diff" data-path="{{{ $filename }}}">{text key="h-gitter.merge-request-diff-heavy"}</div>
    {else}
        {import file="../diff/file-diff.tpl" fileDiffs="{$fileDiffs}"}
    {/if}
{/assign}

{panel type="info" title="{$panelTitle}" content="{$panelContent}"}