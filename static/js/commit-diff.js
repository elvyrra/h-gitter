'use strict';

require.config({
    paths : {
        highlight : 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/9.9.0/highlight.min'
    },
    shim : {
        highlight : {
            exports : ['hljs']
        }
    }
});

require(['highlight', 'jquery', 'app'], (hljs, $, app) => {
    $('.file-diff .code pre').each((index, block) => {
        hljs.highlightBlock(block);
    });

    $('.expand-diff').click(function() {
        const self = this;
        const parent = $(this).parent();
        const tabInfo = app.getRouteInformationFromUri(app.tabset.activeTab.uri);

        $.get(app.getUri('h-gitter-repo-commit-file-diff', {
            repoId : tabInfo.data.repoId,
            commit : tabInfo.data.commit,
            path : $(this).data('path')
        }))

        .done((response) => {
            $(self).replaceWith(response);
            $(parent).find('.code pre').each((index, block) => {
                hljs.highlightBlock(block);
            });
        });
    });
});