/* global app, $ */
'use strict';

require(['emv'], (EMV) => {
    const node = $('#choose-revision-widget');

    const model = new EMV({
        data : {
            options : JSON.parse(node.find('[name="options"]').val()),
            repoId : node.find('[name="repoId"]').val(),
            path : node.find('[name="path"]').val(),
            expanded : false
        }
    });

    model.expand = function() {
        this.expanded = !this.expanded;
    }.bind(model);

    model.choose = function(item) {
        if(item) {
            app.load(
                app.getUri(app.tabset.activeTab.route, {
                    repoId : this.repoId,
                    type : item.type,
                    revision : item.value,
                    path : this.path
                })
            );
        }
    };

    model.$apply(node.get(0));
});