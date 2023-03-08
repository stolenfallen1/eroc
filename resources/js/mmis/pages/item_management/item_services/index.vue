<template>
  <div v-if="can('browse_ItemMaster')">
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
      <template v-slot:item_InventoryGroup_Id="{ item }">
        {{ item.item_InventoryGroup_Id ? item.item_group.name : "..." }}
      </template>
      <template v-slot:item_Category_Id="{ item }">
        {{ item.item_Category_Id ? item.item_category.name : "..." }}
      </template>

      <template v-slot:item_UnitOfMeasure_Id="{ item }">
        {{ item.item_UnitOfMeasure_Id ? item.unit.name : "..." }}
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
        <v-icon small color="success" class="mr-1" @click.stop="viewPR(item)">
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
    <!-- showForm -->
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
  </div>
</template>
<script>
import DataForm from "../../item_management/forms/item_and_services.vue";
import RightSideBar from "@mmis/components/pages/RightSideBar.vue";
import ItemsHelper from "@mmis/mixins/ItemsHelper.vue";
import AppHeader from "@mmis/components/pages/item_management/AppHeader.vue";
import { mapGetters } from "vuex";
import {
  apiGetAllItemsAndServices,
  apiCreateItemandServices,
  apiUpdateItemandServices,
  apiRemoveItemsAndServices,
} from "@mmis/api/items_and_services.api";
export default {
  mixins: [ItemsHelper],
  components: {
    DataForm,
    RightSideBar,
    AppHeader,
  },
  data() {
    return {
      setting: {
        title: "Items and Services",
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
      payload: {},
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
   
    forConfirmation() {
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
      let res = {};
      if (this.isedit)
        res = await apiUpdateItemandServices(this.payload.id, this.payload);
      else
        res = await apiCreateItemandServices(this.payload);
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
      console.log(tab);
    },

    async initialize() {
      this.$store.dispatch("fetchItemGroups");
      this.$store.dispatch("fetchUnits");
      this.$store.dispatch("fetchBrand");
      this.$store.dispatch("fetchDrugAdministration");
      this.$store.dispatch("fetchAntibioticClass");
      this.$store.dispatch("fetchGenericNames");
      this.$store.dispatch("fetchTherapeuticClass");
      this.setting.loading = true;
      let params = this._createParams(this.tableData.options);
      params = params + this._createFilterParams(this.setting.filter);
      if (this.setting.keyword)
        params = params + "&keyword=" + this.setting.keyword;
      params = params + `&tab=${this.setting.param_tab}`;

      let res = await apiGetAllItemsAndServices(params);
      console.log(res, "item and services");
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

    resetFilters() {
      this.setting.filter = {};
      this.initialize();
    },

    editItem(item) {
      
      if (item) {
        console.log(item, "edit");
        this.viewRecord(item);
      }
      setTimeout(() => {
        this.showForm = true;
      }, 50);
    },

    addItem() {
      // this.payload.
      this.tableData.selected = [];
      this.isedit = false;
      this.clearForm();
     
      this.showForm = true;
      if (this.user) {
      }
    },

    async remove(item) {
      let res = await apiRemoveItemsAndServices(this.payload.id);
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
      Object.assign(this.payload, item);

      this.isaction = true;
      this.isedit = true;
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
      if (this.drawer) {
        hideActions = ["add-btn", "filter-btn", "floater-btn"];
        if (!this.can("delete_ItemMaster") || this.hasActions(this.setting))
          hideActions.push("delete");
        if (!this.can("add_ItemMaster") || this.hasActions(this.setting))
          hideActions.push("add");
        if (!this.can("edit_ItemMaster") || this.hasActions(this.setting))
          hideActions.push("edit");
        if (!this.can("read_ItemMaster")) hideActions.push("show");
        if (!this.isAuthorize("pr") || this.hasActions(this.setting))
          hideActions.push("approve");
      } else {
        hideActions = ["floater-btn"];
        if (!this.can("delete_ItemMaster") || this.hasActions(this.setting))
          hideActions.push("delete-btn");
        if (!this.can("add_ItemMaster") || this.hasActions(this.setting))
          hideActions.push("add-btn");
        if (!this.can("edit_ItemMaster") || this.hasActions(this.setting))
          hideActions.push("edit-btn");
        if (!this.can("read_ItemMaster")) hideActions.push("show-btn");
        if (!this.isAuthorize("pr") || this.hasActions(this.setting))
          hideActions.push("approve-btn");
      }
      return hideActions;
    },

    headers() {
      let headerItems = [
        {
          text: "Code",
          sortable: false,
          value: "id",
        },
        { text: "Item Category", value: "item_Category_Id" },
        { text: "Item Name", value: "item_name" },
        { text: "Description", value: "item_Description" },
        { text: "Unit", value: "item_UnitOfMeasure_Id" },
        { text: "Barcode ID", value: "item_Barcode" },
      ];
      if (!this.drawer) {
        headerItems.push({ text: "Action", value: "action" });
      }
      return headerItems;
    },
  },
};
</script>