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

require(['app', 'emv', 'jquery', 'lang', 'highlight'], (app, EMV, $, Lang, hljs) => {
    /**
     * This class manages the behavior of the merge request
     */
    class MergeRequestModel extends EMV {
        /**
         * Constructor
         * @param  {string} tabSelector The tab selector
         */
        constructor(tabSelector) {
            super({
                data : {
                    participants : JSON.parse($(tabSelector).find('input[name="participants"]').val()),
                    comments : JSON.parse($(tabSelector).find('input[name="comments"]').val()),
                    scores : JSON.parse($(tabSelector).find('input[name="scores"]').val()),
                    commentFormDisplayed : false
                },
                computed : {
                    discussions : function() {
                        const discussions = [];

                        this.comments.forEach((comment) => {
                            if(!parseInt(comment.parentId, 10)) {
                                discussions.push({
                                    id : comment.id,
                                    comments : [comment],
                                    commentFormDisplayed : false,
                                    commentFormLoaded : false,
                                    commentForm : '',
                                    file : comment.file,
                                    line : comment.line
                                });
                            }
                            else {
                                const existing = discussions.find((discussion) => {
                                    return discussion.id === comment.parentId;
                                });

                                if(existing) {
                                    existing.comments.push(comment);
                                }
                            }
                        });
                        return discussions;
                    },
                    diffDiscussions : function() {
                        const result = {};

                        this.discussions.forEach((discussion) => {
                            if(discussion.file) {
                                if(!result[discussion.file]) {
                                    result[discussion.file] = {};
                                }

                                result[discussion.file][discussion.line] = discussion;
                            }
                        });

                        return result;
                    },
                    likes : function() {
                        return Object.keys(this.scores).filter((key) => {
                            return this.scores[key] === 1;
                        }).length;
                    },
                    unlikes : function() {
                        return Object.keys(this.scores).filter((key) => {
                            return this.scores[key] === -1;
                        }).length;
                    }
                }
            });


            const newCommentForm = app.forms['h-gitter-merge-request-new-comment-form'];

            if(newCommentForm) {
                newCommentForm.onsuccess = (data) => {
                    this.comments.push(data);
                    this.commentFormDisplayed = false;

                    newCommentForm.reset();
                };
            }

            $('.file-diff .code pre').each((index, block) => {
                hljs.highlightBlock(block);
            });

            $('.expand-diff').click(function() {
                const self = this;
                const parent = $(this).parent();
                const tabInfo = app.getRouteInformationFromUri(app.tabset.activeTab.uri);

                $.get(app.getUri('h-gitter-merge-request-file-diff', {
                    repoId : tabInfo.data.repoId,
                    mergeRequestId : tabInfo.data.mergeRequestId,
                    path : $(this).data('path')
                }))

                .done((response) => {
                    $(self).replaceWith(response);
                    $(parent).find('.code pre').each((index, block) => {
                        hljs.highlightBlock(block);
                    });
                });
            });
        }

        /**
         * Find a participant by it id
         * @param   {int} id The participant id
         * @returns {Object}    The found participant, or null
         */
        getParticipant(id) {
            return this.participants.find((user) => {
                return user.id === id;
            });
        }

        /**
         * Display the comment form to respond an existing discussion
         * @param {Object} discussion The discussion to add a comment on
         * @param {string} file       The file the comment is applied on
         * @param {int}    line       The code line the comment is applied on
         */
        displayCommentResponseForm(discussion, file, line) {
            const currentRoute = app.getRouteInformationFromUri(app.tabset.activeTab.uri);
            const wrapper = $('#h-gitter-discussion-response-' + discussion.id);

            $.get(app.getUri(
                'h-gitter-merge-request-comment',
                {
                    repoId : currentRoute.data.repoId,
                    mergeRequestId : currentRoute.data.mergeRequestId,
                    commentId : 0
                },
                file && line ?
                    {
                        file : file,
                        line : line
                    } :
                    {
                        parentId : discussion.id
                    }
            ))

            .then((response) => {
                discussion.commentForm = response;

                const formId = wrapper.find('form').attr('id');

                const form = app.forms[formId];

                form.inputs.content.node().focus();

                form.onsuccess = (data) => {
                    this.comments.push(data);
                };

                discussion.commentFormDisplayed = true;
            });
        }

        /**
         * Display the form to add a comment to a code line in the diff tab
         * @param {string} file       The file the comment is applied on
         * @param {int}    line       The code line the comment is applied on
         */
        displayDiffCommentForm(file, line) {
            this.discussions.push({
                comments : [],
                commentFormLoaded: false,
                commentFormDisplayed : false,
                commentForm : '',
                file : file,
                line : line
            });

            const discussion = this.diffDiscussions[file][line];

            this.displayCommentResponseForm(discussion, file, line);
        }

        /**
         * Score the merge request
         * @param  {int} value 0, 1 or -1
         */
        score(value) {
            const currentRoute = app.getRouteInformationFromUri(app.tabset.activeTab.uri);

            $.post(app.getUri('h-gitter-score-merge-request', {
                repoId : currentRoute.data.repoId,
                mergeRequestId : currentRoute.data.mergeRequestId,
                score : value
            }))

            .then((response) => {
                this.scores = response;
            });
        }

        /**
         * Like the merge request
         */
        like() {
            this.score(1);
        }

        /**
         * Unlike the merge request
         */
        unlike() {
            this.score(-1);
        }

        /**
         * Remove a comment
         * @param   {Object} comment The comment to remove
         */
        removeComment(comment) {
            const currentRoute = app.getRouteInformationFromUri(app.tabset.activeTab.uri);

            $.ajax({
                url : app.getUri('h-gitter-merge-request-comment', {
                    repoId : currentRoute.data.repoId,
                    mergeRequestId : currentRoute.data.mergeRequestId,
                    commentId : comment.id
                }),
                method : 'delete'
            })

            .then(() => {
                var index = this.comments.indexOf(comment);

                this.comments.splice(index, 1);
            });
        }

        /**
         * Delete the merge request
         */
        deleteMergeRequest() {
            const currentRoute = app.getRouteInformationFromUri(app.tabset.activeTab.uri);

            if(confirm(Lang.get('h-gitter.delete-merge-request-confirmation'))) {
                $.ajax({
                    url : app.getUri('h-gitter-repo-merge-request', {
                        repoId : currentRoute.data.repoId,
                        mergeRequestId : currentRoute.data.mergeRequestId
                    }),
                    method : 'delete',
                    dataType : 'json'
                })

                .done(() => {
                    app.load(app.getUri('h-gitter-repo-merge-requests', {
                        repoId : currentRoute.data.repoId
                    }));
                })

                .fail((xhr) => {
                    app.notify('error', xhr.responseJSON && xhr.responseJSON.message || xhr.responseText);
                });
            }
        }
    }

    const tabSelector = '#h-gitter-merge-request';

    Object.keys(app.forms).forEach((key) => {
        if(key.match('h-gitter-merge-request-comment-form-')) {
            delete app.forms[key];
        }
    });

    const model = new MergeRequestModel(tabSelector);

    window.mrModel = model;

    model.$apply(document.getElementById('h-gitter-merge-request'));
});
