<template>
  <v-simple-table fixed-header dense height="300px">
    <template v-slot:default>
      <thead class="table-head">
        <tr>
          <th rowspan="2" colspan="1" class="text-center">Code</th>
          <th rowspan="2" colspan="1" class="text-center">Item Name</th>
          <th rowspan="1" colspan="2" class="text-center">Approved</th>
          <th rowspan="2" class="text-center">Price</th>
          <th rowspan="2" class="text-center">Total</th>
          <th rowspan="2" class="text-center">Date Approved</th>
          <th rowspan="1" colspan="2" class="text-center">Canvas Supplier</th>
          <th rowspan="2" class="text-center">Action</th>
        </tr>
        <tr>
          <th class="text-center">Qty</th>
          <th class="text-center">Unit</th>
          <th class="text-center">Canvas</th>
          <th class="text-center">Recommended</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(item, index) in items" :key="index">
          <td class="text-center">{{ item.id }}</td>
          <td class="text-center">{{ item.item_master.item_name }}</td>
          <td class="text-center">{{ item.item_Branch_Level1_Approved_Qty }}</td>
          <td class="text-center">
            {{ getUOM(item.item_Branch_Level1_Approved_UnitofMeasurement_Id) }}
          </td>
          <td class="text-center">{{ item.price }}</td>
          <td class="text-center">{{ item.total }}</td>
          <td class="text-center">{{ _dateFormat(item.pr_Branch_Level1_ApprovedDate) }}</td>
          <td class="text-center">
            <v-icon @click="addCanvas(item)" color="success">mdi-plus</v-icon>
          </td>
          <td class="text-center">{{ item.recommended_supplier?item.recommended_supplier.vendor_name: '...' }}</td>
          <td class="text-center">
            <v-icon v-if="checkbox1" @click="checkbox1=!checkbox1" color="primary">mdi-checkbox-outline</v-icon>
            <v-icon v-else @click="checkbox1=!checkbox1">mdi-checkbox-blank-outline</v-icon>
          </td>
        </tr>
      </tbody>
    </template></v-simple-table
  >
</template>
<script>
export default {
  props: {
    items: {
      type: Array,
      default: () => [],
    },
  },
  data() {
    return{
      checkbox1:true
    }
  },
  methods: {
    addCanvas(item) {
      this.$emit("addCanvas", item);
    },
    getUOM(uom) {
      let uom_name = "...";
      this.$store.getters.units.map((unit) => {
        if (unit.id == parseInt(uom)) {
          uom_name = unit.name;
        }
      });
      return uom_name;
    },
  },
  computed: {},
};
</script>
<style lang="scss" scoped>
.table-head {
  th {
    border: 1px solid rgb(105, 103, 103) !important;
  }
}
</style>