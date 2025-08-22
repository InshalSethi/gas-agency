<!-- Profile Modal for changing password -->
  <div class="modal fade" id="profileModal" tabindex="-1" aria-labelledby="profileModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form id="changePasswordForm">
          <div class="modal-header">
            <h5 class="modal-title" id="profileModalLabel">Change Password</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <div class="form-group">
              <label for="current_password">Current Password</label>
              <input type="password" id="current_password" name="current_password" class="form-control" required>
            </div>
            <div class="form-group">
              <label for="new_password">New Password</label>
              <input type="password" id="new_password" name="new_password" class="form-control" required>
            </div>
            <div class="form-group">
              <label for="confirm_password">Confirm New Password</label>
              <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-primary">Change Password</button>
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
          </div>
        </form>
      </div>
    </div>
  </div>
<!-- JS -->
<script src="<?php echo baseurl('js/jquery-3.5.1.slim.min.js'); ?>"></script>
<script src="<?php echo baseurl('js/jquery-3.5.1.min.js'); ?>"></script>
<!-- <script src="<?php //echo baseurl('js/jquery.dataTables.min.js'); ?>"></script> -->
<script type="text/javascript" src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
<script src="<?php echo baseurl('js/bootstrap.min.js'); ?>"></script>
<script src="<?php echo baseurl('js/popper.min.js'); ?>"></script>
<script>
    $(document).ready(function() {
      $(".navbar-toggler").click(function() {
        $(".sidebar").toggle();
    });
        $('#changePasswordModal').on('click', function(event){
        event.preventDefault(); // Prevent the default action
        $('#profileModal').modal('toggle'); // Toggle the modal
      });

//       // Fetch user details and populate modal
//       $.get('<?php echo baseurl('fetch_user_details.php'); ?>', function(user) {
//     // Populate form with user details
//     $('#current_username').val(user.username); // Assuming you have an input with id 'current_username' for displaying username
// });


      // Change Password Form
      $('#changePasswordForm').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        $.post('<?php echo baseurl('change_password.php'); ?>', formData, function(response) {
          $('#profileModal').modal('hide');
          $('#changePasswordForm')[0].reset();
          alert(response.message); // Show a success message or handle the response as needed
          // Optionally, logout user after password change
          window.location.href = '../logout.php';
        }).fail(function(xhr, status, error) {
          var errorMessage = xhr.status + ': ' + xhr.statusText;
          alert('Error - ' + errorMessage);
        });
      });
    });
  </script>