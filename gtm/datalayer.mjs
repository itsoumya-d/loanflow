export const LOANFLOW_EVENTS = Object.freeze({
  VIEWED: 'loan_application_viewed',
  STARTED: 'loan_application_started',
  SUBMITTED: 'loan_application_submitted',
  VALIDATION_FAILED: 'loan_application_validation_failed',
  APPROVED: 'loan_application_approved',
  DECLINED: 'loan_application_declined',
});

const ALLOWED_EVENTS = new Set(Object.values(LOANFLOW_EVENTS));

export function createDataLayerEvent(eventName, parameters = {}, consent = {}) {
  if (!ALLOWED_EVENTS.has(eventName)) {
    throw new Error(`Unknown Loanflow event: ${eventName}`);
  }

  if ((consent.analytics_storage || 'denied') !== 'granted') {
    return null;
  }

  const clean = {};

  for (const [key, value] of Object.entries(parameters)) {
    if (!/^[a-z0-9_]{1,40}$/.test(key)) {
      throw new Error(`Invalid dataLayer key: ${key}`);
    }

    if (!['string', 'number', 'boolean'].includes(typeof value)) {
      throw new Error(`Invalid dataLayer value for ${key}`);
    }

    clean[key] = typeof value === 'string' ? value.slice(0, 500) : value;
  }

  return {
    event: eventName,
    loanflow: {
      schema_version: 1,
      ...clean,
    },
  };
}

export function pushDataLayerEvent(dataLayer, eventName, parameters = {}, consent = {}) {
  const event = createDataLayerEvent(eventName, parameters, consent);

  if (event) {
    dataLayer.push(event);
  }

  return event;
}
