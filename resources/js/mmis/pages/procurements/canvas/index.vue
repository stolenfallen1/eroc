<template>
  <div>
    <AppHeader :setting="setting" @change="changeTab" />
    <custom-table
      :data="setting"
      :tableData="tableData"
      :headers="headers"
      @view="viewRecord"
      @search="search"
      @add="addItem"
      @edit="editItem"
      @remove="remove"
      @fetchPage="initialize"
      @resetFilters="resetFilters"
      @filterRecord="initialize"
      @refresh="initialize"
      :hide="checkPermission"
    >
      <template v-slot:custom_filter>
        <DataFilter :filter="setting.filter" />
      </template>
      <template v-slot:prnumber="{ item }">
        {{
          item.pr_Document_Prefix +
          "-" +
          item.pr_Document_Number +
          "-" +
          item.pr_Document_Suffix
        }}
      </template>
      <template v-slot:warehouse="{ item }">
        {{ item.warehouse.warehouse_description }}
      </template>
      <template v-slot:daterequested="{ item }">
        {{ _dateFormat(item.daterequested) }}
      </template>
      <template v-slot:item_Category_Id="{ item }">
        {{ item.category ? item.category.name : "..." }}
      </template>
      <template v-slot:item_SubCategory_Id="{ item }">
        {{ item.subcategory ? item.subcategory.name : "..." }}
      </template>
    </custom-table>
    <right-side-bar
      :hide="checkPermission"
      @resetFilters="resetFilters"
      @filterRecord="initialize"
      @add="addItem"
    >
      <template v-slot:side_filter>
        <DataFilter :filter="setting.filter" />
      </template>
    </right-side-bar>
    <DataForm :show="showForm" :pr_id="pr_id" @close="showForm = false" />
  </div>
</template>
<script>
import AppHeader from "@mmis/components/pages/canvas/AppHeader.vue"
import DataFilter from "../filter_forms/PurchaseRequest.vue";
import DataForm from "../forms/Canvas.vue";
import RightSideBar from "@mmis/components/pages/RightSideBar.vue";
import CustomTable from "@global/components/CustomTable.vue";
import CanvasHelper from "@mmis/mixins/CanvasHelper.vue"
import {apiGetAllPurchaseRequest} from "@mmis/api/procurements.api"
import { mapGetters } from "vuex";
export default {
  mixins:[CanvasHelper],
  components: {
    CustomTable,
    DataFilter,
    DataForm,
    RightSideBar,
    AppHeader
  },
  data() {
    return {
      setting: {
        title: "Canvas",
        keyword: "",
        loading: false,
        filter: {},
        tab: 0,
        param_tab: 5,
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
      pr_id: 0,
      // payload: {
      //   requested_date: new Date(),
      //   items: [
      //     {
      //       code: "dtte222",
      //       name: "test",
      //     },
      //   ],
      // },
    };
  },
  methods: {
    async initialize() {
      let params = this._createParams(this.tableData.options);
      params = params + this._createFilterParams(this.setting.filter);
      if (this.setting.keyword)
        params = params + "&keyword=" + this.setting.keyword;
      params = params + `&tab=${this.setting.param_tab}`;
      
      let res = await apiGetAllPurchaseRequest(params);
      console.log(res, "purchase");
      if (res.status == 200) {
        this.tableData.selected = [];
        this.tableData.items = res.data.data;
        this.tableData.total = res.data.total;
        this.setting.loading = false;
      }
    },
    changeTab(tab) {
      this.setting.param_tab = tab;
      this.initialize();
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
    viewRecord(item) {
      this.pr_id = item.id;
      console.log(this.pr_id, "selected item");
    },
  },
  computed: {
    ...mapGetters(["drawer"]),
    checkPermission() {
      let hideActions;
      if (this.drawer){
        hideActions = ["add-btn", "filter-btn", "floater-btn", "delete","edit"];
        if ( !this.can("add_canvassMaster") || this.hasActions(this.setting) )
          hideActions.push("add");
        if (!this.can("read_purchaseRequestMaster")) hideActions.push("show");
        if (!this.isAuthorize("pr") || this.hasActions(this.setting))
          hideActions.push("approve");
      } 
      else {
        hideActions = ["floater-btn", "delete-btn", "edit-btn"];
        if ( !this.can("delete_purchaseRequestMaster") || this.hasActions(this.setting) )
          hideActions.push("delete-btn");
        if ( !this.can("add_purchaseRequestMaster") || this.hasActions(this.setting) )
          hideActions.push("add-btn");
        if ( !this.can("edit_purchaseRequestMaster") || this.hasActions(this.setting) )
          hideActions.push("edit-btn");
        if (!this.can("read_purchaseRequestMaster")) hideActions.push("show-btn");
        if (!this.isAuthorize("pr") || this.hasActions(this.setting))
          hideActions.push("approve-btn");
      } 
      return hideActions;
    },
    headers() {
      let headerItems = [
        {
          text: "P.R No.",
          sortable: false,
          value: "prnumber",
        },
        { text: "Date Request", value: "daterequested" },
        { text: "Requesting Dept.", value: "warehouse" },
        { text: "Item group", value: "item_Category_Id" },
        { text: "Category", value: "item_SubCategory_Id" },
        { text: "Status", value: "prstatus" },
        // { text: "Date Approved", value: "updated_at" },
        { text: "Remarks", value: "pr_Justication" },
      ];
      if (!this.drawer) {
        headerItems.push({ text: "Action", value: "action" });
      }
      return headerItems;
    },
  },
};
</script>