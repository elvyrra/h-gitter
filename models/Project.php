<?php

/**
 * Project.php
 *
 * @author Elvyrra SAS
 */

namespace Hawk\Plugins\HGitter;

/**
 * This class describes the behavior of gitter projects
 */
class Project extends Model {
    protected static $tablename = 'HGitterProject';

    protected static $primaryColumn = 'id';

    protected static $fields = array(
        'id' => array(
            'type' => 'int(11)',
            'auto_increment' => true
        ),

        'name' => array(
            'type' => 'varchar(64)'
        ),

        'userId' => array(
            'type' => 'int(11)',
        ),

        'ctime' => array(
            'type' => 'int(11)'
        ),

        'description' => array(
            'type' => 'varchar(4096)'
        ),

        'privileges' => array(
            'type' => 'text'
        )
    );

    protected static $constraints = array(
        'name' => array(
            'type' => 'unique',
            'fields' => array(
                'name'
            )
        ),
        'userId' => array(
            'type' => 'foreign',
            'fields' => array(
                'userId'
            ),
            'references' => array(
                'model' => '\Hawk\User',
                'fields' => array('id')
            ),
            'on_update' => 'RESTRICT',
            'on_delete' => 'RESTRICT'
        )
    );

    /**
     * Constructor
     * @param array $data The initial project data
     */
    public function __construct($data = array()) {
        parent::__construct($data);

        $this->decodedPrivileges = array();

        if(!empty($this->privileges)) {
            $this->decodedPrivileges = json_decode($this->privileges, true);
        }
    }

    /**
     * Check if the project is visible for a given user
     * @param   User  $user The user to check the project is visible for
     * @return  boolean
     */
    public function isVisible($user = null) {
        if(!$user) {
            $user = App::session()->getUser();
        }

        if(!$user->isAllowed('h-gitter.access-plugin')) {
            return false;
        }

        if($this->userId === $user->id) {
            return true;
        }

        if(isset($this->decodedPrivileges[$user->id])) {
            return true;
        }

        return false;
    }

    /**
     * Check if a user has the mast privileges on the project
     * @param   User  $user The user to heck  the privileges'
     * @return boolean
     */
    public function isUserMaster($user = null) {
        if(!$user) {
            $user = App::session()->getUser();
        }

        if($this->userId === $user->id) {
            return true;
        }

        if(!empty($this->decodedPrivileges[$user->id]['master'])) {
            return true;
        }

        return false;
    }


    /**
     * Get the project users
     * @return array
     */
    public function getUsers() {
        return User::getListByExample(new DBExample(array(
            'id' => array(
                '$in' => array_merge(array_keys($this->decodedPrivileges), array($this->userId))
            ),
        )));
    }

    /**
     * Get the avatr URL of the project
     * @return string
     */
    public function getAvatarUrl() {
        $basename = 'project-avatar-' . $this->id;
        $plugin = Plugin::current();

        if(is_file($plugin->getPublicUserfilesDir() . $basename)) {
            return $plugin->getUserfilesUrl($basename);
        }

        return '';
    }
}