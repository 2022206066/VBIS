<div class="row">
    <div class="col-12">
        <div class="card mb-4">
            <div class="card-header pb-0 d-flex justify-content-between align-items-center">
                <h6>Create New User</h6>
                <a href="<?= \app\core\Application::url('/accounts') ?>" class="btn btn-sm btn-outline-primary">Back to Accounts</a>
            </div>
            <div class="card-body">
                <form method="post" action="<?= \app\core\Application::url('/saveAccount') ?>">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="first_name" class="form-control-label">First Name</label>
                                <input class="form-control <?= isset($model['user']->errors['first_name']) ? 'is-invalid' : '' ?>" 
                                       type="text" name="first_name" value="<?= $model['user']->first_name ?? '' ?>" id="first_name" required>
                                <?php if(isset($model['user']->errors['first_name'])): ?>
                                    <div class="invalid-feedback"><?= $model['user']->errors['first_name'] ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="last_name" class="form-control-label">Last Name</label>
                                <input class="form-control <?= isset($model['user']->errors['last_name']) ? 'is-invalid' : '' ?>" 
                                       type="text" name="last_name" value="<?= $model['user']->last_name ?? '' ?>" id="last_name" required>
                                <?php if(isset($model['user']->errors['last_name'])): ?>
                                    <div class="invalid-feedback"><?= $model['user']->errors['last_name'] ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="email" class="form-control-label">Email</label>
                                <input class="form-control <?= isset($model['user']->errors['email']) ? 'is-invalid' : '' ?>" 
                                       type="email" name="email" value="<?= $model['user']->email ?? '' ?>" id="email" required>
                                <?php if(isset($model['user']->errors['email'])): ?>
                                    <div class="invalid-feedback"><?= $model['user']->errors['email'] ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="form-control-label">User Role</label>
                                <?php foreach($model['availableRoles'] as $role): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="role_id" 
                                               value="<?= $role['id'] ?>" id="role_<?= $role['id'] ?>"
                                               <?= ($role['id'] == 2) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="role_<?= $role['id'] ?>">
                                            <?= $role['name'] ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="password" class="form-control-label">Password</label>
                                <input class="form-control" type="password" name="password" id="password" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="confirm_password" class="form-control-label">Confirm Password</label>
                                <input class="form-control" type="password" name="confirm_password" id="confirm_password" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">Create User</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div> 