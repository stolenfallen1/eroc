<template>
  <div>
    <custom-table
      :data="setting"
      :tableData="tableData"
      :headers="headers"
      @search="search"
      @add="addItem"
      @edit="editItem"
      @remove="remove"
      @fetchPage="initialize"
      @resetFilters="resetFilters"
      @filterRecord="initialize"
      :hide="['floater-btn']"
    >
      <template v-slot:custom_filter>
        <DataFilter :filter="setting.filter" />
      </template>
      <template v-slot:daterequested="{ item }">
        {{ _dateFormat(item.daterequested) }}
      </template>
      <template v-slot:category="{ item }">
        {{ item.category.categoryname }}
      </template>
      <template v-slot:updated_at="{ item }">
        <span>{{ _dateFormat(item.updated_at) }}</span>
      </template>
    </custom-table>
    <right-side-bar />
    <DataForm :show="showForm" :payload="payload" @close="showForm = false" />
  </div>
</template>
<script>
import DataFilter from "../filter_forms/PurchaseRequest.vue";
import RightSideBar from "@mmis/components/pages/RightSideBar.vue";
import DataForm from "../forms/PurchaseRequest.vue";
import CustomTable from "@global/components/CustomTable.vue";
import { mapGetters } from "vuex"
export default {
  components: {
    CustomTable,
    DataFilter,
    DataForm,
    RightSideBar,
  },
  data() {
    return {
      setting: {
        title: "Purchase request",
        keyword: "",
        loading: false,
        filter: {},
      },
      tableData: {
        items: [],
        options: {
          itemsPerPage: 15,
        },
        total: 0,
        selected: [],
      },
      loading: false,
      showForm: false,
      payload: {
        requested_date: new Date(),
        items: [
          {
            code: "dtte222",
            name: "test",
          },
        ],
      },
    };
  },
  methods: {
    initialize() {
      this.setting.loading = true;
      let params = this._createParams(this.tableData.options);
      params = params + this._createFilterParams(this.setting.filter);
      if (this.setting.keyword)
        params = params + "&keyword=" + this.setting.keyword;
      params = params + "&withoutadmin=true";
      axios.get(`purchase-request?${params}`).then(({ data }) => {
        this.tableData.items = data.data;
        this.tableData.total = data.total;
        this.setting.loading = false;
      });
    },
    search() {
      this.tableData.options.page = 1;
      this.initialize();
    },
    resetFilters() {
      this.setting.filter = {};
      this.initialize();
    },
    editItem(payload) {
      this.goTo("employee-edit", { id: payload.id });
    },
    addItem() {
      this.showForm = true;
    },
    remove(item) {
      axios.delete(`users/${item.id || item}`).then(({ data }) => {
        this.successNotification(`Employee Deleted`);
        this.initialize();
      });
    },
  },
  computed: {
    ...mapGetters(["drawer"]),
    headers() {
      let headerItems =[
        {
          text: "P.R No.",
          sortable: false,
          value: "prnumber",
        },
        { text: "Date Request", value: "daterequested" },
        { text: "Requesting Dept.", value: "departmentid" },
        { text: "Item group", value: "itemgroupid" },
        { text: "Category", value: "category" },
        { text: "Pr. Status", value: "prstatus" },
        { text: "Date Approved", value: "updated_at" },
        { text: "Remarks", value: "prremarks" },
      ];
      if(!this.drawer){
        headerItems.push({ text: "Action", value: "action" })
      }
      return headerItems
    },
  },
};
</script>