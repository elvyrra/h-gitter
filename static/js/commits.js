'use strict';

require(['app', 'emv', 'jquery'], (app, EMV, $) => {
    const rootNode = document.getElementById('h-gitter-repo-commits');

    class Commits extends EMV {
        constructor() {
            super({
                data : {
                    loading : false
                }
            });
        }

        loadCommits(start) {
            this.loading = true;
            const url = `${app.tabset.activeTab.uri}?start=${start + 1}`;

            $.get(url)

            .done((response) => {
                const replaced = $(rootNode).find('.load-more-commits');
                this.loading = false;

                $(replaced).replaceWith(response);

                this.$clean();

                this.$apply(rootNode);
            });
        }
    }

    const model = new Commits();

    model.$apply(rootNode);
});