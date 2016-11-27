<p>A new merge has been created by {{ $author }} on the repository {{ $project }}/{{ $repo }} : </p>

<h3>{{ $title }}</h3>

<a href="{{ ROOT_URL }}#!{uri action='h-gitter-repo-display-merge-request' repoId='{$repoId}' mergeRequestId='{$mrId}'}">
    Click here to see the merge request
</a>
