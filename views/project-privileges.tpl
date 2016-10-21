<table class="table table-stripped">
    <tr>
        <th>{text key='h-gitter.edit-project-privivileges-username-label'}</th>
        <th>{text key='h-gitter.edit-project-privivileges-master-label'}</th>
        <th></th>
    </tr>

    <tr e-each="{$data : privilegesArray, $sort : 'username'}">
        <td>${username}</td>
        <td>{input type="checkbox" id="h-gitter-edit-project-master-${$index}" e-value="privileges.master"}</td>
        <td>{icon icon="times" class="text-danger pointer" e-click="$root.removeUser.bind($root)"}
    </tr>
</table>