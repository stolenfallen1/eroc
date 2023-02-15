<template>
  <v-simple-table fixed-header dense height="300px">
    <template v-slot:default>
      <thead>
        <tr>
          <th class="text-left">Item Code</th>
          <th class="text-left">Item Name / Description</th>
          <th class="text-left">Attachment</th>
          <th class="text-left">Qty</th>
          <th class="text-left">UOM</th>
          <th class="text-left">Action</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(item, index) in items" :key="index">
          <td>{{ item.id }}</td>
          <td>{{ item.item_Name }}</td>
          <td>
            <v-file-input
              v-model="item.attachment"
              style="min-width: 200px"
              solo
              dense
              prepend-icon=""
              hide-details="auto"
            ></v-file-input>
          </td>
          <td>
            <v-text-field
              v-model="item.quantity"
              style="max-width: 100px"
              solo
              dense
              hide-details="auto"
              type="number"
            ></v-text-field>
          </td>
          <td>
            <v-autocomplete
              v-model="item.unit"
              solo
              :items="units"
              item-text="name"
              item-value="id"
              dense
              hide-details="auto"
              attach
            >
            </v-autocomplete>
          </td>
          <td>
            <v-btn @click="removeItem(index)" text small color="primary">
              <v-icon> mdi-close </v-icon>
            </v-btn>
          </td>
        </tr>
      </tbody>
    </template>
  </v-simple-table>
</template>
<script>
import { mapGetters } from "vuex";
export default {
  props: {
    items: {
      type: Array,
      default: () => [],
    },
  },
  data() {
    return {};
  },
  methods: {
    removeItem(index) {
      this.items.splice(index, 1);
    },
  },
  computed: {
    ...mapGetters(["units"]),
  },
};
</script>