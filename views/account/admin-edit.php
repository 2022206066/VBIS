<div class="row">
    <div class="col-12">
        <div class="card mb-4">
            <div class="card-header pb-0 d-flex justify-content-between align-items-center">
                <h6>Edit User Account</h6>
                <a href="<?= \app\core\Application::url('/accounts') ?>" class="btn btn-sm btn-outline-primary">Back to Accounts</a>
            </div>
            <div class="card-body">
                <form method="post" action="<?= \app\core\Application::url('/updateUserAccount') ?>">
                    <input type="hidden" name="id" value="<?= $model['user']->id ?>">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="first_name" class="form-control-label">First Name</label>
                                <input class="form-control <?= isset($model['user']->errors['first_name']) ? 'is-invalid' : '' ?>" 
                                       type="text" name="first_name" value="<?= $model['user']->first_name ?>" id="first_name">
                                <?php if(isset($model['user']->errors['first_name'])): ?>
                                    <div class="invalid-feedback"><?= $model['user']->errors['first_name'] ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="last_name" class="form-control-label">Last Name</label>
                                <input class="form-control <?= isset($model['user']->errors['last_name']) ? 'is-invalid' : '' ?>" 
                                       type="text" name="last_name" value="<?= $model['user']->last_name ?>" id="last_name">
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
                                       type="email" name="email" value="<?= $model['user']->email ?>" id="email">
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
                                               <?= $model['user']->role_id == $role['id'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="role_<?= $role['id'] ?>">
                                            <?= $role['name'] ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    <h6>Reset Password</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="new_password" class="form-control-label">New Password</label>
                                <input class="form-control <?= isset($model['user']->errors['new_password']) ? 'is-invalid' : '' ?>" 
                                       type="password" name="new_password" id="new_password">
                                <?php if(isset($model['user']->errors['new_password'])): ?>
                                    <div class="invalid-feedback"><?= $model['user']->errors['new_password'] ?></div>
                                <?php endif; ?>
                                <small class="form-text text-muted">Leave blank if you don't want to reset the password</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="confirm_password" class="form-control-label">Confirm New Password</label>
                                <input class="form-control <?= isset($model['user']->errors['confirm_password']) ? 'is-invalid' : '' ?>" 
                                       type="password" name="confirm_password" id="confirm_password">
                                <?php if(isset($model['user']->errors['confirm_password'])): ?>
                                    <div class="invalid-feedback"><?= $model['user']->errors['confirm_password'] ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <button type="submit" class="btn btn-primary">Update User</button>
                        </div>
                    </div>
                </form>
                
                <div class="row mt-4">
                    <div class="col-12">
                        <hr>
                        <h6>Delete Account</h6>
                        <p>This action cannot be undone.</p>
                        <?php
                        // Check for satellites directly in PHP
                        $db = new \app\core\Database();
                        $conn = $db->getConnection();
                        $hasSatellites = false;
                        $satelliteCount = 0;
                        
                        // Only check if we have a valid user ID
                        if (isset($model['user']->id)) {
                            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM satellites WHERE added_by = ?");
                            $stmt->bind_param("i", $model['user']->id);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            if ($row = $result->fetch_assoc()) {
                                $satelliteCount = $row['count'];
                                $hasSatellites = ($satelliteCount > 0);
                            }
                        }
                        
                        if ($hasSatellites) {
                            // User has satellites - show warning button
                            echo '<button type="button" class="btn btn-danger px-4 py-2" style="cursor: pointer; color: white; font-weight: 600; letter-spacing: -0.025rem; box-shadow: 0 4px 6px rgba(50, 50, 93, 0.11), 0 1px 3px rgba(0, 0, 0, 0.08); transition: all 0.15s ease;" onclick="showSatelliteWarning(' . $satelliteCount . ')">Delete User Account</button>';
                        } else {
                            // No satellites - show normal delete button
                            echo '<a href="' . \app\core\Application::url('/deleteUserAccount?id=' . $model['user']->id) . '" 
                                    onclick="return confirm(\'Are you sure you want to delete this user account? This action cannot be undone.\');" 
                                    class="btn btn-danger px-4 py-2" style="color: white; font-weight: 600; letter-spacing: -0.025rem; box-shadow: 0 4px 6px rgba(50, 50, 93, 0.11), 0 1px 3px rgba(0, 0, 0, 0.08); transition: all 0.15s ease;">Delete User Account</a>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Styles for buttons and modal -->
<style>
    .btn-danger {
        background-color: #f5365c;
        border-color: #f5365c;
    }
    
    .btn-danger:hover {
        background-color: #f3547d;
        border-color: #f3547d;
        transform: translateY(-1px);
        box-shadow: 0 7px 14px rgba(50, 50, 93, 0.1), 0 3px 6px rgba(0, 0, 0, 0.08) !important;
    }
    
    .btn-primary {
        background-color: #5e72e4;
        border-color: #5e72e4;
    }
    
    .btn-primary:hover {
        background-color: #7889e8;
        border-color: #7889e8;
        transform: translateY(-1px);
        box-shadow: 0 7px 14px rgba(50, 50, 93, 0.1), 0 3px 6px rgba(0, 0, 0, 0.08) !important;
    }
    
    /* Modal specific styles */
    #satellitesFoundModal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 1050;
        overflow: auto;
        display: none;
    }
    
    #satellitesFoundModal .modal-dialog {
        position: relative;
        width: 100%;
        max-width: 500px;
        margin: 100px auto;
    }
    
    #satellitesFoundModal .modal-content {
        background-color: #fff;
        border-radius: 0.4375rem;
        box-shadow: 0 5px 25px rgba(0, 0, 0, 0.2);
    }
    
    #satellitesFoundModal .modal-header {
        background-color: #f5365c;
        color: white;
        padding: 15px;
        border-top-left-radius: 0.4375rem;
        border-top-right-radius: 0.4375rem;
    }
    
    #satellitesFoundModal .btn-close {
        background-color: transparent;
        border: none;
        color: white;
        font-size: 1.5rem;
        opacity: 0.7;
        filter: brightness(0) invert(1);
    }
    
    #satellitesFoundModal .btn-close:hover {
        opacity: 1;
    }
    
    #satellitesFoundModal .modal-title {
        font-weight: 600;
    }
    
    #satellitesFoundModal .modal-body {
        padding: 20px;
    }
    
    #satellitesFoundModal .modal-footer {
        padding: 15px;
        border-top: 1px solid #e9ecef;
        display: flex;
        justify-content: space-between;
    }
    
    #satellitesFoundModal .alert-danger {
        background-color: rgba(245, 54, 92, 0.1);
        color: #f5365c;
        border-color: rgba(245, 54, 92, 0.2);
        padding: 15px;
        margin-bottom: 15px;
        border-radius: 0.375rem;
    }
</style>

<!-- Modal for satellites warning - Completely hidden by default -->
<div class="modal" id="satellitesFoundModal" tabindex="-1" role="dialog" aria-hidden="true" style="display: none;">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">⚠️ Cannot Delete User</h5>
        <button type="button" class="btn-close" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-danger">
          <strong>This user has <span id="satelliteCount">0</span> satellites associated with their account.</strong>
        </div>
        <p>Before deleting this user, you must reassign these satellites to another user.</p>
        <p>Please use the "Reassign Satellites" tool at the bottom of the Satellites page to transfer ownership.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary">Cancel</button>
        <a href="<?= \app\core\Application::url('/satellites') ?>" class="btn btn-primary">Go to Satellites Page</a>
      </div>
    </div>
  </div>
</div>

<!-- Script for satellite warning -->
<script>
function showSatelliteWarning(count) {
    // Update the count in the modal
    var countElement = document.getElementById('satelliteCount');
    if (countElement) {
        countElement.textContent = count;
    }
    
    // Get the modal element
    var modalElement = document.getElementById('satellitesFoundModal');
    
    // Position the dialog in the center of the viewport
    var dialogElement = modalElement.querySelector('.modal-dialog');
    if (dialogElement) {
        // Reset any previous positioning
        dialogElement.style.margin = '1.75rem auto';
    }
    
    // Show the modal
    modalElement.style.display = 'block';
    document.body.classList.add('modal-open');
    
    // Add backdrop that covers the entire page
    var backdrop = document.createElement('div');
    backdrop.className = 'modal-backdrop';
    document.body.appendChild(backdrop);
    
    // Prevent body from scrolling while modal is open
    document.body.style.overflow = 'hidden';
    
    // Add event listener to close button
    var closeButton = modalElement.querySelector('.btn-close');
    if (closeButton) {
        closeButton.onclick = function() {
            closeModal();
        };
    }
    
    // Add event listener to secondary button
    var secondaryButton = modalElement.querySelector('.btn-secondary');
    if (secondaryButton) {
        secondaryButton.onclick = function() {
            closeModal();
        };
    }
}

function closeModal() {
    var modal = document.getElementById('satellitesFoundModal');
    modal.style.display = 'none';
    document.body.classList.remove('modal-open');
    
    // Remove backdrop
    var backdrop = document.querySelector('.modal-backdrop');
    if (backdrop) {
        backdrop.parentNode.removeChild(backdrop);
    }
    
    // Restore body scrolling
    document.body.style.overflow = '';
}

document.addEventListener('DOMContentLoaded', function() {
    // Ensure modal is hidden on page load
    var modalElement = document.getElementById('satellitesFoundModal');
    if (modalElement) {
        modalElement.style.display = 'none';
    }
});
</script> 