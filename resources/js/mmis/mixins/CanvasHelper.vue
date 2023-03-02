<script>
export default {
  methods:{
    hasActions(setting){
      if(setting.param_tab > 5) {
        return true
      }else{
        return false
      }
    },
    checkCanvasPayload(payload){
      let errors = []
      if(!payload.canvas_item_amount || payload.canvas_item_amount == 0 ){
        errors.push({message:"Price is required"})
      }
      if(!payload.vendor_id || payload.vendor_id == 0 ){
        errors.push({message:"Supplier is required"})
      }
      if(!payload.oum || payload.oum == 0 ){
        errors.push({message:"OUM is required"})
      }
      if(!payload.requested_date){
        errors.push({message:"Requested date is required"})
      }
      if(!payload.lead_time || payload.lead_time == 0){
        errors.push({message:"Lead time is required"})
      }

      return errors;
    }
  }
}
</script>