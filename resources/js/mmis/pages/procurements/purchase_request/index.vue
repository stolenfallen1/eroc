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
      <template v-slot:daterequested="{ item }">
        {{ _dateFormat(item.daterequested) }}
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
      @resetFilters="resetFilters"
      @filterRecord="initialize"
      @add="addItem"
    >
      <template v-slot:side_filter>
        <DataFilter :filter="setting.filter" />
      </template>
    </right-side-bar>
    <DataForm
      :show="showForm"
      :payload="payload"
      @submit="forConfirmation"
      @close="showForm = false"
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
import { apiCreatePurchaseRequest, apiGetAllPurchaseRequest } from "@mmis/api/procurements.api";
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
      notification: {},
    };
  },
  methods: {
    forConfirmation() {
      this.isconfirmation = true;
    },
    async submit(code) {
      console.log(code)
      if (this.user.passcode != code || code == null) {
        this.notification.message = "Incorrect passcode";
        this.notification.color = "error";
        this.isnotification = true;
        return;
      }
      let fd = new FormData();
      this.payload.attachments.forEach((attachment) => {
        fd.append("attachments[]", attachment);
      });

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

      let res = await apiCreatePurchaseRequest(fd);
      console.log(res)
      if (res.data.message == "success") {
        this.notification.message = "Record successfully saved";
        this.notification.color = "success";
        this.isnotification = true;
        this.isconfirmation = false;
        this.showForm = false;
      }
    },
    async initialize() {
      this.setting.loading = true;
      let params = this._createParams(this.tableData.options);
      params = params + this._createFilterParams(this.setting.filter);
      if (this.setting.keyword)
        params = params + "&keyword=" + this.setting.keyword;
      params = params + "&withoutadmin=true";
      let res = await apiGetAllPurchaseRequest(params)
      console.log(res, "purchase")
      if(res.status == 200){
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
    editItem(payload) {
      this.goTo("employee-edit", { id: payload.id });
    },
    addItem() {
      // this.payload.
      this.showForm = true;
    },
    remove(item) {
      axios.delete(`users/${item.id || item}`).then(({ data }) => {
        this.successNotification(`Employee Deleted`);
        this.initialize();
      });
    },
    viewRecord(item) {
      console.log(item, "selected item");
    },
  },
  mounted() {
    if (this.user) {
      this.payload.requested_by = this.user.name;
      this.payload.department = this.user.warehouse.warehouse_Description;
    }
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
        { text: "Item group", value: "itemgroupid" },
        { text: "Category", value: "category" },
        { text: "Pr. Status", value: "status" },
        { text: "Date Approved", value: "updated_at" },
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