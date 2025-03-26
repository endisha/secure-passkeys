document.addEventListener("DOMContentLoaded", function () {
  const passkeyApp = Vue.createApp({
    template: secure_passkeys_object.content,
    data() {
      return {
        is_public_key_credential_allowed: window.PublicKeyCredential
          ? true
          : false,
        i18n: secure_passkeys_object.i18n,
        isRTL: secure_passkeys_object.is_rtl,
        credentials_allowed_count:
          secure_passkeys_object.credentials_allowed_count,
        isLoading: false,
        challenge: "",
        error: "",
        success: "",
        isPasskeysLoading: false,
        addingPasskey: false,
        passkeys: [],
        showSecurityKeyName: false,
        securityKeyNameInput: "",
        inputError: null,
        invaldInput: true,
        savedOptions: {},
        creatingPasskey: false,
        waitingAddPasskey: false,
        deletingPasskey: false,
        deletingPasskeyId: "",
      };
    },
    mounted() {
      this.loadPasskeys();
    },
    methods: {
      async loadPasskeys() {
        this.isPasskeysLoading = true;

        const params = {
          nonce: secure_passkeys_object.nonce,
          action: "secure_passkeys_frontend_get_registered_passkeys_list",
        };

        try {
          const response = await fetch(secure_passkeys_object.url, {
            method: "POST",
            body: new URLSearchParams(params),
          });
          const data = await response.json();

          if (data && data.data) {
            this.passkeys = data.data;
          } else {
            this.error = this.i18n.failed_load_passkeys;
          }
        } catch (error) {
          this.error = error.message || this.i18n.failed_load_passkeys;
        } finally {
          this.isPasskeysLoading = false;
        }
      },
      async addPasskey() {
        this.error = null;
        this.success = null;

        if (!this.is_public_key_credential_allowed) {
          this.error = this.i18n.passkeys_not_supported_in_browser;
          return;
        }

        if (
          this.credentials_allowed_count !== null &&
          this.credentials_allowed_count <= this.passkeys?.length
        ) {
          this.error = this.i18n.reach_maximum_credentials;
          return;
        }

        this.addingPasskey = true;

        try {
          const options = await this.getPasskeyOptions();

          const credential = await navigator.credentials.create({
            publicKey: options,
          });

          const data = this.preparePasskeyData(credential);

          await this.postPasskeyData(data);

          this.savedOptions = data;
          this.addingPasskey = false;
        } catch (error) {
          if (
            error instanceof DOMException &&
            error.name === "NotAllowedError"
          ) {
            this.error = this.i18n.failed_cancel_register;
          } else if (
            error instanceof DOMException &&
            error.name === "InvalidStateError"
          ) {
            this.error = this.i18n.passkey_already_registered;
          } else if (
            error instanceof DOMException &&
            error.name === "AbortError"
          ) {
            this.error = this.i18n.cancelled_register;
          } else if (error.response && error.response.data) {
            this.error = error.response.data.message;
          } else {
            this.error = this.i18n.failed_register;
          }
          this.addingPasskey = false;
        }
      },

      async createPasskey() {
        this.error = null;
        if (this.savedOptions === null) {
          this.showSecurityKeyName = false;
          return;
        }
        if (this.invaldInput) {
          return;
        }
        if (
          this.securityKeyNameInput?.trim() === "" ||
          this.securityKeyNameInput?.trim()?.length < 3 ||
          this.securityKeyNameInput?.trim()?.length > 30
        ) {
          this.inputError = this.i18n.failed_save_passkey_name_length;
          this.invaldInput = true;
          return;
        }

        this.inputError = "";
        this.invaldInput = false;
        this.creatingPasskey = true;
        try {
          let data = this.savedOptions;
          if (this.securityKeyNameInput) {
            data.security_key_name = this.securityKeyNameInput;
          }

          await this.postPasskeyData(data);

          this.addingPasskey = false;
          this.securityKeyNameInput = "";
        } catch (error) {
          if (error.response && error.response.data) {
            this.error = error.response.data.message;
            if (error.response.data.errors) {
              this.error = Object.values(error.response.data.errors).join(", ");
            }
          } else {
            this.error = this.i18n.failed_register;
          }

          this.creatingPasskey = false;
        }
      },
      cancelPasskey() {
        this.error = null;
        this.success = null;
        this.addingPasskey = false;
        this.waitingAddPasskey = false;
        this.securityKeyNameInput = "";
        this.showSecurityKeyName = false;
      },
      async deletePasskey(passkey) {
        this.error = null;
        this.success = null;
        this.deletingPasskeyId = passkey.id;

        if (
          !confirm(
            this.i18n.confirm_delete_passkey +
              " (" +
              passkey.security_key_name +
              ")"
          )
        ) {
          return;
        }

        this.deletingPasskey = true;

        const params = {
          id: passkey.id,
          nonce: secure_passkeys_object.nonce,
          action: "secure_passkeys_frontend_remove_passkey",
        };

        try {
          const response = await fetch(secure_passkeys_object.url, {
            method: "POST",
            body: new URLSearchParams(params),
          });

          const data = await response.json();

          if (data?.success) {
            this.success = this.i18n.success_delete_passkey;
            this.loadPasskeys();
          } else {
            this.error = data?.message || this.i18n.failed_delete_passkey;
          }
        } catch (error) {
          this.error = error.message || this.i18n.failed_delete_passkey;
        } finally {
          this.deletingPasskeyId = "";
          this.deletingPasskey = false;
        }
      },
      async getPasskeyOptions() {
        const params = {
          action: "secure_passkeys_frontend_get_register_options",
          nonce: secure_passkeys_object.nonce,
        };

        try {
          const response = await fetch(secure_passkeys_object.url, {
            method: "POST",
            body: new URLSearchParams(params),
          });

          const data = await response.json();

          if (!data || !data.success) {
            throw new Error(this.i18n.failed_load_options);
          }

          const options = data.data;
          this.challenge = options.challenge;

          options.challenge = this.base64ToUint8Array(options.challenge);
          options.user.id = this.base64ToUint8Array(options.user.id);

          if (options.excludeCredentials) {
            options.excludeCredentials = options.excludeCredentials.map(
              (cred) => ({
                ...cred,
                id: this.base64ToUint8Array(cred.id),
              })
            );
          }

          return options;
        } catch (error) {
          throw new Error(this.i18n.failed_load_options);
        }
      },
      async postPasskeyData(params) {
        params.challenge = this.challenge;
        params.nonce = secure_passkeys_object.nonce;
        params.action = "secure_passkeys_frontend_register_passkey";
        this.waitingAddPasskey = true;

        try {
          const response = await fetch(secure_passkeys_object.url, {
            method: "POST",
            body: this.appendParamsToFormData(params),
          });

          const data = await response.json();

          this.creatingPasskey = false;

          if (!data.success && data?.data === "EMPTY_SECURITY_KEY_NAME") {
            this.showSecurityKeyName = true;
          } else if (data.success) {
            this.showSecurityKeyName = false;
            this.waitingAddPasskey = false;
            this.savedOptions = null;
            this.success = this.i18n.success_save_passkey;
            this.loadPasskeys();
          } else {
            throw new Error(data?.data ?? this.i18n.failed_save_passkey);
          }
        } catch (error) {
          const errorMessage = error.message || this.i18n.failed_save_passkey;
          this.addingPasskey = false;
          this.creatingPasskey = false;
          this.error = errorMessage;
          throw new Error(errorMessage);
        }
      },
      preparePasskeyData(credential) {
        return {
          id: credential.id,
          rawId: this.arrayBufferToBase64(credential.rawId),
          type: credential.type,
          response: {
            clientDataJSON: this.arrayBufferToBase64(
              credential.response.clientDataJSON
            ),
            attestationObject: this.arrayBufferToBase64(
              credential.response.attestationObject
            ),
          },
        };
      },
      base64ToUint8Array(base64) {
        return Uint8Array.from(atob(base64), (c) => c.charCodeAt(0));
      },
      arrayBufferToBase64(buffer) {
        return btoa(String.fromCharCode(...new Uint8Array(buffer)));
      },
      validateInput() {
        const regex = /^[A-Za-z0-9\s\-_]*$/;
        if (!regex.test(this.securityKeyNameInput)) {
          this.invaldInput = true;
          this.inputError = this.i18n.failed_save_passkey_name;
        } else {
          this.inputError = "";
          this.invaldInput = false;
        }

        if (this.securityKeyNameInput === "") {
          this.invaldInput = true;
        }
      },
      appendParamsToFormData(params) {
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
      },
    },
  });

  passkeyApp.mount("#passkey_app");
});
