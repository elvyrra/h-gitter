<div id="h-gitter-repo-file-page">
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

    {assign name="content"}
        <textarea class="hidden" id="h-gitter-file-content">{{{ $fileContent }}}</textarea>
        <div id="h-gitter-file-content-ace" e-ace="{language : '{{ $extension }}', readonly: true, value : content, maxLines : Infinity }"></div>
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