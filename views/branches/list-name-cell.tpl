<div class="lead">
    <a href="{uri action='h-gitter-repo-code-folder' repoId='{$repo->id}' revision='{$branch->name}'}">{{ $branch->name }}</a>
    {if($branch->default)}
        <span class="badge lead alert-success branch-badge">{text key="h-gitter.branch-default"}</span>
    {elseif($branch->merged)}
        <span class="badge lead alert-info branch-badge">{text key="h-gitter.branch-merged"}</span>
    {/if}
</div>

{text key="h-gitter.last-branch-update" date="{$branch->date}" user="{$branch->author}"}