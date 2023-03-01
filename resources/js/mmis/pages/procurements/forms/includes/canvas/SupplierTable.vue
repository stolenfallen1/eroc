<template>
  <v-simple-table fixed-header dense height="300px">
    <template v-slot:default>
      <thead>
        <tr>
          <th class="text-left">Supplier name</th>
          <th class="text-left">Price</th>
          <th class="text-left" >Qty</th>
          <th class="text-left" >Unit</th>
          <th class="text-left" >Lead time</th>
          <th class="text-left" >Attachment</th>
          <th class="text-left" >Recommend</th>
          <th class="text-left">Action</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(canvas, index) in canvases" :key="index">
          <td>{{canvas.id}}</td>
          <td>{{canvas.item_master.item_name}}</td>
          <td>{{canvas.item_Branch_Level1_Approved_Qty}}</td>
          <td>{{getUOM(item.item_Branch_Level1_Approved_UnitofMeasurement_Id)}}</td>
          <td>
            <v-icon @click="addCanvas(canvas)" color="success">mdi-plus</v-icon>
          </td>
        </tr>
      </tbody>
    </template
  ></v-simple-table>
</template>
<script>
export default {
  props:{
    canvases:{
      type:Array,
      default:()=>[]
    }
  },
  methods:{
    addCanvas(item){
      this.$emit("addCanvas", item);
    },
    getUOM(uom){
      let uom_name = '...'
      this.$store.getters.units.map(unit=>{
        if(unit.id == parseInt(uom)){
          uom_name = unit.name
        }
      })
      return uom_name;
    }
  },
  computed:{
    
  }
};
</script>