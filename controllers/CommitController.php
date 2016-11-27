<?php

namespace Hawk\Plugins\HGitter;

class CommitController extends Controller {
    /**
     * Display the list of the commits of a repository, on a given branch
     * @returns string The HTML response
     */
    public function index() {
        $repo = Repo::getById($this->repoId);

        $hashes = array_map('trim', explode(PHP_EOL, $repo->run('log --pretty="format:%H" ' . $this->revision . ' --')));

        $commits = array_map(function($hash) use($repo) {
            $commit = $repo->getCommitInformation($hash);

            $commit->time = date('H:i', $commit->date);
            $commit->avatar = $commit->user ? $commit->user->getProfileData('avatar') : null;

            return $commit;
        }, $hashes);

        usort($commits, function($commit1, $commit2) {
            return $commit2->date - $commit1->date;
        });

        $byDayCommits = array();

        foreach($commits as $commit) {
            $formattedDate = date(Lang::get('main.date-format'), $commit->date);

            if(empty($byDayCommits[$formattedDate])) {
                $byDayCommits[$formattedDate] = array();
            }

            $byDayCommits[$formattedDate][] = $commit;
        }

        $content = View::make($this->getPlugin()->getView('commits/list.tpl'), array(
            'repo' => $repo,
            'allCommits' => $byDayCommits
        ));

        return RepoController::getInstance(array(
            'repoId' => $this->repoId
        ))->display('commits', $content);
    }


    /**
     * Display the details of a commit
     * @return string The HTML response
     */
    public function commit() {
        $repo = Repo::getById($this->repoId);

        $commit = $repo->getCommitInformation($this->commit);
        $commit->avatar = $commit->user ? $commit->user->getProfileData('avatar') : null;
        $commit->formattedDate = date(Lang::get('main.date-format'), $commit->date);

        $diff = $repo->getDiff($commit->parent ? $commit->hash . '^1' : '', $commit->hash);

        $this->addJavaScript($this->getPlugin()->getJsUrl('commit-diff.js'));

        $content = View::make($this->getPlugin()->getView('commits/commit.tpl'), array(
            'repo' => $repo,
            'commit' => $commit,
            'diff' => $diff,
        ));

        return RepoController::getInstance(array(
            'repoId' => $this->repoId
        ))->display('commits', $content);
    }
}