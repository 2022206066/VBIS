<div class="card card-plain">
    <div class="card-header pb-0 text-start">
        <h4 class="font-weight-bolder">Sign Up</h4>
        <p class="mb-0">Enter your details to create an account</p>
    </div>
    <div class="card-body">
        <form role="form" action="<?= \app\core\Application::url('/processRegistration') ?>" method="post">
            <div class="mb-3">
                <input type="text" name="first_name" value="<?= $model->first_name ?? '' ?>" class="form-control form-control-lg" placeholder="First Name" aria-label="First Name" required>
                <?php if (isset($model->errors['first_name'])): ?>
                    <small class="text-danger"><?= $model->errors['first_name'] ?></small>
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <input type="text" name="last_name" value="<?= $model->last_name ?? '' ?>" class="form-control form-control-lg" placeholder="Last Name" aria-label="Last Name" required>
                <?php if (isset($model->errors['last_name'])): ?>
                    <small class="text-danger"><?= $model->errors['last_name'] ?></small>
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <input type="email" name="email" value="<?= $model->email ?? '' ?>" class="form-control form-control-lg" placeholder="Email" aria-label="Email" required>
                <?php if (isset($model->errors['email'])): ?>
                    <small class="text-danger"><?= $model->errors['email'] ?></small>
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <input type="password" name="password" class="form-control form-control-lg" placeholder="Password" aria-label="Password" required>
                <?php if (isset($model->errors['password'])): ?>
                    <small class="text-danger"><?= $model->errors['password'] ?></small>
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <input type="password" name="confirmPassword" class="form-control form-control-lg" placeholder="Confirm Password" aria-label="Confirm Password" required>
                <?php if (isset($model->errors['confirmPassword'])): ?>
                    <small class="text-danger"><?= $model->errors['confirmPassword'] ?></small>
                <?php endif; ?>
            </div>
            <div class="text-center">
                <button type="submit" class="btn btn-lg btn-primary btn-lg w-100 mt-4 mb-0">Sign up</button>
            </div>
        </form>
    </div>
    <div class="card-footer text-center pt-0 px-lg-2 px-1">
        <p class="mb-4 text-sm mx-auto">
            Already have an account?
            <a href="<?= \app\core\Application::url('/login') ?>" class="text-primary font-weight-bold">Sign in</a>
        </p>
    </div>
</div> 