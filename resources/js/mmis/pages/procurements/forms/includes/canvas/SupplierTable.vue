<template>
  <div>
    <v-simple-table fixed-header dense height="300px">
      <template v-slot:default>
        <thead>
          <tr>
            <th class="text-left">Supplier name</th>
            <th class="text-left">Price</th>
            <th class="text-left" >Qty</th>
            <th class="text-left" >Unit</th>
            <th class="text-left" >Lead time</th>
            <th class="text-center" >Attachment</th>
            <th class="text-center" >Recommend</th>
            <th class="text-center">Action</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(canvas, index) in canvases" :key="index">
            <td>{{canvas.vendor.vendor_Name}}</td>
            <td>{{canvas.canvas_item_amount}}</td>
            <td>{{canvas.canvas_Item_Qty}}</td>
            <td>{{canvas.unit.name}}</td>
            <td>{{canvas.canvas_lead_time + ' Days'}}</td>
            <td class="text-center">
              <v-icon @click="viewAttachment(canvas)" color="success">mdi-eye</v-icon>
            </td>
            <td class="text-center">
              <v-icon v-if="canvas.isRecommended == 1" @click="setIsRecommended(canvas)" color="primary">mdi-checkbox-outline</v-icon>
              <v-icon v-else @click="setIsRecommended(canvas)">mdi-checkbox-blank-outline</v-icon>
            </td>
            <td class="text-center">
              <v-icon color="error" @click="$emit('delete', canvas)">mdi-delete</v-icon>
            </td>
          </tr>
        </tbody>
      </template
    ></v-simple-table>
    <AttachmentViewer @close="showviewfile=false" :show="showviewfile" :files="files" />
  </div>
</template>
<script>
export default {
  props:{
    canvases:{
      type:Array,
      default:()=>[]
    }
  },
  data(){
    return {
      showviewfile: false,
      files:[]
    }
  },
  methods:{
    setIsRecommended(canvas){
      this.$emit('setIsRecommended', canvas)
    },
    viewAttachment(canvas){
      this.files = canvas.attachments
      console.log(this.files,"attach")
      setTimeout(() => {
        this.showviewfile = true
      }, 50);
    },
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