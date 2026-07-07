$(document).ready(function () {

  function showAlert(message, type) {
    $('#alertBox')
      .removeClass('d-none alert-success alert-danger')
      .addClass('alert-' + type)
      .text(message);
  }

  $('#loginForm').on('submit', function (e) {
    e.preventDefault();

    const payload = {
      identifier: $('#identifier').val().trim(),
      password:   $('#password').val()
    };

    $('#loginBtn').prop('disabled', true).text('Logging in...');

    $.ajax({
      url: 'php/login.php',
      type: 'POST',
      contentType: 'application/json',
      data: JSON.stringify(payload),
      dataType: 'json',
      xhrFields: { withCredentials: true }, // ensure the session cookie is sent/received
      success: function (response) {
        if (response.success) {
          showAlert(response.message, 'success');
          setTimeout(function () {
            window.location.href = 'profile.html';
          }, 600);
        } else {
          showAlert(response.message, 'danger');
        }
      },
      error: function (xhr) {
        const msg = xhr.responseJSON && xhr.responseJSON.message
          ? xhr.responseJSON.message
          : 'Invalid credentials.';
        showAlert(msg, 'danger');
      },
      complete: function () {
        $('#loginBtn').prop('disabled', false).text('Login');
      }
    });
  });

});
