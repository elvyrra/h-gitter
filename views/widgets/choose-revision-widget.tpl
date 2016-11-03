<div id="choose-revision-widget">
    {button class="btn btn-primary" label="{$selectedLabel}" e-click="expand"}

    <div id="choose-revision-content" e-show="expanded">
        {assign name="panelContent"}
            <input type="hidden" name="options" value="{{{ $options }}}" />
            <input type="hidden" name="repoId" value="{{ $repoId }}" />
            <input type="hidden" name="path" value="{{ $path }}" />
            <input type="text"
                placeholder="{text key='h-gitter.choose-revision-widget-placeholder'}"
                e-autocomplete="{source : options, minLength : 0, search : 'value', value : 'value', change : choose}" />

        {/assign}

        {panel type="default" title="{text key='h-gitter.choose-revision-widget-title'}" icon="search" content="{$panelContent}"}
    </div>
</div>