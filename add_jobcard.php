<div class="row" x-show="showJobcardForm" x-cloak x-transition>
  <div class="col">
    <div 
      class="card card-chart" 
      x-data='{editMode: false, job: {}}' 
      @edit-job.window="()=> {
        editMode=true,
        job = $event.detail
      }" 
      id="jobcard-form"
      :style = "editMode && {border: '1px solid #E86252'}"
    >
      <div class="card-header">
        <h5 class="card-category" x-text="editMode ? moment(job.created_on).format('YYYY-MM-DD') : moment(Date.now()).format('YYYY-MM-DD')"></h5>
        <h4 class="card-title" x-text="editMode ? 'Edit Jobcard' : 'Add Jobcard'"></h4>

      </div>
      <div class="card-body">
        <form method="POST" enctype="multipart/form-data" 
          x-data="formdata()" @edit-job.window="editJob(job)" @submit.prevent="editMode ? ()=>{
          submitEdit(job.id, $event)
          document.getElementById('jobs-table').scrollIntoView({behavior: 'smooth', block: 'start'})
          editMode = false
        } : ()=> {
          submit($event)
          document.getElementById('jobs-table').scrollIntoView({behavior: 'smooth', block: 'start'})
        }">
          <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-4 p-3">
            <div class="form-group pr-sm-2">
              <label for="project">Project</label>
              <input 
                name="project"
                type="text" 
                :disabled="editMode"
                x-model="fields.project.value" 
                x-on:blur="validateField(fields.project)" 
                :class="fields.project.error ? 'border-danger' : ''"
                id="project" required class="form-control" 
                placeholder="Enter project name">
              <span class="text-danger" x-text="fields.project.error" x-cloak></span>
            </div>
            <div class="form-group pr-xl-2">
              <label for="select-client">Client name</label>
              <div class="clients dropdown" x-data="{isExpanded: false}">
                <input 
                  name="client_id"
                  class="form-control dropdown-toggle" 
                  id="select-client" 
                  autocomplete="off"
                  data-toggle="dropdown"
                  :disabled="editMode" 
                  data-toggle="dropdown" 
                  data-offset="0,-10"
                  aria-haspopup="true"
                  :aria-expanded= "isExpanded"
                  placeholder="Select client"
                  @focus="()=> {
                    $('.dropdown-toggle').dropdown()
                    isExpanded = true
                  }"
                  @blur="($event)=> {
                    isExpanded = false
                    let client = $store.clients.list.find(c => c.name.toLowerCase() === $event.target.value.trim().toLowerCase())
                    if (!client) {
                      fields.client.value = undefined
                      fields.location.value = null
                    } else {
                      fields.client.value = client.id
                      fields.location.value = client.location
                    }
                    
                    validateField(fields.client)
                  }" 
                  x-model="fields.client.input"
                  required
                  >
                </input>
                <i class="now-ui-icons arrows-1_minimal-down" :class="isExpanded && 'isOpen'"></i>
                <div class="dropdown-menu p-3" aria-labelledby="dropdownMenuButton">
                  <div class="clients-list">
                    <template x-for="client in $store.clients.list.filter(c => c.name.toLowerCase().includes(fields.client.input.toLowerCase()))">
                      <button type="button" x-text="client.name" class="px-2 py-3"
                        @click="() => {
                          fields.client.input = client.name;
                          fields.client.value = client.id
                          fields.location.value = client.location
                          validateField(fields.client)
                        }">
                      </button>
                    </template>
                  </div>
                </div>
              </div>
              <span style="position: absolute;" class="text-danger" x-text="fields.client.error" x-cloak></span>
            </div>
            <div class="form-group pr-sm-2">
              <label for="assignee">Assigned to</label>
              <!-- Can set assigned_to and supervisor fields to be disabled if: "editMode && fields.assignee.value.trim()" -->
              <select name="assigned_to" class="custom-select" id="assignee" 
                x-model="fields.assignee.value" 
                :disabled="editMode && (job.status === 'COMPLETED' || job.status === 'CANCELLED')"
              >
                <option selected disabled value="">Select assignee</option>
                <template x-for="user in $store.users.list">
                  <option :value="user.id" x-text="user.username"></option>
                </template>
              </select>
            </div>
            <div class="form-group">
              <label for="supervisor">Supervised by</label>
              <select name="supervised_by" class="custom-select" id="supervisor" 
                x-model="fields.supervisor.value"
                :disabled="editMode && (job.status === 'COMPLETED' || job.status === 'CANCELLED')"
              >
                <option selected disabled value="">Select supervisor</option>
                <template x-for="user in $store.users.list">
                  <option :value="user.id" x-text="user.username"></option>
                </template>
              </select>
            </div>
          </div>
          <div class="row px-3 form-group">
            <label for="description">Description</label>
            <textarea 
              name="description" 
              :disabled="editMode && (job.status === 'COMPLETED' || job.status === 'CANCELLED')"
              x-model="fields.description.value" 
              x-on:blur="validateField(fields.description)" 
              :class="fields.description.error ? 'border-danger' : ''"
              class="form-control" id="description" 
              required rows="30">
            </textarea>
            <span class="text-danger" x-text="fields.description.error" x-cloak></span>
          </div>
          <div class="row p-3">
            <div class="form-group col-sm-6 col-xl-3 p-0 pr-sm-2 pr-md-0">
              <label for="location" >Location</label>
              <input 
                name="location"
                type="text" 
                :disabled="editMode"
                x-model="fields.location.value" 
                x-on:blur="validateField(fields.location)" 
                :class="fields.location.error ? 'border-danger' : ''"
                id="location" class="form-control" required 
                placeholder="Enter site location">
              <span class="text-danger" x-text="fields.location.error" x-cloak></span>
            </div>
            <div class="form-group col-sm-6 col-xl-3 p-0 px-md-2">
              <label for="status">Status</label>
              <select 
                class="custom-select" id="status"
                name="status"
                required x-model="fields.status.value" 
                :disabled="editMode && (job.status === 'COMPLETED' || job.status === 'CANCELLED')"
              >
                <option selected disabled value="">Select status</option>
                <option value="REPORTED" :disabled="editMode && isStatusDisabled('REPORTED', job.status)">Reported</option>
                <option value="SCHEDULED" :disabled="editMode && isStatusDisabled('SCHEDULED', job.status)">Scheduled</option>
                <option value="ONGOING" 
                  :disabled="
                    editMode 
                    && isStatusDisabled('ONGOING', job.status) 
                    && (job.status === 'OVERDUE' && moment(fields.endDate.value).isBefore(Date.now(), 'hour'))
                  "
                >Ongoing</option>
                <option value="OVERDUE" :disabled="editMode && isStatusDisabled('OVERDUE', job.status)">Overdue</option>
                <option value="COMPLETED">Completed</option>
                <option value="CANCELLED">Cancelled</option>
                <option value="SUSPENDED">Suspended</option>
              </select>
            </div>
            <div class="form-group col-sm p-0">
              <label for="duration">
                <span x-show="!fields.startDate.value && !fields.endDate.value">Duration: </span> 
                <span x-show="fields.startDate.value || fields.endDate.value">
                  From: 
                  <span x-text="moment(fields.startDate.value).format('YYYY-MM-DD [at:] h:mm A')"></span>
                  <strong>To:</strong> 
                  <span x-text="fields.endDate.value && moment(fields.endDate.value).format('YYYY-MM-DD [at:] h:mm A')"></span>
                </span>
              </label>
              <div class="custom-datepicker" id="duration">
                <input 
                  type="datetime-local" 
                  name="start_date"
                  x-model="fields.startDate.value" 
                  x-on:blur="validateField(fields.startDate)" 
                  :class="fields.startDate.error ? 'border-danger' : ''"
                  :disabled="editMode"
                  id="startDate" required>
                <input 
                  type="datetime-local"
                  name="end_date"
                  :disabled="editMode && (job.status === 'COMPLETED' || job.status === 'CANCELLED')"
                  x-model="fields.endDate.value" 
                  x-on:blur="validateField(fields.endDate)" 
                  :class="fields.endDate.error ? 'border-danger' : ''" 
                  id="endDate" required>
              </div>
              <span class="text-danger" x-text="fields.startDate.error" x-cloak></span>
              <span class="text-danger" x-text="fields.endDate.error" x-cloak></span>
            </div>
          </div>
          <div class="row px-3 row-cols-1 row-cols-md-2">
            <div class="form-group pr-md-3">
              <label for="on-completion-notes">Completion notes</label>
              <textarea name="completion_notes" class="form-control" id="on-completion-notes" rows="30" x-model="fields.completion_notes.value"></textarea>
            </div>
            <div class="form-group">
              <label for="issues-arising">Issues arrising</label>
              <textarea name="issues_arrising" class="form-control" id="issues-arising" rows="30" x-model="fields.issues_arrising.value"></textarea>
            </div>
          </div>
          <div class="form-group mt-2">
              <label for="files">Attach related files</label>
              <input type="file" id="files" name ="attachments[]" class="form-control" 
                multiple accept=".doc,.docx,.pdf,.csv,.xlsx,image/*"
                @change="()=>{
                  let inputFiles = Array.from($event.target.files)
                  if (!editMode || !fields.files?.length) {
                    fields.files = inputFiles
                    return
                  }
                  fields.files = [...fields.files, ...inputFiles]
                }"
              >
              <template x-if="fields.files?.length">
                <div class="selected-files mt-3 px-2" x-show="fields.files?.length" x-cloak x-transition>
                  <template x-for="file in fields.files" :key="index">
                    <div class="file-info">
                      <i class="now-ui-icons" :class="file.type.includes('image') ? 'design_image' : 'files_paper'"></i>
                      <span class="name" x-text="shortenFileName(file.name)"></span>
                      <span class="size" x-text="returnFileSize(file.size)"></span>
                      <template x-if="!file.db_path || file.db_path.includes(`user_${session.id}`)">
                        <div x-id="['delete-file']">
                          <button type="button" class="icon-button" @click="()=>{
                            const i = fields.files.findIndex(f => f.name === file.name)
                            if(!file.db_path) {
                              removeFileFromFileList(file.name, 'files')
                              fields.files.splice(i, 1)
                              return
                            }
                            $(`#${$id('delete-file')}`).modal('show')
                          }">
                            <i style="color: #dc3545;" id="deletefile" class="now-ui-icons ui-1_simple-remove"></i>
                          </button>
                          <!-- Modal -->
                          <div class="modal fade" :id="$id('delete-file')" tabindex="-1" role="dialog" aria-labelledby="deletefile" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                              <div class="modal-content">
                                <div class="modal-header">
                                  <h5 class="modal-title">Are you sure?</h5>
                                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                  </button>
                                </div>
                                <div class="modal-body">
                                  <p x-text="`This will delete the uploaded file: ${file.name} from the server, continue?`"></p>
                                </div>
                                <div class="modal-footer">
                                  <button type="button" class="btn btn-secondary mr-3" data-dismiss="modal">Close</button>
                                  <button type="button" class="btn btn-primary" data-dismiss="modal" @click = "() => {
                                    deleteUploadedFile(file.db_path).then(ok => {
                                      const i = fields.files.findIndex(f => f.name === file.name)
  
                                      fields.files.splice(i, 1)
                                      job.files = job.files.filter(f => f.name !== file.name)
                                      $(`#${$id('delete-file')}`).modal('hide')
                                    }).catch(e => {
                                      console.log(e)
                                      $(`#${$id('delete-file')}`).modal('hide')
                                    })
                                  }">Delete file</button>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </template>
                    </div>
                  </template>
                </div>
              </template>
          </div>
          <div class="form-group action-group">
            <template x-if="editMode">
             <button 
              class="icon-button" 
              style="border: 1px dashed #dc3545; border-radius: 50%; padding: 6px"
              @click="()=> {
                editMode = false;
                clearForm();
              }"
            >
              <i style="color: #dc3545; font-size: 1.2rem;" class="now-ui-icons ui-1_simple-remove"></i>
             </button>
            </template>
            <select class="custom-select" name="priority" id="priority" required x-model="fields.priority.value"
              :disabled="editMode && (job.status === 'COMPLETED' || job.status === 'CANCELLED')"
            >
              <option selected disabled value="">Set priority</option>
              <option value="URGENT">Urgent</option>
              <option value="MEDIUM">Medium</option>
              <option value="LOW">Low</option>
            </select>
            <button type="submit" :disabled="isFormInvalid" class="btn btn-round btn-fab btn-outline-default btn-icon">
              <i class="now-ui-icons" :class="editMode ? 'ui-1_check' : 'ui-1_simple-add'"></i>
            </button>
          </div>
        </form>
      </div>
      <div class="card-footer">
        
      </div>
    </div>
  </div>
</div>