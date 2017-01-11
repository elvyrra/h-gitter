{foreach($allCommits as $date => $commits)}
    {assign name="panelContent"}
        <table class="table table-hover">
            {foreach($commits as $commit)}
                <tr>
                    <td class="col-xs-9">
                        {widget plugin="h-widgets"
                                class="MetaData"
                                avatar="{$commit->avatar}"
                                name="{$commit->author}"
                                size="small"
                                meta="{$commit->message}"
                                description="{text key='h-gitter.repo-commits-time-and-author' author='{$commit->author}' time='{$commit->time}'}"}
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

{if($end < $maxCommits)}
    <div class="text-center load-more-commits">
        {button icon="caret-down"
                label="{text key='h-gitter.repo-commits-load-more'}"
                class="btn-warning"
                e-click="{'loadCommits(' . $end  . ')'}"
                e-show="!loading"
        }
        {icon icon="spinner" class="icon-spin" e-show="loading"}
    </div>
{/if}