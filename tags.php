<div class="modal fade" :id="$id('tags-modal')" tabindex="-1" data-backdrop="static" role="dialog"
  aria-labelledby="tagsModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document"
    x-data="{editMode: false, addMode: false, deleteMode: false, form: tagFormData(), search: '', tagData: {}, selectedTags: []}"
    x-effect="$dispatch('tags-changed', selectedTags)"
    @set-tags.window="selectedTags = $event.detail"
  >
    <div class="modal-content">
      <div class="modal-header d-flex justify-content-between align-items-center">
        <button x-show="addMode || editMode || deleteMode" x-transition type="button" class="back icon-button"
          aria-label="Back" @click="()=> {
                        editMode = false;
                        addMode = false;
                        deleteMode = false;
                      }">
          <i class="now-ui-icons arrows-1_minimal-left"></i>
        </button>
        <h5 class="modal-title" :class="deleteMode && 'text-danger'" id="tagsModalLabel"
          x-text="editMode ? 'Edit Tag' : addMode ? 'Create Tag' : deleteMode ? 'Confirm Delete' : 'Job Tags'">
        </h5>
        <template x-data x-if="!$store.tags.isLoaded">
          <i class="ml-2 now-ui-icons loader_refresh spin"></i>
        </template>
        <button type="button" class="close icon-button" data-dismiss="modal" aria-label="Close" @click="()=> {
                        form.clearForm()
                        editMode = false;
                        addMode = false;
                        deleteMode = false;
                      }">
          <i class="now-ui-icons ui-1_simple-remove"></i>
        </button>
      </div>
      <div class="tag-preview" x-show="addMode || editMode" x-transition>
        <div class="color-label" :style="{backgroundColor: form.fields.colorcode.value}">
          <span class="label" x-text="form.fields.label.value"></span>
        </div>
      </div>
      <div class="modal-body" x-show="deleteMode">
        <p>
          The tag <q x-text="tagData.label"></q> will be deleted and removed from all jobcards that use it,
          there is no undo, continue?
        </p>
      </div>
      <div class="modal-body" x-show="!deleteMode">
        <template x-if="$store.tags.error && !addMode && !$store.tags.list.length">
          <div x-data x-transition.opacity id="tags-error-message" 
            class="error-message" style="height: unset;"
          >
            <img 
                :src="$store.tags.error.status == 500 ? './assets/img/server_error.svg' : './assets/img/no_data.svg'" 
                alt="" class="error-illustration"
                style="height: 100px;"
            >
            <span class="error-description" x-text="$store.tags.error.message"></span>
          </div>
        </template>
        <div class="form-group mb-4" x-show="!addMode && !editMode && ($store.tags.list.length > 0)" x-model="search">
          <input type="text" class="form-control" placeholder="Search tags">
        </div>

        <div x-show="editMode || addMode">
          <div class="form-group" x-id="['tag-label']">
            <label :for="$id('tag-label')">Label</label>
            <input type="text" class="form-control" x-model="form.fields.label.value" :id="$id('tag-label')" placeholder="Label"
              @blur="form.validateField(form.fields.label)">
            <span class="text-danger" x-text="form.fields.label.error"><span>
          </div>
          <div class="form-group" x-id="['tag-color']">
            <label :for="$id('tag-color')">Color Code</label>
            <input type="color" class="form-control py-1" :id="$id('tag-color')" x-model="form.fields.colorcode.value"
              @blur="form.validateField(form.fields.colorcode)">
            <span class="text-danger" x-text="form.fields.colorcode.error"><span>
          </div>
        </div>
        <template x-if="$store.tags.list.length && !addMode && !editMode">
          <div class="tags">
            <template x-for="tag in $store.tags.list.filter(t => t.label.toLowerCase().includes(search.toLowerCase()))"
              :key="tag.id">
              <div class="form-check pl-0">
                <label class="form-check-label">
                  <input class="form-check-input" type="checkbox" :value="tag.id" x-model="selectedTags"
                    :checked="selectedTags.includes(tag.id)">
                  <span class="form-check-sign">
                    <span class="check"></span>
                  </span>
                  <div class="color-label" :style="{backgroundColor: tag.colorcode}">
                    <span class="label" x-text="tag.label"></span>
                  </div>
                  <button type="button" x-show="session.role !== 'USER'" class="icon-button" @click="()=> {
                                editMode = true;
                                form.editTag(tag)
                                tagData = tag
                              }">
                    <i class="now-ui-icons ui-2_settings-90"></i>
                  </button>
                </label>
              </div>
            </template>
          </div>
        </template>
      </div>
      <div class="modal-footer" x-show="editMode">
        <button type="button" class="btn btn-info" @click="()=> {
                      validateField(form.fields.label)
                      validateField(form.fields.colorcode)

                      if (form.isFormValid()) {
                        form.submitEdit(tagData.id).then(()=> {
                          editMode = false;
                        })
                      }
                    }">Save</button>
        <button type="button" class="btn btn-danger" @click="()=> {
                      editMode = false;
                      deleteMode = true;
                    }">Delete</button>
      </div>
      <div class="modal-footer" x-show="addMode">
        <button type="button" class="btn btn-info w-100" :disabled="(addMode) && form.isFormInvalid" @click="()=> {
                      validateField(form.fields.label)
                      validateField(form.fields.colorcode)
                      if (form.isFormValid()) {
                        form.submit().then(() => addMode = false)
                      }
                    }">Create</button>
      </div>
      <div class="modal-footer" x-show="!addMode && !editMode && !deleteMode">
        <button x-show="session.role !== 'USER'" type="button" class="btn btn-secondary w-100" @click="()=> {
                        addMode = true;
                        form.editTag({label: '', colorcode: '#4C6B1F'})
                      }">
          Create New Tag
        </button>
        <small x-show="session.role === 'USER'">Select all that apply</small>
      </div>
      <div class="modal-footer" x-show="deleteMode">
        <button type="button" class="btn btn-danger w-100" @click="()=> {
                        form.deleteTag(tagData.id).then(() => {
                            deleteMode = false;

                            <!-- fields from selectedTags -->
                            index = selectedTags.findIndex(t => t.id == tagData.id);
                            if(index) {
                                selectedTags.splice(index, 1)
                            }
                        })
                      }">
          Delete
        </button>
      </div>
    </div>
  </div>
</div>