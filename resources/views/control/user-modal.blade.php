<div class="modal fade" id="userModal">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5>Edit User</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="userId">

        <input class="form-control mb-2" id="name" placeholder="Name">
        <input class="form-control mb-2" id="email" placeholder="Email">
        <input class="form-control mb-2" id="phone" placeholder="Phone">

        <select class="form-select mb-2" id="role">
            <option value="user">User</option>
            <option value="admin">Admin</option>
        </select>

        <div class="form-check">
            <input class="form-check-input" type="checkbox" id="is_active">
            <label class="form-check-label">Active</label>
        </div>

        <div class="form-check">
            <input class="form-check-input" type="checkbox" id="can_login">
            <label class="form-check-label">Can Login</label>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" id="saveUser">Save</button>
      </div>
    </div>
  </div>
</div>
