'use strict';

require(['emv'], (EMV) => {
    (new EMV()).$apply(document.getElementById('h-gitter-repo-content'));
});