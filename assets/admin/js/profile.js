const { createApp } = Vue;

const app = createApp({
  data() {
    return {
      is_public_key_credential_allowed: window.PublicKeyCredential,
      list: [],
      isLoading: true,
      error: null,
      i18n: secure_passkeys_params.i18n,
      has_access: secure_passkeys_params.has_access,
      is_owner: secure_passkeys_params.is_owner,
      errorMessage: "",
      successMessage: "",
      missingNonce: false,
      deletingId: 0,
      isRTL: secure_passkeys_params.isRTL,
      credentials_allowed_count:
        secure_passkeys_params.credentials_allowed_count,
      challenge: "",
      error: "",
      success: "",
      addingPasskey: false,
      passkeys: [],
      showSecurityKeyName: false,
      securityKeyNameInput: "",
      inputError: null,
      invaldInput: true,
      savedOptions: {},
      waitingAddPasskey: false,
      creatingPasskey: false,
      deletingPasskey: false,
      deletingPasskeyId: "",
      actionProcessing: false,
      actionProcessingId: 0,
    };
  },
  methods: {
    loadPasskeys() {
      this.isLoading = true;
      fetch(secure_passkeys_params.url, {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
        },
        body: new URLSearchParams({
          action:
            "secure_passkeys_adminarea_get_profile_registered_passkeys_list",
          user_id: secure_passkeys_params.user_id,
          nonce: secure_passkeys_params.nonce,
        }),
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            this.list = data.data;
          } else {
            this.errorMessage =
              data.data.message ||
              secure_passkeys_params.i18n.failed_fetch_passkeys;
          }
        })
        .catch((error) => {
          this.errorMessage = secure_passkeys_params.i18n.failed_fetch_passkeys;
        })
        .finally(() => {
          this.isLoading = false;
        });
    },
    deletePasskey(id) {
      var message =
        secure_passkeys_params?.i18n?.delete_message ??
        "Are you sure you want to delete the passkey?";
      if (!confirm(message)) {
        return;
      }
      var that = this;
      this.deletingPasskey = true;

      this.deletingId = id;
      this.errorMessage = "";
      this.successMessage = "";
      jQuery.post(
        secure_passkeys_params.url,
        {
          nonce: secure_passkeys_params.nonce,
          user_id: secure_passkeys_params.user_id,
          id: id,
          action: "secure_passkeys_adminarea_delete_passkey",
        },
        function (data) {
          that.deletingPasskey = false;
          that.deletingId = 0;
          if (data.success) {
            that.successMessage = data.data.message;
            that.loadPasskeys();
          } else {
            if (data.data.missing_nonce) {
              that.missingNonce = true;
            }
            if (data.data.message) {
              that.errorMessage = data.data.message;
            } else {
              that.errorMessage =
                secure_passkeys_params.i18n.failed_delete_passkey;
            }
          }
        },
        "JSON"
      );
    },
    activateDeactivatePasskey(id, is_active) {
      let message = "";

      if (is_active) {
        message =
          secure_passkeys_params?.i18n?.deactivate_message ??
          "Are you sure you want to deactivate the passkey?";
      } else {
        message =
          secure_passkeys_params?.i18n?.activate_message ??
          "Are you sure you want to activate the passkey?";
      }

      if (!confirm(message)) {
        return;
      }
      var that = this;
      this.actionProcessing = true;
      this.actionProcessingId = id;
      this.errorMessage = "";
      this.successMessage = "";
      jQuery.post(
        secure_passkeys_params.url,
        {
          nonce: secure_passkeys_params.nonce,
          action: "secure_passkeys_adminarea_activate_deactivate_passkey",
          id: id,
          procedure: is_active ? "deactivate" : "activate",
        },
        function (data) {
          that.actionProcessing = false;
          that.actionProcessingId = 0;
          if (data.success) {
            that.successMessage = data.data.message;
            that.loadPasskeys();
          } else {
            if (data.data.missing_nonce) {
              that.missingNonce = true;
            }
            if (data.data.message) {
              that.errorMessage = data.data.message;
            } else {
              that.errorMessage = "Something went wrong!";
            }
          }
        },
        "JSON"
      );
    },
    async addPasskey() {
      this.errorMessage = null;
      this.successMessage = null;

      if (!this.is_public_key_credential_allowed) {
        this.errorMessage = this.i18n.passkeys_not_supported_in_browser;
        return;
      }

      if (
        this.credentials_allowed_count !== null &&
        this.credentials_allowed_count <= this.list?.length
      ) {
        this.errorMessage = this.i18n.reach_maximum_credentials;
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
        if (error instanceof DOMException && error.name === "NotAllowedError") {
          this.errorMessage = this.i18n.failed_cancel_register;
        } else if (
          error instanceof DOMException &&
          error.name === "InvalidStateError"
        ) {
          this.errorMessage = this.i18n.passkey_already_registered;
        } else if (
          error instanceof DOMException &&
          error.name === "AbortError"
        ) {
          this.errorMessage = this.i18n.cancelled_register;
        } else if (error.response && error.response.data) {
          this.errorMessage = error.response.data.message;
        } else {
          this.errorMessage = this.i18n.failed_register;
        }

        this.addingPasskey = false;
      }
    },

    async createPasskey() {
      this.errorMessage = null;
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
          this.errorMessage = error.response.data.message;
          if (error.response.data.errors) {
            this.errorMessage = Object.values(error.response.data.errors).join(
              ", "
            );
          }
        } else {
          this.errorMessage = this.i18n.failed_register;
        }

        this.creatingPasskey = false;
      }
    },
    cancelPasskey() {
      this.errorMessage = null;
      this.successMessage = null;
      this.addingPasskey = false;
      this.waitingAddPasskey = false;
      this.securityKeyNameInput = "";
      this.showSecurityKeyName = false;
    },
    getPasskeyOptions() {
      const params = {
        action: "secure_passkeys_frontend_get_register_options",
        nonce: secure_passkeys_params.nonce,
      };
      return new Promise((resolve, reject) => {
        jQuery
          .post(
            secure_passkeys_params.url,
            params,
            (data) => {
              if (!data || !data.success) {
                reject(new Error(this.i18n.failed_load_options));
                return;
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

              resolve(options);
            },
            "json"
          )
          .fail(() => {
            reject(new Error(this.i18n.failed_load_options));
          });
      });
    },

    async postPasskeyData(params) {
      params.challenge = this.challenge;
      params.nonce = secure_passkeys_params.nonce;
      params.action = "secure_passkeys_frontend_register_passkey";
      this.creatingPasskey = true;
      this.waitingAddPasskey = true;

      jQuery
        .post(
          secure_passkeys_params.url,
          params,
          (data) => {
            if (!data.success && data?.data === "EMPTY_SECURITY_KEY_NAME") {
              this.creatingPasskey = false;
              this.showSecurityKeyName = true;
            } else if (data.success) {
              this.showSecurityKeyName = false;
              this.addingPasskey = false;
              this.creatingPasskey = false;
              this.waitingAddPasskey = false;
              this.savedOptions = null;
              this.successMessage = this.i18n.success_save_passkey;
              this.loadPasskeys();
            } else {
              this.creatingPasskey = false;
              this.errorMessage = data?.data ?? this.i18n.failed_save_passkey;
            }
          },
          "json"
        )
        .fail((jqXHR) => {
          const errorMessage =
            jqXHR.responseJSON?.error || this.i18n.failed_save_passkey;
          this.addingPasskey = false;
          this.creatingPasskey = false;
          this.errorMessage = errorMessage;
          reject(new Error(errorMessage));
        });
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
  },
  mounted() {
    this.loadPasskeys();
  },
  template: secure_passkeys_params.content,
});

app.mount("#passkey-app");
