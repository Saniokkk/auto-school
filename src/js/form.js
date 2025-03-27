import Notiflix from 'notiflix';
import toggleModal from './multi-modal';

const modal = document.querySelector('[data-modal-order]');

const form = document.querySelector('#form');

form.addEventListener('submit', formSend);

async function formSend(e) {
  e.preventDefault();

  let formData = new FormData(form);

  formData.forEach(console.log);
  const error = formValidate(e.currentTarget);
  console.log('error: ', error);

  if (error) {
    Notiflix.Notify.failure(error);
    return;
  }

  try {
    let response = await fetch('send_mail.php', {
      method: 'POST',
      body: formData,
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
    const dataObj = {};

    formData.forEach((value, key, forData) => {
      dataObj[key] = value;
    });

    const { name, phone, driving, history } = dataObj;

    if (!name || !phone || (!history && !driving)) {
      return 'Будь ласка заповніть всі данні!';
    }
  }
}
