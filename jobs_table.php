<div class="row">
  <div class="col-md-12">
    <div class="card jobs-card" id="jobs-table">
        <div class="card-header" style="display: flex; align-items: center; gap: 12px">
            <h4 class="card-title"> All Jobs </h4>
            <template x-data x-if="!$store.jobs.isLoaded">
                <i class="now-ui-icons loader_refresh spin"></i>
            </template>
            <template x-data x-cloak x-if="$store.jobs.jobs.length > 0">
                <div class="filters" x-data>
                    <div class="search dropdown">
                        <i class="now-ui-icons ui-1_zoom-bold"></i>
                        <input type="text" x-model="$store.jobs.filters.search.value" name="search" placeholder="Search by:">
                        <button class="dropdown-toggle icon-button" data-toggle="dropdown" data-offset="-10,20"
                            aria-haspopup="true" aria-expanded="false">
                        </button>
                        <span class="search-by" x-text="$store.jobs.filters.search.by?.slice(0, 1).toUpperCase()" x-transition
                            x-cloak>
                        </span>
                        <div class="dropdown-menu dropdown-menu-right">
                            <div class="form-check">
                                <input class="form-check-input" x-model="$store.jobs.filters.search.by" name="search_by"
                                    type="radio" id="search_by_project" value="project" checked>
                                <label class="form-check-label" for="search_by_project">
                                    Project
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" x-model="$store.jobs.filters.search.by" name="search_by"
                                    type="radio" id="search_by_client" value="client">
                                <label class="form-check-label" for="search_by_client">
                                    Client
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" x-model="$store.jobs.filters.search.by" name="search_by"
                                    type="radio" id="search_by_assignee" value="assignee">
                                <label class="form-check-label" for="search_by_assignee">
                                    Assignee
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" x-model="$store.jobs.filters.search.by" name="search_by"
                                    type="radio" id="search_by_supervisor" value="supervisor">
                                <label class="form-check-label" for="search_by_supervisor">
                                    Supervisor
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" x-model="$store.jobs.filters.search.by" name="search_by"
                                    type="radio" id="search_by_location" value="location">
                                <label class="form-check-label" for="search_by_location">
                                    Location
                                </label>
                            </div>
                            <div class="dropdown-divider"></div>
                        </div>
                    </div>
                    <div class="dropdown filter-by">
                        <button class="round-icon-button dropdown-toggle" data-offset="-10,20" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="now-ui-icons design_bullet-list-67"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right p-3">
                            <span class="mb-2">Filter by</span>
                            <div class="dropdown-divider"></div>
                            <form @submit.prevent>
                                <div class="form-group mb-2">
                                    <label for="filter_by_priority" class="mb-2">
                                        Priority
                                        <template x-if="$store.jobs.filters.priority">
                                            <button class="icon-button" @click="$store.jobs.filters.priority = undefined">
                                                <i class="now-ui-icons ui-1_simple-delete"></i>
                                            </button>
                                        </template>
                                    </label>
                                    <select class="custom-select" id="filter_by_priority" x-model="$store.jobs.filters.priority">
                                        <option selected disabled value="">Set priority</option>
                                        <option value="URGENT">Urgent</option>
                                        <option value="MEDIUM">Medium</option>
                                        <option value="LOW">Low</option>
                                    </select>
                                </div>
                                <div class="form-group mb-2">
                                    <label for="filter_by_status" class="mb-2">
                                        Status
                                        <template x-if="$store.jobs.filters.status">
                                            <button class="icon-button" @click="$store.jobs.filters.status = undefined">
                                                <i class="now-ui-icons ui-1_simple-delete"></i>
                                            </button>
                                        </template>
                                    </label>
                                    <select class="custom-select" id="filter_by_status" x-model="$store.jobs.filters.status">
                                        <option selected disabled value="">Select status</option>
                                        <option value="REPORTED">Reported</option>
                                        <option value="SCHEDULED">Scheduled</option>
                                        <option value="ONGOING">Ongoing</option>
                                        <option value="OVERDUE">Overdue</option>
                                        <option value="COMPLETED">Completed</option>
                                        <option value="CANCELLED">Cancelled</option>
                                        <option value="SUSPENDED">Suspended</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <div class="form-group">
                                        <label for="duration_start">From</label>
                                        <input 
                                        id="duration_start"
                                        type="date" 
                                        class="form-control"
                                        x-model="$store.jobs.filters.duration.from"
                                        >
                                    </div>
                                    <div class="form-group">
                                        <label for="duration_end">To</label>
                                        <input 
                                        id="duration_end"
                                        type="date" 
                                        class="form-control"
                                        x-model="$store.jobs.filters.duration.to"
                                        >
                                    </div>
                                </div>
                                <div class="dropdown-divider"></div>
                                <button class="btn btn-danger" style="width: 100%;" @click="$store.jobs.filters.clearFilters()">
                                    Clear Filters
                                </button>
        
                            </form>
                        </div>
                    </div>
                </div>
            </template>
        </div>
        <div class="card-body">
            <div x-data x-show="!($store.jobs.jobs.length > 0)" id="jobs-error-message" class="error-message">
                <img src="" alt="" class="error-illustration">
                <span class="error-description"></span>
            </div>
            <template x-data x-cloak x-if="$store.jobs.getJobs()?.length > 0">
                <table class="table alltime-jobs table-responsive" 
                    :style="{maxHeight: (showJobcardForm || showUserSection) ? '500px' : '800px'}" 
                    x-data
                >
                    <thead class=" text-primary" style="white-space: nowrap;">
                        <th>
                            Created on
                        </th>
                        <th>
                            Project
                        </th>
                        <th style="min-width: 100px;">
                            Client
                        </th>
                        <th style="min-width: 240px;">
                            Description
                        </th>
                        <th>
                            Priority
                        </th>
                        <th>
                            Assigned to
                        </th>
                        <th>
                            Supervised by
                        </th>
                        <th style="min-width: 100px;">
                            Site
                        </th>
                        <th style="min-width: 250px;">
                            Duration
                        </th>
                        <th style="min-width: 200px;">
                            Status
                        </th>
                        <th>
                            Completion notes
                        </th>
                        <th>
                            Issues arrising
                        </th>
                        <th>Actions</th>
                    </thead>
                    <tbody>
                        <template x-for="job in $store.jobs.getJobs()">
                            <tr>
                                <td>
                                    <div class="datetime">
                                        <span class="date" x-text="moment(job.created_at).format('YYYY-MM-DD')"></span>
                                        <span class="time" x-text="moment(job.created_at).format('h:mm A')"></span>  
                                    </div>
                                </td>
                                <td x-text="job.project"></td>
                                <td x-text="$store.clients.getClient(job.client_id)?.name || 'not found'"></td>
                                <td x-text="job.description"></td>
                                <td x-text="job.priority" :class="job.priority.toLowerCase()"></td>
                                <td x-text="$store.users.getUser(job.assigned_to)?.username || 'Null'"></td>
                                <td x-text="$store.users.getUser(job.supervised_by)?.username || 'Null'"></td>
                                <td x-text="job.location"></td>
                                <td>
                                    <div class="duration">
                                        <div class="datetime">
                                            <span class="date" x-text="moment(job.start_date).format('YYYY-MM-DD')"></span>
                                            <span class="time" x-text="moment(job.start_date).format('h:mm A')"></span>
                                        </div>
                                        -
                                        <div class="datetime">
                                            <span class="date" x-text="moment(job.end_date).format('YYYY-MM-DD')"></span>
                                            <span class="time" x-text="moment(job.end_date).format('h:mm A')"></span>
                                        </div>
                                    </div>
                                </td>
                                <td x-text="job.status" :class="job.status.toLowerCase()"></td>
                                <td>
                                    <template x-if="job.completion_notes?.trim()">
                                        <div x-id="['completion_notes']">
                                            <button type="button" class="btn btn-info" data-toggle="modal"
                                                :data-target="'#'+$id('completion_notes', job.id)">
                                                <i class="now-ui-icons files_paper"></i>
                                            </button>
        
                                            <!-- Modal -->
                                            <div class="modal fade" :id="$id('completion_notes', job.id)" tabindex="-1"
                                                role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                <div class="modal-dialog" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Completion Notes</h5>
                                                            <button type="button" class="close" data-dismiss="modal"
                                                                aria-label="Close">
                                                                <span aria-hidden="true">&times;</span>
                                                            </button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="container" x-text="job.completion_notes.trim()">
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary"
                                                                data-dismiss="modal">Close</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- End Of Modal -->
                                        </div>
                                    </template>
                                </td>
                                <td>
                                    <template x-if="job.issues_arrising?.trim()">
                                        <div x-id="['issues_arrising']">
                                            <button type="button" class="btn btn-primary" data-toggle="modal"
                                                :data-target="'#'+$id('issues_arrising', job.id)">
                                                <i class="now-ui-icons files_paper"></i>
                                            </button>
        
                                            <!-- Modal -->
                                            <div class="modal fade" :id="$id('issues_arrising', job.id)" tabindex="-1"
                                                role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                <div class="modal-dialog" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Issues arrising</h5>
                                                            <button type="button" class="close" data-dismiss="modal"
                                                                aria-label="Close">
                                                                <span aria-hidden="true">&times;</span>
                                                            </button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="container" x-text="job.issues_arrising.trim()">
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary"
                                                                data-dismiss="modal">Close</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- End Of Modal -->
                                        </div>
                                    </template>
                                </td>
                                <td>
                                    <div class="job-actions" x-id="['actions']">
                                        <button style="color: #2CA8FF;" class="icon-button" @click="() => {
                                            $dispatch('edit-job', job)
                                            showJobcardForm = true
                                            scrollTo('jobcard-form', 200)
                                        }">
                                            <i class="now-ui-icons ui-2_settings-90"></i>
                                        </button>
                                        <template x-if="job.status !== 'COMPLETED' && job.status !== 'CANCELLED'">
                                            <button style="color: #18ce0f;" type="button" class="icon-button" data-toggle="modal"
                                                :data-target="'#'+$id('actions', 'close_' + job.id)">
                                                <i class="now-ui-icons ui-1_check"></i>
                                            </button>
                                        </template>
        
                                        <!-- Modal -->
                                        <div class="modal fade" :id="$id('actions', 'close_' + job.id)" tabindex="-1"
                                            role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                            <div class="modal-dialog" role="document">
                                                <div class="modal-content" x-data="formdata()">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Finalise Job</h5>
                                                        <button type="button" class="close" data-dismiss="modal"
                                                            aria-label="Close">
                                                            <span aria-hidden="true">&times;</span>
                                                        </button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="container">
                                                            <form action="" @submit.prevent="">
                                                                <div class="row px-3 row-cols-1">
                                                                    <div class="form-group">
                                                                        <label for="closing-completion-notes">Completion
                                                                            notes</label>
                                                                        <textarea name="closing-completion-notes"
                                                                            class="form-control" id="closing-completion-notes"
                                                                            rows="40"
                                                                            x-model="fields.completion_notes.value"></textarea>
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label for="closing-issues-arising">Issues
                                                                            arrising</label>
                                                                        <textarea name="closing-issues-arising"
                                                                            class="form-control" id="closing-issues-arising"
                                                                            rows="40"
                                                                            x-model="fields.issues_arrising.value"></textarea>
                                                                    </div>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button @click="finaliseJob(job.id)" type="button"
                                                            class="btn btn-secondary" data-dismiss="modal">
                                                            Save and Close
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- End Of Modal -->
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </template>
        </div>
    </div>
  </div>
</div>