var generalSetings = Vue.component("general-settings", {
  template: "#general-settings",
  data() {
    return {
      title: "General settings",
      settings: {
        registration_maximum_passkeys_enabled: 1,
        registration_maximum_passkeys_per_user: 3,
        excluded_roles_registration_login: [],
        registration_timeout: 5,
        registration_exclude_credentials_enabled: 1,
        registration_user_verification_enabled: 1,
        login_timeout: 5,
        login_user_verification: "required",
      },
      defaults: {
        roles: {},
      },
      isLoading: false,
      errorMessage: "",
      successMessage: "",
      submitted: false,
      submitting: false,
    };
  },
  mounted() {
    this.getGlobalSetings();
  },
  methods: {
    getGlobalSetings() {
      var that = this;
      this.isLoading = true;
      jQuery.post(
        secure_passkeys_params.url,
        {
          nonce: secure_passkeys_params.nonce,
          action: "secure_passkeys_get_general_settings",
        },
        function (data) {
          that.isLoading = false;

          if (data.data.defaults ?? null) {
            that.defaults = data.data.defaults;
          }

          if (data.success) {
            if (Object.keys(data.data.data).length > 0) {
              that.settings = data.data.data;
            }
          } else {
            if (data.data.missing_nonce) {
              if (data.data.message) {
                that.errorMessage = data.data.message;
              } else {
                that.errorMessage = "Something went wrong!";
              }
            }
          }
        },
        "JSON"
      );
    },
    updateSettings() {
      var that = this;
      this.errorMessage = "";
      this.successMessage = "";
      this.submitting = true;

      jQuery.post(
        secure_passkeys_params.url,
        {
          nonce: secure_passkeys_params.nonce,
          action: "secure_passkeys_update_general_settings",
          option: "general_settings",
          settings: that.settings,
        },
        function (data) {
          that.submitting = false;

          if (data.data.defaults ?? null) {
            that.defaults = data.data.defaults;
          }

          if (data.success == true) {
            that.successMessage = data.data.message;
            that.settings = data.data.data;
            that.submitted = true;
          } else {
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
  },
});

var displaySettings = Vue.component("display-settings", {
  template: "#display-settings",
  data() {
    return {
      title: "Display Settings",
      settings: {
        display_passkey_theme: "default",
        display_passkey_login_wp_enabled: 1,
        display_passkey_login_woocommerce_enabled: 1,
        display_passkey_login_memberpress_enabled: 1,
        display_passkey_login_edd_enabled: 1,
        display_passkey_users_list_enabled: 1,
        display_passkey_edit_user_enabled: 1,
      },
      defaults: {
        themes: null,
      },
      isLoading: false,
      errorMessage: "",
      successMessage: "",
      submitted: false,
      submitting: false,
    };
  },
  mounted() {
    this.getDisplaySettings();
  },
  methods: {
    getDisplaySettings() {
      var that = this;
      this.isLoading = true;
      jQuery.post(
        secure_passkeys_params.url,
        {
          nonce: secure_passkeys_params.nonce,
          action: "secure_passkeys_get_display_settings",
        },
        function (data) {
          that.isLoading = false;

          if (data.data.defaults ?? false) {
            that.defaults = data.data.defaults;
          }

          if (data.success) {
            if (Object.keys(data.data.data).length > 0) {
              that.settings = data.data.data;
            }
          } else {
            if (data.data.missing_nonce) {
              if (data.data.message) {
                that.errorMessage = data.data.message;
              } else {
                that.errorMessage = "Something went wrong!";
              }
            }
          }
        },
        "JSON"
      );
    },
    updateSettings() {
      var that = this;
      this.errorMessage = "";
      this.successMessage = "";
      this.submitting = true;

      jQuery.post(
        secure_passkeys_params.url,
        {
          nonce: secure_passkeys_params.nonce,
          action: "secure_passkeys_update_display_settings",
          option: "display_settings",
          settings: that.settings,
        },
        function (data) {
          that.submitting = false;

          if (data.data.defaults ?? false) {
            that.defaults = data.data.defaults;
          }

          if (data.success == true) {
            that.successMessage = data.data.message;
            that.settings = data.data.data;
            that.submitted = true;
          } else {
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
  },
});

var advancedSettings = Vue.component("advanced-settings", {
  template: "#advanced-settings",
  data() {
    return {
      title: "Advanced Settings",
      settings: {
        challenge_cleanup_days: 0,
        log_cleanup_days: 0,
      },
      defaults: {},
      isLoading: false,
      errorMessage: "",
      successMessage: "",
      submitted: false,
      submitting: false,
    };
  },
  mounted() {
    this.getAdvancedSettings();
  },
  methods: {
    getAdvancedSettings() {
      var that = this;
      this.isLoading = true;
      jQuery.post(
        secure_passkeys_params.url,
        {
          nonce: secure_passkeys_params.nonce,
          action: "secure_passkeys_get_advanced_settings",
        },
        function (data) {
          that.isLoading = false;

          if (data.data.defaults ?? false) {
            that.defaults = data.data.defaults;
          }

          if (data.success) {
            if (Object.keys(data.data.data).length > 0) {
              that.settings = data.data.data;
            }
          } else {
            if (data.data.missing_nonce) {
              if (data.data.message) {
                that.errorMessage = data.data.message;
              } else {
                that.errorMessage = "Something went wrong!";
              }
            }
          }
        },
        "JSON"
      );
    },
    updateSettings() {
      var that = this;
      this.errorMessage = "";
      this.successMessage = "";
      this.submitting = true;

      jQuery.post(
        secure_passkeys_params.url,
        {
          nonce: secure_passkeys_params.nonce,
          action: "secure_passkeys_update_advanced_settings",
          option: "display_settings",
          settings: that.settings,
        },
        function (data) {
          that.submitting = false;

          if (data.data.defaults ?? false) {
            that.defaults = data.data.defaults;
          }

          if (data.success == true) {
            that.successMessage = data.data.message;
            that.settings = data.data.data;
            that.submitted = true;
          } else {
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
  },
});

const routes = [
  {
    name: "general-settings",
    path: "/",
    component: generalSetings,
  },
  {
    name: "display-settings",
    path: "/display-settings",
    component: displaySettings,
  },
  {
    name: "advanced-settings",
    path: "/advanced-settings",
    component: advancedSettings,
  },
];

const router = new VueRouter({
  routes,
});

var app = new Vue({
  el: "#settings",
  router,
});
