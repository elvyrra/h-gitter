<div class="lead">
    <a href="{uri action='h-gitter-repo-display-merge-request' repoId='{$repo->id}' mergeRequestId='{$mr->id}'}">{{ $mr->title }}</a>
</div>

#{{ $mr->id }} {text key="h-gitter.mr-list-opend-by" date="{$date}" username="{$username}"}