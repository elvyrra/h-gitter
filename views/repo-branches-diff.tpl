{if($branch->default)}
    <span class="badge lead alert-success branch-badge">{text key="h-gitter.branch-default"}</span>
{elseif($branch->merged)}
    <span class="badge lead alert-info branch-badge">{text key="h-gitter.branch-merged"}</span>
{else}
    <div class="branch-diff-summary" title="{{{ $branch->diffTitle }}}">
        <div class="ahead diff-count {if(!$branch->ahead)}no-diff{/if}">
            <div class="value"> {{ $branch->ahead }}</div>
            <div class="cursor" width="{{ min(100, $branch->ahead) }}%"></div>
        </div>

        <div class="behind diff-count {if(!$branch->behind)}no-diff{/if}">
            <div class="cursor" width="{{ min(100, $branch->behind) }}%"></div>
            <div class="value"> {{ $branch->behind }}</div>
        </div>
    </div>
{/if}
