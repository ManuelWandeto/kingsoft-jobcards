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
            contactPerson: {
                value: null, error: null,
                rules: ["optional", "minLength:3", "maxLength:50"]
            },
            phone: {
                value: null, error: null,
                rules: ["optional", "numeric"]
            }
        },
        editClient({name, email, contact_person, phone, location}) {
            clearFormErrors(this.fields)
            this.fields.name.value = name
            this.fields.email.value = email
            this.fields.location.value = location
            this.fields.contactPerson.value = contact_person
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
            this.fields.contactPerson.value = ""
            clearFormErrors(this.fields)
            this.isFormValid()
        },
        submit(e) {
            var ok = this.isFormValid();
            if( ! ok ) {
                return
            }
            Alpine.store('clients').isLoaded = false
            fetch("api/clients/add_client.php", {
                method: "POST",
                mode: "same-origin",
                credentials: "same-origin",
                body: JSON.stringify({
                    name: this.fields.name.value,
                    email: this.fields.email.value,
                    location: this.fields.location.value,
                    contact_person: this.fields.contactPerson.value,
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
            contact_person: this.fields.contactPerson.value,
            location: this.fields.location.value,
            phone: this.fields.phone.value,
        }) {
            var ok = this.isFormValid();
            if( ! ok ) {
                return
            }
            Alpine.store('clients').isLoaded = false
            fetch("api/clients/update_client.php", {
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
function deleteClient(id) {
    fetch(`api/clients/delete_client.php?id=${id}`)
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
                associatedJob = Alpine.store('jobs').list.find(j => j.client_id == id)
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