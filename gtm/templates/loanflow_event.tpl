___INFO___
{
  "type": "TAG",
  "id": "loanflow_event",
  "version": 1,
  "securityGroups": [],
  "displayName": "Loanflow Event",
  "brand": {
    "displayName": "Loanflow",
    "id": "loanflow"
  },
  "description": "Sends a consent-aware Loanflow event to the server-side GTM container. No personal data is included.",
  "containerContexts": ["WEB"]
}

___TEMPLATE_PARAMETERS___
[
  {
    "type": "TEXT",
    "name": "endpoint",
    "displayName": "Server container URL",
    "simpleValue": "https://gtm.example.com/collect"
  },
  {
    "type": "TEXT",
    "name": "eventName",
    "displayName": "Event name",
    "simpleValue": "loan_application_submitted"
  },
  {
    "type": "TEXT",
    "name": "applicationReference",
    "displayName": "Application reference",
    "simpleValue": "{{DLV - application_reference}}"
  }
]

___SANDBOXED_JS_FOR_WEB_TEMPLATE___
const log = require('logToConsole');
const sendHttpRequest = require('sendHttpRequest');
const JSON = require('JSON');
const getTimestampMillis = require('getTimestampMillis');
const queryPermission = require('queryPermission');

const endpoint = data.endpoint;
const payload = {
  event: data.eventName,
  application_reference: data.applicationReference,
  occurred_at: getTimestampMillis(),
  source: 'gtm-web'
};

if (queryPermission('send_http_request', endpoint)) {
  sendHttpRequest(endpoint, (statusCode, headers, body) => {
    log('Loanflow event sent', statusCode, body);
  }, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  });
} else {
  log('Loanflow: no send_http_request permission for', endpoint);
}

return data;

___WEB_PERMISSIONS___
[
  {
    "instance": {
      "key": "send_http_request",
      "value": "https://gtm.example.com/collect"
    },
    "capabilities": ["send_http_request"]
  }
]
