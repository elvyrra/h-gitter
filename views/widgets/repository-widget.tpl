{button icon="share icon-flip-horizontal" class="btn-default btn-block" label="{text key='h-gitter.back-project-btn' project='{$project->name}'}" href="{uri action='h-gitter-project-repos' projectId='{$project->id}'}"}

{assign name="content"}
    <p>{text key="h-gitter.clone-repo-description"}</p>

    {input type="text" readonly="true" e-auto-select="true" value="{$repo->getSshUrl()}"}
{/assign}

{panel type="primary" title="{text key='h-gitter.clone-repo-title'}" content="{$content}" id="h-gitter-repo-clone-panel"}