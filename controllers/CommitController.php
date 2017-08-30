<?php

namespace Hawk\Plugins\HGitter;

class CommitController extends Controller {
    const MAX_LIST_ITEMS = 50;

    /**
     * Display the list of the commits of a repository, on a given branch
     * @returns string The HTML response
     */
    public function index($filename = '') {
        $repo = Repo::getById($this->repoId);
        $start = App::request()->getParams('start');
        $end = $start + self::MAX_LIST_ITEMS;
        $maxCommits = $repo->run('rev-list --count ' . $this->revision . ' -- ' . $filename);

        $cmd = 'log --pretty="format:%H"';

        if($start) {
            $cmd .= ' --skip=' . $start;
        }

        $cmd .= ' --max-count=' . self::MAX_LIST_ITEMS . ' ' . $this->revision . ' -- ' . $filename;

        $hashes = array_map('trim', explode(PHP_EOL, $repo->run($cmd)));

        $commits = array_map(function($hash) use($repo) {
            $commit = $repo->getCommitInformation($hash);

            $commit->time = date('H:i', $commit->date);
            $commit->avatar = $commit->user ? $commit->user->getProfileData('avatar') : null;

            return $commit;
        }, $hashes);

        $byDayCommits = array();

        foreach($commits as $commit) {
            $formattedDate = date(Lang::get('main.date-format'), $commit->date);

            if(empty($byDayCommits[$formattedDate])) {
                $byDayCommits[$formattedDate] = array();
            }

            $byDayCommits[$formattedDate][] = $commit;
        }

        if(!$start) {
            $this->addJavaScript($this->getPlugin()->getJsUrl('commits.js'));

            $breadcrumb = array();

            if($filename) {
                $breadcrumb = CodeController::getInstance(array(
                    'repoId' => $this->repoId,
                    'path' => $filename,
                    'type' => $this->type,
                    'revision' => $this->revision
                ))->getBreadcrumb();
            }

            $content = View::make($this->getPlugin()->getView('commits/list.tpl'), array(
                'repo' => $repo,
                'allCommits' => $byDayCommits,
                'maxCommits' => $maxCommits,
                'end' => $end,
                'filename' => $filename,
                'breadcrumb' => $breadcrumb
            ));

            return RepoController::getInstance(array(
                'repoId' => $this->repoId
            ))
            ->display(
                $filename ? 'code' : 'commits',
                $content,
                $filename ?
                    Lang::get($this->_plugin . '.repo-history-title', array(
                        'repo' => $repo->name,
                        'path' => $filename
                    )) :
                    Lang::get($this->_plugin . '.repo-commits-title', array(
                        'repo' => $repo->name
                    ))
            );
        }

        return  View::make($this->getPlugin()->getView('commits/list-items.tpl'), array(
            'repo' => $repo,
            'allCommits' => $byDayCommits,
            'maxCommits' => $maxCommits,
            'end' => $end
        ));
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
        $this->addCss('//cdnjs.cloudflare.com/ajax/libs/highlight.js/9.9.0/styles/monokai-sublime.min.css');

        $content = View::make($this->getPlugin()->getView('commits/commit.tpl'), array(
            'repo' => $repo,
            'commit' => $commit,
            'diff' => $diff,
        ));

        return RepoController::getInstance(array(
            'repoId' => $this->repoId
        ))
        ->display('commits', $content, Lang::get($this->_plugin . '.repo-commit-title', array(
            'commit' => $commit->shortHash,
            'repo' => $repo->name
        )));
    }


    public function fileDiff() {
        $repo = Repo::getById($this->repoId);
        $commit = $repo->getCommitInformation($this->commit);

        $diff = $repo->getDiff($commit->parent ? $commit->hash . '^1' : '', $commit->hash, $this->path);

        return View::make($this->getPlugin()->getView('diff/file-diff.tpl'), array(
            'fileDiffs' => $diff['differences'][$this->path]
        ));
    }
}