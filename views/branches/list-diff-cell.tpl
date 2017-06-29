{if($branch->ahead || $branch->behind)}
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
