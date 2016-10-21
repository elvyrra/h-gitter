<?php

namespace Hawk\Plugins\HGitter;

class RepositoryPageWidget extends Widget {
    public function display() {
        $repo = Repo::getById($this->id);

        $project = $repo->getProject();

        return View::make($this->getPlugin()->getView('repository-widget.tpl'), array(
            'project' => $project,
            'repo' => $repo
        ));
    }
}