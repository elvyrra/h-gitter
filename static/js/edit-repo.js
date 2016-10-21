/* global app */

'use strict';

require(['emv', 'emv-directives'], (EMV) => {
    const form = app.forms['h-gitter-repo-form'];
    const existingRepo = form.inputs['existing-repo'].val();

    const model = new EMV({
        data : {
            existingRepo : existingRepo
        }
    });

    model.$apply(form.node.get(0));
});