<?php

namespace Hawk\Plugins\HGitter;

class TagController extends Controller {
    /**
     * Display the list of the repository tags
     * @return string The HTML response
     */
    public function index() {
        $repo = Repo::getById($this->repoId);

        $tags = array_map(function($tag) use($repo) {
            $info = $repo->getCommitInformation($tag, false);
            $info->name = $tag;

            return $info;
        }, $repo->getTags());



        $list = new ItemList(array(
            'id' => 'h-gitter-tags-list',
            'data' => $tags,
            'sorts' => array(
                'date' => DB::SORT_DESC
            ),
            'navigation' => false,
            'noHeader' => true,
            'fields' => array(
                'name' => array(
                    'href' => function($value) {
                        return App::router()->getUri('h-gitter-repo-code-folder', array(
                            'repoId' => $this->repoId,
                            'type' => 'tag',
                            'revision' => $value
                        ));
                    }
                ),

                'author' => array(
                ),

                'date' => array(
                    'display' => function($value) {
                        return Utils::timeAgo($value);
                    }
                )
            )
        ));

        $content = $list->display();

        if($list->isRefreshing()) {
            return $content;
        }

        return RepoController::getInstance(array(
            'repoId' => $this->repoId
        ))->display('tags', $content);
    }
}