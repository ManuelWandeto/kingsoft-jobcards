<div class="row">
  <div class="col-md-12">
    <div class="card jobs-card" id="jobs-table" x-data="{oldFilters: JSON.parse(JSON.stringify($store.jobs.filters))}">
        <div class="card-header">
            <h4 class="card-title"> All Jobs </h4>
            <template x-data x-if="!$store.jobs.isLoaded && $store.jobs.page === 1">
                <i class="now-ui-icons loader_refresh spin"></i>
            </template>
            <template x-data 
                x-init="$watch('$store.jobs.filters', (filters)=> {
                    let hasChanged = filters.search.value !== oldFilters.search.value
                                ||   filters.priority.join() !== oldFilters.priority.join()
                                ||   filters.status.join() !== oldFilters.status.join()
                                ||   filters.tags.join() !== oldFilters.tags.join()
                                ||   filters.orderBy !== oldFilters.orderBy
                                ||   filters.duration.from !== oldFilters.duration.from
                                ||   filters.duration.to !== oldFilters.duration.to

                    if(hasChanged) {
                        $store.jobs.page = 1
                        $store.jobs.getJobs()
                    }
                    oldFilters = JSON.parse(JSON.stringify(filters))
                })"
                x-if="$store.jobs.error != 500"
            >
                <?php require_once('filters.php') ?>
            </template>
        </div>
        <template x-if="$store.jobs.filters.tags.length">
            <div class="filter-tags pt-2 px-3">
                <template x-for="tagId in $store.jobs.filters.tags" :key="tagId">
                    <div x-data="{tagData: $store.tags.getTag(tagId)}" :style="{borderColor: tagData.colorcode}">
                        <span x-text="tagData.label"></span>
                        <button type="button" class="icon-button" @click= "()=> {
                            index = $store.jobs.filters.tags.findIndex(t => t == tagId);
                            $store.jobs.filters.tags.splice(index, 1)
                        }">
                            <i style="color: #dc3545;" class="now-ui-icons ui-1_simple-remove"></i>
                        </button>
                    </div>
                </template>
            </div>
        </template>
        <div class="card-body">
            <template x-if="$store.jobs.error && !$store.jobs.list.length">
                <div x-data id="jobs-error-message" class="error-message">
                    <img 
                        :src="$store.jobs.error.status == 500 ? './assets/img/server_error.svg' : './assets/img/no_data.svg'" 
                        alt="" class="error-illustration"
                    >
                    <span class="error-description" x-text="$store.jobs.error.message"></span>
                </div>
            </template>
            <template x-data x-cloak x-if="$store.jobs.list?.length > 0">
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
                        <th style="min-width: 120px;">
                            Client
                        </th>
                        <th style="min-width: 150px;">
                            Reporter
                        </th>
                        <th style="min-width: 300px;">
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
                            Files
                        </th>
                        <th>
                            Tags
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
                        <template x-for="(job, index) in $store.jobs.list" :key="index">
                            <tr x-intersect="()=> {
                                if($store.jobs.list[$store.jobs.list.length - 1].id == job.id) {
                                    if($store.jobs.has_next_page) {
                                        $store.jobs.page++
                                        $store.jobs.getJobs()
                                    }
                                }
                            }">
                                <td>
                                    <div class="datetime">
                                        <span class="date" x-text="moment(job.created_at).format('YYYY-MM-DD')"></span>
                                        <span class="time" x-text="moment(job.created_at).format('h:mm A')"></span>  
                                    </div>
                                </td>
                                <td x-text="job.project"></td>
                                <td class="client text-center"
                                >
                                    <template x-if="$store.clients.getClient(job.client_id)?.logo?.trim()">
                                        <img :src="`./uploads/client_logos/${$store.clients.getClient(job.client_id).logo}`" alt="">
                                    </template>
                                    <span 
                                        x-show="!$store.clients.getClient(job.client_id)?.logo" 
                                        x-text="$store.clients.getClient(job.client_id)?.name || 'loading..'"
                                    >
                                    </span>
                                </td>
                                <td>
                                    <template x-if="job.reported_by?.trim() || job.reporter_contacts?.trim()">
                                        <div class="reporter">
                                            <span class="reporter-name" x-text="job.reported_by ?? 'N/A'"></span>
                                            <span class="reporter-phone" x-text="job.reporter_contacts ?? 'N/A'"></span>
                                        </div>
                                    </template>
                                    <span x-show="!job.reported_by?.trim() && !job.reporter_contacts?.trim()">N/A</span>
                                </td>
                                <td><pre x-text="job.description"></pre></td>
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
                                    <template x-if="job.files?.length">
                                        <div x-id="['attachments']">
                                            <button type="button" class="btn btn-info" data-toggle="modal"
                                                :data-target="'#'+$id('attachments', job.id)">
                                                <i class="now-ui-icons files_box"></i>
                                            </button>
        
                                            <!-- Modal -->
                                            <div class="modal fade" :id="$id('attachments', job.id)" tabindex="-1"
                                                role="dialog" aria-hidden="true">
                                                <div class="modal-dialog" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Attachments</h5>
                                                            <button type="button" class="close" data-dismiss="modal"
                                                                aria-label="Close">
                                                                <span aria-hidden="true">&times;</span>
                                                            </button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="container">
                                                                <div class="attachments">
                                                                    <template x-for="file in job.files">
                                                                        <div class="file-info">
                                                                            <div class="name">
                                                                                <i class="now-ui-icons" :class="file.type.includes('image') ? 'design_image' : 'files_paper'"></i>
                                                                                <span class="name" x-text="shortenFileName(file.name, 25)"></span>
                                                                            </div>
                                                                            <div class="download">
                                                                                <strong class="size" x-text="returnFileSize(file.size)"></strong>
                                                                                <a :href="`controllers/download.php?name=${encodeURIComponent(file.name)}&by=${file.uploadedBy}&size=${file.size}&type=${encodeURIComponent(file.type)}`" 
                                                                                    class="icon-button"
                                                                                >
                                                                                    <i class="now-ui-icons arrows-1_cloud-download-93"></i>
                                                                                </a>
                                                                            </div>
                                                                        </div>
                                                                    </template>
                                                                </div>
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
                                    <template x-if="job.tags?.length">
                                        <div x-id="['tag']">
                                            <button type="button" class="btn btn-info" data-toggle="modal"
                                                :data-target="'#'+$id('tag', job.id)">
                                                <i class="now-ui-icons shopping_tag-content" style="transform: rotate(-90deg);"></i>
                                            </button>
        
                                            <!-- Modal -->
                                            <div class="modal fade" :id="$id('tag', job.id)" tabindex="-1"
                                                role="dialog" aria-hidden="true">
                                                <div class="modal-dialog" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Job Tags</h5>
                                                            <button type="button" class="close" data-dismiss="modal"
                                                                aria-label="Close">
                                                                <span aria-hidden="true">&times;</span>
                                                            </button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="job-tags">
                                                                <template x-if="$store.tags.isLoaded">
                                                                    <template x-for="tagId in job.tags" :key="tagId">
                                                                        <div
                                                                            :style="{borderColor: $store.tags.getTag(tagId)?.colorcode ?? 'grey'}"
                                                                        >
                                                                            <span 
                                                                                x-text="$store.tags.getTag(tagId)?.label ?? 'Error loading tag'"
                                                                            >
                                                                            </span>
                                                                        </div>
                                                                    </template>
                                                                </template>
                                                                <span x-show="!$store.tags.isLoaded">
                                                                    <i class="now-ui-icons loader_refresh spin"></i>
                                                                </span>
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
                                                            <div class="container">
                                                                <pre x-text="job.completion_notes.trim()"></pre>
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
                                                            <div class="container">
                                                                <pre x-text="job.issues_arrising.trim()"></pre>
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
                                                                        <label :for="$id('actions', 'close_', 'completion_notes')">Completion
                                                                            notes</label>
                                                                        <textarea name="closing-completion-notes"
                                                                            class="form-control" :id="$id('actions', 'close_', 'completion_notes')"
                                                                            rows="40"
                                                                            x-model="fields.completion_notes.value"></textarea>
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label :for="$id('actions', 'close_', 'issues_arrising')">Issues
                                                                            arrising</label>
                                                                        <textarea name="closing-issues-arising"
                                                                            class="form-control" :id="$id('actions', 'close_', 'issues_arrising')"
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
        <div class="card-footer">
            <template x-if="!$store.jobs.isLoaded && $store.jobs.page !== 1">
                <i class="now-ui-icons loader_refresh spin"></i>
            </template>
        </div>
    </div>
  </div>
</div>