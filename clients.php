<div 
        class="card card-tasks" 
        x-data="{editMode: false, showForm: false, clientdata: {}, ...clientFormData() }" 
        style="position: relative;"
        :style = "editMode && {border: '1px solid #E86252'}"
    >
        <div class="card-header ">
            <h5 class="card-category mb-1">Clients</h5>
            <div style="display: flex; gap: 12px; align-items: center;">
                <h4 class="card-title">Registered clients</h4>
                <template x-data x-if="!$store.clients.isLoaded">
                    <i class="now-ui-icons loader_refresh spin"></i>
                </template>
            </div>
            <template x-if="session.role !== 'USER'">
                <button
                        @click ="() => {
                            showForm = !showForm
                            console.log(window.user)
                            if (editMode) {
                                clearForm()
                                editMode = false
                                showForm = false
                            }
                        }"
                        class="btn btn-round btn-outline-default" 
                        style="position: absolute; right: 12px; top: 16px; color: black;"
                    >
                        <i  class="now-ui-icons" 
                            :class="showForm || editMode ? 'ui-1_simple-remove' : 'ui-1_simple-add'"
                            :style = "editMode && {color: '#E86252'}"
                        ></i>
                </button>
            </template>
        </div>
        <div class="card-body pt-0">
            <div x-data x-show="!editMode && !showForm && !($store.clients.list.length > 0)" x-cloak x-transition.opacity id="clients-error-message" class="error-message">
                <img src="" alt="" class="error-illustration">
                <span class="error-description"></span>
            </div>
            <form 
                action="" 
                class="form-grup" 
                @submit.prevent="editMode ? ()=>{
                    submitEdit(clientdata.id)
                    editMode = false;
                } : ()=>{
                    submit($event)
                    showForm = false
                }" 
            >
                <div class="form-content" x-show="showForm || editMode" x-cloak x-transition.scale>
                    <div class="form-group" >
                        <label for="new-client-name">Client name</label>
                        <input
                            type="text" 
                            name="clientName"
                            id="new-client-name" required class="form-control" 
                            placeholder="Enter client name" 
                            x-model="fields.name.value"
                            @blur="validateField(fields.name)"
                            :class="fields.name.error ? 'border-danger' : ''"
                        />    
                        <span class="text-danger" x-text="fields.name.error" x-cloak></span>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input
                            type="text" 
                            name="email"
                            id="email" class="form-control" 
                            placeholder="Enter client email" 
                            x-model="fields.email.value"
                            @blur="validateField(fields.email)"
                            :class="fields.email.error ? 'border-danger' : ''"
                        />    
                        <span class="text-danger" x-text="fields.email.error" x-cloak></span>
                    </div>
                    <div class="form-group">
                        <label for="location">Location</label>
                        <input
                            type="text" 
                            name="location"
                            id="client-location" required class="form-control" 
                            placeholder="Enter client location" 
                            x-model="fields.location.value"
                            @blur="validateField(fields.location)"
                            :class="fields.location.error ? 'border-danger' : ''"
                        />    
                        <span class="text-danger" x-text="fields.location.error" x-cloak></span>
                    </div>
                    <div class="form-group" >
                        <label for="contact-person">Contact person</label>
                        <input
                            type="text" 
                            name="clientName"
                            id="contact-person" class="form-control" 
                            placeholder="Enter name" 
                            x-model="fields.contactPerson.value"
                            @blur="validateField(fields.contactPerson)"
                            :class="fields.contactPerson.error ? 'border-danger' : ''"
                        />    
                        <span class="text-danger" x-text="fields.contactPerson.error" x-cloak></span>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input
                            type="text" 
                            name="phone"
                            id="phone" class="form-control" 
                            placeholder="Enter client's phone" 
                            x-model="fields.phone.value"
                            @blur="validateField(fields.phone)"
                            :class="fields.phone.error ? 'border-danger' : ''"
                        />    
                        <span class="text-danger" x-text="fields.phone.error" x-cloak></span>
                    </div>
                    <button 
                        type="submit" 
                        style="width: 100%;" 
                        class="btn btn-outline-primary" 
                        :disabled="isFormInvalid"
                    >
                        Submit
                    </button>
                </div>
                
            </form>
            <template x-cloak x-if="$store.clients.list?.length > 0">
                <div class="table-full-width table-responsive" x-show="!editMode && !showForm" x-transition  style="max-height: 300px">
                    <table class="table persons-list" x-data>
                        <thead>
                            <tr>
                                <th style="min-width: 150px;">Name</th>
                                <th>Email</th>
                                <th style="min-width: 150px;">Location</th>
                                <th>Contact</th>
                                <th>Phone</th>
                                <template x-if="session.role !== 'USER'">
                                    <th>Actions</th>
                                </template>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="client in $store.clients.list">
                                <tr>
                                    <td class="text-left" x-text="client.name"></td>
                                    <td class="text-left" x-text="client.email?.trim() || 'N/A'"></td>
                                    <td class="text-left" x-text="client.location"></td>
                                    <td class="text-left" x-text="client.contact_person?.trim() || 'N/A'"></td>
                                    <td class="text-left" x-text="client.phone?.trim() || 'N/A'"></td>
                                    <template x-if="session.role !== 'USER'">
                                        <td class="td-actions text-right" x-id="['client-actions']">
                                            <button type="button" rel="tooltip" title=""
                                                class="btn btn-info btn-round btn-icon btn-icon-mini btn-neutral"
                                                data-original-title="Edit Task"
                                                @click="()=>{
                                                    editMode = true
                                                    editClient(client)
                                                    clientdata = client
                                                }"
                                            >
                                                <i class="now-ui-icons ui-2_settings-90"></i>
                                            </button>
                                            <button type="button" rel="tooltip" title=""
                                                class="btn btn-danger btn-round btn-icon btn-icon-mini btn-neutral"
                                                data-original-title="Remove"
                                                data-toggle="modal" :data-target="'#'+$id('client-actions', 'delete-client-' + client.id)"
                                            >
                                                <i class="now-ui-icons ui-1_simple-remove"></i>
                                            </button>
                                            <!-- Modal -->
                                            <div class="modal fade" :id="$id('client-actions', 'delete-client-' + client.id)" tabindex="-1" role="dialog" aria-hidden="true">
                                                <div class="modal-dialog" role="document">
                                                    <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Confirm</h5>
                                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                        </button>
                                                    </div>
                                                    <div class="modal-body text-left">
                                                        Are you sure you want to delete &quot;<span x-data x-text="client.name"></span>&quot;? Jobs associated with this client will also be deleted!
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-info" data-dismiss="modal">No</button>
                                                        <button type="button" class="btn btn-danger ml-2" data-dismiss="modal" @click="deleteClient(client.id)">Yes!</button>
                                                    </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </template>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </template>
        </div>
        <div class="card-footer ">
            
        </div>
    </div>