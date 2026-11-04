(function () {
  'use strict';

  var config = window.loanflowIntake || {};
  var form = document.querySelector('[data-loanflow-form]');

  if (!form) {
    return;
  }

  window.dataLayer = window.dataLayer || [];

  var statusEl = form.querySelector('[data-loanflow-status]');
  var submitEl = form.querySelector('.loanflow-form__submit');
  var started = false;
  var messages = config.messages || {};

  function push(eventName, params) {
    if (!config.gtm || !config.gtm.enabled) {
      return;
    }

    var payload = Object.assign(
      { event: eventName, loanflow: Object.assign({ plugin_version: '1.0.0' }, params || {}) },
      {}
    );

    window.dataLayer.push(payload);
  }

  function setStatus(message, isError) {
    if (!statusEl) {
      return;
    }

    statusEl.textContent = message;
    statusEl.className = 'loanflow-form__status ' + (isError ? 'is-error' : 'is-success');
  }

  function clearErrors() {
    form.querySelectorAll('.loanflow-field.has-error').forEach(function (field) {
      field.classList.remove('has-error');
    });
  }

  function showErrors(errors) {
    clearErrors();
    var first = null;

    Object.keys(errors || {}).forEach(function (key) {
      var input = form.querySelector('[name="' + key + '"]');
      var wrapper = input ? input.closest('.loanflow-field') : null;

      if (wrapper) {
        wrapper.classList.add('has-error');
        first = first || input;
      }
    });

    if (first) {
      first.focus();
    }
  }

  function toPayload() {
    var data = new FormData(form);
    var amount = parseFloat(String(data.get('loan_amount') || '0'));
    var income = parseFloat(String(data.get('annual_income') || '0'));

    return {
      applicant_name: String(data.get('applicant_name') || ''),
      email: String(data.get('email') || ''),
      phone: String(data.get('phone') || ''),
      loan_amount_cents: Math.round(amount * 100),
      loan_purpose: String(data.get('loan_purpose') || ''),
      employment_status: String(data.get('employment_status') || ''),
      annual_income_cents: Math.round(income * 100),
      credit_check_consent: data.get('credit_check_consent') === '1',
      privacy_consent: data.get('privacy_consent') === '1',
      notes: String(data.get('notes') || ''),
      website: String(data.get('website') || '')
    };
  }

  form.addEventListener('input', function () {
    if (!started) {
      started = true;
      push(config.gtm && config.gtm.events ? config.gtm.events.form_started : 'loan_application_started', {
        step: 'form'
      });
    }
  });

  form.addEventListener('submit', function (event) {
    event.preventDefault();

    if (!form.checkValidity()) {
      push(config.gtm && config.gtm.events ? config.gtm.events.validation_failed : 'loan_application_validation_failed', {
        step: 'client'
      });
      setStatus(messages.validation || 'Please check the highlighted fields.', true);
      form.reportValidity();

      return;
    }

    var payload = toPayload();

    if (payload.website !== '') {
      setStatus(messages.error || 'Something went wrong. Please try again.', true);

      return;
    }

    submitEl.disabled = true;
    setStatus(messages.submitting || 'Submitting your application…', false);

    fetch(config.restUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        'X-Loanflow-Nonce': config.nonce || ''
      },
      body: JSON.stringify(payload)
    })
      .then(function (response) {
        return response.json().then(function (body) {
          return { ok: response.ok, status: response.status, body: body };
        });
      })
      .then(function (result) {
        if (result.ok) {
          push(config.gtm && config.gtm.events ? config.gtm.events.form_submitted : 'loan_application_submitted', {
            step: 'complete',
            application_reference: result.body.reference || ''
          });
          setStatus((messages.success || 'Thank you. Your application reference is') + ' ' + (result.body.reference || ''), false);
          form.reset();
          started = false;

          return;
        }

        if (result.status === 422) {
          push(config.gtm && config.gtm.events ? config.gtm.events.validation_failed : 'loan_application_validation_failed', {
            step: 'server'
          });
          showErrors(result.body.errors || {});
          setStatus(messages.validation || 'Please check the highlighted fields.', true);

          return;
        }

        setStatus(result.body.message || messages.error || 'Something went wrong. Please try again.', true);
      })
      .catch(function () {
        setStatus(messages.error || 'Something went wrong. Please try again.', true);
      })
      .finally(function () {
        submitEl.disabled = false;
      });
  });

  push(config.gtm && config.gtm.events ? config.gtm.events.form_viewed : 'loan_application_viewed', {
    step: 'form'
  });
})();
