<div class="row">
    <div class="col-12">
        <div class="card mb-4">
            <div class="card-header pb-0 d-flex justify-content-between align-items-center">
                <h6>User Accounts</h6>
                <a href="<?= \app\core\Application::url('/createAccount') ?>" class="btn btn-sm btn-primary">
                    <i class="mdi mdi-plus me-2"></i> Create New User
                </a>
            </div>
            <div class="card-body px-0 pt-0 pb-2">
                <div class="table-responsive p-0">
                    <table class="table align-items-center mb-0">
                        <thead>
                            <tr>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Name</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Email</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Roles</th>
                                <th class="text-secondary opacity-7"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($model['users'] as $user): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex px-2 py-1">
                                            <div class="d-flex flex-column justify-content-center">
                                                <h6 class="mb-0 text-sm"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h6>
                                                <p class="text-xs text-secondary mb-0">ID: <?= $user['id'] ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <p class="text-xs font-weight-bold mb-0"><?= htmlspecialchars($user['email']) ?></p>
                                    </td>
                                    <td class="align-middle text-center text-sm">
                                        <?php 
                                        if (isset($user['roles']) && is_array($user['roles'])): 
                                            foreach ($user['roles'] as $role): ?>
                                                <span class="badge badge-sm bg-gradient-success"><?= $role ?></span> 
                                            <?php endforeach;
                                        else: ?>
                                            <span class="text-secondary">No roles</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="align-middle">
                                        <a href="<?= \app\core\Application::url('/editAccount?id=' . $user['id']) ?>" class="text-secondary font-weight-bold text-xs" data-toggle="tooltip" data-original-title="Edit user">
                                            <i class="mdi mdi-pencil me-2"></i> Edit
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div> 