<div id="h-gitter-repo-tree-page">
    {widget class="\Hawk\Plugins\HGitter\ChooseRevisionWidget"}
    <div class="clearfix"></div>

    <div class="row">
        <div class="col-xs-12">
            <ol class="breadcrumb pull-left">
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

            {if($path)}
                <div class="btn-group pull-right">
                    {button label="{text key='h-gitter.repo-code-history-btn'}" href="{uri action='h-gitter-repo-file-history' repoId='{$repoId}' type='{$type}' revision='{$revision}' path='{$path}'}"}
                </div>
            {/if}
        </div>
    </div>

    <div class="clearfix"></div>

    {{$list}}
</div>