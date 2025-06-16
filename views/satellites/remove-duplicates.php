<div class="row">
    <div class="col-12">
        <div class="card mb-4">
            <div class="card-header pb-0 d-flex justify-content-between align-items-center">
                <h6>Remove Duplicate Satellites</h6>
                <a href="<?= \app\core\Application::url('/satellites') ?>" class="btn btn-sm btn-outline-primary">Back to Satellites</a>
            </div>
            <div class="card-body">
                <div class="alert alert-info mb-4">
                    <strong>Information:</strong> This tool will find satellites with duplicate names and keep only the newest version of each (highest ID).
                    <br>It will permanently delete all other instances with the same name.
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card bg-gradient-primary text-white">
                            <div class="card-body p-3">
                                <div class="row">
                                    <div class="col-8">
                                        <div class="numbers">
                                            <p class="text-sm mb-0 text-uppercase font-weight-bold">Total Satellites</p>
                                            <h5 class="font-weight-bolder text-white mb-0">
                                                <?= $model['results']['satellitesChecked'] ?>
                                            </h5>
                                        </div>
                                    </div>
                                    <div class="col-4 text-end">
                                        <div class="icon icon-shape bg-white shadow text-center border-radius-md">
                                            <i class="mdi mdi-satellite-variant text-primary opacity-10" aria-hidden="true"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card bg-gradient-danger text-white">
                            <div class="card-body p-3">
                                <div class="row">
                                    <div class="col-8">
                                        <div class="numbers">
                                            <p class="text-sm mb-0 text-uppercase font-weight-bold">Duplicates Found</p>
                                            <h5 class="font-weight-bolder text-white mb-0">
                                                <?= $model['results']['duplicatesFound'] ?> (<?= $model['results']['uniqueNames'] ?> unique names)
                                            </h5>
                                        </div>
                                    </div>
                                    <div class="col-4 text-end">
                                        <div class="icon icon-shape bg-white shadow text-center border-radius-md">
                                            <i class="mdi mdi-vector-curve text-danger opacity-10" aria-hidden="true"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if (count($model['duplicates']) > 0): ?>
                <div class="card">
                    <div class="card-header p-3">
                        <h6 class="mb-0">Duplicate Satellites Found</h6>
                    </div>
                    <div class="card-body p-3">
                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Satellite Name</th>
                                        <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Duplicate Count</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($model['duplicates'] as $duplicate): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex px-2 py-1">
                                                <div class="d-flex flex-column justify-content-center">
                                                    <h6 class="mb-0 text-sm"><?= htmlspecialchars($duplicate['name']) ?></h6>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="align-middle text-center">
                                            <span class="badge badge-sm bg-gradient-danger"><?= $duplicate['count'] ?> instances</span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4 text-center">
                    <form method="post" onsubmit="return confirm('Are you sure you want to remove all duplicate satellites? This action cannot be undone.');">
                        <input type="hidden" name="action" value="remove">
                        <button type="submit" class="btn btn-danger" style="color: white; font-weight: 600; letter-spacing: -0.025rem; box-shadow: 0 4px 6px rgba(50, 50, 93, 0.11), 0 1px 3px rgba(0, 0, 0, 0.08); transition: all 0.15s ease; padding: 0.5rem 2rem;">
                            <i class="mdi mdi-close-thick me-2"></i> Remove All Duplicates
                        </button>
                        <p class="text-muted mt-2 small">This will keep the newest version of each satellite (highest ID) and delete all other duplicates.</p>
                    </form>
                </div>
                <?php else: ?>
                <div class="alert alert-success">
                    <strong>Good news!</strong> No duplicate satellites were found in the database.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div> 