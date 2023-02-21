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
        {{ item.category ? item.category.name : "..." }}
      </template>
      <template v-slot:item_SubCategory_Id="{ item }">
        {{ item.subcategory ? item.subcategory.name : "..." }}
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
      :disabled="checkSideBtn"
      @resetFilters="resetFilters"
      @filterRecord="initialize"
      @add="addItem"
      @edit="editItem"
      @delete="editItem"
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
      @close="(showForm = false), clearForm()"
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
import PurchaseHelper from "@mmis/mixins/PurchaseHelper.vue";
import { mapGetters } from "vuex";
import {
  apiCreatePurchaseRequest,
  apiGetAllPurchaseRequest,
  apiUpdatePurchaseRequest,
} from "@mmis/api/procurements.api";
export default {
  mixins: [PurchaseHelper],
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
      notification: {
        messages: [],
      },
      selected_item: {},
    };
  },
  methods: {
    forConfirmation() {
      this.notification.messages = [];
      let errors = this.checkPRPayload(this.payload);
      if (errors.length) {
        errors.forEach((error) => {
          this.notification.messages.push(error.message);
          this.notification.color = "error";
          this.isnotification = true;
        });
        return;
      }
      console.log();
      this.isconfirmation = true;
    },
    removeConfirmation(){
      
    },
    async submit(code) {
      if (this.user.passcode != code || code == null) {
        this.notification.messages = [];
        this.notification.messages.push("Incorrect passcode");
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
        if (this.isedit && item['item_Id']) {
          fd.append(`items[${index}][id]`, item.id);
        }
        fd.append(`items[${index}][item_Id]`, item['item_Id']?item['item_Id']:item.id);
        fd.append(`items[${index}][item_Request_Qty]`, item.item_Request_Qty);
        fd.append(
          `items[${index}][item_Request_UnitofMeasurement_Id]`,
          item.item_Request_UnitofMeasurement_Id
        );
      });

      fd.append("pr_Justication", this.payload.pr_Justication ?? "");
      fd.append(
        "pr_Transaction_Date_Required",
        this.payload.pr_Transaction_Date_Required ?? ""
      );
      fd.append("pr_Priority_Id", this.payload.pr_Priority_Id ?? "");
      fd.append("invgroup_id", this.payload.invgroup_id ?? "");
      fd.append("item_Category_Id", this.payload.item_Category_Id ?? "");
      fd.append("item_SubCategory_Id", this.payload.item_SubCategory_Id ?? "");
      if (this.prsn_settings.isActive) {
        fd.append("pr_Document_Prefix", this.payload.pr_Document_Prefix ?? "");
        fd.append("pr_Document_Number", this.payload.pr_Document_Number ?? "");
        fd.append("pr_Document_Suffix", this.payload.pr_Document_Suffix ?? "");
      }

      let res = {};
      if (this.isedit) res = await apiUpdatePurchaseRequest(this.payload.id, fd);
      else res = await apiCreatePurchaseRequest(fd);

      console.log(res);
      if (res.status == 200) {
        this.notification.messages = [];
        this.notification.messages.push("Record successfully saved");
        this.notification.color = "success";
        this.isnotification = true;
        this.isconfirmation = false;
        this.showForm = false;
        this.isedit = false;
        this.initialize();
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
      this.showForm = true;
      if (this.user) {
        this.payload.requested_by = this.user.name;
        this.payload.department = this.user.warehouse.warehouse_Description;
      }
    },
    addItem() {
      // this.payload.
      this.isedit = false;
      this.clearForm();
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
      if (this.payload.pr_RequestedBy == this.user.id) {
        this.isedit = true;
      }
      console.log(this.payload, "payloadddd");
    },
    clearForm() {
      if (!this.isedit) {
        this.payload = {};
        this.payload.requested_date = new Date();
        this.payload.items = [];
      }
      // this.isedit = false
    },
  },
  mounted() {},
  computed: {
    ...mapGetters(["drawer", "user", "prsn_settings"]),
    checkSideBtn(){
      if(this.checkPRStatus(this.payload)){
        return ['edit', 'delete']
      }
      if(!this.isedit && this.user.id != this.payload.user_id){
        return ['edit', 'delete']
      }
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