var overview = Vue.component("overview", {
  template: "#app",
  data() {
    return {
      title: "Overview",
      data: {
        passkeys_count: 0,
        users_count: 0,
        challenges_count: 0,
        logs_count: 0,
        authenticators: {},
        last_login_activity: {},
      },
      isLoading: false,
      errorMessage: "",
    };
  },
  mounted() {
    this.getOverview();
  },
  methods: {
    getOverview() {
      var that = this;
      this.isLoading = true;
      jQuery.post(
        secure_passkeys_params.url,
        {
          nonce: secure_passkeys_params.nonce,
          action: "secure_passkeys_adminarea_overview",
        },
        function (data) {
          that.isLoading = false;
          if (data.success) {
            if (Object.keys(data.data).length > 0) {
              that.data = data.data;
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
  },
});

const routes = [
  {
    name: "overview",
    path: "/",
    component: overview,
  },
];

const router = new VueRouter({
  routes,
});

var app = new Vue({
  el: "#overview",
  router,
});
