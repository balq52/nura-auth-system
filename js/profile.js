$(document).ready(function () {

  function showAlert(message, type) {
    $('#alertBox')
      .removeClass('d-none alert-success alert-danger')
      .addClass('alert-' + type)
      .text(message);
  }

  // ---- Load profile on page load ----
  function loadProfile() {
    if (!localStorage.getItem('auth_token')) {
      window.location.href = 'login.html';
      return;
    }
    $.ajax({
      url: 'php/profile.php',
      type: 'GET',
      dataType: 'json',
      xhrFields: { withCredentials: true },
      success: function (response) {
        $('#loadingBox').addClass('d-none');

        if (!response.success) {
          localStorage.removeItem('auth_token');
          window.location.href = 'login.html';
          return;
        }

        const data = response.data;
        $('#viewUsername').text(data.username);
        $('#viewEmail').text(data.email);
        $('#viewCreatedAt').text(data.created_at || '—');

        $('#name').val(data.name || '');
        $('#age').val(data.age || '');
        $('#bio').val(data.bio || '');
        $('#interests').val((data.interests || []).join(', '));

        $('#profileContent').removeClass('d-none');
      },
      error: function () {
        // No valid session -> back to login
        localStorage.removeItem('auth_token');
        window.location.href = 'login.html';
      }
    });
  }

  loadProfile();

  // ---- Save profile changes ----
  $('#profileForm').on('submit', function (e) {
    e.preventDefault();

    const payload = {
      name:      $('#name').val().trim(),
      age:       $('#age').val(),
      bio:       $('#bio').val().trim(),
      interests: $('#interests').val().trim()
    };

    $.ajax({
      url: 'php/profile.php',
      type: 'POST',
      contentType: 'application/json',
      data: JSON.stringify(payload),
      dataType: 'json',
      xhrFields: { withCredentials: true },
      success: function (response) {
        showAlert(response.message, response.success ? 'success' : 'danger');
      },
      error: function (xhr) {
        const msg = xhr.responseJSON && xhr.responseJSON.message
          ? xhr.responseJSON.message
          : 'Could not save changes.';
        showAlert(msg, 'danger');
      }
    });
  });

  // ---- Logout ----
  $('#logoutBtn').on('click', function () {
    $.ajax({
      url: 'php/logout.php',
      type: 'POST',
      xhrFields: { withCredentials: true },
      complete: function () {
        localStorage.removeItem('auth_token');
        window.location.href = 'login.html';
      }
    });
  });
