<div id="h-gitter-repo-commits">
    {widget class="\Hawk\Plugins\HGitter\ChooseRevisionWidget" noTags="true"}

    <div class="clearfix"></div>

    <div class="commits-list">
        {import file="list-items.tpl"}
    </div>

    <input type="hidden" name="max-commits" value="{{{ $maxCommits }}}" />
</div>