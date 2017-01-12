'use srict';

require(['app', 'jquery', 'lang'], (app, $, Lang) => {
    $('#h-gitter-tags-list').on('click', '.delete-tag', function() {
        if(confirm(Lang.get('h-gitter.confirm-delete-tag'))) {
            const tag = $(this).data('tag');
            const repoId = app.getRouteInformationFromUri(app.tabset.activeTab.uri).data.repoId;

            $.ajax({
                url : app.getUri('h-gitter-repo-tag', {
                    repoId : repoId,
                    tag : tag
                }),
                method : 'delete'
            })

            .done(() => {
                app.lists['h-gitter-tags-list'].refresh();
            });
        }
    });
});