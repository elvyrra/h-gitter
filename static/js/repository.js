'use strict';

require(['app', 'emv', 'jquery', 'lang', 'moment'], (app, EMV, $, Lang) => {
    class ChooseRevisionWidget {
        constructor(data) {

        }
    }

    /**
     *  This class manage the front office of the repository
     */
    class Repository extends EMV {
        /**
         * Constructor
         */
        constructor() {
            super({
                data : {
                    repoId        : $('#h-gitter-repo-id').val(),
                    defaultBranch : $('#h-gitter-repo-default-branch').val(),
                    activeSection : $('#h-gitter-repo-active-section').val() || '',
                    template      : '',
                    data          : {}
                }
            });
        }

        /**
         * Get a section by it name
         * @param   {string} name The name of the section to retrieve
         * @returns {Object}      The found section
         */
        getSection(name) {
            return this.sections.find((section) => section.name === name);
        }

        /**
         * Delete a branch of the managed repository
         * @param  {string} branch The name of the branch to delete
         */
        deleteBranch(branch) {
            if(confirm(Lang.get('h-gitter.delete-branch-confirmation'))) {
                $.ajax({
                    url : app.getUri('h-gitter-repo-branch', {
                        repoId : this.repoId,
                        branch : branch
                    }),
                    method : 'delete',
                    dataType : 'json'
                })

                .done(() => {
                    app.lists['h-gitter-repo-branches-list'].refresh();

                    this.getSection('branches').number--;
                })

                .fail((xhr) => {
                    app.notify('error', xhr.responseJSON && xhr.responseJSON.message || xhr.responseText);
                });
            }
        }

        /**
         * Delete a merge request of the managed repository
         * @param  {integer} mrId The id of the merge request to delete
         */
        deleteMergeRequest(mrId) {
            if(confirm(Lang.get('h-gitter.delete-merge-request-confirmation'))) {
                $.ajax({
                    url : app.getUri('h-gitter-repo-merge-request', {
                        repoId         : this.repoId,
                        mergeRequestId : mrId
                    }),
                    method : 'delete',
                    dataType : 'json'
                })

                .done(() => {
                    app.lists['h-gitter-merge-requests-list'].refresh();

                    this.getSection('merge-requests').number--;
                })

                .fail((xhr) => {
                    app.notify('error', xhr.responseJSON && xhr.responseJSON.message || xhr.responseText);
                });
            }
        }
    }

    const repo = new Repository();

    repo.$apply(document.getElementById('h-gitter-repository-index'));

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
                app.lists['h-gitter-repo-branches-list'].refresh();

                repo.getSection('branches').number--;
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
                app.lists['h-gitter-merge-requests-list'].refresh();

                repo.getSection('merge-requests').number--;
            })

            .fail((xhr) => {
                app.notify('error', xhr.responseJSON && xhr.responseJSON.message || xhr.responseText);
            });
        }

        return false;
    });
});