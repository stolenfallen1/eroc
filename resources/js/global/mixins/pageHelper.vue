<script>
import moment from "moment";
export default {
  methods: {
    _push(child, params = {}) {
      let link = this.$router.resolve({
        name: child,
        params: params,
      });

      let path = link.route.path;

      if (this.$route.path == path) return;

      this.$router.push({ path: path });
    },
    _getters(key) {
      return this.$store.getters[key];
    },
    _createParams(params, old_params) {
      let param = "";
      let sortby;
      let sorttype;
      // let sortby = params.sortBy[0];
      // let sorttype = params.sortDesc[0];
      if (params.sortBy && params.sortBy[0]) sortby = params.sortBy[0];
      if (params.sortDesc && params.sortDesc[0]) sorttype = params.sortDesc[0];
      let page = params.page;
      let perpage = params.itemsPerPage;
      param += `page=${page}&per_page=${perpage}`;
      if (sortby) {
        param += `&sortBy=${sortby}/${sorttype ? "asc" : "desc"}`;
      }
      if (old_params) param += `&${old_params}`;
      return param;
    },
    _createFilterParams(filters, old_params = true) {
      let params = "";
      for (const item in filters) {
        // if(filters[item])
        if (filters[item] != null) {
          params = params + "&" + item + "=" + filters[item];
        }
      }
      if (old_params) return params;
      else return params.substring(1);
    },
    _dateFormat(date) {
      return moment(date).format("MM-DD-YYYY");
    },
  },
};
</script>