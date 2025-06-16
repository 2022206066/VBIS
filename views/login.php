<div class="card card-plain">
    <div class="card-header pb-0 text-start">
        <h4 class="font-weight-bolder">Sign In</h4>
        <p class="mb-0">Enter your email and password to sign in</p>
    </div>
    <div class="card-body">
        <form role="form" action="<?= \app\core\Application::url('/processLogin') ?>" method="post">
            <div class="mb-3">
                <input type="email" name="email" class="form-control form-control-lg" placeholder="Email" aria-label="Email">
            </div>
            <div class="mb-3">
                <input type="password" name="password" class="form-control form-control-lg" placeholder="Password" aria-label="Password">
            </div>
            <div class="text-center">
                <button type="submit" class="btn btn-lg btn-primary btn-lg w-100 mt-4 mb-0">Sign in</button>
            </div>
        </form>
    </div>
    <div class="card-footer text-center pt-0 px-lg-2 px-1">
        <p class="mb-4 text-sm mx-auto">
            Don't have an account?
            <a href="<?= \app\core\Application::url('/registration') ?>" class="text-primary font-weight-bold">Sign up</a>
        </p>
    </div>
</div> 