<div class="lead row">
    <div class="col-xs-12">
        {text key="h-gitter.branch-comparison-title"}
        <a e-href="{$path : 'h-gitter-repo-code-folder', repoId : $root.repoId, revision : branch}" class="text-success bold">${ branch }</a> /
        <a e-href="{$path : 'h-gitter-repo-code-folder', repoId : $root.repoId, revision : $root.defaultBranch}" class="text-primary bold">${ $root.defaultBranch }</a>

        <div class="pull-right">
            {button e-href=" {$path : 'h-gitter-repo-merge-requests', repoId : $root.repoId, $qs : {branch : branch}}"
                    class="btn-primary"
                    icon="code-fork icon-flip-vertical"
                    label="{text key='h-gitter.new-merge-request-btn'}"
            }
        </div>
    </div>
</div>

{import file="../diff/revision-diff-summary.tpl"}

<div e-each="diff.differences">
    {import file="../diff/file-diff.tpl"}
</div>