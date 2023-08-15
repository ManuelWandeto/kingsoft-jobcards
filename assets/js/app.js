const timestampFormatString = "YYYY-MM-DDTHH:mm"
function formdata() {
    Iodine.rule('laterOrEqual', (endDate, startDate) => {
        return new Date(endDate) >= new Date(startDate);
    });
    Iodine.setErrorMessage('laterOrEqual', "End date must be after or equal to '[PARAM]'");
    
    return {
        fields: {
            project: {
                value: null, error: null,
                rules: ["required", "maxLength:80", "minLength:3"]
            },
            client: {
                value: undefined, input: '', error: null,
                rules: ["required"]
            },
            description: {
                value: null, error: null,
                rules: ["required", "minLength:10"]
            },
            priority: {
                value: undefined, error: null,
                rules: ["required"]
            },
            status: {
                value: undefined, error: null,
                rules: ["required"]
            },
            assignee: {
                value: undefined, error: null,
                rules: []
            },
            supervisor: {
                value: undefined, error: null,
                rules: []
            },
            startDate: {
                value: moment().format(timestampFormatString), error: null,
                rules: ["required"]
            },
            endDate: {
                value: null, error: null,
                rules: ["required", `laterOrEqual`]
            },
            location: {
                value: null, error: null,
                rules: ["required", "maxLength:80", "minLength:3"]
            },
            completion_notes: {
                value: null, error: null,
                rules: ["minLength:10"]
            },
            issues_arrising: {
                value: null, error: null,
                rules: ["minLength:10"]
            },
            files: []
        },
        editJob({id, project, client_id, location, description, priority, status, assigned_to, supervised_by, start_date, end_date, completion_notes, issues_arrising}) {
            this.clearForm()
            clearFormErrors(this.fields)
            this.fields.location.value = location
            this.fields.client.value = client_id
            this.fields.client.input = Alpine.store("clients").getClient(client_id).name
            this.fields.project.value = project
            this.fields.description.value = description
            this.fields.priority.value = priority
            this.fields.status.value = status
            this.fields.assignee.value = assigned_to || undefined
            this.fields.supervisor.value = supervised_by || undefined
            this.fields.startDate.value = moment(start_date).format(timestampFormatString)
            this.fields.endDate.value = moment(end_date).format(timestampFormatString)
            this.fields.completion_notes.value = completion_notes?.trim()
            this.fields.issues_arrising.value = issues_arrising?.trim()
            this.fields.files = Alpine.store('jobs').jobs.find(j => j.id == id).files
            this.isFormValid()

        },
        isFormInvalid: true,
        validateField(field) {
            if(field.rules.find(rule => rule.includes('laterOrEqual'))) {
                field.rules.pop()
                if (this.fields.startDate.value) {
                    field.rules.push(`laterOrEqual:${this.fields.startDate.value}`)
                }
            }
            let res = Iodine.assert(field.value, field.rules);
            field.error = res.valid ? null : res.error;
            this.isFormValid();
        },
        isFormValid(){
            this.isFormInvalid = Object.values(this.fields).some(
                (field) => field?.error
            );
            return ! this.isFormInvalid ;
        },
        clearForm() {
            this.fields.location.value = ""
            this.fields.client.value = ""
            this.fields.client.input = ""
            this.fields.project.value = ""
            this.fields.description.value = ""
            this.fields.priority.value = undefined
            this.fields.status.value = undefined
            this.fields.assignee.value = undefined
            this.fields.supervisor.value = undefined
            this.fields.startDate.value = moment().format(timestampFormatString)
            this.fields.endDate.value = undefined
            this.fields.completion_notes.value = ""
            this.fields.issues_arrising.value = ""
            clearFormErrors(this.fields)
            // TODO: clear selected files if any
            document.querySelector('input#files').files = new DataTransfer().files
            this.fields.files = []
            this.isFormValid()
        },
        submit(e) {
            var ok = this.isFormValid();
            if( ! ok ) {
                return
            }
            Alpine.store('jobs').isLoaded = false
            
            const formData = new FormData(e.target)
            formData.set('client_id', this.fields.client.value)
            formData.set('start_date', moment(new Date(this.fields.startDate.value)).format(timestampFormatString))
            formData.set('end_date', moment(new Date(this.fields.endDate.value)).format(timestampFormatString))

            const config = {
                withCredentials: true,
                onUploadProgress: progressEvent => {
                    const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                    console.log(`upload progress: ${percentCompleted}`);
                }
            };
            
            axios.post('includes/add_job.inc.php', formData, config)
            .then(res => {
                const newJob = res.data
                Alpine.store('jobs').isLoaded = true
                Alpine.store('jobs').addJob(newJob)
                showAlert('alert-success', 'Success!', 'Successfully added job')
            })
            .catch(e => {
                Alpine.store('jobs').isLoaded = true
                showAlert('alert-danger', 'Error occured', `Error adding job: ${e.response.data}`, 3500)
            });
            this.clearForm()
        },

        submitEdit(jobId, e) {
            var ok = this.isFormValid();
            if( ! ok ) {
                return
            }
            Alpine.store('jobs').isLoaded = false
            const formData = new FormData(e.target)
            formData.append('id', jobId)
            // set fields that may be disabled on edit
            formData.set('project', this.fields.project.value)
            formData.set('priority', this.fields.priority.value)
            formData.set('description', this.fields.description.value)
            formData.set('assigned_to', this.fields.assignee.value ?? null)
            formData.set('supervised_by', this.fields.supervisor.value ?? null)
            formData.set('location', this.fields.location.value)
            formData.set('status', this.fields.status.value)
            formData.set('client_id', this.fields.client.value)
            formData.set('start_date', moment(new Date(this.fields.startDate.value)).format(timestampFormatString))
            formData.set('end_date', moment(new Date(this.fields.endDate.value)).format(timestampFormatString))

            const config = {
                withCredentials: true,
                onUploadProgress: progressEvent => {
                    const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                    console.log(`upload progress: ${percentCompleted}`);
                }
            };
            axios.post('includes/update_job.inc.php', formData, config)
            .then((res) => {
                updatedJob = res.data
                Alpine.store('jobs').isLoaded = true
                Alpine.store('jobs').editJob(updatedJob.id, updatedJob)
                showAlert('alert-success', 'Success!', 'Successfully updated job')
            })
            .catch(e => {
                Alpine.store('jobs').isLoaded = true
                showAlert('alert-danger', 'Error occured', `Error updating job: ${e.response.data}`, 3500)
            })
            this.clearForm()

        },

        finaliseJob(jobId, fields = {
            status: 'COMPLETED',
            completion_notes: this.fields.completion_notes.value,
            issues_arrising: this.fields.issues_arrising.value
        }) {
            Alpine.store('jobs').isLoaded = false

            fetch("includes/finalize_job.inc.php", {
                method: "POST",
                mode: "same-origin",
                credentials: "same-origin",
                body: JSON.stringify({
                    id: jobId,
                    ...fields
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
                .then((updatedJob) => {
                    Alpine.store('jobs').isLoaded = true
                    Alpine.store('jobs').editJob(updatedJob.id, updatedJob)
                    showAlert('alert-success', 'Completed!', 'Successfully finalized job')
                })
                .catch(e => {
                    Alpine.store('jobs').isLoaded = true
                    showAlert('alert-danger', 'Error occured', `Error updating job: ${e}`, 3500)
                })
        }
    }
};

function returnFileSize(number) {
    if (number < 1024) {
        return `${number} bytes`;
    } else if (number >= 1024 && number < 1048576) {
        return `${(number / 1024).toFixed(1)} KB`;
    } else if (number >= 1048576) {
        return `${(number / 1048576).toFixed(1)} MB`;
    }
}

function shortenFileName(filename, size = 15) {
    let portions = filename.split('.')
    if (portions[0].length <= size) {
        return portions.join('.')
    }
    return [portions[0].slice(0, size) + '---' , portions.pop()].join('.');
}

function removeFileFromFileList(filename, inputId) {
    const dt = new DataTransfer()
    const input = document.getElementById(inputId)
    const { files } = input
    
    for (let i = 0; i < files.length; i++) {
      const file = files[i]
      if (filename !== file.name)
        dt.items.add(file) // here you exclude the file. thus removing it.
    }
    
    input.files = dt.files // Assign the updates list
}

async function deleteUploadedFile(path) {
    try {
        await axios.delete('includes/delete_file.inc.php', {data: {filepath: path}})
        showAlert('alert-success', 'Success!', 'Successfully deleted file')
        return true;
    } catch (e) {
        showAlert('alert-danger', 'Error occured', `Error deleting file: ${e}`, 3500)
        return false;
    }
}

function clearFormErrors(fields) {
    Object.values(fields).forEach(field => {
        if (field?.error) {
            field.error = null
        }
    })
}

function showAlert(className, heading, message, duration=2500) {
    let slot = document.getElementById('alert-slot')
    slot.innerHTML = `
    <div class="alert fade show ${className}" role="alert">
        <h4 class="mt-0 alert-heading">${heading}</h4>
        ${message}
    </div>
    `
    setTimeout(() => {
        $('.alert').alert('close')
    }, duration);
}

function illustrateError(errorSlotId, illustrationPath, message) {
    document.getElementById(errorSlotId).classList.toggle('show')
    const errorIllustraion = document.querySelector(`#${errorSlotId} img.error-illustration`)
    const errorDescription = document.querySelector(`#${errorSlotId} span.error-description`)
    errorIllustraion.setAttribute('src', illustrationPath)
    errorDescription.textContent = message
}

function clientFormData() {
    return {
        fields: {
            name: {
                value: null, error: null,
                rules: ["required", "maxLength:80", "minLength:2"]
            },
            email: {
                value: null, error: null,
                rules: ["optional", "email"]
            },
            location: {
                value: null, error: null,
                rules: ["required", "minLength:3"]
            },
            phone: {
                value: null, error: null,
                rules: ["optional", "numeric"]
            }
        },
        editClient({name, email, phone, location}) {
            clearFormErrors(this.fields)
            this.fields.name.value = name
            this.fields.email.value = email
            this.fields.location.value = location
            this.fields.phone.value = phone
            this.isFormValid()
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
        },
        clearForm() {
            this.fields.name.value = ""
            this.fields.email.value = ""
            this.fields.location.value = ""
            this.fields.phone.value = ""
            clearFormErrors(this.fields)
            this.isFormValid()
        },
        submit(e) {
            var ok = this.isFormValid();
            if( ! ok ) {
                return
            }
            Alpine.store('clients').isLoaded = false
            fetch("includes/add_client.inc.php", {
                method: "POST",
                mode: "same-origin",
                credentials: "same-origin",
                body: JSON.stringify({
                    name: this.fields.name.value,
                    email: this.fields.email.value,
                    location: this.fields.location.value,
                    phone: this.fields.phone.value
                }),
                headers: {
                  "Content-Type": "application/json; charset=UTF-8",
                  "Accept": "application/json"
                }
              })
                .then(async (response) => {
                    if(!response.ok) {
                        let errorMsg = await response.text()
                        throw new Error(errorMsg)
                    }
                    return response.json();
                })
                .then((newClient) => {
                    Alpine.store('clients').isLoaded = true
                    Alpine.store('clients').addClient(newClient)
                    showAlert('alert-success', 'Success!', 'Successfully added client')
                })
                .catch(e => {
                    Alpine.store('clients').isLoaded = true
                    showAlert('alert-danger', 'Error occured', `Error adding client: ${e}`, 3500)
                })
            this.clearForm()
        },
        submitEdit(clientId, fields = {
            name: this.fields.name.value,
            email: this.fields.email.value,
            location: this.fields.location.value,
            phone: this.fields.phone.value,
        }) {
            var ok = this.isFormValid();
            if( ! ok ) {
                return
            }
            Alpine.store('clients').isLoaded = false
            fetch("includes/update_client.inc.php", {
                method: "POST",
                mode: "same-origin",
                credentials: "same-origin",
                body: JSON.stringify({
                    id: clientId,
                    ...fields
                }),
                headers: {
                  "Content-Type": "application/json; charset=UTF-8",
                  "Accept": "application/json"
                }
              })
                .then(async (response) => {
                    if(!response.ok) {
                        let errorMsg = await response.text()
                        throw new Error(errorMsg)
                    }
                    return response.json();
                })
                .then((updatedClient) => {
                    Alpine.store('clients').isLoaded = true
                    Alpine.store('clients').editClient(updatedClient.id, updatedClient)
                    showAlert('alert-success', 'Success!', 'Successfully updated client')
                })
                .catch(e => {
                    Alpine.store('clients').isLoaded = true
                    showAlert('alert-danger', 'Error occured', `Error updating client: ${e}`, 3500)
                })
            this.clearForm()
        }
    }
}
function tagFormData() {
    Iodine.rule('notDefault', (value, param) => value !== param);
    Iodine.setErrorMessage('notDefault', "color must not be the default color");
    return {
        fields: {
            label: {
                value: null, error: null,
                rules: ["required", "maxLength:50", "minLength:2"]
            },
            colorcode: {
                value: null, error: null,
                rules: ["required", "maxLength:7", "minLength:7", "notDefault:#4C6B1F"]
            }
        },
        editTag({label, colorcode}) {
            clearFormErrors(this.fields)
            this.fields.label.value = label
            this.fields.colorcode.value = colorcode
            this.isFormValid()
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
        },
        clearForm() {
            this.fields.label.value = ""
            this.fields.colorcode.value = "#4C6B1F"
            clearFormErrors(this.fields)
            this.isFormValid()
        },
        async submit(e) {
            var ok = this.isFormValid();
            if( ! ok ) {
                return
            }
            Alpine.store('tags').isLoaded = false

            axios.post('includes/add_tag.inc.php', {
                label: this.fields.label.value,
                colorcode: this.fields.colorcode.value
            }, {
                withCredentials: true,
            })
                .then(res => {
                    newTag = res.data
                    Alpine.store('tags').isLoaded = true
                    Alpine.store('tags').addTag(newTag)
                    showAlert('alert-success', 'Success!', 'Successfully added tag')
                })
                .catch(e => {
                    Alpine.store('tags').isLoaded = true
                    showAlert('alert-danger', 'Error occured', `Error adding tag: ${e.response.data}`, 3500)
                })
            
            this.clearForm()
            return
        },
        // submitEdit(clientId, fields = {
        //     name: this.fields.name.value,
        //     email: this.fields.email.value,
        //     location: this.fields.location.value,
        //     phone: this.fields.phone.value,
        // }) {
        //     var ok = this.isFormValid();
        //     if( ! ok ) {
        //         return
        //     }
        //     Alpine.store('clients').isLoaded = false
        //     fetch("includes/update_client.inc.php", {
        //         method: "POST",
        //         mode: "same-origin",
        //         credentials: "same-origin",
        //         body: JSON.stringify({
        //             id: clientId,
        //             ...fields
        //         }),
        //         headers: {
        //           "Content-Type": "application/json; charset=UTF-8",
        //           "Accept": "application/json"
        //         }
        //       })
        //         .then(async (response) => {
        //             if(!response.ok) {
        //                 let errorMsg = await response.text()
        //                 throw new Error(errorMsg)
        //             }
        //             return response.json();
        //         })
        //         .then((updatedClient) => {
        //             Alpine.store('clients').isLoaded = true
        //             Alpine.store('clients').editClient(updatedClient.id, updatedClient)
        //             showAlert('alert-success', 'Success!', 'Successfully updated client')
        //         })
        //         .catch(e => {
        //             Alpine.store('clients').isLoaded = true
        //             showAlert('alert-danger', 'Error occured', `Error updating client: ${e}`, 3500)
        //         })
        //     this.clearForm()
        // }
    }
}
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
            fetch("includes/signup.inc.php", {
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
function isStatusDisabled(optionValue, jobcardStatus) {
    const statusEnum = {
        REPORTED: 1,
        SCHEDULED: 2,
        ONGOING: 3,
        OVERDUE: 4
    }
    return statusEnum[optionValue] < statusEnum[jobcardStatus]
}
async function fetchClients() {
    let response = await fetch("includes/get_clients.inc.php");
    if(!response.ok) {
        throw new Error((`status: ${response.status} error: ${await response.text()}`))
    }
    let json = await response.json()
    return json;
}
async function fetchUsers() {
    let response = await fetch("includes/get_users.inc.php");
    if(!response.ok) {
        throw new Error((`status: ${response.status} error: ${await response.text()}`))
    }
    let json = await response.json()
    return json;
}
async function fetchJobs() {
    let response = await fetch("includes/get_jobs.inc.php");
    if(!response.ok) {
        throw new Error((`status: ${response.status} error: ${await response.text()}`))
    }
    
    let json = await response.json()
    return json;
}

function deleteClient(id) {
    fetch(`includes/delete_client.inc.php?id=${id}`)
        .then(async (res) => {
            if (!res.ok) {
                let errorMsg = await response.text()
                throw new Error(errorMsg)
            }
            return res.json()
        })
        .then(ok => {
            if (ok) {
                Alpine.store('clients').deleteClient(id)
                associatedJob = Alpine.store('jobs').jobs.find(j => j.client_id == id)
                if(associatedJob) {
                    Alpine.store('jobs').deleteJob(associatedJob.id)
                    showAlert('alert-success', 'Success!', 'Successfully deleted client and associated jobs')
                    return 
                }
                showAlert('alert-success', 'Success!', 'Successfully deleted client')
            }
        }).catch(e => {
            showAlert('alert-danger', 'Error occured', `Error deleting client: ${e}`, 3500)
        })
}
function deleteUser(id) {
    fetch(`includes/delete_user.inc.php?id=${id}`)
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
                associatedJob = Alpine.store('jobs').jobs.find(j => j.assigned_to == id || j.supervised_by == id)
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
    fetch("includes/update_user_role.inc.php", {
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

document.addEventListener('alpine:init', () => {
    Alpine.store('jobs', {
        jobs: [],
        filters: {
            search: {
                by: 'project',
                value: ''
            },
            priority: undefined,
            status: undefined,
            duration: {
                from: '',
                to: ''
            },
            clearFilters() {
                this.search.by = 'project',
                this.search.value = ''
                this.priority = undefined
                this.status = undefined
                this.duration.from = ''
                this.duration.to = ''
            }
        },
        applyJobFilters(jobs) {
            let results = jobs
            filters = this.filters;
            if (filters.search.value.trim()) {
                if (filters.search.by === 'client') {
                    results = results.filter(j => {
                        let client = Alpine.store('clients').getClient(j.client_id)
                        return client?.name.toLowerCase().includes(filters.search.value.toLowerCase()) ?? false
                    })
                }
                else if (filters.search.by === 'assignee' || filters.search.by === 'supervisor') {
                    if (filters.search.value?.trim().toLowerCase() === 'null') {
                        results = results.filter(j => filters.search.by === 'assignee' ? !j.assigned_to : !j.supervised_by)
                    } else {
                        results = results.filter(j => {
                            let user = Alpine.store('users').getUser(filters.search.by === 'assignee' ? j.assigned_to : j.supervised_by)
                            return user?.username.toLowerCase().includes(filters.search.value.toLowerCase()) ?? false
                        })
                    }
                }
                else {
                    results = results.filter(j => j[filters.search.by].toLowerCase().includes(filters.search.value.toLowerCase()))
                }
            }
            if(filters.priority?.trim()) {
                results = results.filter(j => j.priority === filters.priority)
            }
            if(filters.status?.trim()) {
                results = results.filter(j => j.status === filters.status)
            }
            if(filters.duration.from && filters.duration.to) {
                results = results.filter(j => {
                    return moment(j.end_date).isBetween(filters.duration.from, filters.duration.to, 'hour')
                })
            }
            return results
        },
        isLoaded: false,
        getJobs() {
            return this.applyJobFilters(this.jobs)
        },
        addJob(job) {
            this.jobs.push({...job})
        },
        editJob(jobId, fields) {
            index = this.jobs.findIndex(j => j.id == jobId);
            this.jobs[index] = {
                ...this.jobs[index],
                ...fields
            }
        },
        deleteJob(jobId) {
            index = this.jobs.findIndex(j => j.id == jobId);
            this.jobs.splice(index, 1);
        }
    })
    fetchJobs().then(jobs => {
        Alpine.store('jobs').jobs = jobs;
        Alpine.store('jobs').isLoaded = true
        if(jobs?.length === 0) {
            illustrateError('jobs-error-message', './assets/img/no_data.svg', 'You have no jobs, start adding some')
        }
    }).catch(e => {
        Alpine.store('jobs').isLoaded = true
        illustrateError('jobs-error-message', './assets/img/server_error.svg', "Internal server error occured")
    }) 
    Alpine.store('clients', {
        list: [],
        isLoaded: false,
        getClient(id) {
            return this.list.find(c => c.id == id)
        },
        addClient(client) {
            this.list.push({...client})
        },
        editClient(clientId, fields) {
            index = this.list.findIndex(c => c.id == clientId);
            this.list[index] = {
                ...this.list[index],
                ...fields
            }
        },
        deleteClient(clientId) {
            index = this.list.findIndex(c => c.id == clientId);
            this.list.splice(index, 1);
        }
    })
    fetchClients().then(clients => {
        Alpine.store('clients').list = clients;
        Alpine.store('clients').isLoaded = true
        if(clients?.length === 0) {
            illustrateError('clients-error-message', './assets/img/no_data.svg', 'Currently no clients, start adding some')
        }
    }).catch(e => {
        Alpine.store('clients').isLoaded = true
        illustrateError('clients-error-message', './assets/img/server_error.svg', "Internal server error occured")
    })
    Alpine.store('users', {
        list: [],
        isLoaded: false,
        getUser(id) {
            return this.list.find(u => u.id == id)
        },
        editUser(userId, fields) {
            index = this.list.findIndex(u => u.id == userId);
            this.list[index] = {
                ...this.list[index],
                ...fields
            }
        },
        addUser(user) {
            this.list.push({...user})
        },
        deleteUser(userId) {
            index = this.list.findIndex(u => u.id == userId);
            this.list.splice(index, 1)
        }
    })
    fetchUsers().then(users => {
        Alpine.store('users').list = users;
        Alpine.store('users').isLoaded = true
    }).catch(e=> {
        Alpine.store('users').isLoaded = true
        illustrateError('users-error-message', './assets/img/server_error.svg', "Internal server error occured")
    })
    Alpine.store('tags', {
        list: [],
        isLoaded: false,
        getTag(id) {
            return this.list.find(t => t.id == id)
        },
        editTag(tagId, fields) {
            index = this.list.findIndex(t => t.id == tagId);
            this.list[index] = {
                ...this.list[index],
                ...fields
            }
        },
        addTag(tag) {
            this.list.push({...tag})
        },
        deleteTag(tagId) {
            index = this.list.findIndex(t => t.id == tagId);
            this.list.splice(index, 1)
        }
    })
})