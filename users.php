<div 
        class="card card-tasks " 
        x-data="{editMode: false, addMode: false, userdata: {}}" 
        style="position: relative;"
        :style = "editMode && {border: '1px solid #E86252'}"
    >
        <div class="card-header ">
            <h5 class="card-category mb-1">Users</h5>
            <div style="display: flex; gap: 12px; align-items: center;">
                <h4 class="card-title">Registered users</h4>
                <template x-data x-if="!$store.users.isLoaded">
                    <i class="now-ui-icons loader_refresh spin"></i>
                </template>
            </div>
            <button x-show="editMode || session.role === 'ADMIN'"
                    @click ="() => {
                        if (editMode) {
                            $dispatch('clear-role')
                            editMode = false
                            return
                        }
                        if (addMode) {
                            $dispatch('clear-add')
                            addMode = false
                            return
                        }
                        addMode = true
                    }"
                    class="btn btn-round btn-outline-default" 
                    style="position: absolute; right: 12px; top: 16px; color: black;"
                >
                    <i  class="now-ui-icons" :class="editMode || addMode ? 'ui-1_simple-remove' : 'ui-1_simple-add'" 
                        :style = "editMode && {color: '#E86252'}"
                    ></i>
            </button>
        </div>
        <div class="card-body pt-0">
            <div x-show="!editMode" x-transition.opacity id="users-error-message" class="error-message">
                <img src="" alt="" class="error-illustration">
                <span class="error-description"></span>
            </div>
            <form 
                x-data="{role: ''}" 
                @submit.prevent="()=> {
                    editUserRole(userdata.id, role)
                    editMode = false
                    role = ''
                }"
                x-show ="editMode" 
                x-cloak
                @clear-role.window="role = ''"
                @edit-role.window = "()=> {
                    userdata = $event.detail
                    role = $event.detail.role
                }"

            >
                <div class="form-group">
                    <label for="edit-role">Role</label>
                    <select name="role" id="edit-role" class="custom-select" x-model="role">
                        <option selected value="" disabled >Select user's role</option>
                        <option value="USER">USER</option>
                        <option value="EDITOR">EDITOR</option>
                        <option value="ADMIN">ADMIN</option>
                    </select>
                </div>
                <button 
                        type="submit" 
                        style="width: 100%;" 
                        class="btn btn-outline-primary" 
                >
                    Submit
                </button>
            </form>
            <form x-data="addUserForm()" @clear-add.window="clearForm()" x-show="addMode" x-cloak x-transition.scale @submit.prevent="()=> {
                submit()
                clearForm()
                addMode = false
            }">
                <div class="form-group">
                    <label for="signup-username">Username</label>
                    <input 
                        class="form-control"
                        autocomplete="username"
                        required type="text" 
                        name="username" 
                        id="signup-username"
                        placeholder="Enter username"
                        aria-describedby="user-icon-addon"
                        x-model="fields.username.value"
                        @blur="validateField(fields.username)"
                    >
                    <span class="text-warning mt-1" x-text="fields.username.error" x-cloak></span>
                </div>
                <div class="form-group">
                    <label for="signup-email">Email</label>
                    <input 
                        class="form-control"
                        type="text" 
                        name="email" 
                        id="signup-email"
                        placeholder="Enter your email"
                        aria-describedby="user-icon-addon"
                        x-model="fields.email.value"
                        @blur="validateField(fields.email)"
                    >
                    <span class="text-warning mt-1" x-text="fields.email.error" x-cloak></span>
                </div>
                <div class="form-group">
                    <label for="signup-role">Role</label>
                    <select name="role" id="signup-role" class="custom-select" x-model="fields.role.value">
                        <option selected value="" disabled >Select user's role</option>
                        <option value="USER">USER</option>
                        <option value="EDITOR">EDITOR</option>
                        <option value="ADMIN">ADMIN</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="signup-password">Password</label>
                    <input 
                        class="form-control"
                        required type="password" 
                        name="password" 
                        id="signup-password"
                        placeholder="Enter password"
                        aria-describedby="password-icon-addon"
                        x-model="fields.password.value"
                        @blur="validateField(fields.password)"
                    >
                    <span class="text-warning mt-1" x-text="fields.password.error" x-cloak></span>
                </div>
                <div class="form-group">
                    <label for="signup-repeat-pwd">Repeat Password</label>
                    <input 
                        class="form-control"
                        required type="password" 
                        name="repeatpwd" 
                        id="signup-repeat-pwd"
                        placeholder="Repeat password"
                        aria-describedby="password-icon-addon"
                        x-model="fields.repeatPassword.value"
                        @blur="validateField(fields.repeatPassword)"
                    >
                    <span class="text-warning mt-1" x-text="fields.repeatPassword.error" x-cloak></span>
                </div>
                <button type="submit" :disabled="isFormInvalid" name="submit" style="width: 100%;" class="btn btn-outline-primary">
                    SUBMIT
                </button>
            </form>
            <template x-cloak x-if="$store.users.list?.length > 0">
                <div class="table-full-width table-responsive" x-show="!editMode && !addMode" x-transition style="max-height: 300px">
                    <table class="table persons-list" x-data>
                        <thead>
                            <tr>
                                <th style="min-width: 150px;">Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th style="min-width: 100px;">Location</th>
                                <th style="min-width: 250px;">Current Task</th>
                                <template x-if="session.role === 'ADMIN'">
                                    <th>Actions</th>
                                </template>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="user in $store.users.list" :key="user.id">
                                <tr>
                                    <td class="text-left" x-text="user.username"></td>
                                    <td class="text-left" x-text="user.email"></td>
                                    <td class="text-left" x-text="user.phone || 'N/A'"></td>
                                    <td class="text-left" x-text="user.current_location?.trim() || 'N/A'"></td>
                                    <td class="text-left" x-text="user.current_task?.trim() || 'N/A'"></td>
                                    <template x-if="session.role === 'ADMIN'">
                                        <td class="td-actions text-right" x-id="['user-actions']">
                                            <button type="button" rel="tooltip" title=""
                                                class="btn btn-info btn-round btn-icon btn-icon-mini btn-neutral"
                                                data-original-title="Edit Task"
                                                @click="()=>{
                                                    editMode = true
                                                    $dispatch('edit-role', user)
                                                }"
                                            >
                                                <i class="now-ui-icons ui-2_settings-90"></i>
                                            </button>
                                            <button type="button" rel="tooltip" title=""
                                                    class="btn btn-danger btn-round btn-icon btn-icon-mini btn-neutral"
                                                    data-original-title="Remove"
                                                    data-toggle="modal" :data-target="'#'+$id('user-actions', 'delete-' + user.id)"
                                            >
                                                    <i class="now-ui-icons ui-1_simple-remove"></i>
                                            </button>
                                            <div class="modal fade" :id="$id('user-actions', 'delete-' + user.id)" tabindex="-1" role="dialog" aria-hidden="true">
                                                <div class="modal-dialog" role="document">
                                                    <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Confirm</h5>
                                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                        </button>
                                                    </div>
                                                    <div class="modal-body text-left">
                                                        Are you sure you want to delete &quot;<span x-data x-text="user.username"></span>&quot;? Jobs associated with this user will be altered!
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-info" data-dismiss="modal">No</button>
                                                        <button type="button" class="btn btn-danger ml-2" data-dismiss="modal" @click="deleteUser(user.id)">Yes!</button>
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