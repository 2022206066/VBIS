<div class="row">
    <div class="col-12">
        <div class="card mb-4">
            <div class="card-header pb-0">
                <h6>Your Account</h6>
            </div>
            <div class="card-body">
                <form method="post" action="<?= \app\core\Application::url('/updateAccount') ?>">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="first_name" class="form-control-label">First Name</label>
                                <input class="form-control <?= isset($model->errors['first_name']) ? 'is-invalid' : '' ?>" 
                                       type="text" name="first_name" value="<?= $model->first_name ?>" id="first_name">
                                <?php if(isset($model->errors['first_name'])): ?>
                                    <div class="invalid-feedback"><?= $model->errors['first_name'] ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="last_name" class="form-control-label">Last Name</label>
                                <input class="form-control <?= isset($model->errors['last_name']) ? 'is-invalid' : '' ?>" 
                                       type="text" name="last_name" value="<?= $model->last_name ?>" id="last_name">
                                <?php if(isset($model->errors['last_name'])): ?>
                                    <div class="invalid-feedback"><?= $model->errors['last_name'] ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="email" class="form-control-label">Email</label>
                                <input class="form-control <?= isset($model->errors['email']) ? 'is-invalid' : '' ?>" 
                                       type="email" name="email" value="<?= $model->email ?>" id="email">
                                <?php if(isset($model->errors['email'])): ?>
                                    <div class="invalid-feedback"><?= $model->errors['email'] ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    <h6>Change Password</h6>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="current_password" class="form-control-label">Current Password</label>
                                <input class="form-control <?= isset($model->errors['current_password']) ? 'is-invalid' : '' ?>" 
                                       type="password" name="current_password" id="current_password">
                                <?php if(isset($model->errors['current_password'])): ?>
                                    <div class="invalid-feedback"><?= $model->errors['current_password'] ?></div>
                                <?php endif; ?>
                                <small class="form-text text-muted">Leave blank if you don't want to change your password</small>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="new_password" class="form-control-label">New Password</label>
                                <input class="form-control <?= isset($model->errors['new_password']) ? 'is-invalid' : '' ?>" 
                                       type="password" name="new_password" id="new_password">
                                <?php if(isset($model->errors['new_password'])): ?>
                                    <div class="invalid-feedback"><?= $model->errors['new_password'] ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="confirm_password" class="form-control-label">Confirm New Password</label>
                                <input class="form-control <?= isset($model->errors['confirm_password']) ? 'is-invalid' : '' ?>" 
                                       type="password" name="confirm_password" id="confirm_password">
                                <?php if(isset($model->errors['confirm_password'])): ?>
                                    <div class="invalid-feedback"><?= $model->errors['confirm_password'] ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <button type="submit" class="btn btn-primary">Update Account</button>
                        </div>
                        <div class="col-md-6 text-end">
                            <a href="<?= \app\core\Application::url('/deleteAccount') ?>" 
                               onclick="return confirm('Are you sure you want to delete your account? This action cannot be undone and all your data will be permanently deleted.');" 
                               class="btn btn-danger">
                                Delete Account
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div> 