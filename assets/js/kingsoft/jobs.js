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
                value: undefined, input: '', data: null, error: null,
                rules: ["required"]
            },
            reporter: {
                value: null, error: null,
                rules: ["optional", "minLength:3", "maxLength:50"]
            },
            reporterContacts: {
                value: null, error: null,
                rules: ["optional", "numeric"]
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
            files: [],
            tags: []
        },
        editJob({
            id, 
            project, 
            client_id, 
            location, 
            reported_by,
            reporter_contacts,
            description, 
            priority, 
            status, 
            assigned_to, 
            supervised_by, 
            start_date, 
            end_date, 
            completion_notes, 
            issues_arrising
        }) {
            this.clearForm()
            clearFormErrors(this.fields)
            const job = Alpine.store('jobs').list.find(j => j.id == id)
            this.fields.location.value = location
            this.fields.client.value = client_id
            this.fields.client.input = Alpine.store("clients").getClient(client_id).name
            this.fields.project.value = project
            this.fields.reporter.value = reported_by ?? null
            this.fields.reporterContacts.value = reporter_contacts ?? null
            this.fields.description.value = description
            this.fields.priority.value = priority
            this.fields.status.value = status
            this.fields.assignee.value = assigned_to || undefined
            this.fields.supervisor.value = supervised_by || undefined
            this.fields.startDate.value = moment(start_date).format(timestampFormatString)
            this.fields.endDate.value = moment(end_date).format(timestampFormatString)
            this.fields.completion_notes.value = completion_notes?.trim()
            this.fields.issues_arrising.value = issues_arrising?.trim()
            this.fields.files = job.files ?? []
            this.fields.tags = job.tags?.map(t => t) ?? []

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
            this.fields.reporter.value = ""
            this.fields.reporterContacts.value = ""
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
            this.fields.tags.splice(0, this.fields.tags.length)
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
            formData.set('tags[]', this.fields.tags)
            formData.set('reported_by', this.fields.reporter.value ?? '')
            formData.set('reporter_contact', this.fields.reporterContacts.value ?? '')

            const config = {
                withCredentials: true,
                onUploadProgress: progressEvent => {
                    const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                    console.log(`upload progress: ${percentCompleted}`);
                }
            };

            axios.post('api/jobs/add_job.php', formData, config)
            .then(res => {
                Alpine.store('jobs').isLoaded = true
                if(!res.data.id) {
                    throw new Error('Error occured adding job') 
                }
                const newJob = res.data
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
            formData.set('reported_by', this.fields.reporter.value ?? '')
            formData.set('reporter_contact', this.fields.reporterContacts.value ?? '')
            formData.set('description', this.fields.description.value)
            formData.set('assigned_to', this.fields.assignee.value ?? '')
            formData.set('supervised_by', this.fields.supervisor.value ?? '')
            formData.set('location', this.fields.location.value)
            formData.set('status', this.fields.status.value)
            formData.set('client_id', this.fields.client.value)
            formData.set('start_date', moment(new Date(this.fields.startDate.value)).format(timestampFormatString))
            formData.set('end_date', moment(new Date(this.fields.endDate.value)).format(timestampFormatString))
            formData.set('tags[]', this.fields.tags)


            const config = {
                withCredentials: true,
                onUploadProgress: progressEvent => {
                    const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                    console.log(`upload progress: ${percentCompleted}`);
                }
            };
            axios.post('api/jobs/update_job.php', formData, config)
            .then((res) => {
                if(!res.data?.id) {
                    throw new Error('Uncaught error updating job')
                }
                updatedJob = res.data
                Alpine.store('jobs').isLoaded = true
                Alpine.store('jobs').editJob(updatedJob.id, updatedJob)
                showAlert('alert-success', 'Success!', 'Successfully updated job')
            })
            .catch(e => {
                Alpine.store('jobs').isLoaded = true
                showAlert('alert-danger', 'Error occured', `Error updating job: ${e.response?.data ?? e}`, 3500)
            })
            this.clearForm()

        },

        finaliseJob(jobId, fields = {
            status: 'COMPLETED',
            completion_notes: this.fields.completion_notes.value,
            issues_arrising: this.fields.issues_arrising.value
        }) {
            Alpine.store('jobs').isLoaded = false

            fetch("api/jobs/finalize_job.php", {
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

function isStatusDisabled(optionValue, jobcardStatus) {
    const statusEnum = {
        REPORTED: 1,
        SCHEDULED: 2,
        ONGOING: 3,
        OVERDUE: 4
    }
    return statusEnum[optionValue] < statusEnum[jobcardStatus]
}