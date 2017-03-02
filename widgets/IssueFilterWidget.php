<?php

namespace Hawk\Plugins\HGitter;

class IssueFilterWidget extends Widget {
    public static $filters = array('status');


    public function getFilters() {

        if(App::request()->getHeaders('X-List-Filter')) {
            App::session()->getUser()->setOption($this->_plugin . '.issues-list-filter', App::request()->getHeaders('X-List-Filter'));
        }

        $result = App::session()->getUser()->getOptions($this->_plugin . '.issues-list-filter') ? json_decode(App::session()->getUser()->getOptions($this->_plugin . '.issues-list-filter'), true) : array();
        foreach($result as $name => $values){
            $result[$name] = array_filter($result[$name]);
        }

        return $result;
    }


    public function display() {
        $filters = $this->getFilters();

        $status = json_decode(Option::get('h-tracker.status'), true);

        usort($status, function($status1, $status2) {
            return $status1['order'] - $status2['order'];
        });

        $form = new Form(array(
            'id' => 'h-gitter-issues-filter-form',
            'attributes' => array(
                'onchange'  => 'app.lists["h-gitter-issues-list"].refresh({
                    headers : {
                        "X-List-Filter" : app.forms["h-gitter-issues-filter-form"].toString()
                    }
                })'
            ),
            'fieldsets' => array(
                'form' => array_map(
                    function ($status) use ($filters) {
                        return new CheckboxInput(
                            array(
                               'name' => 'status['.$status['id'].']',
                               'value' => isset($filters['status'][$status['id']]),
                               'label' => $status['label'],
                               'beforeLabel' => true,
                               'labelWidth'  => 'auto',
                            )
                        );
                    },
                    $status
                )
            )
        ));

        return View::make(Theme::getSelected()->getView("box.tpl"), array(
            'content' => $form,
            'title' => Lang::get('h-tracker.ticket-filter-title'),
            'icon' => 'filter',
        ));
    }
}