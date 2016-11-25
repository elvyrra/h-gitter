'use strict';

require(['emv', 'jquery', 'app'], (EMV, $, app) => {
    const form = app.forms['h-gitter-merge-request-form'];

    /**
     * This model descirbes the behavior of the form to create a new merge request
     */
    class MergeRequestFormModel extends EMV {
        /**
         * Constructor
         */
        constructor() {
            super({
                repoId : form.inputs.repoId.val(),
                title : form.inputs.title.val(),
                sourceBranch : form.inputs.from.val(),
                toBranch : form.inputs.to.val(),
                available : true
            });


            this.$watch(['sourceBranch', 'toBranch'], this.checkAvailability.bind(this));
        }

        /**
         * Check the merge request can be submitted
         */
        checkAvailability() {
            if(!this.sourceBranch || !this.toBranch) {
                this.title = '';
                this.available = false;

                return;
            }

            $.getJSON(app.getUri('h-gitter-repo-merge-request-availability', {
                repoId : this.repoId,
                from : this.sourceBranch,
                to : this.toBranch
            }))

            .done((response) => {
                this.available = response.available;
                this.title = response.title;
            });
        }
    }

    const model = new MergeRequestFormModel();

    const actionParam = app.getRouteInformationFromUri(form.action);

    if (!parseInt(actionParam.data.mergeRequestId, 10)) {
        model.checkAvailability();
    }

    model.$apply(form.node.get(0));
});