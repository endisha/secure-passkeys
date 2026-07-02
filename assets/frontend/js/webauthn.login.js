const publicKeyCredentialAllowed = window.PublicKeyCredential ? true : false;

document.addEventListener("DOMContentLoaded", function () {
  const box = document.getElementById("secure-passkey-login-wrapper");
  const submit = document.querySelector(".submit");
  if (box && submit) {
    submit.insertAdjacentElement("afterend", box);
  }
  if (box) {
    box.style.display = "block";
  }
});

document.addEventListener("DOMContentLoaded", function () {
  document
    .querySelectorAll(
      'input[name="log"], input[name="username"], input[name="user_login"], #username, #user_login',
    )
    .forEach((field) => {
      if (!field.getAttribute("autocomplete")?.includes("webauthn")) {
        field.setAttribute("autocomplete", "username webauthn");
      }
    });

  let conditionalAbortController = null;
  let conditionalRequest = null;
  let isAuthenticating = false;

  document
    .getElementById("login-via-passkey")
    .addEventListener("click", async (event) => {
      event.preventDefault();
      await loginWithPasskey();
    });

  async function loginWithPasskey() {
    if (isAuthenticating) {
      return;
    }
    isAuthenticating = true;

    if (conditionalAbortController) {
      conditionalAbortController.abort();
      conditionalAbortController = null;
    }

    if (conditionalRequest) {
      try {
        await conditionalRequest;
      } catch (e) {}
      conditionalRequest = null;
    }

    const errorMessage = document.getElementById("errorMessage");
    const successMessage = document.getElementById("successMessage");
    const passkeySigninButton = document.getElementById("login-via-passkey");
    const spinnerText = document.getElementById("spinnerText");
    const buttonText = document.getElementById("buttonText");

    errorMessage.style.display = "none";
    successMessage.style.display = "none";
    spinnerText.style.display = "inline-block";
    buttonText.style.display = "none";
    passkeySigninButton.disabled = true;

    if (!publicKeyCredentialAllowed) {
      errorMessage.textContent =
        secure_passkeys_object.i18n.passkeys_not_supported_in_browser;
      errorMessage.style.display = "block";
      spinnerText.style.display = "none";
      buttonText.style.display = "inline";
      passkeySigninButton.disabled = false;
      isAuthenticating = false;
      return;
    }

    try {
      const options = await getLoginOptions();
      let challenge = options.challenge;

      options.challenge = base64ToUint8Array(options.challenge);
      if (options.allowCredentials && Array.isArray(options.allowCredentials)) {
        options.allowCredentials = options.allowCredentials.map((cred) => ({
          ...cred,
          id: base64ToUint8Array(cred.id),
        }));
      }

      const credential = await navigator.credentials.get({
        publicKey: options,
      });
      const data = prepareLoginData(credential);
      data.challenge = challenge;
      await postLoginData(data);
    } catch (error) {
      if (error instanceof DOMException && error.name === "NotAllowedError") {
        errorMessage.textContent =
          secure_passkeys_object.i18n.cancelled_or_no_passkey;
      } else if (error instanceof DOMException && error.name === "AbortError") {
        errorMessage.textContent = secure_passkeys_object.i18n.cancelled_login;
      } else if (error instanceof Error && error.message) {
        errorMessage.textContent = error.message;
      } else {
        errorMessage.textContent = secure_passkeys_object.i18n.failed_login;
      }
      errorMessage.style.display = "block";
      spinnerText.style.display = "none";
      buttonText.style.display = "inline";
      passkeySigninButton.disabled = false;
      isAuthenticating = false;
    }
  }

  async function getLoginOptions() {
    let params = {
      nonce: secure_passkeys_object.nonce,
      action: "secure_passkeys_frontend_get_login_options",
    };

    try {
      const response = await fetch(secure_passkeys_object.url, {
        method: "POST",
        body: new URLSearchParams(params),
      });
      const data = await response.json();

      if (data.success) {
        return data.data;
      } else if (!data.success && data?.data) {
        throw new Error(data.data);
      } else {
        throw new Error(secure_passkeys_object.i18n.failed_load_options);
      }
    } catch (error) {
      if (error instanceof Error && error.message) {
        throw error;
      }
      throw new Error(secure_passkeys_object.i18n.failed_load_options);
    }
  }

  async function postLoginData(params) {
    params.nonce = secure_passkeys_object.nonce;
    params.action = "secure_passkeys_frontend_login";

    if (secure_passkeys_object.redirect_to) {
      params.redirect_to = secure_passkeys_object.redirect_to;
    }

    const formData = appendParamsToFormData(params);

    try {
      const response = await fetch(secure_passkeys_object.url, {
        method: "POST",
        body: formData,
      });
      const data = await response.json();

      if (data.success) {
        document.getElementById("successMessage").textContent =
          secure_passkeys_object.i18n.success_login;
        document.getElementById("successMessage").style.display = "block";

        if (data.data.redirect_url) {
          window.location.href = data.data.redirect_url;
        } else {
          window.location.reload();
        }
      } else {
        throw new Error(data.data);
      }
    } catch (error) {
      if (error instanceof Error && error.message) {
        throw error;
      }
      throw new Error(secure_passkeys_object.i18n.failed_load_options);
    }
  }

  function prepareLoginData(credential) {
    return {
      id: credential.id,
      rawId: decodeBase64EncodedASCII(credential.rawId),
      type: credential.type,
      response: {
        clientDataJSON: decodeBase64EncodedASCII(
          credential.response.clientDataJSON,
        ),
        authenticatorData: decodeBase64EncodedASCII(
          credential.response.authenticatorData,
        ),
        signature: decodeBase64EncodedASCII(credential.response.signature),
        userHandle: credential.response.userHandle
          ? decodeBase64EncodedASCII(credential.response.userHandle)
          : null,
      },
    };
  }

  function base64ToUint8Array(value) {
    return Uint8Array.from(atob(value), (c) => c.charCodeAt(0));
  }

  function decodeBase64EncodedASCII(data) {
    return btoa(String.fromCharCode(...new Uint8Array(data)));
  }

  function appendParamsToFormData(params) {
    const formData = new FormData();

    for (let key in params) {
      if (params.hasOwnProperty(key)) {
        let paramValue = params[key];

        if (typeof paramValue === "object" && paramValue !== null) {
          if (Array.isArray(paramValue)) {
            paramValue.forEach((item, index) => {
              formData.append(`${key}[${index}]`, item);
            });
          } else {
            for (let subKey in paramValue) {
              if (paramValue.hasOwnProperty(subKey)) {
                const subValue = paramValue[subKey];
                formData.append(`${key}[${subKey}]`, subValue);
              }
            }
          }
        } else {
          formData.append(key, paramValue);
        }
      }
    }

    return formData;
  }

  async function initConditionalUI() {
    if (
      !publicKeyCredentialAllowed ||
      !window.PublicKeyCredential.isConditionalMediationAvailable
    ) {
      return;
    }

    const isCMA =
      await window.PublicKeyCredential.isConditionalMediationAvailable();
    if (!isCMA) {
      return;
    }

    try {
      const options = await getLoginOptions();
      let challenge = options.challenge;

      options.challenge = base64ToUint8Array(options.challenge);
      if (options.allowCredentials && Array.isArray(options.allowCredentials)) {
        options.allowCredentials = options.allowCredentials.map((cred) => ({
          ...cred,
          id: base64ToUint8Array(cred.id),
        }));
      }

      conditionalAbortController = new AbortController();

      conditionalRequest = navigator.credentials.get({
        publicKey: options,
        mediation: "conditional",
        signal: conditionalAbortController.signal,
      });

      const credential = await conditionalRequest;

      if (credential) {
        const data = prepareLoginData(credential);
        data.challenge = challenge;
        await postLoginData(data);
      }
    } catch (error) {
      // Ignore errors for conditional UI as they are expected when user logs in manually or aborts
    }
  }

  initConditionalUI();
});
