import Notiflix from 'notiflix';
import toggleModal from './multi-modal';

const modal = document.querySelector('[data-modal-order]');

const form = document.querySelector('#form');

form.addEventListener('submit', formSend);

async function formSend(e) {
  e.preventDefault();

  const formData = {
    name: form.name.value.trim(),
    phone: form.phone.value.trim(),
    driving: form.driving.checked,
    history: form.history.checked,
  };

  const error = formValidate(formData);

  if (error) {
    Notiflix.Notify.failure(error);
    return;
  }

  console.log(formData);

  try {
    let response = await fetch('send_mail.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json', // Вказуємо, що відправляємо JSON
      },
      body: JSON.stringify(formData), // Конвертуємо об'єкт в JSON
    });

    if (response.ok) {
      let result = await response.json();
      toggleModal(e, modal);
      Notiflix.Notify.success(result.message);
      form.reset();
    }
  } catch (error) {
    console.log(error);
    Notiflix.Notify.failure(error.message);
  }

  function formValidate(form) {
    const { name, phone, driving, history } = form;

    if (!name || !phone || (!history && !driving)) {
      return 'Будь ласка заповніть всі данні!';
    }
  }
}
