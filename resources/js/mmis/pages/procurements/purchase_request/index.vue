<template>
  <div>
    <custom-table
      :data="setting"
      :tableData="tableData"
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
      <template v-slot:daterequested="{item}">
        {{_dateFormat(item.daterequested)}}
      </template>
      <template v-slot:category="{item}">
        {{item.category.categoryname}}
      </template>
      <template v-slot:updated_at="{ item }">
        <span>{{ _dateFormat(item.updated_at) }}</span>
      </template>
    </custom-table>
  </div>
</template>
<script>
import DataFilter from "../filter_forms/PurchaseRequest.vue";
import CustomTable from "@global/components/CustomTable.vue";
export default {
  components: {
    CustomTable,
    DataFilter,
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
        headers: [
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
          { text: "Action", value: "action" },
        ],
        items: [],
        options: {
          itemsPerPage: 15,
        },
        total: 0,
        selected: [],
      },
      loading: false,
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
      this.goTo("employee-create");
    },
    remove(item) {
      axios.delete(`users/${item.id || item}`).then(({ data }) => {
        this.successNotification(`Employee Deleted`);
        this.initialize();
      });
    },
  },
};
</script>