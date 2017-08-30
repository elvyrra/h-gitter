<table class="file-diff">
    {foreach($fileDiffs['differences'] as $diff)}
        <tr>
            <td colspan="2" class="empty"></td>
            <td class="file-diff-block-summary">{{ $diff['summary'] }}</td>
        </tr>
        {foreach($diff['details'] as $i => $line)}
            <tr class="{{ $line['type'] === 'addition' ? 'alert-success' : ($line['type'] === 'deletion' ? 'alert-danger' : '')}}">
                <td class="prev-line-number">
                    {{ $line['leftLineNumber'] }}
                    {if(!empty($comments) && $line['rightLineNumber'])}
                        <i class="icon icon-comments icon-lg open-comment-form" e-show="!diffDiscussions['{{{ $filename }}}'][{{$line['rightLineNumber'] }}]" e-click="displayDiffCommentForm('{{{ $filename }}}', {{$line['rightLineNumber'] }})"></i>
                    {/if}
                </td>
                <td class="new-line-number">{{ $line['rightLineNumber'] }}</td>
                <td class="code" id="{{uniqid()}}">
                    {if($line['type'])}
                        <pre class="diff hljs"><span class="hljs-{{$line['type']}}">{{{ $line['code'] }}}</span></pre>
                    {elseif($line['code'])}
                        <pre class="diff hljs">{{{ $line['code'] }}}</pre>
                    {else}
                        <pre class="diff hljs"> </pre>
                    {/if}
                </td>
            </tr>
            {if(!empty($comments) && $line['rightLineNumber'])}
                <tr e-with="{$data : diffDiscussions['{{{ $filename }}}'][{{$line['rightLineNumber'] }}], $as : 'discussion'}">
                    <td class="prev-line-number"></td>
                    <td class="new-line-number"></td>
                    <td class="merge-request-diff-comment">
                        <div e-template="'merge-request-discussion'" class="comments"></div>
                    </td>
                </tr>
            {/if}
        </tr>
        {/foreach}
    {/foreach}
</table>