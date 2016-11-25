<?php
/**
 * Installer.php
 */

namespace Hawk\Plugins\HGitter;

/**
 * This class describes the behavio of the installer for the plugin h-gitter
 */
class Installer extends PluginInstaller{
    /**
     * Install the plugin. This method is called on plugin installation, after the plugin has been inserted in the database
     */
    public function install(){
        // Create  plugin tables
        Project::createTable();

        Repo::createTable();

        CommitCache::createTable();

        MergeRequest::createTable();

        MergeRequestComment::createTable();

        // Create permissions
        Permission::add($this->_plugin . '.access-plugin', 1, 0);
        Permission::add($this->_plugin . '.create-projects', 0, 0);
    }

    /**
     * Uninstall the plugin. This method is called on plugin uninstallation, after it has been removed from the database
     */
    public function uninstall(){
        MergeRequestComment::dropTable();
        MergeRequest::dropTable();
        CommitCache::dropTable();
        Repo::dropTable();
        Project::dropTable();
    }

    /**
     * Activate the plugin. This method is called when the plugin is activated, just after the activation in the database
     */
    public function activate() {
        MenuItem::add(array(
            'plugin' => $this->_plugin,
            'name' => 'menu',
            'labelKey' => $this->_plugin . '.menu-title',
            'icon' => 'git-square',
            'action' => 'h-gitter-index'
        ));
    }

    /**
     * Deactivate the plugin. This method is called when the plugin is deactivated, just after the deactivation in the database
     */
    public function deactivate(){
        $items = MenuItem::getPluginMenuItems($this->_plugin);

        foreach($items as $item) {
            $item->delete();
        }
    }

    /**
     * Configure the plugin. This method contains a page that display the plugin configuration. To treat the submission of the configuration
     * you'll have to create another method, and make a route which action is this method. Uncomment the following function only if your plugin if
     * configurable.
     */
    public function settings() {
        $form = new Form(array(
            'id' => 'h-gitter-settings-form',
            'fieldsets' => array(
                'form' => array(
                    new TextInput(array(
                        'name' => 'folder',
                        'required' => true,
                        'default' => Option::get($this->_plugin . '.default-folder'),
                        'label' => Lang::get($this->_plugin . '.settings-form-default-folder-label')
                    ))
                ),

                'submits' => array(
                    new SubmitInput(array(
                        'name'  => 'valid',
                        'value' => Lang::get('main.valid-button'),
                    )),

                    new ButtonInput(array(
                        'name'    => 'cancel',
                        'value'   => Lang::get('main.cancel-button'),
                        'onclick' => 'app.dialog("close")',
                    )),
                ),
            ),
            'onsuccess' => 'app.dialog("close");',
        ));

        if(!$form->submitted()) {
            return Dialogbox::make(array(
                'title' => Lang::get($this->_plugin . '.settings-form-title'),
                'icon' => 'cogs',
                'page' => $form->display()
            ));
        }
        elseif($form->check()) {
            if(!is_dir($form->getData('folder'))) {
                $form->error('folder', Lang::get($this->_plugin . '.settings-form-folder-not-exists'));

                return $form->response(Form::STATUS_CHECK_ERROR);
            }

            Option::set($this->_plugin . '.default-folder', $form->getData('folder'));

            return $form->response(Form::STATUS_SUCCESS);
        }
    }
}