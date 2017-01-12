<?php

namespace Hawk\Plugins\HGitter;

class ChooseRevisionWidget extends Widget {
    public function display() {
        $route = App::router()->getCurrentRoute();

        $repo = Repo::getById($route->getData('repoId'));
        $revision = $route->getData('revision');
        $path = $route->getData('path');

        $type = $route->getData('type');
        if(!$type) {
            $type = 'branch';
        }

        if($type === 'commit') {
            $revision = $repo->getCommitInformation($revision)->shortHash;
        }

        $branches = $repo->getBranches();
        $tags = $repo->getTags();

        $selectedLabel =
            Lang::get($this->_plugin . '.choose-revision-widget-' . $type, array(
                'revision' => $revision
            )) . ' ' .
            Icon::make(array(
                'icon' => 'caret-down'
            ));

        $options = array();

        if(empty($this->noBranches)) {
            $options = array_merge($options, array_map(function($branch) {
                return array(
                    'category' => 'Branch',
                    'type' => 'branch',
                    'value' => $branch,
                    'label' => $branch
                );
            }, $branches));
        }

        if(empty($this->noTags)) {
            $options = array_merge($options, array_map(function($tag) {
                return array(
                    'category' => 'Tag',
                    'type' => 'tag',
                    'value' => $tag,
                    'label' => $tag
                );
            }, $tags));
        }

        $this->addJavaScript($this->getPlugin()->getJsUrl('choose-revision-widget.js'));

        return View::make($this->getPlugin()->getView('widgets/choose-revision-widget.tpl'), array(
            'selectedLabel' => $selectedLabel,
            'options' => json_encode($options),
            'repoId' => $repo->id,
            'type' => $type,
            'path' => $path,
            'revision' => $revision
        ));
    }
}