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
      :hide="['filter']"
    >
      <template v-slot:status="{ item }">
        <v-switch
          @click.stop="changeStatus(item)"
          class="mt-0"
          v-model="item.status"
          inset
          dense
          hide-details
        ></v-switch>
      </template>
      <template v-slot:updated_at="{ item }">
        <span>{{ defaultDate(item.updated_at) }}</span>
      </template>
      <template v-slot:custom-action="{ item }">
        <v-tooltip bottom>
          <template v-slot:activator="{ on, attrs }">
            <v-icon
              color="success"
              v-bind="attrs"
              v-on="on"
              class="mr-2"
              @click="makeAdmin(item)"
              >mdi-account-multiple
            </v-icon>
          </template>
          <span>Set as Admin</span>
        </v-tooltip>
      </template>
    </custom-table>
  </div>
</template>
<script>
import CustomTable from '@global/components/CustomTable.vue'
export default {
  components:{
    CustomTable
  },
  data() {
    return {
      setting: {
        title: "Samples",
        keyword: "",
        loading: false,
        filter: {},
      },
      tableData: {
        headers: [
          {
            text: "First Name",
            align: "start",
            sortable: false,
            value: "first_name",
          },
          { text: "Last Name", value: "last_name" },
          { text: "Email", value: "email" },
          { text: "Address", value: "address_1" },
          { text: "Status", value: "status" },
          { text: "Last Updated", value: "updated_at" },
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
};
</script>