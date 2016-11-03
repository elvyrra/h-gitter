<div id="h-gitter-repo-tree-page">
    {widget class="\Hawk\Plugins\HGitter\ChooseRevisionWidget"}
    <div class="clearfix"></div>

    <ol class="breadcrumb">
        {foreach($breadcrumb as $elem)}
            {if(!$elem['url'])}
                <li class="active">
                    {{ $elem['label'] }}
                </li>
            {else}
                <li>
                    <a href="{{ $elem['url'] }}" class="text-primary">{{ $elem['label'] }}</a>
                </li>
            {/if}
        {/foreach}
    </ol>

    <div class="clearfix"></div>

    {{$list}}
</div>