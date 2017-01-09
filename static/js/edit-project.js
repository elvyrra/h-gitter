'use strict';

require(['app', 'lang', 'emv', 'emv-directives'], (app, Lang, EMV) => {
    const form = app.forms['h-gitter-project-form'];
    const allUsers = JSON.parse(form.inputs.users.val());
    const privileges = JSON.parse(form.inputs.privileges.val());

    /**
     * Find a user by it userId
     * @param   {int} userId The user Id
     * @returns {Object}     The found user information
     */
    const getUser = function(userId) {
        return allUsers.find((user) => {
            return user.id === parseInt(userId, 10);
        });
    };

    /**
     * This class manage the privileges
     */
    class Privilege extends EMV {
        /**
         * Constructor
         * @param   {Object} data The initial data
         */
        constructor(data) {
            super({
                data : data,
                computed : {
                    user : function() {
                        return getUser(this.userId);
                    },
                    username : function() {
                        return this.user.username;
                    }
                }
            });
        }
    }

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
                    privileges : privileges.map((data) => new Privilege(data))
                },
                computed : {
                    availableUsers : function() {
                        return allUsers.filter((user) => {
                            return !this.privileges.find((privilege) => {
                                return privilege.user === user;
                            });
                        });
                    }
                }
            });
        }

        /**
         * Add a user to the project
         * @param {Object} item The user to add
         */
        addUser(item) {
            if(item) {
                this.privileges.push(new Privilege({
                    userId : item.id,
                    master : false
                }));
            }
        }

        /**
         * Delete user fril the project
         * @param {Object} user The user to delete
         */
        removeUser(user) {
            if(confirm(Lang.get('h-gitter.remove-project-user-confirm'))) {
                const index = this.privileges.indexOf(user);

                this.privileges.splice(index, 1);
            }
        }
    }

    const model = new Project();

    model.$apply(form.node.get(0));
});