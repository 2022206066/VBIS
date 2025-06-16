                <?php if (Application::$app->session->isInRole('Administrator')): ?>
                    <li class="menu-title">
                        <span>Administration</span>
                    </li>
                    <li>
                        <a href="<?= Application::url('/accounts'); ?>">
                            <i class="mdi mdi-account-multiple"></i>
                            <span>User Accounts</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= Application::url('/reports'); ?>">
                            <i class="mdi mdi-chart-bar"></i>
                            <span>Reports</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= Application::url('/reassign-satellites'); ?>">
                            <i class="mdi mdi-satellite"></i>
                            <span>Reassign Satellites</span>
                        </a>
                    </li>
                <?php endif; ?> 