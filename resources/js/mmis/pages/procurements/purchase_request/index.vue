<template>
  <div>
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
      :hide="drawer ? ['add-btn', 'filter', 'floater-btn'] : ['floater-btn']"
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
      <template v-slot:daterequested="{ item }">
        {{ _dateFormat(item.daterequested) }}
      </template>
      <template v-slot:item_Category_Id="{ item }">
        {{ item.category ? item.category.name : '...' }}
      </template>
      <template v-slot:item_SubCategory_Id="{ item }">
        {{ item.subcategory ? item.subcategory.name : '...' }}
      </template>
      <template v-slot:status="{ item }">
        {{ item.status.Status_description }}
      </template>
      <template v-slot:warehouse="{ item }">
        {{ item.warehouse.warehouse_Description }}
      </template>
    </custom-table>
    <right-side-bar
      :hide="['filter']"
      :disabled="isedit?[]:['edit']"
      @resetFilters="resetFilters"
      @filterRecord="initialize"
      @add="addItem"
      @edit="editItem"
    >
      <template v-slot:side_filter>
        <DataFilter :filter="setting.filter" />
      </template>
    </right-side-bar>
    <DataForm
      :show="showForm"
      :isedit="isedit"
      :payload="payload"
      @submit="forConfirmation"
      @close="showForm = false, clearForm()"
    />
    <Confirmation
      @cancel="isconfirmation = false"
      @confirm="submit"
      :show="isconfirmation"
    />
    <SnackBar
      @close="isnotification = false"
      :data="notification"
      :show="isnotification"
    />
  </div>
</template>
<script>
import Confirmation from "@global/components/Confirmation.vue";
import SnackBar from "@global/components/SnackBar.vue";
import DataFilter from "../filter_forms/PurchaseRequest.vue";
import DataForm from "../forms/PurchaseRequest.vue";
import RightSideBar from "@mmis/components/pages/RightSideBar.vue";
import CustomTable from "@global/components/CustomTable.vue";
import { mapGetters } from "vuex";
import {
  apiCreatePurchaseRequest,
  apiGetAllPurchaseRequest,
  apiUpdatePurchaseRequest,
} from "@mmis/api/procurements.api";
export default {
  components: {
    CustomTable,
    DataFilter,
    DataForm,
    RightSideBar,
    Confirmation,
    SnackBar,
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
        items: [],
      },
      isconfirmation: false,
      isnotification: false,
      isedit: false,
      notification: {},
      selected_item: {},
    };
  },
  methods: {
    forConfirmation() {
      this.isconfirmation = true;
    },
    async submit(code) {
      if (this.user.passcode != code || code == null) {
        this.notification.message = "Incorrect passcode";
        this.notification.color = "error";
        this.isnotification = true;
        return;
      }
      let fd = new FormData();

      if (this.payload.attachments && this.payload.attachments.length) {
        this.payload.attachments.forEach((attachment) => {
          fd.append("attachments[]", attachment);
        });
      }

      this.payload.items.forEach((item, index) => {
        if (item.attachment) {
          fd.append(`items[${index}][attachment]`, item.attachment);
        }
        fd.append(`items[${index}][item_Id]`, item.id);
        fd.append(`items[${index}][item_Request_Qty]`, item.quantity);
        fd.append(
          `items[${index}][item_Request_UnitofMeasurement_Id]`,
          item.unit
        );
      });

      fd.append("justication", this.payload.justication);
      fd.append("required_date", this.payload.required_date);
      fd.append("pr_Priority_Id", this.payload.priority);
      fd.append("invgroup_id", this.payload.invgroup_id);
      fd.append("item_Category_Id", this.payload.item_Category_Id);
      fd.append("item_SubCategory_Id", this.payload.item_SubCategory_Id);
      fd.append("pr_Document_Prefix", this.payload.pr_Document_Prefix);
      fd.append("pr_Document_Number", this.payload.pr_Document_Number);
      fd.append("pr_Document_Suffix", this.payload.pr_Document_Suffix);

      let res = {} 
      if(this.isedit) res = await apiUpdatePurchaseRequest(this.payload.id, fd)
      else res = await apiCreatePurchaseRequest(fd);
      console.log(res);
      if (res.data.message == "success") {
        this.notification.message = "Record successfully saved";
        this.notification.color = "success";
        this.isnotification = true;
        this.isconfirmation = false;
        this.showForm = false;
        this.isedit = false;
        this.initialize()
      }
    },
    async initialize() {
      this.setting.loading = true;
      let params = this._createParams(this.tableData.options);
      params = params + this._createFilterParams(this.setting.filter);
      if (this.setting.keyword)
        params = params + "&keyword=" + this.setting.keyword;
      params = params + "&withoutadmin=true";
      let res = await apiGetAllPurchaseRequest(params);
      console.log(res, "purchase");
      if (res.status == 200) {
        this.tableData.items = res.data.data;
        this.tableData.total = res.data.total;
        this.setting.loading = false;
      }
    },
    search() {
      this.tableData.options.page = 1;
      this.initialize();
    },
    resetFilters() {
      this.setting.filter = {};
      this.initialize();
    },
    editItem() {
      this.showForm = true
      if (this.user) {
        this.payload.requested_by = this.user.name;
        this.payload.department = this.user.warehouse.warehouse_Description;
      }
    },
    addItem() {
      // this.payload.
      this.isedit = false;
      this.clearForm()
      this.showForm = true;
      if (this.user) {
        this.payload.requested_by = this.user.name;
        this.payload.department = this.user.warehouse.warehouse_Description;
      }
    },
    remove(item) {
      axios.delete(`users/${item.id || item}`).then(({ data }) => {
        this.successNotification(`Employee Deleted`);
        this.initialize();
      });
    },
    viewRecord(item) {
      Object.assign(this.payload, item);
      if(this.payload.pr_RequestedBy == this.user.id){
        this.isedit = true
      }
      console.log(this.payload, "payloadddd")
    },
    clearForm(){
      if(!this.isedit){
        this.payload = {}
        this.payload.requested_date = new Date()
        this.payload.items = []
      }
      // this.isedit = false
    }
  },
  mounted() {
    
  },
  computed: {
    ...mapGetters(["drawer", "user"]),
    headers() {
      let headerItems = [
        {
          text: "P.R No.",
          sortable: false,
          value: "prnumber",
        },
        { text: "Date Request", value: "daterequested" },
        { text: "Requesting Dept.", value: "warehouse" },
        { text: "Category", value: "item_Category_Id" },
        { text: "Subcategory", value: "item_SubCategory_Id" },
        { text: "Pr. Status", value: "status" },
        { text: "Date Approved", value: "pr_Branch_Level1_ApprovedDate" },
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