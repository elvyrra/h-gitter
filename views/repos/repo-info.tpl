<ul>
    <li>{icon icon="users" size="fw"} {text key="h-gitter.repos-list-info-members" number="{$members}"}</li>
    <li>
        <a href="{uri action='h-gitter-repo-merge-requests' repoId='{$repo->id}'}">
            {icon icon="code-fork" size="fw"} {text key="h-gitter.repos-list-info-merge-requests" number="{$mergeRequests}"}
        </a>
    </li>
</ul>
