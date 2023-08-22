<div class="row" style="margin-top: -5rem;">
    <div class="col-md-6">
        <div class="card  card-tasks">
            <div class="card-header ">
                <h5 class="card-category">Edit Profile</h5>
                <h4 class="card-title active">Personal info</h4>
            </div>
            <div class="card-body ">
                <form x-data="editUserForm()" action="./controllers/update_user.php" method="POST">
                    <div class="form-group">
                        <label for="user-name">Username</label>
                        <input type="text" name="username" id="user-name" required 
                            class="form-control"
                            placeholder="Enter your name" 
                            x-model="fields.username.value"
                            @blur="validateField(fields.username)"
                            :class="fields.username.error ? 'border-danger' : ''" />
                        <span class="text-danger" x-text="fields.username.error" x-cloak></span>
                    </div>
                    <div class="form-group">
                        <label for="user-email">Email</label>
                        <input type="text" name="email" id="user-email" 
                            class="form-control"
                            placeholder="Enter your email" 
                            x-model="fields.email.value"
                            @blur="validateField(fields.email)"
                            :class="fields.email.error ? 'border-danger' : ''" />
                        <span class="text-danger" x-text="fields.email.error" x-cloak></span>
                    </div>
                    <div class="form-group">
                        <label for="user-phone">Phone</label>
                        <input type="text" name="phone" id="user-phone" 
                            class="form-control"
                            placeholder="Enter your number" 
                            x-model="fields.phone.value"
                            @blur="validateField(fields.phone)"
                            :class="fields.phone.error ? 'border-danger' : ''" />
                        <span class="text-danger" x-text="fields.phone.error" x-cloak></span>
                    </div>
                    <div class="form-group">
                        <label for="currentLocation">Current location</label>
                        <input type="text" name="currentLocation" id="currentLocation" 
                            class="form-control"
                            placeholder="Enter current location" 
                            x-model="fields.currentLocation.value"
                            @blur="validateField(fields.currentLocation)"
                            :class="fields.currentLocation.error ? 'border-danger' : ''" />
                        <span class="text-danger" x-text="fields.currentLocation.error" x-cloak></span>
                    </div>
                    <div class="form-group">
                        <label for="currentTask">Current task</label>
                        <textarea name="currentTask" class="form-control" id="currentTask" rows="1"
                            x-model="fields.currentTask.value" 
                            @blur="validateField(fields.currentTask)"
                            :class="fields.currentTask.error ? 'border-danger' : ''">
                        </textarea>
                        <span class="text-danger" x-text="fields.currentTask.error" x-cloak></span>
                    </div>
                    <button type="submit" name="submit" style="width: 100%;" class="btn btn-outline-primary"
                        :disabled="isFormInvalid">
                        Submit
                    </button>
                </form>
            </div>
            <div class="card-footer ">
            </div>
        </div>
    </div>
</div>