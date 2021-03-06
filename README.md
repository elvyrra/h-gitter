# What is H Gitt'r ?

H Gitt'r is a Git repository manager. It allows the users of the application to share their code, see the git properties of the repository :

* The branches
* The commits
* The tags

# Features

* Create / Modify / Delete a project
* Create / Modify / Delete a repository
* Import an existing repository in H Gitt'r
* Manage the users's rights on each project / repo
* Choose the default branch of the repository
* Display the code in the repository
* Display the commits, and the diff of any commit
* Display the open branches, and compare branches
* Display the tags of the repository
* A bug tracker, based on the plugin H-tracker
* A merge request engine, like on git hub, ot gitlab
* Code review
* Comment the code of a merge request
* Like / unlike a merge request
* Accept a merge request
* Create branches
* Remove branches

# Dependencies
This plugin depends on the plugins :
* <a href="http://hawk-app.fr/#!/store/plugins/h-tracker" target="_blank">H tracker </a>
* <a href="http://hawk-app.fr/#!/store/plugins/h-widgets" target="_blank">H Widgets</a>

# Author
This plugin is devloped by the company Elvyrra

©Elvyrra S.A.S

# Changeset :

## v2.2.0
* Do not display to heavy diff in commit page and merge request
* Click to display the diff on to heavy diff

## v2.1.1
* Fix bugs on projects and repositories privileges
* Display only the number of open issues
* Add the button to create a branch from an issue only for new issues
* Display HTML version of repository / project in the list
* Fix bug : users was not added to merge requests participants when adding a comment
* Feat: Display the number of open merge requests of each repository, in the list of repositories

## v2.1.0
* Close an issue if a merge request has '#<id>' in it title, id being the id of the issue
* Create a tag on a given branch
* Create a branch from an issue
* Bug: The assignee is not displayed in the list of issues
* Sort and filter issues by status

## v2.0.1
* Lazy loading on the list of commits in a repository
