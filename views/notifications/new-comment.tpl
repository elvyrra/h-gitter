<p>{{ $author }} let a new comment on the merge request !{{ $mrId }} :</p>

<div> {{ $comment }} </div>

<a href="{{ ROOT_URL }}#!{uri action='h-gitter-repo-display-merge-request' repoId='{$repoId}' mergeRequestId='{$mrId}'}">
    Click here to see the merge request
</a>
