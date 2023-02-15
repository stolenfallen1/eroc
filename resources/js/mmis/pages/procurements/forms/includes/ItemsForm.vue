<template>
  <v-dialog
    v-model="show"
    hide-overlay
    width="700"
    transition="dialog-top-transition"
    scrollable
    persistent
  >
    <v-card>
      <v-card-text>
        <custom-table
          class="pr-form-if-bg-white"
          :data="setting"
          :tableData="tableData"
          :headers="tableData.headers"
          :show_select="true"
          :single_select="false"
          @search="search"
          @fetchPage="initialize"
          :height="'59vh'"
          :hide="['add-btn', 'filter', 'floater-btn']"
        >
          <template v-slot:item_OnHand="{ item }">
            {{item.ware_house_item.item_OnHand}}
          </template>
        </custom-table>
        <div class="pr-form-actions">
          <v-btn class="mr-2" color="error" @click="$emit('cancel')"
            >Cancel</v-btn
          >
          <v-btn color="primary" @click="$emit('selected', tableData.selected)"
            >Select</v-btn
          >
        </div>
      </v-card-text>
    </v-card>
  </v-dialog>
</template>
<script>
import CustomTable from "@global/components/CustomTable.vue";
import { apiGetAllBuildItems } from "@global/api/items";
export default {
  components: {
    CustomTable,
  },
  props: {
    payload: {
      type: Object,
      default: () => {},
    },
    show: {
      type: Boolean,
      default: () => false,
    },
  },
  data() {
    return {
      setting: {
        title: "List of Items",
        keyword: "",
        loading: false,
        filter: {},
      },
      tableData: {
        headers: [
          {
            text: "Code",
            sortable: true,
            value: "id",
          },
          {
            text: "Item name",
            sortable: true,
            value: "item_Name",
          },
          {
            text: "Current stocks",
            sortable: true,
            value: "item_OnHand",
          },
        ],
        items: [],
        options: {
          itemsPerPage: 15,
        },
        total: 0,
        selected: [],
      },
    };
  },
  methods: {
    initialize() {},
    search() {},
    async fetchBuildItems() {
      let params = `warehouse_id=${this.$store.getters.user.warehouse.id}&category_id=${this.payload.category}&subcategory_id=${this.payload.sub_category}`;
      let res = await apiGetAllBuildItems(params);
      this.tableData.items = res.data.data;
      this.tableData.total = res.data.total;
      console.log(this.tableData.items, "items");
    },
  },
  watch: {
    show: {
      handler(val) {
        if (val) {
          this.fetchBuildItems();
        }
      },
    },
  },
};
</script>
