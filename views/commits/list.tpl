<div id="h-gitter-repo-commits">
    {if($filename)}
        <ol class="breadcrumb">
            {foreach($breadcrumb as $i => $elem)}
                {if(!$elem['url'])}
                    <li class="active">
                        {if(!$i)}{text key="h-gitter.repo-history-intro"} {/if}{{ $elem['label'] }}
                    </li>
                {else}
                    <li>
                       {if(!$i)}{text key="h-gitter.repo-history-intro"} {/if}<a href="{{ $elem['url'] }}" class="text-primary">{{ $elem['label'] }}</a>
                    </li>
                {/if}
            {/foreach}
        </ol>
    {else}
        {widget class="\Hawk\Plugins\HGitter\ChooseRevisionWidget" noTags="true"}
        <div class="clearfix"></div>
    {/if}


    <div class="commits-list">
        {import file="list-items.tpl"}
    </div>

    <input type="hidden" name="max-commits" value="{{{ $maxCommits }}}" />
</div>