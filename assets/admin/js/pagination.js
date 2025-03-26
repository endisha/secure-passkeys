Vue.component("paginate", {
  template: "#pagination",
  props: ["pages", "count", "current"],
  data() {
    return {
      showItems: 5,
    };
  },
  computed: {
    nextPage: function () {
      let nextPage = this.current + 1;
      if (nextPage > this.pages) {
        nextPage = this.pages;
      }
      return nextPage;
    },
    prevPage: function () {
      let previousPage = this.current - 1;
      if (previousPage < 1) {
        previousPage = 1;
      }
      return previousPage;
    },
    loopNumbers: function () {
      let start, end, items, i, beforeCurrent;

      items = [];
      start = 1;
      end = this.pages;
      if (this.pages > this.showItems) {
        beforeCurrent = Math.ceil(this.showItems / 2);
        start = this.current - beforeCurrent;
        if (start < 1) {
          start = 1;
        }
        end = start + this.showItems - 1;
        if (end > this.pages) {
          end = this.pages;
          start = end - this.showItems + 1;
        }
      }
      for (i = start; i <= end; i++) {
        items.push(i);
      }
      return items;
    },
  },
  methods: {
    goPage(page) {
      this.$emit("navigate", page);
    },
  },
});
