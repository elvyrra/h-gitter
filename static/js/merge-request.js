'use strict';

require(['app', 'emv', 'jquery', 'moment'], (app, EMV, $, moment) => {
    window.moment = moment;
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
                    commentForm : {
                        file : '',
                        line : 0,
                        parentId : 0
                    }
                },
                computed : {
                    discussions : function() {
                        const discussions = [];

                        this.comments.forEach((comment) => {
                            if(!parseInt(comment.parentId, 10)) {
                                discussions.push({
                                    id : comment.id,
                                    comments : [comment],
                                    commentFormLoaded: false,
                                    commentFormDisplayed : false,
                                    commentForm : ''
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
                            const comment = discussion.comments[0];

                            if(comment.file) {
                                if(!result[comment.file]) {
                                    result[comment.file] = {};
                                }

                                result[comment.file][comment.line] = discussion;
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
         * Display the comment form on an existing discussion
         * @param {Object} discussion The discussion to add a comment on
         * @param {Event}  event      The initial event
         */
        displayCommentResponseForm(discussion, event) {
            if(!discussion.commentFormLoaded) {
                const currentRoute = app.getRouteInformationFromUri(app.tabset.activeTab.uri);
                const button = $(event.currentTarget);

                $.get(app.getUri(
                    'h-gitter-merge-request-comment',
                    {
                        repoId : currentRoute.data.repoId,
                        mergeRequestId : currentRoute.data.mergeRequestId,
                        commentId : 0
                    },
                    {
                        parentId : discussion.id
                    }
                ))

                .then((response) => {
                    const templateName = EMV.utils.uid();

                    this.$registerTemplate(templateName, response);
                    discussion.commentForm = templateName;
                    setTimeout(() => {
                        const formId = $(button).next('.response-wrapper').find('form').attr('id');

                        const form = app.forms[formId];

                        discussion.commentFormLoaded = true;

                        form.inputs.comment.node.focus();

                        form.onsuccess = (data) => {
                            this.comments.push(data);
                        };
                    }, 200);
                });
            }
            discussion.commentFormDisplayed = true;
        }

        /**
         * Display the form to add a new comment on the merge request
         * @param {MergeRequestModel} model Not used
         * @param {Event}             event The initial event that tiggered this method
         */
        displayNewCommentForm(model, event) {
            if(!this.commentFormLoaded) {
                const currentRoute = app.getRouteInformationFromUri(app.tabset.activeTab.uri);
                const button = $(event.currentTarget);

                $.get(app.getUri(
                    'h-gitter-merge-request-comment',
                    {
                        repoId : currentRoute.data.repoId,
                        mergeRequestId : currentRoute.data.mergeRequestId,
                        commentId : 0
                    }
                ))

                .then((response) => {
                    const templateName = EMV.utils.uid();

                    this.$registerTemplate(templateName, response);
                    this.commentForm = templateName;
                    setTimeout(() => {
                        const formId = $(button).next('.response-wrapper').find('form').attr('id');

                        const form = app.forms[formId];

                        this.commentFormLoaded = true;

                        form.inputs.comment.node.focus();

                        form.onsuccess = (data) => {
                            this.comments.push(data);
                            this.commentFormDisplayed = false;
                            form.reset();
                        };
                    }, 200);
                });
            }
            this.commentFormDisplayed = true;
        }


        displayDiffCommentForm(file, line, event) {
            if(!this.diffDiscussions[file]) {
                this.diffDiscussions[file] = {};
            }
            if(!this.diffDiscussions[file][line]) {
                this.diffDiscussions[file][line] = {
                    comments : [],
                    commentFormLoaded: false,
                    commentFormDisplayed : false,
                    commentForm : ''
                };
            }

            const discussion = this.diffDiscussions[file][line];

            if(!discussion.commentFormLoaded) {
                const currentRoute = app.getRouteInformationFromUri(app.tabset.activeTab.uri);
                const button = $(event.currentTarget);

                $.get(app.getUri(
                    'h-gitter-merge-request-comment',
                    {
                        repoId : currentRoute.data.repoId,
                        mergeRequestId : currentRoute.data.mergeRequestId,
                        commentId : 0
                    },
                    {
                        file : file,
                        line : line
                    }
                ))

                .then((response) => {
                    const templateName = EMV.utils.uid();

                    this.$registerTemplate(templateName, response);
                    discussion.commentForm = templateName;
                    setTimeout(() => {
                        const formId = $(button).parents('tr').first().next('.merge-request-diff-comment').find('form').attr('id');

                        const form = app.forms[formId];

                        discussion.commentFormLoaded = true;

                        form.inputs.comment.node.focus();

                        form.onsuccess = (data) => {
                            this.comments.push(data);
                        };
                    }, 200);
                });
            }
            discussion.commentFormDisplayed = true;
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
                'method' : 'delete'
            })

            .then(() => {
                var index = this.comments.indexOf(comment);

                this.comments.splice(index, 1);
            });
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
