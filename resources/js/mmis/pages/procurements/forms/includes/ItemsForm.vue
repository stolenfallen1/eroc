<template>
  <v-dialog
    v-model="show"
    hide-overlay
    width="500"
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
        <div class="pr-form-actions">
          <v-btn class="mr-2" color="error" @click="$emit('cancel')">Cancel</v-btn>
          <v-btn color="primary" @click="$emit('selected', tableData.selected)">Select</v-btn>
        </div>
      </v-card-text>
    </v-card>
  </v-dialog>
</template>
<script>
import CustomTable from "@global/components/CustomTable.vue";
export default {
  components: {
    CustomTable,
  },
  props: {
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
            value: "code",
          },
          {
            text: "Item name",
            sortable: true,
            value: "item_name",
          },
          {
            text: "Current stocks",
            sortable: true,
            value: "stocks",
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
  },
};
</script>
