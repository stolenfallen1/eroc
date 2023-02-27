<template>
  <div v-if="can('browse_purchaseRequestMaster')">
    <AppHeader :setting="setting" @change="changeTab" />
    <custom-table
      :data="setting"
      :tableData="tableData"
      :headers="headers"
      @view="viewRecord"
      @search="search"
      @add="addItem"
      @edit="editItem"
      @remove="removeConfirmation"
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
        {{ getStatus(item) }}
      </template>
      <template v-slot:approved_date="{ item }">
        {{ getApprovedDate(item) }}
      </template>
      <template v-slot:warehouse="{ item }">
        {{ item.warehouse.warehouse_description }}
      </template>
      <template v-slot:custom-action="{ item }">
        <v-icon 
          v-if="!checkPermission.includes('approve-btn')" 
          color="primary" 
          class="mr-1" 
          small 
          @click.stop="approvedPr(item)"
        >
          mdi-thumb-up-outline
        </v-icon>
        <v-icon 
          small color="success" 
          class="mr-1" 
          @click.stop="viewPR(item)"
        >
          mdi-eye-outline
        </v-icon>
      </template>
    </custom-table>
    <right-side-bar
      :isaction="isaction"
      :hide="checkPermission"
      :disabled="checkSideBtn"
      @resetFilters="resetFilters"
      @filterRecord="initialize"
      @add="addItem"
      @edit="editItem"
      @delete="removeConfirmation"
    >
      <template v-slot:side_filter>
        <DataFilter :filter="setting.filter" />
      </template>
      <template v-slot:side-action>
        <v-btn
          :disabled="!payload.id"
          width="100%"
          small
          color="primary"
          @click="approvedPr"
        >
          <v-icon class="mr-2" small> mdi-thumb-up-outline </v-icon>
          Approve
        </v-btn>
      </template>
    </right-side-bar>
    <DataForm
      :show="showForm"
      :isedit="isedit"
      :isapprove="isapprove"
      :payload="payload"
      @submit="forConfirmation"
      @close="(showForm = false), clearForm()"
    />
    <Confirmation
      @cancel="cancelConfirmation"
      @confirm="submit"
      :show="isconfirmation"
    />
    <SnackBar
      @close="isnotification = false"
      :data="notification"
      :show="isnotification"
    />
    <Remarks
      @cancel="isremarks = false"
      :show="isremarks"
      :payload="payload"
      @submit="approvePR"
    />
    <!-- <ApproveForm 
      :show="isapprove"
      :isedit="isedit"
      :payload="payload"
      @submit="forConfirmation"
      @close="(showForm = false), clearForm()"
    /> -->
  </div>
</template>
<script>
import Confirmation from "@global/components/Confirmation.vue";
import Remarks from "@global/components/Remarks.vue";
import SnackBar from "@global/components/SnackBar.vue";
import DataFilter from "../filter_forms/PurchaseRequest.vue";
import DataForm from "../forms/PurchaseRequest.vue";
import ApproveForm from "../forms/PurchaseRequest.vue";
import RightSideBar from "@mmis/components/pages/RightSideBar.vue";
import CustomTable from "@global/components/CustomTable.vue";
import PurchaseHelper from "@mmis/mixins/PurchaseHelper.vue";
import AppHeader from "@mmis/components/pages/procurements/AppHeader.vue";
import { mapGetters } from "vuex";
import {
  apiCreatePurchaseRequest,
  apiGetAllPurchaseRequest,
  apiUpdatePurchaseRequest,
  apiRemovePurchaseRequest,
  apiApprovePurchaseRequestItems,
} from "@mmis/api/procurements.api";
export default {
  mixins: [PurchaseHelper],
  components: {
    CustomTable,
    DataFilter,
    DataForm,
    RightSideBar,
    Confirmation,
    Remarks,
    SnackBar,
    AppHeader,
    ApproveForm,
  },
  data() {
    return {
      setting: {
        title: "Purchase request",
        keyword: "",
        loading: false,
        filter: {},
        tab: 0,
        param_tab: 1,
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
      // hideActions:[],
      isconfirmation: false,
      isnotification: false,
      isedit: false,
      isdelete: false,
      isaction: false,
      isapprove: false,
      isremarks: false,
      notification: {
        messages: [],
      },
      selected_item: {},
    };
  },
  methods: {
    viewPR(item) {},
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
    removeConfirmation() {
      this.isdelete = true;
      this.isconfirmation = true;
    },
    cancelConfirmation() {
      this.isdelete = false;
      this.isconfirmation = false;
    },
    async submit(code) {
      if (this.isdelete) return this.remove();
      if (this.isapprove) return this.checkPRItemsStatus();
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
        console.log(item.attachment, "item hhhhh");
        if (item.attachment) {
          fd.append(`items[${index}][attachment]`, item.attachment);
        }
        if (this.isedit && item["item_Id"]) {
          fd.append(`items[${index}][id]`, item.id);
        }
        fd.append(
          `items[${index}][item_Id]`,
          item["item_Id"] ? item["item_Id"] : item.id
        );
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
      if (this.isedit)
        res = await apiUpdatePurchaseRequest(this.payload.id, fd);
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
    changeTab(tab) {
      this.setting.param_tab = tab;
      this.initialize();
    },
    async initialize() {
      this.setting.loading = true;
      let params = this._createParams(this.tableData.options);
      params = params + this._createFilterParams(this.setting.filter);
      if (this.setting.keyword)
        params = params + "&keyword=" + this.setting.keyword;
      params = params + `&tab=${this.setting.param_tab}`;

      let res = await apiGetAllPurchaseRequest(params);
      console.log(res, "purchase");
      if (res.status == 200) {
        this.tableData.selected = [];
        this.clearForm();
        this.tableData.items = res.data.data;
        this.tableData.total = res.data.total;
        this.setting.loading = false;
      }
    },
    search() {
      this.tableData.options.page = 1;
      this.initialize();
    },
    getStatus(item) {
      if (this.setting.tab == 1) return "Approved";
      else return item.status.Status_description;
    },
    getApprovedDate(item) {
      if (this.setting.tab == 0) return "...";
      if (this.setting.tab == 1)
        return this._dateFormat(item.pr_DepartmentHead_ApprovedDate);
      if (this.setting.tab == 2 || this.setting.tab == 3)
        return this._dateFormat(item.pr_Branch_Level1_ApprovedDate);
    },
    resetFilters() {
      this.setting.filter = {};
      this.initialize();
    },
    editItem(item) {
      console.log(item, "edit")
      if(item){
        this.viewRecord(item)
      }
      setTimeout(() => {
        this.showForm = true;
        if (this.user) {
          this.payload.requested_by = this.user.name;
          this.payload.department = this.user.warehouse.warehouse_description;
        }
      }, 50);
    },
    addItem() {
      // this.payload.
      this.tableData.selected = [];
      this.isedit = false;
      this.clearForm();
      this.showForm = true;
      if (this.user) {
        this.payload.requested_by = this.user.name;
        this.payload.department = this.user.warehouse.warehouse_description;
      }
    },
    checkPRItemsStatus() {
      if (!this.checkApproveItems(this.payload)) {
        this.isconfirmation = false;
        this.isremarks = true;
        return;
      }
      this.approvePR();
    },
    approvedPr(item) {
      if(item){
        this.viewRecord(item)
      }
      setTimeout(() => {
        this.showForm = true;
        this.isapprove = true;
      }, 50);
    },
    async approvePR() {
      let res = await apiApprovePurchaseRequestItems(this.payload);
      if (res.status == 200) {
        console.log(this.payload.items, "approve PR");
        this.notification.messages = [];
        this.notification.messages.push("Record successfully saved");
        this.notification.color = "success";
        this.isnotification = true;
        this.isconfirmation = false;
        this.showForm = false;
        this.isedit = false;
        this.isapprove = false;
        this.isremarks = false;
        this.initialize();
      }
    },
    async remove(item) {
      let res = await apiRemovePurchaseRequest(this.payload.id);

      if (res.status == 200) {
        this.notification.messages = [];
        this.notification.messages.push("Record successfully removed");
        this.notification.color = "success";
        this.isnotification = true;
        this.isconfirmation = false;
        this.isdelete = false;
        this.initialize();
      }
    },
    viewRecord(item) {
      if (this.payload.id && item.id == this.payload.id && this.drawer) {
        this.payload = {};
        this.payload.requested_date = new Date();
        this.payload.items = [];
        this.tableData.selected = [];
        this.isedit = false;
        this.isapprove = false;
        this.isaction = false;
        return;
      }
      Object.assign(this.payload, item);
      this.isaction = true;
      if (this.payload.pr_RequestedBy == this.user.id) {
        this.isedit = true;
      }
      console.log(this.payload, "payloadddd");
    },
    clearForm() {
      if (!this.isedit) {
        this.tableData.selected = [];
        this.payload = {};
        this.payload.requested_date = new Date();
        this.payload.items = [];
        this.tableData.selected = [];
        this.isedit = false;
        this.isapprove = false;
        this.isaction = false;
      }
      // this.isedit = false
    },
  },
  created() {
    // this.checkPermission()
  },
  mounted() {},
  computed: {
    ...mapGetters(["drawer", "user", "prsn_settings"]),
    checkSideBtn() {
      if (this.checkPRStatus(this.payload)) {
        return ["edit", "delete"];
      }
      if (!this.isedit && this.user.id != this.payload.user_id) {
        return ["edit", "delete"];
      }
    },
    checkPermission() {
      let hideActions;
      if (this.drawer){
        hideActions = ["add-btn", "filter-btn", "floater-btn"];
        if ( !this.can("delete_purchaseRequestMaster") || this.hasActions(this.setting) )
          hideActions.push("delete");
        if ( !this.can("add_purchaseRequestMaster") || this.hasActions(this.setting) )
          hideActions.push("add");
        if ( !this.can("edit_purchaseRequestMaster") || this.hasActions(this.setting) )
          hideActions.push("edit");
        if (!this.can("read_purchaseRequestMaster")) hideActions.push("show");
        if (!this.isAuthorize("pr") || this.hasActions(this.setting))
          hideActions.push("approve");
      } 
      else {
        hideActions = ["floater-btn"];
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
        { text: "Category", value: "item_Category_Id" },
        { text: "Subcategory", value: "item_SubCategory_Id" },
        { text: "Pr. Status", value: "status" },
        { text: "Date Approved", value: "approved_date" },
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