function showSuccessMessage(message) {
  Swal.fire({
    icon: 'success',
    title: 'Успіх!',
    text: message,
    confirmButtonColor: '#ff66b2'
  });
}

function showErrorMessage(message) {
  Swal.fire({
    icon: 'error',
    title: 'Помилка!',
    text: message,
    confirmButtonColor: '#ff66b2'
  });
}
