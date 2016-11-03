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