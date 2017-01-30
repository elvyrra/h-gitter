<table class="file-diff">
    {foreach($fileDiffs['differences'] as $diff)}
        <tr>
            <td colspan="2" class="empty"></td>
            <td class="file-diff-block-summary">{{ $diff['summary'] }}</td>
        </tr>
        {foreach($diff['details'] as $i => $line)}
            <tr class="{{ $line['type'] }} {{ $line['type'] === 'addition' ? 'alert-success' : ($line['type'] === 'deletion' ? 'alert-danger' : '')}}">
                <td class="prev-line-number">{{ $line['leftLineNumber'] }}</td>
                <td class="new-line-number">{{ $line['rightLineNumber'] }}</td>
                {if($i === 0)}
                    <td class="code" id="{{uniqid()}}" rowspan="{{ count($diff['details']) }}">
                        <pre class="diff {{ $fileDiffs['extension'] }}">{{{ $diff['code'] }}}</pre>
                    </td>
                {/if}
            </tr>
        </tr>
        {/foreach}
    {/foreach}
</table>