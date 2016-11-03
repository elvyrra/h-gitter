<?php
/**
 * start.php
 *
 * This file is launched for each request. It initialize the plugin routes and event listeners
 */

namespace Hawk\Plugins\HGitter;

App::router()->auth(App::session()->isAllowed('h-gitter.access-plugin'), function() {
    App::router()->prefix('/h-gitter', function() {

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

        App::router()->prefix('/repos', function() {
            // Create a repository / Edit repository settings
            App::router()->any('h-gitter-edit-repo', '/{repoId}/edit', array(
                'where' => array(
                    'repoId' => '\d+'
                ),
                'action' => 'RepoController.edit'
            ));

            // Display the content of a repository
            App::router()->get('h-gitter-display-repo', '/{repoId}', array(
                'where' => array (
                    'repoId' => '\d+'
                ),
                'action' => 'RepoController.display'
            ));

            // Display the home page of a repo
            App::router()->get('h-gitter-repo-home', '/{repoId}/home', array(
                'where' => array (
                    'repoId' => '\d+'
                ),
                'action' => 'RepoController.home'
            ));


            // Display the content of a folder
            App::router()->get('h-gitter-repo-code-folder', '/{repoId}/tree/{type}/{revision}/{path}', array(
                'where' => array (
                    'repoId' => '\d+',
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
            App::router()->get('h-gitter-repo-code-file', '/{repoId}/blob/{type}/{revision}/{path}', array(
                'where' => array (
                    'repoId' => '\d+',
                    'type' => 'commit|branch|tag',
                    'revision' => '[^\/]+'
                ),
                'default' => array (
                    'revision' => 'master',
                    'type' => 'branch'
                ),
                'action' => 'CodeController.displayFile'
            ));

            // Display the list of commits of the repository, for a given branch
            App::router()->get('h-gitter-repo-commits', '/{repoId}/commits/{revision}', array(
                'where' => array(
                    'repoId' => '\d+',
                    'revision' => '[^\/]+'
                ),
                'default' => array(
                    'revision' => 'master',
                ),
                'action' => 'CommitController.index'
            ));

            // Display the list of commits of the repository, for a given branch
            App::router()->get('h-gitter-repo-commit', '/{repoId}/commit/{commit}', array(
                'where' => array(
                    'repoId' => '\d+',
                    'commit' => '[a-f0-9A-F]+'
                ),
                'action' => 'CommitController.commit'
            ));

            // Display the list of branches
            App::router()->get('h-gitter-repo-branches', '/{repoId}/branches', array(
                'where' => array(
                    'repoId' => '\d+',
                ),
                'action' => 'BranchController.index'
            ));

            // Compare a branch with the default repository branch
            App::router()->get('h-gitter-repo-compare-branches', '/{repoId}/branches/{branch}/compare', array(
                'where' => array(
                    'repoId' => '\d+'
                ),
                'action' => 'BranchController.compare'
            ));

            // Create / Delete a branch
            App::router()->any('h-gitter-repo-branch', '/{repoId}/branches/{branch}', array(
                'where' => array(
                    'repoId' => '\d+'
                ),
                'action' => 'BranchController.edit'
            ));

            App::router()->any('h-gitter-repo-branch-info', '/{repoId}/branches/{branch}/info', array(
                'where' => array(
                    'repoId' => '\d+'
                ),
                'action' => 'BranchController.info'
            ));

            // List the repository merge requests
            App::router()->get('h-gitter-repo-merge-requests', '/{repoId}/merge-requests', array(
                'where' => array(
                    'repoId' => '\d+'
                ),
                'action' => 'MergeRequestController.index'
            ));

            // Create / Edit / Delete a merge request
            App::router()->any('h-gitter-repo-merge-request', '/{repoId}/merge-requests/{mergeRequestId}', array(
                'where' => array(
                    'repoId' => '\d+',
                    'mergeRequestId' => '\d+'
                ),
                'action' => 'MergeRequestController.edit'
            ));

            // Display a merge request
            App::router()->get('h-gitter-repo-display-merge-request', '/{repoId}/merge-requests/{mergeRequestId}/display', array(
                'where' => array(
                    'repoId' => '\d+',
                    'mergeRequestId' => '\d+'
                ),
                'action' => 'MergeRequestController.display'
            ));
        });
    });
});