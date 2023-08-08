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
        <form method="POST" x-data="formdata()" @edit-job.window="editJob(job)" @submit.prevent="editMode ? ()=>{
          submitEdit(job.id)
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
              <label for="client-name">Client name</label>
              <select 
                class="custom-select" 
                id="client-name" 
                :disabled="editMode" 
                required 
                x-model="fields.client.value"
                @change="$event => {
                  let client = $store.clients.getClient($event.target.value)
                  fields.location.value = client.location
                }"
              >
                <option selected disabled value="">Select client</option>
                <template x-for="client in $store.clients.list">
                  <option :value="client.id" x-text="client.name"></option>
                </template>
              </select>
              <span class="text-danger" x-text="fields.client.error" x-cloak></span>
            </div>
            <div class="form-group pr-sm-2">
              <label for="assignee">Assigned to</label>
              <!-- Can set assigned_to and supervisor fields to be disabled if: "editMode && fields.assignee.value.trim()" -->
              <select class="custom-select" id="assignee" 
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
              <select class="custom-select" id="supervisor" 
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
            <div class="form-group col-sm-6 col-md-3 p-0 pr-sm-2 pr-md-0">
              <label for="location" >Location</label>
              <input 
                type="text" 
                :disabled="editMode"
                x-model="fields.location.value" 
                x-on:blur="validateField(fields.location)" 
                :class="fields.location.error ? 'border-danger' : ''"
                id="location" class="form-control" required 
                placeholder="Enter site location">
              <span class="text-danger" x-text="fields.location.error" x-cloak></span>
            </div>
            <div class="form-group col-sm-6 col-md-3 p-0 px-md-2">
              <label for="status">Status</label>
              <select 
                class="custom-select" id="status" 
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
            <div class="form-group col-sm col-md-6 p-0">
              <label for="duration">
                <span x-show="!fields.startDate.value && !fields.endDate.value">Duration: </span> 
                <span x-show="fields.startDate.value || fields.endDate.value">
                  From: <span x-text="fields.startDate.value"></span>
                  To: <span x-text="fields.endDate.value"></span>
                </span>
              </label>
              <div class="custom-datepicker" id="duration">
                <input 
                  type="date" 
                  x-model="fields.startDate.value" 
                  x-on:blur="validateField(fields.startDate)" 
                  :class="fields.startDate.error ? 'border-danger' : ''"
                  :disabled="editMode"
                  id="startDate" required>
                <input 
                  type="date"
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
              <textarea name="on-completion-notes" class="form-control" id="on-completion-notes" rows="30" x-model="fields.completion_notes.value"></textarea>
            </div>
            <div class="form-group">
              <label for="issues-arising">Issues arrising</label>
              <textarea name="issues-arising" class="form-control" id="issues-arising" rows="30" x-model="fields.issues_arrising.value"></textarea>
            </div>
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
            <select class="custom-select" id="priority" required x-model="fields.priority.value"
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