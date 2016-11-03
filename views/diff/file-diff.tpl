{assign name="panelTitle"}
    <div class="row">
        <div class="col-xs-12">
            {button class="btn-default pull-right"
                    label="{text key='h-gitter.commit-view-file-btn'}"
                    href="{uri action='h-gitter-repo-code-file' repoId='{$repo->id}' type='commit' revision='{$commit->hash}' path='{$filename}'}"
            }

            <span class="lead">{{ $filename }}</span> ({text key="h-gitter.commit-file-differences" additions="{$fileDiffs['additions']}" deletions="{$fileDiffs['deletions']}"})
        </div>
    </div>
{/assign}

{assign name="panelContent"}
    <table class="file-diff">
        {foreach($fileDiffs['differences'] as $diff)}
            <tr>
                <td colspan="2" class="empty"></td>
                <td class="file-diff-block-summary">{{ $diff['summary'] }}</td>
            </tr>
            {foreach($diff['details'] as $i => $line)}
                <tr class="{{ $line['type'] === 'addition' ? 'alert-success' : ($line['type'] === 'deletion' ? 'alert-danger' : '')}}">
                    <td class="prev-line-number">{{ $line['leftLineNumber'] }}</td>
                    <td class="new-line-number">{{ $line['rightLineNumber'] }}</td>
                    <td class="code">{{{ $line['code'] }}}</td>
                </tr>
            </tr>
            {/foreach}
        {/foreach}
    </table>
{/assign}

{panel type="info" title="{$panelTitle}" content="{$panelContent}"}