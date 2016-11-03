'use strict';

require(['emv', 'jquery', 'app'], (EMV, $, app) => {
    const form = app.forms['h-gitter-merge-request-form'];

    let model = new EMV({
        data : {
            title : form.inputs.title.val(),
            sourceBranch : form.inputs.from.val(),
            toBranch : form.inputs.to.val(),
            valid : form.inputs.from.val() !== form.inputs.to.val()
        }
    });

    model.$watch('sourceBranch', function(value) {
        $.getJson(app.getUri('h-gitter-repo-branch-info'), {
            repoId : form.inputs.repoId.val(),
            branch : value
        })

        .done((response) => {
            model.title = response.message;
        });
    });
});