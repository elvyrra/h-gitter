{assign name='formContent'}
    {{ $form->fieldsets['global'] }}

    <div class="clearfix"></div>

    <fieldset>
        <legend>{text key="h-gitter.edit-repo-privileges-legend"}</legend>
        <table class="table table-hover">
            <tr>
                <th>{text key='h-gitter.edit-repo-privivileges-username-label'}</th>
                <th>{text key='h-gitter.edit-repo-privivileges-master-label'}</th>
            </tr>

            {foreach($users as $user)}
                <tr>
                    <td>{{ $user->username }}</td>
                    <td>{{ $form->inputs['masters[' . $user->id . ']'] }}
                </tr>
            {/foreach}
        </table>
    </fieldset>


    {{ $form->fieldsets['submits'] }}
{/assign}

{form id="h-gitter-repo-form" content="{$formContent}"}