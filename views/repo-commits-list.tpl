<div id="h-gitter-repo-commits">
    {widget class="\Hawk\Plugins\HGitter\ChooseRevisionWidget" noTags="true"}

    <div class="clearfix"></div>

    <div class="commits-list">
        {foreach($allCommits as $date => $commits)}
            {assign name="panelContent"}
                <table class="table table-hover">
                    {foreach($commits as $commit)}
                        <tr>
                            <td class="col-xs-1">
                                {if($commit->avatar)}
                                    <img src="{{ $commit->avatar }}" class="commit-avatar" />
                                {else}
                                    {icon icon='user' class="commit-avatar default-avatar" size="3x"}
                                {/if}
                            </td>
                            </td>
                            <td class="col-xs-8">
                                <div class="commit-message bold lead">{{ $commit->message }}</div>
                                <div>{text key="h-gitter.repo-commits-time-and-author" author="{$commit->author}" time="{$commit->time}"}</div>
                            </td>
                            <td class="col-xs-3">
                                <a href="{uri action='h-gitter-repo-commit' repoId='{$repo->id}' commit='{$commit->hash}'}" class="btn btn-primary commit-btn">
                                    {{ $commit->shortHash }}
                                </a>
                                <a href="{uri action='h-gitter-repo-code-folder' repoId='{$repo->id}' type='commit' revision='{$commit->shortHash}'}" class="btn btn-primary">
                                    {text key="h-gitter.repo-commits-browse-files"}
                                </a>
                            </td>
                        </tr>
                    {/foreach}
                </table>
            {/assign}

            {panel type="info" icon="calendar-check-o" title="{text key='h-gitter.repo-commits-date-separator' date='{$date}'}" content="{$panelContent}"}
        {/foreach}
    </div>
</div>