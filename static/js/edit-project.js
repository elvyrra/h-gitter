/* global app */
'use strict';

require(['emv', 'emv-directives'], (EMV) => {
    const form = app.forms['h-gitter-project-form'];
    const allUsers = JSON.parse(form.inputs.users.val());
    const privileges = JSON.parse(form.inputs.privileges.val());

    /**
     * This class manage the project edition
     */
    class Project extends EMV {
        /**
         * Constructor
         */
        constructor() {
            super({
                data : {
                    privileges : privileges,
                    users : allUsers
                },
                computed : {
                    availableUsers : function() {
                        return allUsers.filter((user) => {
                            return !(user.id in this.privileges);
                        });
                    },
                    privilegesArray : function() {
                        return Object.keys(this.privileges).map((userId) => {
                            return {
                                userId : userId,
                                username : this.getUser(userId).username,
                                privileges : this.privileges[userId]
                            };
                        });
                    }
                }
            });
        }

        /**
         * Find a user by it id
         * @param   {int} userId The user id
         * @returns {Object}     The found user
         */
        getUser(userId) {
            return allUsers.find((user) => {
                return user.id === parseInt(userId, 10);
            });
        }

        /**
         * Add a user to the project
         * @param {Object} item The user to add
         */
        addUser(item) {
            if(item) {
                this.privileges[item.id] = {
                    master : false
                };
            }
        }

        /**
         * Delete user fril the project
         * @param {Object} user The user to delete
         */
        removeUser(user) {
            if(confirm(Lang.get('h-gitter.remove-project-user-confirm'))) {
                delete this.privileges[user.userId];
            }
        }
    }

    const model = new Project();

    window.project = model;

    model.$apply(form.node.get(0));
});