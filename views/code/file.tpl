<div id="h-gitter-repo-file-page">
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

            <div class="btn-group pull-right">
                {button label="{text key='h-gitter.repo-code-history-btn'}" href="{uri action='h-gitter-repo-file-history' repoId='{$repoId}' type='{$type}' revision='{$revision}' path='{$path}'}"}
            </div>
        </div>
    </div>

    <div class="clearfix"></div>

    {assign name="content"}
        <textarea class="hidden" id="h-gitter-file-content">{{{ $fileContent }}}</textarea>
        <div id="h-gitter-file-content-ace" e-ace="{language : '{{ $extension }}', readonly: true, value : content, maxLines : Infinity, theme : 'monokai' }"></div>
    {/assign}

    {panel type="info" icon="file-o" title="{$basename}" content="{$content}"}
</div>

<script type="text/javascript">
    require(['jquery', 'emv'], ($, EMV) => {
        const model = new EMV({
            content : $('#h-gitter-file-content').val()
        });

        model.$apply(document.getElementById('h-gitter-file-content-ace'));
    });
</script>