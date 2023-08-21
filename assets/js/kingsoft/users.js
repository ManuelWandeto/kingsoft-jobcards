function editUserForm() {
    return {
        fields: {
            username: {
                value: session.username ?? null, error: null,
                rules: ["required", "minLength:3"]
            },
            email: {
                value: session.email ?? null, error: null,
                rules: ["optional", "email"]
            },
            phone: {
                value: session.phone ?? null, error: null,
                rules: ["optional", "numeric"]
            },
            currentLocation: {
                value: session.location ?? null, error: null,
                rules: ["optional", "string"]
            },
            currentTask: {
                value: session.task ?? null, error: null,
                rules: ["optional", "string"]
            }
        },
        isFormInvalid: true,
        validateField(field) {
            let res = Iodine.assert(field.value, field.rules);
            field.error = res.valid ? null : res.error;
            this.isFormValid();
        },
        isFormValid(){
            this.isFormInvalid = Object.values(this.fields).some(
                (field) => field.error
            );
            return ! this.isFormInvalid ;
        }
    }
}
function editPasswordForm() {
    Iodine.rule('equals', (value, param) => value === param);
    Iodine.setErrorMessage('equals', "passwords are not equal");

    return {
        fields: {
            oldPassword: {
                value: null, error: null,
                rules: ["required"]
            },
            newPassword: {
                value: null, error: null,
                rules: ["required", "minLength:8"]
            },
            repeatPassword: {
                value: null, error: null,
                rules: ["required", "equals"]
            }
        },
        isFormInvalid: true,
        validateField(field) {
            if (field.rules.find(rule => rule.includes('equals'))) {
                field.rules.pop()
                if (this.fields.newPassword.value) {
                    field.rules.push(`equals:${this.fields.newPassword.value}`)
                }else {
                    this.validateField(this.fields.newPassword)
                }
            }
            let res = Iodine.assert(field.value, field.rules);
            field.error = res.valid ? null : res.error;
            this.isFormValid();
        },
        isFormValid(){
            this.isFormInvalid = Object.values(this.fields).some(
                (field) => field.error
            );
            return ! this.isFormInvalid ;
        }
    }
}
function addUserForm() {
    Iodine.rule('equals', (value, param) => value === param);
    Iodine.setErrorMessage('equals', "passwords are not equal");
    return {
        fields: {
            username: {
                value: null, error: null,
                rules: ["required", "maxLength:80", "minLength:3"]
            },
            email: {
                value: null, error: null,
                rules: ["optional", "email"]
            },
            role: {
                value: undefined, error: null,
                rules: ["required"]
            },
            password: {
                value: null, error: null,
                rules: ["required", "minLength:8"]
            },
            repeatPassword: {
                value: null, error: null,
                rules: ["required", "equals"]
            },
        },
        clearForm() {
            this.fields.username.value = ""
            this.fields.email.value = ""
            this.fields.role.value = undefined
            this.fields.password.value = ""
            this.fields.repeatPassword.value = ""
            clearFormErrors(this.fields)
            this.isFormValid()
        },
        isFormInvalid: true,
        validateField(field) {
            if (field.rules.find(rule => rule.includes('equals'))) {
                field.rules.pop()
                if (this.fields.password.value) {
                    field.rules.push(`equals:${this.fields.password.value}`)
                }else {
                    this.validateField(this.fields.password)
                }
            }
            let res = Iodine.assert(field.value, field.rules);
            field.error = res.valid ? null : res.error;
            this.isFormValid();
        },
        isFormValid() {
            this.isFormInvalid = Object.values(this.fields).some(
                (field) => field.error
            );
            return !this.isFormInvalid;
        },
        submit(e) {
            Alpine.store('users').isLoaded = false;
            fetch("api/users/add_user.php", {
                method: "POST",
                mode: "same-origin",
                credentials: "same-origin",
                body: JSON.stringify({
                    username: this.fields.username.value,
                    email: this.fields.email.value,
                    role: this.fields.role.value,
                    password: this.fields.password.value,
                }),
                headers: {
                  "Content-Type": "application/json; charset=UTF-8"
                }
              })
                .then(async (response) => {
                    if(!response.ok) {
                        let statusText = await response.text()
                        throw new Error(statusText)
                    }
                    return response.json();
                })
                .then((newUser) => {
                    Alpine.store('users').isLoaded = true
                    Alpine.store('users').addUser(newUser)
                    showAlert('alert-success', 'Success!', 'Successfully added user')
                })
                .catch(e => {
                    Alpine.store('users').isLoaded = true
                    showAlert('alert-danger', 'Error occured', `Error adding user: ${e}`, 3500)
                })
            this.clearForm()
        }
    }
}
function deleteUser(id) {
    fetch(`api/users/delete_user.php?id=${id}`)
        .then(async (res) => {
            if (!res.ok) {
                let errorMsg = await res.text()
                throw new Error(errorMsg)
            }
            return res.json()
        })
        .then(ok => {
            if (ok) {
                Alpine.store('users').deleteUser(id)
                associatedJob = Alpine.store('jobs').list.find(j => j.assigned_to == id || j.supervised_by == id)
                if(associatedJob) {
                    if(associatedJob.assigned_to == id) {
                        Alpine.store('jobs').editJob(id, {assigned_to: undefined})
                    }
                    if(associatedJob.supervised_by == id) {
                        Alpine.store('jobs').editJob(id, {supervised_by: undefined})
                    }
                    showAlert('alert-success', 'Success!', 'Successfully deleted user, please reassign or resupervise jobs associated with this user', 5000)
                    return 
                }
                showAlert('alert-success', 'Success!', 'Successfully deleted user')
            }
        }).catch(e => {
            showAlert('alert-danger', 'Error occured', `Error deleting user: ${e}`, 3500)
        })
}
function editUserRole(id, newRole) {
    Alpine.store('users').isLoaded = false
    fetch("api/users/update_user_role.php", {
        method: "POST",
        mode: "same-origin",
        credentials: "same-origin",
        body: JSON.stringify({
            id,
            role: newRole
        }),
        headers: {
            "Content-Type": "application/json; charset=UTF-8"
        }
        })
        .then(async (response) => {
            if(!response.ok) {
                let errorMsg = await response.text()
                throw new Error(errorMsg)
            }
            return response.json();
        })
        .then((ok) => {
            Alpine.store('users').isLoaded = true
            Alpine.store('users').editUser(id, {"role": newRole})
            showAlert('alert-success', 'Success!', "Successfully updated user's role")
        })
        .catch(e => {
            Alpine.store('users').isLoaded = true
            showAlert('alert-danger', 'Error occured', `Error updating user's role: ${e}`, 3500)
        })
}