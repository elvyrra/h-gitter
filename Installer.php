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

        $htracker = PLugin::get('h-tracker');
        if($htracker && !$htracker->isInstalled()) {
            $htracker->install();
        }

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

        $htracker = PLugin::get('h-tracker');
        if($htracker && !$htracker->isActive()) {
            $htracker->activate();

            $items = MenuItem::getPluginMenuItems('h-tracker');

            foreach($items as $item) {
                $item->delete();
            }
        }
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
}