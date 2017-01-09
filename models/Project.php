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
            $this->decodedPrivileges = json_decode($this->privileges);
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

        foreach($this->decodedPrivileges as $privileges) {
            if($privileges->userId === $user->id) {
                return true;
            }
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

        foreach($this->decodedPrivileges as $privileges) {
            if($privileges->userId === $user->id && !empty($privileges->master)) {
                return true;
            }
        }

        return false;
    }


    /**
     * Get the project users
     * @return array
     */
    public function getUsers() {
        if(empty($this->decodedPrivileges)) {
            return array();
        }
        return User::getListByExample(new DBExample(array(
            'id' => array(
                '$in' => array_map(function($privileges) {
                    return $privileges->userId;
                }, $this->decodedPrivileges)
            ),
        )));
    }


    /**
     * Get the basename of the project avatar
     * @return string
     */
    public function getAvatarBasename() {
        return 'project-avatar-' . $this->id;
    }

    /**
     * Get the full path of the project avatar
     * @return string
     */
    public function getAvatarFilename() {
        return Plugin::current()->getPublicUserfilesDir() . $this->getAvatarBasename();
    }

    /**
     * Get the avatr URL of the project
     * @return string
     */
    public function getAvatarUrl() {
        $plugin = Plugin::current();

        if(is_file($this->getAvatarFilename())) {
            return $plugin->getUserfilesUrl($this->getAvatarBasename());
        }

        return '';
    }

    /**
     * Get the folder containing the folder repositories
     * @return string
     */
    public function getDirname() {
        return Plugin::current()->getUserfile('project-' . $this->id);
    }
}