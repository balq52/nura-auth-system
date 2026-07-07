$(document).ready(function () {

  function showAlert(message, type) {
    $('#alertBox')
      .removeClass('d-none alert-success alert-danger')
      .addClass('alert-' + type)
      .text(message);
  }

  $('#registerForm').on('submit', function (e) {
    e.preventDefault(); // stop normal form submission - we use AJAX instead

    const payload = {
      username:  $('#username').val().trim(),
      email:     $('#email').val().trim(),
      password:  $('#password').val(),
      name:      $('#name').val().trim(),
      age:       $('#age').val(),
      bio:       $('#bio').val().trim(),
      interests: $('#interests').val().trim()
    };

    $('#registerBtn').prop('disabled', true).text('Registering...');

    $.ajax({
      url: 'php/register.php',
      type: 'POST',
      contentType: 'application/json',
      data: JSON.stringify(payload),
      dataType: 'json',
      success: function (response) {
        if (response.success) {
          showAlert(response.message, 'success');
          setTimeout(function () {
            window.location.href = 'login.html';
          }, 1200);
        } else {
          showAlert(response.message, 'danger');
        }
      },
      error: function (xhr) {
        const msg = xhr.responseJSON && xhr.responseJSON.message
          ? xhr.responseJSON.message
          : 'Something went wrong. Please try again.';
        showAlert(msg, 'danger');
      },
      complete: function () {
        $('#registerBtn').prop('disabled', false).text('Register');
      }
    });
  });

});
