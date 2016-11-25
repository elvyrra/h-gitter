<?php
/**
 * start.php
 *
 * This file is launched for each request. It initialize the plugin routes and event listeners
 */

namespace Hawk\Plugins\HGitter;

App::router()->auth(App::session()->isAllowed('h-gitter.access-plugin'), function() {
    App::router()->prefix('/h-gitter', function() {
        /**
         * Projects
         */
        App::router()->prefix('/projects', function() {
            // Display the list of the projects
            App::router()->get('h-gitter-index', '', array(
                'action' => 'ProjectController.index'
            ));

            // Create / Edit a project
            App::router()->any('h-gitter-edit-project', '/{projectId}', array(
                'where' => array(
                    'projectId' => '\d+'
                ),
                'action' => 'ProjectController.edit'
            ));


            // See the repos of a project
            App::router()->get('h-gitter-project-repos', '/{projectId}/repos', array(
                'where' => array(
                    'projectId' => '\d+'
                ),
                'action' => 'RepoController.index'
            ));
        });

        /**
         * Repositories
         */
        App::router()->prefix('/repos/{repoId}', array('repoId' => '\d+'), function() {
            App::router()->auth(function($route) {
                $repoId = $route->getData('repoId');
                if(!$repoId) {
                    return true;
                }

                $repo = Repo::getById($repoId);

                return $repo->isVisible();
            }, function() {
                // Create a repository / Edit repository settings
                App::router()->any('h-gitter-edit-repo', '/edit', array(
                    'action' => 'RepoController.edit'
                ));

                // Display the content of a repository
                App::router()->get('h-gitter-display-repo', '', array(
                    'action' => 'RepoController.display'
                ));

                // Display the home page of a repo
                App::router()->get('h-gitter-repo-home', '/home', array(
                    'action' => 'RepoController.home'
                ));


                // Display the content of a folder
                App::router()->get('h-gitter-repo-code-folder', '/tree/{type}/{revision}/{path}', array(
                    'where' => array (
                        'type' => 'commit|branch|tag',
                        'revision' => '[^\/]+',
                    ),
                    'default' => array (
                        'path' => '',
                        'revision' => 'master',
                        'type' => 'branch'
                    ),
                    'action' => 'CodeController.displayFolder'
                ));

                // Display the content of a folder
                App::router()->get('h-gitter-repo-code-file', '/blob/{type}/{revision}/{path}', array(
                    'where' => array (
                        'type' => 'commit|branch|tag',
                        'revision' => '[^\/]+'
                    ),
                    'default' => array (
                        'revision' => 'master',
                        'type' => 'branch'
                    ),
                    'action' => 'CodeController.displayFile'
                ));

                /**
                 * Commits
                 */
                // Display the list of commits of the repository, for a given branch
                App::router()->get('h-gitter-repo-commits', '/commits/{revision}', array(
                    'where' => array(
                        'revision' => '[^\/]+'
                    ),
                    'default' => array(
                        'revision' => 'master',
                    ),
                    'action' => 'CommitController.index'
                ));

                // Display the list of commits of the repository, for a given branch
                App::router()->get('h-gitter-repo-commit', '/commit/{commit}', array(
                    'where' => array(
                        'commit' => '[a-f0-9A-F]+'
                    ),
                    'action' => 'CommitController.commit'
                ));

                /**
                 * Branches
                 */
                // Display the list of branches
                App::router()->get('h-gitter-repo-branches', '/branches', array(
                    'action' => 'BranchController.index'
                ));

                // Compare a branch with the default repository branch
                App::router()->get('h-gitter-repo-compare-branches', '/branches/{branch}/compare', array(
                    'action' => 'BranchController.compare'
                ));

                // Create / Delete a branch
                App::router()->any('h-gitter-repo-branch', '/branches/{branch}', array(
                    'action' => 'BranchController.edit'
                ));

                App::router()->any('h-gitter-repo-branch-info', '/branches/{branch}/info', array(
                    'action' => 'BranchController.info'
                ));

                /**
                 * Tags
                 */
                App::router()->get('h-gitter-repo-tags', '/tags', array(
                    'action' => 'TagController.index'
                ));

                /**
                 * Issues
                 */
                // The list of the open issues
                App::router()->get('h-gitter-repo-issues', '/issues', array(
                    'action' => 'IssueController.index'
                ));

                // Create / Edit / Delete an issue
                App::router()->any('h-gitter-repo-issue', '/issues/{issueId}', array(
                    'action' => 'IssueController.edit'
                ));

                /**
                 * Merge requests
                 */
                // List the repository merge requests
                App::router()->get('h-gitter-repo-merge-requests', '/merge-requests', array(
                    'action' => 'MergeRequestController.index'
                ));

                // Get availabability to be merged between two branches, and the title of the branch to merge
                App::router()->get('h-gitter-repo-merge-request-availability', '/merge-requests/available/{from}/{to}', array(
                    'action' => 'MergeRequestController.availabability'
                ));

                App::router()->prefix('/merge-request/{mergeRequestId}', array('mergeRequestId' => '\d+'), function() {
                    // Create / Edit / Delete a merge request
                    App::router()->any('h-gitter-repo-merge-request', '', array(
                        'action' => 'MergeRequestController.edit'
                    ));

                    // Display a merge request
                    App::router()->get('h-gitter-repo-display-merge-request', '/display', array(
                        'action' => 'MergeRequestController.display'
                    ));

                    // Accept a merge request
                    App::router()->post('h-gitter-accept-merge-request', '/accept', array(
                        'action' => 'MergeRequestController.accept'
                    ));

                    // Thumb up / Thumb down on a merge request
                    App::router()->post('h-gitter-score-merge-request', '/score/{score}', array(
                        'where' => array(
                            'score' => '1|\-1'
                        ),
                        'action' => 'MergeRequestController.score'
                    ));

                    // Create / Edit / Remove a comment on a merge request
                    App::router()->any('h-gitter-merge-request-comment', '/comments/{commentId}', array(
                        'where' => array(
                            'commentId' => '\d+'
                        ),
                        'action' => 'MergeRequestCommentController.edit'
                    ));
                });
            });
        });
    });
});