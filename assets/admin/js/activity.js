var activity = Vue.component("activity", {
  template: "#app",
  data() {
    return {
      title: "activity",
      list: [],
      count: -1,
      current: 1,
      pages: 0,
      isLoading: false,
      errorMessage: "",
      successMessage: "",
      filters: {
        user_id: "",
        created_at: "",
        log_type: "",
        action_by: "",
        ip_address: "",
      },
      missingNonce: false,
    };
  },
  mounted() {
    this.loadList();
    var self = this;
    if (jQuery("html").attr("dir") == "rtl") {
      (function (factory) {
        if (typeof define === "function" && define.amd) {
          define(["../jquery.ui.datepicker"], factory);
        } else {
          factory(jQuery.datepicker);
        }
      })(self.datepicker);
    }
    jQuery(".created_at").datepicker({
      dateFormat: "yy-mm-dd",
      onSelect: function (selectedDate, datePicker) {
        self.filters.created_at = selectedDate;
      },
    });

    jQuery("#user_id")
      .autocomplete({
        source: function (request, response) {
          jQuery.ajax({
            url: secure_passkeys_params.url,
            type: "POST",
            dataType: "json",
            data: {
              action: "secure_passkeys_adminarea_filter_users",
              keyword: request.term,
              model: "logs",
              nonce: secure_passkeys_params.nonce,
            },
            success: function (data) {
              if (data.success) {
                response(
                  jQuery.map(data.data.results, function (item) {
                    return {
                      label: item.text,
                      value: item.id,
                      name: item.name,
                    };
                  })
                );
              } else {
                response([]);
              }
            },
          });
        },
        minLength: 2,
        select: function (event, ui) {
          jQuery("#user_id").val(ui.item.name);
          self.filters.user_id = ui.item.value;
          return false;
        },
      })
      .autocomplete("instance")._renderItem = function (ul, item) {
      var formattedText = item.label.replace(/\|\|/g, "<br>");

      return jQuery("<li>")
        .append(
          "<div style='padding: 5px;font-size: 12px;'>" +
            formattedText +
            "</div>"
        )
        .appendTo(ul);
    };
  },
  computed: {
    hasFilters() {
      return (
        this.filters.user_id != "" ||
        this.filters.created_at != "" ||
        this.filters.log_type != "" ||
        this.filters.action_by != "" ||
        this.filters.ip_address != ""
      );
    },
  },
  methods: {
    loadList(clearMessages = true) {
      var that = this;
      this.count = -1;
      this.isLoading = true;
      if (clearMessages) {
        this.successMessage = "";
        this.errorMessage = "";
      }
      jQuery.post(
        secure_passkeys_params.url,
        {
          nonce: secure_passkeys_params.nonce,
          action: "secure_passkeys_adminarea_activity_list",
          page: this.current,
          filters: this.filters,
        },
        function (data) {
          that.isLoading = false;
          if (data.success) {
            that.list = data.data.records;
            that.count = data.data.count;
            that.current = data.data.current;
            that.pages = data.data.pages;
          } else {
            that.count = 0;
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
    _delete(id) {
      var message =
        secure_passkeys_params?.i18n?.delete_message ??
        "Are you sure you want to delete the record?";
      if (!confirm(message)) {
        return;
      }
      var that = this;
      this.deleting = true;
      this.deletingId = id;
      this.errorMessage = "";
      this.successMessage = "";
      jQuery.post(
        secure_passkeys_params.url,
        {
          nonce: secure_passkeys_params.nonce,
          action: "secure_passkeys_adminarea_delete_passkey",
          id: id,
        },
        function (data) {
          that.deleting = false;
          that.deletingId = 0;
          if (data.success) {
            that.successMessage = data.data.message;
            that.reloadPage(that.current);
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
    statusClass(item) {
      return "label label-" + item.log_type;
    },
    reloadPage(page) {
      this.current = page;
      this.loadList(false);
    },
    loadPage(page) {
      this.current = page;
      this.loadList();
    },
    filterList() {
      this.current = 1;
      if (this.filters.user_id == "" && jQuery("#user_id").val() != "") {
        jQuery("#user_id").val("").removeData("selected-id").focus();
      }
      this.loadList();
    },
    filterReset() {
      this.current = 1;
      this.filters = {
        user_id: "",
        created_at: "",
        log_type: "",
        action_by: "",
        ip_address: "",
      };
      if (this.filters.user_id == "" && jQuery("#user_id").val() != "") {
        jQuery("#user_id").val("").removeData("selected-id").focus();
      }
      this.loadList();
    },
    datepicker(datepicker) {
      datepicker.regional["ar"] = {
        closeText: "إغلاق",
        prevText: "&#x3C;السابق",
        nextText: "التالي&#x3E;",
        currentText: "اليوم",
        monthNames: [
          "يناير",
          "فبراير",
          "مارس",
          "أبريل",
          "مايو",
          "يونيو",
          "يوليو",
          "أغسطس",
          "سبتمبر",
          "أكتوبر",
          "نوفمبر",
          "ديسمبر",
        ],
        monthNamesShort: [
          "يناير",
          "فبراير",
          "مارس",
          "أبريل",
          "مايو",
          "يونيو",
          "يوليو",
          "أغسطس",
          "سبتمبر",
          "أكتوبر",
          "نوفمبر",
          "ديسمبر",
        ],
        dayNames: [
          "الأحد",
          "الاثنين",
          "الثلاثاء",
          "الأربعاء",
          "الخميس",
          "الجمعة",
          "السبت",
        ],
        dayNamesShort: [
          "الأحد",
          "الاثنين",
          "الثلاثاء",
          "الأربعاء",
          "الخميس",
          "الجمعة",
          "السبت",
        ],
        dayNamesMin: ["ح", "ن", "ث", "ر", "خ", "ج", "س"],
        weekHeader: "أسبوع",
        dateFormat: "dd/mm/yy",
        firstDay: 6,
        isRTL: true,
        showMonthAfterYear: false,
        yearSuffix: "",
      };
      datepicker.setDefaults(datepicker.regional["ar"]);
      return datepicker.regional["ar"];
    },
  },
});

const routes = [
  {
    name: "activity",
    path: "/",
    component: activity,
  },
];

const router = new VueRouter({
  routes,
});

var app = new Vue({
  el: "#activity",
  router,
});
