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
            try {
                const res = await axios.post('api/tags/add_tag.php', {
                    label: this.fields.label.value,
                    colorcode: this.fields.colorcode.value
                }, {
                    withCredentials: true,
                })
                newTag = res.data
                if(!newTag) {
                    throw new Error('Unexpected error occured')
                }
                Alpine.store('tags').isLoaded = true
                Alpine.store('tags').addTag(newTag)
                showAlert('alert-success', 'Success!', 'Successfully added tag')
                
            } catch (e) {
                Alpine.store('tags').isLoaded = true
                showAlert('alert-danger', 'Error occured', `Error adding tag: ${e.response?.data ?? e}`, 3500)
            } finally {
                this.clearForm()
                return
            }
        },
        async submitEdit(tagId) {
            var ok = this.isFormValid();
            if( ! ok ) {
                return
            }
            Alpine.store('tags').isLoaded = false
            try {
                const res = await axios.post("api/tags/update_tag.php", {
                    id: tagId,
                    label: this.fields.label.value,
                    colorcode: this.fields.colorcode.value
                }, {
                    withCredentials: true,
                })
                const updatedTag = res.data
                if(!updatedTag) {
                    throw new Error('Unexpected error occured')
                }
                Alpine.store('tags').editTag(updatedTag.id, updatedTag)
                Alpine.store('tags').isLoaded = true
                showAlert('alert-success', 'Success!', 'Successfully updated tag')
            } catch (e) {
                Alpine.store('tags').isLoaded = true
                showAlert('alert-danger', 'Error occured', `Error updating tag: ${e.response?.data ?? e}`, 3500)
            } finally {
                this.clearForm()
                return
            }
        },
        async deleteTag(tagId) {
            Alpine.store('tags').isLoaded = false
            try {
                const res = await axios.post(`api/tags/delete_tag.php?id=${tagId}`)
                const ok = res.data
                Alpine.store('tags').isLoaded = true
                if (!ok) {
                    throw new Error("Uncaught error occured deleting tag")
                }
                Alpine.store('tags').deleteTag(tagId)
                // find jobs that the tag, 
                let associatedJobs = Alpine.store('jobs').list.filter(j => j.tags?.includes((tagId).toString()))
                if (associatedJobs?.length) {
                    for (let i = 0; i < associatedJobs.length; i++) {
                        let tagIndex = associatedJobs[i].tags.findIndex(t => t == (tagId).toString())
                        if(tagIndex !== -1) {
                            associatedJobs[i].tags.splice(tagIndex, 1)
                        }
                    }
                    showAlert('alert-success', 'Success!', 'Successfully deleted tag and removed it from associated jobs')
                    return
                }
                // delete the tag from their tags array property
                showAlert('alert-success', 'Success!', 'Successfully deleted tag')
                
            } catch (e) {
                Alpine.store('tags').isLoaded = true
                showAlert('alert-danger', 'Error occured', `Error deleting tag: ${e.response?.data ?? e}`, 3500)
            } finally {
                this.clearForm()
                return
            }
        }
    }
}