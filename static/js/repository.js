'use strict';

require(['app', 'emv', 'jquery', 'lang'], (app, EMV, $, Lang) => {
    /**
     * Delete a repository branch
     */
    $('#h-gitter-repo-branches-list').on('click', '.delete-branch', function() {
        if(confirm(Lang.get('h-gitter.delete-branch-confirmation'))) {
            $.ajax({
                url : $(this).data('href'),
                method : 'delete',
                dataType : 'json'
            })

            .done(() => {
                app.tabset.activeTab.reload();
            })

            .fail((xhr) => {
                app.notify('error', xhr.responseJSON && xhr.responseJSON.message || xhr.responseText);
            });
        }

        return false;
    });

    /**
     * Delete a merge request
     */
    $('#h-gitter-merge-requests-list').on('click', '.delete-merge-request', function() {
        if(confirm(Lang.get('h-gitter.delete-merge-request-confirmation'))) {
            $.ajax({
                url : $(this).data('href'),
                method : 'delete',
                dataType : 'json'
            })

            .done(() => {
                app.tabset.activeTab.reload();
            })

            .fail((xhr) => {
                app.notify('error', xhr.responseJSON && xhr.responseJSON.message || xhr.responseText);
            });
        }

        return false;
    });
});